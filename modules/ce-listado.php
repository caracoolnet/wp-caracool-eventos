<?php
/**
 * Caracool Eventos · listado y ficha.
 *
 *   [caracool_eventos]  listado con filtro por categoría (también como widget
 *                       de Elementor, «Eventos»)
 *   [caracool_evento]   ficha del evento actual: fechas, lugar, estado, precio
 *                       y botón de compra. Para plantillas de Elementor Pro.
 *
 * Los botones llevan la clase `elementor-button`, así que toman el estilo de
 * botón del Kit de la web sin tocar nada. El resto del diseño hereda la
 * tipografía y el color del contenedor; los acentos salen de los colores
 * globales de Elementor si existen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_register_style( 'caracool-eventos', CARACOOL_EVENTOS_URL . 'assets/ce-front.css', array(), caracool_eventos_ver( 'ce-front.css' ) );
		wp_register_script( 'caracool-eventos', CARACOOL_EVENTOS_URL . 'assets/ce-front.js', array(), caracool_eventos_ver( 'ce-front.js' ), true );
		if ( is_singular( 'cc_evento' ) ) {
			wp_enqueue_style( 'caracool-eventos' );
		}
	}
);

/** Normaliza las opciones del listado venga de donde venga. */
function caracool_eventos_opciones( $a ) {
	$a = shortcode_atts(
		array(
			'categoria' => '',
			'filtro'    => 'si',
			'limite'    => '',
			'pasados'   => 'no',
			'destacar'  => 'titulo',
			'vacio'     => 'No hay fechas a la vista. Vuelve pronto.',
			'datos'     => '',
		),
		(array) $a,
		'caracool_eventos'
	);
	$si = function ( $v ) {
		return in_array( strtolower( (string) $v ), array( 'si', 'sí', 'yes', '1', 'true', 'on' ), true );
	};
	return array(
		'categoria' => (string) $a['categoria'],
		'filtro'    => $si( $a['filtro'] ),
		'limite'    => (int) $a['limite'],
		'pasados'   => in_array( $a['pasados'], array( 'no', 'solo', 'todos' ), true ) ? $a['pasados'] : 'no',
		'destacar'  => 'ciudad' === $a['destacar'] ? 'ciudad' : 'titulo',
		'vacio'     => (string) $a['vacio'],
		'datos'     => '' === $a['datos'] ? (bool) Caracool_Eventos::ajuste( 'schema_lista' ) : $si( $a['datos'] ),
	);
}

/** Enlace de compra o de más información, con el estilo de botón del Kit. */
function caracool_eventos_boton( $d, $clase = '' ) {
	if ( in_array( $d['estado'], array( 'agotado', 'cancelado' ), true ) ) {
		return '<span class="ce-estado ce-estado-' . esc_attr( $d['estado'] ) . '">' . esc_html( $d['estado_txt'] ) . '</span>';
	}
	if ( ! $d['venta_url'] ) {
		return '';
	}
	$attr = $d['venta_blank'] ? ' target="_blank" rel="noopener"' : '';
	$aria = esc_attr( $d['boton'] . ': ' . wp_strip_all_tags( $d['titulo'] ) . ( $d['venta_blank'] ? ' (se abre en otra pestaña)' : '' ) );
	return '<a class="ce-boton elementor-button ' . esc_attr( $clase ) . '" href="' . esc_url( $d['venta_url'] ) . '"' . $attr . ' aria-label="' . $aria . '"><span class="elementor-button-text">' . esc_html( $d['boton'] ) . '</span></a>';
}

/** El bloque de fecha grande del listado: día, mes y año. */
function caracool_eventos_bloque_fecha( $d ) {
	$a   = $d['dt_inicio'];
	$ts  = $a->getTimestamp();
	$dia = wp_date( 'j', $ts );
	$mes = wp_date( 'M', $ts );
	$b   = $d['fin'] ? caracool_eventos_dt( $d['fin'] ) : null;
	if ( $b ) {
		if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) {
			$dia .= '–' . wp_date( 'j', $b->getTimestamp() );
		} else {
			$mes .= '–' . wp_date( 'M', $b->getTimestamp() );
			$dia .= '–' . wp_date( 'j', $b->getTimestamp() );
		}
	}
	$sem = wp_date( 'l', $ts );
	return '<time class="ce-fecha" datetime="' . esc_attr( $a->format( 'Y-m-d' ) ) . '">'
		. '<span class="ce-dia">' . esc_html( $dia ) . '</span>'
		. '<span class="ce-mes">' . esc_html( rtrim( $mes, '.' ) ) . '</span>'
		. '<span class="ce-anio">' . esc_html( $a->format( 'Y' ) ) . '</span>'
		. '<span class="screen-reader-text">' . esc_html( $sem ) . '</span>'
		. '</time>';
}

