# Changelog

## 1.0.5-rc.2 — 23/10/2026

- Conserva 404.php exacto del tema activo1.0.4 tras comparación de fuente.
- Evita desbordamiento de la cuadrícula Home si falla la foto externa mediante min-width:0 local.
- Versiona assets RC2; mantiene RC1 y su ZIP original.
- Corrige inicialización de reglas y puerto en el fixture de QA, sin bypass HTTP en el tema.
- Añade evidencia de restauración, instalación ZIP y rollback de tema con respaldo privado; documenta límites de hosting/MU plugins y vigencia del respaldo.

## 1.0.5-rc.1 — 23/10/2026

Base acumulativa: `97584768e2fedae6e265e4e148d49994da6249e0` (Fase 10).

- Conserva las plantillas, Gutenberg, navegación, blog, taxonomías y controles de formularios de Fases 6–10.
- Añade `404.php`: mensaje propio con H1 y rutas de recuperación; no usa el listado de Blog para errores.
- Home retira el canonical de WordPress Core antes de imprimir el fallback ya existente. Evita duplicación cuando Yoast no está presente; mantiene el comportamiento de Home con Yoast.
- Bootstrap de páginas requiere `AR_ENABLE_STAGING_BOOTSTRAP === true`, además del host y permisos originales. Desactivado por defecto: activar el tema no debe crear/publicar páginas.
- Versiona CSS y JS con la versión del tema para invalidación coherente de caché.
- Genera ZIP determinista, manifest, pruebas reproducibles y planes de instalación/reversión.

Sin cambios editoriales, CSS visual, formularios activos, correos, DNS, plugins del sitio ni configuración de indexación. El diagnóstico de correo de Fase 9.2 se conserva como `.php.txt` fuera del tema; no se incluye en el ZIP.
