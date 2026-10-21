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

	protected function register_controls() {
		$this->start_controls_section( 'contenido', array( 'label' => 'Ficha' ) );
		$this->add_control(
			'nota',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => 'Enseña la fecha, el lugar, el estado, el precio y el botón de compra del evento que se está viendo. Pensado para la plantilla de entrada de los eventos en el Maquetador de temas.',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'estilo', array( 'label' => 'Ficha', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_etiquetas',
			array(
				'label'     => 'Color de las etiquetas',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-ficha dt' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_etiquetas', 'label' => 'Etiquetas', 'selector' => '{{WRAPPER}} .ce-ficha dt' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_valores', 'label' => 'Datos', 'selector' => '{{WRAPPER}} .ce-ficha dd' ) );
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
		echo caracool_eventos_ficha( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
