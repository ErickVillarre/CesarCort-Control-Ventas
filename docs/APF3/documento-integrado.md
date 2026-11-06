# APF3 — Documento técnico integrado para el informe académico

**Curso Integrador II · CesarControl CRM/ERP · 6 de noviembre de 2026 (America/Lima).** Este texto reúne el estado comprobado y las condiciones de cierre del tercer avance. El informe académico que se redacte a partir de él debe citar las evidencias en el cuerpo, sin anexos, y actualizar los resultados pendientes antes de su entrega.

**Cambio posterior para la exposición:** la landing independiente se retiró por solicitud del propietario. La interfaz vigente es solo el CRM; los pasajes sobre landing a continuación describen el corte histórico APF3, conservado como evidencia y no como componente activo.

## 1. Síntesis del incremento

El proyecto parte de `D:\ventas-melamina`, commit `67ccc12` de `feature/backend`, y se trabaja en la rama `apf3-integrador-ii`. El corte inicial conservaba `frontend` React/Vite, `landing-page` React/Vite y `backend` Laravel 12 con Sanctum; la landing fue retirada después por petición del propietario. La captura previa `captura-principal.png` y los logos locales fueron inspeccionados; ambos logos ya estaban versionados en el commit de partida y el logo del CRM permanece. Se preservó la carpeta existente y el único archivo inicial sin seguimiento fue la captura de login.

El APF2 informó un frontend Vercel accesible y una API y base MySQL todavía locales. El `HEAD` de la URL histórica respondió `HTTP 200` el 6/11/2026; eso no demuestra funcionamiento del CRM remoto. El código inicial de APF3 todavía tenía URL API de `127.0.0.1` y referencias de backend incompletas: el revert `ddd87c1` eliminó controladores, middlewares y modelos presentes en `47cf8e1`, mientras el commit inicial APF3 mantenía referencias a ellos. Por ello, los resultados de pruebas APF2 deben reproducirse con el checkout nuevo. La matriz de contraste está en [observaciones-apf2.md](observaciones-apf2.md).

## 2. Solicitudes y control de acceso

El incremento APF3 incorpora un flujo para solicitudes de servicio, información y acceso, con trazabilidad de solicitante, prioridad, estado, responsable, decisión y resolución. La autorización se evalúa en el servidor: un usuario sin sesión recibe `401`; uno sin permiso recibe `403`; la creación y aprobación de una solicitud de acceso no cambia el rol automáticamente. El contrato está en [solicitudes.md](solicitudes.md). **Prueba local:** suite Laravel 6/6 con 61 aserciones y smoke HTTP sobre SQLite separada: vendedor recibió `403` al decidir, gerente aprobó y los permisos del vendedor permanecieron iguales.

## 3. Calidad, seguridad y accesibilidad

