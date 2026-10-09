# Instalacion local APF2

## Requisitos

PHP 8.2 o superior con PDO MySQL, PDO SQLite para pruebas, mbstring, XML, DOM, fileinfo, openssl y zip; Composer; MySQL/MariaDB; Node 22 y npm; Git. En esta laptop PHP y MySQL estan en `C:\xampp` y Composer en `..\.local-tools\composer.ps1`.

## Copia nueva

```powershell
git clone --single-branch --branch crm-erp-permisos-modulos https://github.com/ErickVillarre/CesarCort-Control-Ventas.git CesarCort-APF2
cd CesarCort-APF2
git switch -c apf2-vercel
```

Los cambios APF2 de esta entrega aun son locales: el clon remoto por si solo no los incluye. Para repetir esta version, transferir los archivos revisados o publicar la rama tras autorizacion. Esta laptop usa un worktree vinculado al repositorio anterior; conservar `CesarCort-feature-backend/.git`.

## Backend

```powershell
cd backend
Copy-Item .env.example .env  # solo si .env no existe
composer install --no-interaction --prefer-dist
php artisan key:generate   # solo para una APP_KEY nueva/vacia
php artisan storage:link
```

Configurar en `.env` los datos locales propios, sin compartirlo. En esta instalacion se reutilizaron los valores existentes de conexion a `ventas-melamina` en `127.0.0.1:3306` y su APP_KEY. No se regenero una clave que ya existia.

Iniciar MySQL desde XAMPP. Antes de migrar una base existente, crear y verificar un respaldo fuera del repositorio:

```powershell
mysqldump --host=127.0.0.1 --user=USUARIO --password --single-transaction --routines --triggers --events --hex-blob --result-file=C:\RESPALDOS\respaldo-fechado.sql NOMBRE_BASE
php artisan migrate:status
php artisan migrate
php artisan db:seed
php artisan test
```

No ejecutar los seeders antiguos `DevelopmentDemoDataSeeder` o `RolesAndDemoUsersSeeder` para esta entrega: pueden modificar cuentas previas. `DatabaseSeeder` llama a los tres seeders APF2 en orden. Crean datos con prefijo APF2, no reinician stock/saldos al repetirse y no cargan demos en produccion. Se comprobo su idempotencia en pruebas.

Las cuatro credenciales aleatorias se guardan localmente en `backend/storage/app/private/apf2-credentials.json`; abrir ese archivo en el equipo, sin agregarlo a Git ni incluirlo en capturas. Los correos son `apf2.admin@example.test`, `apf2.gerente@example.test`, `apf2.vendedor1@example.test` y `apf2.vendedor2@example.test`.

## Frontend

```powershell
cd ..\frontend
Copy-Item .env.example .env  # solo si falta
npm ci
npm run lint
npm run build
```

Desarrollo: `VITE_API_URL=http://127.0.0.1:8000/api`. Vercel requiere una URL HTTPS real. No se expone ninguna credencial del backend mediante variables VITE.

## Ejecutar en dos terminales

Desde el proyecto, terminal API:

```powershell
cd backend
& 'C:\xampp\php\php.exe' artisan serve --host=127.0.0.1 --port=8000
```

Terminal frontend:

```powershell
cd frontend
node node_modules/vite/bin/vite.js --host=127.0.0.1 --port=5173 --strictPort
```

Se invoca Vite directamente porque el wrapper npm de PowerShell del equipo consumio los argumentos de `npm run dev -- ...`. El lanzador `scripts/iniciar-apf2.ps1` abre ambos servicios como procesos independientes ocultos y deja registros `apf2-*.log` ignorados por Git. No ocupa puertos ya usados. No hay landing en la rama del curso.

Frontend: http://127.0.0.1:5173. Salud con conexion MySQL: http://127.0.0.1:8000/api/health. `/up` comprueba Laravel. `/api` no es el endpoint de salud.

El token se conserva en sessionStorage para recargas de la pestaña, con vencimiento servidor de 8 horas o 7 dias al ampliar la sesion. Cerrar la pestaña elimina esa persistencia. Logout revoca el token; un 401 limpia la sesion. Los permisos siempre se verifican en la API.

## Restauracion

Crear una base **nueva y vacia**, importar el SQL con el cliente MySQL (`source C:/RESPALDOS/respaldo-fechado.sql`) y comparar tablas/conteos. Nunca restaurar encima de la base activa sin autorizacion. La verificacion de esta entrega restauro las 43 tablas y comparo todo su contenido; ver `evidencias/restauracion.json`.
