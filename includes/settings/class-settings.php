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

		/* ==================================================================
		   SEÇÃO 1 — Cores da Programação (Página de Programação)
		   ================================================================== */
		add_settings_section(
			'pt_event_colors_prog',
			__( '🎨 Cores da Programação', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Cores aplicadas na página de programação do evento (shortcode [event_programacao]).', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		$prog_colors = array(
			'cor_primaria'       => array( 'label' => __( 'Primária (verde principal)', 'pt-event' ), 'desc' => __( 'Badges dos dias, horários, bordas de sessão', 'pt-event' ) ),
			'cor_primaria_claro' => array( 'label' => __( 'Primária clara', 'pt-event' ), 'desc' => __( 'Destaques suaves e hover', 'pt-event' ) ),
			'cor_primaria_bg'    => array( 'label' => __( 'Background primário', 'pt-event' ), 'desc' => __( 'Fundo do label do dia na barra de horários', 'pt-event' ) ),
			'cor_escura'         => array( 'label' => __( 'Escura (títulos)', 'pt-event' ), 'desc' => __( 'Cor dos títulos de sessão e cabeçalhos', 'pt-event' ) ),
			'cor_dourado'        => array( 'label' => __( 'Dourada (destaques)', 'pt-event' ), 'desc' => __( 'Detalhes dourados e ênfases', 'pt-event' ) ),
			'cor_texto'          => array( 'label' => __( 'Texto geral', 'pt-event' ), 'desc' => __( 'Cor padrão do texto do corpo', 'pt-event' ) ),
			'cor_fundo'          => array( 'label' => __( 'Fundo da página', 'pt-event' ), 'desc' => __( 'Background geral da seção de programação', 'pt-event' ) ),
			'cor_especial'       => array( 'label' => __( 'Sessão especial (almoço/coffee)', 'pt-event' ), 'desc' => __( 'Borda e ícone de sessões de intervalo', 'pt-event' ) ),
		);

		foreach ( $prog_colors as $key => $data ) {
			add_settings_field( $key, $data['label'],
				array( $this, 'render_color_field' ), 'pt-event-settings', 'pt_event_colors_prog',
				array( 'key' => $key, 'desc' => $data['desc'] )
			);
		}

		/* ==================================================================
		   SEÇÃO 2 — Cores dos Cards de Sessão
		   ================================================================== */
		add_settings_section(
			'pt_event_colors_card',
			__( '📋 Cores dos Cards de Sessão', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Cores aplicadas dentro de cada card de sessão na programação.', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		$card_colors = array(
			'cor_fundo_sessao'       => array( 'label' => __( 'Fundo do card', 'pt-event' ), 'desc' => __( 'Background branco de cada sessão', 'pt-event' ) ),
			'cor_nome_participante'  => array( 'label' => __( 'Nome do participante', 'pt-event' ), 'desc' => __( 'Cor do nome dentro do card', 'pt-event' ) ),
			'cor_cargo_participante' => array( 'label' => __( 'Cargo do participante', 'pt-event' ), 'desc' => __( 'Cor do cargo/empresa dentro do card', 'pt-event' ) ),
		);

		foreach ( $card_colors as $key => $data ) {
			add_settings_field( $key, $data['label'],
				array( $this, 'render_color_field' ), 'pt-event-settings', 'pt_event_colors_card',
				array( 'key' => $key, 'desc' => $data['desc'] )
			);
		}

		/* ==================================================================
		   SEÇÃO 3 — Imagem de Fundo do Participante
		   ================================================================== */
		add_settings_section(
			'pt_event_participante_bg',
			__( '🖼️ Card do Participante', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Imagem de fundo geométrica que aparece atrás da foto PNG do participante nos carrosséis e grids.', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		add_settings_field( 'foto_fundo_participante', __( 'Imagem de fundo', 'pt-event' ),
			array( $this, 'render_image_field' ), 'pt-event-settings', 'pt_event_participante_bg',
			array( 'key' => 'foto_fundo_participante', 'desc' => __( 'Upload da imagem verde geométrica (recomendado: PNG ~400×400px)', 'pt-event' ) )
		);

		add_settings_field( 'border_color', __( 'Cor da borda da foto', 'pt-event' ),
			array( $this, 'render_color_field' ), 'pt-event-settings', 'pt_event_participante_bg',
			array( 'key' => 'border_color', 'desc' => __( 'Borda ao redor da foto (se aplicável)', 'pt-event' ) )
		);

		add_settings_field( 'card_nome_size', __( 'Tamanho fonte — Nome', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_participante_bg',
			array( 'key' => 'card_nome_size', 'min' => 10, 'max' => 30, 'desc' => __( 'px — Tamanho da fonte do nome do participante', 'pt-event' ) )
		);

		add_settings_field( 'card_cargo_size', __( 'Tamanho fonte — Empresa', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_participante_bg',
			array( 'key' => 'card_cargo_size', 'min' => 8, 'max' => 24, 'desc' => __( 'px — Tamanho da fonte da empresa/cargo', 'pt-event' ) )
		);

		/* ==================================================================
		   SEÇÃO 3b — Carrossel da Home
		   ================================================================== */
		add_settings_section(
			'pt_event_carousel',
			__( '🎠 Carrossel da Home', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Configurações do carrossel de palestrantes exibido na home.', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		add_settings_field( 'carousel_autoplay', __( 'Autoplay', 'pt-event' ),
			array( $this, 'render_checkbox_field' ), 'pt-event-settings', 'pt_event_carousel',
			array( 'key' => 'carousel_autoplay', 'desc' => __( 'Avançar slides automaticamente', 'pt-event' ) )
		);

		add_settings_field( 'carousel_speed', __( 'Intervalo (segundos)', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_carousel',
			array( 'key' => 'carousel_speed', 'min' => 1, 'max' => 30, 'desc' => __( 'Tempo em segundos entre cada slide', 'pt-event' ) )
		);

		/* ==================================================================
		   SEÇÃO 4 — Textos e Layout
		   ================================================================== */
		add_settings_section(
			'pt_event_texts',
			__( '✏️ Textos e Layout', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Textos exibidos na seção de programação e configurações de layout.', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		add_settings_field( 'titulo_secao_sub', __( 'Subtítulo da seção', 'pt-event' ),
			array( $this, 'render_text_field' ), 'pt-event-settings', 'pt_event_texts',
			array( 'key' => 'titulo_secao_sub', 'placeholder' => 'ex: Confira', 'desc' => __( 'Texto pequeno acima do título principal', 'pt-event' ) )
		);

		add_settings_field( 'titulo_secao', __( 'Título da seção', 'pt-event' ),
			array( $this, 'render_text_field' ), 'pt-event-settings', 'pt_event_texts',
			array( 'key' => 'titulo_secao', 'placeholder' => 'ex: Principais Temas', 'desc' => __( 'Título grande da seção de programação', 'pt-event' ) )
		);

		add_settings_field( 'menu_height', __( 'Altura do menu fixo do site (px)', 'pt-event' ),
			array( $this, 'render_number_field' ), 'pt-event-settings', 'pt_event_texts',
			array( 'key' => 'menu_height', 'min' => 0, 'max' => 300, 'desc' => __( '0 = auto-detectar. Use para ajustar o sticky da barra de horários.', 'pt-event' ) )
		);

		/* ==================================================================
		   SEÇÃO 5 — CSS Customizado
		   ================================================================== */
		add_settings_section(
			'pt_event_custom',
			__( '🔧 CSS Customizado', 'pt-event' ),
			function () {
				echo '<p class="description">' . esc_html__( 'CSS adicional aplicado em todas as páginas do frontend onde o plugin está ativo.', 'pt-event' ) . '</p>';
			},
			'pt-event-settings'
		);

		add_settings_field( 'custom_css', __( 'CSS adicional', 'pt-event' ),
			array( $this, 'render_textarea_field' ), 'pt-event-settings', 'pt_event_custom', array( 'key' => 'custom_css' ) );
	}

	/* ------------------------------------------------------------------
	   Render helpers
	   ------------------------------------------------------------------ */

	public function render_color_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		$name     = 'pt_event_settings[' . esc_attr( $args['key'] ) . ']';
		echo '<input type="color" name="' . $name . '" value="' . esc_attr( $value ) . '" style="width:50px;height:34px;padding:2px;cursor:pointer;" />';
		echo ' <input type="text" value="' . esc_attr( $value ) . '" class="pt-event-color-hex" style="width:90px;" readonly />';
		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	public function render_number_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		$min      = isset( $args['min'] ) ? $args['min'] : 0;
		$max      = isset( $args['max'] ) ? $args['max'] : 100;
		echo '<input type="number" name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" style="width:80px;" />';
		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	public function render_text_field( $args ) {
		$settings    = \PTEvent\Helpers\Helpers::get_settings();
		$value       = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		echo '<input type="text" name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr( $placeholder ) . '" />';
		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	public function render_textarea_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '';
		echo '<textarea name="pt_event_settings[' . esc_attr( $args['key'] ) . ']" rows="10" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
	}

	public function render_checkbox_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$value    = isset( $settings[ $args['key'] ] ) ? $settings[ $args['key'] ] : '0';
		$name     = 'pt_event_settings[' . esc_attr( $args['key'] ) . ']';
		echo '<input type="hidden" name="' . $name . '" value="0" />';
		echo '<label>';
		echo '<input type="checkbox" name="' . $name . '" value="1" ' . checked( $value, '1', false ) . ' />';
		if ( ! empty( $args['desc'] ) ) {
			echo ' ' . esc_html( $args['desc'] );
		}
		echo '</label>';
	}

	public function render_image_field( $args ) {
		$settings = \PTEvent\Helpers\Helpers::get_settings();
		$image_id = isset( $settings[ $args['key'] ] ) ? absint( $settings[ $args['key'] ] ) : 0;
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		$name      = 'pt_event_settings[' . esc_attr( $args['key'] ) . ']';
		?>
		<div class="pt-event-image-upload-wrapper">
			<div class="pt-event-image-preview" style="margin-bottom:8px;">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:200px;max-height:200px;border-radius:8px;border:1px solid #ddd;" />
				<?php endif; ?>
			</div>
			<input type="hidden" name="<?php echo $name; ?>" id="<?php echo esc_attr( $args['key'] ); ?>" value="<?php echo esc_attr( $image_id ); ?>" />
			<button type="button" class="button pt-event-upload-bg-image" data-target="<?php echo esc_attr( $args['key'] ); ?>"><?php esc_html_e( 'Selecionar Imagem', 'pt-event' ); ?></button>
			<button type="button" class="button pt-event-remove-bg-image" data-target="<?php echo esc_attr( $args['key'] ); ?>" <?php echo ! $image_id ? 'style="display:none"' : ''; ?>><?php esc_html_e( 'Remover', 'pt-event' ); ?></button>
			<?php if ( ! empty( $args['desc'] ) ) : ?>
				<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	   Sanitize
	   ------------------------------------------------------------------ */

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

		$sanitized['foto_fundo_participante'] = isset( $input['foto_fundo_participante'] ) ? absint( $input['foto_fundo_participante'] ) : 0;
		$sanitized['titulo_secao_sub']        = isset( $input['titulo_secao_sub'] ) ? sanitize_text_field( $input['titulo_secao_sub'] ) : '';
		$sanitized['titulo_secao']            = isset( $input['titulo_secao'] ) ? sanitize_text_field( $input['titulo_secao'] ) : '';
		$sanitized['menu_height']             = isset( $input['menu_height'] ) ? absint( $input['menu_height'] ) : 0;
		$sanitized['card_nome_size']          = isset( $input['card_nome_size'] ) ? max( 10, min( 30, absint( $input['card_nome_size'] ) ) ) : 15;
		$sanitized['card_cargo_size']         = isset( $input['card_cargo_size'] ) ? max( 8, min( 24, absint( $input['card_cargo_size'] ) ) ) : 13;
		$sanitized['carousel_autoplay']       = isset( $input['carousel_autoplay'] ) ? absint( $input['carousel_autoplay'] ) : 0;
		$sanitized['carousel_speed']          = isset( $input['carousel_speed'] ) ? max( 1, min( 30, absint( $input['carousel_speed'] ) ) ) : 6;
		$sanitized['custom_css']              = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

		return $sanitized;
	}

	/* ------------------------------------------------------------------
	   Render page
	   ------------------------------------------------------------------ */

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap pt-event-settings-wrap">
			<h1><?php esc_html_e( 'Programação de Eventos — Configurações', 'pt-event' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'pt_event_settings_group' );
				do_settings_sections( 'pt-event-settings' );
				submit_button();
				?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Shortcodes Disponíveis', 'pt-event' ); ?></h2>
			<table class="widefat" style="max-width:600px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'pt-event' ); ?></th>
						<th><?php esc_html_e( 'Descrição', 'pt-event' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>[event_programacao]</code></td>
						<td><?php esc_html_e( 'Programação completa com timeline', 'pt-event' ); ?></td>
					</tr>
					<tr>
						<td><code>[event_palestrantes_home]</code></td>
						<td><?php esc_html_e( 'Carrossel de palestrantes para a home (5 col × 2 lin)', 'pt-event' ); ?></td>
					</tr>
					<tr>
						<td><code>[event_debatedores]</code></td>
						<td><?php esc_html_e( 'Grid de todos os participantes (4 colunas)', 'pt-event' ); ?></td>
					</tr>
				</tbody>
			</table>

			<?php
			if ( isset( $_GET['seeded'] ) ) {
				$count = intval( $_GET['seeded'] );
				if ( isset( $_GET['seed_error'] ) && $_GET['seed_error'] === 'nenhum' ) {
					echo '<div class="notice notice-warning"><p>' . esc_html__( 'Nenhuma opção selecionada. Marque pelo menos um shortcode para popular.', 'pt-event' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d participantes de teste criados com sucesso!', 'pt-event' ), $count ) . '</p></div>';
				}
			}
			?>

			<hr />
			<h2><?php esc_html_e( '🧪 Dados de Teste', 'pt-event' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Cria participantes fictícios (debatedores) para testar o layout dos cards. Apenas dados marcados como seed serão removidos — os cadastros do cliente ficam intactos.', 'pt-event' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
				<?php wp_nonce_field( 'pt_event_seed_action' ); ?>
				<input type="hidden" name="action" value="pt_event_seed" />
				<fieldset style="margin-bottom:12px;">
					<legend><strong><?php esc_html_e( 'Popular seeds para:', 'pt-event' ); ?></strong></legend>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="seed_home" value="1" checked />
						<?php esc_html_e( 'Home — Carrossel (10 participantes com exibir_home=sim)', 'pt-event' ); ?>
					</label>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="seed_debatedores" value="1" checked />
						<?php esc_html_e( 'Debatedores — Grid completo (8 participantes extras)', 'pt-event' ); ?>
					</label>
				</fieldset>
				<button type="submit" class="button button-secondary" onclick="return confirm('Isso vai apagar os seeds anteriores e criar novos participantes de teste. Os cadastros do cliente NÃO serão afetados. Continuar?');">
					<?php esc_html_e( '🌱 Gerar Participantes de Teste', 'pt-event' ); ?>
				</button>
			</form>
		</div>

		<style>
			.pt-event-settings-wrap .form-table th { width: 220px; font-weight: 600; }
			.pt-event-settings-wrap h2 { margin-top: 30px; padding: 12px 0 8px; border-bottom: 2px solid #006B3F; color: #0A1E3D; }
			.pt-event-settings-wrap .description { color: #666; font-style: italic; margin-top: 4px; }
		</style>
		<?php
	}
}
