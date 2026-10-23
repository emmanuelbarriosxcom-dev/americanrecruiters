# Control de duplicados e intenciones

American Recruiters México · Revisión del 23/10/2026 · Alcance editorial, sin cambios en WordPress.

## Evidencia consultada

- Inventario de migración: `american-recruiters-fase7-20261017/entregables/INVENTARIO-11-ARTICULOS.json`, conciliado con contenido original de `origen/posts-api.json` y el plan de migración de fase 5.
- WordPress actual: lista de Entradas y los once previews leídos el 23/10/2026. Se observaron **12 borradores: 11 históricos y una entrada técnica «QA Fase 6 — plantilla editorial — NO PUBLICAR», ID 5**. No hay doce artículos históricos ni seis artículos nuevos montados.
- [Blog anterior](https://www.americanrecruiters.com.mx/blog/): corrobora los temas históricos; el inventario y WordPress permiten recuperar el artículo de CV que no aparece en la primera vista del índice.
- Research original: `American_Recruiters_Keyword_Research_2026.pdf` (38 páginas) y `American_Recruiters_Keywords_Mexico_2026.csv` (189 registros), extracción Semrush México **09/10/2026**. Los datos son históricos, no una consulta actual a Semrush.
- [Mapa de keywords del repositorio](https://github.com/emmanuelbarriosxcom-dev/americanrecruiters/blob/main/contenidos/mapa-keywords.md) y textos comerciales existentes. El mapa del 17/10 declara que entonces no se había consultado el research completo; esta selección sí utiliza el CSV y el PDF originales recuperados.
- Rutas de servicio, Metodología y Contacto abiertas en desarrollo el 23/10. Actualmente muestran estructura o alcance pendiente, no una oferta plenamente activa. El copy propuesto en el repositorio no se confunde con contenido publicado.

Las capturas de datos de esta revisión quedan en el paquete local de evidencias, fuera de la rama pública: inventario actual, once previews y destinos internos. No se cargó HTML autenticado a servicios externos.

## Los once artículos históricos

Los slugs siguientes son históricos y se conservan. Las URLs se muestran en su dominio original, no como nuevas URLs públicas del entorno de desarrollo.

| ID anterior → desarrollo | Título | URL histórica | Intención cubierta | Límite para los nuevos textos |
| --- | --- | --- | --- | --- |
| 781 → 781 | Reclutamiento especializado vs. Reclutamiento interno: ¿Cuándo conviene externalizar la búsqueda? | https://www.americanrecruiters.com.mx/reclutamiento-especializado-vs-reclutamiento-interno-cuando-conviene-externalizar-la-busqueda/ | Decisión de externalización y comparación de recursos | No crear B o C con otra formulación de esa misma comparación |
| 778 → 778 | Nearshoring en México: ¿Está su empresa lista para competir por el talento internacional? | https://www.americanrecruiters.com.mx/nearshoring-en-mexico-esta-su-empresa-lista-para-competir-por-el-talento-internacional/ | Competencia de talento por nearshoring | No crear otra pieza general de nearshoring; retención nueva se limita a incorporación |
| 775 → 775 | ¿Por qué las mejores ternas de candidatos rechazan su oferta de empleo? | https://www.americanrecruiters.com.mx/por-que-las-mejores-ternas-de-candidatos-rechazan-su-oferta-de-empleo/ | Rechazo de oferta y experiencia antes de contratar | AR-EXP-06 empieza después de la aceptación; no repite motivos de rechazo |
| 772 → 772 | Reclutamiento Masivo sin Perder Calidad: Claves para la Industria Manufacturera y Logística | https://www.americanrecruiters.com.mx/reclutamiento-masivo-sin-perder-calidad-claves-para-la-industria-manufacturera-y-logistica/ | Cobertura operativa de volumen | Ninguna pieza nueva promete reclutamiento masivo o capacidad industrial |
| 769 → 769 | El verdadero costo de una mala contratación: Cálculo de pérdidas operativas para PyMEs y Corporativos | https://www.americanrecruiters.com.mx/el-verdadero-costo-de-una-mala-contratacion-calculo-de-perdidas-operativas-para-pymes-y-corporativos/ | Pérdidas por selección incorrecta | AR-EXP-01 compara honorarios y alcance del proveedor, no calcula pérdidas ni ROI |
| 766 → 766 | Escasez de perfiles IT y Tech en México: El reto de los sueldos en dólares y la fuga de talento | https://www.americanrecruiters.com.mx/escasez-de-perfiles-it-y-tech-en-mexico-el-reto-de-los-sueldos-en-dolares-y-la-fuga-de-talento/ | Oferta y condiciones del talento tecnológico | No crear guía TI ni atribuir especialidades no confirmadas |
| 762 → 762 | ¿Cómo contratar talento de alta dirección en México en tiempos de incertidumbre? | https://www.americanrecruiters.com.mx/como-contratar-talento-de-alta-direccion-en-mexico-en-tiempos-de-incertidumbre/ | Contratación estratégica de directivos | No crear «errores al contratar directivos»; búsqueda pasiva nueva aborda el alcance del acercamiento |
| 718 → 718 | Los 5 errores más comunes en el proceso de reclutamiento y cómo evitarlos | https://www.americanrecruiters.com.mx/los-5-errores-mas-comunes-en-el-proceso-de-reclutamiento-y-como-evitarlos/ | Errores generales del proceso | No repetir lista de errores. Brief, entrevista y matriz desarrollan tareas concretas con herramientas distintas |
| 1 → 783 | ¿Qué hacer y qué no hacer en una entrevista de trabajo? | https://www.americanrecruiters.com.mx/hola-mundo/ | Preparación del candidato para entrevista | AR-EXP-04 se dirige al entrevistador y construye preguntas y criterios |
| 159 → 159 | ¿Cuándo contratar un head hunter? | https://www.americanrecruiters.com.mx/como-tener-una-excelente-entrevista/ | Justificación de contratar un headhunter | No crear otra guía «cuándo contratar»; AR-EXP-05 explica mercado, límites e información del acercamiento |
| 156 → 156 | Puntos esenciales de un cv efectivo | https://www.americanrecruiters.com.mx/pasos-para-crear-un-cv-mas-efectivo/ | Consejos de CV para candidatos | No añadir piezas B2C sobre CV o búsqueda de empleo |

Los artículos antiguos contienen algunas cifras y afirmaciones comerciales sin acreditación suficiente. No se reutilizaron como prueba de precios, garantías, tiempos, infraestructura, procesos propios o resultados. Su revisión queda fuera de este encargo y no se modificaron.

## Decisión sobre los seis candidatos iniciales

| Tema sugerido | Decisión | Artículo final y fundamento |
| --- | --- | --- |
| A. Honorarios de headhunters | Conservar con alcance delimitado | AR-EXP-01, D01, AR-101/104/107. Costo del proveedor, no pérdidas por mala contratación |
| B. Cuándo externalizar | Sustituir | AR-EXP-05, técnicas de búsqueda directa y candidatos pasivos, D02, AR-110/111. Evita duplicar 781/159 |
| C. Reclutamiento interno frente a externo | Sustituir | AR-EXP-06, incorporación orientada a retención, D05, AR-143/148/151. Evita duplicar 781 y distingue fase posterior de 775 |
| D. Cómo evaluar candidatos | Desarrollar con herramienta aplicada | AR-EXP-03, D04, AR-139/140/142: matriz que integra evidencias y pendientes |
| E. Errores al contratar posiciones estratégicas | Sustituir | AR-EXP-04, entrevista estructurada para empleadores, D03, AR-135/123. No otra lista del 718 ni versión del 762 |
| F. Definir una vacante | Conservar con brief operativo | AR-EXP-02, D03, AR-130/131/132. Desarrolla documento de entrada; no repite la mención general del 718 |

## Separación entre las seis intenciones

1. **Honorarios:** decisión de compra de un alcance; no fórmula de pérdidas ni guía completa de selección.
2. **Brief:** decisiones previas de la empresa; no explica en detalle entrevistas ni evalúa finalistas.
3. **Matriz:** integración y comparación de evidencias; no desarrolla banco de preguntas.
4. **Entrevista:** diseño y conducción de una conversación; no replica la matriz ni una guía al candidato.
5. **Búsqueda pasiva:** delimitación del mercado y acercamiento; no decide si externalizar ni promete un servicio especial.
6. **Incorporación:** expectativas y seguimiento después de aceptar; no explica cómo negociar ofertas ni vende consultoría de retención.

Hay relación temática entre entrevista y evaluación, y entre brief y búsqueda; no son duplicados de la misma respuesta. Se han limitado los contenidos para que se complementen. Esto es un análisis editorial de intención, **no una demostración de canibalización en GSC**, que no se consultó en este encargo.

## Protección de landings comerciales

- `/servicios/headhunting/`: conserva intención de contratar búsqueda ejecutiva. Las piezas 01 y 05 resuelven dudas previas específicas y remiten al servicio.
- `/servicios/reclutamiento-personalizado/`: conserva la oferta de selección. Las piezas 02 y 03 no se presentan como servicios nuevos.
- `/servicios/`: orientación de oferta, no guía informativa exhaustiva.
- `/metodologia/`: presentación de colaboración, sin atribuirle una metodología técnica aprobada que no consta.
- `/contacto/`: CTA empresarial. No se presenta el formulario de desarrollo como canal activo.

## Conclusión del control

Seis slugs propuestos únicos y diferentes de los once históricos; ningún contenido nuevo se montó o publicó. No se ejecutó un detector externo de similitud de toda la web. La revisión combina lectura de originales y previews, separación de intención y comparación local de texto; no se promete una certificación absoluta de originalidad.
