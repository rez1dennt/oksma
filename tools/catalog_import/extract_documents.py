#!/usr/bin/env python3
"""Extract ordered text, tables and embedded media from catalog DOCX files."""

from __future__ import annotations

import argparse
import hashlib
import json
import mimetypes
import shutil
import zipfile
from pathlib import Path

from docx import Document
from docx.document import Document as DocumentObject
from docx.table import Table, _Cell
from docx.text.paragraph import Paragraph


def sha256_bytes(content: bytes) -> str:
    return hashlib.sha256(content).hexdigest()


def iter_block_items(parent):
    """Yield top-level Paragraph and Table instances in document order."""
    if isinstance(parent, DocumentObject):
        parent_element = parent.element.body
        parent_object = parent
    elif isinstance(parent, _Cell):
        parent_element = parent._tc
        parent_object = parent
    else:
        raise TypeError(f"Unsupported parent type: {type(parent)!r}")

    for child in parent_element.iterchildren():
        if child.tag.endswith('}p'):
            yield Paragraph(child, parent_object)
        elif child.tag.endswith('}tbl'):
            yield Table(child, parent_object)


def _table_rows(table: Table) -> list[list[str]]:
    return [[cell.text.strip() for cell in row.cells] for row in table.rows]


def extract_document(path: Path, media_dir: Path) -> dict:
    path = Path(path)
    media_dir = Path(media_dir)
    media_dir.mkdir(parents=True, exist_ok=True)
    document = Document(path)

    paragraphs: list[str] = []
    tables: list[list[list[str]]] = []
    blocks: list[dict] = []
    for item in iter_block_items(document):
        if isinstance(item, Paragraph):
            text = item.text.strip()
            if text:
                paragraphs.append(text)
                blocks.append({"type": "paragraph", "text": text})
        else:
            rows = _table_rows(item)
            tables.append(rows)
            blocks.append({"type": "table", "rows": rows})

    relationship_targets = []
    for relation_id, relation in sorted(document.part.rels.items()):
        relationship_targets.append({
            "id": relation_id,
            "type": relation.reltype,
            "target": relation.target_ref,
            "external": bool(relation.is_external),
        })

    media = []
    with zipfile.ZipFile(path) as archive:
        media_entries = sorted(name for name in archive.namelist() if name.startswith('word/media/') and not name.endswith('/'))
        for index, entry in enumerate(media_entries, start=1):
            content = archive.read(entry)
            original_name = Path(entry).name
            target_name = original_name
            target = media_dir / target_name
            if target.exists() and target.read_bytes() != content:
                target_name = f"{index:02d}-{original_name}"
                target = media_dir / target_name
            target.write_bytes(content)
            media.append({
                "filename": target_name,
                "source_entry": entry,
                "mime": mimetypes.guess_type(original_name)[0] or "application/octet-stream",
                "size": len(content),
                "sha256": sha256_bytes(content),
            })

    return {
        "source_filename": path.name,
        "paragraphs": paragraphs,
        "tables": tables,
        "blocks": blocks,
        "relationship_targets": relationship_targets,
        "media": media,
    }


def _document_id(source_kind: str, relative: Path) -> str:
    digest = hashlib.sha1(f"{source_kind}:{relative.as_posix()}".encode('utf-8')).hexdigest()[:12]
    return f"doc-{digest}"


def extract_tree(docx_source: Path, converted_source: Path, out: Path) -> tuple[int, list[dict]]:
    out.mkdir(parents=True, exist_ok=True)
    errors = []
    count = 0
    roots = (("native", docx_source), ("converted", converted_source))
    for source_kind, root in roots:
        if not root.exists():
            continue
        for path in sorted(root.rglob('*.docx'), key=lambda item: item.relative_to(root).as_posix().casefold()):
            relative = path.relative_to(root)
            document_id = _document_id(source_kind, relative)
            document_out = out / document_id
            media_dir = document_out / 'media'
            try:
                content = extract_document(path, media_dir)
                content.update({
                    "document_id": document_id,
                    "source_kind": source_kind,
                    "source_relative_path": relative.as_posix(),
                })
                document_out.mkdir(parents=True, exist_ok=True)
                (document_out / 'content.json').write_text(
                    json.dumps(content, ensure_ascii=False, indent=2) + '\n', encoding='utf-8'
                )
                count += 1
            except Exception as error:  # preserve the exact source and failure for review
                if document_out.exists():
                    shutil.rmtree(document_out)
                errors.append({"source": str(path), "error": f"{type(error).__name__}: {error}"})
    return count, errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--docx-source', type=Path, required=True)
    parser.add_argument('--converted-source', type=Path, required=True)
    parser.add_argument('--out', type=Path, required=True)
    args = parser.parse_args()
    count, errors = extract_tree(args.docx_source, args.converted_source, args.out)
    error_file = args.out.parent / 'extraction-errors.json'
    error_file.write_text(json.dumps(errors, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f"Extracted {count} documents; {len(errors)} error(s).")
    return 1 if errors else 0


if __name__ == '__main__':
    raise SystemExit(main())
