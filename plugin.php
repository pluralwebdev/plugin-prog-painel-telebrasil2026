<?php
/**
 * Plugin Name: Programação de Eventos
 * Plugin URI:  https://pluralweb.biz
 * Description: Plugin para cadastro e exibição dinâmica de programação de eventos com sessões e participantes.
 * Version:     1.5.0
 * Author:      Plural Web
 * Author URI:  https://pluralweb.biz
 * Text Domain: pt-event
 * Domain Path: /languages
 * License:     GPLv2 or later
 */

namespace PTEvent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PT_EVENT_VERSION', '1.5.0' );
define( 'PT_EVENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PT_EVENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PT_EVENT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once PT_EVENT_PLUGIN_DIR . 'includes/helpers/class-helpers.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/database/class-relationship.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/post-types/class-sessao.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/post-types/class-participante.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-meta-boxes.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-sessao-participantes.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/settings/class-settings.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/frontend/class-shortcode.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/frontend/class-shortcode-cards.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/frontend/class-shortcode-home.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/frontend/class-shortcode-debatedores.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/frontend/class-shortcode-patrocinadores.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/post-types/class-patrocinador.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-meta-boxes-patrocinador.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-taxonomy-cota.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-importador.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-editor.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-admin-filters.php';
require_once PT_EVENT_PLUGIN_DIR . 'includes/admin/class-seeder.php';

final class Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init_components' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
	}

	public function activate() {
		Database\Relationship::create_table();

		// Register CPTs before seeding terms
		PostTypes\Patrocinador::get_instance();
		PostTypes\Patrocinador::seed_default_cotas();

		flush_rewrite_rules();
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'pt-event', false, dirname( PT_EVENT_PLUGIN_BASENAME ) . '/languages' );
	}

	public function init_components() {
		PostTypes\Sessao::get_instance();
		PostTypes\Participante::get_instance();
		Admin\Meta_Boxes::get_instance();
		Admin\Sessao_Participantes::get_instance();
		Settings\Settings::get_instance();
		Frontend\Shortcode::get_instance();
		Frontend\Shortcode_Home::get_instance();
		Frontend\Shortcode_Debatedores::get_instance();
		Frontend\Shortcode_Patrocinadores::get_instance();
		PostTypes\Patrocinador::get_instance();
		Admin\Meta_Boxes_Patrocinador::get_instance();
		Admin\Taxonomy_Cota::get_instance();
		Admin\Importador::get_instance();
		Admin\Editor::get_instance();
		Admin\Admin_Filters::get_instance();
		Admin\Seeder::get_instance();
	}

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed = array( 'pt_sessao', 'pt_participante', 'pt_patrocinador' );
		$is_settings = ( 'toplevel_page_pt-event-settings' === $hook );
		if ( in_array( $screen->post_type, $allowed, true ) || $is_settings ) {
			if ( $is_settings || in_array( $screen->post_type, array( 'pt_participante', 'pt_patrocinador' ), true ) ) {
				wp_enqueue_media();
			}
			wp_enqueue_style(
				'pt-event-admin',
				PT_EVENT_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				PT_EVENT_VERSION
			);
			wp_enqueue_script(
				'pt-event-admin',
				PT_EVENT_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				PT_EVENT_VERSION,
				true
			);
			wp_localize_script( 'pt-event-admin', 'ptEventAdmin', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pt_event_admin' ),
			) );
		}
	}

	public function frontend_assets() {
		$settings = get_option( 'pt_event_settings', array() );
		$custom_css = isset( $settings['custom_css'] ) ? $settings['custom_css'] : '';

		wp_enqueue_style(
			'pt-event-frontend',
			PT_EVENT_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			PT_EVENT_VERSION
		);

		wp_enqueue_script(
			'pt-event-frontend',
			PT_EVENT_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			PT_EVENT_VERSION,
			true
		);

		$menu_height = isset( $settings['menu_height'] ) ? absint( $settings['menu_height'] ) : 0;
		wp_localize_script( 'pt-event-frontend', 'ptEventConfig', array(
			'menuHeight' => $menu_height,
		) );

		if ( $custom_css ) {
			wp_add_inline_style( 'pt-event-frontend', $custom_css );
		}

		// Enqueue Google Fonts used in typography settings
		$google_fonts = Helpers\Helpers::get_used_google_fonts();
		if ( ! empty( $google_fonts ) ) {
			$families = array();
			foreach ( $google_fonts as $family => $weights ) {
				$f = str_replace( ' ', '+', $family );
				if ( ! empty( $weights ) ) {
					sort( $weights );
					$f .= ':wght@' . implode( ';', $weights );
				}
				$families[] = $f;
			}
			$url = 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( function ( $f ) {
				return 'family=' . $f;
			}, $families ) ) . '&display=swap';
			wp_enqueue_style( 'pt-event-google-fonts', $url, array(), null );
		}
	}
}

Plugin::get_instance();
