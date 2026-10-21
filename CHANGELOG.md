# Cambios

## 0.4.0, 21 de octubre de 2026

- La fecha del listado tiene cinco diseños: Clásico (el de siempre), Calendario, Bloque de color, Contorno y En línea. Se elige en el widget, en «Diseño de la fecha», o con `fecha="calendario"` en el shortcode.
- Cada diseño tiene su sección en la pestaña Estilo del widget, con sus tamaños y colores. Los tamaños se pueden cambiar por dispositivo.
- El día de la semana puede ir abreviado («vie») o entero («viernes»). El Calendario lo pone entero si no se dice otra cosa. En el shortcode es `semana="corto"` o `semana="completo"`.
- En el Clásico, el tamaño del mes se puede poner a mano. Mientras no se haga, sigue tomando el ancho del número.
- En los diseños con caja, un número que no cabe (como «10–12») se reduce hasta caber.
- El widget recién puesto sigue saliendo en Clásico, así que las webs que ya lo usan no cambian al actualizar.

## 0.3.0, 20 de octubre de 2026

- Nueva fecha en el listado: el día de la semana pequeño arriba, el número grande y el mes debajo, al mismo ancho que el número. El ancho lo ajusta `ce-front.js`; con un solo dígito el mes toma el ancho de dos, y un «10–12» se reduce hasta caber en su columna.
- El año ya no se ve por defecto. El widget tiene el interruptor «Mostrar el año» y el shortcode, `anio="si"`. Los lectores de pantalla siguen oyendo la fecha completa.
- En la pestaña de estilo del widget, la tipografía «Mes y año» pasa a ser tres controles: día de la semana, mes y año.

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
