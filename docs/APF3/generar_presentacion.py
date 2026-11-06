"""Genera la presentación APF3 desde hechos y capturas sintéticas del repositorio.

Requiere python-pptx y Pillow. Ejecutar desde cualquier directorio:
    python docs/APF3/generar_presentacion.py
"""

from pathlib import Path

from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.util import Inches, Pt


HERE = Path(__file__).resolve().parent
EVIDENCE = HERE / "evidencias"
OUTPUT = HERE / "Presentacion-APF3-CesarControl.pptx"

CREAM = RGBColor(246, 243, 238)
WHITE = RGBColor(255, 253, 250)
GRAPHITE = RGBColor(39, 36, 33)
COPPER = RGBColor(128, 80, 53)
COPPER_LIGHT = RGBColor(235, 222, 210)
MUTED = RGBColor(98, 90, 83)
BORDER = RGBColor(217, 208, 197)
BLUE = RGBColor(49, 86, 109)
BLUE_LIGHT = RGBColor(232, 240, 243)
RED = RGBColor(150, 63, 57)
RED_LIGHT = RGBColor(249, 234, 231)

prs = Presentation()
prs.slide_width = Inches(13.333)
prs.slide_height = Inches(7.5)


def rect(slide, x, y, w, h, fill=WHITE, line=None, radius=False):
    shape = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE if radius else MSO_SHAPE.RECTANGLE,
        Inches(x), Inches(y), Inches(w), Inches(h)
    )
    shape.fill.solid()
    shape.fill.fore_color.rgb = fill
    shape.line.fill.background() if line is None else None
    if line is not None:
        shape.line.color.rgb = line
        shape.line.width = Pt(0.8)
    if radius:
        shape.adjustments[0] = 0.11
    return shape


def txt(slide, s, x, y, w, h, size=20, color=GRAPHITE, bold=False,
        font="Aptos", align=PP_ALIGN.LEFT, valign=MSO_ANCHOR.MIDDLE,
        margin=0, break_words=False):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.clear()
    tf.word_wrap = break_words
    tf.margin_left = Inches(margin)
    tf.margin_right = Inches(margin)
    tf.margin_top = Inches(0.01)
    tf.margin_bottom = Inches(0.01)
    tf.vertical_anchor = valign
    p = tf.paragraphs[0]
    p.text = s
    p.alignment = align
    p.font.name = font
    p.font.size = Pt(size)
    p.font.bold = bold
    p.font.color.rgb = color
    p.space_after = Pt(0)
    return box


def base(title, kicker, number, dark=False):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    bg = GRAPHITE if dark else CREAM
    fg = WHITE if dark else GRAPHITE
    rect(slide, 0, 0, 13.333, 7.5, bg)
    rect(slide, 0, 0, 0.12, 7.5, COPPER)
    txt(slide, kicker.upper(), 0.55, 0.36, 9.2, 0.26, 10.5,
        COPPER_LIGHT if dark else COPPER, True)
    txt(slide, title, 0.55, 0.76, 12.15, 0.78, 30, fg, True)
    rect(slide, 0.55, 1.63, 12.2, 0.015, BORDER if not dark else MUTED)
    txt(slide, "CESARCONTROL  /  APF3  /  06 NOV 2026", 0.55, 7.10, 8.4, 0.18,
        9, MUTED if not dark else COPPER_LIGHT)
    txt(slide, f"{number:02d} / 15", 11.7, 7.08, 1.0, 0.2, 9,
        MUTED if not dark else COPPER_LIGHT, align=PP_ALIGN.RIGHT)
    return slide


def card(slide, x, y, w, h, heading, body, accent=COPPER, heading_size=17,
         body_size=15):
    rect(slide, x, y, w, h, WHITE, BORDER, True)
    rect(slide, x + 0.18, y + 0.22, 0.055, 0.44, accent)
    txt(slide, heading, x + 0.35, y + 0.17, w - 0.55, 0.55,
        heading_size, GRAPHITE, True)
    txt(slide, body, x + 0.24, y + 0.82, w - 0.48, h - 1.04,
        body_size, MUTED, valign=MSO_ANCHOR.TOP, break_words=True)