/** El listado completo como HTML. */
function caracool_eventos_render( $a = array() ) {
	$o      = caracool_eventos_opciones( $a );
	$posts  = caracool_eventos_consulta(
		array(
			'categoria' => $o['categoria'],
			'limite'    => $o['limite'],
			'pasados'   => $o['pasados'],
		)
	);
	$items  = array();
	$cats   = array();
	foreach ( $posts as $p ) {
		$d = caracool_eventos_datos( $p );
		if ( ! $d || empty( $d['dt_inicio'] ) ) {
			continue;
		}
		foreach ( $d['categorias'] as $c ) {
			$cats[ $c->slug ] = $c->name;
		}
		$items[] = $d;
	}

	wp_enqueue_style( 'caracool-eventos' );
	$uid  = 'ce-' . wp_unique_id();
	$html = '<div class="ce-listado ce-destaca-' . esc_attr( $o['destacar'] ) . ( 'solo' === $o['pasados'] ? ' ce-pasados' : '' ) . '" id="' . esc_attr( $uid ) . '" data-ce-listado>';

	if ( $o['filtro'] && count( $cats ) > 1 ) {
		wp_enqueue_script( 'caracool-eventos' );
		asort( $cats );
		$html .= '<div class="ce-filtro" role="group" aria-label="Filtrar por categoría">';
		$html .= '<button type="button" class="ce-filtro-btn" aria-pressed="true" data-ce-cat="">Todos</button>';
		foreach ( $cats as $slug => $nombre ) {
			$html .= '<button type="button" class="ce-filtro-btn" aria-pressed="false" data-ce-cat="' . esc_attr( $slug ) . '">' . esc_html( $nombre ) . '</button>';
		}
		$html .= '</div>';
	}

	$html .= '<p class="ce-vacio"' . ( $items ? ' hidden' : '' ) . '>' . esc_html( $o['vacio'] ) . '</p>';

	if ( $items ) {
		$html  .= '<ul class="ce-lista">';
		$schema = '';
		foreach ( $items as $d ) {
			$slugs  = implode( ' ', wp_list_pluck( $d['categorias'], 'slug' ) );
			$l      = $d['lugar'];
			$ciudad = $l && $l['ciudad'] ? $l['ciudad'] : '';
			$sala   = $l ? $l['nombre'] : '';
			$horas  = caracool_eventos_texto_horas( $d );

			if ( 'ciudad' === $o['destacar'] && $ciudad ) {
				$principal = $ciudad;
				$segunda   = $sala;
			} else {
				$principal = $d['titulo'];
				$segunda   = trim( $sala . ( $sala && $ciudad ? ', ' : '' ) . $ciudad );
			}

			$html .= '<li class="ce-evento ce-estado-' . esc_attr( $d['estado'] ) . '" data-ce-cats="' . esc_attr( $slugs ) . '">';
			$html .= caracool_eventos_bloque_fecha( $d );
			$html .= '<div class="ce-info">';
			$html .= '<h3 class="ce-titulo"><a href="' . esc_url( $d['url'] ) . '">' . esc_html( wp_strip_all_tags( $principal ) ) . '</a></h3>';
			$meta  = array();
			if ( $segunda ) {
				$meta[] = '<span class="ce-lugar">' . esc_html( $segunda ) . '</span>';
			}
			if ( $horas ) {
				$meta[] = '<span class="ce-hora">' . esc_html( $horas ) . '</span>';
			}
			if ( $meta ) {
				$html .= '<p class="ce-meta">' . implode( '<span class="ce-sep" aria-hidden="true"></span>', $meta ) . '</p>';
			}
			if ( ! in_array( $d['estado'], array( 'programado', 'agotado', 'cancelado' ), true ) ) {
				$html .= '<p class="ce-aviso-estado">' . esc_html( $d['estado_txt'] ) . '</p>';
			}
			$html .= '</div>';
			// Un evento que ya ha pasado no vende nada: ni botón ni estado.
			$paso  = (int) get_post_meta( $d['id'], '_ce_ts_fin', true ) < time();
			$html .= '<div class="ce-accion">' . ( $paso ? '' : caracool_eventos_boton( $d ) ) . '</div>';
			$html .= '</li>';

			if ( $o['datos'] ) {
				$schema .= caracool_eventos_schema_html( $d );
			}
		}
		$html .= '</ul>' . $schema;
	}

	return $html . '</div>';
}

