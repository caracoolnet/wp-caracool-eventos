# Caracool Eventos

Plugin de WordPress para publicar conciertos y eventos. Cada evento tiene fecha, hora (opcional), lugar y enlace de venta, y lleva los datos estructurados que Google usa en sus resultados de eventos. El listado esconde solo los que ya han pasado y se puede filtrar por categoría.

Es uno de los plugins de la casa de Caracool: se cuelga del menú «Caracool» y se actualiza desde las releases de `caracoolnet/wp-caracool-eventos`.

## Qué tiene un evento

- **Fechas.** Día de inicio, hora de inicio y hora de fin, las dos opcionales. Si no hay hora, no se enseña ninguna. Un evento puede durar varios días.
- **Lugar.** Se elige de la lista o se crea en el momento desde el propio evento. Los lugares se ven y se editan en Eventos → Lugares, con su dirección, código postal, ciudad, provincia, país y web.
- **Categorías.** Giras, tipos de evento o lo que haga falta. Son jerárquicas y sirven para filtrar el listado.
- **Estado.** Programado, últimas entradas, agotado, aplazado, nueva fecha o cancelado. Agotado y cancelado se enseñan en lugar del botón de compra.
- **Venta.** Enlace, texto del botón, si se abre en otra pestaña y precio «desde».
- **Artista.** Por defecto, el de los ajustes. Para un cartel compartido, varios nombres separados por comas.

## Dónde se configura

Caracool → Eventos. Tres pestañas:

- **Ajustes.** Base de la URL de cada evento (`/evento/nombre/` por defecto), artista y tipo de artista para Google, organizador, moneda, texto del botón y si la venta se abre en otra pestaña.
- **Cómo se usa.** Opciones del shortcode.
- **Importar.** Pegar una lista de eventos en JSON. Si un evento ya existe con la misma dirección, se actualiza. La hora de fin solo se importa si viene en el campo `hora_fin`.

Los eventos se crean y editan en el menú **Eventos**, con el editor clásico para que la caja de datos quede a la vista. La lista se abre en **Próximos**; al lado están **Pasados**, **Borradores** y **Papelera**.

## Cómo se pone en una página

Con Elementor hay dos widgets:

- **Eventos**: el listado. Se elige qué categorías salen, si son los próximos o el histórico y cuántos. También si lleva filtro, qué se lee primero (el título o la ciudad), si se enseña el año y el diseño de la fecha:
  - **Clásico**: el día de la semana arriba, el número grande y el mes debajo, al ancho del número.
  - **Calendario**: una hoja con el día de la semana en una franja arriba.
  - **Bloque de color**: un cuadrado relleno.
  - **Contorno**: la caja solo con el borde.
  - **En línea**: «6 NOV» con el día de la semana debajo.

  Cada diseño tiene su sección en la pestaña de estilo, con sus tamaños (por dispositivo) y sus colores. El día de la semana puede ir abreviado o entero.
- **Ficha del evento**: fecha, lugar con enlace al mapa, estado, precio y botón. Para la plantilla de entrada de los eventos en el Maquetador de temas.

Sin Elementor:

```
[caracool_eventos]
[caracool_eventos categoria="gira-ellar" limite="6" filtro="no"]
[caracool_eventos pasados="solo" anio="si" vacio="Aún no hay histórico"]
[caracool_eventos fecha="calendario" semana="completo"]
[caracool_evento]
```

Los botones llevan la clase `elementor-button`, así que toman el estilo de botón del Kit sin tocar nada. El resto hereda la tipografía y el color del contenedor.

Si la página de un evento no está hecha con Elementor, la ficha aparece sola delante del texto.

## Cuándo deja de salir un evento

Cuando termina: a su hora de fin si la tiene y, si no, al acabar su último día, con la zona horaria de la web. Su página sigue publicada.

## Datos para Google

Cada evento lleva un bloque JSON-LD de tipo `MusicEvent` con nombre, fechas con zona horaria, estado, modalidad presencial, lugar con dirección, imagen, descripción, oferta (enlace, disponibilidad, precio y moneda), artista y organizador. Va en la página del evento y, si está activado en los ajustes, también en los listados.

Cuando un evento ya ha terminado, el bloque se queda igual (el estado sigue siendo «programado», porque se celebró) pero sin la oferta de entradas.

Google necesita como mínimo la fecha y el lugar con dirección o ciudad. La caja del evento avisa de lo que falta, y la lista de eventos señala los próximos que no lo cumplen.

El tipo se cambia con el filtro `caracool_eventos_schema_tipo` y el bloque entero con `caracool_eventos_schema`.

## Archivos

```
caracool-eventos.php      constantes, menú, ajustes y actualizador
inc/caracool-menu.php     menú común de la casa (no se edita aquí)
modules/ce-tipos.php      tipo de contenido, categorías, lugares, fechas y consultas
modules/ce-lugares.php    dirección de cada lugar
modules/ce-datos.php      caja «Datos del evento»
modules/ce-schema.php     JSON-LD
modules/ce-listado.php    listado, ficha y shortcodes
modules/ce-widget.php     widgets de Elementor
modules/ce-importar.php   importación desde JSON
assets/ce-front.css       estilos del listado y la ficha
assets/ce-front.js        ancho del mes y filtro por categoría
```

Datos de cada evento (post meta): `_ce_inicio`, `_ce_hora_inicio`, `_ce_fin`, `_ce_hora_fin`, `_ce_estado`, `_ce_venta_url`, `_ce_venta_blank`, `_ce_boton`, `_ce_precio`, `_ce_artista`. Las consultas usan dos marcas de tiempo calculadas, `_ce_ts_inicio` y `_ce_ts_fin`. Todos están en la API REST.
