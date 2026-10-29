<?php
/**
 * Caracool Eventos · tipos de contenido y consultas.
 *
 * Registra el tipo «cc_evento», sus categorías («cc_evento_cat», jerárquicas,
 * para giras o tipos de evento) y sus lugares («cc_lugar», con dirección en
 * los metadatos del término). También guarda las fechas en dos marcas de
 * tiempo calculadas (_ce_ts_inicio y _ce_ts_fin) que son las que usan las
 * consultas para ordenar y para saber si un evento ya ha pasado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Claves de los datos de un evento y su tipo para el registro en REST. */
function caracool_eventos_campos() {
	return array(
		'_ce_inicio'      => 'string', // Y-m-d
		'_ce_hora_inicio' => 'string', // H:i o vacío
		'_ce_fin'         => 'string', // Y-m-d o vacío
		'_ce_hora_fin'    => 'string', // H:i o vacío
		'_ce_hora_puertas' => 'string', // H:i o vacío: apertura de puertas
		'_ce_estado'      => 'string',
		'_ce_venta_url'   => 'string',
		'_ce_venta_blank' => 'string', // '1' o '0'
		'_ce_boton'       => 'string',
		'_ce_precio'      => 'string',
		'_ce_artista'     => 'string',
	);
}

/** Estados posibles: etiqueta, eventStatus de schema.org y disponibilidad. */
function caracool_eventos_estados() {
	return array(
		'programado'   => array( 'Programado', 'EventScheduled', 'InStock' ),
		'ultimas'      => array( 'Últimas entradas', 'EventScheduled', 'LimitedAvailability' ),
		'agotado'      => array( 'Agotado', 'EventScheduled', 'SoldOut' ),
		'aplazado'     => array( 'Aplazado', 'EventPostponed', 'InStock' ),
		'reprogramado' => array( 'Nueva fecha', 'EventRescheduled', 'InStock' ),
		'cancelado'    => array( 'Cancelado', 'EventCancelled', 'Discontinued' ),
	);
}

add_action( 'init', 'caracool_eventos_registrar_tipos', 5 );

function caracool_eventos_registrar_tipos() {
	$slug = class_exists( 'Caracool_Eventos' ) ? Caracool_Eventos::ajuste( 'slug' ) : 'evento';

	register_post_type(
		'cc_evento',
		array(
			'labels'        => array(
				'name'               => 'Eventos',
				'singular_name'      => 'Evento',
				'menu_name'          => 'Eventos',
				'add_new'            => 'Añadir evento',
				'add_new_item'       => 'Añadir evento',
				'edit_item'          => 'Editar evento',
				'new_item'           => 'Evento nuevo',
				'view_item'          => 'Ver evento',
				'view_items'         => 'Ver eventos',
				'search_items'       => 'Buscar eventos',
				'not_found'          => 'No hay eventos.',
				'not_found_in_trash' => 'No hay eventos en la papelera.',
				'all_items'          => 'Todos los eventos',
				'item_published'     => 'Evento publicado.',
				'item_updated'       => 'Evento actualizado.',
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 21,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => $slug, 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'elementor' ),
		)
	);

	register_taxonomy(
		'cc_evento_cat',
		'cc_evento',
		array(
			'labels'            => array(
				'name'          => 'Categorías de eventos',
				'singular_name' => 'Categoría',
				'menu_name'     => 'Categorías',
				'all_items'     => 'Todas las categorías',
				'edit_item'     => 'Editar categoría',
				'add_new_item'  => 'Añadir categoría',
				'new_item_name' => 'Nombre de la categoría',
				'search_items'  => 'Buscar categorías',
				'not_found'     => 'No hay categorías.',
				'back_to_items' => 'Volver a las categorías',
			),
			'description'       => 'Giras, tipos de evento… Sirven para filtrar el listado.',
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => $slug . '-categoria', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'cc_lugar',
		'cc_evento',
		array(
			'labels'             => array(
				'name'          => 'Lugares',
				'singular_name' => 'Lugar',
				'menu_name'     => 'Lugares',
				'all_items'     => 'Todos los lugares',
				'edit_item'     => 'Editar lugar',
				'view_item'     => 'Ver lugar',
				'add_new_item'  => 'Añadir lugar',
				'new_item_name' => 'Nombre del lugar',
				'search_items'  => 'Buscar lugares',
				'not_found'     => 'No hay lugares.',
				'back_to_items' => 'Volver a los lugares',
			),
			'description'        => 'Salas, teatros, festivales… con su dirección.',
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'show_admin_column'  => false,
			'meta_box_cb'        => false, // Se elige en la caja de datos del evento.
			'rewrite'            => false,
		)
	);

	$auth = function () {
		return current_user_can( 'edit_posts' );
	};
	foreach ( caracool_eventos_campos() as $clave => $tipo ) {
		register_post_meta(
			'cc_evento',
			$clave,
			array(
				'type'          => $tipo,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => $auth,
			)
		);
	}
	foreach ( array( '_ce_ts_inicio', '_ce_ts_fin' ) as $clave ) {
		register_post_meta(
			'cc_evento',
			$clave,
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => array( 'schema' => array( 'readonly' => true ) ),
				'auth_callback' => '__return_false',
			)
		);
	}

	if ( get_option( 'caracool_eventos_flush' ) ) {
		delete_option( 'caracool_eventos_flush' );
		flush_rewrite_rules();
	}
}

