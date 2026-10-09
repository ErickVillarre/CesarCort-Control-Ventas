# Estado del despliegue

Fecha: 2026-10-09. Objetivo: frontend CesarControl en Vercel; API y MySQL externos pendientes.

- Rama de trabajo y destino remoto: apf2-vercel. Base: 47cf8e1a58846da3e40cc58deede458b6bc48165.
- Repositorio: https://github.com/ErickVillarre/CesarCort-Control-Ventas
- Rama local y remota publicada: [apf2-vercel](https://github.com/ErickVillarre/CesarCort-Control-Ventas/tree/apf2-vercel), con seguimiento origin/apf2-vercel.
- Commit de aplicacion publicado y desplegado: [30b8c596ee0461ee8f742076c8ae63173e24ad37](https://github.com/ErickVillarre/CesarCort-Control-Ventas/commit/30b8c596ee0461ee8f742076c8ae63173e24ad37). Esta actualizacion posterior solo registra documentacion y evidencias.
- Produccion: https://cesarcontrol-apf2.vercel.app
- Preview verificada: https://cesarcontrol-apf2-fzoiozw3f-notrabajamos.vercel.app (requiere acceso autorizado a Vercel).
- Despliegue final de produccion: https://cesarcontrol-apf2-oe83xfw7j-notrabajamos.vercel.app
- Proyecto Vercel: cesarcontrol-apf2; equipo notrabajamos.
- URL publica de API: no proporcionada. MySQL externo: no configurado.
- Estado global: PARCIAL. Frontend publicado y verificado; login publico deshabilitado hasta conectar API y MySQL externos.

## Alcance

El documento solicitado limita el commit a preparacion de despliegue. Las correcciones funcionales APF2 anteriores se conservan en el directorio local y en ../respaldos/20261009-100538-antes-despliegue; no se incluyen automaticamente en este commit. En Login, Axios y Clientes solo se preparan fragmentos relacionados con despliegue. Las evidencias locales APF2 anteriores corresponden al arbol local completo, no a esta revision limitada. El despliegue se ejecuto desde una copia exacta del commit generada con git archive, sin incorporar cambios funcionales locales. Los archivos de entorno y credenciales se excluyeron de la subida.

No se modifica main ni crm-erp-permisos-modulos. No se usan resets, limpieza destructiva, push forzado ni migrate:fresh. La base local ventas-melamina y APP_KEY existente se conservan.

## Auditoria y comandos ejecutados

git status --short, branch --show-current, branch -a, log --oneline -5 y remote -v: rama apf2-vercel, remoto correcto, cambios previos respaldados.

PHP 8.2.12; Composer 2.10.3; Node 22.15.0; npm 10.9.2; Git 2.49.0.windows.1; Vercel CLI 63.1.0.

composer install completo sin actualizaciones; uso cache por restriccion inicial de red. Laravel 12.56.0; route:list correcto, 63 rutas API; migrate:status confirma 17 migraciones aplicadas. /api/health local HTTP 200 con database=connected. No se ejecutaron migraciones ni seeders durante esta preparacion.

npm ci: 241 paquetes instalados. npm run lint: exit 0. DEPLOYMENT_MODE=frontend-only npm run build: exit 0. Build parcial revisado en Chrome; evidencia en evidencias/despliegue/preview-local.json. El build normal rechaza API vacia o local; el modo parcial elimina la API local del bundle.

Vercel autenticado en la cuenta seleccionada, equipo notrabajamos. La credencial anterior de GitHub fue rechazada; se renovo por navegador. git push -u origin apf2-vercel completo correctamente. El acceso manual lo realizo el usuario, sin compartir tokens.

Vercel configurado con Root Directory frontend, Vite, Node 22.x, npm ci, npm run build y salida dist. DEPLOYMENT_MODE=frontend-only configurado para Preview y Production; VITE_API_URL no configurada.

La primera llamada vercel deploy --yes, sin --prod, fue asignada automaticamente a produccion por Vercel al tratarse del primer despliegue del proyecto. Se documento el comportamiento y se creo una preview explicita con --target preview. Despues de verificar esa preview en Chrome, se ejecuto vercel deploy --prod --yes. No se presenta el primer despliegue automatico como una preview.

La proteccion de preview se conserva. La CLI autenticada genero el acceso de automatizacion; Chrome uso una cookie temporal limitada al host exacto. El archivo temporal fue eliminado y no se guardaron secretos en las evidencias.

Instalacion, lint y build de la copia exacta 30b8c59: aprobados. Build remoto: aprobado. Preview y produccion se comprobaron en Chrome: /, /login y /ventas responden 200; CSS/JS cargan; sin imagenes rotas, errores de consola ni solicitudes API. Las rutas comprueban retorno SPA; no prueban ventas o autenticacion funcionales.

Archivos del despliegue: frontend/vercel.json, frontend/vite.config.js, frontend/src/config/deployment.js, fragmentos de Login/Clientes/Axios, exclusiones Git/Vercel/Docker, backend/Dockerfile, backend/.env.production.example, README y docs/APF2. Esta entrega conserva separados los cambios funcionales previos.

## Pendientes

API Laravel, MySQL externo, Docker build (Docker no instalado), CORS/login/ventas/stock/credito publicos siguen pendientes. Los ajustes de backend son de empaquetado y plantillas; no se cambia logica de negocio. La base local ventas-melamina permanece local y respaldada.

El build Vercel reporto 19 vulnerabilidades de dependencias (1 baja, 2 moderadas, 16 altas). Las auditorias npm posteriores en la copia local no reprodujeron el resultado; no se declaran resueltas ni se cambiaron dependencias. Ver pruebas-despliegue.md y la evidencia de auditoria.

Comandos para repetir publicacion y rollback: [despliegue-vercel.md](despliegue-vercel.md). Resultados detallados: [pruebas-despliegue.md](pruebas-despliegue.md).
