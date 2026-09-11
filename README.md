# CesarControl CRM/ERP y Landing

Sistema para ventas de melamina, clientes, inventario, caja, creditos,
mantenimiento, recursos humanos, marketing, reportes y landing comercial.

## Rama para usar

```bash
git clone -b feature/backend https://github.com/ErickVillarre/CesarCort-Control-Ventas.git
cd CesarCort-Control-Ventas
```

## Estructura

- `backend`: API Laravel con Sanctum.
- `frontend`: CRM/ERP en React + Vite.
- `landing-page`: landing comercial en React + Vite.

## Instalacion local

Backend:

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

CRM/ERP:

```bash
cd frontend
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

Landing:

```bash
cd landing-page
npm install
copy .env.example .env
npm run dev -- --host 127.0.0.1 --port 5174
```

## Rutas locales

- Landing: `http://127.0.0.1:5174`
- Login CRM/ERP: `http://127.0.0.1:5173/login`
- API Laravel: `http://127.0.0.1:8000/api`

## Credenciales de desarrollo

Estas credenciales son para ambiente local/demo.

### Acceso desde landing

| Pantalla | Usuario | Contrasena | Resultado |
| --- | --- | --- | --- |
| Modal del landing | `admin` | `1234` | Redirige al login real del CRM/ERP |

Variables relacionadas en `backend/.env`:

```env
LANDING_DEMO_USER=admin
LANDING_DEMO_PASSWORD=1234
LANDING_CRM_LOGIN_URL=http://127.0.0.1:5173/login
LANDING_URL=http://127.0.0.1:5174
```

### Login CRM/ERP

| Rol | Usuario | Contrasena | Cambio obligatorio |
| --- | --- | --- | --- |
| Admin principal | `admin@gmail.com` | `123` | No |
| Gerente | `gerencia@cesarcontrol.local` | `Temporal123!` | Si |
| Vendedor | `ventas@cesarcontrol.local` | `Temporal123!` | Si |
| Caja | `caja@cesarcontrol.local` | `Temporal123!` | Si |
| Mantenimiento | `mantenimiento@cesarcontrol.local` | `Temporal123!` | Si |
| Recursos humanos | `rrhh@cesarcontrol.local` | `Temporal123!` | Si |
| Marketing | `marketing@cesarcontrol.local` | `Temporal123!` | Si |
| Cliente portal | `cliente@cesarcontrol.local` | `Temporal123!` | Si |

El valor `Temporal123!` viene de `DEMO_USER_PASSWORD`. Puedes cambiarlo en
`backend/.env` antes de ejecutar seeders.

### Usuarios extra del seeder local

El seeder `DevelopmentDemoDataSeeder` solo corre en `APP_ENV=local` y crea
usuarios adicionales:

| Usuario | Rol | Contrasena |
| --- | --- | --- |
| `empleado7@cesarcontrol.local` | Caja | `Temporal123!` |
| `empleado8@cesarcontrol.local` | Mantenimiento | `Temporal123!` |
| `empleado9@cesarcontrol.local` | Recursos humanos | `Temporal123!` |
| `empleado10@cesarcontrol.local` | Marketing | `Temporal123!` |
| `empleado11@cesarcontrol.local` | Gerente | `Temporal123!` |
| `empleado12@cesarcontrol.local` | Vendedor | `Temporal123!` |

Todos los usuarios temporales piden cambiar contrasena despues del primer login.

## Seeders utiles

Roles, permisos y usuarios principales:

```bash
cd backend
php artisan db:seed --class=RolesAndDemoUsersSeeder
```

Datos amplios para probar el sistema local:

```bash
cd backend
php artisan db:seed --class=DevelopmentDemoDataSeeder
```

## Verificacion

Backend:

```bash
cd backend
php artisan test
```

CRM/ERP:

```bash
cd frontend
npm run build
```

Landing:

```bash
cd landing-page
npm run build
```

## Notas

- No publiques `backend/.env` con datos reales.
- El landing no inicia sesion dentro del CRM; solo valida el acceso demo y
  redirige a `http://127.0.0.1:5173/login`.
- El login real del CRM/ERP mantiene roles, permisos y cambio obligatorio de
  contrasena cuando corresponde.
