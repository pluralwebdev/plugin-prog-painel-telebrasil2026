<?php
namespace PTEvent\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {

	public static function get_settings() {
		$defaults = array(
			'cor_fundo_sessao'       => '#ffffff',
			'cor_cabecalho'          => '#1a1a2e',
			'cor_texto'              => '#333333',
			'cor_nome_participante'  => '#1a1a2e',
			'cor_cargo_participante' => '#666666',
			'border_color'           => '#cccccc',
			'border_width'           => '2',
			'border_radius'          => '50',
			'custom_css'             => '',
		);

		$settings = get_option( 'pt_event_settings', array() );
		return wp_parse_args( $settings, $defaults );
	}

	public static function get_participantes_by_sessao( $sessao_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sp.participante_id, sp.papel, sp.ordem
				FROM {$table} AS sp
				INNER JOIN {$wpdb->posts} AS p ON p.ID = sp.participante_id
				WHERE sp.sessao_id = %d
				AND p.post_status = 'publish'
				ORDER BY sp.ordem ASC",
				$sessao_id
			)
		);

		if ( empty( $results ) ) {
			return array();
		}

		$participantes = array();
		foreach ( $results as $row ) {
			$confirmado = get_post_meta( $row->participante_id, '_pt_event_confirmado', true );
			if ( 'sim' !== $confirmado ) {
				continue;
			}

			$participantes[] = array(
				'id'      => $row->participante_id,
				'papel'   => $row->papel,
				'ordem'   => $row->ordem,
				'nome'    => get_post_meta( $row->participante_id, '_pt_event_nome', true ),
				'foto'    => get_post_meta( $row->participante_id, '_pt_event_foto', true ),
				'cargo'   => get_post_meta( $row->participante_id, '_pt_event_cargo', true ),
				'empresa' => get_post_meta( $row->participante_id, '_pt_event_empresa', true ),
				'bio'     => get_post_meta( $row->participante_id, '_pt_event_bio', true ),
				'links'   => get_post_meta( $row->participante_id, '_pt_event_links', true ),
			);
		}

		return $participantes;
	}

	public static function get_sessoes_agrupadas() {
		$args = array(
			'post_type'      => 'pt_sessao',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => '_pt_event_ordem',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		);

		$sessoes = get_posts( $args );

		if ( empty( $sessoes ) ) {
			return array();
		}

		$agrupadas = array();
		foreach ( $sessoes as $sessao ) {
			$dia         = get_post_meta( $sessao->ID, '_pt_event_dia', true );
			$hora_inicio = get_post_meta( $sessao->ID, '_pt_event_hora_inicio', true );
			$hora_fim    = get_post_meta( $sessao->ID, '_pt_event_hora_fim', true );
			$horario     = $hora_inicio . ' - ' . $hora_fim;

			if ( ! isset( $agrupadas[ $dia ] ) ) {
				$agrupadas[ $dia ] = array();
			}

			if ( ! isset( $agrupadas[ $dia ][ $horario ] ) ) {
				$agrupadas[ $dia ][ $horario ] = array();
			}

			$agrupadas[ $dia ][ $horario ][] = array(
				'id'        => $sessao->ID,
				'titulo'    => get_post_meta( $sessao->ID, '_pt_event_titulo', true ),
				'subtitulo' => get_post_meta( $sessao->ID, '_pt_event_subtitulo', true ),
				'descricao' => get_post_meta( $sessao->ID, '_pt_event_descricao', true ),
				'trilha'    => get_post_meta( $sessao->ID, '_pt_event_trilha', true ),
			);
		}

		ksort( $agrupadas );

		return $agrupadas;
	}

	public static function format_dia_label( $dia ) {
		if ( empty( $dia ) ) {
			return '';
		}
		$timestamp = strtotime( $dia );
		if ( false === $timestamp ) {
			return esc_html( $dia );
		}
		return date_i18n( 'l, j \d\e F \d\e Y', $timestamp );
	}
}
