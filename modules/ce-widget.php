<?php
/**
 * Caracool Eventos · widgets de Elementor.
 *
 * Solo se carga dentro de `elementor/widgets/register`, cuando las clases de
 * Elementor ya existen. Los dos widgets pintan lo mismo que los shortcodes.
 *
 * Cada texto tiene en Estilo los mismos controles que el Encabezado de
 * Elementor, en el mismo orden: alineación, tipografía, trazo, sombra, modo de
 * fusión y color en Normal y Al pasar el cursor. Los nombres internos de los
 * controles que ya existían (tipo_titulo, color_titulo…) no cambian, para que
 * las webs que ya los usan conserven sus ajustes al actualizar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;

/** Controles comunes de los dos widgets. */
trait Caracool_Eventos_Controles {

	/** Etiquetas HTML que ofrece el Encabezado de Elementor. */
	protected function etiquetas_html() {
		return array(
			'h1'   => 'H1',
			'h2'   => 'H2',
			'h3'   => 'H3',
			'h4'   => 'H4',
			'h5'   => 'H5',
			'h6'   => 'H6',
			'div'  => 'div',
			'span' => 'span',
			'p'    => 'p',
		);
	}

	/** «.a, .b» → «{{WRAPPER}} .a, {{WRAPPER}} .b». */
	protected function sel( $lista ) {
		return implode( ', ', array_map( function ( $s ) {
			return '{{WRAPPER}} ' . $s;
		}, (array) $lista ) );
	}

