# Despliegue CesarControl en Vercel

## Configuracion

Proyecto: cesarcontrol-apf2, equipo notrabajamos. Root Directory: frontend. Framework: Vite. Node 22.x. Install Command: npm ci. Build Command: npm run build. Output Directory: dist. El vercel.json existente conserva el retorno SPA y excluye /api/.

Ejecutar la CLI desde la raiz del repositorio o de su copia limpia cuando Root Directory sea frontend. Ejecutarla desde frontend con ese mismo Root Directory produciria una busqueda frontend/frontend. [Configuracion oficial](https://vercel.com/docs/builds/configure-a-build#root-directory).

## Sin API publica: frontend parcial

Configurar DEPLOYMENT_MODE=frontend-only para Preview y Production. No configurar una URL falsa en VITE_API_URL. El build fuerza la URL incorporada a vacia, muestra un aviso y deshabilita login y llamadas API. Este despliegue publica la interfaz, no el sistema funcional completo.

~~~powershell
$env:DEPLOYMENT_MODE='frontend-only'
cd frontend
npm ci
npm run lint
npm run build
~~~

## Con API publica

Quitar DEPLOYMENT_MODE=frontend-only. Configurar VITE_API_URL con la URL HTTPS real terminada en /api. El build comprueba esquema, host y ruta y rechaza localhost, redes privadas y credenciales incorporadas. Configurar FRONTEND_URL y CORS_ALLOWED_ORIGINS en Laravel con los dominios Vercel autorizados. Las imagenes de clientes derivan de la base publica de la API. [Vite en Vercel](https://vercel.com/docs/frameworks/frontend/vite).

## Publicar el commit exacto

Revisar archivos staged y secretos. Commit solo en apf2-vercel; push sin --force. Crear copia limpia del commit: no ejecutar deploy desde un arbol con cambios funcionales locales que no esten en el commit. .vercelignore excluye backend, entornos locales, dependencias, logs y evidencias de la subida del frontend. .vercel esta ignorado por Git.

Desde esa copia limpia, iniciar sesion manualmente si es necesario, vincular solo el proyecto CesarControl y desplegar primero Preview:

~~~powershell
npx vercel login
npx vercel link --yes --team notrabajamos --project cesarcontrol-apf2
npx vercel --yes --scope notrabajamos
~~~

Verificar URL real, recursos, ausencia de 404, rutas directas y aviso parcial. Solo despues:

~~~powershell
npx vercel --prod --yes --scope notrabajamos
~~~

## API y MySQL

La API necesita hosting PHP 8.2+ o Docker; la base necesita MySQL gestionado con TLS. Dockerfile disponible, no validado con Docker en este equipo. El document root debe ser public; almacenamiento persistente para uploads y permisos de escritura para storage/bootstrap/cache. public/storage se excluye del contexto Docker por ser una junction Windows local; crearlo dentro del contenedor mediante php artisan storage:link.

No ejecutar el seeder demo en produccion: solo admite local/testing. Las cuentas remotas deben provisionarse de forma controlada con nuevas contrasenas. Revisar el plan de migraciones y respaldar antes de aplicarlo a una base existente; una migracion historica elimina columnas. [Despliegue Laravel](https://laravel.com/docs/12.x/deployment).

## Rollback

Desde Vercel, volver a un despliegue anterior verificado del mismo proyecto, o desplegar nuevamente su commit exacto. No se dispone de un despliegue anterior durante la primera publicacion. API: volver a la imagen/revision compatible conservando claves y datos. Restaurar respaldos solo en una base separada para verificar antes de cambiar conexion. No revertir datos con comandos destructivos ni cambiar main.
