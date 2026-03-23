<?php
namespace PTEvent\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	private static $instance = null;

	/** Tab definitions: slug => label */
	private $tabs = array();

	/** Which sections belong to each tab */
	private $tab_sections = array();

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->tabs = array(
			'programacao'    => __( 'Programação', 'pt-event' ),
			'participantes'  => __( 'Participantes', 'pt-event' ),
			'patrocinadores' => __( 'Patrocinadores', 'pt-event' ),
			'ferramentas'    => __( 'Ferramentas', 'pt-event' ),
		);

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	private function current_tab() {
		return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'programacao';
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

		$this->register_tab_programacao();
		$this->register_tab_participantes();
		$this->register_tab_patrocinadores();
	}

	/* ======================================================================
	   TAB: Programação
	   ====================================================================== */
	private function register_tab_programacao() {
		$page = 'pt-event-tab-programacao';

		add_settings_section( 'pt_event_colors_prog', __( '🎨 Cores da Programação', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Cores aplicadas na página de programação do evento.', 'pt-event' ) . '</p>'; },
			$page
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
				array( $this, 'render_color_field' ), $page, 'pt_event_colors_prog',
				array( 'key' => $key, 'desc' => $data['desc'] )
			);
		}

		add_settings_section( 'pt_event_colors_card', __( '📋 Cores dos Cards de Sessão', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Cores aplicadas dentro de cada card de sessão.', 'pt-event' ) . '</p>'; },
			$page
		);

		$card_colors = array(
			'cor_fundo_sessao'       => array( 'label' => __( 'Fundo do card', 'pt-event' ), 'desc' => __( 'Background branco de cada sessão', 'pt-event' ) ),
			'cor_nome_participante'  => array( 'label' => __( 'Nome do participante', 'pt-event' ), 'desc' => __( 'Cor do nome dentro do card', 'pt-event' ) ),
			'cor_cargo_participante' => array( 'label' => __( 'Cargo do participante', 'pt-event' ), 'desc' => __( 'Cor do cargo/empresa dentro do card', 'pt-event' ) ),
		);

		foreach ( $card_colors as $key => $data ) {
			add_settings_field( $key, $data['label'],
				array( $this, 'render_color_field' ), $page, 'pt_event_colors_card',
				array( 'key' => $key, 'desc' => $data['desc'] )
			);
		}

		add_settings_section( 'pt_event_texts', __( '✏️ Textos e Layout', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Textos e configurações de layout da programação.', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'titulo_secao_sub', __( 'Subtítulo da seção', 'pt-event' ),
			array( $this, 'render_text_field' ), $page, 'pt_event_texts',
			array( 'key' => 'titulo_secao_sub', 'placeholder' => 'ex: Confira', 'desc' => __( 'Texto pequeno acima do título principal', 'pt-event' ) )
		);

		add_settings_field( 'titulo_secao', __( 'Título da seção', 'pt-event' ),
			array( $this, 'render_text_field' ), $page, 'pt_event_texts',
			array( 'key' => 'titulo_secao', 'placeholder' => 'ex: Principais Temas', 'desc' => __( 'Título grande da seção de programação', 'pt-event' ) )
		);

		add_settings_field( 'menu_height', __( 'Altura do menu fixo do site (px)', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_texts',
			array( 'key' => 'menu_height', 'min' => 0, 'max' => 300, 'desc' => __( '0 = auto-detectar. Use para ajustar o sticky da barra de horários.', 'pt-event' ) )
		);

		add_settings_section( 'pt_event_custom', __( '🔧 CSS Customizado', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'CSS adicional aplicado em todas as páginas do frontend.', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'custom_css', __( 'CSS adicional', 'pt-event' ),
			array( $this, 'render_textarea_field' ), $page, 'pt_event_custom', array( 'key' => 'custom_css' ) );
	}

	/* ======================================================================
	   TAB: Participantes
	   ====================================================================== */
	private function register_tab_participantes() {
		$page = 'pt-event-tab-participantes';

		add_settings_section( 'pt_event_participante_bg', __( '🖼️ Card do Participante', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Imagem de fundo e estilos dos cards de participantes.', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'foto_fundo_participante', __( 'Imagem de fundo', 'pt-event' ),
			array( $this, 'render_image_field' ), $page, 'pt_event_participante_bg',
			array( 'key' => 'foto_fundo_participante', 'desc' => __( 'Upload da imagem verde geométrica (recomendado: PNG ~400×400px)', 'pt-event' ) )
		);

		add_settings_field( 'border_color', __( 'Cor da borda da foto', 'pt-event' ),
			array( $this, 'render_color_field' ), $page, 'pt_event_participante_bg',
			array( 'key' => 'border_color', 'desc' => __( 'Borda ao redor da foto (se aplicável)', 'pt-event' ) )
		);

		add_settings_field( 'card_nome_size', __( 'Tamanho fonte — Nome', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_participante_bg',
			array( 'key' => 'card_nome_size', 'min' => 10, 'max' => 30, 'desc' => __( 'px', 'pt-event' ) )
		);

		add_settings_field( 'card_cargo_size', __( 'Tamanho fonte — Empresa', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_participante_bg',
			array( 'key' => 'card_cargo_size', 'min' => 8, 'max' => 24, 'desc' => __( 'px', 'pt-event' ) )
		);

		add_settings_section( 'pt_event_carousel', __( '🎠 Carrossel da Home', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Configurações do carrossel de palestrantes exibido na home.', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'carousel_autoplay', __( 'Autoplay', 'pt-event' ),
			array( $this, 'render_checkbox_field' ), $page, 'pt_event_carousel',
			array( 'key' => 'carousel_autoplay', 'desc' => __( 'Avançar slides automaticamente', 'pt-event' ) )
		);

		add_settings_field( 'carousel_speed', __( 'Intervalo (segundos)', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_carousel',
			array( 'key' => 'carousel_speed', 'min' => 1, 'max' => 30, 'desc' => __( 'Tempo em segundos entre cada slide', 'pt-event' ) )
		);
	}

	/* ======================================================================
	   TAB: Patrocinadores
	   ====================================================================== */
	private function register_tab_patrocinadores() {
		$page = 'pt-event-tab-patrocinadores';

		add_settings_section( 'pt_event_pat_card', __( '🏢 Card do Patrocinador', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Estilo visual dos cards de patrocinadores. Os tamanhos de cada cota são configurados em Patrocinadores > Cotas.', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'pat_card_bg', __( 'Cor de fundo do card', 'pt-event' ),
			array( $this, 'render_color_field' ), $page, 'pt_event_pat_card',
			array( 'key' => 'pat_card_bg', 'desc' => __( 'Background do card', 'pt-event' ) )
		);

		add_settings_field( 'pat_card_radius', __( 'Border radius (px)', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_pat_card',
			array( 'key' => 'pat_card_radius', 'min' => 0, 'max' => 50, 'desc' => __( 'Arredondamento dos cantos do card', 'pt-event' ) )
		);

		add_settings_field( 'pat_card_shadow', __( 'Box shadow', 'pt-event' ),
			array( $this, 'render_text_field' ), $page, 'pt_event_pat_card',
			array( 'key' => 'pat_card_shadow', 'placeholder' => '0 0 15px rgba(0,0,0,0.30)', 'desc' => __( 'CSS box-shadow do card', 'pt-event' ) )
		);

		add_settings_section( 'pt_event_pat_title', __( '🏷️ Título da Seção', 'pt-event' ),
			function () { echo '<p class="description">' . esc_html__( 'Estilo do título de cada seção de cota (DIAMANTE, PLATINUM, etc.).', 'pt-event' ) . '</p>'; },
			$page
		);

		add_settings_field( 'pat_title_color', __( 'Cor do título', 'pt-event' ),
			array( $this, 'render_color_field' ), $page, 'pt_event_pat_title',
			array( 'key' => 'pat_title_color', 'desc' => __( 'Cor do nome da cota', 'pt-event' ) )
		);

		add_settings_field( 'pat_title_size', __( 'Tamanho do título (px)', 'pt-event' ),
			array( $this, 'render_number_field' ), $page, 'pt_event_pat_title',
			array( 'key' => 'pat_title_size', 'min' => 12, 'max' => 48, 'desc' => __( 'px', 'pt-event' ) )
		);
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
		// Merge with existing settings so we don't lose data from other tabs
		$existing  = get_option( 'pt_event_settings', array() );
		$sanitized = is_array( $existing ) ? $existing : array();

		$color_keys = array(
			'cor_primaria', 'cor_primaria_claro', 'cor_primaria_bg',
			'cor_escura', 'cor_dourado', 'cor_texto', 'cor_fundo',
			'cor_fundo_sessao', 'cor_nome_participante', 'cor_cargo_participante',
			'cor_especial', 'border_color',
			'pat_card_bg', 'pat_title_color',
		);
		foreach ( $color_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_hex_color( $input[ $key ] );
			}
		}

		$text_keys = array( 'titulo_secao_sub', 'titulo_secao', 'pat_card_shadow' );
		foreach ( $text_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		if ( isset( $input['foto_fundo_participante'] ) ) {
			$sanitized['foto_fundo_participante'] = absint( $input['foto_fundo_participante'] );
		}
		if ( isset( $input['menu_height'] ) ) {
			$sanitized['menu_height'] = absint( $input['menu_height'] );
		}
		if ( isset( $input['card_nome_size'] ) ) {
			$sanitized['card_nome_size'] = max( 10, min( 30, absint( $input['card_nome_size'] ) ) );
		}
		if ( isset( $input['card_cargo_size'] ) ) {
			$sanitized['card_cargo_size'] = max( 8, min( 24, absint( $input['card_cargo_size'] ) ) );
		}
		if ( isset( $input['carousel_autoplay'] ) ) {
			$sanitized['carousel_autoplay'] = absint( $input['carousel_autoplay'] );
		}
		if ( isset( $input['carousel_speed'] ) ) {
			$sanitized['carousel_speed'] = max( 1, min( 30, absint( $input['carousel_speed'] ) ) );
		}
		if ( isset( $input['pat_card_radius'] ) ) {
			$sanitized['pat_card_radius'] = max( 0, min( 50, absint( $input['pat_card_radius'] ) ) );
		}
		if ( isset( $input['pat_title_size'] ) ) {
			$sanitized['pat_title_size'] = max( 12, min( 48, absint( $input['pat_title_size'] ) ) );
		}
		if ( isset( $input['custom_css'] ) ) {
			$sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
		}

		return $sanitized;
	}

	/* ------------------------------------------------------------------
	   Render page
	   ------------------------------------------------------------------ */

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = $this->current_tab();
		$base_url   = admin_url( 'admin.php?page=pt-event-settings' );
		?>
		<div class="wrap pt-event-settings-wrap">
			<h1><?php esc_html_e( 'Programação de Eventos — Configurações', 'pt-event' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'ferramentas' === $active_tab ) : ?>
				<?php $this->render_tab_ferramentas(); ?>
			<?php else : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'pt_event_settings_group' );
					do_settings_sections( 'pt-event-tab-' . $active_tab );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>

		<style>
			.pt-event-settings-wrap .nav-tab-wrapper { margin-bottom: 20px; }
			.pt-event-settings-wrap .nav-tab { font-size: 14px; padding: 8px 16px; }
			.pt-event-settings-wrap .nav-tab-active { background: #fff; border-bottom-color: #fff; font-weight: 600; }
			.pt-event-settings-wrap .form-table th { width: 220px; font-weight: 600; }
			.pt-event-settings-wrap h2 { margin-top: 30px; padding: 12px 0 8px; border-bottom: 2px solid #006B3F; color: #0A1E3D; }
			.pt-event-settings-wrap .description { color: #666; font-style: italic; margin-top: 4px; }
		</style>
		<?php
	}

	/**
	 * Tab: Ferramentas (shortcodes + seeds).
	 */
	private function render_tab_ferramentas() {
		?>
		<h2><?php esc_html_e( '📎 Shortcodes Disponíveis', 'pt-event' ); ?></h2>
		<table class="widefat" style="max-width:700px;">
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
					<td><?php esc_html_e( 'Grid de debatedores (4 colunas)', 'pt-event' ); ?></td>
				</tr>
				<tr>
					<td><code>[event_patrocinadores]</code></td>
					<td><?php esc_html_e( 'Seções de patrocinadores agrupadas por cota', 'pt-event' ); ?></td>
				</tr>
			</tbody>
		</table>

		<?php
		if ( isset( $_GET['seeded'] ) ) {
			$count     = intval( $_GET['seeded'] );
			$count_pat = isset( $_GET['seeded_pat'] ) ? intval( $_GET['seeded_pat'] ) : 0;
			if ( isset( $_GET['seed_error'] ) && $_GET['seed_error'] === 'nenhum' ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Nenhuma opção selecionada. Marque pelo menos um shortcode para popular.', 'pt-event' ) . '</p></div>';
			} else {
				$parts = array();
				$part_count = $count - $count_pat;
				if ( $part_count > 0 ) {
					$parts[] = sprintf( __( '%d participantes', 'pt-event' ), $part_count );
				}
				if ( $count_pat > 0 ) {
					$parts[] = sprintf( __( '%d patrocinadores', 'pt-event' ), $count_pat );
				}
				$msg = implode( ' + ', $parts ) . __( ' de teste criados com sucesso!', 'pt-event' );
				echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
			}
		}
		?>

		<hr />
		<h2><?php esc_html_e( '🧪 Dados de Teste', 'pt-event' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Cria dados fictícios para testar o layout. Apenas dados marcados como seed serão removidos — cadastros do cliente ficam intactos.', 'pt-event' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
			<?php wp_nonce_field( 'pt_event_seed_action' ); ?>
			<input type="hidden" name="action" value="pt_event_seed" />
			<fieldset style="margin-bottom:12px;">
				<legend><strong><?php esc_html_e( 'Popular seeds para:', 'pt-event' ); ?></strong></legend>
				<label style="display:block;margin:6px 0;">
					<input type="checkbox" name="seed_home" value="1" checked />
					<?php esc_html_e( 'Home — Carrossel (20 participantes com exibir_home=sim)', 'pt-event' ); ?>
				</label>
				<label style="display:block;margin:6px 0;">
					<input type="checkbox" name="seed_debatedores" value="1" checked />
					<?php esc_html_e( 'Debatedores — Grid completo (8 participantes extras)', 'pt-event' ); ?>
				</label>
				<label style="display:block;margin:6px 0;">
					<input type="checkbox" name="seed_patrocinadores" value="1" checked />
					<?php esc_html_e( 'Patrocinadores — 22 sponsors nas 5 cotas com logos placeholder', 'pt-event' ); ?>
				</label>
			</fieldset>
			<button type="submit" class="button button-secondary" onclick="return confirm('Isso vai apagar os seeds anteriores e criar novos de teste. Cadastros do cliente NÃO serão afetados. Continuar?');">
				<?php esc_html_e( '🌱 Gerar Dados de Teste', 'pt-event' ); ?>
			</button>
		</form>
		<?php
	}
}
