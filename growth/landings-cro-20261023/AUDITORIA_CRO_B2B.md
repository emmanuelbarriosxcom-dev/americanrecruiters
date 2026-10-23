# Auditoría CRO B2B — American Recruiters México

## Decisión principal

Montar y revisar los contenidos de las rutas existentes antes de ampliar el árbol. Home sí tiene contenido B2B; las siete páginas interiores inspeccionadas siguen siendo estructuras provisionales. El primer impedimento del recorrido es llegar desde «Solicitar propuesta» a Contacto con campos deshabilitados, una situación deliberada del staging. No debe presentarse como un fallo de entrega del formulario ni resolverse activándolo en este encargo.

| Página | Evidencia actual observada | Fricción concreta | Prioridad de ejecución / propuesta |
|---|---|---|---|
| Home `/` | H1 «El liderazgo adecuado cambia el rumbo», oferta en texto, CTA a Contacto, selector de necesidades y ruta separada Candidatos | La tarjeta Fast Placement recibe protagonismo semejante a las dos ofertas actuales aunque está en definición; el CTA principal acaba en recepción deshabilitada | P1. Conservar hero y CTA. Dejar Fast Placement como información secundaria hasta completar oferta; no añadir promesas de velocidad |
| Servicios `/servicios/` | H1 «Servicios», tres tarjetas con «Contenido comercial pendiente» y enlace «Ver estructura» | No permite elegir por tipo de necesidad | P0. Comparador por necesidad y rutas existentes, con Fast Placement separado |
| Headhunting `/servicios/headhunting/` | H1 «Headhunting», frase de plantilla y CTA a Contacto; no desarrollo comercial | No explica encaje ni qué preparar para solicitar propuesta | P0. Conservar copy útil de Fase 3 y añadir ficha breve del encargo |
| Servicio personalizado `/servicios/reclutamiento-personalizado/` | H1 específico, pero cuerpo provisional y CTA genérico | No explica diferencia respecto a búsqueda ejecutiva ni límites del encargo | P0. Tres preguntas sobre puesto, apoyo y participación de RH; no prometer volumen o evaluaciones |
| Fast Placement `/servicios/fast-placement/` | Declaración explícita de oferta pendiente, sin tiempos ni garantías | No hay base para comparar su utilidad; «Solicitar propuesta» puede sugerir oferta cerrada | P1. CTA informativo en prototipo y cuestionario comercial, sin oferta definitiva |
| Metodología `/metodologia/` | H1 «Cómo trabajamos», placeholder; solo enlace a Inicio en `main` | La ruta de evaluación no aporta contexto ni siguiente paso en contenido | P1. Reutilizar secuencia conceptual ya redactada, ratificar antes de tratarla como proceso oficial; CTA a Contacto |
| Nosotros `/nosotros/` | H1 «Nosotros», placeholder; sin equipo o pruebas en contenido | No aporta responsables identificables para contrastar la firma | P1. Módulo de responsables/casos solo con materiales acreditados; no importar «10 años» o superlativos históricos |
| Contacto `/contacto/` | «Demostración deshabilitada», campos básicos, teléfono opcional, selección de servicio, botón no disponible y aviso pendiente | No existe recepción comercial activa; falta guía de mensaje y derivación de candidatos dentro del contenido | P0. Guía de consulta y ayuda por campo listas; activación de recepción queda fuera del encargo y depende de privacidad/receptor/QA |

## Qué ya funciona y debe conservarse

Header distingue Candidatos y CTA empresarial. Home explica headhunting y selección; usa servicios y metodología como rutas secundarias. No falta un CTA en Home: añadir otro módulo idéntico no resuelve la recepción deshabilitada. Las fotos se etiquetan ilustrativas; no son pruebas de equipo. Fast Placement no anuncia SLA. No hay evidencia de un aumento de conversión ni una prueba A/B realizada.

## UX, móvil y evidencia visual

