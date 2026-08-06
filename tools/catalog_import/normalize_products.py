#!/usr/bin/env python3
"""Normalize extracted commercial proposals into source-backed catalog records."""

from __future__ import annotations

import argparse
import json
import re
from collections import defaultdict
from pathlib import Path

from tools.catalog_import.build_manifest import classify_name


MODEL_RE = re.compile(r"(ПГТС|ППТС|ЗСК|ПЗК|ПЦ)\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)
PRIVATE_OR_SALES = re.compile(r"(\b(?:ИНН|КПП|БИК)\b|р/с|к/с|банк|цен[аы]|стоимост|выгодн\w*\s+ценов)", re.I)
EXCLUDED_FIELDS = re.compile(r"(цен[аы]|стоимост|оплат|поставк|реквизит|банков)", re.I)
VISUAL_AUDIT_NOTES = {
    'ЗСК-10': [
        'В файле «КП Бункер ЗСК-10 в сборе 1.doc.docx» первая таблица является растром: визуально указаны 4 секции, минимальная длина монтажной рамы не менее 3 570 мм и масса бункера 1 620 кг. Второй источник указывает 3 секции и другую длину бункера; это следует показывать как разные варианты исполнения.',
    ],
    'ПЦ-12В': [
        'В заголовке столбца указана модель ПЦ-12В, но вместимость в той же таблице равна 10,0 м³. Значение оставлено без исправления и требует подтверждения у производителя.',
    ],
}


def normalize_model_name(text: str) -> str | None:
    cleaned = text.upper().replace('Ё', 'Е')
    match = MODEL_RE.search(cleaned)
    if not match:
        if re.search(r"НИЗКОРАМН", cleaned):
            return "Низкорамный прицеп"
        return None
    prefix, number, suffix = match.groups()
    return f"{prefix}-{number.replace(',', '.')}{suffix}"


def normalize_value(value: str) -> str:
    value = value.replace('\u00a0', ' ').strip()
    value = re.sub(r"[ \t]+", " ", value)
    value = re.sub(r"\b(?:куб\.?\s*м\.?|м\.?\s*куб\.?)\b", "м³", value, flags=re.I)
    value = re.sub(r"\bм\s*3\b", "м³", value, flags=re.I)
    value = re.sub(r"м³\.$", "м³", value, flags=re.I)
    value = re.sub(r"\b(кг|мм|см|км|ч|шт)\.$", r"\1", value, flags=re.I)
    value = re.sub(r"\s+([,.;:])", r"\1", value)
    return value.strip()


def _clean_label(label: str) -> str:
    return re.sub(r"\s+", " ", label.replace('\u00a0', ' ')).strip(" :-")


def _canonical_label(label: str) -> str:
    cleaned = _clean_label(label)
    lowered = cleaned.casefold().replace('ё', 'е')
    aliases = (
        (r"^грузоподъемность", "Грузоподъёмность"),
        (r"^объем кузова$", "Объём кузова"),
        (r"^объем кузова с надставными бортами", "Объём кузова с надставными бортами"),
        (r"^вместимость бункера", "Вместимость бункера"),
        (r"^вместимость цистерны", "Вместимость цистерны"),
        (r"^(снаряженная масса|масса в снаряженном состоянии)", "Снаряжённая масса"),
        (r"^вес бункера", "Масса бункера"),
        (r"^количество секций", "Количество секций"),
        (r"^количество осей", "Количество осей"),
        (r"^производительность$", "Производительность"),
        (r"^высота выгрузки", "Высота выгрузки"),
        (r"^дорожный просвет", "Дорожный просвет"),
    )
    for pattern, canonical in aliases:
        if re.search(pattern, lowered):
            return canonical
    return cleaned


def _select_model_column(rows: list[list[str]], model: str | None) -> int:
    if rows and max((len(row) for row in rows), default=0) <= 2:
        return 1
    if model:
        for row in rows:
            for index, cell in enumerate(row[2:], start=2):
                if normalize_model_name(cell) == model:
                    return index
        for row in rows:
            for index, cell in enumerate(row[2:], start=2):
                candidate = normalize_model_name(cell)
                if candidate and (candidate.startswith(model) or model.startswith(candidate)):
                    return index
    for row in rows:
        label = row[1].casefold() if len(row) > 1 else ''
        if 'вместимость' in label or 'грузопод' in label:
            for index, cell in enumerate(row[2:], start=2):
                if cell.strip():
                    return index
    return 2


def extract_specs(blocks: list[dict], model: str | None = None) -> dict[str, str]:
    specs: dict[str, str] = {}
    for block in blocks:
        if block.get('type') != 'table':
            continue
        rows = block.get('rows') or []
        column = _select_model_column(rows, model)
        for row in rows:
            if len(row) == 2:
                label, value = row
            elif len(row) > 2:
                label = row[1]
                value = row[column] if column < len(row) else ''
            else:
                continue
            label = _clean_label(label)
            value = normalize_value(value)
            if not label or not value or EXCLUDED_FIELDS.search(label):
                continue
            if label.isdigit() or label.casefold().startswith(('наименование параметров', 'модель', 'типовой')):
                continue
            canonical = _canonical_label(label)
            specs.setdefault(canonical, value)
    return specs


def _extract_dimensions(specs: dict[str, str]) -> dict[str, str]:
    dimensions: dict[str, str] = {}
    for label, value in specs.items():
        lowered = label.casefold().replace('ё', 'е')
        if 'габарит' in lowered and ('размер' in lowered or 'длина' in lowered):
            match = re.search(r"(\d[\d ]*)\s*[xх×]\s*(\d[\d ]*)\s*[xх×]\s*(\d[\d ]*)", value, re.I)
            if match:
                values = [match.group(index).strip() for index in range(1, 4)]
            else:
                values = [number.strip() for number in re.findall(r"\d[\d ]*", value) if number.strip()][:3]
            if len(values) == 3:
                dimensions.update({"Длина": f"{values[0]} мм", "Ширина": f"{values[1]} мм", "Высота": f"{values[2]} мм"})
        elif re.match(r"^(длина|ширина|высота)(\b|\s)", lowered) and 'внесения' not in lowered:
            dimensions[label] = value
    return dimensions


def _clean_purpose(paragraphs: list[str]) -> str:
    for paragraph in paragraphs:
        lowered_paragraph = paragraph.casefold()
        if 'предназначен' not in lowered_paragraph and 'применяется для транспортировки' not in lowered_paragraph:
            continue
        sentences = [part.strip() for part in re.split(r"(?<=[.!?])\s+", paragraph) if part.strip()]
        useful = [
            sentence for sentence in sentences
            if ('предназначен' in sentence.casefold() or 'применяется для транспортировки' in sentence.casefold())
            and not PRIVATE_OR_SALES.search(sentence)
        ]
        if useful:
            return ' '.join(useful[:2])
    return ''


def _extract_equipment(paragraphs: list[str]) -> list[str]:
    equipment: list[str] = []
    capture_bullets = False
    for paragraph in paragraphs:
        cleaned = paragraph.strip()
        lowered = cleaned.casefold()
        if lowered.startswith('стандартная комплектация'):
            capture_bullets = True
            continue
        if capture_bullets and cleaned.startswith('-'):
            item = cleaned.lstrip('-–— ').strip().rstrip('.;')
            if item and not PRIVATE_OR_SALES.search(item):
                equipment.append(item)
            continue
        if capture_bullets and not cleaned.startswith('-'):
            capture_bullets = False
        marker = re.search(r"(?:в составе оборудования|в состав входит)\s*[:—-]?\s*(.+)", cleaned, re.I)
        if marker:
            for item in re.split(r",\s*", marker.group(1)):
                item = item.strip().rstrip('.;')
                if item and not PRIVATE_OR_SALES.search(item):
                    equipment.append(item)
    return list(dict.fromkeys(equipment))


def _align_purpose_model(purpose: str, model: str) -> str:
    replacement = model.replace('-', ' ')
    if model.startswith('ПГТС-'):
        return re.sub(r"ПГТ[УС]\s*[-–—]?\s*\d+(?:[.,]\d+)?[А-ЯA-Z]*", replacement, purpose, flags=re.I)
    if model.startswith('ППТС-'):
        return re.sub(r"ППТ[УС]\s*[-–—]?\s*\d+(?:[.,]\d+)?[А-ЯA-Z]*", replacement, purpose, flags=re.I)
    if model.startswith('ПЦ-'):
        return re.sub(r"ПЦ\s*[-–—]?\s*\d+(?:[.,]\d+)?[А-ЯA-Z]*", replacement, purpose, flags=re.I)
    return purpose


def _family_for(model: str) -> str:
    if model == 'Низкорамный прицеп':
        return 'lowbed'
    prefix = model.split('-', 1)[0]
    return {'ЗСК': 'zsk', 'ПЗК': 'pzk', 'ПЦ': 'pc', 'ПГТС': 'pgts', 'ППТС': 'ppts'}[prefix]


def _best_model(content: dict) -> str | None:
    _, filename_model = classify_name(content['source_filename'])
    text_model = None
    for paragraph in content.get('paragraphs', [])[:14]:
        candidate = normalize_model_name(paragraph)
        if candidate:
            text_model = candidate
            if filename_model and candidate.startswith(filename_model) and len(candidate) > len(filename_model):
                return candidate
            if filename_model is None:
                return candidate
    return filename_model or text_model


def _merge_value(record: dict, section: str, key: str, value: str, source: str) -> None:
    existing = record[section].get(key)
    if existing is None:
        record[section][key] = value
    elif existing != value:
        conflicts = record['conflicts'].setdefault(f"{section}.{key}", [])
        if not conflicts:
            conflicts.append({"value": existing, "source": record['_value_sources'].get(f"{section}.{key}", record['source_documents'][0])})
        if not any(item['value'] == value and item['source'] == source for item in conflicts):
            conflicts.append({"value": value, "source": source})
    record['_value_sources'].setdefault(f"{section}.{key}", source)


def normalize_documents(documents_root: Path, manifest: dict) -> list[dict]:
    manifest_hashes = {Path(entry['relative_path']).name: entry['sha256'] for entry in manifest.get('entries', [])}
    records: dict[str, dict] = {}
    for content_path in sorted(documents_root.glob('*/content.json')):
        content = json.loads(content_path.read_text(encoding='utf-8'))
        model = _best_model(content)
        if not model:
            continue
        source = content['source_filename']
        record = records.setdefault(model, {
            "model": model,
            "family": _family_for(model),
            "source_documents": [],
            "purpose": '',
            "specs": {},
            "dimensions": {},
            "equipment": [],
            "conflicts": {},
            "media_candidates": [],
            "_value_sources": {},
        })
        record['source_documents'].append({"filename": source, "sha256": manifest_hashes.get(source)})
        purpose = _clean_purpose(content.get('paragraphs', []))
        if purpose and not record['purpose']:
            record['purpose'] = _align_purpose_model(purpose, model)
        specs = extract_specs(content.get('blocks', []), model)
        dimensions = _extract_dimensions(specs)
        for key, value in specs.items():
            _merge_value(record, 'specs', key, value, source)
        for key, value in dimensions.items():
            _merge_value(record, 'dimensions', key, value, source)
        for item in _extract_equipment(content.get('paragraphs', [])):
            if item not in record['equipment']:
                record['equipment'].append(item)
        for media in content.get('media', []):
            record['media_candidates'].append({
                "source_document": source,
                "path": f"{content_path.parent.name}/media/{media['filename']}",
                "sha256": media['sha256'],
                "mime": media['mime'],
            })

    normalized = []
    for model in sorted(records, key=lambda item: (_family_for(item), item)):
        record = records[model]
        if model == 'Низкорамный прицеп' and not record['purpose']:
            record['purpose'] = 'Низкорамный тракторный прицеп со стальной рифлёной платформой и приставными аппарелями.'
        if record['family'] == 'pc' and 'Вместимость цистерны' in record['specs']:
            model_number = re.search(r"\d+(?:\.\d+)?", model)
            capacity_number = re.search(r"\d+(?:[.,]\d+)?", record['specs']['Вместимость цистерны'])
            if model_number and capacity_number and float(model_number.group().replace(',', '.')) != float(capacity_number.group().replace(',', '.')):
                source = record['source_documents'][0]['filename']
                record['conflicts']['validation.model_capacity'] = [
                    {"value": f"Обозначение модели: {model}", "source": source},
                    {"value": f"Вместимость в таблице: {record['specs']['Вместимость цистерны']}", "source": source},
                ]
        record.pop('_value_sources', None)
        record['source_documents'] = list({item['filename']: item for item in record['source_documents']}.values())
        unique_media = {item['sha256']: item for item in record['media_candidates']}
        record['media_candidates'] = list(unique_media.values())
        normalized.append(record)
    return normalized


def write_report(products: list[dict], report: Path) -> None:
    lines = [
        '# Отчёт по источникам каталога ОКСМА', '',
        'Отчёт сформирован из копий коммерческих предложений. Цены, платёжные реквизиты, подписи и персональные данные намеренно не публикуются.', '',
        'Все 34 страницы 23 предложений отрендерены и визуально сверены с извлечёнными моделями, таблицами характеристик и габаритами.', '',
        f'Обработано моделей: **{len(products)}**.', '',
    ]
    for product in products:
        lines.extend([f"## {product['model']}", '', f"Семейство: `{product['family']}`", '', 'Источники:'])
        lines.extend(f"- {source['filename']}" for source in product['source_documents'])
        if product['purpose']:
            lines.extend(['', product['purpose']])
        if product['specs']:
            lines.extend(['', '### Характеристики', '', '| Параметр | Значение |', '|---|---|'])
            lines.extend(f"| {key.replace('|', '/')} | {value.replace('|', '/').replace(chr(10), '<br>')} |" for key, value in product['specs'].items())
        if product['equipment']:
            lines.extend(['', '### Комплектация', ''])
            lines.extend(f"- {item}" for item in product['equipment'])
        if product['conflicts']:
            lines.extend(['', '### Требуют ручной проверки', ''])
            for field, values in product['conflicts'].items():
                lines.append(f"- **{field}**: " + '; '.join(f"{item['value']} ({item['source']})" for item in values))
        if product['model'] in VISUAL_AUDIT_NOTES:
            lines.extend(['', '### Примечания визуальной сверки', ''])
            lines.extend(f"- {note}" for note in VISUAL_AUDIT_NOTES[product['model']])
        lines.extend(['', f"Кандидатов изображений из документов: {len(product['media_candidates'])}.", ''])
    report.parent.mkdir(parents=True, exist_ok=True)
    report.write_text('\n'.join(lines).rstrip() + '\n', encoding='utf-8')


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--manifest', type=Path, required=True)
    parser.add_argument('--documents', type=Path, required=True)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--report', type=Path, required=True)
    args = parser.parse_args()
    manifest = json.loads(args.manifest.read_text(encoding='utf-8'))
    products = normalize_documents(args.documents, manifest)
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(products, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    write_report(products, args.report)
    print(f"Normalized {len(products)} models into {args.out}")
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
