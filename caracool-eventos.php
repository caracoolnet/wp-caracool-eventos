<?php
/**
 * Plugin Name:  Caracool Eventos
 * Plugin URI:   https://github.com/caracoolnet/wp-caracool-eventos
 * Description:  Conciertos y eventos con fecha, hora, lugar y enlace de venta. Se agrupan por categorías (giras, tipos), el listado esconde solos los que ya han pasado y cada evento lleva los datos estructurados que pide Google.
 * Version:      0.6.0
 * Author:       Caracool
 * Author URI:   https://caracool.net
 * License:      GPL-2.0-or-later
 * Text Domain:  caracool-eventos
 * Requires PHP: 7.4
 *
 * ARQUITECTURA
 * ─────────────────────────────────────────────────────────────────────
 * Este archivo es delgado: constantes, menú de la casa, página de ajustes y
 * actualizador desde GitHub. El resto va en /modules, un archivo por tarea:
 *
 *   ce-tipos.php     tipo de contenido «cc_evento», categorías y lugares
 *   ce-datos.php     la caja de datos del evento (fechas, lugar, venta…)
 *   ce-lugares.php   campos de dirección de cada lugar
 *   ce-schema.php    datos estructurados Event (JSON-LD) para Google
 *   ce-listado.php   listado con filtro: shortcode y widget de Elementor
 *   ce-importar.php  importación de eventos desde un JSON
 *
 * Los módulos se autorregistran. Si falta uno, el resto sigue funcionando.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CARACOOL_EVENTOS_VERSION', '0.6.0' );
define( 'CARACOOL_EVENTOS_FILE', __FILE__ );
define( 'CARACOOL_EVENTOS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARACOOL_EVENTOS_URL', plugin_dir_url( __FILE__ ) );
define( 'CARACOOL_EVENTOS_REPO', 'caracoolnet/wp-caracool-eventos' );

/*
 * Menú compartido de la casa. Archivo común a los plugins de Caracool, igual
 * byte a byte en todos. No crea dependencia entre plugins.
 */
require_once CARACOOL_EVENTOS_DIR . 'inc/caracool-menu.php';

/**
 * Versión de un recurso del plugin para la URL: la del plugin más la fecha
 * del archivo, para que cada build nuevo invalide las cachés.
 */
function caracool_eventos_ver( $archivo ) {
	$ruta  = CARACOOL_EVENTOS_DIR . 'assets/' . $archivo;
	$fecha = file_exists( $ruta ) ? filemtime( $ruta ) : 0;
	return CARACOOL_EVENTOS_VERSION . ( $fecha ? '.' . $fecha : '' );
}

class Caracool_Eventos {

