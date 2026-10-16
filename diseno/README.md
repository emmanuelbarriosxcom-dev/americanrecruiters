# American Recruiters México — Ejecutivo Editorial · Opción 2

Fuente visual aprobada: kit madre suministrado por el responsable del proyecto. Esta carpeta documenta el sistema, no constituye contenido comercial aprobado ni sustituye la arquitectura V1.1.

## Referencia independiente

`index.html` conserva el prototipo maestro y su logotipo oficial PNG embebido. `css/design-system.css` contiene el sistema original compartido. Las fotografías externas de Unsplash son ilustrativas: no representan equipo, oficinas ni proyectos de la empresa. Los formularios del prototipo son demostraciones; no envían datos.

## Implementación WordPress

Solución principal: tema clásico ligero **American Recruiters — Ejecutivo Editorial**, con Gutenberg para el contenido editable. No depende de Elementor Pro ni de licencias nuevas. Header, footer, navegación y componentes son compartidos. Los plugins previos se conservan, sin actualizaciones ni desactivaciones.

El tema implementa portada provisional, índice y detalle de servicios, corporativa, metodología, Blog, artículo, contacto, candidatos, FAQ, privacidad y 404. Las páginas estructurales son únicamente placeholders de desarrollo. No son la homepage definitiva ni redacción comercial final. Fast Placement permanece pendiente de definición.

En Gutenberg: categoría de patrones **American Recruiters · Ejecutivo Editorial** (introducción, tarjetas, FAQ y CTA). Plantillas asignables desde los ajustes de página. Componentes de referencia en `/sistema-visual/` del staging.

## Reglas

- Paleta original: #675C6C, #4D4653, #1EABA3, #0F8F91, #FFFFFF, #25232A, #F5F4F7; base editorial #26222D y menta #DDF4F3.
- Montserrat 400–900 mediante Google Fonts; no se redistribuyen archivos de fuente.
- Contenedor máximo 1200 px; gutters 36/20/16 px; botones de radio 9 px; foco visible y movimiento reducido.
- Usar `.ar-interior-hero`, `.ar-section`, `.ar-grid-3`, `.ar-card`, `.ar-reading`, `.ar-breadcrumb`, `.ar-cta-band`.
- En texto pequeño sobre blanco, la implementación usa #087577 para legibilidad; mantiene los tokens originales como colores de marca.
- No duplicar el HTML madre en todas las páginas ni crear CSS global paralelo.
- No inventar cobertura local, plazos, certificaciones, garantías ni metodología.
- CTA empresarial `/contacto/`; candidatos `/candidatos/`; los campos demo están deshabilitados y no almacenan datos ni CV.
- Staging con acceso normal de WordPress y noindex, sin protección HTTP adicional, según decisión expresa del responsable. Noindex NO equivale a privacidad o control de acceso.
- Producción, DNS y hosting global quedan fuera de alcance.

## Continuidad y rollback

El respaldo previo completo se conserva en WPvivid y en una descarga privada; nunca se añade al repositorio. Cambiar a Twenty Twenty-Five revierte el tema sin borrar las páginas. Para recuperar también el estado de datos, usar la copia completa de WPvivid previa, exclusivamente en desarrollo, tras aprobación de la restauración. No se ha ejecutado una restauración de prueba.

Antes de lanzamiento: aprobar contenidos, alcance Fast Placement, correos y privacidad; construir formularios reales separados con almacenamiento privado de CV; revisar licencias y versiones de plugins; efectuar QA final y decidir explícitamente la retirada de noindex. Esta fase no habilita indexación ni comunicaciones comerciales.
