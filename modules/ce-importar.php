<?php
/**
 * Caracool Eventos · importación desde JSON.
 *
 * Acepta una lista de eventos en JSON. Sirve para traer eventos de otra web o
 * de otro plugin (por ejemplo, de una exportación de Modern Events Calendar).
 * Un evento cuyo slug ya existe se actualiza en vez de duplicarse.
 *
 * Claves que entiende (todas opcionales salvo el título y la fecha):
 *   title | titulo        nombre del evento
 *   slug                  dirección
 *   status                publish, draft…
 *   fecha | inicio        AAAA-MM-DD
 *   hora | hora_inicio    HH:MM
 *   fin                   AAAA-MM-DD o «AAAA-MM-DD HH:MM»
 *   hora_fin              HH:MM
 *   ocultar_hora          true para no dar hora
 *   lugar                 nombre del lugar (se crea si no existe)
 *   direccion, cp, ciudad, provincia, pais   dirección del lugar
 *   entradas | venta      enlace de venta
 *   entradas_texto | boton, coste | precio, estado, artista
 *   categoria             nombre de la categoría (se crea si no existe)
 *   contenido             texto del evento (HTML)
 *   imagen                URL de la imagen destacada (se descarga)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'caracool_eventos_panel_importar', 'caracool_eventos_panel_importar' );

function caracool_eventos_panel_importar() {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="caracool_eventos_importar">
		<?php wp_nonce_field( 'caracool_eventos_importar' ); ?>
		<div class="ce-card">
			<div class="ce-card-head"><div class="ce-card-icon"><?php echo Caracool_Eventos::icono( 'importar' ); // phpcs:ignore ?></div><h2>Importar eventos</h2></div>
			<p class="ce-card-desc">Pega una lista de eventos en JSON. Si un evento ya existe con la misma dirección, se actualiza en lugar de duplicarse. Las claves que se entienden están al principio de <code>modules/ce-importar.php</code>.</p>
			<div class="ce-campo">
				<label for="ce-json">Eventos en JSON</label>
				<div><textarea id="ce-json" name="ce_json" rows="10" placeholder='[{"title":"Concierto en Valencia","fecha":"2026-12-12","hora":"21:00","lugar":"Roig Arena","ciudad":"Valencia"}]'></textarea></div>
			</div>
			<div class="ce-campo">
				<label for="ce-cat">Categoría para todos</label>
				<div><input type="text" id="ce-cat" name="ce_categoria"><span class="ce-hint">Opcional. Se suma a la que traiga cada evento.</span></div>
			</div>
			<div class="ce-campo ce-campo-sw">
				<div><span class="ce-label">Descargar las imágenes</span><span class="ce-hint">Trae a esta web la imagen de cada evento. Tarda más.</span></div>
				<label class="ce-sw"><input type="checkbox" name="ce_imagenes" value="1" checked><span></span></label>
			</div>
		</div>
		<p class="ce-acciones"><button type="submit" class="ce-btn ce-btn-primario"><?php echo Caracool_Eventos::icono( 'importar' ); // phpcs:ignore ?>Importar</button></p>
	</form>
	<?php
}

add_action(
	'admin_post_caracool_eventos_importar',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'caracool_eventos_importar' );
		$json   = isset( $_POST['ce_json'] ) ? wp_unslash( $_POST['ce_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$cat    = isset( $_POST['ce_categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['ce_categoria'] ) ) : '';
		$imagen = ! empty( $_POST['ce_imagenes'] );
		$lista  = json_decode( (string) $json, true );
		if ( ! is_array( $lista ) ) {
			$msg = 'No se ha importado nada: el texto no es un JSON válido.';
		} else {
			if ( isset( $lista['title'] ) || isset( $lista['titulo'] ) ) {
				$lista = array( $lista );
			}
			$r   = caracool_eventos_importar( $lista, array( 'categoria' => $cat, 'imagenes' => $imagen ) );
			$msg = sprintf( 'Importación terminada: %d nuevos, %d actualizados, %d sin importar.', $r['nuevos'], $r['actualizados'], $r['saltados'] );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . Caracool_Eventos::PAGINA . '&importados=' . rawurlencode( $msg ) . '#ce-t-importar' ) );
		exit;
	}
);

/**
 * Importa una lista de eventos.
 *
 * @return array nuevos, actualizados, saltados, ids.
 */
