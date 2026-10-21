<?php
/**
 * Caracool Eventos · ficha del evento.
 *
 *   [caracool_evento]   ficha del evento actual (también como widget de
 *                       Elementor, «Ficha del evento»).
 *
 * Tres diseños: lista (fila por dato), columnas (texto a la izquierda y
 * tarjeta de compra a la derecha) y entrada (banda con forma de entrada de
 * concierto). Los añadidos (puertas, cuenta atrás, calendario, compartir, mapa
 * y más fechas) van apagados por defecto: la ficha de siempre no cambia.
 *
 * La cuenta atrás, «copiar enlace», el menú de compartir del móvil y el mapa
 * los lleva assets/ce-front.js. El mapa de Google solo se carga si la persona
 * lo pide, para no poner cookies de Google al abrir la página.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Diseños de la ficha. */
function caracool_eventos_disenos_ficha() {
	return array( 'lista', 'columnas', 'entrada' );
}

/** Normaliza las opciones de la ficha venga de donde venga. */
function caracool_eventos_opciones_ficha( $a ) {
	$a  = shortcode_atts(
		array(
			'id'          => 0,
			'diseno'      => 'lista',
			'iconos'      => 'no',
			'puertas'     => 'no',
			'cuenta'      => 'no',
			'calendario'  => 'no',
			'compartir'   => 'no',
			'mapa'        => 'no',
			'mas'         => 'no',
			'mas_limite'  => 3,
			'categoria'   => 'no',
			't_cuando'    => 'Cuándo',
			't_donde'     => 'Dónde',
			't_estado'    => 'Estado',
			't_precio'    => 'Precio',
			't_puertas'   => 'Puertas',
			't_concierto' => 'Concierto',
			't_mas'       => 'Más fechas de la gira',
			't_mapa'      => 'Ver mapa',
		),
		(array) $a,
		'caracool_evento'
	);
	$si = function ( $v ) {
		return in_array( strtolower( (string) $v ), array( 'si', 'sí', 'yes', '1', 'true', 'on' ), true );
	};
	$o  = array(
		'id'         => (int) $a['id'],
		'diseno'     => in_array( $a['diseno'], caracool_eventos_disenos_ficha(), true ) ? $a['diseno'] : 'lista',
		'mas_limite' => max( 1, (int) $a['mas_limite'] ),
	);
	foreach ( array( 'iconos', 'puertas', 'cuenta', 'calendario', 'compartir', 'mapa', 'mas', 'categoria' ) as $k ) {
		$o[ $k ] = $si( $a[ $k ] );
	}
	foreach ( $a as $k => $v ) {
		if ( 0 === strpos( $k, 't_' ) ) {
			$o[ $k ] = (string) $v;
		}
	}
	return $o;
}

/** Iconos de línea, del color del texto. */
function caracool_eventos_icono( $nombre ) {
	$p = array(
		'cuando'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'donde'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'precio'    => '<path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6z"/>',
		'estado'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/>',
		'compartir' => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/>',
		'enlace'    => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'whatsapp'  => '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 18.8z"/><path d="M9 9.5c.3 2 2.5 4.2 4.5 4.5l1-1.2 1.8.8-.4 1.6c-3.4.4-7.6-3.8-7.2-7.2l1.6-.4.8 1.8z"/>',
		'mapa'      => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
	);
	if ( ! isset( $p[ $nombre ] ) ) {
		return '';
	}
	return '<svg class="ce-ico" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $nombre ] . '</svg>';
}

/** «Desde 22 €», o vacío si no toca enseñar precio. */
function caracool_eventos_texto_precio( $d ) {
	if ( '' === (string) $d['precio'] || in_array( $d['estado'], array( 'agotado', 'cancelado' ), true ) ) {
		return '';
	}
	$n = (float) $d['precio'];
	$t = number_format_i18n( $n, floor( $n ) == $n ? 0 : 2 ); // phpcs:ignore Universal.Operators.StrictComparisons
	return 'Desde ' . $t . ' ' . ( 'EUR' === $d['moneda'] ? '€' : $d['moneda'] );
}

/** Dirección en una línea: «Calle, 4, 01012 Ciudad». */
function caracool_eventos_texto_direccion( $l ) {
	if ( ! $l ) {
		return '';
	}
	return implode( ', ', array_filter( array( $l['direccion'], trim( $l['cp'] . ' ' . $l['ciudad'] ) ) ) );
}

