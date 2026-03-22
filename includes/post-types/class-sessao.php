<?php
namespace PTEvent\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sessao {

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
			'name'               => __( 'Sessões', 'pt-event' ),
			'singular_name'      => __( 'Sessão', 'pt-event' ),
			'menu_name'          => __( 'Sessões', 'pt-event' ),
			'add_new'            => __( 'Adicionar Sessão', 'pt-event' ),
			'add_new_item'       => __( 'Adicionar Nova Sessão', 'pt-event' ),
			'edit_item'          => __( 'Editar Sessão', 'pt-event' ),
			'new_item'           => __( 'Nova Sessão', 'pt-event' ),
			'view_item'          => __( 'Ver Sessão', 'pt-event' ),
			'search_items'       => __( 'Buscar Sessões', 'pt-event' ),
			'not_found'          => __( 'Nenhuma sessão encontrada', 'pt-event' ),
			'not_found_in_trash' => __( 'Nenhuma sessão na lixeira', 'pt-event' ),
		);

		$args = array(
			'labels'       => $labels,
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-calendar-alt',
			'menu_position' => 25,
			'supports'     => array( 'title' ),
			'has_archive'  => false,
			'rewrite'      => false,
		);

		register_post_type( 'pt_sessao', $args );
	}
}