add_shortcode(
	'caracool_eventos',
	function ( $a ) {
		return caracool_eventos_render( $a );
	}
);

/** Ficha del evento: para la página de cada evento. */
function caracool_eventos_ficha( $id = 0 ) {
	$d = caracool_eventos_datos( $id ? $id : get_the_ID() );
	if ( ! $d ) {
		return '';
	}
	wp_enqueue_style( 'caracool-eventos' );
	$filas = array();
	if ( $d['dt_inicio'] ) {
		$t = caracool_eventos_texto_fechas( $d );
		$h = caracool_eventos_texto_horas( $d );
		$filas[] = array( 'Cuándo', ucfirst( wp_date( 'l', $d['dt_inicio']->getTimestamp() ) ) . ', ' . $t . ( $h ? '. ' . $h : '' ) );
	}
	if ( $d['lugar'] ) {
		$l     = $d['lugar'];
		$dir   = implode( ', ', array_filter( array( $l['direccion'], trim( $l['cp'] . ' ' . $l['ciudad'] ) ) ) );
		$mapa  = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $l['nombre'] . ', ' . $dir );
		$valor = '<strong>' . esc_html( $l['nombre'] ) . '</strong>' . ( $dir ? '<br>' . esc_html( $dir ) : '' ) . '<br><a href="' . esc_url( $mapa ) . '" target="_blank" rel="noopener">Ver en el mapa</a>';
		$filas[] = array( 'Dónde', $valor, true );
	}
	if ( 'programado' !== $d['estado'] ) {
		$filas[] = array( 'Estado', $d['estado_txt'] );
	}
	if ( '' !== (string) $d['precio'] && ! in_array( $d['estado'], array( 'agotado', 'cancelado' ), true ) ) {
		$precio  = number_format_i18n( (float) $d['precio'], floor( (float) $d['precio'] ) == (float) $d['precio'] ? 0 : 2 ); // phpcs:ignore Universal.Operators.StrictComparisons
		$filas[] = array( 'Precio', 'Desde ' . $precio . ' ' . ( 'EUR' === $d['moneda'] ? '€' : $d['moneda'] ) );
	}
	$html = '<div class="ce-ficha ce-estado-' . esc_attr( $d['estado'] ) . '"><dl>';
	foreach ( $filas as $f ) {
		$html .= '<div class="ce-ficha-fila"><dt>' . esc_html( $f[0] ) . '</dt><dd>' . ( empty( $f[2] ) ? esc_html( $f[1] ) : $f[1] ) . '</dd></div>';
	}
	$html .= '</dl>';
	$b     = caracool_eventos_boton( $d );
	if ( $b ) {
		$html .= '<div class="ce-ficha-accion">' . $b . '</div>';
	}
	return $html . '</div>';
}

add_shortcode(
	'caracool_evento',
	function ( $a ) {
		$a = shortcode_atts( array( 'id' => 0 ), $a, 'caracool_evento' );
		return caracool_eventos_ficha( (int) $a['id'] );
	}
);

/*
 * En la página de un evento que no se ha montado con Elementor ni lleva ya la
 * ficha, la ficha va delante del texto. Con una plantilla de Elementor Pro se
 * coloca donde se quiera con el shortcode [caracool_evento].
 */
add_filter(
	'the_content',
	function ( $c ) {
		if ( ! is_singular( 'cc_evento' ) || ! in_the_loop() || ! is_main_query() ) {
			return $c;
		}
		if ( has_shortcode( $c, 'caracool_evento' ) || false !== strpos( $c, 'ce-ficha' ) ) {
			return $c;
		}
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents ) {
			$doc = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
			if ( $doc && $doc->is_built_with_elementor() ) {
				return $c;
			}
		}
		return caracool_eventos_ficha() . $c;
	},
	8
);

// ── Widget de Elementor ────────────────────────────────────────────────

add_action(
	'elementor/widgets/register',
	function ( $gestor ) {
		require_once CARACOOL_EVENTOS_DIR . 'modules/ce-widget.php';
		$gestor->register( new Caracool_Eventos_Widget() );
		$gestor->register( new Caracool_Evento_Ficha_Widget() );
	}
);
