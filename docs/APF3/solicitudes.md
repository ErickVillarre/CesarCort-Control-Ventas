# Solicitudes de servicio, información y acceso

## Implementación local

Las solicitudes autenticadas registran solicitante, tipo (`servicio`, `informacion`, `acceso`), asunto, descripción, prioridad (`baja`, `media`, `alta`, `critica`), estado, responsable, fecha de solicitud, decisión, quién decidió, fecha de decisión, resolución y fecha de cierre. `solicitud_eventos` conserva creación, asignación y resolución. La interfaz muestra tipo, prioridad, estado, responsable y decisión con texto/símbolo además del color.

| Acción | Ruta API | Control aplicado |
| --- | --- | --- |
| Crear | `POST /api/solicitudes` | Token Sanctum y contraseña vigente; el solicitante se toma del token, no del cuerpo. |
| Listar/consultar historial | `GET /api/solicitudes`, `GET /api/solicitudes/{id}`, `GET /api/solicitudes/{id}/eventos` | Usuario común solo ve las suyas; admin/gerente con `solicitudes.gestionar` pueden revisar el conjunto. |
| Asignar responsable | `PATCH /api/solicitudes/{id}/asignar` | Admin/gerente con permiso; registra evento y pasa a `en_revision`. |
| Resolver | `PATCH /api/solicitudes/{id}/resolver` | Admin/gerente con permiso, responsable ya asignado, decisión `aprobada`/`rechazada` y resolución obligatoria. |

Una decisión **aprobada** de tipo `acceso` solo registra la autorización y su motivo. No cambia `role_id`, permisos ni tokens. Una concesión efectiva, si procede, requiere el flujo administrativo separado y autorización propia. La voz no pronuncia la descripción ni datos privados de la solicitud.

## Prueba comprobada

En SQLite de prueba aislada, `Apf3WorkflowTest` y [el smoke HTTP](evidencias/api-resultados.json) confirmaron `401` sin token, `403` cuando el vendedor intentó resolver, asignación/decisión del gerente, decisión guardada y permisos del vendedor idénticos antes/después. En Chrome 156 headless, un vendedor registró desde la interfaz una solicitud de servicio con deuteranopia y guía de voz activadas; quedó visible en “Mis solicitudes”. La captura está en [evidencias/crm-authenticated-desktop.png](evidencias/crm-authenticated-desktop.png). Los formularios y listados remotos siguen pendientes de prueba después del despliegue integral.
