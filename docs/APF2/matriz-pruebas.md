# Matriz de pruebas APF2

Fecha 2026-10-09. Los resultados automatizados no sustituyen una validacion de produccion. Las pruebas Laravel usan SQLite en memoria; HTTP y navegador usan MySQL local.

| Caso | Resultado | Evidencia real |
| --- | --- | --- |
| T01 login admin | Aprobado | API login y perfil 200; navegador y backend-tests.txt |
| T02 gerente y restriccion usuarios/roles | Aprobado | Login 200, roles/empleados 403; api.json y prueba Laravel |
| T03 vendedor y restriccion configuracion | Aprobado | Login 200, roles/empleados 403; menu filtrado y prueba Laravel |
| T04 venta sin stock | Aprobado | 422, ventas/pagos sin insertar, stock intacto; test_t04 |
| T05 venta credito | Aprobado | MySQL HTTP 201, stock 46 a 45, saldo 0 a 29.50; venta-credito-mysql.json; prueba Laravel saldo 138 |
| T06 datos invalidos | Aprobado | 422 por campos/productos duplicados/factura invalida; 403 descuento/precio no autorizado |
| T07 Laravel | Aprobado | 14 passed, 63 assertions, exit 0; backend-tests.txt |
| T08 lint/build | Aprobado | Exit 0 en frontend-lint.txt y frontend-build.txt; advertencia Browserslist documentada |
| T09 health API | Aprobado | /api/health 200 con database=connected; anonimo /productos 401; api.json |
| T10 restaurar respaldo | Aprobado | apf2_restore_20261009_081448: 43 tablas restauradas y contenido identico; restauracion.json |

Adicional: cuenta descuenta saldo, credito insuficiente revierte cabecera/detalle/stock, caja impide segundo cobro, cliente con historial se desactiva, token revocado devuelve 401, roles historicos/usuarios inactivos rechazados, seeders idempotentes.

Navegador: Aprobado: tres roles, recarga, logout, modulos y consola sin errores.

Venta en efectivo desde formulario completo: Aprobado: HTTP 201, total 35.40, vuelto 64.60. Ver venta-efectivo-interfaz.json.

Las salidas completas de comandos estan en [backend-tests.txt](evidencias/backend-tests.txt), [frontend-lint.txt](evidencias/frontend-lint.txt) y [frontend-build.txt](evidencias/frontend-build.txt). JSON de comprobaciones contiene estados/contadores y datos sinteticos, sin tokens ni contrasenas.

Observaciones corregidas durante la verificacion: flags de Vite consumidos por PowerShell, advertencia de claves React en alertas, mapeo de efectivo/contado y saldo antiguo en el carrito. La compilacion remota, Docker y pruebas publicas quedan pendientes.
