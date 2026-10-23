# Release candidate — American Recruiters Ejecutivo Editorial

Versión: **1.0.5-rc.1**. Rama: `release/ar-ejecutivo-editorial-20261023`.
Fecha: 23/10/2026. Estado final: **BLOCKED para integración en WordPress compartido**.

El candidato y el ZIP están completos. La activación y ejecución se probaron en WordPress Playground aislado, no en el entorno compartido. No se ha instalado este ZIP en desarrollo ni en producción.

## Procedencia verificada

`main a432c88` → Fase 6 `87cfb7a` → Fase 8 `353ca6b` → Fase 9 `ddd7784`, `0c9abcf` → Fase 9.2 `5f92480` → Fase 9.3 `5ef1e06`, `5f75133` → Fase 10 `f81a559`, `9758476`.

Todos los extremos solicitados son ancestros de Fase 10; no se fusionaron ramas. Las referencias y SHA completos están en `release/qa/branches.json`. La rama local Fase 6 `1741712` tiene el mismo árbol `wordpress/` que Fase 6 remota: no aporta un parche pendiente al tema. El repositorio local es superficial en `main`; se verificó toda la cadena de trabajo disponible desde ese punto. No había ramas remotas posteriores adicionales en la consulta del 23/10.

## Entrega

ZIP `release/ar-editorial-1.0.5-rc.1.zip`, 21 archivos, raíz `ar-editorial/`. `MANIFEST.md`, `release/manifest.json` y `release/SHA256SUMS` identifican su contenido. El ZIP no es un respaldo completo de WordPress.

## Dependencias

WordPress >=6.6; PHP >=8.0. Gutenberg de Core. Google Fonts (Montserrat, solicitud externa) y medios de uploads existentes. Yoast no es imprescindible para activar el tema; debe conservarse la configuración SEO del sitio. La prueba local adicional usó Yoast gratuito 24.2; no replica Premium 21.0 y todos los plugins observados en desarrollo.

Las páginas, entradas, categorías, autores, asignaciones de plantillas, opciones Home/Blog, navegación editorial y bloques viven en la base de datos; no vienen en el ZIP. La Home depende de contenido Gutenberg existente. No recrear contenido desde los textos del diseño maestro.

## Motivo del bloqueo

No hay copia autorizada completa del estado actual de BD, uploads y PHP activo para probar una actualización con el contenido real. Además, en Playground una URL amigable inexistente redirige a Home; la consulta explícita de un ID inexistente devuelve correctamente 404. GET del endpoint aislado tampoco entrega el 405 esperado, aunque POST vacío sí entrega 403 y la guarda pura pasa. Falta reproducir y resolver el enrutamiento HTTP y la ruta amigable en un clon con el mismo servidor/reglas del destino. No se afirma ausencia total de regresiones ni se permite cerrar ese control con la prueba por ID. Ver `PENDIENTES_INTEGRACION.md`.
