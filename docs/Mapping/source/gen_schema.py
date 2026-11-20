#!/usr/bin/env python3
"""Regenerates LMS 3NF Relational Schema Mapping (.svg, .png) from spec.json with corridor validation."""
import json
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

SKILL_SCRIPTS = Path.home() / ".gemini" / "skills" / "erd-builder" / "scripts"
sys.path.insert(0, str(SKILL_SCRIPTS))
import build_erd_mapping as builder  # noqa: E402


def render_png_reliable(svg_path: Path, png_path: Path, width: int, height: int) -> str:
    if shutil.which("rsvg-convert"):
        return builder.render_svg_to_png(svg_path, png_path, width, height)
    candidates = [
        shutil.which("chromium"),
        shutil.which("google-chrome"),
        shutil.which("msedge"),
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    ]
    for exe in candidates:
        if exe and Path(exe).exists():
            with tempfile.TemporaryDirectory() as td:
                subprocess.run(
                    [
                        exe, "--headless=new", "--disable-gpu", "--no-sandbox", "--hide-scrollbars",
                        f"--user-data-dir={td}", f"--window-size={width},{height}",
                        f"--screenshot={png_path.resolve()}", svg_path.resolve().as_uri(),
                    ],
                    check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                )
            if png_path.exists() and png_path.stat().st_size > 0:
                return Path(exe).stem
    raise RuntimeError("No working SVG->PNG renderer found")


def main() -> None:
    here = Path(__file__).resolve().parent
    spec_path = here.parents[1] / "ERD" / "source" / "spec.json"
    spec = json.loads(spec_path.read_text(encoding="utf-8"))
    prefix = spec.get("prefix", "LMS")
    map_dir = here.parent

    mapping = builder.RelationalMappingBuilder(spec["mapping"])
    errs = mapping.validate_geometry()
    if errs:
        for e in errs:
            print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(1)

    svg_text = mapping.render_svg()
    for target_dir in (map_dir, here):
        (target_dir / f"{prefix}-Relational-Mapping.svg").write_text(svg_text, encoding="utf-8")

    png_file = map_dir / f"{prefix}-Relational-Mapping.png"
    engine = render_png_reliable(
        map_dir / f"{prefix}-Relational-Mapping.svg", png_file, mapping.W, mapping.H
    )
    print(f"[RELATIONAL MAPPING] Generated {png_file} ({mapping.W}x{mapping.H}) via {engine}")


if __name__ == "__main__":
    main()
