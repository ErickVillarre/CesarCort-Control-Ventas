# CesarControl CRM/ERP — despliegue APF2

Rama de despliegue: apf2-vercel, basada en crm-erp-permisos-modulos. React/Vite para frontend, Laravel 12 para API y MySQL para datos.

## Estado y alcance

Esta entrega prepara la publicacion del frontend en Vercel. La API publica y MySQL externo estan pendientes. El modo parcial muestra un aviso y deshabilita el acceso hasta conectar una API real. Ver [estado del despliegue](docs/APF2/estado-despliegue.md).

El commit de despliegue incluye configuracion, ajustes de URLs, aviso de API pendiente y documentacion. Las correcciones funcionales APF2 anteriores se conservan como cambios locales separados; no se incluyen automaticamente en esta publicacion. Las evidencias APF2 historicas describen esa instalacion local.

## Desarrollo local

PHP 8.2+, Composer, MySQL, Node 22.12+ y npm. Configurar .env desde los ejemplos solo si falta, usando la conexion propia y una APP_KEY local. Respaldar datos antes de migrar. No ejecutar reinicios de base ni seeders contra una base existente sin revisar su alcance.

~~~powershell
cd backend
composer install
php artisan migrate:status
php artisan serve --host=127.0.0.1 --port=8000
~~~

Otra terminal:

~~~powershell
cd frontend
npm ci
npm run dev
~~~

Frontend local configurado con VITE_API_URL=http://127.0.0.1:8000/api, solo en .env ignorado. Health de Laravel: /up. La instalacion APF2 local completa tambien tiene /api/health con comprobacion MySQL; la API remota aun no existe.

## Verificacion y build parcial

~~~powershell
cd frontend
npm run lint
$env:DEPLOYMENT_MODE='frontend-only'
npm run build
~~~

Para publicar con API, quitar ese modo y configurar VITE_API_URL con su URL HTTPS real terminada en /api. Nunca subir .env, credenciales, dumps, vendor ni node_modules.

## Publicacion y rollback

Vercel: Root Directory frontend, Vite, npm ci, npm run build, dist. La CLI debe ejecutarse desde la raiz del repositorio/copia limpia con esa configuracion. Publicar primero preview y verificar antes de produccion. [Pasos y rollback](docs/APF2/despliegue-vercel.md), [variables](docs/APF2/variables-entorno.md), [pruebas](docs/APF2/pruebas-despliegue.md).

Para rollback, volver al despliegue verificado anterior del mismo proyecto y conservar datos/claves. Dockerfile de API preparado, pendiente de construccion y comprobacion en un entorno con Docker. No se publica la base local de XAMPP.
