# Cambios

## 0.2.0, 20 de octubre de 2026

- La lista de eventos del admin se abre en «Próximos» y tiene una pestaña «Pasados». Detrás quedan Borradores y Papelera. Los próximos van del más cercano al más lejano; los pasados, del más reciente al más antiguo.
- Un evento que ya ha terminado sigue diciendo a Google que estaba programado, porque se celebró, pero deja de ofrecer entradas en sus datos estructurados.
- El importador ya no saca la hora de fin de la fecha de fin. Solo la toma si viene en su propio campo, `hora_fin`, porque los calendarios suelen rellenarla por defecto.

## 0.1.0, 19 de octubre de 2026

Primera versión.

- Tipo de contenido «Eventos» con categorías jerárquicas y lugares con dirección.
- Fecha de inicio, hora de inicio y de fin opcionales, y eventos de varios días.
- Lugares nuevos desde el propio evento; lista de lugares con aviso de los que no tienen dirección.
- Estado del evento, enlace de venta con opción de abrir en otra pestaña, texto del botón, precio y artista.
- Datos estructurados `MusicEvent` para Google en la página del evento y, si se quiere, en los listados.
- Listado que esconde los eventos terminados, con filtro por categoría y modo histórico.
- Widgets de Elementor «Eventos» y «Ficha del evento», y shortcodes `[caracool_eventos]` y `[caracool_evento]`.
- Ajustes en el menú «Caracool», con el diseño de los plugins de la casa.
- Importación de eventos desde JSON.
- Actualizaciones desde las releases de GitHub.
