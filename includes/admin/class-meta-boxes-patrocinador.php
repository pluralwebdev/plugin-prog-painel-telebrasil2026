<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Meta_Boxes_Patrocinador {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_pt_patrocinador', array( $this, 'save_meta' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'pt_event_patrocinador_details',
			__( 'Detalhes do Patrocinador', 'pt-event' ),
			array( $this, 'render_meta_box' ),
			'pt_patrocinador',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'pt_event_patrocinador_meta', 'pt_event_patrocinador_nonce' );

		$logo_id  = get_post_meta( $post->ID, '_pt_patrocinador_logo', true );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$cota_id  = get_post_meta( $post->ID, '_pt_patrocinador_cota', true );
		$ordem    = get_post_meta( $post->ID, '_pt_patrocinador_ordem', true );
		$url      = get_post_meta( $post->ID, '_pt_patrocinador_url', true );

		$cotas = get_terms( array(
			'taxonomy'   => 'pt_cota_patrocinio',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num',
			'meta_key'   => '_pt_cota_order',
			'order'      => 'ASC',
		) );

		echo '<table class="form-table pt-event-meta-table">';

		// Logo
		echo '<tr>';
		echo '<th><label>' . esc_html__( 'Logomarca', 'pt-event' ) . '</label></th>';
		echo '<td>';
		echo '<div class="pt-event-foto-wrapper">';
		echo '<div class="pt-event-foto-preview" style="max-width:300px;">';
		if ( $logo_url ) {
			echo '<img src="' . esc_url( $logo_url ) . '" alt="" style="max-width:100%;height:auto;" />';
		}
		echo '</div>';
		echo '<input type="hidden" name="_pt_patrocinador_logo" id="_pt_patrocinador_logo" value="' . esc_attr( $logo_id ) . '" />';
		echo '<button type="button" class="button pt-event-upload-patrocinador-logo">' . esc_html__( 'Selecionar Logo', 'pt-event' ) . '</button> ';
		echo '<button type="button" class="button pt-event-remove-patrocinador-logo" ' . ( ! $logo_id ? 'style="display:none"' : '' ) . '>' . esc_html__( 'Remover', 'pt-event' ) . '</button>';
		echo '<p class="description">' . esc_html__( 'Upload da logomarca do patrocinador (PNG ou SVG recomendado, fundo transparente).', 'pt-event' ) . '</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

		// Cota
		echo '<tr>';
		echo '<th><label for="_pt_patrocinador_cota">' . esc_html__( 'Cota de Patrocínio', 'pt-event' ) . '</label></th>';
		echo '<td>';
		echo '<select id="_pt_patrocinador_cota" name="_pt_patrocinador_cota">';
		echo '<option value="">' . esc_html__( '— Selecionar Cota —', 'pt-event' ) . '</option>';
		if ( ! is_wp_error( $cotas ) ) {
			foreach ( $cotas as $cota ) {
				$w = get_term_meta( $cota->term_id, '_pt_cota_width', true );
				$h = get_term_meta( $cota->term_id, '_pt_cota_height', true );
				$label = $cota->name . ' (' . $w . '×' . $h . 'px)';
				echo '<option value="' . esc_attr( $cota->term_id ) . '" ' . selected( $cota_id, $cota->term_id, false ) . '>' . esc_html( $label ) . '</option>';
			}
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Define o tamanho do card e a seção onde aparecerá no shortcode.', 'pt-event' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		// Ordem
		echo '<tr>';
		echo '<th><label for="_pt_patrocinador_ordem">' . esc_html__( 'Ordem na seção', 'pt-event' ) . '</label></th>';
		echo '<td>';
		echo '<input type="number" id="_pt_patrocinador_ordem" name="_pt_patrocinador_ordem" value="' . esc_attr( $ordem ) . '" min="0" max="999" style="width:80px;" />';
		echo '<p class="description">' . esc_html__( 'Ordem do patrocinador dentro da sua cota (0 = primeiro).', 'pt-event' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		// URL
		echo '<tr>';
		echo '<th><label for="_pt_patrocinador_url">' . esc_html__( 'Website', 'pt-event' ) . '</label></th>';
		echo '<td>';
		echo '<input type="url" id="_pt_patrocinador_url" name="_pt_patrocinador_url" value="' . esc_attr( $url ) . '" class="regular-text" placeholder="https://" />';
		echo '<p class="description">' . esc_html__( 'Link opcional para o site do patrocinador.', 'pt-event' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['pt_event_patrocinador_nonce'] ) || ! wp_verify_nonce( $_POST['pt_event_patrocinador_nonce'], 'pt_event_patrocinador_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Logo
		if ( isset( $_POST['_pt_patrocinador_logo'] ) ) {
			update_post_meta( $post_id, '_pt_patrocinador_logo', absint( $_POST['_pt_patrocinador_logo'] ) );
		}

		// Cota (term ID) — also set taxonomy relationship
		if ( isset( $_POST['_pt_patrocinador_cota'] ) ) {
			$cota_id = absint( $_POST['_pt_patrocinador_cota'] );
			update_post_meta( $post_id, '_pt_patrocinador_cota', $cota_id );
			if ( $cota_id ) {
				wp_set_object_terms( $post_id, array( (int) $cota_id ), 'pt_cota_patrocinio' );
			} else {
				wp_set_object_terms( $post_id, array(), 'pt_cota_patrocinio' );
			}
		}

		// Ordem
		if ( isset( $_POST['_pt_patrocinador_ordem'] ) ) {
			update_post_meta( $post_id, '_pt_patrocinador_ordem', absint( $_POST['_pt_patrocinador_ordem'] ) );
		}

		// URL
		if ( isset( $_POST['_pt_patrocinador_url'] ) ) {
			update_post_meta( $post_id, '_pt_patrocinador_url', esc_url_raw( $_POST['_pt_patrocinador_url'] ) );
		}
	}
}
