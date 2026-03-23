<?php
namespace PTEvent\Frontend;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Debatedores {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'event_debatedores', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'titulo'    => '',
			'subtitulo' => '',
			'tipo'      => '',
			'colunas'   => 4,
		), $atts, 'event_debatedores' );

		$settings      = Helpers::get_settings();
		$participantes = Helpers::get_all_participantes( $atts['tipo'] );

		if ( empty( $participantes ) ) {
			return '<p class="pt-debatedores-vazia">' . esc_html__( 'Nenhum participante disponível.', 'pt-event' ) . '</p>';
		}

		$bg_url   = $this->get_bg_url( $settings );
		$css_vars = Shortcode_Cards::build_card_css_vars( $settings );
		$cols     = intval( $atts['colunas'] );

		ob_start();
		?>
		<div class="pt-debatedores" style="<?php echo esc_attr( $css_vars ); ?>">

			<?php if ( ! empty( $atts['titulo'] ) || ! empty( $atts['subtitulo'] ) ) : ?>
				<div class="pt-debatedores-header">
					<?php if ( ! empty( $atts['titulo'] ) ) : ?>
						<h2 class="pt-debatedores-title"><?php echo esc_html( $atts['titulo'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $atts['subtitulo'] ) ) : ?>
						<p class="pt-debatedores-subtitle"><?php echo esc_html( $atts['subtitulo'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="pt-debatedores-grid" style="grid-template-columns: repeat(<?php echo esc_attr( $cols ); ?>, 1fr);">
				<?php foreach ( $participantes as $part ) : ?>
					<?php echo Shortcode_Cards::render_card( $part, $bg_url ); ?>
				<?php endforeach; ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	private function get_bg_url( $settings ) {
		$bg_id = isset( $settings['foto_fundo_participante'] ) ? absint( $settings['foto_fundo_participante'] ) : 0;
		return $bg_id ? wp_get_attachment_image_url( $bg_id, 'medium_large' ) : '';
	}
}
