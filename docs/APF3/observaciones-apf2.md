# Observaciones APF2 contrastadas con el estado local

Auditoría del **6 de noviembre de 2026**. Se contrastó el informe APF2 `APF2_CesarControl_Curso_Integrador_II_Informe_Completo (1).docx` (secciones 6–9) con el commit local inicial `67ccc12` y su árbol de archivos. Las afirmaciones del APF2 se mantienen como antecedentes; no se trasladan automáticamente al APF3.

| Observación o resultado APF2 | Evidencia histórica | Comprobación local inicial APF3 | Estado al iniciar APF3 |
| --- | --- | --- | --- |
| Frontend publicado en Vercel; login público y negocio remoto pendientes | APF2 §§6.2, 7.3, 8; URL declarada `https://cesarcontrol-apf2.vercel.app` | `frontend/src/api/axios.js` usa `http://127.0.0.1:8000/api`; `landing-page/src/App.jsx` tiene el mismo valor por defecto. No hay API/BD remotas configuradas en el repositorio. | **Pendiente** confirmar la publicación histórica y crear un despliegue integral nuevo. |
| Roles admin, gerente y vendedor probados localmente | APF2 §§5.3, 7.3: resultado informado | En `67ccc12`, `backend/routes/api.php` referencia middlewares y controladores ausentes; `AuthController.php` contiene solo `login` aunque las rutas piden `me`, `logout` y `changePassword`; faltaban modelos `Role`, `Permission` y `Empleado`. | **Regresión/estado no reproducido**: reparar y volver a probar. |
| 14 pruebas y 63 aserciones aprobadas en APF2 | APF2 §§1, 6.4, 7.3 | El árbol inicial solo contiene las pruebas `ExampleTest.php`; no permite atribuir las 14 pruebas al checkout actual. | **Pendiente** ejecutar y registrar la suite de esta rama. |
| Ventas, stock y créditos locales preparados | APF2 §§5.4, 7.4 | Existen controladores, migraciones y vistas; `VentaController::store` descuenta stock solo al finalizar/cobrar. No se documentó una prueba reproducida de este checkout. | **Parcial** por presencia de código, **pendiente** validación funcional y consistencia. |
| Respaldo y restauración de `ventas-melamina` realizados | APF2 §§4.5, 5.1, 6.4 | El respaldo histórico no está incluido en la carpeta del proyecto. El 6/11 se respaldó y restauró una copia de la SQLite local: ver [despliegue-reversion.md](despliegue-reversion.md). La conexión MySQL activa no respondió; no fue modificada. | **SQLite comprobada**; **MySQL actual pendiente de respaldo** antes de cualquier migración. |
| Dockerfile preparado, sin ejecutar | APF2 §§1, 7.3, 8 | No hay Dockerfile versionado en el árbol `67ccc12`. | **Pendiente** preparar y probar un empaquetado de API compatible con el proveedor elegido. |
| Alerta de 19 vulnerabilidades en Vercel con discrepancia local | APF2 §§7.6, 8 | Auditoría inicial `npm audit` del 6/11/2026: CRM 16 paquetes (13 altos, 2 moderados, 1 bajo); landing 2 altos. Tras actualizar lockfiles compatibles, una nueva auditoría completa arrojó **0** en ambos. | **Levantada en los lockfiles actuales**; el número 19 histórico no describía este checkout. Falta verificar CI/remoto. |
| Metas: disponibilidad ≥99.5 %, API p95 ≤2 s, carga inicial ≤3 s, errores <1 % | APF2 §7.4 | El APF2 las etiqueta como metas, sin serie de medición. | **Pendiente** medir línea base con método y muestras; véase [capacidad.md](capacidad.md). |
| Evidencias Scrum y actas incompletas | APF2 §§3.6, 9.1 | No hay acta ni backlog APF2 en este checkout. | **Pendiente** registrar Sprint 6 y celebrar Review/retrospectiva reales. |

La URL Vercel histórica del APF2 respondió `HTTP 200` a una solicitud `HEAD` el 6/11/2026, según comprobación de esta sesión. Esa respuesta solo verifica que el recurso web respondió; no demuestra login ni API/BD de APF3.

### Causa histórica de la regresión de identidad/operaciones

El commit `47cf8e1` contenía `CajaController`, `MarketingController`, `ReporteController`, los middlewares `CheckPermission` y `EnsurePasswordWasChanged`, y los modelos `Role`, `Permission` y `Empleado`. El commit `ddd87c1` (`Revert "Implement role-based CRM ERP modules"`) eliminó esos archivos, confirmado por `git show --stat ddd87c1`. En el commit de partida `67ccc12` reaparecieron rutas y referencias a parte de esa funcionalidad sin todos los archivos de soporte. La observación APF3 es, por tanto, una regresión del árbol versionado; restaurar las piezas compatibles y probarlas es trabajo de integración, no una historia nueva de construcción completa de esos módulos.

## Alcance funcional heredado

El proyecto ya contiene código de clientes, productos, ventas, créditos, caja, mantenimiento, marketing y reportes. El Sprint 6 no vuelve a declarar su construcción desde cero. Su trabajo es recuperar la integración del checkout actual, implementar solicitudes y accesibilidad, asegurar pruebas/CI y completar el despliegue remoto.

## Datos de demostración heredados

`DevelopmentDemoDataSeeder` crea hasta 50 productos generados además de 14 referencias, 40 clientes más uno anónimo y hasta 250 ventas. Usa identificadores ficticios con forma de DNI/RUC/teléfono. No debe ejecutarse sobre la base existente ni usarse como contenido final de demostración. La alternativa APF3 debe ser idempotente, separada y con datos sintéticos de menor volumen; ninguna limpieza masiva de datos actuales está autorizada.

## Estado local al corte APF3

Se restauraron desde `47cf8e1` los controladores Caja, Marketing y Reportes que el revert `ddd87c1` había eliminado; se reconstruyeron identidad/permisos y rutas faltantes, y se probaron ventas/stock/caja en SQLite aislada. El seeder APF3 optativo produjo 12 productos, 5 clientes nombrados, 4 ventas y 1 crédito, y pasó una repetición idempotente. La suite actual pasó 6 pruebas y 61 aserciones; los lockfiles actualizados quedaron con 0 avisos en `npm audit` y `composer audit --locked`. La auditoría APF2 no se considera levantada en producción hasta ejecutar CI del commit publicado y desplegar frontend, API y MySQL remotos con pruebas externas.