/** Enlace a Google Maps con el lugar. */
function caracool_eventos_url_mapa( $l ) {
	$dir = caracool_eventos_texto_direccion( $l );
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $l['nombre'] . ( $dir ? ', ' . $dir : '' ) );
}

/**
 * Horas para la ficha: «22:00», «21:00 – 22:30» o, con puertas,
 * «Puertas 21:00 · Concierto 22:00».
 */
function caracool_eventos_texto_horas_ficha( $d, $o ) {
	$h = caracool_eventos_texto_horas( $d );
	if ( $o['puertas'] && '' !== (string) $d['hora_puertas'] && $h ) {
		return $o['t_puertas'] . ' ' . $d['hora_puertas'] . ' · ' . $o['t_concierto'] . ' ' . $h;
	}
	return $h;
}

/**
 * Cuenta atrás. El texto del servidor vale para quien no tenga JavaScript;
 * ce-front.js lo rehace en el navegador, así una página guardada en caché no
 * dice «faltan 3 días» cuando ya es el día.
 */
function caracool_eventos_cuenta( $d ) {
	if ( empty( $d['dt_inicio'] ) || in_array( $d['estado'], array( 'cancelado', 'aplazado' ), true ) ) {
		return '';
	}
	$fin = (int) get_post_meta( $d['id'], '_ce_ts_fin', true );
	if ( $fin && $fin < time() ) {
		return '';
	}
	$hoy  = new DateTime( 'today', wp_timezone() );
	$dia  = new DateTime( $d['dt_inicio']->format( 'Y-m-d' ), wp_timezone() );
	$n    = (int) $hoy->diff( $dia )->format( '%r%a' );
	$txt  = $n <= 0 ? 'Es hoy' : ( 1 === $n ? 'Es mañana' : 'Faltan ' . $n . ' días' );
	return '<span class="ce-cuenta" data-ce-dia="' . esc_attr( $dia->format( 'Y-m-d' ) ) . '">' . esc_html( $txt ) . '</span>';
}

// ── Añadir al calendario ────────────────────────────────────────────────

/** Inicio y fin en UTC para los calendarios. Sin hora, día entero. */
function caracool_eventos_horas_calendario( $d ) {
	$a = $d['dt_inicio'];
	if ( '' === (string) $d['hora_inicio'] ) {
		$ultimo = $d['fin'] ? caracool_eventos_dt( $d['fin'] ) : $a;
		$b      = ( clone $ultimo )->modify( '+1 day' );
		return array( $a->format( 'Ymd' ), $b->format( 'Ymd' ), true );
	}
	$b = ! empty( $d['dt_fin'] ) && '' !== (string) $d['hora_fin'] ? $d['dt_fin'] : ( clone $a )->modify( '+2 hours' );
	$u = new DateTimeZone( 'UTC' );
	return array( ( clone $a )->setTimezone( $u )->format( 'Ymd\THis\Z' ), ( clone $b )->setTimezone( $u )->format( 'Ymd\THis\Z' ), false );
}

function caracool_eventos_url_google_calendar( $d ) {
	list( $a, $b ) = caracool_eventos_horas_calendario( $d );
	$l             = $d['lugar'];
	return add_query_arg(
		array(
			'action'   => 'TEMPLATE',
			'text'     => rawurlencode( wp_strip_all_tags( $d['titulo'] ) ),
			'dates'    => $a . '/' . $b,
			'location' => rawurlencode( $l ? $l['nombre'] . ( caracool_eventos_texto_direccion( $l ) ? ', ' . caracool_eventos_texto_direccion( $l ) : '' ) : '' ),
			'details'  => rawurlencode( $d['url'] ),
		),
		'https://calendar.google.com/calendar/render'
	);
}

function caracool_eventos_url_ics( $d ) {
	return add_query_arg( 'ce_ics', $d['id'], home_url( '/' ) );
}

