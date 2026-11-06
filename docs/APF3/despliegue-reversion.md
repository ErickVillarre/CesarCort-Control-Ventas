# Despliegue remoto y reversión

## Estado inicial comprobado

- Rama inicial `feature/backend`, commit `67ccc12`; rama APF3 `apf3-integrador-ii` creada desde esa fuente local. No hubo `pull`, `reset`, `clean` ni sustitución de la carpeta de trabajo.
- El informe APF2 declara frontend en Vercel `https://cesarcontrol-apf2.vercel.app`, con API y MySQL locales. Esa URL es una referencia histórica, no la URL del APF3 ni evidencia de funcionamiento remoto completo actual.
- La URL histórica respondió `HTTP 200` a una solicitud `HEAD` el 6/11/2026; no se probó un flujo de negocio allí. No hay cuentas de GitHub/Vercel/hosting vinculadas a las herramientas disponibles en esta sesión, ni CLI `gh`/Vercel ni Docker local listos para publicar/probar el contenedor. `git push --dry-run` fue rechazado por credenciales inválidas y el intento real de publicar solo la rama APF3 falló por falta de autenticación; no se publicó ningún commit. El Dockerfile y rewrites preparados requieren pruebas reales.
- `frontend/src/api/axios.js` y el fallback de la landing apuntaban a `127.0.0.1` en el commit inicial. Por ello, un build publicado sin `VITE_API_URL` remoto no sería una entrega funcional.
- El 6/11 se copió la SQLite local ignorada a `D:\ventas-melamina-backups\APF3-2026-11-06\database-original.sqlite` y se probó una copia de restauración con el mismo SHA-256 `768e8f1a9cc97dabde75e9c8da914dd5884228ef0bd2df0c05e89bf6b3bab4b8`; `PRAGMA quick_check=ok`. Esa SQLite tenía 3 migraciones y 0 usuarios. El `.env` activo indica MySQL, pero la conexión TCP no respondió; **la base MySQL no se respaldó ni modificó**. Este backup no autoriza migraciones sobre MySQL.
- Al inicio, `ClienteController` fijaba adjuntos a `Storage::disk('local')`. La rama APF3 añadió `CLIENT_DOCUMENTS_DISK` y una ruta de descarga autenticada; el valor por defecto sigue siendo `local`, cuyo root es `storage/app/private`. Un contenedor efímero sin volumen persistente perdería los documentos al reiniciarse. Para producción se necesita configurar un disco duradero autorizado, migrar adjuntos existentes (incluidos los que puedan estar en el disco público heredado), y probar descarga autorizada y recuperación tras reinicio. Cambiar solo `FILESYSTEM_DISK` no selecciona el disco de documentos: se usa `CLIENT_DOCUMENTS_DISK`.

## Puerta antes de producción

Opciones técnicas documentadas, **sin proveedor ni plan contratado**: Vercel indica que una SPA Vite necesita `vercel.json` con rewrite a `index.html` para rutas directas ([documentación oficial](https://vercel.com/docs/frameworks/frontend/vite)); Render admite PHP/Laravel mediante Docker ([guía oficial](https://render.com/docs/deploy-php-laravel-docker)) y describe MySQL como servicio privado con disco persistente ([guía oficial](https://render.com/docs/deploy-mysql)). Estas guías no demuestran disponibilidad de cuenta, cuota gratuita ni precio para este proyecto; cualquier plan con cargo requiere autorización previa.

1. Confirmar cuenta/proveedor y plan sin cargos no autorizados; resolver la autenticación GitHub para publicar solo la rama APF3; comprobar acceso a frontend, servicio PHP/Docker, MySQL externo y almacenamiento duradero para adjuntos.
2. Probar localmente el mismo commit con CI y base de prueba aislada. Confirmar que la demo es sintética y que los activos publicados tienen derechos de uso.
3. Crear base remota separada. Registrar límites, usuario de mínimo privilegio y respaldo exportado; restaurar ese respaldo en entorno aislado y verificar integridad antes de migrar. La MySQL local actual tampoco se modifica mientras no exista respaldo restaurable.
4. Configurar secretos solo en el proveedor: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `CLIENT_DOCUMENTS_DISK` y credenciales del almacenamiento si aplica; definir `CORS_ALLOWED_ORIGINS` con dominios HTTPS reales. El frontend usa `VITE_API_URL` terminado en `/api`; la landing también requiere `VITE_CRM_LOGIN_URL` real. No publicar valores de secretos en Git ni evidencias.
5. Ejecutar migraciones controladas (`php artisan migrate --force`) sobre la base nueva tras el backup; usar seeder demo solo en entorno separado. Confirmar HTTPS, `/up` sin información sensible y conexión a BD mediante una operación autorizada.
6. Publicar frontend y landing desde el commit APF3. Tras cambiar variables de Vercel, crear nuevo deployment y verificar rutas directas al recargar.
7. Desde una red externa real, comprobar login de admin/gerente/vendedor, 401/403, consultas, venta, stock, crédito si aplica, logout, accesibilidad y vista móvil/tableta/laptop. Registrar dispositivo/red realmente usados.
8. Vincular URL/identificador de deployment, SHA de commit, ejecución CI y evidencia de pruebas. Solo entonces declarar el sistema remoto completo.

## Reversión

Para un fallo de interfaz/API sin cambio de esquema incompatible, volver a publicar el último commit y configuración conocidos como buenos, y verificar health check y operación. Para fallo de migración o corrupción, detener escrituras, preservar logs y la base afectada, restaurar el respaldo **de esa misma base** en un entorno seguro, verificar integridad y reabrir solo tras prueba funcional. No ejecutar `migrate:fresh`, `db:wipe`, borrados masivos ni restaurar la SQLite local como si fuera la MySQL de producción.

| Evento | Fecha/hora | Commit/deployment | Respaldo y restauración | Resultado |
| --- | --- | --- | --- | --- |
| Publicación APF3 | Pendiente | Pendiente | Pendiente | Pendiente |
| Reversión | No ejecutada | No aplica | No aplica | No aplica |
