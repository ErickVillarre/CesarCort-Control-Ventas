# Estado del despliegue

Fecha: 2026-10-09. Objetivo: frontend CesarControl en Vercel; API y MySQL externos pendientes.

- Rama de trabajo y destino remoto: apf2-vercel. Base: 47cf8e1a58846da3e40cc58deede458b6bc48165.
- Repositorio: https://github.com/ErickVillarre/CesarCort-Control-Ventas
- Rama publicada: https://github.com/ErickVillarre/CesarCort-Control-Ventas/tree/apf2-vercel (confirmar al completar push).
- Commit publicado y URLs preview/produccion: pendientes de registrar despues de ejecutar.
- URL publica de API: no proporcionada. MySQL externo: no configurado.
- Estado global: parcial en preparacion. Un frontend publicado no habilita el sistema completo.

## Alcance

El documento solicitado limita el commit a preparacion de despliegue. Las correcciones funcionales APF2 anteriores se conservan en el directorio local y en ../respaldos/20261009-100538-antes-despliegue; no se incluyen automaticamente en este commit. En Login, Axios y Clientes solo se preparan fragmentos relacionados con despliegue. Las evidencias locales APF2 anteriores corresponden al arbol local completo, no a esta revision limitada. El despliegue se ejecutara desde una copia exacta del commit, sin cambios locales ni .env.

No se modifica main ni crm-erp-permisos-modulos. No se usan resets, limpieza destructiva, push forzado ni migrate:fresh. La base local ventas-melamina y APP_KEY existente se conservan.

## Auditoria y comandos ejecutados

git status --short, branch --show-current, branch -a, log --oneline -5 y remote -v: rama apf2-vercel, remoto correcto, cambios previos respaldados.

PHP 8.2.12; Composer 2.10.3; Node 22.15.0; npm 10.9.2; Git 2.49.0.windows.1; Vercel CLI 63.1.0.

composer install completo sin actualizaciones; uso cache por restriccion inicial de red. Laravel 12.56.0; route:list correcto, 63 rutas API; migrate:status confirma 17 migraciones aplicadas. /api/health local HTTP 200 con database=connected. No se ejecutaron migraciones ni seeders durante esta preparacion.

npm ci: 241 paquetes instalados. npm run lint: exit 0. DEPLOYMENT_MODE=frontend-only npm run build: exit 0. Build parcial revisado en Chrome; evidencia en evidencias/despliegue/preview-local.json. El build normal rechaza API vacia o local; el modo parcial elimina la API local del bundle.

Vercel autenticado en la cuenta seleccionada, equipo notrabajamos. La credencial anterior de GitHub fue rechazada; se renovo por navegador y git push --dry-run confirmo acceso a la rama destino. El acceso manual lo realizo el usuario, sin compartir tokens.

## Pendientes

Registrar commit y URLs reales; verificar preview antes de produccion. API Laravel, MySQL externo, Docker build (Docker no instalado), CORS/login/ventas/stock/credito publicos siguen pendientes. Los ajustes de backend son de empaquetado y plantillas; no se cambia logica de negocio.
