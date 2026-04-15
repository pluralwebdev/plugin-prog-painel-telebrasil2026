<?php
namespace PTEvent\Frontend;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Home_V2 {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'event_palestrantes_v2', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'colunas'   => 5,
			'linhas'    => 2,
			'titulo'    => '',
			'subtitulo' => '',
		), $atts, 'event_palestrantes_v2' );

		$settings      = Helpers::get_settings();
		$participantes = Helpers::get_participantes_home();

		if ( empty( $participantes ) ) {
			return '<p class="pt-v2-vazia">' . esc_html__( 'Nenhum palestrante disponível.', 'pt-event' ) . '</p>';
		}

		$per_page = intval( $atts['colunas'] ) * intval( $atts['linhas'] );
		$pages    = array_chunk( $participantes, $per_page );
		$bg_url   = $this->get_bg_url( $settings );
		$css_vars = Shortcode_Cards::build_card_css_vars( $settings );
		$css_vars .= Helpers::build_typography_css_vars( $settings, array(
			'--pt-typo-carousel-titulo'    => 'typo_carousel_titulo',
			'--pt-typo-carousel-subtitulo' => 'typo_carousel_subtitulo',
			'--pt-typo-carousel-nome'      => 'typo_carousel_nome',
			'--pt-typo-carousel-empresa'   => 'typo_carousel_empresa',
		) );
		$autoplay = ! empty( $settings['carousel_autoplay'] ) ? '1' : '0';
		$speed    = ! empty( $settings['carousel_speed'] ) ? intval( $settings['carousel_speed'] ) : 6;

		ob_start();
		?>
		<div class="pt-v2-carousel" style="<?php echo esc_attr( $css_vars ); ?>" data-cols="<?php echo esc_attr( $atts['colunas'] ); ?>" data-autoplay="<?php echo esc_attr( $autoplay ); ?>" data-speed="<?php echo esc_attr( $speed ); ?>">

			<?php if ( ! empty( $atts['titulo'] ) || ! empty( $atts['subtitulo'] ) ) : ?>
				<div class="pt-v2-header">
					<?php if ( ! empty( $atts['titulo'] ) ) : ?>
						<h2 class="pt-v2-title"><?php echo esc_html( $atts['titulo'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $atts['subtitulo'] ) ) : ?>
						<p class="pt-v2-subtitle"><?php echo esc_html( $atts['subtitulo'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="pt-v2-track">
				<?php foreach ( $pages as $page_index => $page ) : ?>
					<div class="pt-v2-slide">
						<div class="pt-v2-grid" style="grid-template-columns: repeat(<?php echo esc_attr( $atts['colunas'] ); ?>, 1fr);">
							<?php foreach ( $page as $part ) : ?>
								<?php echo Shortcode_Cards::render_card( $part, $bg_url ); ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $pages ) > 1 ) : ?>
				<div class="pt-v2-bullets">
					<?php foreach ( $pages as $i => $page ) : ?>
						<button class="pt-v2-bullet<?php echo 0 === $i ? ' active' : ''; ?>" data-slide="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	private function get_bg_url( $settings ) {
		$bg_id = isset( $settings['foto_fundo_participante'] ) ? absint( $settings['foto_fundo_participante'] ) : 0;
		return $bg_id ? wp_get_attachment_image_url( $bg_id, 'medium_large' ) : '';
	}
}
