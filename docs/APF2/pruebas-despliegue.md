# Pruebas de despliegue

## Ejecutadas en esta preparacion

| Comprobacion | Resultado real |
| --- | --- |
| npm ci | Aprobado: 241 paquetes, exit 0 |
| npm run lint | Aprobado: exit 0 |
| Build con DEPLOYMENT_MODE=frontend-only | Aprobado: exit 0; aviso parcial esperado |
| Preview local del bundle /, /login, /ventas | Aprobado: pagina y aviso, sin imagenes rotas |
| Login sin API | Deshabilitado de forma explicita; sin solicitudes API |
| Consola, JavaScript y recursos locales | Sin errores; preview-local.json |
| API local /api/health | HTTP 200, database=connected |
| Laravel route:list | Correcto, 63 rutas API |
| migrate:status local | 17 migraciones aplicadas |
| Credenciales en candidatos Git | No se encontraron contrasenas locales ni APP_KEY |
| GitHub dry-run | Acceso correcto tras renovar autenticacion |
| GitHub push apf2-vercel | Commit 30b8c59 publicado; sin push a main/base |
| Copia exacta de 30b8c59 | npm ci, lint y build parcial: exit 0 |
| Build Vercel Preview y Production | READY; npm ci y npm run build completados |
| Preview remota /, /login, /ventas | HTTP 200; aviso visible; login deshabilitado |
| Produccion publica /, /login, /ventas | HTTP 200; aviso visible; login deshabilitado |
| Recursos remotos y consola | CSS/JS correctos; 0 imagenes rotas; 0 errores HTTP/JS/consola |
| API en publicacion parcial | 0 solicitudes API; no se intenta conectar al equipo local |

El bundle contiene una cadena interna de Axios http://localhost como respaldo de entorno de su libreria; no se usa como URL de API. La inspeccion de red confirma cero solicitudes API en modo parcial. No quedan rutas de imagenes que apunten al backend local.

## Verificacion remota

Aplicacion desplegada desde git archive del commit 30b8c596ee0461ee8f742076c8ae63173e24ad37. Preview: https://cesarcontrol-apf2-fzoiozw3f-notrabajamos.vercel.app. Produccion: https://cesarcontrol-apf2.vercel.app. Pruebas Chrome completadas en ambos hosts; JSON y capturas en evidencias/despliegue/preview-remoto.* y produccion-remota.*. La preview se comprobo con cookie temporal de la CLI autenticada, limitada al host y eliminada despues, conservando la proteccion Vercel.

La primera publicacion sin --prod fue asignada automaticamente a produccion por Vercel. Se creo despues la preview explicita (--target preview), se verifico y solo entonces se ejecuto el despliegue final --prod. El comportamiento queda registrado en estado-despliegue.md.

Login publico, redireccion por rol, dashboard, productos, clientes, ventas, stock, creditos, respuestas API y CORS remoto: pendientes hasta disponer de API y MySQL externos. No marcar como aprobados por haber pasado anteriormente en localhost. La aplicacion usa vistas por estado; las pruebas de /login y /ventas verifican retorno SPA, no React Router autenticado.

Las evidencias historicas en matriz-pruebas.md corresponden a APF2 local completo. Salidas actuales: evidencias/frontend-lint.txt, evidencias/frontend-build.txt y evidencias/despliegue/preview-local.json.

## Dependencias

El build remoto reporto 19 vulnerabilidades (1 baja, 2 moderadas y 16 altas). npm audit --omit=dev y npm audit --include=dev, ejecutados despues contra registry.npmjs.org en la copia exacta local, devolvieron exit 0 y cero vulnerabilidades. Evidencia: evidencias/despliegue/auditoria-dependencias.json. La discrepancia queda pendiente; sin un reporte remoto detallado no puede identificarse el alcance de las 19 ni afirmarse que quedaron resueltas. No se actualizaron dependencias ni se uso npm audit fix.
