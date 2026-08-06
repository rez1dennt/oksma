#!/usr/bin/env python3
"""Build a deterministic inventory of an extracted catalog archive."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
from pathlib import Path


MODEL_PATTERNS = (
    ("pgts", "ПГТС", re.compile(r"ПГТС\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)),
    ("ppts", "ППТС", re.compile(r"ППТС\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)),
    ("zsk", "ЗСК", re.compile(r"ЗСК\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)),
    ("pzk", "ПЗК", re.compile(r"ПЗК\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)),
    ("pc", "ПЦ", re.compile(r"ПЦ\s*[-–—]?\s*(\d+(?:[.,]\d+)?)([А-ЯA-Z]?)", re.I)),
)


def _clean_name(name: str) -> str:
    stem = Path(name).stem.upper().replace("Ё", "Е")
    return re.sub(r"\s+", " ", stem.replace("_", " ")).strip()


def classify_name(name: str) -> tuple[str | None, str | None]:
    """Return a conservative product family/model classification from a filename."""
    cleaned = _clean_name(name)
    if re.search(r"НИЗКОРАМН", cleaned, re.I):
        return "lowbed", "Низкорамный прицеп"

    for family, prefix, pattern in MODEL_PATTERNS:
        match = pattern.search(cleaned)
        if not match:
            continue
        number = match.group(1).replace(",", ".")
        suffix = match.group(2).upper()
        return family, f"{prefix}-{number}{suffix}"
    return None, None


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as source:
        for chunk in iter(lambda: source.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def build_manifest(source: Path) -> dict:
    source = source.resolve()
    if not source.is_dir():
        raise ValueError(f"Source directory does not exist: {source}")

    entries = []
    first_by_hash: dict[str, str] = {}
    files = sorted((path for path in source.rglob("*") if path.is_file()), key=lambda p: p.relative_to(source).as_posix().casefold())
    for path in files:
        relative = path.relative_to(source).as_posix()
        digest = sha256_file(path)
        family, model = classify_name(path.name)
        duplicate_of = first_by_hash.get(digest)
        if duplicate_of is None:
            first_by_hash[digest] = relative
        entries.append({
            "relative_path": relative,
            "extension": path.suffix.lower(),
            "size": path.stat().st_size,
            "sha256": digest,
            "family": family,
            "model": model,
            "duplicate_of": duplicate_of,
        })
    return {"source": source.name, "file_count": len(entries), "entries": entries}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--source", type=Path, required=True)
    parser.add_argument("--out", type=Path, required=True)
    args = parser.parse_args()
    manifest = build_manifest(args.source)
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Indexed {manifest['file_count']} files into {args.out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
