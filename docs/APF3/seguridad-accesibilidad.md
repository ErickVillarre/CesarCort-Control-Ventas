# Seguridad y accesibilidad funcional

**Actualización para exposición:** la landing independiente fue retirada. Las pruebas y auditorías de landing de este documento corresponden al corte histórico APF3; el panel de accesibilidad vigente está en el login y las vistas autenticadas del CRM.

## Seguridad del alcance APF3

| Control | Comprobación requerida | Estado al iniciar |
| --- | --- | --- |
| Autenticación | `401` para rutas CRM sin token; login, perfil y logout reproducibles. | Incompleto en el checkout `67ccc12`; `AuthController` no contenía todos los métodos declarados por `api.php`. |
| Autorización | `403` para un token válido sin permiso; el backend valida cada operación y no depende del menú. | Rutas declaran permisos, pero los aliases middleware faltaban en `bootstrap/app.php` al iniciar. Pendiente de reparación y pruebas. |
| Solicitudes de acceso | Solo un autorizador decide; crear o aprobar solicitud no concede `role_id` ni permisos. | Función APF3 pendiente de prueba. |
| Secretos y datos | No versionar `.env`, respaldos, tokens, datos personales ni dependencias generadas. | `.gitignore` excluye `.env`, `vendor` y `node_modules`; revisar `git diff --cached` antes de cada commit. |
| Producción | HTTPS, `APP_DEBUG=false`, CORS limitado a orígenes reales, secreto `APP_KEY` privado, BD remota con mínimos privilegios. | Pendiente de proveedor y prueba remota. |
| Salud | Endpoint no sensible que confirme disponibilidad, sin revelar contraseñas ni detalles de conexión. | Laravel declara `/up`; falta comprobarlo en el despliegue final. |
| Adjuntos de clientes | Descarga por ruta autenticada con permiso `clientes.ver`; archivos nuevos en disco privado configurable y duradero. | Ruta y configuración APF3 preparadas; sin prueba de persistencia en hosting ni migración de adjuntos heredados. |

La landing comercial es pública; las rutas y datos de CRM/ERP requieren autenticación y permiso. Los formularios públicos deben limitarse a información expresamente pública y validar longitud/contenido en el servidor. No se reutilizarán datos reales en capturas o seeders.

### Auditoría de dependencias al inicio

El 6/11/2026 se ejecutaron `npm audit --json` y `npm audit --omit=dev --json` sobre los lockfiles iniciales. El CRM reportó **16 paquetes** en la auditoría completa (13 altos, 2 moderados, 1 bajo) y **5 altos** al omitir desarrollo; entre los directos productivos figuraban Axios y React Router DOM. La landing reportó **2 altos** en ambas variantes (Vite y `source-map-js`). Se actualizaron dependencias compatibles y se eliminó React Router DOM, que no se usaba. Una nueva ejecución de `npm audit --json` sobre ambos proyectos dio **0 vulnerabilidades**; los lockfiles nuevos fijan Axios 1.21.0 y Vite 8.3.6. El agente frontend informó lint/build aprobados tras el cambio. Son conteos del feed, no número de ataques confirmados ni evidencia de explotación. El APF2 había informado 19 vulnerabilidades en Vercel; esa cifra no coincide con los lockfiles iniciales ni finales de esta rama.

También se ejecutó `composer audit --locked --format=json` en `backend` el 6/11/2026: **58 avisos en 13 paquetes** al inicio, distribuidos en 1 crítico, 15 altos, 26 medios, 8 bajos y 8 sin severidad asignada. Con `--no-dev` fueron 53 avisos en 12 paquetes. Se actualizó `composer.lock` (Laravel 12.71.0 y dompdf 3.1.6, entre otros); la repetición de `composer audit --locked` devolvió **0 avisos**, `composer validate` fue válido y `php artisan test` pasó 6 pruebas/61 aserciones. Los conteos del feed no son incidentes observados.

## Accesibilidad: criterios de aceptación

- El panel se encuentra en landing, login y vistas autenticadas, también en ancho móvil.
- Modos `normal`, `protanopia`, `deuteranopia`, `tritanopia`, `acromatopsia` y `alto contraste` cambian tokens semánticos para acciones, estados, alertas, enlaces y gráficos. El modo elegido se conserva al navegar y recargar sin depender del login.
- Ningún estado se identifica solo por color. Etiquetas como «Pendiente», «Aprobado» y «Error», símbolos y leyendas acompañan a la paleta. Para gráficos se revisan marcadores/patrones cuando el color no basta.
- Contraste de texto normal ≥4.5:1, texto grande ≥3:1 y componentes no textuales/foco visible ≥3:1 donde aplica WCAG 2.2 AA. Se deben medir pares reales y registrar los resultados, no inferirlos de la apariencia.
- La voz se activa voluntariamente en español; se puede detener/desactivar; anuncia acciones principales por foco o selección y mensajes importantes sin repetir cada tecla ni superponer audios. Se comprueba con teclado y toque donde el navegador ofrece `speechSynthesis`.
- No pronunciar contraseñas, tokens, nombres o datos privados de clientes, contenidos de campos ni respuestas completas del servidor. Si no existe síntesis de voz, el control comunica la limitación y conserva etiquetas accesibles.

