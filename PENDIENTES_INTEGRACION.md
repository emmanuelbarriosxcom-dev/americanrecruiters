# INT-01–INT-05 — cierre técnico del RC2

Fecha: 23/10/2026. Candidato: **1.0.5-rc.2**. Integración compartida: **NO AUTORIZABLE AÚN**. PASS indica la prueba descrita; FAIL indica un criterio pendiente, no necesariamente un defecto del tema.

| ID | Resultado | Evidencia y alcance |
|---|---|---|
| INT-01 | **PASS** | Lectura de los 20 archivos de texto del tema activo 1.0.4 mediante el editor de archivos, sin guardarlo. Comparación normalizando solo whitespace final: diferencias sustantivas en functions.php, front-page.php y style.css. El 404 activo ya existía: RC2 conserva exactamente su fuente. Logo del respaldo coincide con el paquete; no se certificó por separado el binario actual del servidor. Las ramas content/articulos-b2b y growth/landings-cro no aportan cambios al tema PHP. |
| INT-02 | **PASS — respaldo disponible** | Último respaldo disponible autorizado WPvivid 55e5433ebdcb2, 16/10/2026 23:54 (hora mostrada por WPvivid), 87.77 MB. Restauradas 25 tablas/914 filas de origen, uploads y archivos de configuración preservados privados. El clon usa SQLite y configuración local segura; no ejecuta credenciales del servidor. Solo options aumenta 5 filas al arrancar plugins. No acredita el estado editorial posterior al 16/10 ni equivalencia nativa MySQL/hosting. |
| INT-03 | **PASS** | RC1 inmutable: ruta inexistente 301 por redirect_canonical con reglas mal inicializadas por el fixture. Reiniciar WP_Rewrite y regenerar reglas, sin cambiar tema, devuelve 404. Control con Twenty Twenty-Five y RC1 devuelve 404. Endpoint: bienvenida de Yoast al instalar gratis causa 302 inicial; el cliente que sigue redirects ocultaba el estado. Clon con Premium: GET 405 y POST vacío 403 en ambas acciones, 4/4. No se añadió ningún bypass al tema. |
| INT-04 | **FAIL — paridad completa pendiente** | Seis plugins estándar y versiones verificadas en administración: Elementor 3.27.5, Pro 3.26.2, Importer 0.9.6, WPvivid 0.9.136, Yoast 24.2 y Premium 21.0. Se reprodujeron esas versiones solo en el clon. Hay dos MU plugins del hosting; se conservan sus archivos del respaldo, pero no se certificaron versiones actuales ni se ejecutaron en WASM. Falta validar sus efectos y configuración completa de Yoast en clon nativo equivalente. Sin cambios a plugins compartidos. |
| INT-05 | **PASS — reversión completa del tema** | Theme_Upgrader instala el ZIP final RC2 sobre 1.0.4 y restaura el ZIP privado 1.0.4. 39/39 páginas HTTP entre antes/RC2/rollback, endpoints 4/4. Hashes de todos los archivos del tema vuelven exactamente al inicio; hashes editoriales y opciones seleccionadas permanecen iguales. No fue subida visual por dashboard ni ensayo de desastre de servidor/BD. |

## Para autorizar la integración

1. Certificar los dos MU plugins vigentes y su efecto con Yoast en un clon privado con PHP/MySQL y reglas equivalentes al hosting. No copiar ciegamente plugins del hosting a WASM ni desactivar sus redirecciones en el compartido.
2. Confirmar diferencias de BD/uploads/configuración desde el respaldo del 16/10 y congelar cambios concurrentes antes de desplegar. Si hay cambios relevantes, usar un respaldo más reciente autorizado y repetir el ensayo de actualización/reversión. No se ha creado un nuevo backup en el compartido.
3. Comprobar medios/fuentes con su disponibilidad real y logs del entorno nativo. En el clon se bloquearon a propósito solicitudes externas y correo; no es certificación visual pixel a pixel con servicios externos.
4. Responsable técnico revisa resultados y autoriza una ventana de integración. Este documento no concede autorización de despliegue.

Producción, main y WordPress compartido: **sin modificaciones**. No se publicaron borradores ni activaron formularios. No se incluyen credenciales, SQL, plugins comerciales ni medios privados en GitHub.

Detalle, límites y reproducción: QA_TECNICO.md. Evidencias saneadas: release/qa/rc2/. Los fallos históricos de RC1 permanecen en release/archive/rc1 y release/qa, no representan el resultado final RC2.