/*
 * Los eventos se editan con el editor clásico: la caja de datos queda arriba,
 * a la vista, en vez de plegada al pie del editor de bloques.
 */
add_filter(
	'use_block_editor_for_post_type',
	function ( $usar, $tipo ) {
		return 'cc_evento' === $tipo ? false : $usar;
	},
	10,
	2
);

/*
 * El lugar se elige en la caja de datos del evento. Para que el editor de
 * bloques (si alguien lo fuerza) no enseñe además su propio panel de lugares,
 * se marca la taxonomía como no visible en la API sin quitarle la REST.
 */
add_filter(
	'rest_prepare_taxonomy',
	function ( $res, $tax ) {
		if ( 'cc_lugar' === $tax->name && isset( $res->data['visibility'] ) ) {
			$res->data['visibility']['show_ui'] = false;
		}
		return $res;
	},
	10,
	2
);

// ── Fechas ──────────────────────────────────────────────────────────────

/** Un DateTime en la zona horaria de la web a partir de fecha y hora. */
function caracool_eventos_dt( $fecha, $hora = '', $fin_del_dia = false ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $fecha ) ) {
		return null;
	}
	$h = preg_match( '/^\d{1,2}:\d{2}$/', (string) $hora ) ? $hora : ( $fin_del_dia ? '23:59:59' : '00:00' );
	try {
		return new DateTimeImmutable( $fecha . ' ' . $h, wp_timezone() );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Recalcula las marcas de tiempo de inicio y fin de un evento.
 *
 * El fin es la hora de fin del último día si existe y, si no, el final de ese
 * día. Un evento sin fecha de fin dura su día de inicio.
 */
function caracool_eventos_recalcular( $post_id ) {
	if ( 'cc_evento' !== get_post_type( $post_id ) ) {
		return;
	}
	$ini   = get_post_meta( $post_id, '_ce_inicio', true );
	$hini  = get_post_meta( $post_id, '_ce_hora_inicio', true );
	$fin   = get_post_meta( $post_id, '_ce_fin', true );
	$hfin  = get_post_meta( $post_id, '_ce_hora_fin', true );
	$d_ini = caracool_eventos_dt( $ini, $hini );
	if ( ! $d_ini ) {
		delete_post_meta( $post_id, '_ce_ts_inicio' );
		delete_post_meta( $post_id, '_ce_ts_fin' );
		return;
	}
	$dia_fin = ( $fin && $fin >= $ini ) ? $fin : $ini;
	$d_fin   = caracool_eventos_dt( $dia_fin, $hfin, true );
	if ( ! $d_fin || $d_fin < $d_ini ) {
		$d_fin = caracool_eventos_dt( $dia_fin, '', true );
	}
	update_post_meta( $post_id, '_ce_ts_inicio', $d_ini->getTimestamp() );
	update_post_meta( $post_id, '_ce_ts_fin', $d_fin->getTimestamp() );
}

add_action( 'save_post_cc_evento', 'caracool_eventos_recalcular', 30 );
add_action(
	'rest_after_insert_cc_evento',
	function ( $post ) {
		caracool_eventos_recalcular( $post->ID );
	}
);
add_action( 'updated_post_meta', 'caracool_eventos_meta_cambiada', 10, 3 );
add_action( 'added_post_meta', 'caracool_eventos_meta_cambiada', 10, 3 );

function caracool_eventos_meta_cambiada( $meta_id, $post_id, $clave ) {
	static $en_curso = false;
	if ( $en_curso || ! in_array( $clave, array( '_ce_inicio', '_ce_hora_inicio', '_ce_fin', '_ce_hora_fin' ), true ) ) {
		return;
	}
	$en_curso = true;
	caracool_eventos_recalcular( $post_id );
	$en_curso = false;
}

// ── Lectura de un evento ────────────────────────────────────────────────

/**
 * Todo lo que hace falta para pintar un evento, ya resuelto.
 *
 * @return array|null
 */
function caracool_eventos_datos( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'cc_evento' !== $post->post_type ) {
		return null;
	}
	$id      = $post->ID;
	$s       = class_exists( 'Caracool_Eventos' ) ? Caracool_Eventos::get_settings() : array();
	$estados = caracool_eventos_estados();

	$ini  = get_post_meta( $id, '_ce_inicio', true );
	$hini = get_post_meta( $id, '_ce_hora_inicio', true );
	$fin  = get_post_meta( $id, '_ce_fin', true );
	$hfin = get_post_meta( $id, '_ce_hora_fin', true );
	if ( $fin && $fin <= $ini ) {
		$fin = '';
	}

	$estado = get_post_meta( $id, '_ce_estado', true );
	if ( ! isset( $estados[ $estado ] ) ) {
		$estado = 'programado';
	}

	$lugar   = null;
	$terms   = get_the_terms( $id, 'cc_lugar' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$t     = $terms[0];
		$lugar = array(
			'id'        => $t->term_id,
			'nombre'    => $t->name,
			'direccion' => get_term_meta( $t->term_id, 'ce_direccion', true ),
			'cp'        => get_term_meta( $t->term_id, 'ce_cp', true ),
			'ciudad'    => get_term_meta( $t->term_id, 'ce_ciudad', true ),
			'provincia' => get_term_meta( $t->term_id, 'ce_provincia', true ),
			'pais'      => get_term_meta( $t->term_id, 'ce_pais', true ),
			'web'       => get_term_meta( $t->term_id, 'ce_web', true ),
		);
	}

	$cats = get_the_terms( $id, 'cc_evento_cat' );
	$cats = ( $cats && ! is_wp_error( $cats ) ) ? $cats : array();

	$blank = get_post_meta( $id, '_ce_venta_blank', true );
	if ( '' === $blank ) {
		$blank = ! empty( $s['nueva_ventana'] ) ? '1' : '0';
	}
	$boton = get_post_meta( $id, '_ce_boton', true );
	if ( '' === $boton ) {
		$boton = isset( $s['boton'] ) && '' !== $s['boton'] ? $s['boton'] : 'Entradas';
	}
	$artista = get_post_meta( $id, '_ce_artista', true );
	if ( '' === $artista ) {
		$artista = isset( $s['artista'] ) ? $s['artista'] : get_bloginfo( 'name' );
	}

	return array(
		'id'          => $id,
		'titulo'      => get_the_title( $id ),
		'url'         => get_permalink( $id ),
		'inicio'      => $ini,
		'hora_inicio' => $hini,
		'fin'         => $fin,
		'hora_fin'    => $hfin,
		'hora_puertas' => (string) get_post_meta( $id, '_ce_hora_puertas', true ),
		'dt_inicio'   => caracool_eventos_dt( $ini, $hini ),
		'dt_fin'      => $fin ? caracool_eventos_dt( $fin, $hfin, true ) : ( $hfin ? caracool_eventos_dt( $ini, $hfin ) : null ),
		'estado'      => $estado,
		'estado_txt'  => $estados[ $estado ][0],
		'schema_est'  => $estados[ $estado ][1],
		'schema_disp' => $estados[ $estado ][2],
		'venta_url'   => get_post_meta( $id, '_ce_venta_url', true ),
		'venta_blank' => '1' === (string) $blank,
		'boton'       => $boton,
		'precio'      => get_post_meta( $id, '_ce_precio', true ),
		'moneda'      => isset( $s['moneda'] ) ? $s['moneda'] : 'EUR',
		'artista'     => $artista,
		'lugar'       => $lugar,
		'categorias'  => $cats,
		'imagen'      => get_the_post_thumbnail_url( $id, 'full' ),
		'resumen'     => has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 40, '…' ),
	);
}