def pill(slide, label, x, y, w, fill=COPPER_LIGHT, fg=COPPER):
    rect(slide, x, y, w, 0.38, fill, None, True)
    txt(slide, label, x + 0.1, y + 0.01, w - 0.2, 0.34, 11.5,
        fg, True, align=PP_ALIGN.CENTER)


def line(slide, x1, y1, x2, y2, color=BORDER, width=1.1):
    shape = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(x1), Inches(y1),
                                   Inches(x2-x1), Inches(y2-y1))
    shape.fill.solid()
    shape.fill.fore_color.rgb = color
    shape.line.fill.background()
    return shape


def image_fit(slide, filename, x, y, w, h):
    from PIL import Image
    path = EVIDENCE / filename
    with Image.open(path) as im:
        iw, ih = im.size
    scale = min(w / iw, h / ih)
    pw, ph = iw * scale, ih * scale
    rect(slide, x, y, w, h, WHITE, BORDER, True)
    slide.shapes.add_picture(str(path), Inches(x + (w-pw)/2),
                             Inches(y + (h-ph)/2), Inches(pw), Inches(ph))


def notes(slide, body):
    frame = slide.notes_slide.notes_text_frame
    frame.text = body


# 01
s = base("Gestión del servicio y versión final", "Avance 3 · Semana 13", 1, True)
txt(s, "CesarControl CRM / ERP", 0.64, 1.94, 8.7, 0.58, 23,
    COPPER_LIGHT, True)
txt(s, "Solicitudes, control, CI, capacidad y evidencia de la entrega.",
    0.64, 2.64, 7.8, 1.38, 25, WHITE, break_words=True)
rect(s, 0.64, 5.45, 7.72, 0.9, RGBColor(57, 52, 48), None, True)
txt(s, "Estado: CRM local funcional · publicación integral pendiente",
    0.9, 5.62, 7.2, 0.55, 16, WHITE, True)
rect(s, 9.5, 1.94, 2.7, 3.65, RGBColor(52, 47, 43), None, True)
txt(s, "APF3", 9.72, 2.65, 2.3, 1.1, 47, COPPER_LIGHT, True,
    align=PP_ALIGN.CENTER)
txt(s, "06 / 11 / 2026", 9.86, 4.17, 2.0, 0.38, 13, WHITE,
    align=PP_ALIGN.CENTER)
notes(s, "Aclarar al inicio que esta presentación muestra el incremento verificable y el estado real del despliegue. La URL pública integral todavía no existe.")

# 02
s = base("Alcance exigido en el tercer avance", "Mapa de la consigna", 2)
items = [
    ("01 · Servicio", "Solicitudes de servicio, información y acceso con trazabilidad."),
    ("02 · Gobierno", "Observaciones APF2, control de cambios y Sprint 6."),
    ("03 · Calidad", "Integración continua, seguridad y accesibilidad."),
    ("04 · Capacidad", "Línea base local y plan de medición del hosting."),
    ("05 · Despliegue", "CRM + Laravel + MySQL bajo HTTPS y pruebas externas."),
    ("06 · Evidencia", "Documento integrado, PPT y referencias comprobables."),
]
for i, (h, b) in enumerate(items):
    col, row = i % 3, i // 3
    card(s, 0.58 + col*4.16, 1.89 + row*2.33, 3.91, 2.05, h, b,
         COPPER if col != 2 else BLUE, 16, 14.7)
notes(s, "La consigna pide una versión final desplegada. Aquí se separa lo implementado localmente del criterio remoto aún pendiente.")

# 03
s = base("Observaciones del APF2 levantadas", "Diagnóstico → acción → evidencia", 3)
rows = [
    ("Frontend histórico", "APF2 mostraba Vercel; API/MySQL seguían locales.",
     "Se preparó configuración para API HTTPS; no se atribuye despliegue APF3."),
    ("Checkout incompleto", "Faltaban piezas de identidad, permisos y controladores.",
     "Integración reparada; suite Laravel y smoke 401/403 aprobados."),
    ("Datos de demo", "Volumen excesivo y sin aislamiento suficiente.",
     "Seeder sintético opcional y copia MySQL para exposición."),
    ("Calidad", "Sin workflow APF3 versionado; avisos de dependencias.",
     "Actions con lint/build/tests; auditorías finales sin avisos."),
]
for i, (a,b,c) in enumerate(rows):
    y = 1.9 + i*1.18
    rect(s, 0.6, y, 12.1, 1.02, WHITE, BORDER, True)
    txt(s, a, 0.86, y+0.16, 2.17, 0.68, 15.5, GRAPHITE, True)
    txt(s, b, 3.0, y+0.15, 4.0, 0.72, 13.8, MUTED, break_words=True)
    txt(s, c, 7.15, y+0.15, 5.25, 0.72, 13.8, COPPER, break_words=True)
