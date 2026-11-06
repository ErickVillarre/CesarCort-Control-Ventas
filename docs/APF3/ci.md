# Integración continua

Al abrir APF3 no había un workflow versionado en `.github/workflows/` en el commit `67ccc12`. En la rama APF3 se publicó `.github/workflows/apf3-ci.yml` con jobs separados para CRM, landing y backend. La ejecución remota del commit `3874929` terminó correctamente. Los comandos reales declarados por el proyecto son:

| Componente | Instalación | Validación |
| --- | --- | --- |
| CRM React/Vite | `npm ci` en `frontend` | `npm run lint` y `npm run build` |
| Landing React/Vite | `npm ci` en `landing-page` | `npm run build` (no declara script de lint) |
| API Laravel | `composer install --no-interaction --prefer-dist` en `backend` | `php artisan test` |

`backend/phpunit.xml` configura `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` y entorno `testing`, por lo que la suite no debe tocar MySQL local ni una base remota. El runner necesita PHP 8.2 o superior con extensiones requeridas por Composer, y Node compatible con Vite 8. La versión exacta que se use debe constar en el workflow.

El workflow preparado usa Node 22 y PHP 8.3 (`mbstring`, `dom`, `fileinfo`, `pdo_sqlite`, `sqlite3`, `pdo_mysql`, `gd`), instala con lockfiles, genera una `APP_KEY` efímera en el runner y ejecuta `php artisan test`. Se activa en push y pull request dirigidos a `apf3-integrador-ii`, con permiso GitHub `contents: read`.

## Puertas de calidad

1. Instalar dependencias con los lockfiles existentes.
2. Ejecutar lint y build del CRM, build de landing y pruebas Laravel en base aislada.
3. Dejar visibles las salidas y el código de salida de todos los pasos; una prueba fallida no se marca como aprobada ni se omite.
4. Ejecutar en push y pull request de la rama APF3; no hacer merge automático.
5. Registrar URL de ejecución, SHA del commit probado, fecha y resultado en este documento o [pruebas.md](pruebas.md).

| Ejecución | Commit | Resultado frontend | Resultado landing | Resultado backend | Observación |
| --- | --- | --- | --- | --- | --- |
| Validación local, 6/11/2026 | Backend `17bfdd2`, interfaces `4457ff5` | `npm run lint` y `npm run build`: exit 0 | `npm run build`: exit 0 | `php artisan test`: 6 pruebas, 61 aserciones, exit 0 | `npm audit` de ambos y `composer audit --locked`: 0 avisos; `composer validate` válido. |
| [GitHub Actions 42218037081](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218037081), 6/11/2026 | `dfb7b15` | Aprobado | Aprobado | Falló: 1 prueba del seeder, 5 aprobadas | El `.env.example` aportaba `APF3_DEMO_SEED=false` en el entorno del runner; la prueba no aislaba `$_SERVER`. |
| [GitHub Actions 42218612774](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774), 6/11/2026 | `3874929` | Aprobado | Aprobado | Aprobado | Se aisló y restauró la variable del proceso, `$_ENV` y `$_SERVER` en la prueba. Los tres jobs terminaron con `success`. |

La CI valida código en un runner. No valida una venta desde una red externa ni demuestra que el despliegue remoto corresponda al commit hasta comprobar también el proveedor y la URL pública.
