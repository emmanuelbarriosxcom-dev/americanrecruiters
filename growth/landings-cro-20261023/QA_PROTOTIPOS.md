# QA de los prototipos — 23/10/2026

## Método y límites

Se abrieron los seis HTML en el navegador integrado. El control de viewport solicitado a 390×844 mantuvo 1280×720 en el navegador exterior. Para revisar las propuestas se utilizó una página local con marcos HTML: cada documento hijo midió su propio `window.innerWidth`, `innerHeight`, H1 y ancho del documento, y los mostró en la interfaz. No se presenta esto como una prueba en dispositivo físico ni como QA móvil del staging.

| Prototipo | Escritorio: viewport / documento | Marco móvil: viewport / documento | Marco tablet: viewport / documento | H1 | Formulario operativo |
|---|---|---|---|---|---|
| Headhunting | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |
| Servicio personalizado | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |
| Servicios | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |
| Contacto | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |
| Operativo | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |
| Headhunting CDMX | 1280 / 1280 | 390 / 390 | 768 / 768 | 1 | No |

Altura de los marcos: 844 móvil y 1024 tablet. No se detectó overflow horizontal en los documentos medidos. Evidencia estructurada: `evidencias/qa-marcos.json` y `qa-desktop.json`. Capturas por archivo con sufijos `-responsive.jpg` y `-desktop.jpg`.

## Interacciones comprobadas

- Menú compacto de la propuesta CDMX: apertura y cierre manual, enlaces legibles.
- FAQ «¿Cuánto cuesta y cuánto tarda la búsqueda?» de CDMX: expansión manual y respuesta visible sin plazos inventados.
- CTA de cierre de CDMX pulsado dentro del marco móvil: abrió el prototipo Contacto, que mantiene controles deshabilitados y ningún `<form>`.
- Recorrido de contenidos revisado por scroll en CDMX móvil: texto, enlaces, FAQ y cierre. Las capturas iniciales del resto muestran composición superior; no equivalen a un ensayo exhaustivo en cada teléfono.
- Contacto conserva un ancla local `#solicitud` para que el CTA de su hero no recargue la misma página. No se envió información.
- Dos propuestas nuevas tienen cuatro FAQs cada una. No se añadieron FAQs especulativas a Fast Placement.
- Las imágenes utilizadas son únicamente el logo oficial; no se crearon retratos, oficinas ni fotografías como supuesta evidencia.
- No hay endpoints, llamadas de formulario, almacenamiento, GA4/GTM o captación en los prototipos. La página de revisión solo cambia documentos locales y muestra medidas del DOM.

Se comprobaron además 160 referencias locales de los documentos HTML, sin destinos de archivo ausentes. Esto no acredita respuesta HTTP o recepción de los destinos externos.

## Pendiente antes de implementación

Repetir QA sobre las páginas montadas de WordPress y su plantilla real. Confirmar navegación con teclado y lectores de pantalla, contraste con contenido final y medición, privacidad y recepción en una tarea autorizada. No se probó una conversión comercial real ni se certifica accesibilidad completa.
