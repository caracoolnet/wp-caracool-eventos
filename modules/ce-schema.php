<?php
/**
 * Caracool Eventos · datos estructurados para Google.
 *
 * Cada evento lleva su bloque JSON-LD de schema.org (MusicEvent, un tipo de
 * Event) con lo que Google pide y recomienda: nombre, fechas con zona horaria,
 * estado, modalidad, lugar con dirección, imagen, descripción, oferta de
 * entradas, artista y organizador. Cuando el evento ya ha terminado, el bloque
 * se queda igual pero sin la oferta de entradas.
 *
 * Va en la página de cada evento y, si está activado en los ajustes, también
 * en los listados, uno por evento mostrado.
 *
 * El tipo se puede cambiar con el filtro `caracool_eventos_schema_tipo` y el
 * bloque entero con `caracool_eventos_schema`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Fecha en el formato que pide Google: con hora y zona, o solo el día. */
function caracool_eventos_iso( $fecha, $hora ) {
	if ( '' === (string) $hora ) {
		return $fecha;
	}
	$dt = caracool_eventos_dt( $fecha, $hora );
	return $dt ? $dt->format( 'c' ) : $fecha;
}

/**
 * El bloque Event de un evento, como array.
 *
 * @param array $d Lo que devuelve caracool_eventos_datos().
 * @return array|null Null si le faltan los datos mínimos (fecha y lugar).
 */
function caracool_eventos_schema( $d ) {
	if ( ! $d || empty( $d['dt_inicio'] ) || empty( $d['lugar'] ) ) {
		return null;
	}
	$s = Caracool_Eventos::get_settings();

	$e = array(
		'@context'            => 'https://schema.org',
		'@type'               => apply_filters( 'caracool_eventos_schema_tipo', 'MusicEvent', $d ),
		'name'                => wp_strip_all_tags( $d['titulo'] ),
		'url'                 => $d['url'],
		'startDate'           => caracool_eventos_iso( $d['inicio'], $d['hora_inicio'] ),
		'eventStatus'         => 'https://schema.org/' . $d['schema_est'],
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
	);

	if ( $d['fin'] ) {
		$e['endDate'] = caracool_eventos_iso( $d['fin'], $d['hora_fin'] );
	} elseif ( '' !== (string) $d['hora_fin'] && '' !== (string) $d['hora_inicio'] ) {
		$e['endDate'] = caracool_eventos_iso( $d['inicio'], $d['hora_fin'] );
	}

	$l   = $d['lugar'];
	$dir = array_filter(
		array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $l['direccion'],
			'addressLocality' => $l['ciudad'],
			'postalCode'      => $l['cp'],
			'addressRegion'   => $l['provincia'],
			'addressCountry'  => $l['pais'] ? $l['pais'] : 'ES',
		)
	);
	$e['location'] = array_filter(
		array(
			'@type'   => 'Place',
			'name'    => $l['nombre'],
			'address' => $dir,
			'url'     => $l['web'],
		)
	);

	if ( $d['imagen'] ) {
		$e['image'] = array( $d['imagen'] );
	}
	if ( $d['resumen'] ) {
		$e['description'] = wp_strip_all_tags( $d['resumen'] );
	}

	// Un evento que ya ha terminado conserva su estado (sigue siendo «programado»:
	// se celebró), pero deja de ofrecer entradas.
	$ts_fin  = (int) get_post_meta( $d['id'], '_ce_ts_fin', true );
	$pasado  = $ts_fin && $ts_fin < time();

	if ( $d['venta_url'] && ! $pasado ) {
		$oferta = array(
			'@type'        => 'Offer',
			'url'          => $d['venta_url'],
			'availability' => 'https://schema.org/' . $d['schema_disp'],
			'validFrom'    => get_the_date( 'c', $d['id'] ),
		);
		if ( '' !== (string) $d['precio'] ) {
			$oferta['price']         = (string) $d['precio'];
			$oferta['priceCurrency'] = $d['moneda'];
		}
		$e['offers'] = $oferta;
	}

	$tipo_artista = isset( $s['artista_tipo'] ) ? $s['artista_tipo'] : 'Person';
	$artistas     = array_filter( array_map( 'trim', explode( ',', (string) $d['artista'] ) ) );
	if ( $artistas ) {
		$p = array();
		foreach ( $artistas as $i => $nombre ) {
			// El primero es el de los ajustes o el del evento; los que vienen
			// detrás en un cartel compartido se dan como personas.
			$p[] = array( '@type' => 0 === $i ? $tipo_artista : 'Person', 'name' => $nombre );
		}
		$e['performer'] = 1 === count( $p ) ? $p[0] : $p;
	}

	$org = isset( $s['organizador'] ) && '' !== $s['organizador'] ? $s['organizador'] : get_bloginfo( 'name' );
	if ( $org ) {
		$e['organizer'] = array(
			'@type' => 'Organization',
			'name'  => $org,
			'url'   => home_url( '/' ),
		);
	}

	return apply_filters( 'caracool_eventos_schema', $e, $d );
}

/** El bloque listo para imprimir. */
function caracool_eventos_schema_html( $d ) {
	$e = caracool_eventos_schema( $d );
	if ( ! $e ) {
		return '';
	}
	return '<script type="application/ld+json">' . wp_json_encode( $e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
}

add_action(
	'wp_head',
	function () {
		if ( is_singular( 'cc_evento' ) ) {
			echo caracool_eventos_schema_html( caracool_eventos_datos( get_queried_object_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	20
);
