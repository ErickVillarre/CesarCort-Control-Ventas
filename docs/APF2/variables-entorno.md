# Variables de entorno del despliegue

| Variable | Entorno | Valor / estado |
| --- | --- | --- |
| DEPLOYMENT_MODE | Vercel Preview y Production | frontend-only mientras falte API |
| VITE_API_URL | Vercel | Vacia en publicacion parcial; URL HTTPS real terminada en /api para sistema conectado |
| APP_ENV | Hosting API | production |
| APP_DEBUG | Hosting API | false |
| APP_KEY | Hosting API, secreta | Generar clave propia; mantener estable; no proporcionada |
| APP_URL | Hosting API | URL HTTPS real de API pendiente |
| FRONTEND_URL | Hosting API | https://cesarcontrol-apf2.vercel.app cuando se configure el hosting API |
| CORS_ALLOWED_ORIGINS | Hosting API | https://cesarcontrol-apf2.vercel.app; agregar solo previews expresamente autorizadas, sin comodin |
| SANCTUM_STATEFUL_DOMAINS | Hosting API | Solo necesario al habilitar cookies stateful; actualmente bearer tokens |
| DB_CONNECTION | Hosting API | mysql |
| DB_HOST, DB_PORT, DB_DATABASE | Hosting API | Conexion externa pendiente |
| DB_USERNAME, DB_PASSWORD | Hosting API, secretos | Usuario restringido; valores pendientes |
| MYSQL_ATTR_SSL_CA | Hosting API | Ruta de CA del proveedor para TLS |

Nunca colocar secretos en VITE_*: se incorporan al navegador. No subir archivos .env, claves, credenciales ni dumps. La plantilla backend/.env.production.example contiene nombres sin valores privados. MySQL usa TCP con TLS, no HTTPS.

Estado remoto comprobado: DEPLOYMENT_MODE configurado como frontend-only en Preview y Production. VITE_API_URL ausente. Las variables Laravel/MySQL anteriores son preparacion para el hosting futuro, no una conexion remota ya operativa.

Desarrollo local conserva VITE_API_URL=http://127.0.0.1:8000/api en .env ignorado y la conexion existente a ventas-melamina. Ninguno de esos valores configura la produccion. Configurar nuevas contrasenas para las cuentas remotas; las cuentas de demostracion local no se publican.