/** «12 dic 2026», «12–14 dic 2026», «30 dic 2026 – 2 ene 2027». */
function caracool_eventos_texto_fechas( $d ) {
	if ( empty( $d['dt_inicio'] ) ) {
		return '';
	}
	$a = $d['dt_inicio'];
	if ( empty( $d['fin'] ) ) {
		return wp_date( 'j M Y', $a->getTimestamp() );
	}
	$b = caracool_eventos_dt( $d['fin'] );
	if ( ! $b ) {
		return wp_date( 'j M Y', $a->getTimestamp() );
	}
	if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) {
		return wp_date( 'j', $a->getTimestamp() ) . '–' . wp_date( 'j M Y', $b->getTimestamp() );
	}
	if ( $a->format( 'Y' ) === $b->format( 'Y' ) ) {
		return wp_date( 'j M', $a->getTimestamp() ) . ' – ' . wp_date( 'j M Y', $b->getTimestamp() );
	}
	return wp_date( 'j M Y', $a->getTimestamp() ) . ' – ' . wp_date( 'j M Y', $b->getTimestamp() );
}

/** «21:00», «21:00 – 23:30» o vacío si no hay hora. */
function caracool_eventos_texto_horas( $d ) {
	$t = (string) $d['hora_inicio'];
	if ( '' === $t ) {
		return '';
	}
	if ( '' !== (string) $d['hora_fin'] ) {
		$t .= ' – ' . $d['hora_fin'];
	}
	return $t;
}

