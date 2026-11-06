# CesarControl CRM/ERP — CESCORT

Sistema de ventas de melamina con API Laravel/Sanctum y CRM React/Vite. La landing independiente se retiró del proyecto para la exposición del 6 de noviembre de 2026; la entrada del sistema es el login del CRM. El avance APF3 se desarrolla en la rama `apf3-integrador-ii`. La carpeta local fue la fuente de trabajo; véase [docs/APF3/handoff.md](docs/APF3/handoff.md) para el estado comprobado y los pendientes.

## Estructura

- `backend/`: API, migraciones, permisos y pruebas.
- `frontend/`: CRM/ERP autenticado.
- `docs/APF3/`: contraste APF2, Sprint 6, seguridad, pruebas, capacidad, despliegue y control de cambios.

Para la sustentación: [presentación APF3](docs/APF3/Presentacion-APF3-CesarControl.pptx), [guion](docs/APF3/guion-presentacion.md), [documento integrado](docs/APF3/documento-integrado.md) y [resumen factual para el informe](docs/APF3/resumen-para-informe.md). El diseño vigente del CRM usa grafito, crema y madera cobriza.

El logo original `frontend/public/logo.png` se conserva. La copia que pertenecía a la landing retirada permanece recuperable en el historial Git. `captura-principal.png` documenta el estado visual local anterior al APF3; no demuestra el funcionamiento actual.

## Exposición en esta PC

Haz doble clic en `iniciar-exposicion.cmd`. El iniciador comprueba MySQL de XAMPP, reinicia la API Laravel en `127.0.0.1:8000` con `cesarcontrol_datos_expo` (una copia migrada de tu base `ventas-melamina`), deja el CRM en `127.0.0.1:5173` y abre el login en Chrome incógnito. La base original no se modifica al arrancar. Si falta una dependencia, el iniciador muestra el error y deja registros en `%LOCALAPPDATA%\CesarControl\exposicion\logs`.

La URL de exposición es `http://127.0.0.1:5173/login`. Es local a esta PC; no es el despliegue público HTTPS. La copia contiene una foto de los registros de la base original al momento del respaldo; los cambios posteriores no se sincronizan automáticamente y las ventas nuevas del CRM se guardan en la copia. Se añadieron solo tres usuarios sintéticos para acceder: `admin.demo@cesarcontrol.test`, `gerencia.demo@cesarcontrol.test` y `ventas.demo@cesarcontrol.test`; la contraseña de exposición se entrega fuera de Git.

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

Configura `VITE_API_URL` según el entorno. Un build de producción requiere la URL **HTTPS real** de la API; la interfaz muestra un error de configuración si falta. Los datos y operaciones del CRM pasan por autenticación y permisos del backend.

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

La CI de `.github/workflows/apf3-ci.yml` ejecuta estos comandos en la rama APF3 con SQLite en memoria para las pruebas Laravel. El empaquetado Docker de `backend/` y las rutas directas del CRM en Vercel están preparados; el procedimiento y las verificaciones remotas exigidas están en [docs/APF3/despliegue-reversion.md](docs/APF3/despliegue-reversion.md).