notes(s, "Fuente: observaciones-apf2.md. El HTTP 200 de la URL histórica no demuestra el CRM remoto.")

# 04
s = base("Arquitectura comprobada para la exposición", "Sistema local en esta PC", 4)
blocks = [
    ("CRM", "React + Vite", "127.0.0.1:5173"),
    ("API", "Laravel + Sanctum", "127.0.0.1:8000"),
    ("DATOS", "MySQL de XAMPP", "cesarcontrol_datos_expo"),
]
for i, (h,b,c) in enumerate(blocks):
    x = 0.64 + i*4.28
    rect(s, x, 2.45, 3.65, 2.22, WHITE, BORDER, True)
    rect(s, x, 2.45, 3.65, 0.12, COPPER if i != 2 else BLUE)
    txt(s, h, x+0.25, 2.75, 3.15, 0.3, 13, COPPER, True)
    txt(s, b, x+0.25, 3.15, 3.15, 0.5, 22, GRAPHITE, True)
    txt(s, c, x+0.25, 3.85, 3.15, 0.5, 13.5, MUTED)
    if i < 2:
        txt(s, "→", x+3.7, 3.25, 0.52, 0.54, 26, COPPER, True,
            align=PP_ALIGN.CENTER)
rect(s, 0.64, 5.25, 12.05, 0.92, BLUE_LIGHT, None, True)
txt(s, "Los registros nuevos de la exposición se guardan en la copia; la base original se conserva.",
    0.92, 5.45, 11.54, 0.47, 16, BLUE, True)
notes(s, "No presentar estas direcciones localhost como URL pública. La copia proviene de una exportación y restauración verificadas de la base original.")

# 05
s = base("Solicitudes de servicio, información y acceso", "Proceso y autorización", 5)
labels = ["Registrar", "Revisar", "Decidir", "Resolver / cerrar"]
for i, label in enumerate(labels):
    x = 0.62 + i*3.17
    rect(s, x, 2.12, 2.75, 0.78, COPPER_LIGHT if i != 2 else BLUE_LIGHT,
         None, True)
    txt(s, label, x+0.1, 2.26, 2.55, 0.47, 17,
        COPPER if i != 2 else BLUE, True, align=PP_ALIGN.CENTER)
    if i < 3:
        txt(s, "→", x+2.79, 2.28, 0.35, 0.45, 22, COPPER,
            align=PP_ALIGN.CENTER)
card(s, 0.65, 3.47, 3.78, 2.25, "Datos del caso",
     "Tipo, solicitante, prioridad, estado, fecha, responsable, decisión y resolución.",
     COPPER, 17, 15)
card(s, 4.73, 3.47, 3.78, 2.25, "Permisos",
     "Sin sesión: 401. Usuario sin facultad de decisión: 403.", BLUE, 17, 15)
card(s, 8.81, 3.47, 3.78, 2.25, "Acceso controlado",
     "Aprobar una solicitud de acceso no cambia el rol ni los permisos automáticamente.",
     COPPER, 17, 15)
notes(s, "La operación fue comprobada con vendedor y gerente. El gerente aprobó acceso y los permisos del vendedor permanecieron iguales.")

# 06
s = base("Interfaz renovada y accesible", "CRM actual · capturas sintéticas", 6)
image_fit(s, "redesign-login-desktop.png", 0.64, 1.92, 8.24, 4.73)
image_fit(s, "redesign-login-mobile.png", 9.1, 1.92, 2.22, 4.73)
pill(s, "Grafito", 11.52, 2.12, 1.15, GRAPHITE, WHITE)
pill(s, "Crema", 11.52, 2.58, 1.15, WHITE, GRAPHITE)
pill(s, "Madera", 11.52, 3.04, 1.15, COPPER, WHITE)
txt(s, "6 modos\nde visión", 11.55, 4.07, 1.1, 0.9, 13.5,
    GRAPHITE, True, align=PP_ALIGN.CENTER, break_words=True)
