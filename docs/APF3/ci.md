# Integración continua

Al abrir APF3 no había un workflow versionado en `.github/workflows/` en el commit `67ccc12`. En la rama APF3 se publicó `.github/workflows/apf3-ci.yml` con jobs para CRM, landing y backend; la ejecución remota del commit `3874929` terminó correctamente. Tras retirar la landing para la exposición, el workflow conserva CRM y backend. El corte de diseño añadió un tercer job para construir la imagen Docker de producción desde `backend/Dockerfile`, sin secretos ni conexión a MySQL. Los comandos actuales son:

| Componente | Instalación | Validación |
| --- | --- | --- |
| CRM React/Vite | `npm ci` en `frontend` | `npm run lint` y `npm run build` |
| API Laravel | `composer install --no-interaction --prefer-dist` en `backend` | `php artisan test` |
| Imagen PHP de producción | Checkout del repositorio | `docker build --file backend/Dockerfile --tag cesarcontrol-apf3:ci backend` |

`backend/phpunit.xml` configura `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` y entorno `testing`, por lo que la suite no debe tocar MySQL local ni una base remota. El runner necesita PHP 8.2 o superior con extensiones requeridas por Composer, y Node compatible con Vite 8. La versión exacta que se use debe constar en el workflow.

El workflow preparado usa Node 22 y PHP 8.3 (`mbstring`, `dom`, `fileinfo`, `pdo_sqlite`, `sqlite3`, `pdo_mysql`, `gd`), instala con lockfiles, genera una `APP_KEY` efímera en el runner y ejecuta `php artisan test`. Se activa en push y pull request dirigidos a `apf3-integrador-ii`, con permiso GitHub `contents: read`.

## Puertas de calidad

1. Instalar dependencias con los lockfiles existentes.
2. Ejecutar lint y build del CRM y pruebas Laravel en base aislada.
3. Dejar visibles las salidas y el código de salida de todos los pasos; una prueba fallida no se marca como aprobada ni se omite.
4. Ejecutar en push y pull request de la rama APF3; no hacer merge automático.
5. Construir la imagen PHP en un job independiente para detectar errores del Dockerfile antes de enlazar un proveedor.
6. Registrar URL de ejecución, SHA del commit probado, fecha y resultado en este documento o [pruebas.md](pruebas.md).

| Ejecución | Commit | Resultado frontend | Resultado landing | Resultado backend | Observación |
| --- | --- | --- | --- | --- | --- |
| Validación local, 6/11/2026 | Backend `17bfdd2`, interfaces `4457ff5` | `npm run lint` y `npm run build`: exit 0 | `npm run build`: exit 0 | `php artisan test`: 6 pruebas, 61 aserciones, exit 0 | `npm audit` de ambos y `composer audit --locked`: 0 avisos; `composer validate` válido. |
| [GitHub Actions 42218037081](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218037081), 6/11/2026 | `dfb7b15` | Aprobado | Aprobado | Falló: 1 prueba del seeder, 5 aprobadas | El `.env.example` aportaba `APF3_DEMO_SEED=false` en el entorno del runner; la prueba no aislaba `$_SERVER`. |
| [GitHub Actions 42218612774](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42218612774), 6/11/2026 | `3874929` | Aprobado | Aprobado | Aprobado | Se aisló y restauró la variable del proceso, `$_ENV` y `$_SERVER` en la prueba. Los tres jobs terminaron con `success`. |
| [GitHub Actions 42223705891](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42223705891), 6/11/2026 | `85f4f99` | Aprobado | Ya retirada | Aprobado | Tras quitar landing y conectar la exposición a copia MySQL, ambos jobs vigentes terminaron con `success`. |

La CI valida código en un runner. No valida una venta desde una red externa ni demuestra que el despliegue remoto corresponda al commit hasta comprobar también el proveedor y la URL pública.

La columna landing de las ejecuciones anteriores refleja la versión histórica previa a su retirada y se conserva como evidencia. El run `85f4f99` tuvo dos trabajos. Desde el cambio de diseño y preparación de despliegue, el workflow ejecuta `frontend`, `backend` y `backend-image`; el resultado del commit final debe verificarse directamente en GitHub Actions antes de exponer.
