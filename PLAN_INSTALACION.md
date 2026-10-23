# Instalación controlada

Ensayo realizado únicamente en clon privado del respaldo16/10: Theme_Upgrader instaló el ZIP final RC2 y permitió rollback exacto del tema. No se instaló en WordPress compartido ni producción.

1. Resolver INT-04 y confirmar el delta de BD/uploads/configuración del respaldo antes de autorizar integración. Congelar cambios concurrentes y registrar estado activo.
2. Obtener respaldo recuperable actualizado del tema, BD, uploads y configuración. Guardar template/stylesheet, portada/blog, permalinks, noindex y configuración Yoast. No publicar ni mover borradores.
3. En clon privado nativo equivalente, bloquear correo, cron, HTTP externo no requerido y formularios; mantener noindex. No ejecutar credenciales productivas en otro servidor.
4. Verificar SHA256 del ZIP con MANIFEST.md. Raíz ar-editorial/ y21 archivos. Comparar frente al tema activo, incluidos hotfixes posteriores. El paquete no incluye contenido/medios/plugins.
5. Instalar ZIP mediante Theme_Upgrader o subida de tema controlada. El ensayo de esta entrega usó la API, no la interfaz dashboard. Conservar slug ar-editorial y no definir AR_ENABLE_STAGING_BOOTSTRAP.
6. Mantener BD/uploads y configuración SEO existentes. No actualizar plugins, migrar artículos, cambiar destinatarios, publicar borradores ni habilitar formularios.
7. Repetir rutas, 404 real, endpoints bloqueados405/403, canonical/title/robots, medios, navegación y vista móvil. Conservar logs. Repetir rollback de tema y comparar hashes/opciones/contenido como en clone-results.json.
8. Solo después de revisión técnica y autorización explícita, proponer ventana para desarrollo compartido. Este RC no autoriza producción ni indexación. No purgar cachés globales sin autorización.
