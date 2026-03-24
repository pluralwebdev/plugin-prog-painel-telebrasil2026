<?php
namespace PTEvent\Frontend;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Patrocinadores {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'event_patrocinadores', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$settings = Helpers::get_settings();

		$card_bg     = ! empty( $settings['pat_card_bg'] ) ? $settings['pat_card_bg'] : '#ffffff';
		$card_radius = ! empty( $settings['pat_card_radius'] ) ? intval( $settings['pat_card_radius'] ) : 10;
		$card_shadow = ! empty( $settings['pat_card_shadow'] ) ? $settings['pat_card_shadow'] : '0 0 15px rgba(0,0,0,0.30)';
		$title_color = ! empty( $settings['pat_title_color'] ) ? $settings['pat_title_color'] : '#006B3F';
		$title_size  = ! empty( $settings['pat_title_size'] ) ? intval( $settings['pat_title_size'] ) : 20;

		// Get all cotas ordered by _pt_cota_order
		$cotas = get_terms( array(
			'taxonomy'   => 'pt_cota_patrocinio',
			'hide_empty' => true,
			'orderby'    => 'meta_value_num',
			'meta_key'   => '_pt_cota_order',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $cotas ) || empty( $cotas ) ) {
			return '';
		}

		$css_vars = '--pt-pat-card-bg:' . $card_bg . ';'
			. '--pt-pat-card-radius:' . $card_radius . 'px;'
			. '--pt-pat-card-shadow:' . $card_shadow . ';'
			. '--pt-pat-title-color:' . $title_color . ';'
			. '--pt-pat-title-size:' . $title_size . 'px;';

		$css_vars .= Helpers::build_typography_css_vars( $settings, array(
			'--pt-typo-pat-titulo' => 'typo_pat_titulo',
		) );

		ob_start();
		?>
		<div class="pt-patrocinadores" style="<?php echo esc_attr( $css_vars ); ?>">
			<?php foreach ( $cotas as $cota ) :
				$width    = absint( get_term_meta( $cota->term_id, '_pt_cota_width', true ) ) ?: 196;
				$height   = absint( get_term_meta( $cota->term_id, '_pt_cota_height', true ) ) ?: 88;
				$cols     = absint( get_term_meta( $cota->term_id, '_pt_cota_cols', true ) ) ?: 6;
				$logo_pct = absint( get_term_meta( $cota->term_id, '_pt_cota_logo_pct', true ) ) ?: 55;

				$patrocinadores = get_posts( array(
					'post_type'      => 'pt_patrocinador',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'meta_key'       => '_pt_patrocinador_ordem',
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'tax_query'      => array(
						array(
							'taxonomy' => 'pt_cota_patrocinio',
							'field'    => 'term_id',
							'terms'    => $cota->term_id,
						),
					),
				) );

				if ( empty( $patrocinadores ) ) {
					continue;
				}

				$section_style = '--pt-pat-cols:' . $cols . ';'
					. '--pt-pat-card-w:' . $width . 'px;'
					. '--pt-pat-card-h:' . $height . 'px;'
					. '--pt-pat-logo-pct:' . $logo_pct . '%;';
			?>
				<div class="pt-pat-section" style="<?php echo esc_attr( $section_style ); ?>">
					<h3 class="pt-pat-section-title"><?php echo esc_html( strtoupper( $cota->name ) ); ?></h3>
					<div class="pt-pat-grid">
						<?php foreach ( $patrocinadores as $pat ) :
							$logo_id  = get_post_meta( $pat->ID, '_pt_patrocinador_logo', true );
							$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium_large' ) : '';
							$url      = get_post_meta( $pat->ID, '_pt_patrocinador_url', true );
							$nome     = $pat->post_title;

							$tag_open  = $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="pt-pat-card" title="' . esc_attr( $nome ) . '">' : '<div class="pt-pat-card">';
							$tag_close = $url ? '</a>' : '</div>';
						?>
							<?php echo $tag_open; ?>
								<?php if ( $logo_url ) : ?>
									<img class="pt-pat-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome ); ?>" loading="lazy" />
								<?php else : ?>
									<span class="pt-pat-placeholder"><?php echo esc_html( $nome ); ?></span>
								<?php endif; ?>
							<?php echo $tag_close; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
