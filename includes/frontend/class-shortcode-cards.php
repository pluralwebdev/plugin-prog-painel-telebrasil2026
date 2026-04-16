<?php
namespace PTEvent\Frontend;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared card rendering logic used by Home Carousel, Debatedores Grid, and Programação.
 */
class Shortcode_Cards {

	/**
	 * Render a single participant card with background image + PNG photo overlay.
	 */
	public static function render_card( $part, $bg_url = '' ) {
		$img_url      = ! empty( $part['foto'] ) ? wp_get_attachment_image_url( $part['foto'], 'medium_large' ) : '';
		$nome         = ! empty( $part['nome'] ) ? $part['nome'] : '';
		$empresa      = ! empty( $part['empresa'] ) ? $part['empresa'] : '';
		$cargo        = ! empty( $part['cargo'] ) ? $part['cargo'] : '';
		$initials     = Helpers::get_initials( $nome );
		$foto_largura = ! empty( $part['foto_largura'] ) ? absint( $part['foto_largura'] ) : 0;
		$foto_unidade = ( ! empty( $part['foto_unidade'] ) && in_array( $part['foto_unidade'], array( '%', 'px' ), true ) ) ? $part['foto_unidade'] : '%';
		$foto_style   = $foto_largura ? ' style="width:' . $foto_largura . $foto_unidade . '"' : '';

		ob_start();
		?>
		<div class="pt-card-participante">
			<div class="pt-card-foto-wrapper">
				<?php if ( $bg_url ) : ?>
					<img class="pt-card-foto-bg" src="<?php echo esc_url( $bg_url ); ?>" alt="" loading="lazy" />
				<?php endif; ?>
				<?php if ( $img_url ) : ?>
					<img class="pt-card-foto-person" src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $nome ); ?>" loading="lazy"<?php echo $foto_style; ?> />
				<?php else : ?>
					<div class="pt-card-foto-placeholder"><?php echo esc_html( $initials ); ?></div>
				<?php endif; ?>
			</div>
			<div class="pt-card-info">
				<div class="pt-card-nome"><?php echo esc_html( $nome ); ?></div>
				<?php if ( $empresa ) : ?>
					<div class="pt-card-empresa"><?php echo esc_html( $empresa ); ?></div>
				<?php elseif ( $cargo ) : ?>
					<div class="pt-card-empresa"><?php echo esc_html( $cargo ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build CSS variables for card theming.
	 */
	public static function build_card_css_vars( $settings ) {
		$vars = array(
			'--pt-primaria'        => $settings['cor_primaria'],
			'--pt-escura'          => $settings['cor_escura'],
			'--pt-cor-nome'        => $settings['cor_nome_participante'],
			'--pt-cor-cargo'       => $settings['cor_cargo_participante'],
			'--pt-card-nome-size'  => ( ! empty( $settings['card_nome_size'] ) ? $settings['card_nome_size'] . 'px' : '15px' ),
			'--pt-card-cargo-size' => ( ! empty( $settings['card_cargo_size'] ) ? $settings['card_cargo_size'] . 'px' : '13px' ),
		);

		$css = '';
		foreach ( $vars as $prop => $val ) {
			if ( $val ) {
				$css .= $prop . ':' . $val . ';';
			}
		}
		return $css;
	}
}
