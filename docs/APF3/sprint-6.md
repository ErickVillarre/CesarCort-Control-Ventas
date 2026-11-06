# Sprint 6 — backlog y seguimiento

**Actualización para exposición:** se retiró la landing independiente y el sistema entra directamente al CRM. La evidencia histórica de landing se conserva en la documentación; el alcance vigente de las historias de interfaz corresponde al CRM.

**Fecha de registro:** 6 de noviembre de 2026 (America/Lima). La duración, la fecha formal de inicio y los responsables individuales deben ser confirmados por el equipo; los nombres de integrantes indicados en el APF2 no constituyen asignaciones nuevas. Estado = evidencia verificable en esta rama, no intención.

**Objetivo propuesto:** dejar un incremento APF3 verificable en una rama exclusiva: solicitudes con control de acceso, accesibilidad funcional, demostración sintética coherente, pruebas y CI, y despliegue integral cuando se disponga de proveedores autorizados.

El informe APF2 describe Sprints 1–4. No se encontró un registro de Sprint 5 en este checkout ni entre los documentos APF2 examinados; su estado debe confirmarlo el equipo. No se replanifican módulos ya presentes solo por faltar esa acta.

| ID | Historia y criterio de aceptación | Tareas principales | Responsable | Estado técnico actual |
| --- | --- | --- | --- | --- |
| S6-01 | Como usuario, quiero solicitar servicio/información/acceso y ver el estado; cada registro conserva solicitante, tipo, prioridad, responsable, fechas, decisión y resolución. | Modelo, migración, rutas, interfaz, pruebas de autorización. | Desarrollo backend/frontend; asignación personal pendiente | Implementado y probado localmente; aceptación del equipo pendiente |
| S6-02 | Como rol autorizador, quiero resolver acceso con una decisión registrada; crear la solicitud nunca cambia permisos. | Permiso explícito, auditoría de decisión, pruebas 401/403 y no escalamiento. | Desarrollo backend; asignación personal pendiente | Implementado y probado localmente; aceptación del equipo pendiente |
| S6-03 | Como usuario, quiero elegir modos de daltonismo y voz voluntaria antes y después del login; persisten, son legibles y no revelan datos privados. | Tokens de color, panel, síntesis de voz, foco, revisión móvil y pruebas. | Desarrollo frontend; asignación personal pendiente | Comprobado instrumentalmente; audio y dispositivos físicos pendientes |
| S6-04 | Como evaluador, quiero un conjunto demo pequeño y coherente sin tocar datos reales. | Seeder aislado/idempotente, conciliación de ventas/stock/crédito, respaldo. | Desarrollo backend; asignación personal pendiente | Aprobado en prueba local y CI; base remota pendiente |
| S6-05 | Como equipo, quiero CI que ejecute comandos reales de lint/build y tests con DB aislada. | Workflow, ejecución y corrección de fallos. | Integración; asignación personal pendiente | [Tres jobs aprobados en GitHub Actions](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774) |
| S6-06 | Como operador, quiero frontend + API + MySQL remotos HTTPS con rutas directas, permisos y venta comprobados. | Hosting autorizado, secretos, migración con respaldo, almacenamiento duradero de adjuntos, health check, pruebas externas. | Operación/despliegue; asignación personal pendiente | Pendiente de proveedor, base/adjuntos remotos, respaldo y pruebas externas |
| S6-07 | Como equipo, quiero registrar cambio, capacidad y evidencia para revisión académica. | Matriz APF2, bitácora, línea base, plan de capacidad, handoff. | Documentación; asignación personal pendiente | Documentación local redactada; aceptación académica pendiente |

## Definition of Done

Una historia se marca **terminada** solo si existe código en la rama APF3, prueba pertinente aprobada o demostración registrada, evidencia de permisos cuando aplique, referencia de commit y documentación actualizada. Para S6-06 se requieren frontend, API y base remotos conectados; carga del frontend por sí sola no cumple el criterio.

## Review y retrospectiva

No consta una reunión de Review ni retrospectiva de Sprint 6 al abrir este documento. Quedan **pendientes** fecha, asistentes reales, demostración, aceptación del Product Owner, acuerdos y responsables. La auditoría técnica individual de esta rama no se presentará como acta de reunión.

### Registro para completar cuando ocurra

| Ceremonia | Fecha y participantes | Evidencia demostrada | Decisiones y acciones |
| --- | --- | --- | --- |
| Review Sprint 6 | Pendiente | Pendiente | Pendiente |
| Retrospectiva Sprint 6 | Pendiente | Pendiente | Pendiente |

## Corte técnico del 6 de noviembre de 2026

| Historia | Estado comprobado en esta sesión | Pendiente para cierre formal |
| --- | --- | --- |
| S6-01 y S6-02 | API/UI de solicitudes implementadas. Prueba 401/403; gerente aprobó acceso sin cambiar permisos del vendedor. Solicitud de servicio registrada desde CRM con accesibilidad activa. | Aceptación del equipo. |
| S6-03 | Seis modos, persistencia y guía por voz verificados instrumentalmente en Chrome headless; captura de escritorio y móvil emulado. | Audio audible, teclado/tacto físico, otros navegadores y contraste integral WCAG. |
| S6-04 | Seeder optativo e idempotente validado: 12 productos, 5 clientes nombrados, 4 ventas, 1 crédito. SQLite inicial respaldada/restaurada; MySQL original respaldada/restaurada en copia y conservada; demo en MySQL separada probada. | Probar demo y respaldo/restauración en la base del hosting autorizado. |
| S6-05 | Rama publicada y [GitHub Actions aprobó](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774) frontend, landing y backend para `3874929`; comandos locales pasan (6 tests/61 aserciones, lint/build). | Registrar aceptación del equipo cuando corresponda. |
| S6-06 | Dockerfile y configuración SPA preparados; no hay URL APF3 integral. | Proveedor/plan, MySQL remoto, adjuntos duraderos, despliegue y pruebas externas. |
| S6-07 | Contraste APF2, cambios, seguridad, pruebas, capacidad local y documento integrado redactados. | Completar handoff con commit final y actas reales de Review/retrospectiva cuando ocurran. |

La Review y la retrospectiva siguen **sin reunión comprobada**. No se asignan asistentes, acuerdos ni aceptación del Product Owner por inferencia.
