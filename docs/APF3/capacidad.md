# Línea base y planificación de capacidad

**Actualización para exposición:** las muestras de landing son históricas; la landing independiente fue retirada y el sistema actual entra directamente al CRM.

**Fecha:** 6 de noviembre de 2026 (America/Lima). Las metas heredadas del APF2 (API p95 ≤2 s, carga inicial ≤3 s, errores <1 %, disponibilidad ≥99.5 %) son **objetivos**, no resultados remotos de esta versión.

## Método y entorno

- Interfaz: Chrome 156 headless + Selenium, Vite preview local por `127.0.0.1`, 3 navegaciones por vista/perfil en el mismo navegador. Escritorio 1366 × 768; móvil emulado 390 × 844. Se midieron `loadEventEnd` y `first-contentful-paint` con Performance API. La primera carga y las dos posteriores se muestran para distinguir caché. No se aplicó limitación de red; no es Lighthouse ni celular físico.
- API: Laravel servido por `php artisan serve` en `127.0.0.1:8001`, PHP local y SQLite sintética separada; 20 solicitudes **secuenciales** autenticadas por cada una de cuatro rutas. Se calculó mediana, p95 empírico (19.ª muestra ordenada de 20) y máximo. Una venta pendiente/cobrada y una solicitud de acceso completaron el flujo funcional. No se ejecutó carga concurrente ni sobre producción.
- Tasa de errores: respuestas HTTP 5xx divididas entre 98 respuestas contabilizadas por el script antes de la prueba final de logout. Los 401/403/409 esperados se contabilizaron aparte. El resultado no equivale a disponibilidad sostenida.
- Evidencia JSON: [navegador](evidencias/browser-resultados.json) y [API](evidencias/api-resultados.json).
- Para la exposición se repitió el mismo smoke local sobre MySQL de XAMPP en la base separada `cesarcontrol_apf3_expo` y backend temporal 8002 antes de cambiar el puerto final a 8000. [Resultado MySQL](evidencias/mysql-expo-api-resultados.json).

## Carga de interfaz local

| Vista/perfil | `loadEventEnd` ms, 3 muestras | FCP ms, 3 muestras | Mediana load/FCP ms |
| --- | --- | --- | --- |
| CRM escritorio | 1626.7 / 15.5 / 13.0 | 1764 / 36 / 36 | 15.5 / 36 |
| CRM móvil emulado | 151.1 / 13.6 / 14.5 | 188 / 28 / 28 | 14.5 / 28 |
| Landing escritorio | 144.1 / 13.6 / 11.9 | 268 / 80 / 76 | 13.6 / 80 |
| Landing móvil emulado | 140.1 / 24.8 / 11.7 | 252 / 80 / 64 | 24.8 / 80 |

La primera muestra CRM de escritorio fue un valor atípico de 1.63 s/1.76 s; se conserva en la tabla. Los valores warm cache de una red local **no predicen** la carga en datos móviles. Faltan LCP, TBT, bytes transferidos en red real y medición después del despliegue. El build de landing incluye una imagen hero de unos 2.0 MB; conviene medirla en una red móvil real antes de decidir una optimización.

## Latencia y errores de API local

| Ruta, 20 lecturas | Mediana | p95 | Máximo |
| --- | ---: | ---: | ---: |
| `GET /api/auth/me` | 217.4 ms | 306.1 ms | 311.4 ms |
| `GET /api/productos` | 168.2 ms | 262.4 ms | 274.6 ms |
| `GET /api/clientes` | 168.9 ms | 263.5 ms | 285.1 ms |
| `GET /api/dashboard` | 197.3 ms | 293.1 ms | 314.5 ms |

De 98 respuestas contabilizadas, **0 fueron 5xx** (tasa observada 0/98 = 0 % en esta muestra local). Hubo tres 401/403/409 esperados por las pruebas negativas. Se comprobó una venta de prueba: stock 10 → 9 solo al cobrar y 409 en cobro repetido. Esta muestra pequeña no permite inferir una tasa de errores futura ni el p95 remoto.

La repetición con **MySQL local** dio p95 de 237.7 ms en `GET /api/auth/me`, 181.8 ms en productos, 187.7 ms en clientes y 243.7 ms en dashboard (20 lecturas secuenciales por ruta); también 0/98 respuestas 5xx. Son dos muestras pequeñas de bases locales distintas, no una comparación de rendimiento concluyente ni una prueba de red externa.

## Recursos y plan

No se seleccionó un proveedor ni plan de API/MySQL, por lo que CPU, memoria, conexiones, almacenamiento, suspensión, cuotas y precio **no tienen un valor comprobado** para el hosting final. El Dockerfile existe, pero Docker no está instalado en esta PC y la imagen no se construyó aquí. No se contratará capacidad sin autorización del titular.

1. Antes de desplegar, registrar límites reales del plan elegido y asegurar volumen o disco privado duradero para adjuntos, retención de logs y respaldo MySQL restaurable. Si el plan exige pago, pedir aprobación antes de crearlo.
2. Medir el mismo commit por HTTPS desde escritorio y red móvil externa: al menos tres corridas por perfil, FCP/LCP/TBT, bytes transferidos, login y navegación autenticada. Registrar dispositivo, red, fecha y URL.
3. Repetir las rutas API con 20 muestras por ruta desde fuera de la computadora y una base demo aislada; anotar p95, 5xx, conexión MySQL y tamaño de datos. Separar 401/403 esperados de errores reales.
4. Vigilar p95, 5xx, memoria PHP, conexiones de BD, almacenamiento y backups cada semana al inicio. Ajustar índices, paginación y recursos si las mediciones lo exigen; solicitar autorización antes de un plan con cargos.
5. Probar una restauración de la **misma** base remota y repetir el flujo venta/stock después de cambios de esquema o de hosting. No ejecutar carga agresiva en producción.
