# QA técnico ejecutado

## Entorno aislado real

WordPress Playground CLI 3.1.60, Node 24.14.0, SQLite y PHP.wasm. No es el servidor MySQL/PHP nativo del destino. La herramienta emitió advertencia de engine (recomienda Node >=24.18.0/npm >=11.16.0) y aviso por usar un solo worker. Las ejecuciones terminaron correctamente. No se actualizó el Node del usuario.

| Matriz | Resultado |
|---|---|
| WordPress 6.6.2 / PHP 8.0.30 | 41/41 controles PASS |
| WordPress 7.1.3 / PHP 8.3.33, sin Yoast | 41/41 PASS |
| WordPress 7.1.3 / PHP 8.3.33, Yoast 24.2 | 41/41 PASS |

Archivos crudos: `release/qa/integration-*.json`. Sus salidas tienen código 0 y stderr vacío. Se corrigió primero la secuencia del runner: las funciones del tema se cargan en la siguiente petición tras `switch_theme`; el primer intento falló por ejecutarlas en la petición de activación. No fue un fallo del tema.

## Cobertura

- Parseo PHP real con `token_get_all(..., TOKEN_PARSE)` de las 17 plantillas/archivos PHP en ambas versiones; no es PHPCS ni un análisis formal de todo PHP.
- Soportes Gutenberg y cuatro patrones registrados; fixtures de contenido en bloques renderizados. No probado guardado/reapertura visual en editor ni todos los bloques de la BD compartida.
- Activación no crea páginas; bootstrap falla cerrado sin opt-in incluso con usuario administrador/host simulado localmente.
- Validadores: entradas válidas ficticias, arrays, email inválido/CRLF, servicios ajenos a allowlist, longitud, campos vacíos después de sanitizar, privacidad sin validar, PDF grande/no subido/contenido falso. No subida real de CV ni antivirus.
- Guardas 405/403/429/503; un POST válido nunca habilita recepción. La plantilla de revisión tiene fieldset y controles disabled, no `<form>` ni consentimiento activo. En otros hosts el componente queda oculto.
- Revisión de código: no `wp_mail`, almacenamiento de CV ni endpoint de éxito en el tema. Dos acciones `admin_post` (públicas/autenticadas) solo devuelven bloqueo; su rate limit guarda un contador HMAC temporal, no una solicitud. El diagnóstico de correo histórico no está integrado.
- `node --check` de navegación, JSON de theme.json y `git diff --check`: correctos.
- ZIP: 21 archivos, lectura íntegra, hashes individuales, lista permitida, sin symlinks ni credenciales/rutas privadas detectadas por el control estático; revisión manual adicional de PHP. No es garantía universal de ausencia de secretos en todo el repositorio histórico.

## HTTP real local con Yoast 24.2

`release/qa/http.json`: 8/8 PASS. Home, Blog, segunda página, artículo, categoría, búsqueda, ID inexistente y borrador no accesible. H1 único; sin warnings/fatales en HTML; borrador centinela ausente; paginación presente; noindex preservado. Home tiene un canonical, artículo un canonical. Yoast omite canonical en archivos noindex de esta prueba: no se añadieron manualmente. Presencia de Yoast 24.2 confirmada en HTML.

**Incidencia abierta:** `release/qa/http-friendly-failure.json`: una ruta amigable inexistente termina redirigida a `/` (200). La prueba por `?p=999999` sí devuelve 404 y usa la nueva plantilla. Falta diagnosticar diferencia en clon con servidor/reglas del destino. No declarado PASS.

## Frontend

Menú real del candidato en instalación local: 390×844, abre menú y Servicios, Escape cierra submenú y después menú, aria-expanded coherente, scrollWidth 390. Captura `local-menu-390.png`. Home sintética local no replica la Home editorial completa; no se atribuyen métricas Lighthouse a este release.

Diseño aprobado y desarrollo consultados mediante navegador a 1280px. Se conservaron CSS visual, logo, estructura y scripts base en Git; las diferencias del candidato están limitadas a los cuatro puntos del changelog. No se certifica equivalencia pixel a pixel de todo el contenido real. FAQ de Home pertenece a bloques editables nativos; el ZIP no debe sustituirla por la maqueta del diseño.

## Comparación remota de solo lectura

Navegador: Home + Servicios, Blog, Contacto, Candidatos, FAQ y Nosotros; una H1 por página muestreada y noindex/nofollow. Home canonical único; varias interiores todavía muestran base estructural. No se confirmaron entradas públicas del blog mediante el listado muestreado. Los formularios detectados a nivel documento en `browser-existing.json` incluyen la búsqueda de la barra administrativa: no son evidencia de recepción comercial activa. Producción y HTML maestro consultados sin modificaciones.

Consultas HTTP sin sesión desde shell recibieron 403 en desarrollo; recursos fuente bloqueados también en el navegador. `public-readonly.json` registra la limitación, no un diagnóstico de caída pública. No se intentó eludirla ni leer PHP por HTTP. La comparación completa de archivos PHP activos queda pendiente. Las capturas con sesión no equivalen a pruebas incógnitas.

## Reproducción

Desde la raíz del repositorio:

```sh
npm install --prefix tools/release
QA_PHP=8.3 QA_WP=7.1.3 QA_YOAST=1 node tools/release/run.mjs
# En otra terminal, con el servidor anterior vivo:
python3 tools/release/check-http.py
node --check wordpress/ar-editorial/assets/js/navigation.js
python3 tools/release/build.py
git diff --check
```

Para mínimo soportado: parar el servidor y ejecutar `QA_PHP=8.0 QA_WP=6.6.2 node tools/release/run.mjs`. Usar Node compatible con la versión del CLI. Cada arranque es temporal y genera fixtures locales publicadas únicamente en esa instalación aislada; no apuntar el runner a un WordPress existente. Las pruebas no envían correos. Interrumpir servidor al terminar.

## Endpoints HTTP aislados

`release/qa/endpoints-local.json`: POST vacío sin nonce devuelve 403 y aviso de canal deshabilitado (PASS). GET del endpoint local termina en 200 sin el aviso esperado (FAIL, no se declara 405 HTTP). Las cuatro pruebas directas de `ar_f9_guard` pasan, pero no sustituyen este control de enrutamiento. Debe comprobarse junto con INT-03 en un clon equivalente al destino. No hubo envíos de correo ni CV.

## Referencias técnicas

- https://developer.wordpress.org/playground/developers/local-development/wp-playground-cli/
- https://developer.wordpress.org/themes/classic-themes/basics/template-hierarchy/
- https://developer.yoast.com/features/seo-tags/canonical-urls/api/
