# Estado del entorno APF2

Fecha: 2026-10-09, America/Lima. Estado: listo localmente con observaciones de publicacion.

## Proyecto y auditoria

Ruta: C:/Users/Bvill/Music/HOLA}/CesarCort-APF2. Worktree vinculado a CesarCort-feature-backend. Rama: apf2-vercel. Commit base: 47cf8e1a58846da3e40cc58deede458b6bc48165. Sin commit nuevo, push, merge ni PR; main no fue modificada ni creada.

La instalacion anterior estaba en feature/backend, commit 67ccc12, con 5 archivos rastreados modificados y 10 nuevos. Se respaldo cada cambio y los .env existentes en ../respaldos/20261009-030813-instalacion-previa. No se copiaron node_modules/vendor al respaldo de cambios: son regenerables desde lockfiles. Los datos de negocio se respaldaron por SQL. El arbol anterior se conserva.

Se ejecutaron git status --short, git branch --show-current y git log --oneline -5 antes de editar. git fetch origin crm-erp-permisos-modulos descargo 47cf8e1. git worktree add -b apf2-vercel ../CesarCort-APF2 FETCH_HEAD creo la copia separada.

| Herramienta | Version / ubicacion |
| --- | --- |
| Windows | 10.0.26200; PowerShell 5.1.26100.9444 |
| PHP | 8.2.12, C:/xampp/php/php.exe |
| Composer | 2.10.3, ../.local-tools/composer.ps1 |
| Laravel | 12.56.0 |
| Node / npm | 22.15.0 / 10.9.2 |
| Git | 2.49.0.windows.1 |
| MariaDB XAMPP | 10.4.32 |
| Vercel CLI | 63.1.0, consultada con npx |
| Docker | No disponible |

Estructura: backend/app, backend/routes, backend/database, backend/tests; frontend/src y frontend/src/api/axios.js; scripts; docs/APF2. Entrada React: frontend/src/main.jsx y App.jsx. La navegacion de modulos existente usa estado de Dashboard, no un router de paginas. Landing ausente.

## Instalacion y base

composer install completo: 119 paquetes fijados por lockfile. npm ci completo: 241 paquetes. Sin instalaciones globales. PHP/Composer/MySQL preexistian fuera de PATH. Composer check-platform-reqs paso. El primer npm ci no tenia todos los paquetes en cache; el reintento autorizado con red paso. La compilacion inicial encontro spawn EPERM del sandbox; la ejecucion autorizada paso.

Se reutilizo .env local del proyecto anterior sin mostrar secretos; APP_KEY existente conservada. storage:link paso. storage y bootstrap/cache escribibles: comprobado por ejecucion de Laravel/Composer y pruebas. Configuracion frontend local: http://127.0.0.1:8000/api.

MySQL iniciado desde XAMPP. Base elegida explicitamente por el usuario: ventas-melamina. Respaldo previo: ../respaldos/ventas-melamina-20261009-030938.sql, SHA256 6b0e3fa5ae077ffa25766e17d266828c90d129de50d19b07ac6bc07cee2777d4. Restauracion: apf2_restore_20261009_081448; 43 tablas iguales al original. No se borro la base de verificacion.

Se ejecuto migrate:status, migrate y db:seed. La migracion APF2 conserva valores historicos, amplia tipos de pago y agrega registro de procedencia demo. No se uso migrate:fresh. DatabaseSeeder: Apf2AccessSeeder, Apf2CatalogSeeder, Apf2SalesSeeder. Datos agregados: 4 usuarios, 24 productos, 12 clientes, 24 lineas credito/cuenta, 30 ventas historicas y ventas sinteticas de verificacion. Los registros anteriores de clientes/productos/ventas/detalles/creditos/movimientos/usuarios/empleados se compararon contra el respaldo; permanecen iguales. Los permisos compartidos de admin/gerente/vendedor se ajustaron segun APF2 y la politica es reversible.

## Servicios y limites

API http://127.0.0.1:8000/api/health; frontend http://127.0.0.1:5173. Procesos independientes, registros apf2-*.log ignorados. El wrapper PowerShell npm consumia flags; el lanzador usa directamente el CLI de Vite y strictPort. No se inicio landing de otra rama.

Comandos aprobados: composer install, composer check-platform-reqs, artisan migrate:status/migrate/db:seed/storage:link/test, npm ci/lint/build, restauracion SQL, pruebas HTTP/Chrome, git diff --check. Salidas de pruebas en evidencias/.

Pendiente: Docker build, proveedor API/MySQL, variables y dominios reales, autenticacion Vercel, despliegue autorizado y verificacion remota. No se ejecuto key:generate por existir clave valida. No se ejecuto login ni despliegue Vercel. Browserslist advierte datos antiguos; lint/build pasan. No se hizo una auditoria exhaustiva de dependencias ni pruebas completas de todos los modulos operativos historicos.

## Archivos cambiados

Inventario completo al generar este informe (?? indica archivos/carpetas nuevos):

~~~text
M README.md
 M backend/app/Http/Controllers/AuthController.php
 M backend/app/Http/Controllers/CajaController.php
 M backend/app/Http/Controllers/EmpleadoController.php
 M backend/app/Http/Controllers/RoleController.php
 M backend/app/Http/Controllers/VentaController.php
 M backend/app/Models/User.php
 M backend/bootstrap/app.php
 M backend/config/cors.php
 M backend/database/seeders/DatabaseSeeder.php
 M backend/database/seeders/RolesAndDemoUsersSeeder.php
 M backend/routes/api.php
 M frontend/src/AdminAccess.jsx
 M frontend/src/App.jsx
 M frontend/src/ChangePassword.jsx
 M frontend/src/Clientes.jsx
 M frontend/src/Creditos.jsx
 M frontend/src/Dashboard.jsx
 M frontend/src/Login.jsx
 M frontend/src/Productos.jsx
 M frontend/src/Ventas.jsx
 M frontend/src/api/axios.js
 M frontend/vite.config.js
?? backend/.dockerignore
?? backend/.env.production.example
?? backend/Dockerfile
?? backend/app/Http/Middleware/EnsureActiveAccess.php
?? backend/app/Services/
?? backend/config/apf2.php
?? backend/database/migrations/2026_10_09_000001_prepare_apf2_schema.php
?? backend/database/seeders/Apf2AccessSeeder.php
?? backend/database/seeders/Apf2CatalogSeeder.php
?? backend/database/seeders/Apf2SalesSeeder.php
?? backend/tests/Feature/Apf2Test.php
?? docs/
?? frontend/vercel.json
?? scripts/
~~~

Ver tambien evidencias/git-final.txt para el estado final y diff --stat. Secretos, credenciales, dumps, dependencias y logs siguen fuera de Git.
