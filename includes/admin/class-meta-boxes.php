<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Meta_Boxes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_sessao_meta_boxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_participante_meta_boxes' ) );
		add_action( 'save_post_pt_sessao', array( $this, 'save_sessao_meta' ) );
		add_action( 'save_post_pt_participante', array( $this, 'save_participante_meta' ) );
	}

	public function add_sessao_meta_boxes() {
		add_meta_box(
			'pt_event_sessao_details',
			__( 'Detalhes da Sessão', 'pt-event' ),
			array( $this, 'render_sessao_meta_box' ),
			'pt_sessao',
			'normal',
			'high'
		);
	}

	public function add_participante_meta_boxes() {
		add_meta_box(
			'pt_event_participante_details',
			__( 'Detalhes do Participante', 'pt-event' ),
			array( $this, 'render_participante_meta_box' ),
			'pt_participante',
			'normal',
			'high'
		);
	}

	public function render_sessao_meta_box( $post ) {
		wp_nonce_field( 'pt_event_sessao_meta', 'pt_event_sessao_nonce' );

		$fields = array(
			'titulo'      => array( 'label' => __( 'Título', 'pt-event' ), 'type' => 'text' ),
			'subtitulo'   => array( 'label' => __( 'Subtítulo', 'pt-event' ), 'type' => 'text' ),
			'descricao'   => array( 'label' => __( 'Descrição', 'pt-event' ), 'type' => 'textarea' ),
			'hora_inicio' => array( 'label' => __( 'Hora Início', 'pt-event' ), 'type' => 'time' ),
			'hora_fim'    => array( 'label' => __( 'Hora Fim', 'pt-event' ), 'type' => 'time' ),
			'dia'         => array( 'label' => __( 'Dia', 'pt-event' ), 'type' => 'date' ),
			'trilha'      => array( 'label' => __( 'Trilha', 'pt-event' ), 'type' => 'text' ),
			'ordem'       => array( 'label' => __( 'Ordem', 'pt-event' ), 'type' => 'number' ),
		);

		echo '<table class="form-table pt-event-meta-table">';
		foreach ( $fields as $key => $field ) {
			$meta_key = '_pt_event_' . $key;
			$value    = get_post_meta( $post->ID, $meta_key, true );

			echo '<tr>';
			echo '<th><label for="' . esc_attr( $meta_key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';

			if ( 'textarea' === $field['type'] ) {
				echo '<textarea id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			}

			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	public function render_participante_meta_box( $post ) {
		wp_nonce_field( 'pt_event_participante_meta', 'pt_event_participante_nonce' );

		$fields = array(
			'nome'       => array( 'label' => __( 'Nome', 'pt-event' ), 'type' => 'text' ),
			'cargo'      => array( 'label' => __( 'Cargo', 'pt-event' ), 'type' => 'textarea' ),
			'empresa'    => array( 'label' => __( 'Empresa', 'pt-event' ), 'type' => 'text' ),
			'tipo_participante' => array( 'label' => __( 'Tipo de Participante', 'pt-event' ), 'type' => 'select_tipo' ),
			'exibir_home' => array( 'label' => __( 'Exibir na Home (carrossel)', 'pt-event' ), 'type' => 'checkbox' ),
			'bio'        => array( 'label' => __( 'Bio', 'pt-event' ), 'type' => 'textarea' ),
			'links'      => array( 'label' => __( 'Links (um por linha)', 'pt-event' ), 'type' => 'textarea' ),
			'confirmado' => array( 'label' => __( 'Confirmado', 'pt-event' ), 'type' => 'select' ),
		);

		$foto_id  = get_post_meta( $post->ID, '_pt_event_foto', true );
		$foto_url = $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';

		echo '<table class="form-table pt-event-meta-table">';

		// Foto field com Media Library
		echo '<tr>';
		echo '<th><label>' . esc_html__( 'Foto', 'pt-event' ) . '</label></th>';
		echo '<td>';
		echo '<div class="pt-event-foto-wrapper">';
		echo '<div class="pt-event-foto-preview">';
		if ( $foto_url ) {
			echo '<img src="' . esc_url( $foto_url ) . '" alt="" />';
		}
		echo '</div>';
		echo '<input type="hidden" name="_pt_event_foto" id="_pt_event_foto" value="' . esc_attr( $foto_id ) . '" />';
		echo '<button type="button" class="button pt-event-upload-foto">' . esc_html__( 'Selecionar Foto', 'pt-event' ) . '</button> ';
		echo '<button type="button" class="button pt-event-remove-foto" ' . ( ! $foto_id ? 'style="display:none"' : '' ) . '>' . esc_html__( 'Remover', 'pt-event' ) . '</button>';
		echo '<p class="description" style="margin-top:6px;">Tamanho recomendado: <strong>600 × 550 px</strong>. Foto do participante da cintura para cima.</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

		foreach ( $fields as $key => $field ) {
			$meta_key = '_pt_event_' . $key;
			$value    = get_post_meta( $post->ID, $meta_key, true );

			echo '<tr>';
			echo '<th><label for="' . esc_attr( $meta_key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';

			if ( 'textarea' === $field['type'] ) {
				echo '<textarea id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
			} elseif ( 'select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '">';
				echo '<option value="nao" ' . selected( $value, 'nao', false ) . '>' . esc_html__( 'Não', 'pt-event' ) . '</option>';
				echo '<option value="sim" ' . selected( $value, 'sim', false ) . '>' . esc_html__( 'Sim', 'pt-event' ) . '</option>';
				echo '</select>';
			} elseif ( 'select_tipo' === $field['type'] ) {
				$tipos = array(
					''             => __( '— Selecionar —', 'pt-event' ),
					'debatedor'    => __( 'Debatedor', 'pt-event' ),
					'fireside'     => __( 'Fireside', 'pt-event' ),
					'keynote'      => __( 'Keynote', 'pt-event' ),
					'moderador'    => __( 'Moderador', 'pt-event' ),
					'patrocinador' => __( 'Patrocinador', 'pt-event' ),
				);
				echo '<select id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '">';
				foreach ( $tipos as $val => $label ) {
					echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'checkbox' === $field['type'] ) {
				echo '<label>';
				echo '<input type="checkbox" id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" value="sim" ' . checked( $value, 'sim', false ) . ' />';
				echo ' ' . esc_html__( 'Sim, exibir este participante no carrossel da home', 'pt-event' );
				echo '</label>';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			}

			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	public function save_sessao_meta( $post_id ) {
		if ( ! isset( $_POST['pt_event_sessao_nonce'] ) || ! wp_verify_nonce( $_POST['pt_event_sessao_nonce'], 'pt_event_sessao_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array( 'titulo', 'subtitulo', 'descricao', 'hora_inicio', 'hora_fim', 'dia', 'trilha', 'ordem' );

		foreach ( $fields as $field ) {
			$meta_key = '_pt_event_' . $field;
			if ( isset( $_POST[ $meta_key ] ) ) {
				$value = 'descricao' === $field
					? sanitize_textarea_field( $_POST[ $meta_key ] )
					: sanitize_text_field( $_POST[ $meta_key ] );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	public function save_participante_meta( $post_id ) {
		if ( ! isset( $_POST['pt_event_participante_nonce'] ) || ! wp_verify_nonce( $_POST['pt_event_participante_nonce'], 'pt_event_participante_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array( 'nome', 'foto', 'cargo', 'empresa', 'tipo_participante', 'bio', 'links', 'confirmado' );

		foreach ( $fields as $field ) {
			$meta_key = '_pt_event_' . $field;
			if ( isset( $_POST[ $meta_key ] ) ) {
				if ( in_array( $field, array( 'cargo', 'bio', 'links' ), true ) ) {
					$value = sanitize_textarea_field( $_POST[ $meta_key ] );
				} elseif ( 'foto' === $field ) {
					$value = absint( $_POST[ $meta_key ] );
				} else {
					$value = sanitize_text_field( $_POST[ $meta_key ] );
				}
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Checkbox: unchecked means not in $_POST at all
		$exibir_home = isset( $_POST['_pt_event_exibir_home'] ) ? 'sim' : 'nao';
		update_post_meta( $post_id, '_pt_event_exibir_home', $exibir_home );
	}
}
