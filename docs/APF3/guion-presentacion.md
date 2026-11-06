# Guion breve para la exposición APF3

**Archivo para proyectar:** [Presentacion-APF3-CesarControl.pptx](Presentacion-APF3-CesarControl.pptx). Duración sugerida: 10–12 minutos más demostración. Las notas del expositor también están dentro del PPT. Usar solo capturas sintéticas ante público; los datos de la copia MySQL del propietario pueden contener información privada.

| Diapositiva | Mensaje oral sugerido | Evidencia principal |
| --- | --- | --- |
| 1. Portada | «Este es el avance APF3 de CesarControl. Mostraré el incremento y su estado real de publicación». | [Documento integrado](documento-integrado.md) |
| 2. Alcance | Recorrer los ocho temas de la consigna agrupados en servicio, gobierno, calidad, capacidad, despliegue y evidencia. | [README APF3](README.md) |
| 3. APF2 | El informe APF2 reportaba frontend en Vercel, pero API y MySQL locales. Se corrigieron piezas faltantes y se creó CI. | [Matriz APF2](observaciones-apf2.md) |
| 4. Arquitectura | Explicar React/Vite → Laravel/Sanctum → MySQL XAMPP en **esta PC**. Las ventas nuevas de la exposición van a la copia. | [Despliegue y respaldo](despliegue-reversion.md) |
| 5. Solicitudes | Mostrar los tres tipos y el flujo; 401/403 y aprobación de acceso sin asignación automática de permisos. | [Contrato y pruebas](solicitudes.md) |
| 6. Diseño | Mostrar login y móvil emulado. La nueva paleta es grafito/crema/madera; seis modos y voz voluntaria. | [Capturas](evidencias/redesign-login-desktop.png), [contraste](evidencias/redisenio-contraste.json) |
| 7. Datos | Distinguir original, copia de trabajo y demo sintética. El backup se verificó restaurando 43 tablas. | [Resultados MySQL](evidencias/mysql-datos-expo-resultados.json) |
| 8. Cambios | Cada cambio tiene motivo, impacto, commit, revisión, publicación y posible reversión. | [Bitácora](control-cambios.md) |
| 9. CI | Enseñar el run aprobado y mencionar los dos fallos corregidos: prueba del seeder y build Docker. Verificar el run del commit final antes de exponer. | [CI](ci.md) |
| 10. Rendimiento | p95 de 181.8 a 243.7 ms en API MySQL local, 20 lecturas secuenciales por ruta; 0/98 respuestas 5xx. | [Capacidad](capacidad.md) |
| 11. Capacidad | Explicar qué límites de hosting y métricas de red real se medirán al publicar. | [Plan](capacidad.md) |
| 12. Sprint 6 | S6-01 a S6-05 con evidencia local/CI; S6-06 remoto abierto; S6-07 documentado. | [Sprint 6](sprint-6.md) |
| 13. Retrospectiva | Hay aprendizajes técnicos. La reunión, asistentes y acuerdos todavía no constan y se deben completar con el equipo. | [Sprint 6](sprint-6.md) |
| 14. Despliegue | **Decir claramente:** la URL pública APF3 aún no existe. El CRM local funciona; faltan cuentas/plan, MySQL y adjuntos remotos, backup y prueba externa. | [Plan y reversión](despliegue-reversion.md) |
| 15. Cierre | Abrir login, entrar con cuenta demo, consultar módulos y registrar una solicitud. Mostrar GitHub Actions y los documentos. | [Pruebas](pruebas.md) |

## Demostración en vivo

1. Ejecutar `iniciar-exposicion.cmd` si el CRM no está abierto y esperar login en `http://127.0.0.1:5173/login`. XAMPP MySQL debe estar disponible; el script comprueba el servicio. No se ejecutan migraciones al iniciar.
2. Usar una de las **cuentas de exposición entregadas por chat**, sin proyectar la contraseña. Las credenciales no forman parte del PPT ni del repositorio.
3. Abrir productos, clientes y ventas. Si hay público externo, evitar proyectar registros identificables de la copia del propietario; preferir las capturas sintéticas del PPT.
4. Mostrar Solicitudes: tipo, prioridad, estado y responsable; explicar por qué aprobar acceso no altera roles.
5. Mostrar el panel de accesibilidad antes/después del login. La voz debe activarse solo si el dispositivo y la sala permiten escucharla sin revelar información privada.
6. Enseñar la ejecución de CI del commit final en GitHub y el estado de publicación: rama subida, sistema local operativo, despliegue remoto aún pendiente.

**Frase de cierre precisa:** «El incremento APF3 está implementado y verificado localmente, con CI en GitHub. La condición de URL pública integral sigue abierta hasta conectar el alojamiento, desplegar Laravel/MySQL y probar desde otra red».
