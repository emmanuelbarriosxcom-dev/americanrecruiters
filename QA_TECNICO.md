# QA técnico — RC2 / 23-10-2026

## Resultados

| Prueba | Resultado | Evidencia |
|---|---|---|
| WP7.1.3 / PHP8.3.33 / Yoast24.2, fixtures | 41/41 PASS | release/qa/rc2/integration-8.3-yoast.json |
| WP6.6.2 / PHP8.0.30, fixtures | 41/41 PASS | release/qa/rc2/integration-8.0.json |
| HTTP local con Yoast, incluyendo ruta inexistente, ID y borrador | 9/9 PASS | release/qa/rc2/http-final.json |
| Clon del respaldo, antes → ZIP RC2 → rollback1.0.4 | 39/39 PASS | release/qa/rc2/clone-phases.json |
| GET/POST vacío, dos endpoints | 4/4 PASS, 405/403 | release/qa/rc2/clone-endpoints.json |
| ZIP real mediante Theme_Upgrader y rollback de todos los archivos | PASS | install-rc2.json, install-rollback.json, clone-results.json |
| Tablas restauradas / integridad editorial | 25/25; hashes sin cambios | import-counts.json, clone-results.json |
| Home en navegador | 360×800,390×844,768×900,1280×900 PASS | browser-rc2-final.json |

Las rutas de evidencia abreviadas corresponden a release/qa/rc2/. Cada fase del clon contiene 13 rutas: Home, Servicios, tres servicios individuales, Metodología, Nosotros, Blog, Contacto, Candidatos, FAQ, Privacidad y una inexistente. Esta última devuelve404, las demás200; H1 único, canonical como máximo uno y noindex. La BD del respaldo contiene 13 páginas públicas,10 páginas borrador y12 entradas borrador; ninguna entrada pública. Los artículos y la paginación probados mediante fixtures NO acreditan una migración editorial publicada en el clon real.

## INT-03: diagnóstico causal

Se utilizó el ZIP RC1 original, sin modificarlo. Con el fixture original, la ruta amigable inexistente devuelve301 hacia Home. La traza identifica WordPress Core redirect_canonical; el error se reproduce con peticiones directas de Playground, fetch sin seguir redirects y HTTP nativo de Node. El fixture cambiaba permalink_structure pero usaba WP_Rewrite ya inicializado con el estado anterior. En el mismo tema inmutable, WP_Rewrite->init() seguido de flush_rules(false) cambia las reglas y devuelve404. Después Twenty Twenty-Five y RC1 dan404. Solo se corrigió el fixture, sin desactivar canonical ni alterar rutas del tema.

El endpoint recién instalado devolvía302 por Yoast Installation_Success_Integration->maybe_redirect (flag should_redirect_after_install_free), y el cliente seguía la redirección hacia una página200. Además el servidor CLI devuelve una redirección inicial a la misma URL en su primera petición HTTP. Son efectos separados de arranque, no éxito de formulario. Superado el onboarding, GET devuelve405. En el clon restaurado con Premium21.0 y Core24.2, GET405/POST vacío403 en ambas acciones. No se enviaron solicitudes válidas, archivos ni correos.

Trazas públicas saneadas: trace-rc1-fixture.json, diagnosis-controls.json, rewrite-same-theme.json y setup-http.json. Fixture histórico inmutable: release/archive/rc1/integration-original.php. Los encabezados privados/cookies no se publican.

## Clon privado y restauración

Respaldo WPvivid ID55e5433ebdcb2, fecha UI16/10/2026 23:54; metadata del servidor17/10 05:54 (diferencia de zona horaria, no dos respaldos distintos). Archivo92034922 bytes, SHA256 f797824763f76a99bd00642bd88e4ada3e6b622d0a0e23b35c9b27de0f30e715.

Ubicación privada local: /Users/emmanuelbarrios/Documents/Codex/AR-integracion-privada-20261023 (directorio700; ZIP y snapshot SQLite600). Conserva SQL original, BD SQLite restaurada al terminar rollback, uploads, core/wp-config original, temas y plugins del respaldo. Ninguno se sube al repositorio. El runtime es temporal y reconstruible con los materiales guardados; la copia clone-restored-rollback.sqlite preserva su BD lógica al concluir.

Se importaron las25 tablas del SQL,914 filas originales. Solo se retiraron del string de trabajo comentarios independientes de metadata WPvivid rechazados por el parser; SQL original intacto. Tras arrancar plugins:919 filas, las5 adicionales en options. Se conservaron las cuentas editoriales y hashes de posts/postmeta/terms/term_taxonomy/term_relationships antes/después/rollback. Opciones template,stylesheet,show_on_front,page_on_front,page_for_posts,permalink_structure y active_plugins iguales. Esto no es comparación byte a byte de toda la BD: plugins/cachés pueden escribir metadatos.

