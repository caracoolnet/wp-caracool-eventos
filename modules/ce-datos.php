<?php
/**
 * Caracool Eventos · caja «Datos del evento» en el editor.
 *
 * Fechas y horas (la hora es opcional y el evento puede durar varios días),
 * el lugar (uno de la lista o uno nuevo creado ahí mismo), el estado, la
 * venta de entradas, el precio y el artista. Avisa de lo que le falta a un
 * evento para que Google lo acepte.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'add_meta_boxes_cc_evento',
	function () {
		add_meta_box( 'ce-datos', 'Datos del evento', 'caracool_eventos_caja', 'cc_evento', 'normal', 'high' );
	}
);

/** Lo que le falta a un evento para que Google lo dé por bueno. */
function caracool_eventos_faltas( $d ) {
	$f = array();
	if ( empty( $d['dt_inicio'] ) ) {
		$f[] = 'la fecha de inicio';
	}
	if ( empty( $d['lugar'] ) ) {
		$f[] = 'el lugar';
	} elseif ( empty( $d['lugar']['direccion'] ) && empty( $d['lugar']['ciudad'] ) ) {
		$f[] = 'la dirección o la ciudad del lugar';
	}
	return $f;
}

function caracool_eventos_caja( $post ) {
	$s       = Caracool_Eventos::get_settings();
	$v       = function ( $k ) use ( $post ) {
		return (string) get_post_meta( $post->ID, $k, true );
	};
	$ini     = $v( '_ce_inicio' );
	$fin     = $v( '_ce_fin' );
	$varios  = '' !== $fin && $fin > $ini;
	$blank   = $v( '_ce_venta_blank' );
	$blank   = '' === $blank ? (string) $s['nueva_ventana'] : $blank;
	$estado  = $v( '_ce_estado' );
	$lugares = get_terms( array( 'taxonomy' => 'cc_lugar', 'hide_empty' => false, 'orderby' => 'name' ) );
	$actual  = wp_get_object_terms( $post->ID, 'cc_lugar', array( 'fields' => 'ids' ) );
	$actual  = $actual && ! is_wp_error( $actual ) ? (int) $actual[0] : 0;
	$d       = caracool_eventos_datos( $post );
	$faltas  = 'auto-draft' === $post->post_status ? array() : caracool_eventos_faltas( $d );

	wp_nonce_field( 'ce_datos', 'ce_datos_nonce' );
	?>
	<div class="ce-wrap ce-caja">
		<style><?php echo Caracool_Eventos::estilos_admin(); // phpcs:ignore ?>
		.ce-caja{margin:0;max-width:none;}
		.ce-caja .ce-card{box-shadow:none;border:0;padding:4px 2px;margin:0;}
		.ce-caja .ce-fila{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-end;}
		.ce-caja .ce-fila > div{display:flex;flex-direction:column;gap:4px;}
		.ce-caja .ce-fila label{font-size:12px;font-weight:600;color:var(--ce-tinta-suave);}
		.ce-caja input[type=date]{width:170px;}
		.ce-caja input[type=time]{width:120px;}
		.ce-caja .ce-sub{margin-top:12px;padding:14px 16px;background:var(--ce-fondo);border-radius:var(--ce-r-medio);}
		.ce-caja .ce-sub .ce-fila input{max-width:none;}
		.ce-caja .ce-falta{margin:0 0 14px;padding:10px 14px;background:#fdf3e3;border:1px solid #eccf94;border-radius:var(--ce-r-chico);font-size:12.5px;color:#7a5a12;}
		.ce-caja .ce-sec{font-size:13px;font-weight:650;margin:0;}
		</style>

		<?php if ( $faltas ) : ?>
			<p class="ce-falta">Para que Google muestre este evento falta <?php echo esc_html( implode( ', ', $faltas ) ); ?>.</p>
		<?php endif; ?>

		<div class="ce-card">
			<div class="ce-campo">
				<span class="ce-label">Cuándo</span>
				<div>
					<div class="ce-fila">
						<div><label for="ce_inicio">Empieza</label><input type="date" id="ce_inicio" name="ce_datos[_ce_inicio]" value="<?php echo esc_attr( $ini ); ?>"></div>
						<div><label for="ce_hora_inicio">Hora</label><input type="time" id="ce_hora_inicio" name="ce_datos[_ce_hora_inicio]" value="<?php echo esc_attr( $v( '_ce_hora_inicio' ) ); ?>"></div>
						<div><label for="ce_hora_fin">Hora de fin</label><input type="time" id="ce_hora_fin" name="ce_datos[_ce_hora_fin]" value="<?php echo esc_attr( $v( '_ce_hora_fin' ) ); ?>"></div>
						<div><label for="ce_hora_puertas">Apertura de puertas</label><input type="time" id="ce_hora_puertas" name="ce_datos[_ce_hora_puertas]" value="<?php echo esc_attr( $v( '_ce_hora_puertas' ) ); ?>"></div>
					</div>
					<span class="ce-hint">Si dejas la hora vacía, no se muestra ninguna. La hora de fin y la apertura de puertas son opcionales.</span>
					<div class="ce-campo-sw" style="display:flex;gap:12px;align-items:center;margin-top:12px">
						<label class="ce-sw"><input type="checkbox" id="ce_varios" <?php checked( $varios ); ?>><span></span></label>
						<label for="ce_varios" style="font-size:13px;font-weight:560">Dura varios días</label>
					</div>
					<div class="ce-fila" id="ce_bloque_fin" style="margin-top:10px;<?php echo $varios ? '' : 'display:none'; ?>">
						<div><label for="ce_fin">Último día</label><input type="date" id="ce_fin" name="ce_datos[_ce_fin]" value="<?php echo esc_attr( $varios ? $fin : '' ); ?>"></div>
					</div>
				</div>
			</div>

			<div class="ce-campo">
				<label for="ce_lugar">Lugar</label>
				<div>
					<select id="ce_lugar" name="ce_lugar_id">
						<option value="">Sin lugar</option>
						<?php foreach ( (array) $lugares as $t ) : ?>
							<?php $c = get_term_meta( $t->term_id, 'ce_ciudad', true ); ?>
							<option value="<?php echo esc_attr( $t->term_id ); ?>" <?php selected( $actual, $t->term_id ); ?>><?php echo esc_html( $t->name . ( $c ? ' · ' . $c : '' ) ); ?></option>
						<?php endforeach; ?>
						<option value="__nuevo">+ Añadir un lugar nuevo</option>
					</select>
					<span class="ce-hint"><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=cc_lugar&post_type=cc_evento' ) ); ?>" target="_blank" rel="noopener">Ver y editar los lugares</a></span>
					<div class="ce-sub" id="ce_lugar_nuevo" style="display:none">
						<p class="ce-sec">Lugar nuevo</p>
						<div class="ce-fila" style="margin-top:8px">
							<div style="flex:2 1 220px"><label for="ce_ln_nombre">Nombre de la sala o recinto</label><input type="text" id="ce_ln_nombre" name="ce_lugar_nuevo[nombre]"></div>
							<div style="flex:3 1 260px"><label for="ce_ln_dir">Dirección</label><input type="text" id="ce_ln_dir" name="ce_lugar_nuevo[ce_direccion]"></div>
						</div>
						<div class="ce-fila" style="margin-top:10px">
							<div style="flex:0 1 110px"><label for="ce_ln_cp">Código postal</label><input type="text" id="ce_ln_cp" name="ce_lugar_nuevo[ce_cp]"></div>
							<div style="flex:1 1 160px"><label for="ce_ln_ciudad">Ciudad</label><input type="text" id="ce_ln_ciudad" name="ce_lugar_nuevo[ce_ciudad]"></div>
							<div style="flex:1 1 160px"><label for="ce_ln_prov">Provincia o región</label><input type="text" id="ce_ln_prov" name="ce_lugar_nuevo[ce_provincia]"></div>
							<div style="flex:0 1 70px"><label for="ce_ln_pais">País</label><input type="text" id="ce_ln_pais" name="ce_lugar_nuevo[ce_pais]" value="ES" maxlength="2"></div>
						</div>
						<span class="ce-hint">Se guarda en la lista de lugares al guardar el evento y queda disponible para los siguientes.</span>
					</div>
				</div>
			</div>

			<div class="ce-campo">
				<label for="ce_estado">Estado</label>
				<div>
					<select id="ce_estado" name="ce_datos[_ce_estado]" style="max-width:260px">
						<?php foreach ( caracool_eventos_estados() as $k => $e ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $estado ? $estado : 'programado', $k ); ?>><?php echo esc_html( $e[0] ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="ce-hint">«Agotado» y «Cancelado» se enseñan en el listado en lugar del botón de compra.</span>
				</div>
			</div>

			<div class="ce-campo">
				<label for="ce_venta">Venta de entradas</label>
				<div>
					<input type="url" id="ce_venta" name="ce_datos[_ce_venta_url]" value="<?php echo esc_attr( $v( '_ce_venta_url' ) ); ?>" placeholder="https://">
					<div class="ce-fila" style="margin-top:10px">
						<div><label for="ce_boton">Texto del botón</label><input type="text" id="ce_boton" name="ce_datos[_ce_boton]" value="<?php echo esc_attr( $v( '_ce_boton' ) ); ?>" placeholder="<?php echo esc_attr( $s['boton'] ); ?>" style="width:200px"></div>
						<div><label for="ce_precio">Precio desde (<?php echo esc_html( $s['moneda'] ); ?>)</label><input type="number" id="ce_precio" name="ce_datos[_ce_precio]" value="<?php echo esc_attr( $v( '_ce_precio' ) ); ?>" min="0" step="0.01" style="width:130px;min-width:0"></div>
					</div>
					<div style="display:flex;gap:12px;align-items:center;margin-top:12px">
						<input type="hidden" name="ce_datos[_ce_venta_blank]" value="0">
						<label class="ce-sw"><input type="checkbox" id="ce_blank" name="ce_datos[_ce_venta_blank]" value="1" <?php checked( '1', $blank ); ?>><span></span></label>
						<label for="ce_blank" style="font-size:13px;font-weight:560">Abrir la venta en otra pestaña</label>
					</div>
				</div>
			</div>

			<div class="ce-campo">
				<label for="ce_artista">Artista</label>
				<div>
					<input type="text" id="ce_artista" name="ce_datos[_ce_artista]" value="<?php echo esc_attr( $v( '_ce_artista' ) ); ?>" placeholder="<?php echo esc_attr( $s['artista'] ); ?>">
					<span class="ce-hint">Vacío usa el de los ajustes. Para un cartel con varios, sepáralos con comas.</span>
				</div>
			</div>
		</div>
	</div>
	<script>
	(function(){
		var sel=document.getElementById('ce_lugar'),nuevo=document.getElementById('ce_lugar_nuevo');
		function ver(){nuevo.style.display=sel.value==='__nuevo'?'':'none';if(sel.value==='__nuevo'){document.getElementById('ce_ln_nombre').focus();}}
		sel.addEventListener('change',ver);ver();
		var vs=document.getElementById('ce_varios'),bf=document.getElementById('ce_bloque_fin'),f=document.getElementById('ce_fin'),i=document.getElementById('ce_inicio');
		vs.addEventListener('change',function(){bf.style.display=vs.checked?'':'none';if(!vs.checked){f.value='';}else if(!f.value&&i.value){f.value=i.value;}});
		i.addEventListener('change',function(){if(f.value&&f.value<i.value){f.value=i.value;}f.min=i.value;});
		if(i.value){f.min=i.value;}
	})();
	</script>
	<?php
}

add_action( 'save_post_cc_evento', 'caracool_eventos_guardar_caja', 10, 2 );

function caracool_eventos_guardar_caja( $post_id, $post ) {
	if ( empty( $_POST['ce_datos_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ce_datos_nonce'] ) ), 'ce_datos' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['ce_datos'] ) && is_array( $_POST['ce_datos'] ) ? wp_unslash( $_POST['ce_datos'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	caracool_eventos_guardar_datos( $post_id, $in );

	// Lugar: uno de la lista, uno nuevo o ninguno.
	$lugar = isset( $_POST['ce_lugar_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ce_lugar_id'] ) ) : '';
	if ( '__nuevo' === $lugar ) {
		$n     = isset( $_POST['ce_lugar_nuevo'] ) && is_array( $_POST['ce_lugar_nuevo'] ) ? wp_unslash( $_POST['ce_lugar_nuevo'] ) : array(); // phpcs:ignore
		$id    = caracool_eventos_crear_lugar( isset( $n['nombre'] ) ? $n['nombre'] : '', $n );
		$lugar = $id ? $id : '';
	}
	if ( '' === $lugar ) {
		wp_set_object_terms( $post_id, array(), 'cc_lugar' );
	} elseif ( ctype_digit( (string) $lugar ) ) {
		wp_set_object_terms( $post_id, array( (int) $lugar ), 'cc_lugar' );
	}
}

/** Limpia y guarda los datos de un evento. Lo usan la caja y el importador. */
function caracool_eventos_guardar_datos( $post_id, $in ) {
	$fecha = function ( $x ) {
		$x = trim( (string) $x );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $x ) ? $x : '';
	};
	$hora = function ( $x ) {
		$x = trim( (string) $x );
		if ( preg_match( '/^(\d{1,2}):(\d{2})/', $x, $m ) && (int) $m[1] < 24 && (int) $m[2] < 60 ) {
			return sprintf( '%02d:%02d', $m[1], $m[2] );
		}
		return '';
	};
	$limpio = array();
	if ( array_key_exists( '_ce_inicio', $in ) ) {
		$limpio['_ce_inicio'] = $fecha( $in['_ce_inicio'] );
	}
	if ( array_key_exists( '_ce_fin', $in ) ) {
		$limpio['_ce_fin'] = $fecha( $in['_ce_fin'] );
	}
	foreach ( array( '_ce_hora_inicio', '_ce_hora_fin', '_ce_hora_puertas' ) as $k ) {
		if ( array_key_exists( $k, $in ) ) {
			$limpio[ $k ] = $hora( $in[ $k ] );
		}
	}
	if ( array_key_exists( '_ce_estado', $in ) ) {
		$limpio['_ce_estado'] = array_key_exists( $in['_ce_estado'], caracool_eventos_estados() ) ? $in['_ce_estado'] : 'programado';
	}
	if ( array_key_exists( '_ce_venta_url', $in ) ) {
		$limpio['_ce_venta_url'] = esc_url_raw( trim( (string) $in['_ce_venta_url'] ) );
	}
	if ( array_key_exists( '_ce_venta_blank', $in ) ) {
		$limpio['_ce_venta_blank'] = '1' === (string) $in['_ce_venta_blank'] ? '1' : '0';
	}
	foreach ( array( '_ce_boton', '_ce_artista' ) as $k ) {
		if ( array_key_exists( $k, $in ) ) {
			$limpio[ $k ] = sanitize_text_field( (string) $in[ $k ] );
		}
	}
	if ( array_key_exists( '_ce_precio', $in ) ) {
		$p                    = str_replace( ',', '.', trim( (string) $in['_ce_precio'] ) );
		$limpio['_ce_precio'] = is_numeric( $p ) && $p >= 0 ? rtrim( rtrim( number_format( (float) $p, 2, '.', '' ), '0' ), '.' ) : '';
	}
	// Un fin igual o anterior al inicio no es «varios días».
	if ( isset( $limpio['_ce_fin'], $limpio['_ce_inicio'] ) && $limpio['_ce_fin'] && $limpio['_ce_fin'] <= $limpio['_ce_inicio'] ) {
		$limpio['_ce_fin'] = '';
	}
	foreach ( $limpio as $k => $val ) {
		if ( '' === $val ) {
			delete_post_meta( $post_id, $k );
		} else {
			update_post_meta( $post_id, $k, $val );
		}
	}
	caracool_eventos_recalcular( $post_id );
}

// Aviso en la lista de eventos cuando alguno no tiene lo que pide Google.
add_action(
	'admin_notices',
	function () {
		$sc = get_current_screen();
		if ( ! $sc || 'edit-cc_evento' !== $sc->id ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'cc_evento',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( array( 'key' => '_ce_ts_fin', 'value' => time(), 'compare' => '>=', 'type' => 'NUMERIC' ) ), // phpcs:ignore
			)
		);
		$mal = array();
		foreach ( $ids as $id ) {
			if ( caracool_eventos_faltas( caracool_eventos_datos( $id ) ) ) {
				$mal[] = '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
			}
		}
		if ( $mal ) {
			echo '<div class="notice notice-warning"><p>A estos eventos próximos les falta algún dato para salir en Google: ' . implode( ', ', $mal ) . '.</p></div>'; // phpcs:ignore
		}
	}
);
