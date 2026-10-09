# Evidencias APF2

## Disponibles

- Salidas completas de Laravel, lint y build en evidencias/*.txt.
- api.json: login de cuatro usuarios, 401, 403, consultas y logout.
- navegador.json: resultado de navegacion de tres roles y consola.
- venta-credito-mysql.json: venta sintetica, stock y saldo antes/despues.
- venta-efectivo-interfaz.json: resultado del formulario de venta, si la verificacion termino.
- restauracion.json: respaldo con hash y comparacion de 43 tablas.
- preservacion.json: registros anteriores conservados.
- Captura del login sin credenciales, menus de roles y carrito con datos exclusivamente sinteticos: apf2-*.png.
- git-final.txt: rama, commit, cambios y diff --stat al terminar.

## Capturas para la entrega academica

1. Git: rama apf2-vercel y commit base. Captura de GitHub pendiente hasta autorizar commit/push; los cambios aun son locales.
2. Migraciones ejecutadas y registros filtrados por APF2, sin mostrar datos anteriores.
3. Login y menus de admin/gerente/vendedor; pueden usarse las capturas incluidas. No capturar contrasenas.
4. Respuestas 401/403 en cliente HTTP, ocultando Authorization.
5. Venta sintetica que modifica stock y saldo; comparar con los JSON incluidos.
6. Salidas php artisan test, npm run lint y npm run build.
7. Restauracion en base apf2_restore_20261009_081448 y coincidencia de tablas.
8. URL Vercel/API y captura de health remoto: pendiente, no existe publicacion verificada.

Los respaldos SQL y el archivo privado de credenciales no son evidencias publicables. El Dockerfile esta preparado pero no probado. Los modulos operativos historicos no tienen cobertura completa en esta entrega; no presentarlos como validados solo por tener menu.