Backup theme1.0.3; fuente textual activa1.0.4 capturada en solo lectura y superpuesta únicamente en la copia de trabajo. Logo del respaldo coincide con RC2; binario actual del servidor no certificado aparte. Plugins estándar contrastados con el admin actual y replicados en el clon: Elementor3.27.5, Pro3.26.2, Importer0.9.6, WPvivid0.9.136, Yoast24.2, Premium21.0. Paquetes públicos exactos de Elementor/Yoast/WPvivid se descargaron solo al clon. Plugins comerciales se mantuvieron privados. Dos MU plugins del hosting preservados desde el respaldo, no cargados en WASM; sus versiones actuales quedan pendientes. No se asume que el respaldo histórico confirme su estado vigente.

Seguridad: solo127.0.0.1 con datos privados; blog_public forzado0; pre_wp_mail bloqueado; HTTP saliente responde204 local sin solicitud externa; cron/auto-updates desactivados; CSP self/data y form-action none. Home/siteurl se sobreescriben localmente, URLs de contenido se adaptan solo al render, sin reemplazar la BD. El wp-config del servidor se conserva, pero NO se ejecutan sus credenciales. El guard inicial devolvía WP_Error y chocó con Elementor al imprimirlo; se corrigió solo el guard a204. No es parche del tema. Log final: cero fatales y errores de BD; un aviso WPvivid de traducción demasiado temprana. No se certifica ausencia universal de notices de plugins.

## ZIP y reversión

El instalador de WordPress Theme_Upgrader procesa el ZIP con overwrite_package, usando un administrador solo en la instalación privada. No es subida por interfaz del dashboard ni simple activación de un directorio montado. Primera ronda probó RC1 y RC2 preliminar:52/52 rutas; ronda final del ZIP con CSS corregido:39/39. Resultado final en clone-results.json.

Rollback desde ZIP privado del tema1.0.4:21 archivos con hashes idénticos al punto inicial, opciones registradas y contenido editorial iguales. No se restauró una BD antigua sobre datos nuevos ni se borraron uploads. No equivale a restauración completa del servidor/BD nativa tras desastre.

## Frontend y defecto corregido

Home del respaldo, sin sesión en el clon: H1 único, un canonical local, noindex/nofollow, sin controles comerciales activos. En360×800,390×844,768×900 y1280×900, scrollWidth coincide con viewport. FAQ abre; menú/submenú móvil y Escape funcionan. El CTA Solicitar propuesta abre Contacto local, sin controles de envío habilitados. No hubo formularios enviados.

Antes del ajuste CSS, la imagen externa bloqueada dejaba una anchura mínima intrínseca en la cuadrícula: scrollWidth466 a390. min-width:0 en sus hijos resuelve el caso sin esconder overflow. No se afirma que el frontend del servidor, con la foto cargada, presentara siempre ese defecto. Se bloquearon fuentes y fotos externas deliberadamente; no es comparación pixel a pixel con recursos externos.

## Reproducción y límites

Playground CLI3.1.60, Node24.14.0, PHP.wasm y SQLite. CLI recomienda Node>=24.18/npm>=11.16 y más workers; hubo avisos, sin fallo final. No representa PHP/MySQL/reglas del hosting.

Instalar dependencias locales con npm install --prefix tools/release. Ejecutar QA_PHP=8.3 QA_WP=7.1.3 QA_YOAST=1 node tools/release/run.mjs y, con el servidor vivo, python3 tools/release/check-http.py. Para mínimo: parar y ejecutar QA_PHP=8.0 QA_WP=6.6.2 QA_PORT=9415 node tools/release/run.mjs. Este último puerto permite evitar otro proceso local; el fixture ya no depende de un puerto fijo. No conectar el runner a sitios existentes.

Para el clon, tools/release/clone-phases.mjs requiere AR_PRIVATE_BACKUP_DIR con db/*.sql, clone-work/{themes,plugins,uploads} y baseline-active-theme.zip preparados desde copia de trabajo privada, nunca directorios compartidos. Puede sobrescribir el tema de esa copia privada. Usa el ZIP final del repo, deja la copia de trabajo en1.0.4 tras rollback y genera evidencia privada. Para el diagnóstico RC1: diagnose-rc1.mjs requiere rc1-source/ar-editorial extraído del ZIP original. Ambos runners deben usarse de uno en uno con puertos libres; parar servidores al terminar. Los scripts de prueba no forman parte del ZIP.

Se preservan informes RC1 como historia. Su FAIL de rutas/endpoints ya tiene diagnóstico, no debe sumarse como fallo del RC2. Pendiente: paridad de MU plugins/hosting y delta de respaldo antes de integración; ver PENDIENTES_INTEGRACION.md.
