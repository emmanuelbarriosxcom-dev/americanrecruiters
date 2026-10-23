# Rollback

Alcance: revertir solo el tema en el entorno que se haya actualizado posteriormente. No se ejecutó reversión sobre WordPress compartido en esta tarea.

- Disparadores: fatal/warning nuevo, error de navegación, pérdida de contenido/imagen, H1/canonical duplicado, formulario habilitado, cambio de noindex o defecto de rutas.
- Mantener el tema anterior como copia completa fuera del directorio activo, junto con su SHA y manifest. No reemplazar primero functions.php dejando plantillas de otra versión.
- Interrumpir la actualización, usar mantenimiento controlado si corresponde y restaurar atómicamente el directorio completo `ar-editorial` anterior. Conservar el mismo slug y opciones de tema. Si se necesita cambiar temporalmente a otro tema, hacerlo únicamente en el clon/ventana autorizados.
- Este RC no modifica BD ni uploads; una reversión de tema no debe restaurar indiscriminadamente una BD antigua ni borrar medios o trabajo posterior de otros agentes. Si ocurrió una escritura accidental, comparar y recuperar solo con plan específico del respaldo.
- Restaurar las opciones registradas solo si cambiaron. No habilitar formularios ni indexación. Invalidar caché únicamente del entorno afectado con autorización.
- Repetir Home, servicios, Blog/artículo/paginación/404, menú móvil, FAQ, Contacto/CV y canonical/robots; conservar logs y evidencia.
- Rollback completo de BD/servidor no ensayado. La prueba local activó el candidato y verificó que no crease páginas; no equivale a demostrar restauración del sitio real. Ensayo del procedimiento con respaldo real es requisito antes de integración compartida.