	const OPTION_KEY = 'caracool_eventos_settings';
	const PAGINA     = 'caracool-eventos';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'caracool_plugins', array( $this, 'presentarse' ) );
		add_action( 'admin_post_caracool_eventos_save', array( $this, 'save_settings' ), 10 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'update_details' ), 20, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( CARACOOL_EVENTOS_FILE ), array( $this, 'enlace_ajustes' ) );
	}

	/** Valores por defecto de los ajustes generales. */
	public static function defaults() {
		return array(
			'slug'          => 'evento',
			'artista'       => get_bloginfo( 'name' ),
			'artista_tipo'  => 'Person',
			'organizador'   => get_bloginfo( 'name' ),
			'moneda'        => 'EUR',
			'boton'         => 'Entradas',
			'nueva_ventana' => 1,
			'schema_lista'  => 1,
		);
	}

	public static function get_settings() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public static function ajuste( $clave ) {
		$s = self::get_settings();
		return isset( $s[ $clave ] ) ? $s[ $clave ] : null;
	}

	// ── Menú y página de ajustes ────────────────────────────────────────

	public function add_menu() {
		add_submenu_page(
			CARACOOL_MENU_SLUG,
			'Caracool Eventos',
			'Eventos',
			'manage_options',
			self::PAGINA,
			array( $this, 'render_settings_page' )
		);
	}

	public function presentarse( $lista ) {
		$lista[] = array(
			'nombre'  => 'Eventos',
			'pagina'  => self::PAGINA,
			'version' => CARACOOL_EVENTOS_VERSION,
			'resumen' => 'Conciertos y eventos con categorías, lugares, venta de entradas y datos para Google.',
		);
		return $lista;
	}

	public function enlace_ajustes( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGINA ) ) . '">Ajustes</a>' );
		return $links;
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'caracool_eventos_save' );

		$antes = self::get_settings();
		$in    = isset( $_POST['ce'] ) && is_array( $_POST['ce'] ) ? wp_unslash( $_POST['ce'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$slug = sanitize_title( isset( $in['slug'] ) ? $in['slug'] : '' );
		$tipo = isset( $in['artista_tipo'] ) && in_array( $in['artista_tipo'], array( 'Person', 'MusicGroup', 'PerformingGroup' ), true ) ? $in['artista_tipo'] : 'Person';

		$nuevo = array(
			'slug'          => $slug ? $slug : 'evento',
			'artista'       => sanitize_text_field( isset( $in['artista'] ) ? $in['artista'] : '' ),
			'artista_tipo'  => $tipo,
			'organizador'   => sanitize_text_field( isset( $in['organizador'] ) ? $in['organizador'] : '' ),
			'moneda'        => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', isset( $in['moneda'] ) ? $in['moneda'] : 'EUR' ), 0, 3 ) ),
			'boton'         => sanitize_text_field( isset( $in['boton'] ) ? $in['boton'] : '' ),
			'nueva_ventana' => empty( $in['nueva_ventana'] ) ? 0 : 1,
			'schema_lista'  => empty( $in['schema_lista'] ) ? 0 : 1,
		);
		if ( '' === $nuevo['moneda'] ) {
			$nuevo['moneda'] = 'EUR';
		}
		update_option( self::OPTION_KEY, $nuevo );

		// Si cambia la base de la URL, las reglas de reescritura se rehacen en
		// la siguiente carga, cuando el tipo de contenido ya lleva el slug nuevo.
		if ( $antes['slug'] !== $nuevo['slug'] ) {
			update_option( 'caracool_eventos_flush', 1 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGINA . '&guardado=1' ) );
		exit;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s        = self::get_settings();
		$guardado = isset( $_GET['guardado'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$import   = isset( $_GET['importados'] ) ? sanitize_text_field( wp_unslash( $_GET['importados'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$total    = wp_count_posts( 'cc_evento' );
		$n_pub    = isset( $total->publish ) ? (int) $total->publish : 0;
		$proximos = function_exists( 'caracool_eventos_consulta' ) ? count( caracool_eventos_consulta( array( 'limite' => -1 ) ) ) : 0;
		$ejemplo  = home_url( '/' . $s['slug'] . '/nombre-del-evento/' );
		?>
		<div class="ce-wrap">
			<style><?php echo self::estilos_admin(); // phpcs:ignore WordPress.Security.EscapeOutput ?></style>

			<header class="ce-head">
				<div class="ce-brand">
					<?php echo self::logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="ce-brand-txt">
						<h1>Eventos <span class="ce-badge ce-badge-ver">v<?php echo esc_html( CARACOOL_EVENTOS_VERSION ); ?></span></h1>
						<p>Conciertos y eventos con venta de entradas y datos para Google</p>
					</div>
				</div>
				<span class="ce-chip"><i></i><?php echo esc_html( sprintf( '%d publicados, %d próximos', $n_pub, $proximos ) ); ?></span>
			</header>

			<?php if ( $guardado ) : ?>
				<div class="ce-aviso">Configuración guardada.</div>
			<?php endif; ?>
			<?php if ( '' !== $import ) : ?>
				<div class="ce-aviso"><?php echo esc_html( $import ); ?></div>
			<?php endif; ?>

			<nav class="ce-tabs" role="tablist">
				<button type="button" class="ce-tab" data-ce-tab="ce-t-ajustes"><?php echo self::icono( 'ajustes' ); // phpcs:ignore ?>Ajustes</button>
				<button type="button" class="ce-tab" data-ce-tab="ce-t-uso"><?php echo self::icono( 'uso' ); // phpcs:ignore ?>Cómo se usa</button>
				<button type="button" class="ce-tab" data-ce-tab="ce-t-importar"><?php echo self::icono( 'importar' ); // phpcs:ignore ?>Importar</button>
			</nav>

			<section id="ce-t-ajustes" class="ce-panel">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="caracool_eventos_save">
					<?php wp_nonce_field( 'caracool_eventos_save' ); ?>

					<div class="ce-card">
						<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'enlace' ); // phpcs:ignore ?></div><h2>Dirección de cada evento</h2></div>
						<p class="ce-card-desc">Cada evento tiene su propia página. Es la que Google enlaza en sus resultados de eventos.</p>
						<div class="ce-campo">
							<label for="ce-slug">Base de la URL</label>
							<div>
								<input type="text" id="ce-slug" name="ce[slug]" value="<?php echo esc_attr( $s['slug'] ); ?>">
								<span class="ce-hint">Ahora: <code><?php echo esc_html( $ejemplo ); ?></code>. Si la cambias, revisa los enlaces que ya tengas puestos.</span>
							</div>
						</div>
					</div>

					<div class="ce-card">
						<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'google' ); // phpcs:ignore ?></div><h2>Datos para Google</h2></div>
						<p class="ce-card-desc">Google pide saber quién actúa y quién organiza. Estos valores se usan cuando el evento no dice otra cosa.</p>
						<div class="ce-campo">
							<label for="ce-artista">Artista por defecto</label>
							<div><input type="text" id="ce-artista" name="ce[artista]" value="<?php echo esc_attr( $s['artista'] ); ?>"></div>
						</div>
						<div class="ce-campo">
							<label>Tipo de artista</label>
							<div class="ce-pills">
								<?php foreach ( array( 'Person' => 'Persona', 'MusicGroup' => 'Grupo musical', 'PerformingGroup' => 'Compañía' ) as $v => $l ) : ?>
									<label class="<?php echo $s['artista_tipo'] === $v ? 'on' : ''; ?>"><input type="radio" name="ce[artista_tipo]" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s['artista_tipo'], $v ); ?>><?php echo esc_html( $l ); ?></label>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ce-campo">
							<label for="ce-organizador">Organizador</label>
							<div><input type="text" id="ce-organizador" name="ce[organizador]" value="<?php echo esc_attr( $s['organizador'] ); ?>"><span class="ce-hint">Se enlaza a la portada de esta web.</span></div>
						</div>
						<div class="ce-campo">
							<label for="ce-moneda">Moneda de los precios</label>
							<div><input type="text" id="ce-moneda" name="ce[moneda]" value="<?php echo esc_attr( $s['moneda'] ); ?>" maxlength="3" style="min-width:90px;width:90px"><span class="ce-hint">Código de tres letras: EUR, USD, MXN…</span></div>
						</div>
						<div class="ce-campo ce-campo-sw">
							<div><span class="ce-label">Datos también en los listados</span><span class="ce-hint">Además de en la página de cada evento, los listados llevan los datos de los eventos que muestran.</span></div>
							<label class="ce-sw"><input type="checkbox" name="ce[schema_lista]" value="1" <?php checked( $s['schema_lista'], 1 ); ?>><span></span></label>
						</div>
					</div>

					<div class="ce-card">
						<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'ticket' ); // phpcs:ignore ?></div><h2>Venta de entradas</h2></div>
						<p class="ce-card-desc">Lo que se propone al crear un evento. Cada evento puede cambiarlo.</p>
						<div class="ce-campo">
							<label for="ce-boton">Texto del botón</label>
							<div><input type="text" id="ce-boton" name="ce[boton]" value="<?php echo esc_attr( $s['boton'] ); ?>"></div>
						</div>
						<div class="ce-campo ce-campo-sw">
							<div><span class="ce-label">Abrir la venta en otra pestaña</span><span class="ce-hint">Valor inicial de los eventos nuevos.</span></div>
							<label class="ce-sw"><input type="checkbox" name="ce[nueva_ventana]" value="1" <?php checked( $s['nueva_ventana'], 1 ); ?>><span></span></label>
						</div>
					</div>

					<p class="ce-acciones">
						<button type="submit" class="ce-btn ce-btn-primario"><?php echo self::icono( 'ok' ); // phpcs:ignore ?>Guardar configuración</button>
						<a class="ce-btn ce-btn-ghost" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=cc_evento' ) ); ?>">Añadir evento</a>
					</p>
				</form>
			</section>

			<section id="ce-t-uso" class="ce-panel">
				<div class="ce-card">
					<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'uso' ); // phpcs:ignore ?></div><h2>Poner el listado en una página</h2></div>
					<p class="ce-card-desc">Con Elementor, busca el widget <strong>Eventos</strong>. Sin Elementor, pega este código donde quieras el listado:</p>
					<p><code class="ce-code">[caracool_eventos]</code></p>
					<table class="ce-tabla">
						<thead><tr><th>Opción</th><th>Qué hace</th></tr></thead>
						<tbody>
							<tr><td><code>categoria="gira-ellar"</code></td><td>Solo los eventos de esa categoría. Admite varias separadas por comas.</td></tr>
							<tr><td><code>filtro="no"</code></td><td>Quita los botones para filtrar por categoría.</td></tr>
							<tr><td><code>limite="6"</code></td><td>Cuántos eventos enseñar. Por defecto, todos.</td></tr>
							<tr><td><code>pasados="solo"</code></td><td>Enseña solo los que ya han pasado, del más reciente al más antiguo. Sirve para un histórico.</td></tr>
							<tr><td><code>vacio="Pronto, nuevas fechas"</code></td><td>Texto cuando no hay eventos que enseñar.</td></tr>
							<tr><td><code>anio="si"</code></td><td>Enseña el año debajo del mes.</td></tr>
							<tr><td><code>etiqueta="h2"</code></td><td>Etiqueta HTML del título: de <code>h1</code> a <code>h6</code>, <code>div</code>, <code>span</code> o <code>p</code>. Por defecto, <code>h3</code>.</td></tr>
							<tr><td><code>fecha="calendario"</code></td><td>Diseño de la fecha: <code>clasico</code> (el de siempre), <code>calendario</code>, <code>bloque</code>, <code>contorno</code> o <code>linea</code>.</td></tr>
							<tr><td><code>semana="completo"</code></td><td>Día de la semana entero («viernes») o abreviado («vie», con <code>corto</code>). Si no se pone, el calendario lo lleva entero y el resto, abreviado.</td></tr>
						</tbody>
					</table>
				</div>
				<div class="ce-card">
					<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'uso' ); // phpcs:ignore ?></div><h2>La ficha de cada evento</h2></div>
					<p class="ce-card-desc">Con Elementor, pon el widget <strong>Ficha del evento</strong> en la plantilla de los eventos del Maquetador de temas. Sin Elementor, la ficha sale sola delante del texto, o donde pongas este código:</p>
					<p><code class="ce-code">[caracool_evento]</code></p>
					<table class="ce-tabla">
						<thead><tr><th>Opción</th><th>Qué hace</th></tr></thead>
						<tbody>
							<tr><td><code>diseno="columnas"</code></td><td>Diseño: <code>lista</code> (el de siempre), <code>columnas</code> (texto a la izquierda y tarjeta de compra a la derecha) o <code>entrada</code> (banda con forma de entrada).</td></tr>
							<tr><td><code>iconos="si"</code></td><td>Iconos junto a cada dato.</td></tr>
							<tr><td><code>puertas="si"</code></td><td>Enseña la apertura de puertas, si el evento la tiene.</td></tr>
							<tr><td><code>cuenta="si"</code></td><td>Cuenta atrás: «Faltan 16 días», «Es mañana», «Es hoy».</td></tr>
							<tr><td><code>calendario="si"</code></td><td>Enlaces para añadir el evento a Google Calendar o descargar el .ics.</td></tr>
							<tr><td><code>compartir="si"</code></td><td>WhatsApp, copiar enlace y el menú de compartir del móvil.</td></tr>
							<tr><td><code>mapa="si"</code></td><td>Botón «Ver mapa». Google Maps solo se carga al pulsarlo.</td></tr>
							<tr><td><code>mas="si" mas_limite="3"</code></td><td>Las próximas fechas de la misma categoría.</td></tr>
							<tr><td><code>categoria="si"</code></td><td>La categoría como etiqueta.</td></tr>
							<tr><td><code>etiqueta_lugar="h2" etiqueta_mas="h4"</code></td><td>Etiqueta HTML del lugar (diseño Entrada) y del título de «Más fechas». Por defecto, <code>h3</code>.</td></tr>
						</tbody>
					</table>
				</div>
				<div class="ce-card">
					<div class="ce-card-head"><div class="ce-card-icon"><?php echo self::icono( 'reloj' ); // phpcs:ignore ?></div><h2>Cuándo deja de salir un evento</h2></div>
					<p class="ce-card-desc">Un evento sale del listado cuando termina: a su hora de fin si la tiene y, si no, al acabar su último día. Su página sigue publicada y sus datos siguen valiendo para el histórico.</p>
				</div>
			</section>

			<section id="ce-t-importar" class="ce-panel">
				<?php do_action( 'caracool_eventos_panel_importar' ); ?>
			</section>

			<footer class="ce-pie">Hecho con <span class="ce-corazon">&#10084;</span> por Caracool</footer>
		</div>
		<script>
		(function(){
			var tabs=document.querySelectorAll('.ce-tab'),panels=document.querySelectorAll('.ce-panel'),clave='ceEventosTab',activa=null;
			try{activa=localStorage.getItem(clave);}catch(e){}
			if(location.hash&&document.getElementById(location.hash.slice(1))){activa=location.hash.slice(1);}
			if(!activa||!document.getElementById(activa)){activa='ce-t-ajustes';}
			function ir(id){
				tabs.forEach(function(t){var on=t.dataset.ceTab===id;t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false');});
				panels.forEach(function(p){p.classList.toggle('active',p.id===id);});
				try{localStorage.setItem(clave,id);}catch(e){}
			}
			tabs.forEach(function(t){t.addEventListener('click',function(){ir(t.dataset.ceTab);history.replaceState(null,'','#'+t.dataset.ceTab);});});
			document.querySelectorAll('.ce-pills').forEach(function(g){var ls=g.querySelectorAll('label');g.querySelectorAll('input').forEach(function(i){i.addEventListener('change',function(){ls.forEach(function(l){l.classList.remove('on');});i.closest('label').classList.add('on');});});});
			ir(activa);
		})();
		</script>
		<?php
	}

	// ── Sistema visual compartido de los plugins de Caracool ────────────

	/** Estilos del panel. Públicos para que los usen también las cajas del editor. */
	public static function estilos_admin() {
		return '
		.ce-wrap{--ce-acento:#c1502e;--ce-acento-ink:#7a3319;--ce-acento-suave:#f4e3dc;
			--ce-ok:#2f7a4f;--ce-ok-suave:#e3f1e8;
			--ce-tinta:#1c1b19;--ce-tinta-suave:#6b6660;--ce-tinta-tenue:#a29c93;
			--ce-panel:#ffffff;--ce-fondo:#f6f5f2;--ce-linea:#e7e4de;
			--ce-r-grande:16px;--ce-r-medio:10px;--ce-r-chico:7px;
			--ce-sombra:0 1px 2px rgba(28,27,25,.04), 0 8px 24px -12px rgba(28,27,25,.12);
			max-width:1040px;margin:20px 20px 40px 0;color:var(--ce-tinta);
			font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
		.ce-wrap *{box-sizing:border-box;}
		.ce-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:6px 4px 18px;margin-bottom:6px;}
		.ce-brand{display:flex;align-items:center;gap:16px;}
		.ce-logo{height:30px;width:auto;display:block;flex:none;}
		.ce-brand-txt{border-left:1px solid var(--ce-linea);padding-left:16px;}
		.ce-brand h1{margin:0;padding:0;font-size:19px;line-height:1.2;display:flex;align-items:center;gap:10px;}
		.ce-brand p{margin:3px 0 0;font-size:12.5px;color:var(--ce-tinta-suave);}
		.ce-badge{display:inline-block;font-size:11px;font-weight:600;letter-spacing:.03em;padding:3px 9px;border-radius:var(--ce-r-chico);}
		.ce-badge-ver{background:var(--ce-fondo);color:var(--ce-tinta-suave);}
		.ce-chip{display:inline-flex;align-items:center;gap:7px;background:var(--ce-ok-suave);color:var(--ce-ok);font-size:12px;font-weight:600;padding:6px 13px;border-radius:999px;}
		.ce-chip i{width:6px;height:6px;border-radius:50%;background:var(--ce-ok);display:block;}
		.ce-aviso{background:var(--ce-ok-suave);color:var(--ce-ok);border-radius:var(--ce-r-medio);padding:12px 16px;margin-bottom:16px;font-size:13.5px;}
		.ce-aviso.ce-aviso-mal{background:#fbe9e6;color:#9b2c1c;}
		.ce-tabs{display:flex;flex-wrap:wrap;gap:4px;background:var(--ce-panel);border:1px solid var(--ce-linea);border-radius:var(--ce-r-medio);padding:4px;margin-bottom:22px;width:max-content;max-width:100%;}
		.ce-tab{display:flex;align-items:center;gap:7px;border:0;background:transparent;font:inherit;font-size:13.5px;font-weight:560;color:var(--ce-tinta-suave);padding:8px 14px;border-radius:7px;cursor:pointer;}
		.ce-tab svg{width:16px;height:16px;opacity:.75;}
		.ce-tab:hover{background:#f1efeb;color:var(--ce-tinta);}
		.ce-tab.active{background:var(--ce-tinta);color:#fff;}
		.ce-tab.active svg{opacity:1;}
		.ce-tab:focus-visible{outline:2px solid var(--ce-tinta);outline-offset:2px;}
		.ce-panel{display:none;}
		.ce-panel.active{display:block;}
		.ce-card{background:var(--ce-panel);border:1px solid var(--ce-linea);border-radius:var(--ce-r-grande);box-shadow:var(--ce-sombra);padding:22px 24px;margin-bottom:16px;}
		.ce-card-head{display:flex;align-items:center;gap:12px;margin-bottom:6px;}
		.ce-card-icon{width:34px;height:34px;border-radius:50%;background:var(--ce-acento-suave);color:var(--ce-acento);display:flex;align-items:center;justify-content:center;flex:none;}
		.ce-card-icon svg{width:18px;height:18px;}
		.ce-card h2{margin:0;padding:0;font-size:16px;}
		.ce-card-desc{margin:4px 0 14px;font-size:13.5px;line-height:1.55;color:var(--ce-tinta-suave);max-width:70ch;}
		.ce-campo{display:grid;grid-template-columns:220px 1fr;gap:14px;align-items:start;padding:14px 0;border-top:1px solid var(--ce-linea);}
		.ce-card-desc + .ce-campo,.ce-card-head + .ce-campo{border-top:0;}
		.ce-campo > label,.ce-label{font-size:13.5px;font-weight:600;padding-top:7px;display:block;}
		.ce-campo-sw{grid-template-columns:1fr auto;align-items:center;}
		.ce-campo-sw .ce-label{padding-top:0;}
		.ce-hint{display:block;margin-top:6px;font-size:12.5px;line-height:1.5;color:var(--ce-tinta-tenue);max-width:60ch;}
		.ce-hint code,.ce-card code,.ce-code{background:var(--ce-fondo);padding:2px 6px;border-radius:4px;color:var(--ce-tinta);font-size:12.5px;}
		.ce-wrap select,.ce-wrap input[type=number],.ce-wrap input[type=text],.ce-wrap input[type=url],.ce-wrap input[type=date],.ce-wrap input[type=time],.ce-wrap textarea{
			border:1px solid var(--ce-linea);border-radius:var(--ce-r-medio);padding:8px 11px;font-size:13.5px;
			width:100%;max-width:440px;background:#fdfcfb;color:var(--ce-tinta);line-height:1.4;min-height:38px;}
		.ce-wrap textarea{max-width:none;font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-size:12.5px;}
		.ce-wrap input:focus,.ce-wrap select:focus,.ce-wrap textarea:focus{outline:none;border-color:var(--ce-tinta);box-shadow:0 0 0 3px rgba(28,27,25,.08);}
		.ce-pills{display:inline-flex;flex-wrap:wrap;border:1px solid var(--ce-linea);border-radius:999px;padding:3px;gap:2px;background:#fdfcfb;}
		.ce-pills label{position:relative;font-size:12.5px;font-weight:560;color:var(--ce-tinta-suave);padding:6px 12px;border-radius:999px;cursor:pointer;}
		.ce-pills label.on{background:var(--ce-tinta);color:#fff;}
		.ce-pills input{position:absolute;opacity:0;width:0;height:0;}
		.ce-pills label:focus-within{outline:2px solid var(--ce-tinta);outline-offset:1px;}
		.ce-sw{position:relative;display:inline-block;width:44px;height:25px;flex:none;}
		.ce-sw input{opacity:0;width:0;height:0;position:absolute;}
		.ce-sw span{position:absolute;inset:0;background:#cfd3d8;border-radius:999px;transition:background .2s;cursor:pointer;}
		.ce-sw span:before{content:"";position:absolute;width:19px;height:19px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 2px rgba(0,0,0,.2);}
		.ce-sw input:checked + span{background:var(--ce-tinta);}
		.ce-sw input:checked + span:before{transform:translateX(19px);}
		.ce-sw input:focus-visible + span{outline:2px solid var(--ce-tinta);outline-offset:2px;}
		.ce-acciones{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0 0;}
		.ce-btn{display:inline-flex;align-items:center;gap:9px;border:1px solid transparent;border-radius:999px;padding:11px 24px;font-size:13.5px;font-weight:600;cursor:pointer;text-decoration:none;line-height:1.2;}
		.ce-btn svg{width:15px;height:15px;}
		.ce-btn-primario{background:var(--ce-tinta);color:#fff;}
		.ce-btn-primario:hover,.ce-btn-primario:focus{background:#000;color:#fff;}
		.ce-btn-ghost{background:transparent;border-color:var(--ce-linea);color:var(--ce-tinta);}
		.ce-btn-ghost:hover,.ce-btn-ghost:focus{background:#f1efeb;color:var(--ce-tinta);}
		.ce-pie{margin-top:26px;text-align:center;font-size:12px;color:var(--ce-tinta-tenue);}
		.ce-corazon{color:var(--ce-acento);}
		.ce-tabla{width:100%;border-collapse:collapse;font-size:13.5px;margin-top:8px;}
		.ce-tabla th{text-align:left;font-size:12px;color:var(--ce-tinta-tenue);border-bottom:1px solid var(--ce-linea);padding:0 12px 8px 0;font-weight:600;}
		.ce-tabla td{padding:10px 12px 10px 0;border-bottom:1px solid var(--ce-linea);vertical-align:top;color:var(--ce-tinta-suave);}
		@media (max-width:782px){.ce-campo{grid-template-columns:1fr;}.ce-wrap{margin-right:10px;}}
		';
	}

	/** Iconos de trazo para pestañas y tarjetas. */
	public static function icono( $n ) {
		$d = array(
			'ajustes'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
			'uso'      => '<path d="M4 6h16M4 12h16M4 18h10"/>',
			'importar' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 21h14"/>',
			'enlace'   => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
			'google'   => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
			'ticket'   => '<path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"/><path d="M13 5v14" stroke-dasharray="2 2"/>',
			'reloj'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'ok'       => '<path d="m20 6-11 11-5-5"/>',
			'calendario' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
			'lugar'    => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		);
		$p = isset( $d[ $n ] ) ? $d[ $n ] : '';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
	}

	/** Logotipo de Caracool incrustado, sin archivo de imagen aparte. */
	public static function logo_svg() {
		$p = array(
			'M39.23,9.44c0,4.65-.8,7.71-2.13,9.98-1.6-1.2-3.99-2.26-6.92-2.26-7.58,0-14.5,6.92-14.5,35.91,0,23.14,2.93,30.06,10.37,30.06,4.12,0,7.85-.67,10.37-2,1.06,2.26,2,5.59,2,10.37,0,3.86-6.12,8.25-14.1,8.25-15.16,0-24.34-3.99-24.34-45.49C0,10.64,14.63,2.66,26.6,2.66c11.31,0,12.64,3.86,12.64,6.78',
			'M41.9,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.89-.66-18.89-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M65.44,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.67,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82',
			'M88.31,35.24c6.12-4.26,11.04-5.98,18.49-5.98s8.91,1.33,8.91,5.98c0,2.39-.27,5.98-1.46,9.31-1.86-.93-3.59-1.06-4.92-1.06-1.6,0-4.12.8-5.72,3.06l-.13,48.54q0,2.66-15.16,2.66v-62.51Z',
			'M119.96,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.88-.66-18.88-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M143.51,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.66,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82',
			'M202.69,36.97c-2.53-1.86-4.65-3.59-9.84-3.59-9.44,0-20.22,3.99-20.22,33.12,0,26.6,6.92,29.39,17.82,29.39,5.19,0,9.44-2.39,12.5-4.92.53.93.8,2.13.8,3.06,0,1.73-6.38,5.99-13.7,5.99-12.9,0-21.81-2.53-21.81-34.05,0-33.12,13.43-36.71,24.21-36.71,5.59,0,11.17,2.79,11.17,4.26,0,1.06-.27,2.53-.93,3.46',
			'M234.21,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M233.01,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06',
			'M286.88,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M285.68,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06',
			'M325.71,96.29c0,1.46.27,1.46-4.39,1.46V2.39c0-2.39,1.07-2.39,4.39-2.39v96.29Z',
		);
		$svg = '<svg class="ce-logo" viewBox="0 0 325.72 100.01" role="img" aria-label="Caracool">';
		foreach ( $p as $d ) {
			$svg .= '<path d="' . $d . '" fill="#1a1a1a"/>';
		}
		return $svg . '</svg>';
	}

	// ── Comprobador de actualizaciones contra las releases de GitHub ────

	private function ultima_release() {
		$cache = get_site_transient( 'caracool_eventos_release' );
		if ( false !== $cache ) {
			return $cache;
		}
		$r = wp_remote_get(
			'https://api.github.com/repos/' . CARACOOL_EVENTOS_REPO . '/releases/latest',
			array(
				'timeout' => 8,
				'headers' => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);
		if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
			set_site_transient( 'caracool_eventos_release', array(), 30 * MINUTE_IN_SECONDS );
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		set_site_transient( 'caracool_eventos_release', $data, 6 * HOUR_IN_SECONDS );
		return $data;
	}

	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$rel = $this->ultima_release();
		if ( empty( $rel['tag_name'] ) ) {
			return $transient;
		}
		$nueva = ltrim( $rel['tag_name'], 'vV' );
		if ( version_compare( $nueva, CARACOOL_EVENTOS_VERSION, '<=' ) ) {
			return $transient;
		}
		$zip = '';
		if ( ! empty( $rel['assets'][0]['browser_download_url'] ) ) {
			$zip = $rel['assets'][0]['browser_download_url'];
		} elseif ( ! empty( $rel['zipball_url'] ) ) {
			$zip = $rel['zipball_url'];
		}
		$slug                         = plugin_basename( CARACOOL_EVENTOS_FILE );
		$item                         = new stdClass();
		$item->slug                   = 'caracool-eventos';
		$item->plugin                 = $slug;
		$item->new_version            = $nueva;
		$item->url                    = 'https://github.com/' . CARACOOL_EVENTOS_REPO;
		$item->package                = $zip;
		$item->tested                 = get_bloginfo( 'version' );
		$transient->response[ $slug ] = $item;
		return $transient;
	}

	public function update_details( $res, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'caracool-eventos' !== $args->slug ) {
			return $res;
		}
		$rel            = $this->ultima_release();
		$info           = new stdClass();
		$info->name     = 'Caracool Eventos';
		$info->slug     = 'caracool-eventos';
		$info->version  = ! empty( $rel['tag_name'] ) ? ltrim( $rel['tag_name'], 'vV' ) : CARACOOL_EVENTOS_VERSION;
		$info->author   = '<a href="https://caracool.net">Caracool</a>';
		$info->homepage = 'https://github.com/' . CARACOOL_EVENTOS_REPO;
		$info->sections = array(
			'description' => 'Conciertos y eventos con categorías, lugares, venta de entradas y datos estructurados para Google.',
			'changelog'   => ! empty( $rel['body'] ) ? nl2br( esc_html( $rel['body'] ) ) : 'Sin notas.',
		);
		if ( ! empty( $rel['assets'][0]['browser_download_url'] ) ) {
			$info->download_link = $rel['assets'][0]['browser_download_url'];
		}
		return $info;
	}
}

new Caracool_Eventos();

// ── Módulos ─────────────────────────────────────────────────────────────
foreach ( array( 'ce-tipos.php', 'ce-lugares.php', 'ce-datos.php', 'ce-schema.php', 'ce-listado.php', 'ce-ficha.php', 'ce-importar.php' ) as $modulo ) {
	$ruta = CARACOOL_EVENTOS_DIR . 'modules/' . $modulo;
	if ( file_exists( $ruta ) ) {
		require_once $ruta;
	}
}

register_activation_hook( __FILE__, 'caracool_eventos_activar' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/** Al activar: registra los tipos y rehace las reglas de las URL. */
function caracool_eventos_activar() {
	if ( function_exists( 'caracool_eventos_registrar_tipos' ) ) {
		caracool_eventos_registrar_tipos();
	}
	flush_rewrite_rules();
}
