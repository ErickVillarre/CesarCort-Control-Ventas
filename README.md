# CesarControl CRM/ERP

Sistema de gestion empresarial para ventas de melamina, inventario, clientes, caja, creditos, mantenimiento, recursos humanos, marketing y reportes.

## Requisitos

- PHP 8.2 o superior.
- Composer.
- MySQL 8.
- Node.js y npm.

## Estructura

- `backend`: API Laravel con Sanctum.
- `frontend`: React, Vite y Tailwind.
- `backend/database/migrations`: estructura de base de datos.
- `backend/database/seeders`: seeders voluntarios para roles y datos locales.

## Variables

Configura `backend/.env` desde `backend/.env.example` y completa los datos de MySQL en tu entorno local. No publiques `.env`.

Para el frontend puedes crear `frontend/.env` desde `frontend/.env.example`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
VITE_YAPE_QR_URL=
```

`VITE_YAPE_QR_URL` debe apuntar a un QR configurado por la empresa. Si no se configura, la pantalla de ventas lo muestra como pendiente.

## Instalacion

Backend:

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate
```

Frontend:

```bash
cd frontend
npm install
npm run dev
```

API local: `http://127.0.0.1:8000/api`

Frontend local: la URL que muestre Vite, normalmente `http://127.0.0.1:5173`.

## Usuarios y accesos

El sistema usa roles y permisos por modulo y por accion. El administrador principal debe gestionar usuarios desde el modulo de empleados y accesos.

Seeder de roles y permisos:

```bash
cd backend
php artisan db:seed --class=RolesAndDemoUsersSeeder
```

Seeder de datos abundantes para desarrollo local:

```bash
cd backend
php artisan db:seed --class=DevelopmentDemoDataSeeder
```

Este seeder solo se ejecuta en entorno local. No elimina informacion existente y evita duplicar registros base cuando hay codigos o claves configurables.

## Modulos

- Ventas: punto de venta, preventas, comprobantes internos y envio a caja.
- Caja: apertura, cobro, validacion, caja chica y cierre.
- Clientes y CRM: ficha, busqueda, seguimiento y estado activo/inactivo.
- Inventario: productos, stock y avisos de faltantes.
- Creditos y dinero a cuenta: saldos, movimientos y restricciones por rol.
- Mantenimiento: maquinas, fallas, mantenimientos, repuestos, pedidos y cortes de energia.
- Recursos Humanos: empleados, asistencia y movimientos laborales.
- Marketing: tendencias desde ventas, redes configurables y calendario.
- Reportes: centro de reportes con filtros y exportacion CSV.

## Pruebas

Backend:

```bash
cd backend
php artisan test
```

Frontend:

```bash
cd frontend
npm run lint
npm run build
```

## Preparacion para GitHub

El proyecto ignora archivos de entorno, dependencias, logs, sesiones, dumps SQL, archivos privados y subidas sensibles. Antes de publicar, revisa que no existan credenciales ni datos reales en archivos versionados.
