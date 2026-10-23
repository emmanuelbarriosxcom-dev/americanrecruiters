# American Recruiters — 1.0.5-rc.2

Fecha: 23/10/2026. Rama: release/ar-ejecutivo-editorial-20261023.

**RC2 validado en el clon del respaldo disponible; integración compartida pendiente de paridad del hosting y actualización del estado de referencia.** Ver PENDIENTES_INTEGRACION.md. No instalado en producción ni desarrollo compartido. RC1 se conserva inmutable.

## Cambios respecto a RC1

- Restaura exactamente el 404.php del tema activo 1.0.4, evitando una sustitución visual innecesaria.
- Corrige el desbordamiento de la cuadrícula de Home cuando la fotografía externa no está disponible: min-width:0 en los hijos directos de moment-layout. No oculta contenido ni overflow. Caso reproducido a 390px con imagen bloqueada: scrollWidth 466 → 390.
- Versiona assets como 1.0.5-rc.2. Conserva controles de bootstrap opt-in, canonical y formularios de RC1.
- Corrige únicamente el fixture de QA para inicializar WP_Rewrite antes de regenerar reglas. No añade una corrección artificial de HTTP al tema.

## Paquete y compatibilidad

ZIP release/ar-editorial-1.0.5-rc.2.zip, 21 archivos bajo ar-editorial/. SHA-256 en MANIFEST.md y release/SHA256SUMS. RC1 permanece en release/ar-editorial-1.0.5-rc.1.zip con SHA original.

WordPress >=6.6 / PHP >=8.0; pruebas del RC2 en WP6.6.2/PHP8.0.30 y WP7.1.3/PHP8.3.33. Gutenberg Core. Clon con los seis plugins estándar activos observados, incluido Yoast gratuito 24.2 y Premium 21.0. No se actualizó ningún plugin compartido.

El tema no incluye BD/uploads, contenido editorial, configuración del hosting ni plugins. La Home Gutenberg y sus asignaciones permanecen en BD. No ejecutar el diagnóstico de correo histórico, conservado como texto fuera del tema.

## Procedencia

Base acumulativa Fase10 97584768e2fedae6e265e4e148d49994da6249e0 → RC1 f167982c4939e117110abff52df93c55283d8315 → RC2. Las ramas técnicas de Fases6–10 son ancestrales a esa base. Nuevas referencias content/articulos-b2b-20261023 (413cfc2) y growth/landings-cro-20261023 (a432c88) revisadas: no incorporan cambios al directorio del tema. No se fusionó ni alteró main.

## Resultado

Instalación mediante la API real Theme_Upgrader, rollback exacto de todos los archivos del tema y conservación de hashes editoriales/opciones. 39/39 rutas por fases, 4/4 endpoints, 9/9 HTTP sintético y 41/41 controles por matriz. El clon lógico no equivale al hosting nativo; el respaldo tiene fecha 16/10. No se recalcularon ni atribuyen métricas Lighthouse o conversiones.
