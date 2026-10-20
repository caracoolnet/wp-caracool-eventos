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

		$this->start_controls_section( 'estilo_fecha', array( 'label' => 'Fecha', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'color_fecha',
			array(
				'label'     => 'Color',
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'active' => true ),
				'selectors' => array( '{{WRAPPER}} .ce-fecha' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_semana', 'label' => 'Día de la semana', 'selector' => '{{WRAPPER}} .ce-semana' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_dia', 'label' => 'Día', 'selector' => '{{WRAPPER}} .ce-dia' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'tipo_mes', 'label' => 'Mes (el tamaño lo ajusta solo al ancho del día)', 'selector' => '{{WRAPPER}} .ce-mes' ) );
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
