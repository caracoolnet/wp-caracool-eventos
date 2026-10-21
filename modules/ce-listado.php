<?php
/**
 * Caracool Eventos · listado.
 *
 *   [caracool_eventos]  listado con filtro por categoría (también como widget
 *                       de Elementor, «Eventos»). anio="si" enseña el año.
 *   La ficha de cada evento ([caracool_evento]) está en ce-ficha.php.
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
			'anio'      => 'no',
			'fecha'     => 'clasico',
			'semana'    => '',
		),
		(array) $a,
		'caracool_eventos'
	);
	$si = function ( $v ) {
		return in_array( strtolower( (string) $v ), array( 'si', 'sí', 'yes', '1', 'true', 'on' ), true );
	};
	$fecha  = in_array( $a['fecha'], caracool_eventos_disenos_fecha(), true ) ? $a['fecha'] : 'clasico';
	$semana = in_array( $a['semana'], array( 'corto', 'completo' ), true ) ? $a['semana'] : ( 'calendario' === $fecha ? 'completo' : 'corto' );
	return array(
		'fecha'     => $fecha,
		'semana'    => $semana,
		'categoria' => (string) $a['categoria'],
		'filtro'    => $si( $a['filtro'] ),
		'limite'    => (int) $a['limite'],
		'pasados'   => in_array( $a['pasados'], array( 'no', 'solo', 'todos' ), true ) ? $a['pasados'] : 'no',
		'destacar'  => 'ciudad' === $a['destacar'] ? 'ciudad' : 'titulo',
		'vacio'     => (string) $a['vacio'],
		'anio'      => $si( $a['anio'] ),
		'datos'     => '' === $a['datos'] ? (bool) Caracool_Eventos::ajuste( 'schema_lista' ) : $si( $a['datos'] ),
	);
}

/**
 * Diseños del bloque de fecha. Todos usan el mismo marcado; cambia la clase
 * ce-f-<diseño> de la raíz del listado y, con ella, el CSS.
 */
function caracool_eventos_disenos_fecha() {
	return array( 'clasico', 'calendario', 'bloque', 'contorno', 'linea' );
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

/**
 * El bloque de fecha del listado: día de la semana, número y mes. Cómo se ve
 * depende del diseño elegido (ce-front.css); en el clásico, el mes va al ancho
 * del número (lo ajusta assets/ce-front.js). El año solo se ve si se pide;
 * para lectores de pantalla va siempre la fecha completa.
 */
function caracool_eventos_bloque_fecha( $d, $anio = false, $semana = 'corto' ) {
	$a   = $d['dt_inicio'];
	$ts  = $a->getTimestamp();
	$dia = wp_date( 'j', $ts );
	$mes = rtrim( wp_date( 'M', $ts ), '.' );
	$b   = $d['fin'] ? caracool_eventos_dt( $d['fin'] ) : null;
	$txt = wp_date( 'l j \d\e F \d\e Y', $ts );
	if ( $b ) {
		$tb = $b->getTimestamp();
		if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) {
			$dia .= '–' . wp_date( 'j', $tb );
		} else {
			$mes .= '–' . rtrim( wp_date( 'M', $tb ), '.' );
			$dia .= '–' . wp_date( 'j', $tb );
		}
		$txt .= ' al ' . wp_date( 'l j \d\e F \d\e Y', $tb );
	}
	$sem = 'completo' === $semana ? wp_date( 'l', $ts ) : rtrim( wp_date( 'D', $ts ), '.' );
	return '<time class="ce-fecha" datetime="' . esc_attr( $a->format( 'Y-m-d' ) ) . '">'
		. '<span class="ce-fecha-caja" aria-hidden="true">'
		. '<span class="ce-semana">' . esc_html( $sem ) . '</span>'
		. '<span class="ce-dia">' . esc_html( $dia ) . '</span>'
		. '<span class="ce-mes">' . esc_html( $mes ) . '</span>'
		. ( $anio ? '<span class="ce-anio">' . esc_html( $a->format( 'Y' ) ) . '</span>' : '' )
		. '</span>'
		. '<span class="ce-sr">' . esc_html( $txt ) . '</span>'
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
	// El script ajusta el ancho del mes y lleva el filtro por categoría.
	wp_enqueue_script( 'caracool-eventos' );
	$uid  = 'ce-' . wp_unique_id();
	$html = '<div class="ce-listado ce-f-' . esc_attr( $o['fecha'] ) . ' ce-destaca-' . esc_attr( $o['destacar'] ) . ( 'solo' === $o['pasados'] ? ' ce-pasados' : '' ) . '" id="' . esc_attr( $uid ) . '" data-ce-listado>';

	if ( $o['filtro'] && count( $cats ) > 1 ) {
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
			$html .= caracool_eventos_bloque_fecha( $d, $o['anio'], $o['semana'] );
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

// ── Widget de Elementor ────────────────────────────────────────────────

add_action(
	'elementor/widgets/register',
	function ( $gestor ) {
		require_once CARACOOL_EVENTOS_DIR . 'modules/ce-widget.php';
		$gestor->register( new Caracool_Eventos_Widget() );
		$gestor->register( new Caracool_Evento_Ficha_Widget() );
	}
);
