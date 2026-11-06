# Control de cambios APF3

Cada cambio se registra antes de producción con motivo, alcance/impacto, referencia al commit, revisión técnica, aprobación, despliegue y reversión posible. Un commit o un test aprobado no sustituyen la aprobación operativa de publicar datos o cambiar hosting.

| ID | Motivo e impacto | Commit | Revisión/aprobación | Despliegue | Reversión |
| --- | --- | --- | --- | --- | --- |
| APF3-001 | Preservar trabajo inicial y separar el avance. Rama `apf3-integrador-ii` creada desde `67ccc12`; no altera ramas anteriores. | Base `67ccc12`; código en `17bfdd2` y `4457ff5` | Revisión local del árbol y `git status` realizada; aprobación académica pendiente | No desplegado | Volver al commit base sin borrar rama ni datos. |
| APF3-002 | Recuperar integración de identidad/permisos del checkout inicial y agregar solicitudes. Impacta migraciones, API y acceso. | `17bfdd2` | `php artisan test`: 6 pruebas/61 aserciones; HTTP 401/403 y acceso sin cambio de permisos. Aprobación de producción pendiente. | No desplegado | Respaldar BD objetivo; revertir código y restaurar **esa** base si la migración no es compatible. |
| APF3-003 | Accesibilidad visual/voz y actualización de diseño. Impacta interfaz de landing y CRM. | `4457ff5` | Lint/build aprobados, seis modos y operación de solicitud con voz comprobados instrumentalmente; audio físico pendiente. | No desplegado | Redeploy del commit previo de frontend. |
| APF3-004 | Demo reducida y CI. Impacta solo base aislada y workflow; no se limpian datos existentes. | Seeder `17bfdd2`; workflow `f561b58` | Seeder idempotente, 12 productos/5 clientes/4 ventas/1 crédito; CI **local** validada, ejecución Actions pendiente. | No desplegado | No ejecutar seeder en producción; restaurar base demo aislada si es necesario. |
| APF3-005 | Despliegue remoto completo. Impacta variables de entorno, esquema, adjuntos y disponibilidad. | Pendiente | Requiere credenciales GitHub válidas, proveedor/plan autorizado, respaldo MySQL restaurable y almacenamiento duradero | No desplegado | Volver al deployment previo y restaurar backup validado según [despliegue-reversion.md](despliegue-reversion.md). |

## Secuencia de aprobación de producción

1. Registrar alcance, migraciones y riesgo; enlazar el commit APF3.
2. Verificar CI y respaldo restaurable de la base objetivo.
3. Obtener aprobación del responsable del entorno/proveedor cuando sea necesaria.
4. Desplegar el mismo commit probado y registrar URL, hora y resultado de health check.
5. Ejecutar prueba funcional breve: login, permisos, consulta, venta, stock y salida.
6. Si falla, detener nuevas migraciones, volver al artefacto previo y restaurar según el procedimiento documentado; registrar el incidente.