Las ocho URLs devolvieron contenido navegable en navegador. A 1280 px, el ancho del documento fue 1280 en los ocho registros. Se observó la composición de Contacto y el maestro; capturas en `evidencias/`. El control de viewport solicitó 390×844 pero mantuvo `innerWidth=1280`, `innerHeight=720`: **QA móvil del staging pendiente**, no validado por una captura de escritorio. Los prototipos se revisan aparte en marcos de ancho CSS real; ver `QA_PROTOTIPOS.md`.

## Hipótesis y medición

1. Elegir por necesidad puede facilitar el paso del hub a una landing pertinente. Comparar clics a servicio / sesiones del hub, por dispositivo.
2. Una ficha del encargo puede mejorar la utilidad de las consultas. Evaluar solicitudes válidas B2B con contexto / solicitudes B2B recibidas. Validación comercial, no inferida de clics.
3. Separar candidatos del contacto puede reducir consultas mal dirigidas. Contar solicitudes de candidatos recibidas por canal empresarial sin enviar datos personales a analítica.

Medir desde el lanzamiento real con línea base comparable. No hay datos GA4/GSC de conversión consultados en este trabajo. Ninguna meta porcentual ni tasa actual se ha inventado.

## Fuentes y alcance de evidencia

Inspección: 23/10/2026. Repo base: `a432c88c19a422cba50d571ce4f367119e0e8465`.

- **S1** Staging: https://desarrollo.americanrecruiters.mx/ y siete interiores. Evidencia propia: `evidencias/staging-inspeccion.json`, registros por URL, texto y enlaces del DOM. No equivale a un ensayo de conversión ni a una revisión del backend.
- **S2** Sistema aprobado: https://emmanuelbarriosxcom-dev.github.io/americanrecruiters/diseno/; `diseno/css/design-system.css`, tokens y componentes `.ar-*`; `diseno/README.md`, «Reglas». Screenshot `evidencias/diseno-maestro-1280.jpg`.
- **S3** Arquitectura V1.1: https://emmanuelbarriosxcom-dev.github.io/americanrecruiters/arquitectura/; `arquitectura/index.html`, registros `services`, `headhunting`, `recruiting`, `futureIndustries`, `futurePlaces`. Evidencia de propuesta, no de oferta contractual.
- **S4** Copy previo: `contenidos/headhunting.md`, `servicio-personalizado.md`, `servicios.md`, `contacto.md`, `metodologia.md`, `nosotros.md`, `fast-placement-pendiente.md`, bloques A/B. La existencia del archivo no demuestra implementación. `contenidos/matriz-validaciones.md` fecha 17/10/2026: separa confirmado, propuesta y pendiente; no se han ratificado sus pendientes en esta fase.
- **S5** Web anterior: https://www.americanrecruiters.com.mx/servicios/; menciona directivos, gerenciales, administrativos y operativos. https://www.americanrecruiters.com.mx/contacto/ contiene en su DOM Bosque de Ciruelos 160, Miguel Hidalgo, 11700 CDMX. `evidencias/web-anterior.json` y `contacto-anterior-cobertura.json`. Publicación histórica no prueba capacidad vigente, oficina actual ni cobertura contratada.
- **S6** Research parcialmente recuperado: extracto de trabajo Fase 5 del 17/10 (`datos-workbooks.json`, bloque `keywords`), incorporado en `evidencias/research-recuperado.json`. Registros AR-064, AR-038, AR-039, AR-044, AR-057 y AR-062 con referencias Semrush México fechadas 09/10/2026. No se abrió el informe original de Semrush en esta fase, ni se verificó demanda actual o GSC. Los términos orientan hipótesis; no se trasladan volúmenes a proyecciones de conversión.

**READY** = módulo editorial utilizable con lo observado, pendiente de revisión final y montaje autorizado. No significa publicado ni formulario habilitado. **CONDICIONADO** = requiere evidencia comercial específica antes de integrarse. **DESCARTADO** = no abrir una URL en esta fase por duplicación o falta de justificación.
