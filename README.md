# CesarControl CRM/ERP — CESCORT

Sistema de ventas de melamina con API Laravel/Sanctum, CRM React/Vite y landing pública React/Vite. El avance APF3 se desarrolla en la rama `apf3-integrador-ii`. La carpeta local fue la fuente de trabajo; véase [docs/APF3/handoff.md](docs/APF3/handoff.md) para el estado comprobado y los pendientes.

## Estructura

- `backend/`: API, migraciones, permisos y pruebas.
- `frontend/`: CRM/ERP autenticado.
- `landing-page/`: página pública.
- `docs/APF3/`: contraste APF2, Sprint 6, seguridad, pruebas, capacidad, despliegue y control de cambios.

El logo original `frontend/public/logo.png` y su copia en `landing-page/assets/logo.png` se conservan en Git. `captura-principal.png` documenta el estado visual local anterior al APF3; no demuestra el funcionamiento actual.

## Preparación local segura

Se requiere PHP 8.2+, Composer, Node.js compatible con Vite 8 y una base de **pruebas separada**. No ejecutes migraciones o seeders sobre la base MySQL existente sin respaldo restaurable.

```powershell
cd backend
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env; php artisan key:generate }
```

Configura en `backend/.env` una base nueva y aislada. Luego ejecuta `php artisan migrate` y `php artisan serve --host=127.0.0.1 --port=8000`. Para los usuarios y datos sintéticos pequeños, define `APF3_DEMO_SEED=true` y una `DEMO_USER_PASSWORD` de al menos 12 caracteres, y ejecuta `php artisan db:seed --class=Apf3DemoSeeder`. El seeder rechaza `APP_ENV` distinto de `local`/`testing` y bases con datos ajenos a APF3. No incluye credenciales de producción ni debe ejecutarse contra datos reales.

```powershell
cd frontend
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm run dev
```

```powershell
cd landing-page
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm run dev
```

Configura `VITE_API_URL` y `VITE_CRM_LOGIN_URL` según el entorno. Un build de producción requiere la URL **HTTPS real** de la API; la interfaz muestra un error de configuración si falta. La landing es pública, mientras que los datos y operaciones del CRM pasan por autenticación y permisos del backend.

## Verificación

```powershell
cd backend
php artisan test
```

```powershell
cd frontend
npm run lint
npm run build
```

```powershell
cd landing-page
npm run build
```

La CI de `.github/workflows/apf3-ci.yml` ejecuta estos comandos en la rama APF3 con SQLite en memoria para las pruebas Laravel. El empaquetado Docker de `backend/` y las rutas directas de ambas aplicaciones Vercel están preparados; el procedimiento y las verificaciones remotas exigidas están en [docs/APF3/despliegue-reversion.md](docs/APF3/despliegue-reversion.md).