notes(s, "El logo original se conserva. Escritorio y móvil corresponden a Chrome headless y emulación local. La guía de voz es voluntaria; el spy verificó llamadas es-PE, sin escucha física.")

# 07
s = base("Datos, respaldo y separación", "Continuidad de la exposición", 7)
card(s, 0.65, 2.0, 3.78, 3.15, "Base original",
     "ventas-melamina\n18 usuarios · 82 productos\n43 clientes · 250 ventas\n\nRespaldo SQL y restauración de 43 tablas verificados.",
     GRAPHITE, 19, 17)
card(s, 4.74, 2.0, 3.78, 3.15, "Copia de trabajo",
     "cesarcontrol_datos_expo\nMismos registros de negocio\n4 migraciones APF3\n3 cuentas sintéticas por rol\n\nVentas nuevas: solo en esta copia.",
     BLUE, 19, 17)
card(s, 8.83, 2.0, 3.78, 3.15, "Demo aislada",
     "Seeder optativo e idempotente\n12 productos · 5 clientes\n4 ventas · 1 crédito\n\nDatos sintéticos para pruebas, fuera de la base original.",
     COPPER, 19, 17)
rect(s, 0.65, 5.58, 11.96, 0.62, BLUE_LIGHT, None, True)
txt(s, "No se ejecutó migrate:fresh ni se borraron datos originales.",
    0.88, 5.68, 11.48, 0.36, 15, BLUE, True)
notes(s, "El respaldo está fuera del repositorio. No proyectar datos personales de la copia. Las capturas incluidas aquí usan datos sintéticos.")

# 08
s = base("Control de cambios y reversión", "Registro APF3", 8)
steps = [
    ("Motivo", "Problema / necesidad"),
    ("Impacto", "Código, datos y usuarios"),
    ("Validación", "Revisión, tests y CI"),
    ("Publicación", "Commit y entorno"),
    ("Reversión", "Artefacto y backup"),
]
for i, (h,b) in enumerate(steps):
    x = 0.63 + i*2.55
    rect(s, x, 2.16, 2.28, 2.08, WHITE, BORDER, True)
    txt(s, f"0{i+1}", x+0.19, 2.36, 0.75, 0.45, 23, COPPER, True)
    txt(s, h, x+0.19, 2.91, 1.92, 0.45, 16.5, GRAPHITE, True)
    txt(s, b, x+0.19, 3.44, 1.93, 0.58, 12.7, MUTED, break_words=True)
    if i < 4:
        txt(s, "→", x+2.31, 2.97, 0.21, 0.35, 18, COPPER)
rect(s, 0.66, 4.93, 11.94, 1.02, BLUE_LIGHT, None, True)
txt(s, "Producción exige backup restaurable de su propia base y aprobación operativa del entorno.",
    0.93, 5.1, 11.35, 0.63, 17, BLUE, True)
notes(s, "La bitácora control-cambios.md registra APF3-001 a APF3-010. La aprobación académica y el despliegue público no se infieren del commit.")

# 09
s = base("Integración continua", "GitHub Actions · rama apf3-integrador-ii", 9)
card(s, 0.65, 2.0, 3.78, 2.68, "Frontend",
     "Node 22\nnpm ci → lint → build\n\nAprobado en 14f5a70.",
     COPPER, 19, 16.5)
card(s, 4.74, 2.0, 3.78, 2.68, "Backend",
     "PHP 8.3\ncomposer install → tests\n\n6 pruebas · 61 aserciones.",
     BLUE, 19, 16.5)
card(s, 8.83, 2.0, 3.78, 2.68, "Imagen Docker",
     "Build de imagen PHP de producción aprobado en CI. Ejecución remota pendiente.",
     COPPER, 19, 16.5)
rect(s, 0.65, 5.1, 11.95, 0.87, RED_LIGHT, None, True)
txt(s, "Dos fallos corregidos: prueba del seeder y dependencia SQLite de Docker. Tres jobs actuales aprobados.",
    0.92, 5.25, 11.42, 0.53, 16, RED, True)
