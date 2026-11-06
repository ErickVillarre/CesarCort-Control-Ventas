"""Calcula contraste de pares semánticos del CRM; no es una auditoría WCAG integral."""

import json
import re
from datetime import date
from pathlib import Path


HERE = Path(__file__).resolve().parent
CSS = (HERE / "../../frontend/src/index.css").resolve()
OUT = HERE / "evidencias/redisenio-contraste.json"
STYLES = CSS.read_text(encoding="utf-8")
MODES = ("normal", "protanopia", "deuteranopia", "tritanopia",
         "acromatopsia", "alto-contraste")
PAIRS = (
    ("ink/surface", "ink", "surface"),
    ("muted/surface", "muted", "surface"),
    ("white/primary", "#ffffff", "primary"),
    ("white/primary-hover", "#ffffff", "primary-hover"),
    ("white/success", "#ffffff", "success"),
    ("white/danger", "#ffffff", "danger"),
    ("white/warning", "#ffffff", "warning"),
    ("white/info", "#ffffff", "info"),
    ("sidebar-ink/sidebar", "sidebar-ink", "sidebar"),
    ("sidebar-muted/sidebar", "sidebar-muted", "sidebar"),
    ("ink/sidebar-active", "ink", "sidebar-active"),
    ("success/success-soft", "success", "success-soft"),
    ("danger/danger-soft", "danger", "danger-soft"),
    ("warning/warning-soft", "warning", "warning-soft"),
    ("info/page", "info", "page"),
)


def variables(selector):
    match = re.search(r"(?:^|\n)" + re.escape(selector) + r"\s*\{([^}]+)\}",
                      STYLES)
    if not match:
        raise ValueError(f"No se encontró {selector}")
    return dict(re.findall(r"--ui-([\w-]+):\s*(#[0-9a-fA-F]{3,8})\s*;",
                           match.group(1)))


def luminance(color):
    value = color.lstrip("#")
    if len(value) == 3:
        value = "".join(ch * 2 for ch in value)
    rgb = [int(value[i:i+2], 16) / 255 for i in (0, 2, 4)]
    linear = [v / 12.92 if v <= 0.04045 else ((v + 0.055) / 1.055) ** 2.4
              for v in rgb]
    return sum(a*b for a,b in zip(linear, (0.2126, 0.7152, 0.0722)))


def ratio(a, b):
    high, low = sorted((luminance(a), luminance(b)), reverse=True)
    return (high + 0.05) / (low + 0.05)


root = variables(":root")
result = {
    "fecha": date.today().isoformat(),
    "fuente": "frontend/src/index.css",
    "metodo": "luminancia relativa sRGB; razón (Lclaro+0.05)/(Loscuro+0.05)",
    "umbral_texto_normal": 4.5,
    "alcance": "15 pares semánticos por modo; no cubre imágenes, gradientes, todos los estados ni interacción",
    "modos": {},
}
for mode in MODES:
    colors = root | (variables(f'[data-vision="{mode}"]') if mode != "normal" else {})
    values = {}
    for label, fg, bg in PAIRS:
        fg_color = fg if fg.startswith("#") else colors[fg]
        bg_color = bg if bg.startswith("#") else colors[bg]
        values[label] = round(ratio(fg_color, bg_color), 2)
    minimum = min(values.values())
    result["modos"][mode] = {
        "minimo": minimum,
        "cumple_umbral_en_pares_evaluados": minimum >= 4.5,
        "pares": values,
    }
OUT.write_text(json.dumps(result, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print("Contrastes mínimos:", {k: v["minimo"] for k,v in result["modos"].items()})
if any(not x["cumple_umbral_en_pares_evaluados"] for x in result["modos"].values()):
    raise SystemExit("Algún par no alcanza 4.5:1")
