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

El bundle contiene una cadena interna de Axios http://localhost como respaldo de entorno de su libreria; no se usa como URL de API. La inspeccion de red confirma cero solicitudes API en modo parcial. No quedan rutas de imagenes que apunten al backend local.

## Verificacion remota pendiente

Registrar las URLs reales y resultados de Preview y Production despues del despliegue. La prueba local del directorio de trabajo incluye cambios APF2 previos; verificar tambien la copia exacta del commit de despliegue antes de publicar.

Login publico, redireccion por rol, dashboard, productos, clientes, ventas, stock, creditos, respuestas API y CORS remoto: pendientes hasta disponer de API y MySQL externos. No marcar como aprobados por haber pasado anteriormente en localhost. La aplicacion usa vistas por estado; las pruebas de /login y /ventas verifican retorno SPA, no React Router autenticado.

Las evidencias historicas en matriz-pruebas.md corresponden a APF2 local completo. Salidas actuales: evidencias/frontend-lint.txt, evidencias/frontend-build.txt y evidencias/despliegue/preview-local.json.
