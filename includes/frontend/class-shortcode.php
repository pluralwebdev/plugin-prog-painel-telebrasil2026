<?php
namespace PTEvent\Frontend;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'event_programacao', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		wp_enqueue_style( 'pt-event-frontend' );

		$settings  = Helpers::get_settings();
		$agrupadas = Helpers::get_sessoes_agrupadas();

		if ( empty( $agrupadas ) ) {
			return '<p class="pt-programacao-vazia">' . esc_html__( 'Nenhuma programação disponível.', 'pt-event' ) . '</p>';
		}

		$css_vars = $this->build_css_vars( $settings );

		ob_start();
		?>
		<div class="pt-programacao" style="<?php echo esc_attr( $css_vars ); ?>">
			<?php foreach ( $agrupadas as $dia => $horarios ) : ?>
				<div class="pt-dia">
					<h2 class="pt-dia-titulo"><?php echo esc_html( Helpers::format_dia_label( $dia ) ); ?></h2>

					<?php foreach ( $horarios as $horario => $sessoes ) : ?>
						<div class="pt-bloco-horario">
							<div class="pt-horario"><?php echo esc_html( $horario ); ?></div>

							<div class="pt-sessoes <?php echo count( $sessoes ) > 1 ? 'pt-sessoes-simultaneas' : ''; ?>">
								<?php foreach ( $sessoes as $sessao ) : ?>
									<div class="pt-sessao">
										<?php if ( ! empty( $sessao['titulo'] ) ) : ?>
											<h3 class="pt-titulo"><?php echo esc_html( $sessao['titulo'] ); ?></h3>
										<?php endif; ?>

										<?php if ( ! empty( $sessao['subtitulo'] ) ) : ?>
											<h4 class="pt-subtitulo"><?php echo esc_html( $sessao['subtitulo'] ); ?></h4>
										<?php endif; ?>

										<?php if ( ! empty( $sessao['descricao'] ) ) : ?>
											<div class="pt-descricao"><?php echo wp_kses_post( $sessao['descricao'] ); ?></div>
										<?php endif; ?>

										<?php if ( ! empty( $sessao['trilha'] ) ) : ?>
											<span class="pt-trilha"><?php echo esc_html( $sessao['trilha'] ); ?></span>
										<?php endif; ?>

										<?php
										$participantes = Helpers::get_participantes_by_sessao( $sessao['id'] );
										if ( ! empty( $participantes ) ) :
										?>
											<div class="pt-participantes">
												<?php foreach ( $participantes as $part ) : ?>
													<div class="pt-participante">
														<div class="pt-foto">
															<?php if ( ! empty( $part['foto'] ) ) : ?>
																<?php
																$img_url = wp_get_attachment_image_url( $part['foto'], 'medium' );
																if ( $img_url ) :
																?>
																	<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $part['nome'] ); ?>" loading="lazy" />
																<?php endif; ?>
															<?php endif; ?>
														</div>
														<div class="pt-nome"><?php echo esc_html( $part['nome'] ); ?></div>
														<?php if ( ! empty( $part['cargo'] ) ) : ?>
															<div class="pt-cargo"><?php echo nl2br( esc_html( $part['cargo'] ) ); ?></div>
														<?php endif; ?>
														<?php if ( ! empty( $part['papel'] ) ) : ?>
															<div class="pt-papel"><?php echo esc_html( $part['papel'] ); ?></div>
														<?php endif; ?>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function build_css_vars( $settings ) {
		$vars = array(
			'--pt-bg-sessao'    => $settings['cor_fundo_sessao'],
			'--pt-cor-cabecalho' => $settings['cor_cabecalho'],
			'--pt-cor-texto'     => $settings['cor_texto'],
			'--pt-cor-nome'      => $settings['cor_nome_participante'],
			'--pt-cor-cargo'     => $settings['cor_cargo_participante'],
			'--pt-border-color'  => $settings['border_color'],
			'--pt-border-width'  => $settings['border_width'] . 'px',
			'--pt-border-radius' => $settings['border_radius'] . '%',
		);

		$css = '';
		foreach ( $vars as $prop => $val ) {
			$css .= $prop . ':' . $val . ';';
		}

		return $css;
	}
}