notes(s, "El run 14f5a70 aprobó frontend, backend e imagen Docker. Verificar también el run del commit final de documentación antes de exponer. En ci.md constan ambos fallos previos.")

# 10
s = base("Rendimiento: línea base local", "MySQL XAMPP · 20 lecturas secuenciales por ruta", 10)
values = [
    ("Perfil", 237.7), ("Productos", 181.8),
    ("Clientes", 187.7), ("Dashboard", 243.7),
]
for i, (label, value) in enumerate(values):
    y = 2.12 + i*0.84
    txt(s, label, 0.73, y, 2.05, 0.44, 16, GRAPHITE, True)
    rect(s, 2.78, y+0.03, 7.3, 0.38, COPPER_LIGHT, None, True)
    rect(s, 2.78, y+0.03, 7.3*(value/300), 0.38, COPPER, None, True)
    txt(s, f"{value:.1f} ms", 10.37, y, 2.0, 0.44, 17, COPPER, True)
rect(s, 0.72, 5.78, 11.88, 0.57, BLUE_LIGHT, None, True)
txt(s, "p95 local · 0/98 respuestas 5xx · sin concurrencia ni red externa",
    0.9, 5.88, 11.48, 0.33, 15.5, BLUE, True)
notes(s, "No confundir la meta APF2 p95 ≤2 s con un resultado de producción. El p95 mostrado es local, en 20 lecturas secuenciales por ruta. La muestra de UI incluyó 3 navegaciones por perfil, con móvil emulado.")

