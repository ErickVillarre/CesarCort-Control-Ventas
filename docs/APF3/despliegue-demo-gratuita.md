# Ruta de despliegue gratuito para la exposición

**Elección del propietario:** demo gratuita con Vercel (CRM) y Railway Trial (Laravel + MySQL). **Estado al 6/11/2026:** cuentas aún sin iniciar sesión; no existe URL pública APF3. Esta ruta usa **solo datos sintéticos**. La copia local `cesarcontrol_datos_expo`, que contiene registros del propietario, no se sube a los proveedores.

## Alcance y límite de la opción

- [Vercel Hobby](https://vercel.com/docs/plans/hobby) es gratuito para uso personal/no comercial; se usará para esta demostración académica. El frontend React/Vite ya tiene `frontend/vercel.json` para rutas directas y exige `VITE_API_URL` HTTPS.
- [Railway Trial](https://docs.railway.com/pricing/free-trial) ofrece un crédito único de US$5 por **hasta 30 días**, sin tarjeta, y permite desplegar código y MySQL. Luego pasa al plan Free con US$1 de crédito mensual; si se agota, la aplicación puede suspenderse. El volumen de la prueba puede eliminarse 30 días después de que termine el crédito. **No es alojamiento permanente garantizado.**
- El [template MySQL de Railway](https://docs.railway.com/databases/mysql) crea una base privada dentro del proyecto; se enlazará a Laravel mediante referencias a `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD` y `MYSQLDATABASE`, sin copiar contraseñas al repositorio.
- El backend se construye desde `/backend/Dockerfile` y escucha el `PORT` que Railway entrega. La CI también comprueba el contenedor en un puerto distinto de 80. [Guía de monorepos](https://docs.railway.com/deployments/monorepo) y [health checks](https://docs.railway.com/deployments/healthchecks).
- Si Railway asigna una prueba limitada, puede restringir la red o los buckets. Antes de afirmar que cumple APF3 se comprobará API, MySQL, adjuntos, CORS y operaciones desde una red externa. No se contratará un plan de pago automáticamente.

## Secuencia al disponer de las sesiones

1. Crear Vercel y Railway con **Continue with GitHub**, sin tarjeta. Vincular exclusivamente la rama `apf3-integrador-ii`; no usar `main` ni otras ramas.
2. En Railway, crear proyecto y servicio MySQL privado. Registrar identificador, región, plan, límites y estado de volumen. Verificar que la base nueva está vacía; conservar un respaldo/registro previo antes de importaciones o migraciones.
3. Crear servicio API desde este repositorio con directorio raíz `/backend` y Dockerfile. Configurar `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` privado, `APP_URL` HTTPS y `DB_CONNECTION=mysql`. Mapear `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` a las variables del servicio MySQL mediante referencias de Railway. Configurar `FRONTEND_URL` y `CORS_ALLOWED_ORIGINS` solo para el dominio Vercel final. No copiar `.env` local ni la base del propietario.
4. Para documentos de clientes, adjuntar un volumen **privado** al servicio API en `/var/www/html/storage/app/private` y mantener `CLIENT_DOCUMENTS_DISK=local`. Probar que un archivo sintético autorizado sobrevive al reinicio. Si el plan no permite ese volumen, la función de adjuntos no se declarará desplegada.
5. Antes de habilitar el dominio público, ejecutar migraciones controladas en la MySQL nueva. Poblar con el seeder APF3 **solo sobre esa base vacía**, en una ejecución aislada con `APP_ENV=local`/`APF3_DEMO_SEED=true`; el proceso web final vuelve a `APP_ENV=production` y `APP_DEBUG=false`. La contraseña demo remota se genera como secreto nuevo en Railway y no se escribe en Git ni en la presentación. El seeder comprueba que no haya datos ajenos a APF3.
6. Configurar `frontend/` como directorio raíz del proyecto Vercel, rama APF3, build `npm run build`, salida `dist`. Poner `VITE_API_URL=https://<dominio-api>/api` y volver a desplegar si se cambia. Después fijar el dominio Vercel real en CORS de Laravel.
7. Probar por HTTPS desde otra red: ruta directa del CRM al recargar, health check sin secretos, login de tres roles, 401/403, productos/clientes, venta y stock, logout, créditos, accesibilidad y móvil/tableta/laptop. Guardar URL, commit, run CI, fecha y evidencias sin datos personales.
8. Antes de que caduque el crédito, exportar un respaldo MySQL restaurable y decidir con el propietario si se elimina la demo o se migra a un plan duradero. No dejar datos personales en un servicio que pueda expirar.

## Criterio para la sustentación

La versión remota solo se declarará **desplegada** cuando frontend, API y MySQL tengan URL/servicio reales, funcionen conjuntamente y se prueben desde una red ajena a esta PC. Una cuenta creada o un frontend Vercel aislado no cumplen ese criterio. La [guía principal de despliegue y reversión](despliegue-reversion.md) conserva los pasos de respaldo y rollback.
