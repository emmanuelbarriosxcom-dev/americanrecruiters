# Expansión editorial B2B — American Recruiters México

Entrega del 23/10/2026. **Seis artículos completos**, revisados y listos para montaje editorial en Gutenberg. No son briefs ni posts ya creados en WordPress.

## Artículos terminados

| ID | Documento | Intención específica | Cluster original | Palabras del cuerpo |
| --- | --- | --- | --- | ---: |
| AR-EXP-01 | [Honorarios de headhunters](01-honorarios-headhunter.md) | Comprender y comparar honorarios y alcance | D01 | 1031 |
| AR-EXP-02 | [Definir una vacante](02-definir-vacante.md) | Preparar un brief de contratación | D03 | 1010 |
| AR-EXP-03 | [Evaluación de candidatos](03-evaluacion-candidatos.md) | Comparar evidencias y pendientes con una matriz | D04 | 999 |
| AR-EXP-04 | [Entrevista estructurada](04-entrevista-estructurada.md) | Preparar preguntas y criterios para el entrevistador | D03 | 977 |
| AR-EXP-05 | [Candidatos pasivos](05-candidatos-pasivos.md) | Delimitar mercado, acercamiento e información | D02 | 975 |
| AR-EXP-06 | [Retención e incorporación](06-retencion-incorporacion.md) | Organizar continuidad después de aceptar la oferta | D05 | 1066 |

Total: **6058 palabras** de cuerpo editorial, sin fichas ni notas. Conteo con enlaces convertidos en su texto visible; incluye encabezados y tablas. No se añadió relleno para alcanzar un límite.

Cada Markdown contiene una ficha SEO completa —title, descripción, H1, slug, keyword principal y secundarias, intención, buyer persona, journey, objetivo de conversión y excerpt— seguida del artículo completo y notas no publicables claramente separadas.

## Archivos de apoyo

- `MATRIZ_ARTICULOS_SEO.xlsx`: tres pestañas. **Matriz** resume temas y decisiones; **Ficha SEO** permite revisar los metadatos y el enlazado; **Research** conserva 16 keywords originales utilizadas como evidencia, con volumen/KD históricos y procedencia. No suma volúmenes de variantes ni atribuye métricas a frases nuevas.
- [CONTROL_DUPLICADOS.md](CONTROL_DUPLICADOS.md): once históricos con ID, URL, intención y límites; contraste con WordPress actual; sustituciones de los candidatos iniciales y separación respecto de landings.
- [BRIEFS_IMAGENES.md](BRIEFS_IMAGENES.md): seis propuestas 16:9 con dirección Ejecutivo Editorial, ALT condicionado a la imagen real, ubicación y recursos explicativos.
- [PLAN_INTERLINKING.md](PLAN_INTERLINKING.md): dieciséis enlaces contextuales ya incluidos y relaciones futuras entre artículos, todavía no enlazadas a URLs inexistentes.

## Research e inventario utilizados

1. `American_Recruiters_Keyword_Research_2026.pdf`, original de 38 páginas, y `American_Recruiters_Keywords_Mexico_2026.csv`, 189 keywords. Semrush México, extracción **09/10/2026**. Las secciones «Información que apoya la contratación» y «Clusters semánticos y solapamientos», junto con los registros AR-101–152, fundamentan D01–D05.
2. Inventario de migración de fase 7 y plan de migración de fase 5; originales de los once posts.
3. WordPress de desarrollo, lista de Entradas y once previews revisados el 23/10: **11 históricos en borrador + ID 5 de QA**. No se alteraron.
4. [Repositorio de contenidos](https://github.com/emmanuelbarriosxcom-dev/americanrecruiters/tree/main/contenidos), [blog anterior](https://www.americanrecruiters.com.mx/blog/) y [entorno de desarrollo](https://desarrollo.americanrecruiters.mx/).

Las frases «cómo definir una vacante» y «búsqueda de candidatos pasivos» son elecciones editoriales apoyadas en planificación y técnicas/proceso de headhunting. No tienen un volumen propio atribuido. Las restantes se normalizan ortográficamente respecto del CSV sin inventar nuevas consultas medidas.

Las fuentes profesionales externas y su alcance están identificados en el plan de enlaces y en cada artículo. Se consultaron AESC, OPM y la introducción pública de CIPD. No se usaron porcentajes de éxito, estadísticas de mercado, precios, garantías ni supuestos casos de la marca.

## Montaje posterior en Gutenberg

1. Crear las entradas solo cuando exista instrucción para ello; esta entrega no las crea.
2. Utilizar el H1 como título de la entrada y evitar duplicarlo en el cuerpo si la plantilla ya lo muestra.
3. Montar solamente el texto entre `INICIO_ARTICULO` y `FIN_ARTICULO`, excluyendo el H1 que se use en la cabecera. No pegar el front matter ni las notas editoriales.
4. Registrar title y descripción en Yoast, keyword principal como focus keyphrase y excerpt en su campo. Mantener un slug único y confirmar la URL definitiva antes de enlazar otros artículos.
5. Convertir tablas en bloques Tabla reales y comprobar su lectura móvil. Seleccionar/licenciar las imágenes de los briefs y confirmar el ALT contra el recurso final.
6. Probar la plantilla, HTTP, canonical, robots, enlaces y CTA después del montaje. El QA editorial no equivale a un semáforo de Yoast ejecutado o a una validación pública de artículos todavía inexistentes.

## QA y límites de la entrega

- Lectura editorial inicial y segunda revisión completa de los seis cuerpos, metadatos, excerpt, CTA y fuentes.
- Un H1 por documento y niveles H2/H3 sin saltos; seis slugs únicos, diferentes de los once históricos.
- Temas de externalización/comparación interno-externo y errores generales sustituidos por respuestas específicas; motivos documentados. No se declara ausencia absoluta de canibalización basada en GSC.
- Matriz Excel exportada, inspeccionada y renderizada para comprobar legibilidad. Métricas históricas tomadas literalmente del CSV; valores `ND` conservados como información no disponible.
- No se ejecutó un detector externo universal de plagio ni se garantiza posicionamiento. No se copiaron párrafos de artículos históricos o fuentes externas.

**Pendientes de montaje, no de redacción:** selección/licencia de fotografías, validación visual en la plantilla, Yoast y QA público después de montar. Además, las páginas comerciales de desarrollo muestran contenido estructural pendiente y Contacto sigue deshabilitado para envíos reales: deben estar aprobadas y activas antes del lanzamiento comercial. No se modificaron para resolverlo dentro de este encargo.

## Rama y conservación

Rama de entrega solicitada: `content/articulos-b2b-20261023`. Solo se añaden archivos dentro de `contenidos/expansion-editorial/`; no se altera `main`, WordPress, producción, los once posts ni el diseño global. El paquete ZIP contiene la misma carpeta editorial para descarga independiente.
