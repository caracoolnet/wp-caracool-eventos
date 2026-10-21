<?php
/**
 * Caracool Eventos · widgets de Elementor.
 *
 * Solo se carga dentro de `elementor/widgets/register`, cuando las clases de
 * Elementor ya existen. Los dos widgets pintan lo mismo que los shortcodes.
 * El contenedor de Elementor decide anchos y márgenes; aquí solo hay opciones
 * de contenido y los colores y tipografías propios del listado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Caracool_Eventos_Widget extends \Elementor\Widget_Base {

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
				'description' => 'Tamaños y colores de cada diseño, en la pestaña Estilo.',
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

		$this->controles_disenos();

		$this->start_controls_section( 'estilo_fecha', array( 'label' => 'Fecha: tipografías', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_fecha',
			array(
				'label'       => 'Color general',
				'type'        => Controls_Manager::COLOR,
				'global'      => array( 'active' => true ),
				'selectors'   => array( '{{WRAPPER}} .ce-fecha' => 'color: {{VALUE}};' ),
				'description' => 'En Calendario y Bloque mandan los colores de su propia sección.',
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_semana', 'label' => 'Día de la semana', 'selector' => '{{WRAPPER}} .ce-semana' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_dia', 'label' => 'Día', 'selector' => '{{WRAPPER}} .ce-dia' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_mes', 'label' => 'Mes', 'selector' => '{{WRAPPER}} .ce-mes' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_anio', 'label' => 'Año', 'selector' => '{{WRAPPER}} .ce-anio' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'estilo_texto', array( 'label' => 'Título y datos', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_titulo',
			array(
				'label'     => 'Color del título',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-titulo, {{WRAPPER}} .ce-titulo a' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_titulo', 'label' => 'Título', 'selector' => '{{WRAPPER}} .ce-titulo' ) );
		$this->add_control(
			'color_meta',
			array(
				'label'     => 'Color de lugar y hora',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-meta' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_meta', 'label' => 'Lugar y hora', 'selector' => '{{WRAPPER}} .ce-meta' ) );
		$this->end_controls_section();

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

	// ── Controles de cada diseño de fecha ──────────────────────────────
	//
	// Cada diseño tiene su sección en Estilo, que solo se ve con ese diseño
	// elegido. Casi todo son variables CSS que lee ce-front.css: así los
	// valores por defecto viven en un solo sitio y el widget solo los pisa.

	/** Un color que se guarda en una variable CSS. */
	private function color_var( $id, $label, $var, $diseno ) {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-listado' => $var . ': {{VALUE}};' ),
				'condition' => array( 'fecha' => $diseno ),
			)
		);
	}

	/** Un tamaño, distinto por dispositivo, que se guarda en una variable CSS. */
	private function tam_var( $id, $label, $var, $diseno, $max_px = 200, $max_em = 12 ) {
		$this->add_responsive_control(
			$id,
			array(
				'label'      => $label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => $max_px ),
					'em' => array( 'min' => 0, 'max' => $max_em, 'step' => 0.05 ),
				),
				'selectors'  => array( '{{WRAPPER}} .ce-listado' => $var . ': {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'fecha' => $diseno ),
			)
		);
	}

	private function titulo_ctrl( $id, $texto, $diseno ) {
		$this->add_control(
			$id,
			array(
				'label'     => $texto,
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'fecha' => $diseno ),
			)
		);
	}

	private function controles_disenos() {
		$tab = Controls_Manager::TAB_STYLE;

		// Clásico.
		$this->start_controls_section( 'd_clasico', array( 'label' => 'Fecha: Clásico', 'tab' => $tab, 'condition' => array( 'fecha' => 'clasico' ) ) );
		$this->add_control(
			'cl_mes_manual',
			array(
				'label'        => 'Tamaño del mes a mano',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'ce-mes-manual-',
				'description'  => 'Apagado, el mes toma solo el ancho del número.',
				'condition'    => array( 'fecha' => 'clasico' ),
			)
		);
		foreach ( array( 'semana' => 'Día de la semana', 'dia' => 'Número', 'mes' => 'Mes' ) as $k => $l ) {
			$this->titulo_ctrl( 'cl_t_' . $k, $l, 'clasico' );
			$this->color_var( 'cl_' . $k . '_color', 'Color', '--ce-cl-' . $k . '-color', 'clasico' );
			$cond = array( 'fecha' => 'clasico' );
			if ( 'mes' === $k ) {
				$cond['cl_mes_manual'] = 'yes';
			}
			$this->add_responsive_control(
				'cl_' . $k . '_tam',
				array(
					'label'      => 'Tamaño',
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'em' ),
					'range'      => array(
						'px' => array( 'min' => 6, 'max' => 160 ),
						'em' => array( 'min' => 0.3, 'max' => 10, 'step' => 0.05 ),
					),
					'selectors'  => array( '{{WRAPPER}} .ce-f-clasico .ce-' . $k => 'font-size: {{SIZE}}{{UNIT}};' ),
					'condition'  => $cond,
				)
			);
		}
		$this->tam_var( 'cl_col', 'Ancho de la columna de la fecha', '--ce-col', 'clasico', 300, 20 );
		$this->end_controls_section();

		// Calendario.
		$this->start_controls_section( 'd_calendario', array( 'label' => 'Fecha: Calendario', 'tab' => $tab, 'condition' => array( 'fecha' => 'calendario' ) ) );
		$this->tam_var( 'cal_ancho', 'Ancho de la hoja', '--ce-cal-ancho', 'calendario', 300, 20 );
		$this->color_var( 'cal_fondo', 'Fondo', '--ce-cal-fondo', 'calendario' );
		$this->color_var( 'cal_tinta', 'Color del número y el mes', '--ce-cal-tinta', 'calendario' );
		$this->titulo_ctrl( 'cal_t_borde', 'Borde', 'calendario' );
		$this->tam_var( 'cal_borde', 'Grosor', '--ce-cal-borde', 'calendario', 20, 2 );
		$this->color_var( 'cal_borde_color', 'Color', '--ce-cal-borde-color', 'calendario' );
		$this->tam_var( 'cal_radio', 'Radio de las esquinas', '--ce-cal-radio', 'calendario', 80, 5 );
		$this->titulo_ctrl( 'cal_t_franja', 'Franja del día de la semana', 'calendario' );
		$this->color_var( 'cal_franja', 'Fondo', '--ce-cal-franja', 'calendario' );
		$this->color_var( 'cal_franja_txt', 'Texto', '--ce-cal-franja-txt', 'calendario' );
		$this->tam_var( 'cal_semana_tam', 'Tamaño del texto', '--ce-cal-semana-tam', 'calendario', 60, 4 );
		$this->tam_var( 'cal_franja_alto', 'Alto (aire arriba y abajo)', '--ce-cal-franja-alto', 'calendario', 40, 3 );
		$this->titulo_ctrl( 'cal_t_dia', 'Número', 'calendario' );
		$this->color_var( 'cal_dia_color', 'Color', '--ce-cal-dia-color', 'calendario' );
		$this->tam_var( 'cal_dia_tam', 'Tamaño', '--ce-cal-dia-tam', 'calendario', 160, 10 );
		$this->titulo_ctrl( 'cal_t_mes', 'Mes', 'calendario' );
		$this->color_var( 'cal_mes_color', 'Color', '--ce-cal-mes-color', 'calendario' );
		$this->tam_var( 'cal_mes_tam', 'Tamaño', '--ce-cal-mes-tam', 'calendario', 100, 6 );
		$this->end_controls_section();

		// Bloque de color.
		$this->start_controls_section( 'd_bloque', array( 'label' => 'Fecha: Bloque de color', 'tab' => $tab, 'condition' => array( 'fecha' => 'bloque' ) ) );
		$this->tam_var( 'bl_tam', 'Tamaño del bloque', '--ce-bl-tam', 'bloque', 300, 20 );
		$this->color_var( 'bl_fondo', 'Fondo', '--ce-bl-fondo', 'bloque' );
		$this->color_var( 'bl_tinta', 'Color del texto', '--ce-bl-tinta', 'bloque' );
		$this->tam_var( 'bl_radio', 'Radio de las esquinas', '--ce-bl-radio', 'bloque', 150, 10 );
		$this->titulo_ctrl( 'bl_t_tam', 'Tamaños', 'bloque' );
		$this->tam_var( 'bl_semana_tam', 'Día de la semana', '--ce-bl-semana-tam', 'bloque', 60, 4 );
		$this->tam_var( 'bl_dia_tam', 'Número', '--ce-bl-dia-tam', 'bloque', 160, 10 );
		$this->tam_var( 'bl_mes_tam', 'Mes', '--ce-bl-mes-tam', 'bloque', 100, 6 );
		$this->end_controls_section();

		// Contorno.
		$this->start_controls_section( 'd_contorno', array( 'label' => 'Fecha: Contorno', 'tab' => $tab, 'condition' => array( 'fecha' => 'contorno' ) ) );
		$this->tam_var( 'co_tam', 'Ancho de la caja', '--ce-co-tam', 'contorno', 300, 20 );
		$this->color_var( 'co_tinta', 'Color del número y el mes', '--ce-co-tinta', 'contorno' );
		$this->color_var( 'co_semana_color', 'Color del día de la semana', '--ce-co-semana-color', 'contorno' );
		$this->titulo_ctrl( 'co_t_borde', 'Borde', 'contorno' );
		$this->tam_var( 'co_borde', 'Grosor', '--ce-co-borde', 'contorno', 20, 2 );
		$this->color_var( 'co_borde_color', 'Color', '--ce-co-borde-color', 'contorno' );
		$this->tam_var( 'co_radio', 'Radio de las esquinas', '--ce-co-radio', 'contorno', 150, 10 );
		$this->titulo_ctrl( 'co_t_tam', 'Tamaños', 'contorno' );
		$this->tam_var( 'co_semana_tam', 'Día de la semana', '--ce-co-semana-tam', 'contorno', 60, 4 );
		$this->tam_var( 'co_dia_tam', 'Número', '--ce-co-dia-tam', 'contorno', 160, 10 );
		$this->tam_var( 'co_mes_tam', 'Mes', '--ce-co-mes-tam', 'contorno', 100, 6 );
		$this->end_controls_section();

		// En línea.
		$this->start_controls_section( 'd_linea', array( 'label' => 'Fecha: En línea', 'tab' => $tab, 'condition' => array( 'fecha' => 'linea' ) ) );
		$this->tam_var( 'li_col', 'Ancho de la columna de la fecha', '--ce-li-col', 'linea', 400, 25 );
		$this->titulo_ctrl( 'li_t_fecha', 'Número y mes', 'linea' );
		$this->color_var( 'li_color', 'Color', '--ce-li-color', 'linea' );
		$this->tam_var( 'li_tam', 'Tamaño', '--ce-li-tam', 'linea', 120, 8 );
		$this->titulo_ctrl( 'li_t_semana', 'Día de la semana', 'linea' );
		$this->color_var( 'li_semana_color', 'Color', '--ce-li-semana-color', 'linea' );
		$this->tam_var( 'li_semana_tam', 'Tamaño', '--ce-li-semana-tam', 'linea', 60, 4 );
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
				'anio'      => isset( $s['anio'] ) && 'yes' === $s['anio'] ? 'si' : 'no',
			)
		);
	}
}

