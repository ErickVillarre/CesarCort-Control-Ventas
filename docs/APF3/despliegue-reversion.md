# Despliegue remoto y reversión

**Alcance actual para exposición:** la landing separada fue retirada; el despliegue pendiente comprende el frontend CRM, Laravel y MySQL. Las menciones de landing en el estado inicial son antecedentes históricos.

## Estado inicial comprobado

- Rama inicial `feature/backend`, commit `67ccc12`; rama APF3 `apf3-integrador-ii` creada desde esa fuente local. No hubo `pull`, `reset`, `clean` ni sustitución de la carpeta de trabajo.
- El informe APF2 declara frontend en Vercel `https://cesarcontrol-apf2.vercel.app`, con API y MySQL locales. Esa URL es una referencia histórica, no la URL del APF3 ni evidencia de funcionamiento remoto completo actual.
- La URL histórica respondió `HTTP 200` a una solicitud `HEAD` el 6/11/2026; no se probó un flujo de negocio allí. El primer `git push` falló por autenticación, pero Git Credential Manager permitió publicar después **solo** [la rama APF3](https://github.com/ErickVillarre/CesarCort-Control-Ventas/tree/apf3-integrador-ii). [GitHub Actions aprobó frontend, landing y backend para `3874929`](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774). Vercel y el hosting de API/MySQL no están vinculados; Docker no está instalado localmente. El Dockerfile y los rewrites preparados requieren prueba real en el proveedor.
- `frontend/src/api/axios.js` apuntaba a `127.0.0.1` en el commit inicial. Por ello, un build publicado sin `VITE_API_URL` remoto no sería una entrega funcional.
- El 6/11 se copió la SQLite local ignorada a `D:\ventas-melamina-backups\APF3-2026-11-06\database-original.sqlite` y se probó una copia de restauración con el mismo SHA-256 `768e8f1a9cc97dabde75e9c8da914dd5884228ef0bd2df0c05e89bf6b3bab4b8`; `PRAGMA quick_check=ok`. Esa SQLite tenía 3 migraciones y 0 usuarios. En el primer corte MySQL no respondió y este backup no autorizaba migraciones sobre ella.
- Al inicio, `ClienteController` fijaba adjuntos a `Storage::disk('local')`. La rama APF3 añadió `CLIENT_DOCUMENTS_DISK` y una ruta de descarga autenticada; el valor por defecto sigue siendo `local`, cuyo root es `storage/app/private`. Un contenedor efímero sin volumen persistente perdería los documentos al reiniciarse. Para producción se necesita configurar un disco duradero autorizado, migrar adjuntos existentes (incluidos los que puedan estar en el disco público heredado), y probar descarga autorizada y recuperación tras reinicio. Cambiar solo `FILESYSTEM_DISK` no selecciona el disco de documentos: se usa `CLIENT_DOCUMENTS_DISK`.

## Entorno local de exposición

Con MySQL de XAMPP ya activo, se respaldó la base original `ventas-melamina` a `D:\ventas-melamina-backups\APF3-2026-11-06\mysql-original-before-expo-20261106-074410.sql`, SHA-256 `5cd0e856d1decbbe6c42bf648c1b5ccbcfd3edad34e42f9d2fa712a686122004`. La restauración aislada reprodujo 43 tablas y los conteos originales. Esos conteos siguieron iguales después de la preparación: 19 migraciones, 18 usuarios, 82 productos, 43 clientes y 250 ventas.

El primer montaje de exposición usó `cesarcontrol_apf3_expo`, una base MySQL nueva con 21 migraciones y datos sintéticos APF3. [Su prueba funcional](evidencias/mysql-expo-api-resultados.json) verificó tres roles, 401/403 esperados, venta y stock coherentes, y 0/98 errores 5xx. Este entorno es local y la base sintética queda disponible como alternativa.

Después de solicitar sus registros originales, se hizo otro respaldo restaurado de `ventas-melamina`: `D:\ventas-melamina-backups\APF3-2026-11-06\mysql-original-before-expo-20261106-080312.sql`, SHA-256 `0a50ba9740c530b60b77e74fda2a3ef7b722a4ab1bf2b51f356f04a7b7d562aa`. La copia persistente `cesarcontrol_datos_expo` recibió las cuatro migraciones pendientes y tres usuarios sintéticos de acceso; conserva 82 productos, 43 clientes y 250 ventas del respaldo. El iniciador apunta ahora a esa copia; Laravel escucha en `127.0.0.1:8000` y el CRM en `127.0.0.1:5173`. [Verificación API/navegador](evidencias/mysql-datos-expo-resultados.json). La base `ventas-melamina` original quedó sin cambios; para sincronizar datos nuevos de ella se requeriría un nuevo respaldo/copia controlada.

## Puerta antes de producción

Opciones técnicas documentadas, **sin proveedor ni plan contratado**: Vercel indica que una SPA Vite necesita `vercel.json` con rewrite a `index.html` para rutas directas ([documentación oficial](https://vercel.com/docs/frameworks/frontend/vite)); Render admite PHP/Laravel mediante Docker ([guía oficial](https://render.com/docs/deploy-php-laravel-docker)) y describe MySQL como servicio privado con disco persistente ([guía oficial](https://render.com/docs/deploy-mysql)). Estas guías no demuestran disponibilidad de cuenta, cuota gratuita ni precio para este proyecto; cualquier plan con cargo requiere autorización previa.

1. Confirmar cuenta/proveedor y plan sin cargos no autorizados; comprobar acceso a frontend, servicio PHP/Docker, MySQL externo y almacenamiento duradero para adjuntos.
2. Confirmar CI aprobada del commit que se desplegará y base de prueba aislada. La rama ya tuvo una [ejecución aprobada para `3874929`](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774). Confirmar que la demo es sintética y que los activos publicados tienen derechos de uso.
3. Crear base remota separada. Registrar límites, usuario de mínimo privilegio y respaldo exportado; restaurar ese respaldo en entorno aislado y verificar integridad antes de migrar. El respaldo de la MySQL local de exposición no sustituye el respaldo de esa futura base remota.
4. Configurar secretos solo en el proveedor: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `CLIENT_DOCUMENTS_DISK` y credenciales del almacenamiento si aplica; definir `CORS_ALLOWED_ORIGINS` con el dominio HTTPS real. El frontend usa `VITE_API_URL` terminado en `/api`. No publicar valores de secretos en Git ni evidencias.
5. Ejecutar migraciones controladas (`php artisan migrate --force`) sobre la base nueva tras el backup; usar seeder demo solo en entorno separado. Confirmar HTTPS, `/up` sin información sensible y conexión a BD mediante una operación autorizada.
6. Publicar el CRM desde el commit APF3. Tras cambiar variables de Vercel, crear nuevo deployment y verificar rutas directas al recargar.
7. Desde una red externa real, comprobar login de admin/gerente/vendedor, 401/403, consultas, venta, stock, crédito si aplica, logout, accesibilidad y vista móvil/tableta/laptop. Registrar dispositivo/red realmente usados.
8. Vincular URL/identificador de deployment, SHA de commit, ejecución CI y evidencia de pruebas. Solo entonces declarar el sistema remoto completo.

## Reversión

Para un fallo de interfaz/API sin cambio de esquema incompatible, volver a publicar el último commit y configuración conocidos como buenos, y verificar health check y operación. Para fallo de migración o corrupción, detener escrituras, preservar logs y la base afectada, restaurar el respaldo **de esa misma base** en un entorno seguro, verificar integridad y reabrir solo tras prueba funcional. No ejecutar `migrate:fresh`, `db:wipe`, borrados masivos ni restaurar la SQLite local como si fuera la MySQL de producción.

| Evento | Fecha/hora | Commit/deployment | Respaldo y restauración | Resultado |
| --- | --- | --- | --- | --- |
| Publicación APF3 | Pendiente | Pendiente | Pendiente | Pendiente |
| Reversión | No ejecutada | No aplica | No aplica | No aplica |
