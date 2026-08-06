from __future__ import annotations

import argparse
import json
from pathlib import Path


CATEGORY_BY_FAMILY = {
    "lowbed": "polupricepy",
    "pc": "polupricepy-cisterny",
    "pgts": "polupricepy",
    "ppts": "polupricepy",
    "pzk": "zagruzchiki-suhih-kormov",
    "zsk": "zagruzchiki-suhih-kormov",
}

SUBTITLE_BY_FAMILY = {
    "lowbed": "Низкорамный тракторный прицеп",
    "pc": "Полуприцеп-цистерна",
    "pgts": "Полуприцеп самосвальный тракторный",
    "ppts": "Полуприцеп самосвальный тракторный",
    "pzk": "Прицепной загрузчик сухих кормов",
    "zsk": "Загрузчик сухих кормов",
}

BENEFITS_BY_FAMILY = {
    "lowbed": ["Изготовление под задачу", "Доставка по России", "Гарантия качества"],
    "pc": ["Самозагрузка и перемешивание", "Комплектация под задачу", "Доставка по России"],
    "pgts": ["Надёжная ходовая часть", "Задняя выгрузка", "Доставка по России"],
    "ppts": ["Перевозка сельхозгрузов", "Задняя выгрузка", "Сервисная поддержка"],
    "pzk": ["Бережная перевозка кормов", "Регулируемая выгрузка", "Гарантия качества"],
    "zsk": ["Точная выгрузка кормов", "Изготовление под шасси", "Сервисная поддержка"],
}


def compact_summary(text: str, limit: int = 185) -> str:
    text = " ".join(text.split()).strip()
    if len(text) <= limit:
        return text
    shortened = text[: limit - 1].rsplit(" ", 1)[0].rstrip(".,;:")
    return f"{shortened}."


def declaration_ids(family: str) -> list[str]:
    if family in {"pc", "pzk"}:
        return ["feed-trailers-2026"]
    if family in {"pgts", "ppts"}:
        return ["tractor-trailers-2026"]
    return []


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build PHP catalog data from verified proposal extraction")
    parser.add_argument("--products", default=".tmp/catalog-import/products.normalized.json")
    parser.add_argument("--selections", default="tools/catalog_import/image_selections.json")
    parser.add_argument("--output", default="data/imported-products.php")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    products = json.loads(Path(args.products).read_text(encoding="utf-8"))
    selections = json.loads(Path(args.selections).read_text(encoding="utf-8"))

    slugs_by_category: dict[str, list[str]] = {}
    for item in products:
        category = CATEGORY_BY_FAMILY[item["family"]]
        slugs_by_category.setdefault(category, []).append(selections[item["model"]]["slug"])

    output: dict[str, dict] = {}
    for item in products:
        model = item["model"]
        family = item["family"]
        slug = selections[model]["slug"]
        category = CATEGORY_BY_FAMILY[family]
        siblings = slugs_by_category[category]
        position = siblings.index(slug)
        related = [candidate for candidate in siblings[position + 1 :] + siblings[:position] if candidate != slug][:3]
        purpose = " ".join((item.get("purpose") or SUBTITLE_BY_FAMILY[family]).split())

        product = {
            "slug": slug,
            "category": category,
            "name": model,
            "subtitle": SUBTITLE_BY_FAMILY[family],
            "sku": model,
            "badge": "Популярная модель" if slug == "zsk-10" else "",
            "summary": compact_summary(purpose),
            "description": purpose,
            "images": [f"/assets/images/products/{family}/{slug}-1.webp"],
            "benefits": BENEFITS_BY_FAMILY[family],
            "specs": item.get("specs") or {"Модель": model},
            "dimensions": item.get("dimensions") or {},
            "equipment": item.get("equipment") or [],
            "related": related,
            "seo_title": f"{model} — {SUBTITLE_BY_FAMILY[family].lower()} ОКСМА",
            "seo_description": f"Характеристики, размеры и комплектация {model}. Запросите расчёт стоимости и срок изготовления.",
            "source_documents": [doc["filename"] for doc in item.get("source_documents", [])],
        }
        documents = declaration_ids(family)
        if documents:
            product["document_ids"] = documents
        output[slug] = product

    payload = json.dumps(output, ensure_ascii=False, indent=4)
    php = """<?php

declare(strict_types=1);

// Generated from the verified commercial-proposal archive by
// tools/catalog_import/build_php_catalog.py. Do not add prices here.
return json_decode(<<<'JSON'
""" + payload + """
JSON, true, 512, JSON_THROW_ON_ERROR);
"""
    Path(args.output).write_text(php, encoding="utf-8", newline="\n")


if __name__ == "__main__":
    main()
