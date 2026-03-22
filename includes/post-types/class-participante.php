<?php
namespace PTEvent\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Participante {

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
			'name'               => __( 'Participantes', 'pt-event' ),
			'singular_name'      => __( 'Participante', 'pt-event' ),
			'menu_name'          => __( 'Participantes', 'pt-event' ),
			'add_new'            => __( 'Adicionar Participante', 'pt-event' ),
			'add_new_item'       => __( 'Adicionar Novo Participante', 'pt-event' ),
			'edit_item'          => __( 'Editar Participante', 'pt-event' ),
			'new_item'           => __( 'Novo Participante', 'pt-event' ),
			'view_item'          => __( 'Ver Participante', 'pt-event' ),
			'search_items'       => __( 'Buscar Participantes', 'pt-event' ),
			'not_found'          => __( 'Nenhum participante encontrado', 'pt-event' ),
			'not_found_in_trash' => __( 'Nenhum participante na lixeira', 'pt-event' ),
		);

		$args = array(
			'labels'       => $labels,
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-groups',
			'menu_position' => 26,
			'supports'     => array( 'title', 'thumbnail' ),
			'has_archive'  => false,
			'rewrite'      => false,
		);

		register_post_type( 'pt_participante', $args );
	}
}
