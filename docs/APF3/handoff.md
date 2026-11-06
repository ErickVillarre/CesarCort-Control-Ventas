# Handoff factual APF3 para el informe académico

**Corte:** 6 de noviembre de 2026, America/Lima. El informe académico citará las evidencias dentro del cuerpo y no llevará anexos. Este documento distingue pruebas locales de publicación remota.

## Fuente y rama

- Carpeta inicial: `D:\ventas-melamina`, rama `feature/backend`, commit `67ccc12`; remoto configurado `https://github.com/ErickVillarre/CesarCort-Control-Ventas.git`. No se hizo `pull`, `reset`, `clean` ni cambio que sobrescribiera la carpeta.
- Al inicio, `captura-principal.png` era el único archivo sin seguimiento. Fue inspeccionado (login anterior sin datos sensibles visibles) e incorporado como evidencia histórica. `frontend/public/logo.png` y `landing-page/assets/logo.png` ya estaban versionados y eran el mismo blob de 328922 bytes; se conservaron. Los nuevos favicon SVG son derivados gráficos simples para tamaños pequeños. La licencia/titularidad de la foto y logo debe confirmarla el propietario antes de uso comercial.
- Rama exclusiva de este avance: `apf3-integrador-ii`, creada desde `67ccc12`. Commits locales principales APF3: `17bfdd2` (backend/datos), `4457ff5` (interfaces/accesibilidad) y `f561b58` (documentación/CI). El SHA exacto de cierre se obtiene con `git rev-parse HEAD` en esta rama. No se tocó `main` ni ramas de otros cursos.
- `git push --dry-run` fue rechazado por credenciales GitHub inválidas; el intento posterior de `git push -u origin apf3-integrador-ii` tampoco pudo autenticarse. **La rama todavía no se publicó** y no hay SHA remoto ni ejecución de CI comprobada.

## Implementación y pruebas locales

- API Laravel/Sanctum: restauración compatible de controladores Caja, Marketing y Reportes eliminados por un revert anterior; modelos/middleware de roles, perfil/logout/cambio de contraseña, solicitudes APF3, health check genérico y correcciones de venta/stock/cobro. Una solicitud de acceso aprobada no concede permisos automáticamente.
- CRM y landing React/Vite: diseño melamina en tonos cálidos, vistas responsive, panel de accesibilidad antes/después del login, seis modos de color, guía por voz voluntaria `es-PE`, enlaces/acciones con texto y foco visible. El enlace de la landing va al login real; producción exige `VITE_API_URL` HTTPS. Adjuntos de clientes usan descarga autenticada y disco privado configurable, aún sin almacenamiento remoto persistente.
- `php artisan test`: **6 pruebas, 61 aserciones, 0 fallos**. `composer validate` válido, `composer audit --locked` con **0 avisos**. CRM: lint/build y `npm audit` aprobados, **0 vulnerabilidades**. Landing: build y `npm audit` aprobados, **0 vulnerabilidades**. Los resultados son locales; [pruebas.md](pruebas.md) detalla comandos y alcance.
- API HTTP en SQLite separada: login de admin, gerente y vendedor 200; productos sin token 401; vendedor sin permiso 403; aprobación de acceso por gerente sin variación de permisos; venta pendiente/cobrada stock 10→9, cobro duplicado 409, logout revoca token. 98 respuestas contabilizadas, 0 errores 5xx. [Resultado JSON](evidencias/api-resultados.json).
- Chrome 156 headless: escritorio 1366 px y móvil emulado 390 px, ambos locales. Seis modos cambiaron paleta; alto contraste y tritanopia persistieron al recargar. Con deuteranopia y voz activadas, un vendedor registró una solicitud desde la UI; el spy captó acciones/resultados `es-PE` y ninguna contraseña. La voz audible no se escuchó ni se probó físicamente. [Resultados](evidencias/browser-resultados.json) y [capturas](evidencias/crm-authenticated-desktop.png).