## Matriz de pruebas a registrar

### Verificación estática de contraste realizada

El 6/11/2026 se extrajeron los valores hexadecimales heredados de `:root` y de cada `[data-vision]` en `frontend/src/index.css` y `landing-page/src/styles.css`. Se calculó luminancia sRGB y razón `(Lclaro + 0.05) / (Loscuro + 0.05)` para los pares semánticos utilizados por texto, botones, estados y foco. Se evaluaron 11 pares por modo del CRM (`ink/surface`, `muted/surface`, blanco/`primary`, blanco/`success`, blanco/`danger`, blanco/`warning`, blanco/`info`, `success/soft`, `danger/soft`, `warning/soft`, `info/page`) y 7 pares por modo de landing (sin fondos `soft` ni `page` equivalentes). Los mínimos fueron:

| Modo | CRM, mínimo entre 11 pares | Landing, mínimo entre 7 pares |
| --- | ---: | ---: |
| Normal | 5.51:1 | 6.29:1 |
| Protanopia | 5.68:1 | 6.26:1 |
| Deuteranopia | 5.89:1 | 6.29:1 |
| Tritanopia | 5.72:1 | 6.29:1 |
| Acromatopsia | 7.62:1 | 7.46:1 |
| Alto contraste | 8.01:1 | 9.00:1 |

Todos esos pares superan 4.5:1. **Alcance limitado:** el cálculo no cubre imágenes, gradientes, todos los colores fijos heredados, componentes de terceros, hover ni la experiencia visual/audio en navegador. No se declara conformidad integral WCAG 2.2 AA hasta una revisión visual y de interacción.

| Caso | Entorno/dispositivo | Resultado | Evidencia |
| --- | --- | --- | --- |
| Normal → protanopia → deuteranopia | Chrome 156 headless, preview local, desktop/móvil emulado, 6/11/2026 | **Comprobación instrumental:** cambiaron `--ui-primary`, `--ui-danger` y `--ui-success`. Falta inspección visual completa de cada componente. | `browser_probe.py` y capturas locales en `D:\ventas-melamina-backups\APF3-2026-11-06`. |
| Tritanopia → acromatopsia → alto contraste | Mismo entorno | **Comprobación instrumental:** cambiaron las mismas variables; contrastes de tokens calculados arriba. | Mismo script y capturas. |
| Persistencia tras recarga y login | Chrome 156 headless, preview y CRM dev local | `alto-contraste` persistió tras recarga en CRM y landing. En CRM autenticado, `tritanopia` persistió tras recargar `/solicitudes` en móvil emulado; el modo `deuteranopia` permaneció al pasar de login a dashboard. | [JSON de navegador](evidencias/browser-resultados.json) y [captura móvil](evidencias/crm-authenticated-mobile.png). |
| Voz activada, foco con teclado y acción principal | Chrome 156 headless, landing desktop, preview local | Spy de `speechSynthesis.speak` confirmó `es-PE` y foco en «Solicitar cotización». **No demuestra audio audible** ni uso en un dispositivo físico. | `browser_probe.py`, llamada interceptada. |
| Voz detenida/desactivada y navegador sin síntesis | Chrome 156 headless, landing desktop | Tras «Detener voz», el spy no registró nuevas llamadas al refocar el control. Navegador sin síntesis: **pendiente**. | `browser_probe.py`. |
| Operación real de CRM con accesibilidad activa | Chrome 156 headless, CRM dev + API/SQLite separadas, escritorio | Vendedor inició sesión con `deuteranopia` y voz activadas, abrió Solicitudes y registró una solicitud de servicio. El spy captó `Iniciar sesión`, `Abrir solicitudes`, `Registrar solicitud` y confirmación genérica; **no** registró contraseña ni texto del formulario. | [captura de operación](evidencias/crm-authenticated-desktop.png); DB sintética separada. |

Una prueba por CLI o spy puede verificar código, llamadas de voz y API, pero no prueba que el usuario haya oído el audio. La prueba con teclado/tacto físico, navegadores sin síntesis y los dispositivos/red externa quedan pendientes hasta que se efectúen realmente. La revisión de contraste calculó pares de tokens; no constituye una auditoría completa WCAG de todas las vistas.
