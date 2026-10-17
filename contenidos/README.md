# American Recruiters México — Contenidos interiores · Fase 3

Fecha: 17 de octubre de 2026. Versión editorial 1.0. Idioma: español de México.

## Estado y alcance

Paquete completo de redacción para revisión, no autorización de publicación. No se modificó WordPress, la Home, el diseño, las rutas ni producción. Hay nueve páginas con copy desarrollado, una estructura provisional de Fast Placement y un documento de requisitos de privacidad; estos dos últimos no son textos definitivos.

| Documento | Destino | Estado |
|---|---|---|
| [Headhunting](headhunting.md) | /servicios/headhunting/ | Copy completo; revisión comercial |
| [Servicio personalizado](servicio-personalizado.md) | /servicios/reclutamiento-personalizado/ | Copy completo; revisión comercial del alcance |
| [Servicios](servicios.md) | /servicios/ | Copy completo; tarjeta Fast Placement provisional |
| [Cómo trabajamos](metodologia.md) | /metodologia/ | Copy completo; secuencia editorial pendiente de ratificación |
| [Nosotros](nosotros.md) | /nosotros/ | Copy completo sin antigüedad numérica |
| [Preguntas frecuentes](preguntas-frecuentes.md) | /preguntas-frecuentes/ | Copy completo; condiciones comerciales no presumidas |
| [Contacto](contacto.md) | /contacto/ | Copy completo; formulario y privacidad requieren aprobación |
| [Candidatos](candidatos.md) | /candidatos/ | Copy completo con estados habilitado/no habilitado separados |
| [Blog](blog.md) | /blog/ | Introducción completa; no incluye artículos nuevos |
| [Fast Placement](fast-placement-pendiente.md) | /servicios/fast-placement/ | Solo estructura y copy neutral provisional |
| [Privacidad](requisitos-privacidad.md) | /privacidad/ | Requisitos para revisión legal, no aviso redactado |

Consultar también [mapa de keywords](mapa-keywords.md), [matriz de validaciones](matriz-validaciones.md) y [QA editorial](qa-editorial.md).

## Cómo leer y montar los archivos

Cada página distingue **Bloque A: ficha SEO**, **Bloque B: contenido propuesto** y **Notas no publicables**. Solo el Bloque B aprobado se incorpora al cuerpo de WordPress. Las fichas, fuentes, instrucciones, alternativas de estado y notas internas no deben aparecer como copy comercial.

El texto es una propuesta original basada en fuentes; que una frase sea editorial no significa que el cliente ya la haya aprobado. No hay volúmenes, rankings, datos de Search Console, testimonios, tarifas ni resultados de contratación atribuidos al proyecto.

## Fuentes y límites de evidencia

Fuentes web consultadas en lectura el 17/10/2026:

- [Arquitectura V1.1](https://emmanuelbarriosxcom-dev.github.io/americanrecruiters/arquitectura/): rutas, familias H01/R01, separación B2B/candidatos y Fast Placement condicionado. El rótulo histórico «ninguna página está publicada» del mapa no refleja el estado posterior del staging.
- [Maestro Ejecutivo Editorial](https://emmanuelbarriosxcom-dev.github.io/americanrecruiters/diseno/): composición, módulos y tono de referencia; su copy de demostración no acredita procesos comerciales.
- [Home nueva](https://desarrollo.americanrecruiters.mx/): revisada para diferenciar los interiores. Staging visible y noindex; no confundirlo con producción.
- [Web anterior](https://www.americanrecruiters.com.mx/), [Servicios](https://www.americanrecruiters.com.mx/servicios/), [Nosotros](https://www.americanrecruiters.com.mx/nosotros/) y [Blog](https://www.americanrecruiters.com.mx/blog/): oferta y declaraciones corporativas públicas. Sus superlativos, porcentajes de coincidencia y beneficios no demostrados no se trasladan al nuevo copy.
- Informe Corporativo — American Recruiters México: documento interno localizado y leído en la carpeta autorizada del proyecto. Mezcla hechos, inferencias de mercado y recomendaciones; no se reproduce ni se publica el archivo privado. No se usan sus hipótesis sobre precios, tamaño, resultados, expansión ni servicios adicionales como hechos del cliente.
- American Recruiters Web Brand Book: documento interno localizado y leído; orienta legibilidad y composición. La opción 2 aprobada determina el diseño vigente. No se redistribuye el PDF privado.
- Confirmaciones aportadas por el responsable en el encargo: conservar Headhunting y Servicio personalizado; añadir Fast Placement sin definición; México como cobertura general provisional; receptores y tratamiento de CV pendientes.

No se localizaron como documentos independientes consultables en la carpeta Drive y repositorio revisados: Keyword Research 2026 completo, Estudio de Competencia 2026, Auditoría SEO y Migración 2026 e Inventario de URLs de migración. La arquitectura y el encargo sí consignan H01/R01; las palabras de apoyo del mapa son propuestas semánticas, no resultados medidos del estudio ausente. No se verificaron sus cifras originales ni se reconstruyeron con suposiciones. El comparativo del informe corporativo no sustituye al estudio independiente de competencia.

## Instrucciones para el siguiente implementador (Agente D)

1. Obtener aprobación de Sergio para cada Bloque B y resolver las filas de la matriz que condicionen su publicación. No montar las notas como texto visible.
2. Trabajar solo en desarrollo y crear revisión/respaldo antes de sustituir cada placeholder. Conservar el tema, estilos, menús, Home y plantillas globales.
3. Usar Gutenberg y componentes del tema: hero interior, ancho de lectura, tarjetas, CTA y details/summary. Un H1 por página: la plantilla existente ya imprime el título; no añadir un segundo H1 dentro del cuerpo.
4. Asignar el H1 propuesto al título visible de la página o a su hero según la plantilla, conservando el slug. Titles y descripciones van en Yoast, no como párrafos del cuerpo.
5. Mantener las rutas relativas propuestas sobre el dominio de staging. Son páginas estructurales existentes, no páginas con estos textos ya publicados. /solicitud-recibida/ y /cv-recibido/ son rutas previstas en la arquitectura, no verificadas como implementadas.
6. No incorporar formularios funcionales hasta aprobar receptores, privacidad, almacenamiento y validación técnica. Los textos de éxito solo se usan después de una confirmación real del servidor; no al hacer clic en Enviar. No prometer entrega al buzón, entrevista o respuesta individual.
7. Fast Placement queda con su estado provisional; no activar una oferta cerrada, plazo o garantía. Mantener separada la recepción empresarial del flujo de CV.
8. Conservar noindex. Verificar canonical de staging sin trasladarlo a producción, enlaces, H1–H3, ALT real de cada imagen y responsive. La propuesta de ALT no se copia si la fotografía elegida no muestra esa escena.
9. No crear páginas por sinónimos, ciudades o industrias ni migrar artículos con esta autorización. Revisar el inventario de migración cuando se facilite.
10. Validar formularios con un destinatario de pruebas aprobado en una tarea independiente. No usar al director comercial para envíos QA.

## Listo para integrar frente a listo para publicar

Los nueve textos completos pueden prepararse para montaje tras revisión editorial. No deben publicarse automáticamente: metodología requiere ratificación; Contacto y Candidatos tienen dependencias funcionales y legales. Fast Placement y Privacidad siguen bloqueados como contenido definitivo. La falta de estudios completos no impide revisar el copy conservador, pero limita la validación cuantitativa de la estrategia SEO.
