# Pendientes de integración

Estado: **BLOCKED**. El código y ZIP candidatos están entregados; no instalar en WordPress compartido todavía.

| ID | Dependencia técnica | Evidencia / cierre requerido |
|---|---|---|
| INT-01 | Estado final de otros agentes y tema PHP activo | Las ramas remotas forman cadena hasta 9758476. Comparación PHP del servidor no disponible por HTTP y assets restringidos. Obtener copia autorizada del directorio activo, comparar SHA/diff y revisar cualquier hotfix posterior. No sobrescribir cambios concurrentes. |
| INT-02 | Clon de BD y medios finales | Home está en Gutenberg/BD; varias interiores públicas muestreadas siguen como base y el listado no confirma blog migrado. Probar contenido real final y asignaciones de plantillas con el candidato en clon, sin publicar borradores en el compartido. No es solicitud de aprobación comercial. |
| INT-03 | 404 de ruta amigable | Playground redirige URL inexistente a Home; 404 por ID funciona. Además GET del endpoint aislado termina en 200, aunque POST sin nonce devuelve 403; no se ha certificado el 405 HTTP. Reproducir con reglas/servidor del clon equivalente al destino, corregir causa y comprobar HTTP 404 de URL amigable sin pérdida de diseño. |
| INT-04 | Yoast y conjunto exacto de plugins | Local Yoast gratuito 24.2 probado; desarrollo expone recursos Premium 21.0 y Core 24.2. Inventariar versiones realmente activas; no actualizar plugins. Probar canonical/title/robots/grafo con configuración real. |
| INT-05 | Ensayo de actualización y recuperación | ZIP íntegro y activación aislada probada desde directorio montado. No probado upload del ZIP en dashboard ni rollback de BD/tema real. Ejecutar actualización y reversión en clon con respaldo y registrar resultado. |

## Medios y dependencias externas

No incluidos en ZIP:

- `uploads/2026/10/ar-hero-editorial-640.webp`, `-960.webp`, `-1300.webp`; dimensiones/variantes conservadas. La optimización se aplica al bloque con identificador de fotografía original `photo-1556761175-b413da4baf72`.
- Imágenes editoriales de Home bajo `uploads/2026/08/`, recogidas con sus URLs exactas en `release/qa/existing-home.json`; destacados del blog y cualquier medio de interiores requieren inventario completo de BD/uploads.
- Fotografía secundaria externa `images.unsplash.com/photo-1600880292203-757bb62b4baf` y Google Fonts Montserrat. Revisar disponibilidad en el clon; no sustituir imágenes ni redistribuir fuentes sin revisión.
- Configuración de portada/entradas, post_content Gutenberg, asignaciones `_wp_page_template`, usuarios/autores, categorías, permalinks, Yoast y caché no se transportan en el tema.

Formularios y CV permanecen deshabilitados. Privacidad, operación y entrega de correo siguen fuera de este release. No se incorpora ni ejecuta el diagnóstico histórico de correo. Los resultados Lighthouse 92/99/100 son reportes previos: no fueron recalculados ni garantizados para este candidato.
