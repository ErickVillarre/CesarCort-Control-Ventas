# Control de cambios APF3

Cada cambio se registra antes de producción con motivo, alcance/impacto, referencia al commit, revisión técnica, aprobación, despliegue y reversión posible. Un commit o un test aprobado no sustituyen la aprobación operativa de publicar datos o cambiar hosting.

| ID | Motivo e impacto | Commit | Revisión/aprobación | Despliegue | Reversión |
| --- | --- | --- | --- | --- | --- |
| APF3-001 | Preservar trabajo inicial y separar el avance. Rama `apf3-integrador-ii` creada desde `67ccc12`; no altera ramas anteriores. | Base `67ccc12`; código en `17bfdd2` y `4457ff5` | Revisión local del árbol y `git status` realizada; aprobación académica pendiente | No desplegado | Volver al commit base sin borrar rama ni datos. |
| APF3-002 | Recuperar integración de identidad/permisos del checkout inicial y agregar solicitudes. Impacta migraciones, API y acceso. | `17bfdd2` | `php artisan test`: 6 pruebas/61 aserciones; HTTP 401/403 y acceso sin cambio de permisos. Aprobación de producción pendiente. | No desplegado | Respaldar BD objetivo; revertir código y restaurar **esa** base si la migración no es compatible. |
| APF3-003 | Accesibilidad visual/voz y actualización de diseño. Impacta interfaz de landing y CRM. | `4457ff5` | Lint/build aprobados, seis modos y operación de solicitud con voz comprobados instrumentalmente; audio físico pendiente. | No desplegado | Redeploy del commit previo de frontend. |
| APF3-004 | Demo reducida y CI. Impacta solo base aislada y workflow; no se limpian datos existentes. | Seeder `17bfdd2`; workflow `f561b58`; prueba corregida `3874929` | Seeder idempotente, 12 productos/5 clientes/4 ventas/1 crédito; [GitHub Actions aprobó los tres jobs](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774). | No desplegado en producción | No ejecutar seeder en producción; restaurar base demo aislada si es necesario. |
| APF3-005 | Despliegue remoto completo. Impacta variables de entorno, esquema, adjuntos y disponibilidad. | Pendiente | Requiere proveedor/plan autorizado, respaldo MySQL restaurable y almacenamiento duradero | No desplegado | Volver al deployment previo y restaurar backup validado según [despliegue-reversion.md](despliegue-reversion.md). |
| APF3-006 | Retirar la landing independiente para exponer directamente el CRM; impacta archivos de landing, CI, CORS por defecto y la ruta exclusiva de solicitudes de landing. | Cambio posterior a `de53eea`; SHA: `git rev-parse HEAD` | Solicitado por el propietario; validación local aprobada (Laravel 6/6, lint/build CRM). Logo original conservado; landing recuperable desde Git. | Solo local; sin despliegue público | Restaurar carpeta y configuración desde el commit anterior si se revierte la decisión. |
| APF3-007 | Preparar exposición local con MySQL de XAMPP separado y un iniciador del CRM. Impacta base demo y procesos locales; no cambia la base original. | Cambio posterior a `de53eea`; SHA: `git rev-parse HEAD` | Respaldo original restaurado en copia; 21 migraciones en base nueva; tres roles, venta/stock y 0/98 errores 5xx en smoke MySQL. | CRM local `127.0.0.1:5173` y API local `127.0.0.1:8000` activos | Detener procesos del proyecto; recuperar la base original desde su respaldo solo si alguna intervención posterior la afectase. |
| APF3-008 | Conectar la exposición a los registros originales mediante una copia migrada y validar sesiones al cambiar de base. | Cambio posterior a `d415333`; SHA: `git rev-parse HEAD` | Respaldo nuevo restaurado en copia; cuatro migraciones pendientes aplicadas solo en `cesarcontrol_datos_expo`; tres logins y consultas de 82 productos, 43 clientes, 250 ventas y dashboard con HTTP 200. | CRM local con copia de datos; base original intacta | Volver a `d415333` y al destino `cesarcontrol_apf3_expo`; conservar respaldos fuera de Git. |

## Secuencia de aprobación de producción

1. Registrar alcance, migraciones y riesgo; enlazar el commit APF3.
2. Verificar CI y respaldo restaurable de la base objetivo.
3. Obtener aprobación del responsable del entorno/proveedor cuando sea necesaria.
4. Desplegar el mismo commit probado y registrar URL, hora y resultado de health check.
5. Ejecutar prueba funcional breve: login, permisos, consulta, venta, stock y salida.
6. Si falla, detener nuevas migraciones, volver al artefacto previo y restaurar según el procedimiento documentado; registrar el incidente.
