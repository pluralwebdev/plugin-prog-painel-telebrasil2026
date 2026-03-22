<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Filters {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Filtros dropdown
		add_action( 'restrict_manage_posts', array( $this, 'sessao_filters' ) );
		add_action( 'restrict_manage_posts', array( $this, 'participante_filters' ) );

		// Aplicar filtros na query
		add_action( 'pre_get_posts', array( $this, 'apply_sessao_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_participante_filters' ) );

		// Colunas customizadas - Sessoes
		add_filter( 'manage_pt_sessao_posts_columns', array( $this, 'sessao_columns' ) );
		add_action( 'manage_pt_sessao_posts_custom_column', array( $this, 'sessao_column_content' ), 10, 2 );
		add_filter( 'manage_edit-pt_sessao_sortable_columns', array( $this, 'sessao_sortable_columns' ) );

		// Colunas customizadas - Participantes
		add_filter( 'manage_pt_participante_posts_columns', array( $this, 'participante_columns' ) );
		add_action( 'manage_pt_participante_posts_custom_column', array( $this, 'participante_column_content' ), 10, 2 );
		add_filter( 'manage_edit-pt_participante_sortable_columns', array( $this, 'participante_sortable_columns' ) );

		// Ordenacao por meta
		add_action( 'pre_get_posts', array( $this, 'custom_orderby' ) );
	}

	// =========================================================================
	// SESSOES - Filtros
	// =========================================================================

	public function sessao_filters( $post_type ) {
		if ( 'pt_sessao' !== $post_type ) {
			return;
		}

		$this->render_dia_filter();
		$this->render_trilha_filter();
	}

	private function render_dia_filter() {
		global $wpdb;

		$dias = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_pt_event_dia' AND meta_value != ''
			ORDER BY meta_value ASC"
		);

		if ( empty( $dias ) ) {
			return;
		}

		$current = isset( $_GET['pt_filter_dia'] ) ? sanitize_text_field( $_GET['pt_filter_dia'] ) : '';

		echo '<select name="pt_filter_dia">';
		echo '<option value="">' . esc_html__( 'Todos os dias', 'pt-event' ) . '</option>';
		foreach ( $dias as $dia ) {
			$label = \PTEvent\Helpers\Helpers::format_dia_label( $dia );
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $dia ),
				selected( $current, $dia, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	private function render_trilha_filter() {
		global $wpdb;

		$trilhas = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_pt_event_trilha' AND meta_value != ''
			ORDER BY meta_value ASC"
		);

		if ( empty( $trilhas ) ) {
			return;
		}

		$current = isset( $_GET['pt_filter_trilha'] ) ? sanitize_text_field( $_GET['pt_filter_trilha'] ) : '';

		echo '<select name="pt_filter_trilha">';
		echo '<option value="">' . esc_html__( 'Todas as trilhas', 'pt-event' ) . '</option>';
		foreach ( $trilhas as $trilha ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $trilha ),
				selected( $current, $trilha, false ),
				esc_html( $trilha )
			);
		}
		echo '</select>';
	}

	public function apply_sessao_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'pt_sessao' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' ) ?: array();

		if ( ! empty( $_GET['pt_filter_dia'] ) ) {
			$meta_query[] = array(
				'key'   => '_pt_event_dia',
				'value' => sanitize_text_field( $_GET['pt_filter_dia'] ),
			);
		}

		if ( ! empty( $_GET['pt_filter_trilha'] ) ) {
			$meta_query[] = array(
				'key'   => '_pt_event_trilha',
				'value' => sanitize_text_field( $_GET['pt_filter_trilha'] ),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	// =========================================================================
	// SESSOES - Colunas
	// =========================================================================

	public function sessao_columns( $columns ) {
		$new = array();
		$new['cb']       = $columns['cb'];
		$new['title']    = $columns['title'];
		$new['pt_dia']   = __( 'Dia', 'pt-event' );
		$new['pt_hora']  = __( 'Horario', 'pt-event' );
		$new['pt_trilha'] = __( 'Trilha', 'pt-event' );
		$new['pt_ordem'] = __( 'Ordem', 'pt-event' );
		$new['date']     = $columns['date'];
		return $new;
	}

	public function sessao_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'pt_dia':
				$dia = get_post_meta( $post_id, '_pt_event_dia', true );
				echo $dia ? esc_html( \PTEvent\Helpers\Helpers::format_dia_label( $dia ) ) : '—';
				break;
			case 'pt_hora':
				$inicio = get_post_meta( $post_id, '_pt_event_hora_inicio', true );
				$fim    = get_post_meta( $post_id, '_pt_event_hora_fim', true );
				echo $inicio ? esc_html( $inicio . ' - ' . $fim ) : '—';
				break;
			case 'pt_trilha':
				$trilha = get_post_meta( $post_id, '_pt_event_trilha', true );
				echo $trilha ? esc_html( $trilha ) : '—';
				break;
			case 'pt_ordem':
				echo esc_html( get_post_meta( $post_id, '_pt_event_ordem', true ) );
				break;
		}
	}

	public function sessao_sortable_columns( $columns ) {
		$columns['pt_dia']   = 'pt_dia';
		$columns['pt_ordem'] = 'pt_ordem';
		return $columns;
	}

	// =========================================================================
	// PARTICIPANTES - Filtros
	// =========================================================================

	public function participante_filters( $post_type ) {
		if ( 'pt_participante' !== $post_type ) {
			return;
		}

		$this->render_confirmado_filter();
		$this->render_empresa_filter();
	}

	private function render_confirmado_filter() {
		$current = isset( $_GET['pt_filter_confirmado'] ) ? sanitize_text_field( $_GET['pt_filter_confirmado'] ) : '';

		echo '<select name="pt_filter_confirmado">';
		echo '<option value="">' . esc_html__( 'Todos (confirmacao)', 'pt-event' ) . '</option>';
		printf( '<option value="sim" %s>%s</option>', selected( $current, 'sim', false ), esc_html__( 'Confirmados', 'pt-event' ) );
		printf( '<option value="nao" %s>%s</option>', selected( $current, 'nao', false ), esc_html__( 'Nao confirmados', 'pt-event' ) );
		echo '</select>';
	}

	private function render_empresa_filter() {
		global $wpdb;

		$empresas = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_pt_event_empresa' AND meta_value != ''
			ORDER BY meta_value ASC"
		);

		if ( empty( $empresas ) ) {
			return;
		}

		$current = isset( $_GET['pt_filter_empresa'] ) ? sanitize_text_field( $_GET['pt_filter_empresa'] ) : '';

		echo '<select name="pt_filter_empresa">';
		echo '<option value="">' . esc_html__( 'Todas as empresas', 'pt-event' ) . '</option>';
		foreach ( $empresas as $empresa ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $empresa ),
				selected( $current, $empresa, false ),
				esc_html( $empresa )
			);
		}
		echo '</select>';
	}

	public function apply_participante_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'pt_participante' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' ) ?: array();

		if ( ! empty( $_GET['pt_filter_confirmado'] ) ) {
			$meta_query[] = array(
				'key'   => '_pt_event_confirmado',
				'value' => sanitize_text_field( $_GET['pt_filter_confirmado'] ),
			);
		}

		if ( ! empty( $_GET['pt_filter_empresa'] ) ) {
			$meta_query[] = array(
				'key'   => '_pt_event_empresa',
				'value' => sanitize_text_field( $_GET['pt_filter_empresa'] ),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	// =========================================================================
	// PARTICIPANTES - Colunas
	// =========================================================================

	public function participante_columns( $columns ) {
		$new = array();
		$new['cb']             = $columns['cb'];
		$new['title']          = $columns['title'];
		$new['pt_nome']        = __( 'Nome', 'pt-event' );
		$new['pt_cargo']       = __( 'Cargo', 'pt-event' );
		$new['pt_empresa']     = __( 'Empresa', 'pt-event' );
		$new['pt_confirmado']  = __( 'Confirmado', 'pt-event' );
		$new['date']           = $columns['date'];
		return $new;
	}

	public function participante_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'pt_nome':
				echo esc_html( get_post_meta( $post_id, '_pt_event_nome', true ) ?: '—' );
				break;
			case 'pt_cargo':
				$cargo = get_post_meta( $post_id, '_pt_event_cargo', true );
				echo $cargo ? esc_html( wp_trim_words( $cargo, 8 ) ) : '—';
				break;
			case 'pt_empresa':
				echo esc_html( get_post_meta( $post_id, '_pt_event_empresa', true ) ?: '—' );
				break;
			case 'pt_confirmado':
				$conf = get_post_meta( $post_id, '_pt_event_confirmado', true );
				echo 'sim' === $conf
					? '<span style="color:#00a32a;font-weight:600;">&#10003; Sim</span>'
					: '<span style="color:#d63638;">&#10007; Nao</span>';
				break;
		}
	}

	public function participante_sortable_columns( $columns ) {
		$columns['pt_nome']       = 'pt_nome';
		$columns['pt_empresa']    = 'pt_empresa';
		$columns['pt_confirmado'] = 'pt_confirmado';
		return $columns;
	}

	// =========================================================================
	// Ordenacao customizada
	// =========================================================================

	public function custom_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		$map = array(
			'pt_dia'        => '_pt_event_dia',
			'pt_ordem'      => '_pt_event_ordem',
			'pt_nome'       => '_pt_event_nome',
			'pt_empresa'    => '_pt_event_empresa',
			'pt_confirmado' => '_pt_event_confirmado',
		);

		if ( isset( $map[ $orderby ] ) ) {
			$query->set( 'meta_key', $map[ $orderby ] );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
