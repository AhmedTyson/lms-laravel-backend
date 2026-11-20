#!/usr/bin/env python3
"""Regenerates LMS Chen ERD (.svg, .drawio, .png) from spec.json with geometry validation."""
import json
import os
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
    spec = json.loads((here / "spec.json").read_text(encoding="utf-8"))
    prefix = spec.get("prefix", "LMS")
    erd_dir = here.parent
    chen = builder.ChenBuilder(spec["chen"])
    errs = chen.validate_geometry()
    if errs:
        for e in errs:
            print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(1)

    svg_text = chen.render_svg()
    drawio_text = chen.render_drawio()
    for target_dir in (erd_dir, here):
        (target_dir / f"{prefix}-Chen-ERD.svg").write_text(svg_text, encoding="utf-8")
        (target_dir / f"{prefix}-Chen-ERD.drawio").write_text(drawio_text, encoding="utf-8")

    png_file = erd_dir / f"{prefix}-Chen-ERD.png"
    engine = render_png_reliable(erd_dir / f"{prefix}-Chen-ERD.svg", png_file, chen.W, chen.H)
    print(f"[CHEN ERD] Generated {png_file} ({chen.W}x{chen.H}) via {engine}")


if __name__ == "__main__":
    main()