function caracool_eventos_importar( $lista, $op = array() ) {
	$op  = wp_parse_args( $op, array( 'categoria' => '', 'imagenes' => true ) );
	$r   = array( 'nuevos' => 0, 'actualizados' => 0, 'saltados' => 0, 'ids' => array() );
	$val = function ( $e, $claves, $def = '' ) {
		foreach ( (array) $claves as $k ) {
			if ( isset( $e[ $k ] ) && '' !== $e[ $k ] && null !== $e[ $k ] ) {
				return $e[ $k ];
			}
		}
		return $def;
	};

	foreach ( $lista as $e ) {
		if ( ! is_array( $e ) ) {
			++$r['saltados'];
			continue;
		}
		$titulo = sanitize_text_field( $val( $e, array( 'title', 'titulo' ) ) );
		$fecha  = substr( (string) $val( $e, array( 'fecha', 'inicio' ) ), 0, 10 );
		if ( '' === $titulo || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
			++$r['saltados'];
			continue;
		}
		$slug   = sanitize_title( $val( $e, 'slug', $titulo ) );
		$estado = $val( $e, 'status', 'publish' );
		$estado = in_array( $estado, array( 'publish', 'draft', 'pending', 'private', 'future' ), true ) ? $estado : 'publish';

		$existe = get_posts(
			array(
				'post_type'   => 'cc_evento',
				'name'        => $slug,
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		$datos  = array(
			'post_type'    => 'cc_evento',
			'post_title'   => $titulo,
			'post_name'    => $slug,
			'post_status'  => 'future' === $estado ? 'publish' : $estado,
			'post_content' => wp_kses_post( (string) $val( $e, 'contenido' ) ),
		);
		if ( $existe ) {
			$datos['ID'] = $existe[0];
			$id          = wp_update_post( $datos, true );
			$nuevo       = false;
		} else {
			$id    = wp_insert_post( $datos, true );
			$nuevo = true;
		}
		if ( is_wp_error( $id ) || ! $id ) {
			++$r['saltados'];
			continue;
		}

		// Horas: si el evento dice que no la enseñe, se quedan vacías.
		$ocultar = ! empty( $e['ocultar_hora'] ) && 'no' !== $e['ocultar_hora'];
		$hora    = $ocultar ? '' : (string) $val( $e, array( 'hora', 'hora_inicio' ) );
		$fin_raw = trim( (string) $val( $e, 'fin' ) );
		$fin_dia = substr( $fin_raw, 0, 10 );
		$hfin    = (string) $val( $e, 'hora_fin', strlen( $fin_raw ) > 10 ? trim( substr( $fin_raw, 11, 5 ) ) : '' );
		// Una hora de fin a medianoche o anterior al inicio no aporta nada.
		if ( $ocultar || '' === $hora || in_array( $hfin, array( '23:59', '00:00' ), true ) || ( $fin_dia === $fecha && $hfin <= $hora ) ) {
			$hfin = '';
		}

		$entradas = (string) $val( $e, array( 'entradas', 'venta', 'venta_url' ) );
		caracool_eventos_guardar_datos(
			$id,
			array(
				'_ce_inicio'      => $fecha,
				'_ce_hora_inicio' => $hora,
				'_ce_fin'         => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fin_dia ) && $fin_dia > $fecha ? $fin_dia : '',
				'_ce_hora_fin'    => $hfin,
				'_ce_estado'      => (string) $val( $e, 'estado', 'programado' ),
				'_ce_venta_url'   => $entradas,
				'_ce_boton'       => (string) $val( $e, array( 'entradas_texto', 'boton' ) ),
				'_ce_precio'      => (string) $val( $e, array( 'coste', 'precio' ) ),
				'_ce_artista'     => (string) $val( $e, 'artista' ),
			)
		);

		$lugar = (string) $val( $e, 'lugar' );
		if ( '' !== $lugar ) {
			$extra = array();
			foreach ( array( 'direccion', 'cp', 'ciudad', 'provincia', 'pais' ) as $k ) {
				if ( '' !== (string) $val( $e, $k ) ) {
					$extra[ 'ce_' . $k ] = (string) $val( $e, $k );
				}
			}
			$lid = caracool_eventos_crear_lugar( $lugar, $extra );
			if ( $lid ) {
				wp_set_object_terms( $id, array( $lid ), 'cc_lugar' );
			}
		}

		$cats = array_filter( array( (string) $val( $e, 'categoria' ), $op['categoria'] ) );
		if ( $cats ) {
			$tids = array();
			foreach ( $cats as $c ) {
				$t = term_exists( $c, 'cc_evento_cat' );
				if ( ! $t ) {
					$t = wp_insert_term( $c, 'cc_evento_cat' );
				}
				if ( ! is_wp_error( $t ) ) {
					$tids[] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
				}
			}
			wp_set_object_terms( $id, $tids, 'cc_evento_cat', true );
		}

		$img = (string) $val( $e, 'imagen' );
		if ( $op['imagenes'] && $img && ! has_post_thumbnail( $id ) ) {
			$aid = caracool_eventos_traer_imagen( $img, $id );
			if ( $aid ) {
				set_post_thumbnail( $id, $aid );
			}
		}

		caracool_eventos_recalcular( $id );
		$r['ids'][] = $id;
		++$r[ $nuevo ? 'nuevos' : 'actualizados' ];
	}
	return $r;
}

/** Descarga una imagen una sola vez: si ya se trajo antes, reutiliza el adjunto. */
function caracool_eventos_traer_imagen( $url, $post_id ) {
	$url = esc_url_raw( $url );
	if ( ! $url ) {
		return 0;
	}
	$ya = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_ce_origen', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $url, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $ya ) {
		return (int) $ya[0];
	}
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$aid = media_sideload_image( $url, $post_id, null, 'id' );
	if ( is_wp_error( $aid ) ) {
		return 0;
	}
	update_post_meta( $aid, '_ce_origen', $url );
	return (int) $aid;
}