// ── Consultas ───────────────────────────────────────────────────────────

/**
 * Eventos para un listado.
 *
 * @param array $a categoria (slugs, array o lista con comas), limite (-1 todos),
 *                 pasados ('no' próximos, 'solo' pasados, 'todos').
 * @return WP_Post[]
 */
function caracool_eventos_consulta( $a = array() ) {
	$a = wp_parse_args(
		$a,
		array(
			'categoria' => '',
			'limite'    => -1,
			'pasados'   => 'no',
		)
	);
	$ahora = time();
	$q     = array(
		'post_type'      => 'cc_evento',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $a['limite'] > 0 ? (int) $a['limite'] : -1,
		'meta_key'       => '_ce_ts_inicio', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value_num',
		'order'          => 'solo' === $a['pasados'] ? 'DESC' : 'ASC',
		'no_found_rows'  => true,
	);
	if ( 'no' === $a['pasados'] ) {
		$q['meta_query'] = array( array( 'key' => '_ce_ts_fin', 'value' => $ahora, 'compare' => '>=', 'type' => 'NUMERIC' ) ); // phpcs:ignore
	} elseif ( 'solo' === $a['pasados'] ) {
		$q['meta_query'] = array( array( 'key' => '_ce_ts_fin', 'value' => $ahora, 'compare' => '<', 'type' => 'NUMERIC' ) ); // phpcs:ignore
	}
	$cats = is_array( $a['categoria'] ) ? $a['categoria'] : array_filter( array_map( 'trim', explode( ',', (string) $a['categoria'] ) ) );
	if ( $cats ) {
		$q['tax_query'] = array( array( 'taxonomy' => 'cc_evento_cat', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', $cats ) ) ); // phpcs:ignore
	}
	return get_posts( $q );
}

// ── Listado del admin: columnas y orden ────────────────────────────────

add_filter(
	'manage_cc_evento_posts_columns',
	function ( $c ) {
		$n = array();
		foreach ( $c as $k => $v ) {
			if ( 'date' === $k ) {
				continue;
			}
			$n[ $k ] = $v;
			if ( 'title' === $k ) {
				$n['ce_fecha']  = 'Fecha';
				$n['ce_lugar']  = 'Lugar';
				$n['ce_estado'] = 'Estado';
			}
		}
		return $n;
	}
);

add_action(
	'manage_cc_evento_posts_custom_column',
	function ( $col, $id ) {
		$d = caracool_eventos_datos( $id );
		if ( ! $d ) {
			return;
		}
		if ( 'ce_fecha' === $col ) {
			$txt = caracool_eventos_texto_fechas( $d );
			echo $txt ? esc_html( $txt ) : '<span style="color:#b32d2e">Sin fecha</span>';
			$h = caracool_eventos_texto_horas( $d );
			if ( $h ) {
				echo '<br><span style="color:#646970">' . esc_html( $h ) . '</span>';
			}
			$fin = (int) get_post_meta( $id, '_ce_ts_fin', true );
			if ( $fin && $fin < time() ) {
				echo '<br><span style="color:#646970">Ya ha pasado</span>';
			}
		} elseif ( 'ce_lugar' === $col ) {
			if ( $d['lugar'] ) {
				echo esc_html( $d['lugar']['nombre'] );
				if ( $d['lugar']['ciudad'] ) {
					echo '<br><span style="color:#646970">' . esc_html( $d['lugar']['ciudad'] ) . '</span>';
				}
			} else {
				echo '—';
			}
		} elseif ( 'ce_estado' === $col ) {
			echo esc_html( $d['estado_txt'] );
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-cc_evento_sortable_columns',
	function ( $c ) {
		$c['ce_fecha'] = 'ce_fecha';
		return $c;
	}
);

// ── Listado del admin: pestañas Próximos, Pasados, Borradores y Papelera ──

/** Qué pestaña de tiempo está abierta en la lista de eventos: proximos, pasados o vacío. */
function caracool_eventos_vista_admin() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ce_cuando'] ) ) {
		$v = sanitize_key( wp_unslash( $_GET['ce_cuando'] ) );
		return in_array( $v, array( 'proximos', 'pasados' ), true ) ? $v : '';
	}
	// Sin pestaña, sin estado y sin búsqueda, la lista se abre en Próximos.
	if ( empty( $_GET['post_status'] ) && empty( $_GET['s'] ) && empty( $_GET['m'] ) && empty( $_GET['cc_evento_cat'] ) ) {
		return 'proximos';
	}
	// phpcs:enable
	return '';
}

/**
 * Estados que salen en las pestañas Próximos y Pasados del admin: todos menos
 * la papelera y los borradores automáticos. Así un borrador no se queda
 * escondido; WordPress le pone «— Borrador» al lado del título.
 */
function caracool_eventos_estados_vista() {
	return array( 'publish', 'future', 'draft', 'pending', 'private' );
}

/** Condición de fecha de cada pestaña, para meta_query. */
function caracool_eventos_condicion_vista( $vista ) {
	$ahora = time();
	if ( 'pasados' === $vista ) {
		return array( 'key' => '_ce_ts_fin', 'value' => $ahora, 'compare' => '<', 'type' => 'NUMERIC' );
	}
	// Próximos: los que aún no han terminado y los que no tienen fecha, para que no se pierdan.
	return array(
		'relation' => 'OR',
		array( 'key' => '_ce_ts_fin', 'value' => $ahora, 'compare' => '>=', 'type' => 'NUMERIC' ),
		array( 'key' => '_ce_ts_fin', 'compare' => 'NOT EXISTS' ),
	);
}

/** Cuántos eventos hay en una pestaña, publicados o no. */
function caracool_eventos_contar_vista( $vista ) {
	$q = new WP_Query(
		array(
			'post_type'              => 'cc_evento',
			'post_status'            => caracool_eventos_estados_vista(),
			'perm'                   => 'readable',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array( caracool_eventos_condicion_vista( $vista ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) $q->found_posts;
}

add_filter(
	'views_edit-cc_evento',
	function ( $views ) {
		$vista = caracool_eventos_vista_admin();
		$base  = admin_url( 'edit.php?post_type=cc_evento' );
		$nuevo = array();
		foreach ( array( 'proximos' => 'Próximos', 'pasados' => 'Pasados' ) as $clave => $nombre ) {
			$nuevo[ 'ce_' . $clave ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'ce_cuando', $clave, $base ) ),
				$vista === $clave ? ' class="current" aria-current="page"' : '',
				esc_html( $nombre ),
				caracool_eventos_contar_vista( $clave )
			);
		}
		// Detrás, los estados de WordPress que no son «publicado»: borradores,
		// pendientes, programados para publicarse, privados y papelera.
		foreach ( $views as $clave => $html ) {
			if ( in_array( $clave, array( 'all', 'publish', 'mine' ), true ) ) {
				continue;
			}
			$nuevo[ $clave ] = $html;
		}
		return $nuevo;
	}
);

add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'cc_evento' !== $q->get( 'post_type' ) ) {
			return;
		}
		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return;
		}
		$vista = caracool_eventos_vista_admin();
		$orden = $q->get( 'orderby' );

		// Con un OR de EXISTS / NOT EXISTS, los eventos sin fecha siguen
		// saliendo en la lista en vez de desaparecer al ordenar.
		$fecha = array(
			'relation' => 'OR',
			'ce_f'     => array( 'key' => '_ce_ts_inicio', 'compare' => 'EXISTS', 'type' => 'NUMERIC' ),
			array( 'key' => '_ce_ts_inicio', 'compare' => 'NOT EXISTS' ),
		);

		if ( $vista ) {
			$q->set( 'post_status', caracool_eventos_estados_vista() );
			$q->set( 'perm', 'readable' );
			$q->set( 'meta_query', array( 'relation' => 'AND', $fecha, caracool_eventos_condicion_vista( $vista ) ) );
			// Próximos, del más cercano al más lejano; pasados, del más reciente al más antiguo.
			$dir = 'pasados' === $vista ? 'DESC' : 'ASC';
			if ( 'ce_fecha' === $orden ) {
				$dir = 'DESC' === strtoupper( (string) $q->get( 'order' ) ) ? 'DESC' : 'ASC';
			}
			$q->set( 'orderby', array( 'ce_f' => $dir ) );
			return;
		}

		if ( 'ce_fecha' === $orden || '' === $orden ) {
			$q->set( 'meta_query', $fecha );
			$q->set( 'orderby', array( 'ce_f' => '' === $orden ? 'DESC' : ( 'DESC' === strtoupper( (string) $q->get( 'order' ) ) ? 'DESC' : 'ASC' ) ) );
		}
	}
);
