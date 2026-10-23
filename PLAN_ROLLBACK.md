# Rollback del tema

Ensayo completo de archivos del tema realizado en clon privado:1.0.4 → ZIP RC2 → ZIP privado1.0.4, todos los hashes iguales al inicio. Contenido editorial y opciones seleccionadas conservados;39/39 rutas y4/4 endpoints PASS. Evidencia: release/qa/rc2/clone-results.json. No se hizo rollback en sitios compartidos.

1. Disparadores: fatal nuevo, rutas/404 incorrectas, contenido o medios perdidos, canonical/H1 duplicado, formulario habilitado o pérdida de noindex.
2. Conservar una copia íntegra del tema anterior fuera del directorio activo y registrar hashes antes de instalar. Usar ZIP o reemplazo atómico completo, no archivos sueltos mezclados.
3. Restaurar el directorio ar-editorial anterior con el mismo slug. Releer caché de temas y verificar versión/hashes. La API Theme_Upgrader procesó ambos ZIP en el ensayo.
4. Comparar template,stylesheet,portada/blog,permalinks,active_plugins y hashes de contenido. Restaurar opciones solo si cambiaron. No restaurar indiscriminadamente BD antigua ni borrar uploads/trabajo concurrente.
5. Repetir Home, interiores, blog/404, Contacto/Candidatos, noindex/canonical, menús y controles inactivos. No enviar formularios.
6. Mantener respaldo íntegro de BD/configuración para recuperación específica si hubiera escritura accidental. La importación inicial del respaldo está probada en SQLite; no se ensayó recuperación nativa completa de servidor/MySQL. Esta distinción sigue vigente.