# 11
s = base("Planificación de capacidad", "Medir al activar el hosting elegido", 11)
items = [
    ("Límites del plan", "CPU, RAM, conexiones MySQL, almacenamiento, suspensión y cuotas."),
    ("Tráfico real", "FCP/LCP/TBT, bytes, p95 y errores desde HTTPS y red móvil."),
    ("Operación", "Vigilar p95, 5xx, memoria PHP, espacio, conexiones y backups."),
    ("Respuesta", "Optimizar consultas/paginación; ampliar recursos solo con autorización."),
]
for i,(h,b) in enumerate(items):
    x = 0.65 + (i%2)*6.12
    y = 1.98 + (i//2)*2.13
    card(s, x, y, 5.85, 1.86, h, b, BLUE if i%2 else COPPER, 18, 15.5)
notes(s, "Los recursos reales del hosting siguen sin valor medido porque no hay proveedor ni plan seleccionado. Se requiere backup remoto restaurable y almacenamiento duradero para adjuntos.")

# 12
s = base("Sprint 6: avance por historia", "Estado técnico · aceptación formal pendiente", 12)
histories = [
    ("S6-01/02", "Solicitudes y permisos", "Probado local"),
    ("S6-03", "Accesibilidad", "Instrumental"),
    ("S6-04", "Demo aislada", "Probado local"),
    ("S6-05", "CI", "Aprobado"),
    ("S6-06", "Despliegue integral", "Pendiente"),
    ("S6-07", "Documentación/capacidad", "Redactado"),
]
for i,(code,name,state) in enumerate(histories):
    y = 1.9 + i*0.71
    rect(s, 0.65, y, 11.95, 0.61, WHITE, BORDER, True)
    txt(s, code, 0.88, y+0.08, 1.55, 0.44, 15, COPPER, True)
    txt(s, name, 2.55, y+0.08, 6.4, 0.44, 15.5, GRAPHITE)
    pill(s, state, 9.53, y+0.11, 2.55,
         RED_LIGHT if state=="Pendiente" else BLUE_LIGHT,
         RED if state=="Pendiente" else BLUE)
notes(s, "La Review y la aceptación del Product Owner no constan. S6-06 permanece abierto hasta comprobar frontend, API y MySQL remotos.")

# 13
s = base("Retrospectiva del Sprint 6", "Hechos técnicos disponibles · ceremonia pendiente", 13)
card(s, 0.65, 2.0, 5.72, 2.85, "Funcionó",
     "Pruebas aisladas, roles y acceso sin escalamiento; CI reparada; backup y copia de datos antes de migrar.",
     BLUE, 20, 17)
card(s, 6.86, 2.0, 5.72, 2.85, "Mejorar en el siguiente ciclo",
     "Conectar hosting y medir en red real; probar voz audible, dispositivos físicos y restauración remota.",
     COPPER, 20, 17)
rect(s, 0.65, 5.28, 11.93, 0.84, RED_LIGHT, None, True)
txt(s, "Sin reunión verificada: fecha, participantes, acuerdos y responsables siguen por registrar.",
    0.92, 5.41, 11.42, 0.53, 16.5, RED, True)
notes(s, "No atribuir este análisis técnico a una reunión. Completar acta solo cuando el equipo se reúna y confirme decisiones.")

# 14
s = base("Despliegue de versión final: estado real", "Criterio de cierre remoto", 14)
card(s, 0.65, 2.0, 5.76, 3.25, "Disponible ahora",
     "Rama APF3 en GitHub y CI previa aprobada.\nCRM + Laravel + MySQL local funcionales.\nLogin de 3 roles, datos y dashboard comprobados.\nIniciador para exposición en esta PC.",
     BLUE, 20, 17)
card(s, 6.84, 2.0, 5.76, 3.25, "Falta para URL pública",
     "Iniciar sesión en Vercel y Railway Trial.\nMySQL y adjuntos persistentes.\nBackup remoto, variables HTTPS y migración.\nPruebas externas de login, venta, stock y accesibilidad.",
     RED, 20, 17)
rect(s, 0.65, 5.62, 11.95, 0.65, GRAPHITE, None, True)
txt(s, "URL pública APF3: ninguna. La URL del APF2 es histórica.",
    0.92, 5.73, 11.4, 0.38, 16.5, WHITE, True)
notes(s, "El propietario eligió Vercel Hobby + Railway Trial para una demo de exposición con datos sintéticos. La prueba puede caducar en 30 días o por crédito. La consigna define desplegado como frontend, API y BD remotos funcionando. Aún faltan las sesiones de proveedor. No se contrató ningún plan de pago.")

# 15
s = base("Evidencias y guion de demostración", "Entrega integrada", 15)
steps = [
    ("1", "Abrir login", "127.0.0.1:5173/login"),
    ("2", "Ingresar por rol", "Administrador · gerente · vendedor"),
    ("3", "Consultar", "Productos · clientes · ventas"),
    ("4", "Solicitar", "Servicio / información / acceso"),
    ("5", "Mostrar pruebas", "CI · 401/403 · stock · capacidad"),
]
for i,(n,h,b) in enumerate(steps):
    y=1.92+i*0.84
    rect(s, 0.65, y, 8.08, 0.70, WHITE, BORDER, True)
    pill(s, n, 0.81, y+0.17, 0.52, COPPER_LIGHT, COPPER)
    txt(s, h, 1.53, y+0.12, 2.55, 0.44, 16.5, GRAPHITE, True)
    txt(s, b, 4.08, y+0.12, 4.34, 0.44, 14.5, MUTED)
rect(s, 9.12, 1.93, 3.42, 4.13, GRAPHITE, None, True)
txt(s, "DOCUMENTOS", 9.42, 2.3, 2.84, 0.36, 13.5, COPPER_LIGHT, True)
txt(s, "documento-integrado.md\nresumen-para-informe.md\npruebas.md\ncapacidad.md\nci.md\nhandoff.md\ndespliegue-demo-gratuita.md",
    9.42, 2.95, 2.86, 2.54, 14.5, WHITE, break_words=True,
    valign=MSO_ANCHOR.TOP)
notes(s, "Los documentos enlazan evidencia JSON, capturas sintéticas y runs de GitHub. No abrir registros de clientes reales durante la presentación pública. La contraseña demo se entrega fuera del PPT.")

prs.core_properties.title = "APF3 — CesarControl CRM/ERP"
prs.core_properties.subject = "Gestión del servicio y versión final"
prs.core_properties.author = "Equipo CesarControl"
prs.core_properties.keywords = "APF3, Sprint 6, solicitudes, CI, capacidad, despliegue"
prs.save(OUTPUT)
print(f"Generada: {OUTPUT} ({len(prs.slides)} diapositivas)")
