# APF3 — CesarControl CRM/ERP

**Actualización para exposición:** la landing independiente fue retirada por solicitud del propietario. Este directorio conserva evidencia histórica de la landing y documenta el CRM vigente.

Registro técnico del tercer avance de Curso Integrador II. Fecha de apertura: **6 de noviembre de 2026** (America/Lima). Este directorio contiene hechos comprobados, planes y pendientes diferenciados. El informe académico integrado debe citar las evidencias pertinentes dentro del cuerpo y **no llevar anexos**.

## Alcance y fuentes

- Fuente de trabajo inicial: `D:\ventas-melamina`, rama `feature/backend`, commit `67ccc12`. Al iniciar había un archivo sin seguimiento: `captura-principal.png`. La rama de trabajo APF3 se creó desde ese commit, preservando la carpeta local.
- Contraste histórico: `D:\Erick\Downloads\APF2_CesarControl_Curso_Integrador_II_Informe_Completo (1).docx`, secciones 6–9. Es un **reporte del APF2**, no una prueba nueva del estado remoto. También existe un borrador `Avance02-Integrador ll.docx` que plantea Odoo; la arquitectura implementada en este repositorio es React/Vite + Laravel, por lo que se usa el informe APF2 específico de CesarControl como referencia principal.
- Código y configuración del commit inicial: `README.md`, `backend/`, `frontend/`, `landing-page/` y `git status` del 6 de noviembre de 2026.
- `frontend/public/logo.png` y `landing-page/assets/logo.png` ya estaban versionados en `67ccc12` con el mismo blob Git `2d84c67fa00e043dea2ceef08d5de704fac16eaa` (328922 bytes). No se reemplazará el original. La titularidad/licencia de las imágenes debe confirmarla el propietario antes de una publicación comercial.
- `captura-principal.png` es una captura previa del login de escritorio (1920 × 1080) sin datos sensibles visibles en la inspección local. Se conserva como evidencia del estado inicial, **no** como prueba de la versión APF3 ni de acceso remoto.

## Documentos

| Tema | Documento |
| --- | --- |
| Comparación APF2 y observaciones | [observaciones-apf2.md](observaciones-apf2.md) |
| Historias, tareas, Review y retrospectiva del Sprint 6 | [sprint-6.md](sprint-6.md) |
| Solicitudes de servicio, información y acceso | [solicitudes.md](solicitudes.md) |
| Registro y procedimiento de cambios | [control-cambios.md](control-cambios.md) |
| Integración continua | [ci.md](ci.md) |
| Seguridad y accesibilidad | [seguridad-accesibilidad.md](seguridad-accesibilidad.md) |
| Capacidad y rendimiento | [capacidad.md](capacidad.md) |
| Despliegue y reversión | [despliegue-reversion.md](despliegue-reversion.md) |
| Resultados y evidencias de pruebas | [pruebas.md](pruebas.md) |
| Entrega factual para el informe | `handoff.md`, que se completa al cerrar el trabajo |
| Narrativa integrada para el informe, sin anexos | [documento-integrado.md](documento-integrado.md) |

## Criterio de cierre

«Publicado» significa frontend, API y base de datos remotos conectados y probados. Una página Vercel que carga sin operaciones no satisface este criterio. Las pruebas de celular/tableta o de otra red solo se declaran realizadas cuando haya evidencia de un dispositivo y red efectivamente usados.