## Respaldo, migraciones y demo

- SQLite local inicial ignorada: respaldo `D:\ventas-melamina-backups\APF3-2026-11-06\database-original.sqlite`, SHA-256 `768e8f1a9cc97dabde75e9c8da914dd5884228ef0bd2df0c05e89bf6b3bab4b8`; copia de restauración con mismo hash y `PRAGMA quick_check=ok`. Tenía 3 migraciones y 0 usuarios.
- `.env` original apuntaba a MySQL; la conexión TCP no respondió. **MySQL no fue respaldado, migrado ni modificado**. El respaldo SQLite no sustituye un respaldo MySQL. Las 21 migraciones del proyecto se ejecutaron solo en una SQLite nueva, en `D:\ventas-melamina-backups\APF3-2026-11-06`.
- Seeder APF3 optativo, solo `local/testing` con `APF3_DEMO_SEED=true` y base separada: 12 productos, 5 clientes nombrados, 4 ventas (3 cobradas, 1 pendiente), 1 crédito; prueba idempotente. Una consulta crea un cliente anónimo auxiliar, de modo que API devolvió 6 clientes. Los tres usuarios de prueba son `admin.demo@cesarcontrol.test`, `gerencia.demo@cesarcontrol.test` y `ventas.demo@cesarcontrol.test`; la contraseña local se entregó por chat y **no se versiona**.

## Capacidad, dispositivos y Sprint 6

- Línea base de carga **local**: 3 navegaciones por CRM/landing y perfil escritorio/móvil emulado; medianas FCP CRM 36/28 ms y landing 80/80 ms. Primera carga CRM escritorio tuvo valor atípico FCP 1764 ms. API local: 20 lecturas/ruta, p95 de 262 a 306 ms y 0/98 respuestas 5xx. Método y muestras completos en [capacidad.md](capacidad.md). Hosting, LCP/TBT y rendimiento remoto sin medir.
- Dispositivos/redes realmente usados: la PC de desarrollo con Chrome 156 headless y emulación de pantalla; servidor y navegador en `127.0.0.1`. **No** hubo prueba física en celular, tableta, laptop independiente, datos móviles ni red externa; tampoco prueba audible de voz.
- Sprint 6: S6-01/02 solicitudes y permisos, S6-03 accesibilidad, S6-04 demo y S6-05 workflow están implementados y verificados localmente. S6-06 despliegue integral está bloqueado; S6-07 documentación/capacidad local está redactada. No constan Review ni retrospectiva con participantes reales: actas y aceptación pendientes. Véase [sprint-6.md](sprint-6.md).

## Publicación y bloqueos concretos

- URL pública APF3: **ninguna**. `https://cesarcontrol-apf2.vercel.app` responde a `HEAD 200`, pero es una URL histórica del APF2, no demuestra API/BD remotas APF3. URLs locales para inspección en esta PC: landing `http://127.0.0.1:5174/`, CRM `http://127.0.0.1:5173/login`, API `http://127.0.0.1:8000/api/health`.
- Hosting APF3: frontend/landing Vercel candidatos, API Laravel preparada con Dockerfile, MySQL remoto **sin proveedor/plan seleccionado**. No se contrataron servicios ni se cargaron secretos. Docker no está instalado localmente, así que su imagen aún no se construyó. Rewrites SPA preparados; ninguna ruta directa remota fue comprobada.
- Para completar: renovar acceso GitHub del titular y publicar **solo** `apf3-integrador-ii`; verificar Actions del SHA; elegir y autorizar un proveedor sin cargos no consentidos; crear MySQL remoto y almacenamiento privado duradero; registrar backup restaurable de la base objetivo, variables HTTPS/CORS/APP_KEY/APP_DEBUG=false, migraciones controladas y health check; probar login, permisos, venta, stock y accesibilidad desde red externa y dispositivos reales. Procedimiento y reversión en [despliegue-reversion.md](despliegue-reversion.md).