	/**
	 * Una sección de Estilo con los controles del Encabezado de Elementor.
	 *
	 * @param string $id    Prefijo de los controles nuevos.
	 * @param string $label Título de la sección.
	 * @param array  $sel   Selectores del texto.
	 * @param array  $o     tipo (nombre del grupo de tipografía), color (id del
	 *                      color normal), hover (selectores al pasar el cursor),
	 *                      alinear (bool), condition, nota.
	 */
	protected function seccion_texto( $id, $label, $sel, $o = array() ) {
		$o     = wp_parse_args(
			$o,
			array(
				'tipo'      => $id . '_tipo',
				'color'     => $id . '_color',
				'hover'     => null,
				'alinear'   => false,
				'condition' => array(),
				'nota'      => '',
			)
		);
		$s     = $this->sel( $sel );
		$hover = $this->sel( null === $o['hover'] ? array_map( function ( $x ) {
			return $x . ':hover';
		}, (array) $sel ) : $o['hover'] );

		$this->start_controls_section( $id, array( 'label' => $label, 'tab' => Controls_Manager::TAB_STYLE, 'condition' => $o['condition'] ) );
		if ( $o['nota'] ) {
			$this->add_control( $id . '_nota', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => $o['nota'], 'content_classes' => 'elementor-descriptor' ) );
		}
		if ( $o['alinear'] ) {
			$this->add_responsive_control(
				$id . '_alinear',
				array(
					'label'     => 'Alineación',
					'type'      => Controls_Manager::CHOOSE,
					'options'   => array(
						'left'    => array( 'title' => 'Izquierda', 'icon' => 'eicon-text-align-left' ),
						'center'  => array( 'title' => 'Centro', 'icon' => 'eicon-text-align-center' ),
						'right'   => array( 'title' => 'Derecha', 'icon' => 'eicon-text-align-right' ),
						'justify' => array( 'title' => 'Justificado', 'icon' => 'eicon-text-align-justify' ),
					),
					'selectors' => array( $s => 'text-align: {{VALUE}};' ),
					'separator' => 'after',
				)
			);
		}
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $o['tipo'], 'selector' => $s ) );
		$this->add_group_control( Group_Control_Text_Stroke::get_type(), array( 'name' => $id . '_trazo', 'selector' => $s ) );
		$this->add_group_control( Group_Control_Text_Shadow::get_type(), array( 'name' => $id . '_sombra', 'selector' => $s ) );
		$this->add_control(
			$id . '_fusion',
			array(
				'label'     => 'Modo de fusión',
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					''            => 'Normal',
					'multiply'    => 'Multiplicar',
					'screen'      => 'Pantalla',
					'overlay'     => 'Superponer',
					'darken'      => 'Oscurecer',
					'lighten'     => 'Aclarar',
					'color-dodge' => 'Sobreexponer color',
					'saturation'  => 'Saturación',
					'color'       => 'Color',
					'difference'  => 'Diferencia',
					'exclusion'   => 'Exclusión',
					'hue'         => 'Tono',
					'luminosity'  => 'Luminosidad',
				),
				'selectors' => array( $s => 'mix-blend-mode: {{VALUE}};' ),
				'separator' => 'none',
			)
		);
		$this->start_controls_tabs( $id . '_estados', array( 'separator' => 'before' ) );
		$this->start_controls_tab( $id . '_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			$o['color'],
			array(
				'label'     => 'Color de texto',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( $s => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();
		$this->start_controls_tab( $id . '_hover', array( 'label' => 'Al pasar el cursor' ) );
		$this->add_control(
			$id . '_color_hover',
			array(
				'label'     => 'Color de texto',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( $hover => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			$id . '_transicion',
			array(
				'label'      => 'Duración de la transición',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 's', 'ms' ),
				'range'      => array(
					's'  => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ),
					'ms' => array( 'min' => 0, 'max' => 3000, 'step' => 100 ),
				),
				'selectors'  => array( $s => 'transition-property: color; transition-duration: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	/** Un color que se guarda en una variable CSS de la raíz. */
	protected function color_var( $id, $label, $var, $cond = array() ) {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} ' . $this->raiz => $var . ': {{VALUE}};' ),
				'condition' => $cond,
			)
		);
	}

	/** Un tamaño, distinto por dispositivo, que se guarda en una variable CSS de la raíz. */
	protected function tam_var( $id, $label, $var, $cond = array(), $max_px = 200, $max_em = 12, $unidades = array( 'px', 'em' ) ) {
		$this->add_responsive_control(
			$id,
			array(
				'label'      => $label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => $unidades,
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => $max_px ),
					'em' => array( 'min' => 0, 'max' => $max_em, 'step' => 0.05 ),
					'%'  => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array( '{{WRAPPER}} ' . $this->raiz => $var . ': {{SIZE}}{{UNIT}};' ),
				'condition'  => $cond,
			)
		);
	}

	protected function titulo_ctrl( $id, $texto, $cond = array() ) {
		$this->add_control(
			$id,
			array(
				'label'     => $texto,
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => $cond,
			)
		);
	}
}

// ── Widget «Eventos»: el listado ───────────────────────────────────────

class Caracool_Eventos_Widget extends \Elementor\Widget_Base {

	use Caracool_Eventos_Controles;

	/** Raíz donde viven las variables CSS del listado. */
	protected $raiz = '.ce-listado';

	public function get_name() {
		return 'caracool_eventos';
	}

	public function get_title() {
		return 'Eventos';
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'eventos', 'conciertos', 'gira', 'fechas', 'caracool' );
	}

	public function get_style_depends() {
		return array( 'caracool-eventos' );
	}

	public function get_script_depends() {
		return array( 'caracool-eventos' );
	}

	protected function register_controls() {
		$cats = array();
		$t    = get_terms( array( 'taxonomy' => 'cc_evento_cat', 'hide_empty' => false ) );
		if ( $t && ! is_wp_error( $t ) ) {
			foreach ( $t as $term ) {
				$cats[ $term->slug ] = $term->name;
			}
		}

		// ── Contenido ──
		$this->start_controls_section( 'contenido', array( 'label' => 'Eventos' ) );
		$this->add_control(
			'categoria',
			array(
				'label'       => 'Categorías',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $cats,
				'label_block' => true,
				'description' => 'Vacío enseña todas.',
			)
		);
		$this->add_control(
			'pasados',
			array(
				'label'   => 'Qué eventos',
				'type'    => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => array(
					'no'    => 'Próximos',
					'solo'  => 'Ya pasados (histórico)',
					'todos' => 'Todos',
				),
			)
		);
		$this->add_control(
			'limite',
			array(
				'label'       => 'Cuántos',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => '',
				'description' => 'Vacío o 0 enseña todos.',
			)
		);
		$this->add_control(
			'filtro',
			array(
				'label'        => 'Filtro por categoría',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => 'Solo aparece si los eventos mostrados tienen más de una categoría.',
			)
		);
		$this->add_control(
			'destacar',
			array(
				'label'   => 'Qué se lee primero',
				'type'    => Controls_Manager::SELECT,
				'default' => 'titulo',
				'options' => array(
					'titulo' => 'El título del evento',
					'ciudad' => 'La ciudad',
				),
			)
		);
		$this->add_control(
			'etiqueta',
			array(
				'label'   => 'Etiqueta HTML del título',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => $this->etiquetas_html(),
			)
		);
		$this->add_control(
			'fecha',
			array(
				'label'       => 'Diseño de la fecha',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'clasico',
				'separator'   => 'before',
				'options'     => array(
					'clasico'    => 'Clásico',
					'calendario' => 'Calendario',
					'bloque'     => 'Bloque de color',
					'contorno'   => 'Contorno',
					'linea'      => 'En línea',
				),
				'description' => 'La caja de cada diseño y los textos de la fecha se ajustan en la pestaña Estilo.',
			)
		);
		$this->add_control(
			'semana',
			array(
				'label'   => 'Día de la semana',
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''         => 'Según el diseño',
					'corto'    => 'Abreviado (vie)',
					'completo' => 'Entero (viernes)',
				),
			)
		);
		$this->add_control(
			'anio',
			array(
				'label'        => 'Mostrar el año',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => 'Útil en el histórico, donde se mezclan años.',
			)
		);
		$this->add_control(
			'vacio',
			array(
				'label'       => 'Texto sin eventos',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'No hay fechas a la vista. Vuelve pronto.',
				'label_block' => true,
			)
		);
		$this->end_controls_section();

		// ── Estilo ──
		$this->start_controls_section( 'estilo_lista', array( 'label' => 'Lista', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_linea',
			array(
				'label'     => 'Línea entre eventos',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ce-listado' => '--ce-linea: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'espacio',
			array(
				'label'      => 'Aire de cada evento',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .ce-listado' => '--ce-aire: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		$this->cajas_fecha();

		$this->seccion_texto( 'tx_semana', 'Fecha: día de la semana', array( '.ce-semana' ), array( 'tipo' => 'tipo_semana' ) );
		$this->seccion_texto( 'tx_dia', 'Fecha: número', array( '.ce-dia' ), array( 'tipo' => 'tipo_dia' ) );
		$this->seccion_texto(
			'tx_mes',
			'Fecha: mes',
			array( '.ce-mes' ),
			array(
				'tipo' => 'tipo_mes',
				'nota' => 'En el diseño Clásico el mes toma solo el ancho del número. Para darle un tamaño a mano, enciende «Tamaño del mes a mano» en «Fecha: caja».',
			)
		);
		$this->seccion_texto( 'tx_anio', 'Fecha: año', array( '.ce-anio' ), array( 'tipo' => 'tipo_anio', 'condition' => array( 'anio' => 'yes' ) ) );
		$this->seccion_texto(
			'tx_titulo',
			'Título',
			array( '.ce-titulo', '.ce-titulo a' ),
			array(
				'tipo'    => 'tipo_titulo',
				'color'   => 'color_titulo',
				'hover'   => array( '.ce-titulo a:hover', '.ce-titulo a:focus-visible' ),
				'alinear' => true,
			)
		);
		$this->seccion_texto( 'tx_meta', 'Lugar y hora', array( '.ce-meta' ), array( 'tipo' => 'tipo_meta', 'color' => 'color_meta', 'alinear' => true ) );

		$this->start_controls_section( 'estilo_filtro', array( 'label' => 'Filtro', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_filtro',
			array(
				'label'     => 'Color',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-listado' => '--ce-filtro: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'color_filtro_txt',
			array(
				'label'     => 'Texto del activo',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-listado' => '--ce-filtro-txt: {{VALUE}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_filtro', 'label' => 'Tipografía', 'selector' => '{{WRAPPER}} .ce-filtro-btn' ) );
		$this->end_controls_section();
	}

	/**
	 * La caja de cada diseño de fecha: anchos, fondos, bordes y esquinas. Los
	 * textos de la fecha tienen sus propias secciones.
	 */
	private function cajas_fecha() {
		$tab = Controls_Manager::TAB_STYLE;

		$c = array( 'fecha' => 'clasico' );
		$this->start_controls_section( 'd_clasico', array( 'label' => 'Fecha: caja', 'tab' => $tab, 'condition' => $c ) );
		$this->add_control(
			'cl_mes_manual',
			array(
				'label'        => 'Tamaño del mes a mano',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'ce-mes-manual-',
				'description'  => 'Apagado, el mes toma solo el ancho del número. Encendido, manda el tamaño de «Fecha: mes».',
			)
		);
		$this->tam_var( 'cl_col', 'Ancho de la columna de la fecha', '--ce-col', $c, 300, 20 );
		$this->end_controls_section();

		$c = array( 'fecha' => 'calendario' );
		$this->start_controls_section( 'd_calendario', array( 'label' => 'Fecha: caja', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'cal_ancho', 'Ancho de la hoja', '--ce-cal-ancho', $c, 300, 20 );
		$this->color_var( 'cal_fondo', 'Fondo', '--ce-cal-fondo', $c );
		$this->color_var( 'cal_tinta', 'Color del número y el mes', '--ce-cal-tinta', $c );
		$this->titulo_ctrl( 'cal_t_borde', 'Borde', $c );
		$this->tam_var( 'cal_borde', 'Grosor', '--ce-cal-borde', $c, 20, 2 );
		$this->color_var( 'cal_borde_color', 'Color', '--ce-cal-borde-color', $c );
		$this->tam_var( 'cal_radio', 'Radio de las esquinas', '--ce-cal-radio', $c, 80, 5 );
		$this->titulo_ctrl( 'cal_t_franja', 'Franja del día de la semana', $c );
		$this->color_var( 'cal_franja', 'Fondo', '--ce-cal-franja', $c );
		$this->color_var( 'cal_franja_txt', 'Texto', '--ce-cal-franja-txt', $c );
		$this->tam_var( 'cal_franja_alto', 'Alto (aire arriba y abajo)', '--ce-cal-franja-alto', $c, 40, 3 );
		$this->end_controls_section();

		$c = array( 'fecha' => 'bloque' );
		$this->start_controls_section( 'd_bloque', array( 'label' => 'Fecha: caja', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'bl_tam', 'Tamaño del bloque', '--ce-bl-tam', $c, 300, 20 );
		$this->color_var( 'bl_fondo', 'Fondo', '--ce-bl-fondo', $c );
		$this->color_var( 'bl_tinta', 'Color del texto', '--ce-bl-tinta', $c );
		$this->tam_var( 'bl_radio', 'Radio de las esquinas', '--ce-bl-radio', $c, 150, 10 );
		$this->end_controls_section();

		$c = array( 'fecha' => 'contorno' );
		$this->start_controls_section( 'd_contorno', array( 'label' => 'Fecha: caja', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'co_tam', 'Ancho de la caja', '--ce-co-tam', $c, 300, 20 );
		$this->color_var( 'co_tinta', 'Color del texto', '--ce-co-tinta', $c );
		$this->titulo_ctrl( 'co_t_borde', 'Borde', $c );
		$this->tam_var( 'co_borde', 'Grosor', '--ce-co-borde', $c, 20, 2 );
		$this->color_var( 'co_borde_color', 'Color', '--ce-co-borde-color', $c );
		$this->tam_var( 'co_radio', 'Radio de las esquinas', '--ce-co-radio', $c, 150, 10 );
		$this->end_controls_section();

		$c = array( 'fecha' => 'linea' );
		$this->start_controls_section( 'd_linea', array( 'label' => 'Fecha: caja', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'li_col', 'Ancho de la columna de la fecha', '--ce-li-col', $c, 400, 25 );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo caracool_eventos_render( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'categoria' => is_array( $s['categoria'] ) ? implode( ',', $s['categoria'] ) : (string) $s['categoria'],
				'filtro'    => 'yes' === $s['filtro'] ? 'si' : 'no',
				'limite'    => (int) $s['limite'],
				'pasados'   => $s['pasados'],
				'destacar'  => $s['destacar'],
				'vacio'     => $s['vacio'],
				'fecha'     => isset( $s['fecha'] ) ? $s['fecha'] : 'clasico',
				'semana'    => isset( $s['semana'] ) ? $s['semana'] : '',
				'etiqueta'  => isset( $s['etiqueta'] ) ? $s['etiqueta'] : 'h3',
				'anio'      => isset( $s['anio'] ) && 'yes' === $s['anio'] ? 'si' : 'no',
			)
		);
	}
}

// ── Widget «Ficha del evento» ──────────────────────────────────────────

class Caracool_Evento_Ficha_Widget extends \Elementor\Widget_Base {

	use Caracool_Eventos_Controles;

	/** Raíz donde viven las variables CSS de la ficha. */
	protected $raiz = '.ce-ficha';

	public function get_name() {
		return 'caracool_evento_ficha';
	}

	public function get_title() {
		return 'Ficha del evento';
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'evento', 'concierto', 'ficha', 'entradas', 'caracool' );
	}

	public function get_style_depends() {
		return array( 'caracool-eventos' );
	}

	public function get_script_depends() {
		return array( 'caracool-eventos' );
	}

	private function interruptor( $id, $label, $descripcion = '' ) {
		$this->add_control(
			$id,
			array(
				'label'        => $label,
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => $descripcion,
			)
		);
	}

	protected function register_controls() {
		$hoja = array( 'diseno' => array( 'columnas', 'entrada' ) );
		$ent  = array( 'diseno' => 'entrada' );

		// ── Contenido ──
		$this->start_controls_section( 'contenido', array( 'label' => 'Ficha' ) );
		$this->add_control(
			'diseno',
			array(
				'label'       => 'Diseño de la ficha',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'lista',
				'options'     => array(
					'lista'    => 'Lista',
					'columnas' => 'Dos columnas',
					'entrada'  => 'Entrada',
				),
				'description' => 'Con «Dos columnas» la ficha ya pinta el texto del evento: en la plantilla no hace falta el widget de contenido.',
			)
		);
		$this->add_control(
			'etiqueta_lugar',
			array(
				'label'     => 'Etiqueta HTML del lugar',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => $this->etiquetas_html(),
				'condition' => $ent,
			)
		);
		$this->interruptor( 'iconos', 'Iconos' );
		$this->interruptor( 'categoria', 'Categoría como etiqueta' );
		$this->interruptor( 'puertas', 'Apertura de puertas', 'Si el evento la tiene: «Puertas 21:00 · Concierto 22:00».' );
		$this->interruptor( 'cuenta', 'Cuenta atrás', '«Faltan 16 días», «Es mañana», «Es hoy».' );
		$this->interruptor( 'calendario', 'Añadir al calendario', 'Google Calendar y archivo .ics para Apple y Outlook.' );
		$this->interruptor( 'compartir', 'Compartir', 'WhatsApp, copiar enlace y, en el móvil, su menú de compartir.' );
		$this->interruptor( 'mapa', 'Mapa', 'Se carga al pulsar «Ver mapa», no al abrir la página.' );
		$this->interruptor( 'mas', 'Más fechas', 'Las próximas de la misma categoría.' );
		$this->add_control(
			'mas_limite',
			array(
				'label'     => 'Cuántas fechas',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 20,
				'default'   => 3,
				'condition' => array( 'mas' => 'yes' ),
			)
		);
		$this->add_control(
			'etiqueta_mas',
			array(
				'label'     => 'Etiqueta HTML de «Más fechas»',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => $this->etiquetas_html(),
				'condition' => array( 'mas' => 'yes' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'textos', array( 'label' => 'Textos' ) );
		$textos = array(
			't_cuando'     => array( 'Cuándo', 'Cuándo' ),
			't_donde'      => array( 'Dónde', 'Dónde' ),
			't_estado'     => array( 'Estado', 'Estado' ),
			't_precio'     => array( 'Precio', 'Precio' ),
			't_puertas'    => array( 'Puertas', 'Puertas' ),
			't_concierto'  => array( 'Concierto', 'Concierto (junto a la hora)' ),
			't_lugar'      => array( 'Lugar', 'Antetítulo del lugar (Entrada)' ),
			't_llegar'     => array( 'Cómo llegar', 'Botón del mapa (Entrada)' ),
			't_desde'      => array( 'Entradas desde', 'Antes del precio (Entrada)' ),
			't_calendario' => array( 'Añadir al calendario', 'Menú de calendario (Entrada)' ),
			't_compartir'  => array( 'Compartir', 'Menú de compartir (Entrada)' ),
			't_mas'        => array( 'Más fechas de la gira', 'Título de más fechas' ),
			't_mapa'       => array( 'Ver mapa', 'Botón del mapa al pulsar' ),
		);
		foreach ( $textos as $k => $t ) {
			$this->add_control(
				$k,
				array(
					'label'   => $t[1],
					'type'    => Controls_Manager::TEXT,
					'default' => $t[0],
				)
			);
		}
		$this->end_controls_section();

		// ── Estilo: cajas ──
		$tab = Controls_Manager::TAB_STYLE;

		$this->start_controls_section( 'estilo', array( 'label' => 'General', 'tab' => $tab ) );
		$this->color_var( 'color_acento', 'Acento (cuenta atrás y etiquetas)', '--ce-fi-acento' );
		$this->color_var( 'color_iconos', 'Iconos', '--ce-fi-ico' );
		$this->color_var( 'color_lineas', 'Líneas', '--ce-fi-linea' );
		$this->end_controls_section();

		$c = array( 'diseno' => 'lista' );
		$this->start_controls_section( 'e_lista', array( 'label' => 'Diseño: Lista', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'li_etq', 'Ancho de las etiquetas', '--ce-fi-etq', $c, 400, 25 );
		$this->tam_var( 'li_aire', 'Aire de cada fila', '--ce-fi-aire', $c, 80, 5 );
		$this->end_controls_section();

		$c = array( 'diseno' => 'columnas' );
		$this->start_controls_section( 'e_columnas', array( 'label' => 'Diseño: Dos columnas', 'tab' => $tab, 'condition' => $c ) );
		$this->tam_var( 'ta_ancho', 'Ancho de la tarjeta', '--ce-ta-ancho', $c, 700, 40, array( 'px', 'em', '%' ) );
		$this->tam_var( 'ta_hueco', 'Separación con el texto', '--ce-ta-hueco', $c, 200, 10 );
		$this->color_var( 'ta_fondo', 'Fondo de la tarjeta', '--ce-ta-fondo', $c );
		$this->color_var( 'ta_tinta', 'Texto de la tarjeta', '--ce-ta-tinta', $c );
		$this->tam_var( 'ta_relleno', 'Relleno', '--ce-ta-relleno', $c, 100, 6 );
		$this->tam_var( 'ta_borde', 'Grosor del borde', '--ce-ta-borde', $c, 20, 2 );
		$this->color_var( 'ta_borde_color', 'Color del borde', '--ce-ta-borde-color', $c );
		$this->tam_var( 'ta_radio', 'Radio de las esquinas', '--ce-ta-radio', $c, 80, 5 );
		$this->add_control(
			'ta_sombra',
			array(
				'label'     => 'Sombra',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''                            => 'Suave',
					'0 18px 50px rgba(0,0,0,.14)' => 'Marcada',
					'none'                        => 'Sin sombra',
				),
				'selectors' => array( '{{WRAPPER}} .ce-ficha' => '--ce-ta-sombra: {{VALUE}};' ),
				'condition' => $c,
			)
		);
		$this->tam_var( 'ta_hoja', 'Ancho de la hoja de calendario', '--ce-ta-hoja', $c, 300, 20 );
		$this->end_controls_section();

		$this->start_controls_section( 'e_entrada', array( 'label' => 'Diseño: Entrada', 'tab' => $tab, 'condition' => $ent ) );
		$this->color_var( 'en_fondo', 'Fondo', '--ce-en-fondo', $ent );
		$this->color_var( 'en_tinta', 'Texto', '--ce-en-tinta', $ent );
		$this->color_var( 'en_acento', 'Iconos', '--ce-en-acento', $ent );
		$this->color_var( 'en_corte', 'Línea de corte', '--ce-en-corte', $ent );
		$this->color_var( 'en_compra_fondo', 'Fondo de la zona de compra', '--ce-en-compra-fondo', $ent );
		$this->color_var( 'en_cuenta', 'Fondo de la cuenta atrás', '--ce-en-cuenta', $ent );
		$this->color_var( 'en_cuenta_txt', 'Texto de la cuenta atrás', '--ce-en-cuenta-txt', $ent );
		$this->tam_var( 'en_radio', 'Radio de las esquinas', '--ce-en-radio', $ent, 80, 5 );
		$this->tam_var( 'en_relleno', 'Relleno', '--ce-en-relleno', $ent, 100, 6 );
		$this->tam_var( 'en_compra_ancho', 'Ancho de la zona de compra', '--ce-en-compra-ancho', $ent, 500, 30 );
		$this->tam_var( 'en_hoja', 'Ancho de la hoja de calendario', '--ce-en-hoja', $ent, 300, 20 );
		$this->add_control(
			'en_sombra',
			array(
				'label'     => 'Sombra',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''                             => 'Suave',
					'0 24px 60px rgba(0,0,0,.28)'  => 'Marcada',
					'none'                         => 'Sin sombra',
				),
				'selectors' => array( '{{WRAPPER}} .ce-ficha' => '--ce-en-sombra: {{VALUE}};' ),
				'condition' => $ent,
			)
		);
		$this->titulo_ctrl( 'en_t_menu', 'Menús de calendario y compartir', $ent );
		$this->color_var( 'en_menu_fondo', 'Fondo', '--ce-fi-menu-fondo', $ent );
		$this->color_var( 'en_menu_tinta', 'Texto', '--ce-fi-menu-tinta', $ent );
		$this->color_var( 'en_pildora_borde', 'Borde de los botones', '--ce-fi-pildora-borde', $ent );
		$this->end_controls_section();

		$this->start_controls_section( 'e_hoja', array( 'label' => 'Hoja de calendario', 'tab' => $tab, 'condition' => $hoja ) );
		$this->color_var( 'cal_fondo', 'Fondo', '--ce-cal-fondo', $hoja );
		$this->color_var( 'cal_tinta', 'Número y mes', '--ce-cal-tinta', $hoja );
		$this->color_var( 'cal_franja', 'Fondo de la franja', '--ce-cal-franja', $hoja );
		$this->color_var( 'cal_franja_txt', 'Texto de la franja', '--ce-cal-franja-txt', $hoja );
		$this->color_var( 'cal_borde_color', 'Color del borde', '--ce-cal-borde-color', $hoja );
		$this->tam_var( 'cal_borde', 'Grosor del borde', '--ce-cal-borde', $hoja, 20, 2 );
		$this->tam_var( 'cal_radio', 'Radio de las esquinas', '--ce-cal-radio', $hoja, 80, 5 );
		$this->tam_var( 'cal_semana_tam', 'Tamaño del día de la semana', '--ce-cal-semana-tam', $hoja, 60, 4 );
		$this->tam_var( 'cal_dia_tam', 'Tamaño del número', '--ce-cal-dia-tam', $hoja, 160, 10 );
		$this->tam_var( 'cal_mes_tam', 'Tamaño del mes', '--ce-cal-mes-tam', $hoja, 100, 6 );
		$this->end_controls_section();

		$m = array( 'mapa' => 'yes' );
		$this->start_controls_section( 'e_mapa', array( 'label' => 'Mapa', 'tab' => $tab, 'condition' => $m ) );
		$this->tam_var( 'mapa_alto', 'Alto del mapa', '--ce-fi-mapa-alto', $m, 800, 40 );
		$this->tam_var( 'mapa_radio', 'Radio de las esquinas', '--ce-fi-mapa-radio', $m, 60, 4 );
		$this->end_controls_section();

		// ── Estilo: textos, con los controles del Encabezado ──
		$this->seccion_texto( 'tx_etiquetas', 'Etiquetas («Cuándo», «Dónde»…)', array( '.ce-ficha dt' ), array( 'tipo' => 'tipo_etiquetas', 'color' => 'color_etiquetas', 'condition' => array( 'diseno' => 'lista' ) ) );
		$this->seccion_texto( 'tx_datos', 'Datos', array( '.ce-ficha dd', '.ce-tarjeta', '.ce-entrada-dir' ), array( 'tipo' => 'tipo_valores', 'color' => 'color_datos', 'alinear' => true ) );
		$this->seccion_texto( 'tx_eyebrow', 'Antetítulo del lugar', array( '.ce-entrada-eyebrow' ), array( 'condition' => $ent ) );
		$this->seccion_texto( 'tx_lugar', 'Lugar', array( '.ce-entrada-lugar' ), array( 'condition' => $ent, 'alinear' => true ) );
		$this->seccion_texto( 'tx_horas', 'Horas', array( '.ce-hora-chip', '.ce-tarjeta-hora' ), array( 'condition' => $hoja ) );
		$this->seccion_texto( 'tx_precio', 'Precio', array( '.ce-precio-cifra' ), array( 'condition' => $hoja ) );
		$this->seccion_texto( 'tx_cuenta', 'Cuenta atrás', array( '.ce-ficha .ce-cuenta' ), array( 'color' => 'color_cuenta', 'condition' => array( 'cuenta' => 'yes' ) ) );
		$this->seccion_texto(
			'tx_enlaces',
			'Enlaces («Ver en el mapa», «Cómo llegar»)',
			array( '.ce-ficha a:not(.elementor-button):not(.ce-accion-enlace):not(.ce-mas-ver)' ),
			array( 'color' => 'color_enlaces' )
		);
		$this->seccion_texto( 'tx_acciones', 'Calendario y compartir', array( '.ce-ficha .ce-accion-enlace' ), array( 'tipo' => 'tipo_acciones', 'color' => 'color_acciones' ) );
		$x = array( 'mas' => 'yes' );
		$this->seccion_texto( 'tx_mas_titulo', 'Más fechas: título', array( '.ce-mas-titulo' ), array( 'color' => 'mas_titulo_color', 'condition' => $x, 'alinear' => true ) );
		$this->seccion_texto( 'tx_mas_fecha', 'Más fechas: fechas', array( '.ce-mas-fecha' ), array( 'color' => 'mas_fecha_color', 'condition' => $x ) );
		$this->seccion_texto( 'tx_mas_donde', 'Más fechas: lugares', array( '.ce-mas-donde' ), array( 'condition' => $x ) );
		$this->seccion_texto( 'tx_mas_ver', 'Más fechas: «Ver»', array( '.ce-ficha .ce-mas-ver' ), array( 'condition' => $x ) );
	}

	protected function render() {
		$id = get_the_ID();
		if ( 'cc_evento' !== get_post_type( $id ) ) {
			$ultimo = caracool_eventos_consulta( array( 'limite' => 1 ) );
			$id     = $ultimo ? $ultimo[0]->ID : 0;
			if ( ! $id ) {
				echo '<p class="ce-vacio">La ficha se ve en la página de cada evento.</p>';
				return;
			}
		}
		$s = $this->get_settings_for_display();
		$a = array(
			'id'             => $id,
			'diseno'         => isset( $s['diseno'] ) ? $s['diseno'] : 'lista',
			'mas_limite'     => isset( $s['mas_limite'] ) ? (int) $s['mas_limite'] : 3,
			'etiqueta_lugar' => isset( $s['etiqueta_lugar'] ) ? $s['etiqueta_lugar'] : 'h3',
			'etiqueta_mas'   => isset( $s['etiqueta_mas'] ) ? $s['etiqueta_mas'] : 'h3',
		);
		foreach ( array( 'iconos', 'categoria', 'puertas', 'cuenta', 'calendario', 'compartir', 'mapa', 'mas' ) as $k ) {
			$a[ $k ] = isset( $s[ $k ] ) && 'yes' === $s[ $k ] ? 'si' : 'no';
		}
		foreach ( array( 't_cuando', 't_donde', 't_estado', 't_precio', 't_puertas', 't_concierto', 't_lugar', 't_llegar', 't_desde', 't_calendario', 't_compartir', 't_mas', 't_mapa' ) as $k ) {
			if ( isset( $s[ $k ] ) ) {
				$a[ $k ] = $s[ $k ];
			}
		}
		echo caracool_eventos_ficha( $a ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