// El archivo .ics para Apple Calendar y Outlook: ?ce_ics=ID.
add_action(
	'template_redirect',
	function () {
		if ( empty( $_GET['ce_ics'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$d = caracool_eventos_datos( absint( $_GET['ce_ics'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $d || 'publish' !== get_post_status( $d['id'] ) || empty( $d['dt_inicio'] ) ) {
			return;
		}
		list( $a, $b, $dia ) = caracool_eventos_horas_calendario( $d );
		$esc                 = function ( $t ) {
			return str_replace( array( '\\', ';', ',', "\r\n", "\n" ), array( '\\\\', '\;', '\,', '\n', '\n' ), wp_strip_all_tags( (string) $t ) );
		};
		$l    = $d['lugar'];
		$lin  = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Caracool//Caracool Eventos//ES',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:ce-' . $d['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			( $dia ? 'DTSTART;VALUE=DATE:' : 'DTSTART:' ) . $a,
			( $dia ? 'DTEND;VALUE=DATE:' : 'DTEND:' ) . $b,
			'SUMMARY:' . $esc( $d['titulo'] ),
			'URL:' . $esc( $d['url'] ),
			'DESCRIPTION:' . $esc( $d['url'] ),
		);
		if ( $l ) {
			$lin[] = 'LOCATION:' . $esc( $l['nombre'] . ( caracool_eventos_texto_direccion( $l ) ? ', ' . caracool_eventos_texto_direccion( $l ) : '' ) );
		}
		if ( 'cancelado' === $d['estado'] ) {
			$lin[] = 'STATUS:CANCELLED';
		}
		$lin[] = 'END:VEVENT';
		$lin[] = 'END:VCALENDAR';
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( get_post_field( 'post_name', $d['id'] ) ) . '.ics"' );
		echo implode( "\r\n", $lin ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
);

// ── Piezas de la ficha ──────────────────────────────────────────────────

/** La hoja de calendario de los diseños columnas y entrada. */
function caracool_eventos_hoja( $d ) {
	return '<div class="ce-ficha-hoja ce-f-calendario">' . caracool_eventos_bloque_fecha( $d, false, 'completo' ) . '</div>';
}

/** Enlaces de calendario y compartir. */
function caracool_eventos_acciones_ficha( $d, $o ) {
	$a   = array();
	$ico = function ( $n ) use ( $o ) {
		return $o['iconos'] ? caracool_eventos_icono( $n ) : '';
	};
	$paso = (int) get_post_meta( $d['id'], '_ce_ts_fin', true ) < time();
	if ( $o['calendario'] && ! $paso && 'cancelado' !== $d['estado'] ) {
		$a[] = '<a class="ce-accion-enlace" href="' . esc_url( caracool_eventos_url_google_calendar( $d ) ) . '" target="_blank" rel="noopener">' . $ico( 'calendario' ) . '<span>Google Calendar</span></a>';
		$a[] = '<a class="ce-accion-enlace" href="' . esc_url( caracool_eventos_url_ics( $d ) ) . '" download>' . $ico( 'calendario' ) . '<span>Apple u Outlook (.ics)</span></a>';
	}
	if ( $o['compartir'] ) {
		$texto = wp_strip_all_tags( $d['titulo'] ) . ' ' . $d['url'];
		$a[]   = '<button type="button" class="ce-accion-enlace ce-compartir-nativo" data-ce-compartir="' . esc_attr( $d['url'] ) . '" data-ce-titulo="' . esc_attr( wp_strip_all_tags( $d['titulo'] ) ) . '" hidden>' . $ico( 'compartir' ) . '<span>Compartir</span></button>';
		$a[]   = '<a class="ce-accion-enlace" href="' . esc_url( 'https://wa.me/?text=' . rawurlencode( $texto ) ) . '" target="_blank" rel="noopener">' . $ico( 'whatsapp' ) . '<span>WhatsApp</span></a>';
		$a[]   = '<button type="button" class="ce-accion-enlace" data-ce-copiar="' . esc_attr( $d['url'] ) . '">' . $ico( 'enlace' ) . '<span>Copiar enlace</span></button>';
	}
	return $a ? '<div class="ce-ficha-acciones">' . implode( '', $a ) . '</div>' : '';
}

/** Mapa que se carga al pulsar. */
function caracool_eventos_mapa_ficha( $d, $o ) {
	if ( ! $o['mapa'] || ! $d['lugar'] ) {
		return '';
	}
	$l   = $d['lugar'];
	$dir = caracool_eventos_texto_direccion( $l );
	$src = 'https://www.google.com/maps?q=' . rawurlencode( $l['nombre'] . ( $dir ? ', ' . $dir : '' ) ) . '&output=embed';
	return '<div class="ce-mapa" data-ce-mapa="' . esc_url( $src ) . '">'
		. '<button type="button" class="ce-mapa-btn">' . ( $o['iconos'] ? caracool_eventos_icono( 'mapa' ) : '' ) . '<span>' . esc_html( $o['t_mapa'] ) . '</span></button>'
		. '<span class="ce-mapa-nota">Al abrirlo se carga Google Maps.</span>'
		. '</div>';
}

/** Próximas fechas de la misma categoría (o de todas si no tiene). */
function caracool_eventos_mas_fechas( $d, $o ) {
	if ( ! $o['mas'] ) {
		return '';
	}
	$cat   = $d['categorias'] ? $d['categorias'][0]->slug : '';
	$posts = caracool_eventos_consulta( array( 'categoria' => $cat, 'limite' => $o['mas_limite'] + 1 ) );
	$items = '';
	$n     = 0;
	foreach ( $posts as $p ) {
		if ( (int) $p->ID === (int) $d['id'] || $n >= $o['mas_limite'] ) {
			continue;
		}
		$e = caracool_eventos_datos( $p );
		if ( ! $e || empty( $e['dt_inicio'] ) ) {
			continue;
		}
		$ts     = $e['dt_inicio']->getTimestamp();
		$l      = $e['lugar'];
		$donde  = $l ? trim( ( $l['ciudad'] ? $l['ciudad'] . ' · ' : '' ) . $l['nombre'] ) : wp_strip_all_tags( $e['titulo'] );
		$items .= '<li class="ce-mas-item"><time class="ce-mas-fecha" datetime="' . esc_attr( $e['dt_inicio']->format( 'Y-m-d' ) ) . '">' . esc_html( wp_date( 'j', $ts ) . ' ' . rtrim( wp_date( 'M', $ts ), '.' ) ) . '</time>'
			. '<span class="ce-mas-donde">' . esc_html( $donde ) . '</span>'
			. '<a class="ce-mas-ver" href="' . esc_url( $e['url'] ) . '" aria-label="' . esc_attr( 'Ver ' . wp_strip_all_tags( $e['titulo'] ) ) . '">Ver</a></li>';
		++$n;
	}
	if ( ! $items ) {
		return '';
	}
	return '<div class="ce-mas"><h3 class="ce-mas-titulo">' . esc_html( $o['t_mas'] ) . '</h3><ul class="ce-mas-lista">' . $items . '</ul></div>';
}

/** Etiqueta de categoría y de estado. */
function caracool_eventos_chips_ficha( $d, $o ) {
	$c = '';
	if ( $o['categoria'] && $d['categorias'] ) {
		$c .= '<span class="ce-chip">' . esc_html( $d['categorias'][0]->name ) . '</span>';
	}
	if ( 'programado' !== $d['estado'] ) {
		$c .= '<span class="ce-chip ce-chip-estado ce-chip-' . esc_attr( $d['estado'] ) . '">' . esc_html( $d['estado_txt'] ) . '</span>';
	}
	return $c ? '<div class="ce-chips">' . $c . '</div>' : '';
}

// ── Los tres diseños ────────────────────────────────────────────────────

function caracool_eventos_ficha_lista( $d, $o ) {
	$ico   = function ( $n ) use ( $o ) {
		return $o['iconos'] ? caracool_eventos_icono( $n ) : '';
	};
	$filas = array();
	if ( $d['dt_inicio'] ) {
		$t    = caracool_eventos_texto_fechas( $d );
		$h    = caracool_eventos_texto_horas_ficha( $d, $o );
		$v    = esc_html( ucfirst( wp_date( 'l', $d['dt_inicio']->getTimestamp() ) ) . ', ' . $t . ( $h ? '. ' . $h : '' ) );
		if ( $o['cuenta'] ) {
			$cu = caracool_eventos_cuenta( $d );
			$v .= $cu ? ' ' . $cu : '';
		}
		$filas[] = array( 'cuando', $o['t_cuando'], $v );
	}
	if ( $d['lugar'] ) {
		$l       = $d['lugar'];
		$dir     = caracool_eventos_texto_direccion( $l );
		$filas[] = array( 'donde', $o['t_donde'], '<strong>' . esc_html( $l['nombre'] ) . '</strong>' . ( $dir ? '<br>' . esc_html( $dir ) : '' ) . '<br><a href="' . esc_url( caracool_eventos_url_mapa( $l ) ) . '" target="_blank" rel="noopener">Ver en el mapa</a>' );
	}
	if ( 'programado' !== $d['estado'] ) {
		$filas[] = array( 'estado', $o['t_estado'], esc_html( $d['estado_txt'] ) );
	}
	$pr = caracool_eventos_texto_precio( $d );
	if ( $pr ) {
		$filas[] = array( 'precio', $o['t_precio'], esc_html( $pr ) );
	}
	$html = '<dl>';
	foreach ( $filas as $f ) {
		$html .= '<div class="ce-ficha-fila ce-fila-' . esc_attr( $f[0] ) . '"><dt>' . $ico( $f[0] ) . '<span>' . esc_html( $f[1] ) . '</span></dt><dd>' . $f[2] . '</dd></div>';
	}
	$html .= '</dl>';
	$b     = caracool_eventos_boton( $d );
	if ( $b ) {
		$html .= '<div class="ce-ficha-accion">' . $b . '</div>';
	}
	return ( $o['categoria'] && $d['categorias'] ? '<div class="ce-chips"><span class="ce-chip">' . esc_html( $d['categorias'][0]->name ) . '</span></div>' : '' )
		. $html . caracool_eventos_acciones_ficha( $d, $o ) . caracool_eventos_mapa_ficha( $d, $o ) . caracool_eventos_mas_fechas( $d, $o );
}

/** Texto del evento, sin volver a meter la ficha delante. */
function caracool_eventos_contenido_ficha( $d ) {
	$GLOBALS['caracool_eventos_sin_ficha'] = true;
	$c                                     = apply_filters( 'the_content', get_post_field( 'post_content', $d['id'] ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$GLOBALS['caracool_eventos_sin_ficha'] = false;
	return trim( (string) $c );
}

function caracool_eventos_ficha_columnas( $d, $o ) {
	$ico = function ( $n ) use ( $o ) {
		return $o['iconos'] ? caracool_eventos_icono( $n ) : '';
	};
	$h   = caracool_eventos_texto_horas_ficha( $d, $o );

	$t  = '<aside class="ce-tarjeta">';
	$t .= '<div class="ce-tarjeta-top">' . ( $d['dt_inicio'] ? caracool_eventos_hoja( $d ) : '' ) . '<div class="ce-tarjeta-cuando">';
	if ( '' !== (string) $d['hora_inicio'] ) {
		$t .= '<span class="ce-tarjeta-hora">' . esc_html( caracool_eventos_texto_horas( $d ) ) . '</span>';
		if ( $o['puertas'] && '' !== (string) $d['hora_puertas'] ) {
			$t .= '<span class="ce-tarjeta-puertas">' . esc_html( $o['t_puertas'] . ' ' . $d['hora_puertas'] ) . '</span>';
		}
	} elseif ( $d['dt_inicio'] ) {
		$t .= '<span class="ce-tarjeta-hora">' . esc_html( caracool_eventos_texto_fechas( $d ) ) . '</span>';
	}
	if ( $o['cuenta'] ) {
		$t .= caracool_eventos_cuenta( $d );
	}
	$t .= '</div></div>';
	if ( $d['lugar'] ) {
		$l   = $d['lugar'];
		$dir = caracool_eventos_texto_direccion( $l );
		$t  .= '<div class="ce-tarjeta-lin">' . $ico( 'donde' ) . '<div><strong>' . esc_html( $l['nombre'] ) . '</strong>' . ( $dir ? '<br>' . esc_html( $dir ) : '' ) . '<br><a href="' . esc_url( caracool_eventos_url_mapa( $l ) ) . '" target="_blank" rel="noopener">Ver en el mapa</a></div></div>';
	}
	$pr = caracool_eventos_texto_precio( $d );
	if ( $pr ) {
		$t .= '<div class="ce-tarjeta-lin">' . $ico( 'precio' ) . '<div><strong>' . esc_html( $pr ) . '</strong></div></div>';
	}
	$b  = caracool_eventos_boton( $d );
	$t .= $b ? '<div class="ce-ficha-accion">' . $b . '</div>' : '';
	$t .= caracool_eventos_mapa_ficha( $d, $o ) . caracool_eventos_acciones_ficha( $d, $o );
	$t .= '</aside>';

	$texto = caracool_eventos_contenido_ficha( $d );
	$izq   = caracool_eventos_chips_ficha( $d, $o ) . $texto;
	$html  = '<div class="ce-columnas' . ( '' === trim( wp_strip_all_tags( $izq ) ) ? ' ce-columnas-sola' : '' ) . '">';
	$html .= '' !== trim( wp_strip_all_tags( $izq ) ) ? '<div class="ce-columnas-texto">' . $izq . '</div>' : '';
	$html .= $t . '</div>';
	return $html . caracool_eventos_mas_fechas( $d, $o );
}

function caracool_eventos_ficha_entrada( $d, $o ) {
	$l    = $d['lugar'];
	$h    = caracool_eventos_texto_horas_ficha( $d, $o );
	$html = caracool_eventos_chips_ficha( $d, $o );
	$html .= '<div class="ce-entrada">';
	$html .= '<div class="ce-entrada-fecha">' . ( $d['dt_inicio'] ? caracool_eventos_hoja( $d ) : '' ) . '</div>';
	$html .= '<div class="ce-entrada-datos">';
	if ( $l ) {
		$html .= '<h3 class="ce-entrada-lugar">' . esc_html( $l['nombre'] ) . '</h3>';
		$dir   = caracool_eventos_texto_direccion( $l );
		$html .= $dir ? '<p class="ce-entrada-dir">' . esc_html( $dir ) . ' · <a href="' . esc_url( caracool_eventos_url_mapa( $l ) ) . '" target="_blank" rel="noopener">Ver en el mapa</a></p>' : '';
	}
	if ( $h ) {
		$html .= '<p class="ce-entrada-horas">' . esc_html( $h ) . '</p>';
	}
	if ( $o['cuenta'] ) {
		$cu    = caracool_eventos_cuenta( $d );
		$html .= $cu ? '<p class="ce-entrada-cuenta">' . $cu . '</p>' : '';
	}
	$html .= '</div>';
	$pr    = caracool_eventos_texto_precio( $d );
	$b     = caracool_eventos_boton( $d );
	if ( $pr || $b ) {
		$html .= '<div class="ce-entrada-compra">' . ( $pr ? '<span class="ce-entrada-precio">' . esc_html( $pr ) . '</span>' : '' ) . $b . '</div>';
	}
	$html .= '</div>';
	return $html . caracool_eventos_acciones_ficha( $d, $o ) . caracool_eventos_mapa_ficha( $d, $o ) . caracool_eventos_mas_fechas( $d, $o );
}

/**
 * Ficha del evento. Acepta el id y las opciones de caracool_eventos_opciones_ficha().
 * Por compatibilidad, caracool_eventos_ficha( 123 ) sigue funcionando.
 */
function caracool_eventos_ficha( $a = array() ) {
	if ( ! is_array( $a ) ) {
		$a = array( 'id' => (int) $a );
	}
	$o = caracool_eventos_opciones_ficha( $a );
	$d = caracool_eventos_datos( $o['id'] ? $o['id'] : get_the_ID() );
	if ( ! $d ) {
		return '';
	}
	wp_enqueue_style( 'caracool-eventos' );
	wp_enqueue_script( 'caracool-eventos' );
	// Si la plantilla ya pinta la ficha, el texto del evento no la repite.
	if ( (int) $d['id'] === (int) get_queried_object_id() ) {
		$GLOBALS['caracool_eventos_ficha_pintada'] = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}
	$f    = 'caracool_eventos_ficha_' . $o['diseno'];
	$html = '<div class="ce-ficha ce-ficha-' . esc_attr( $o['diseno'] ) . ' ce-estado-' . esc_attr( $d['estado'] ) . ( $o['iconos'] ? ' ce-con-iconos' : '' ) . '" data-ce-ficha>';
	return $html . $f( $d, $o ) . '</div>';
}

add_shortcode(
	'caracool_evento',
	function ( $a ) {
		return caracool_eventos_ficha( (array) $a );
	}
);

/*
 * En la página de un evento que no se ha montado con Elementor ni lleva ya la
 * ficha, la ficha va delante del texto. Con una plantilla de Elementor Pro se
 * coloca donde se quiera con el widget o con [caracool_evento].
 */
add_filter(
	'the_content',
	function ( $c ) {
		if ( ! empty( $GLOBALS['caracool_eventos_sin_ficha'] ) || ! empty( $GLOBALS['caracool_eventos_ficha_pintada'] ) ) {
			return $c;
		}
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
