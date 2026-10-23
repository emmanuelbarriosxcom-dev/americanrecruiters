# Plan de implementación — preparado, no ejecutado

## 0. Entrega y límites

Rama `growth/landings-cro-20261023`, carpeta `growth/landings-cro-20261023/`. Solo entregables nuevos; no se modifica `main`, WordPress, archivos de tema ni producción. Los prototipos tienen `noindex,nofollow`, no son páginas comerciales desplegadas, no contienen endpoints o formularios reales. No activar GitHub Pages para esta rama.

## 1. Resolver primero el recorrido existente

1. Conservar Home y su identidad. Tomar contenido Fase 3 como base, nunca confundirlo con lo observado en staging.
2. Revisar y montar el hub + dos servicios; incorporar los módulos SV-01, HH-01 y SP-01 reemplazando bloques equivalentes, sin duplicar guías/CTAs.
3. Incorporar CT-01 como ayuda del formulario, conservar separación empresarial/candidatos. No activar formularios con este trabajo.
4. Revisar Metodología y Nosotros con contenido verificado. No mostrar frases de revisión interna en una web comercial de producción.

## 2. Preparación técnica de una fase futura

Antes de cualquier montaje autorizado: respaldo de página, revisión, título, metadatos, configuración y medios; verificar recuperación. Montar fragmentos en Gutenberg usando componentes existentes, un solo H1 en el hero. No pegar el documento HTML completo ni sus header/footer en el cuerpo WordPress. Los fragmentos no incluyen H1, fuentes, scripts o CSS global.

Los enlaces de las demos tienen `data-destino-wp` con la ruta prevista. Resolverlos en WordPress usando el dominio del entorno. El CTA demo lleva a una pantalla deshabilitada, no a un buzón. Revisar que no se duplique el CTA de la plantilla antes de insertar el módulo de cierre. No instalar plugins ni alterar el tema por este paquete.

## 3. Puertas de publicación comercial

- Alcance y diferenciación aprobados por Comercial.
- Receptor, privacidad y recepción probados por responsables autorizados en fase independiente; una pantalla de éxito no demuestra entrega en buzón.
- Identidad y materiales de confianza acreditados. Si faltan, no inventar ni desplegar marcadores.
- QA de navegación, teclado, contraste, tablas/tarjetas y anchos reales en el frontend; comparar con snapshot previo.
- Conservar canonicals/URL al montar cambios en rutas existentes; titles/metas de Fase 3 siguen sujetos a revisión, no se sustituyen por estas demos automáticamente.

## 4. Expansiones, después

Operativos: empezar por bloque dentro de Servicio personalizado si se ratifica el alcance. Abrir URL propia solo con intención distinta y evidencia suficiente. CDMX: no publicar hasta confirmar cobertura y material local. TI/industria/finanzas: no abrir landings para completar un menú. Fast Placement: completar ficha antes de oferta final.

## 5. Medición después del lanzamiento

Registrar fecha y baseline, no simular datos del staging. Primeros 28 días: navegación hub → servicio → contacto; sesiones por página/dispositivo, clics de CTA por ubicación y solicitudes empresariales registradas con éxito cuando el seguimiento esté verificado. A los 56 días: revisar GSC por query/URL y calidad comercial por servicio, separando cambios de adquisición del cambio de contenido.

Definiciones: CTR de CTA = clics en ese CTA / vistas elegibles del módulo si se mide exposición; si no, usar sesiones de página y etiquetar el denominador. Solicitud = registro confirmado por servidor, no clic. Lead cualificado = solicitud empresarial validada según criterio comercial acordado. No equiparar solicitud, contratación e ingreso. No transmitir nombres, correos, CV, teléfonos ni texto libre a analítica.

Eventos propuestos, **no implementados**: `select_service`, `commercial_cta_click`, `business_enquiry_success`; parámetros de contexto `service`, `cta_location`, `page_path` sin PII. Antes de crear eventos, revisar GA4/GTM existentes y reutilizar equivalentes; no duplicar `generate_lead`. El `success` requiere señal verificable del backend y deduplicación por envío, nunca visita a agradecimiento.

## 6. QA y reversión futura

Validar enlaces reales, H1 único, FAQs, teclado y menú a 360/390/768/1280 o 1440 con `innerWidth` verificado. Guardar captures antes/después. Probar envíos solo en una tarea autorizada con datos TEST. Ante pérdidas de CTA, scroll horizontal, regresión de recepción o tracking duplicado: restaurar la revisión de la página afectada y retirar solo el bloque nuevo; no revertir globalmente el sitio ni tocar otras ramas. No iniciar pruebas A/B sin tráfico y seguimiento suficientes.
