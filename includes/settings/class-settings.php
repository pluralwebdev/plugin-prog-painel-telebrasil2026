<?php
namespace PTEvent\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'Programação - Configurações', 'pt-event' ),
			__( 'Config. Evento', 'pt-event' ),
			'manage_options',
			'pt-event-settings',
			array( $this, 'render_page' ),
			'dashicons-admin-generic',
			27
		);
	}

	public function register_settings() {
		register_setting( 'pt_event_settings_group', 'pt_event_settings', array(
			'sanitize_callback' => array( $this, 'sanitize' ),
		) );

		// --- Cores principais ---
		add_settings_section( 'pt_event_colors', __( 'Cores Principais', 'pt-event' ), null, 'pt-event-settings' );

		$color_fields = array(
			'cor_primaria'           => __( 'Cor Primária (verde)', 'pt-event' ),
			'cor_primaria_claro'     => __( 'Cor Primária Clara', 'pt-event' ),
			'cor_primaria_bg'        => __( 'Cor Primária Background', 'pt-event' ),
			'cor_escura'             => __( 'Cor Escura (títulos)', 'pt-event' ),
			'cor_dourado'            => __( 'Cor Dourada (destaques)', 'pt-event' ),
			'cor_texto'              => __( 'Cor do Texto', 'pt-event' ),
			'cor_fundo'              => __( 'Cor de Fundo', 'pt-event' ),
			'cor_fundo_sessao'       => __( 'Fundo da Sessão', 'pt-event' ),
			'cor_nome_participante'  => __( 'Nome do Participante', 'pt-event' ),
			'cor_cargo_participante' => __( 'Cargo do Participante', 'pt-event' ),
			'cor_especial'           => __( 'Cor Sessão Especial (almoço)', 'pt-event' ),
		);

		foreach ( $color_fields as $key => $label ) {
			add_settings_field(
				$key, $label,
				array( $this, 'render_color_field' ),
				'pt-event-settings', 'pt_event_colors',
				array( 'key' => $key )
			);
		}

		// --- Imagem do Participante ---
		add_settings_section( 'pt_event_image', __( 'Imagem do Participante', 'pt-event' ), null, 'pt-event-settings' );

		add_settings_field( 'border_color', __( 'Cor da Borda', 'pt-event' ),
			array( $this, 'render_color_field' ), 'pt-event-settings', 'pt_event_image', array( 'key' => 'border_color' ) );

		add_settings_field( 'border_width', __( 'Largura da Borda (px)', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_image',
			array( 'key' => 'border_width', 'min' => 0, 'max' => 20 ) );

		add_settings_field( 'border_radius', __( 'Border Radius (%)', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_image',
			array( 'key' => 'border_radius', 'min' => 0, 'max' => 50 ) );

		// --- Textos da seção ---
		add_settings_section( 'pt_event_texts', __( 'Textos', 'pt-event' ), null, 'pt-event-settings' );

		add_settings_field( 'titulo_secao_sub', __( 'Subtítulo da seção (ex: "Confira")', 'pt-event' ),
			array( $this, 'render_text_field' ), 'pt-event-settings', 'pt_event_texts', array( 'key' => 'titulo_secao_sub' ) );

		add_settings_field( 'titulo_secao', __( 'Título da seção (ex: "Principais Temas")', 'pt-event' ),
			array( $this, 'render_text_field' ), 'pt-event-settings', 'pt_event_texts', array( 'key' => 'titulo_secao' ) );

		// --- CSS Customizado ---
		add_settings_section( 'pt_event_custom', __( 'CSS Customizado', 'pt-event' ), null, 'pt-event-settings' );

		add_settings_field( 'custom_css', __( 'CSS', 'pt-event' ),
			array( $this, 'render_textarea_field' ), 'pt-event-settings', 'pt_event_custom', array( 'key' => 'custom_css' ) );
	}

	public function render_color_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		echo '<input type="color" name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '" />';
	}

	public function render_number_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		$min      = isset( $args['min'] ) ? $args['min'] : 0;
		$max      = isset( $args['max'] ) ? $args['max'] : 100;
		echo '<input type="number" name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" />';
	}

	public function render_text_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		echo '<input type="text" name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	public function render_textarea_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		echo '<textarea name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" rows="10" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
	}

	public function sanitize( $input ) {
		$sanitized = array();

		$color_keys = array(
			'cor_primaria', 'cor_primaria_claro', 'cor_primaria_bg',
			'cor_escura', 'cor_dourado', 'cor_texto', 'cor_fundo',
			'cor_fundo_sessao', 'cor_nome_participante', 'cor_cargo_participante',
			'cor_especial', 'border_color',
		);
		foreach ( $color_keys as $key ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
		}

		$sanitized['border_width']    = isset( $input['border_width'] ) ? absint( $input['border_width'] ) : 3;
		$sanitized['border_radius']   = isset( $input['border_radius'] ) ? absint( $input['border_radius'] ) : 50;
		$sanitized['titulo_secao_sub'] = isset( $input['titulo_secao_sub'] ) ? sanitize_text_field( $input['titulo_secao_sub'] ) : '';
		$sanitized['titulo_secao']     = isset( $input['titulo_secao'] ) ? sanitize_text_field( $input['titulo_secao'] ) : '';
		$sanitized['custom_css']       = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

		return $sanitized;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Programação de Eventos - Configurações', 'pt-event' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'pt_event_settings_group' );
				do_settings_sections( 'pt-event-settings' );
				submit_button();
				?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Shortcode', 'pt-event' ); ?></h2>
			<p><code>[event_programacao]</code></p>
			<p><?php esc_html_e( 'Insira este shortcode em qualquer página ou post para exibir a programação do evento.', 'pt-event' ); ?></p>
		</div>
		<?php
	}
}
