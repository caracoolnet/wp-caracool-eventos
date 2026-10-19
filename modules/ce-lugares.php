<?php
/**
 * Caracool Eventos · lugares.
 *
 * Cada lugar es un término de «cc_lugar». El nombre es el de la sala o el
 * recinto y la dirección va en sus metadatos, que es lo que Google necesita
 * para el campo «location» del evento. Se ven y se editan en Eventos →
 * Lugares, y también se pueden crear desde el propio evento.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Campos de dirección de un lugar: clave => etiqueta. */
function caracool_eventos_campos_lugar() {
	return array(
		'ce_direccion' => 'Dirección',
		'ce_cp'        => 'Código postal',
		'ce_ciudad'    => 'Ciudad',
		'ce_provincia' => 'Provincia o región',
		'ce_pais'      => 'País',
		'ce_web'       => 'Web del lugar',
	);
}

/** Guarda la dirección de un lugar a partir de un array con las claves de arriba. */
function caracool_eventos_guardar_lugar( $term_id, $datos ) {
	foreach ( caracool_eventos_campos_lugar() as $k => $l ) {
		if ( ! array_key_exists( $k, $datos ) ) {
			continue;
		}
		$v = 'ce_web' === $k ? esc_url_raw( trim( (string) $datos[ $k ] ) ) : sanitize_text_field( (string) $datos[ $k ] );
		if ( 'ce_pais' === $k ) {
			$v = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $v ), 0, 2 ) );
		}
		if ( '' === $v ) {
			delete_term_meta( $term_id, $k );
		} else {
			update_term_meta( $term_id, $k, $v );
		}
	}
}

/**
 * Crea un lugar (o devuelve el que ya existe con ese nombre) y guarda su dirección.
 *
 * @return int ID del término o 0.
 */
function caracool_eventos_crear_lugar( $nombre, $datos = array() ) {
	$nombre = sanitize_text_field( $nombre );
	if ( '' === $nombre ) {
		return 0;
	}
	$t = term_exists( $nombre, 'cc_lugar' );
	if ( ! $t ) {
		$t = wp_insert_term( $nombre, 'cc_lugar' );
	}
	if ( is_wp_error( $t ) ) {
		return 0;
	}
	$id = (int) ( is_array( $t ) ? $t['term_id'] : $t );
	if ( $datos ) {
		caracool_eventos_guardar_lugar( $id, $datos );
	}
	return $id;
}

foreach ( caracool_eventos_campos_lugar() as $k => $l ) {
	register_meta( 'term', $k, array( 'object_subtype' => 'cc_lugar', 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
}

// ── Formularios de Eventos → Lugares ───────────────────────────────────

add_action(
	'cc_lugar_add_form_fields',
	function () {
		wp_nonce_field( 'ce_lugar', 'ce_lugar_nonce' );
		foreach ( caracool_eventos_campos_lugar() as $k => $l ) {
			$ph = 'ce_pais' === $k ? 'ES' : ( 'ce_web' === $k ? 'https://' : '' );
			echo '<div class="form-field"><label for="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label>';
			echo '<input type="' . ( 'ce_web' === $k ? 'url' : 'text' ) . '" name="ce_lugar[' . esc_attr( $k ) . ']" id="' . esc_attr( $k ) . '" value="' . ( 'ce_pais' === $k ? 'ES' : '' ) . '" placeholder="' . esc_attr( $ph ) . '">';
			if ( 'ce_pais' === $k ) {
				echo '<p>Código de dos letras: ES, MX, AR…</p>';
			}
			echo '</div>';
		}
	}
);

add_action(
	'cc_lugar_edit_form_fields',
	function ( $term ) {
		wp_nonce_field( 'ce_lugar', 'ce_lugar_nonce' );
		foreach ( caracool_eventos_campos_lugar() as $k => $l ) {
			$v = get_term_meta( $term->term_id, $k, true );
			echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td>';
			echo '<input type="' . ( 'ce_web' === $k ? 'url' : 'text' ) . '" name="ce_lugar[' . esc_attr( $k ) . ']" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
			if ( 'ce_pais' === $k ) {
				echo '<p class="description">Código de dos letras: ES, MX, AR…</p>';
			}
			echo '</td></tr>';
		}
	}
);

$caracool_eventos_guardar_form_lugar = function ( $term_id ) {
	if ( empty( $_POST['ce_lugar_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ce_lugar_nonce'] ) ), 'ce_lugar' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['ce_lugar'] ) && is_array( $_POST['ce_lugar'] ) ? wp_unslash( $_POST['ce_lugar'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	caracool_eventos_guardar_lugar( $term_id, $in );
};
add_action( 'created_cc_lugar', $caracool_eventos_guardar_form_lugar );
add_action( 'edited_cc_lugar', $caracool_eventos_guardar_form_lugar );

// Columnas de la lista de lugares: ciudad en vez de la descripción.
add_filter(
	'manage_edit-cc_lugar_columns',
	function ( $c ) {
		unset( $c['description'], $c['slug'] );
		$n = array();
		foreach ( $c as $k => $v ) {
			$n[ $k ] = $v;
			if ( 'name' === $k ) {
				$n['ce_ciudad'] = 'Ciudad';
				$n['ce_dir']    = 'Dirección';
			}
		}
		if ( isset( $n['posts'] ) ) {
			$n['posts'] = 'Eventos';
		}
		return $n;
	}
);
add_filter(
	'manage_cc_lugar_custom_column',
	function ( $out, $col, $term_id ) {
		if ( 'ce_ciudad' === $col ) {
			$c = get_term_meta( $term_id, 'ce_ciudad', true );
			$p = get_term_meta( $term_id, 'ce_pais', true );
			return esc_html( $c . ( $p && 'ES' !== $p ? ' (' . $p . ')' : '' ) );
		}
		if ( 'ce_dir' === $col ) {
			$d = get_term_meta( $term_id, 'ce_direccion', true );
			return $d ? esc_html( $d ) : '<span style="color:#b32d2e">Falta la dirección</span>';
		}
		return $out;
	},
	10,
	3
);