class Caracool_Evento_Ficha_Widget extends \Elementor\Widget_Base {

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

	/** Un color que se guarda en una variable CSS de la ficha. */
	private function color_var( $id, $label, $var, $cond = array() ) {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-ficha' => $var . ': {{VALUE}};' ),
				'condition' => $cond,
			)
		);
	}

	/** Un tamaño, distinto por dispositivo, que se guarda en una variable CSS de la ficha. */
	private function tam_var( $id, $label, $var, $cond = array(), $max_px = 200, $max_em = 12, $unidades = array( 'px', 'em' ) ) {
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
				'selectors'  => array( '{{WRAPPER}} .ce-ficha' => $var . ': {{SIZE}}{{UNIT}};' ),
				'condition'  => $cond,
			)
		);
	}

	private function titulo_ctrl( $id, $texto, $cond = array() ) {
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
		$this->end_controls_section();

		$this->start_controls_section( 'textos', array( 'label' => 'Textos' ) );
		$textos = array(
			't_cuando'    => array( 'Cuándo', 'Cuándo' ),
			't_donde'     => array( 'Dónde', 'Dónde' ),
			't_estado'    => array( 'Estado', 'Estado' ),
			't_precio'    => array( 'Precio', 'Precio' ),
			't_puertas'   => array( 'Puertas', 'Puertas' ),
			't_concierto' => array( 'Concierto', 'Concierto (junto a la hora, con puertas)' ),
			't_mas'       => array( 'Más fechas de la gira', 'Título de más fechas' ),
			't_mapa'      => array( 'Ver mapa', 'Botón del mapa' ),
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

		// ── Estilo ──
		$tab = Controls_Manager::TAB_STYLE;

		$this->start_controls_section( 'estilo', array( 'label' => 'General', 'tab' => $tab ) );
		$this->add_control(
			'color_etiquetas',
			array(
				'label'     => 'Color de las etiquetas',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-ficha dt' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'color_datos',
			array(
				'label'     => 'Color de los datos',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-ficha dd, {{WRAPPER}} .ce-tarjeta' => 'color: {{VALUE}};' ),
			)
		);
		$this->color_var( 'color_acento', 'Acento (cuenta atrás y etiquetas)', '--ce-fi-acento' );
		$this->add_control(
			'color_enlaces',
			array(
				'label'     => 'Enlaces («Ver en el mapa», «Ver»)',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-ficha a:not(.elementor-button):not(.ce-accion-enlace)' => 'color: {{VALUE}};' ),
			)
		);
		$this->color_var( 'color_acciones', 'Calendario y compartir', '--ce-fi-acciones' );
		$this->color_var( 'color_cuenta', 'Cuenta atrás', '--ce-fi-cuenta' );
		$this->color_var( 'color_iconos', 'Iconos', '--ce-fi-ico' );
		$this->color_var( 'color_lineas', 'Líneas', '--ce-fi-linea' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_etiquetas', 'label' => 'Etiquetas', 'selector' => '{{WRAPPER}} .ce-ficha dt' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_valores', 'label' => 'Datos', 'selector' => '{{WRAPPER}} .ce-ficha dd, {{WRAPPER}} .ce-tarjeta, {{WRAPPER}} .ce-entrada-datos p' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_acciones', 'label' => 'Calendario y compartir', 'selector' => '{{WRAPPER}} .ce-ficha .ce-ficha-acciones' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'e_lista', array( 'label' => 'Diseño: Lista', 'tab' => $tab, 'condition' => array( 'diseno' => 'lista' ) ) );
		$this->tam_var( 'li_etq', 'Ancho de las etiquetas', '--ce-fi-etq', array( 'diseno' => 'lista' ), 400, 25 );
		$this->tam_var( 'li_aire', 'Aire de cada fila', '--ce-fi-aire', array( 'diseno' => 'lista' ), 80, 5 );
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
					''                               => 'Suave',
					'0 18px 50px rgba(0,0,0,.14)'    => 'Marcada',
					'none'                           => 'Sin sombra',
				),
				'selectors' => array( '{{WRAPPER}} .ce-ficha' => '--ce-ta-sombra: {{VALUE}};' ),
				'condition' => $c,
			)
		);
		$this->tam_var( 'ta_hoja', 'Ancho de la hoja de calendario', '--ce-ta-hoja', $c, 300, 20 );
		$this->end_controls_section();

		$e = array( 'diseno' => 'entrada' );
		$this->start_controls_section( 'e_entrada', array( 'label' => 'Diseño: Entrada', 'tab' => $tab, 'condition' => $e ) );
		$this->color_var( 'en_fondo', 'Fondo', '--ce-en-fondo', $e );
		$this->color_var( 'en_tinta', 'Texto', '--ce-en-tinta', $e );
		$this->color_var( 'en_corte', 'Línea de corte', '--ce-en-corte', $e );
		$this->color_var( 'en_compra_fondo', 'Fondo de la zona de compra', '--ce-en-compra-fondo', $e );
		$this->color_var( 'en_cuenta', 'Cuenta atrás', '--ce-en-cuenta', $e );
		$this->tam_var( 'en_radio', 'Radio de las esquinas', '--ce-en-radio', $e, 80, 5 );
		$this->tam_var( 'en_relleno', 'Relleno', '--ce-en-relleno', $e, 100, 6 );
		$this->tam_var( 'en_lugar_tam', 'Tamaño del lugar', '--ce-en-lugar-tam', $e, 80, 5 );
		$this->tam_var( 'en_compra_ancho', 'Ancho de la zona de compra', '--ce-en-compra-ancho', $e, 500, 30 );
		$this->tam_var( 'en_hoja', 'Ancho de la hoja de calendario', '--ce-en-hoja', $e, 300, 20 );
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

		$x = array( 'mas' => 'yes' );
		$this->start_controls_section( 'e_mas', array( 'label' => 'Más fechas', 'tab' => $tab, 'condition' => $x ) );
		$this->color_var( 'mas_titulo_color', 'Color del título', '--ce-fi-mas-titulo', $x );
		$this->color_var( 'mas_fecha_color', 'Color de las fechas', '--ce-fi-mas-fecha-color', $x );
		$this->tam_var( 'mas_fecha_tam', 'Tamaño de las fechas', '--ce-fi-mas-fecha', $x, 60, 4 );
		$this->end_controls_section();
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
			'id'         => $id,
			'diseno'     => isset( $s['diseno'] ) ? $s['diseno'] : 'lista',
			'mas_limite' => isset( $s['mas_limite'] ) ? (int) $s['mas_limite'] : 3,
		);
		foreach ( array( 'iconos', 'categoria', 'puertas', 'cuenta', 'calendario', 'compartir', 'mapa', 'mas' ) as $k ) {
			$a[ $k ] = isset( $s[ $k ] ) && 'yes' === $s[ $k ] ? 'si' : 'no';
		}
		foreach ( array( 't_cuando', 't_donde', 't_estado', 't_precio', 't_puertas', 't_concierto', 't_mas', 't_mapa' ) as $k ) {
			if ( isset( $s[ $k ] ) && '' !== $s[ $k ] ) {
				$a[ $k ] = $s[ $k ];
			}
		}
		echo caracool_eventos_ficha( $a ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
