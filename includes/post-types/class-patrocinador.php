<?php
namespace PTEvent\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Patrocinador {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->register();
	}

	public function register() {
		$labels = array(
			'name'               => __( 'Patrocinadores', 'pt-event' ),
			'singular_name'      => __( 'Patrocinador', 'pt-event' ),
			'menu_name'          => __( 'Patrocinadores', 'pt-event' ),
			'add_new'            => __( 'Adicionar Patrocinador', 'pt-event' ),
			'add_new_item'       => __( 'Adicionar Novo Patrocinador', 'pt-event' ),
			'edit_item'          => __( 'Editar Patrocinador', 'pt-event' ),
			'new_item'           => __( 'Novo Patrocinador', 'pt-event' ),
			'view_item'          => __( 'Ver Patrocinador', 'pt-event' ),
			'search_items'       => __( 'Buscar Patrocinadores', 'pt-event' ),
			'not_found'          => __( 'Nenhum patrocinador encontrado', 'pt-event' ),
			'not_found_in_trash' => __( 'Nenhum patrocinador na lixeira', 'pt-event' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 27,
			'supports'      => array( 'title' ),
			'has_archive'   => false,
			'rewrite'       => false,
		);

		register_post_type( 'pt_patrocinador', $args );

		// Taxonomy — Cotas de Patrocínio
		$tax_labels = array(
			'name'              => __( 'Cotas de Patrocínio', 'pt-event' ),
			'singular_name'     => __( 'Cota', 'pt-event' ),
			'search_items'      => __( 'Buscar Cotas', 'pt-event' ),
			'all_items'         => __( 'Todas as Cotas', 'pt-event' ),
			'edit_item'         => __( 'Editar Cota', 'pt-event' ),
			'update_item'       => __( 'Atualizar Cota', 'pt-event' ),
			'add_new_item'      => __( 'Adicionar Nova Cota', 'pt-event' ),
			'new_item_name'     => __( 'Nome da Nova Cota', 'pt-event' ),
			'menu_name'         => __( 'Cotas', 'pt-event' ),
		);

		register_taxonomy( 'pt_cota_patrocinio', 'pt_patrocinador', array(
			'labels'            => $tax_labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'rewrite'           => false,
		) );
	}

	/**
	 * Seed default cotas on activation.
	 */
	public static function seed_default_cotas() {
		$defaults = array(
			array( 'name' => 'Diamante', 'width' => 530, 'height' => 236, 'cols' => 2, 'logo_pct' => 70, 'order' => 1 ),
			array( 'name' => 'Platinum', 'width' => 421, 'height' => 188, 'cols' => 3, 'logo_pct' => 65, 'order' => 2 ),
			array( 'name' => 'Ouro',     'width' => 309, 'height' => 138, 'cols' => 4, 'logo_pct' => 60, 'order' => 3 ),
			array( 'name' => 'Prata',    'width' => 196, 'height' => 88,  'cols' => 6, 'logo_pct' => 55, 'order' => 4 ),
			array( 'name' => 'Bronze',   'width' => 196, 'height' => 88,  'cols' => 6, 'logo_pct' => 55, 'order' => 5 ),
		);

		foreach ( $defaults as $cota ) {
			if ( term_exists( $cota['name'], 'pt_cota_patrocinio' ) ) {
				continue;
			}
			$term = wp_insert_term( $cota['name'], 'pt_cota_patrocinio' );
			if ( ! is_wp_error( $term ) ) {
				update_term_meta( $term['term_id'], '_pt_cota_width', $cota['width'] );
				update_term_meta( $term['term_id'], '_pt_cota_height', $cota['height'] );
				update_term_meta( $term['term_id'], '_pt_cota_cols', $cota['cols'] );
				update_term_meta( $term['term_id'], '_pt_cota_logo_pct', $cota['logo_pct'] );
				update_term_meta( $term['term_id'], '_pt_cota_order', $cota['order'] );
			}
		}
	}
}