La rama APF3 ejecutó CI con `npm ci`, lint/build del CRM y pruebas Laravel con SQLite en memoria. Después de retirar la landing, se añadió un job que construye la imagen Docker PHP; [GitHub Actions para `14f5a70`](https://github.com/ErickVillarre/CesarCort-Control-Ventas/actions/runs/42232344846) aprobó frontend, backend e imagen. El primer run falló por la prueba del seeder y un run posterior detectó que faltaba `libsqlite3-dev` en Docker; ambos fallos y sus correcciones se registran en [ci.md](ci.md). Los secretos se quedan fuera de Git; la configuración remota requiere HTTPS, `APP_DEBUG=false`, CORS limitado y respaldo de la base objetivo. Los criterios de roles, protección de datos y controles se documentan en [seguridad-accesibilidad.md](seguridad-accesibilidad.md).

La interfaz ofrece modos normal, protanopia, deuteranopia, tritanopia, acromatopsia y alto contraste en landing, login y CRM. La selección persiste, los estados conservan texto/símbolos, hay foco visible y la guía de voz es voluntaria en español y detenible. Chrome 156 headless verificó los seis cambios de paleta y una solicitud real desde CRM con deuteranopia y voz activadas. El spy registró acciones y confirmación sin leer contraseña; no prueba audio audible. La revisión integral WCAG, toque físico y navegadores/dispositivos reales permanecen abiertos.

## 4. Datos y continuidad

El seeder heredado generaba hasta 64 productos, 41 clientes y 250 ventas; este volumen y formato no corresponde a la demostración reducida solicitada. El APF3 implementó un seeder optativo e idempotente para una base aislada: 12 productos, 5 clientes nombrados, 4 ventas y 1 crédito, con precios en soles, stock y saldos conciliados. No se eliminaron datos existentes. El primer corte respaldó y restauró la SQLite local. Después de iniciar XAMPP MySQL, se exportó y restauró una copia aislada de la base original `ventas-melamina`; sus 43 tablas y conteos se conservaron. El primer montaje usó `cesarcontrol_apf3_expo` con datos sintéticos y 0/98 errores 5xx en el smoke. Por petición posterior, el CRM se conectó a `cesarcontrol_datos_expo`, copia migrada de la base original: 82 productos, 43 clientes y 250 ventas visibles, con login de tres roles y dashboard comprobados. Véanse [despliegue-reversion.md](despliegue-reversion.md) y [pruebas.md](pruebas.md).

## 5. Cambios, Sprint 6 y capacidad

Los cambios APF3 están clasificados por motivo, impacto, commit, revisión, aprobación, despliegue y reversión en [control-cambios.md](control-cambios.md). El [Sprint 6](sprint-6.md) prioriza solicitudes, accesibilidad, datos demo, CI, medición y despliegue integral sin reabrir la construcción de módulos ya presentes. No hay acta verificable de Sprint 5, Review ni retrospectiva de Sprint 6; los participantes y acuerdos se completarán cuando esas reuniones ocurran.

Las metas heredadas de APF2 para carga, p95, errores y disponibilidad no son mediciones. [capacidad.md](capacidad.md) registra una línea base **local** con Chrome 156 headless y tres navegaciones en cada uno de cuatro perfiles, más 20 lecturas por ruta API representativa. El p95 SQLite fue 262–306 ms; en la repetición local MySQL, 181.8–243.7 ms. Cada smoke contabilizó 98 respuestas y 0 errores 5xx. Estas muestras no sustituyen la evaluación de URL pública, carga concurrente, hosting real ni red móvil externa.

## 6. Despliegue y resultado de aceptación

El criterio de versión final exige URL HTTPS pública del frontend y de la API, base MySQL remota conectada, login y permisos para admin/gerente/vendedor, consulta de datos, venta de demostración con persistencia y stock consistente, y pruebas de accesibilidad. Requiere también CI del mismo commit y pruebas desde una red ajena a la computadora. El procedimiento, variables sin valores secretos, respaldo previo y reversión están en [despliegue-reversion.md](despliegue-reversion.md). La tabla de ejecución real está en [pruebas.md](pruebas.md). La rama GitHub y la imagen Docker construida en CI ya están publicadas; faltan proveedor remoto conectado, respaldo restaurable de la **base remota objetivo** y almacenamiento persistente para adjuntos. El contenedor aún no se ejecutó con MySQL remoto.

**Estado de este corte:** funciones principales y pruebas locales APF3 ejecutadas; rama publicada y CI remota aprobada. El despliegue integral y las pruebas de dispositivos/red externa siguen pendientes. La versión final del informe debe tomar de `handoff.md` los commits, URL y resultados comprobados y mantener los bloqueos que no se resuelvan.

## 7. Ajuste visual y material para la exposición

Tras la revisión del propietario, el CRM sustituyó la paleta verde por una identidad sobria de grafito, crema y madera cobriza. Se reorganizó el login, se armonizaron navegación, tarjetas, tablas, acciones y gráficos con tokens semánticos para los seis modos de visión, y se conservó el logo original. El favicon usa el mismo sistema cromático. Lint y build del CRM aprobaron. Las capturas actuales de [login de escritorio](evidencias/redesign-login-desktop.png), [login móvil emulado](evidencias/redesign-login-mobile.png), [panel de escritorio](evidencias/redesign-dashboard-desktop.png) y [panel móvil emulado](evidencias/redesign-dashboard-mobile.png) se tomaron con datos sintéticos, no con registros del propietario.

Se calcularon 15 pares semánticos por modo mediante luminancia relativa sRGB. Los mínimos fueron normal 5.85:1, protanopia 5.92:1, deuteranopia 5.56:1, tritanopia 5.78:1, acromatopsia 7.62:1 y alto contraste 8.01:1; todos superaron 4.5:1 en **esos pares**. [Método y valores](evidencias/redisenio-contraste.json). Esto no acredita todas las interacciones ni conformidad WCAG integral. Se preparó la [presentación APF3](Presentacion-APF3-CesarControl.pptx) y el [guion](guion-presentacion.md) con la distinción entre sistema local funcional y despliegue remoto pendiente.
