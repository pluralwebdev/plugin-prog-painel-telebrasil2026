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
		$settings  = Helpers::get_settings();
		$agrupadas = Helpers::get_sessoes_agrupadas();

		if ( empty( $agrupadas ) ) {
			return '<p class="pt-programacao-vazia">' . esc_html__( 'Nenhuma programação disponível.', 'pt-event' ) . '</p>';
		}

		$css_vars = $this->build_css_vars( $settings );
		$dias     = array_keys( $agrupadas );

		ob_start();
		?>
		<div class="pt-programacao" style="<?php echo esc_attr( $css_vars ); ?>">

			<?php echo $this->render_day_selector( $dias, $settings ); ?>

			<div class="pt-time-nav-wrapper" id="ptTimeNavWrapper">
				<div class="pt-time-nav" id="ptTimeNav"></div>
			</div>

			<div class="pt-prog-container">
				<?php $dia_index = 0; ?>
				<?php foreach ( $agrupadas as $dia => $horarios ) : ?>
					<div id="pt-dia-<?php echo esc_attr( $dia_index ); ?>" class="pt-day-content<?php echo 0 === $dia_index ? ' active' : ''; ?>">

						<?php foreach ( $horarios as $horario => $sessoes ) :
							$parts     = explode( ' - ', $horario );
							$hora_ini  = isset( $parts[0] ) ? trim( $parts[0] ) : '';
							$hora_fim  = isset( $parts[1] ) ? trim( $parts[1] ) : '';
							$is_especial = false;
							foreach ( $sessoes as $s ) {
								$t = strtolower( $s['titulo'] );
								if ( false !== strpos( $t, 'almoço' ) || false !== strpos( $t, 'almoco' ) || false !== strpos( $t, 'coffee' ) || false !== strpos( $t, 'intervalo' ) || false !== strpos( $t, 'coquetel' ) ) {
									$is_especial = true;
								}
							}
						?>
							<?php foreach ( $sessoes as $sessao ) : ?>
								<div class="pt-sessao-card<?php echo $is_especial ? ' pt-sessao-especial' : ''; ?>" data-hora="<?php echo esc_attr( $hora_ini ); ?>"<?php echo $is_especial ? ' data-especial="1"' : ''; ?>>
									<div class="pt-sessao-timeline">
										<div class="pt-timeline-horario">
											<div class="pt-timeline-hora-inicio"><?php echo esc_html( $hora_ini ); ?></div>
											<?php if ( $hora_fim ) : ?>
												<div class="pt-timeline-hora-fim">até <?php echo esc_html( $hora_fim ); ?></div>
											<?php endif; ?>
										</div>
									</div>
									<div class="pt-sessao-content">
										<div class="pt-sessao-content-inner">
											<?php if ( ! empty( $sessao['subtitulo'] ) ) : ?>
												<div class="pt-sessao-tipo"><?php echo esc_html( $sessao['subtitulo'] ); ?></div>
											<?php elseif ( ! empty( $sessao['trilha'] ) ) : ?>
												<div class="pt-sessao-tipo"><?php echo esc_html( $sessao['trilha'] ); ?></div>
											<?php endif; ?>

											<?php if ( ! empty( $sessao['titulo'] ) ) : ?>
												<div class="pt-sessao-titulo"><?php echo esc_html( $sessao['titulo'] ); ?></div>
											<?php endif; ?>

											<?php if ( ! empty( $sessao['descricao'] ) ) : ?>
												<div class="pt-sessao-desc"><?php echo wp_kses_post( $sessao['descricao'] ); ?></div>
											<?php endif; ?>
										</div>

										<?php
										$participantes = Helpers::get_participantes_by_sessao( $sessao['id'] );
										if ( ! empty( $participantes ) ) :
											$grouped = $this->group_by_papel( $participantes );
											$bg_url  = $this->get_bg_url( $settings );
										?>
											<div class="pt-participantes-area">
												<?php foreach ( $grouped as $papel => $parts ) : ?>
													<div class="pt-papel-section">
														<div class="pt-papel-label"><?php echo esc_html( $papel ); ?></div>
														<div class="pt-participantes-row pt-participantes-grid">
															<?php foreach ( $parts as $part ) : ?>
																<?php echo Shortcode_Cards::render_card( $part, $bg_url ); ?>
															<?php endforeach; ?>
														</div>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endforeach; ?>

					</div>
					<?php $dia_index++; ?>
				<?php endforeach; ?>
			</div>

			<?php echo $this->render_day_selector( $dias, $settings, true ); ?>

		</div>
		<?php
		return ob_get_clean();
	}

	private function render_day_selector( $dias, $settings, $bottom = false ) {
		$wrapper_class = $bottom ? 'pt-day-selector-bottom' : 'pt-day-selector';
		$html = '<div class="' . $wrapper_class . '">';

		if ( ! $bottom ) {
			$sub   = ! empty( $settings['titulo_secao_sub'] ) ? $settings['titulo_secao_sub'] : 'Confira';
			$title = ! empty( $settings['titulo_secao'] ) ? $settings['titulo_secao'] : 'Principais Temas';
			$html .= '<div class="pt-day-selector-title">';
			$html .= '<div class="pt-day-selector-sub">' . esc_html( $sub ) . '</div>';
			$html .= '<h2>' . esc_html( $title ) . '</h2>';
			$html .= '</div>';
			$html .= '<div class="pt-day-badges">';
		}

		foreach ( $dias as $i => $dia ) {
			$timestamp = strtotime( $dia );
			$active    = 0 === $i ? ' active' : '';
			$dia_num   = $timestamp ? date_i18n( 'd', $timestamp ) : ( $i + 1 );
			$dia_mes   = $timestamp ? strtoupper( date_i18n( 'M', $timestamp ) ) : '';
			$dia_ano   = $timestamp ? date_i18n( 'Y', $timestamp ) : '';
			$dia_sem   = $timestamp ? date_i18n( 'l', $timestamp ) : '';
			$dia_label = $timestamp ? date_i18n( 'd/M', $timestamp ) . ' — ' . date_i18n( 'l', $timestamp ) : '';
			$ordinal   = ( $i + 1 ) . 'º Dia';

			$html .= '<div class="pt-day-badge' . $active . '" data-day="pt-dia-' . $i . '" data-label="' . esc_attr( $dia_label ) . '">';
			$html .= '<div class="pt-day-badge-top">' . esc_html( $ordinal ) . '</div>';
			$html .= '<div class="pt-day-badge-body">';
			$html .= '<div class="pt-day-badge-mes">' . esc_html( $dia_mes ) . '</div>';
			$html .= '<div class="pt-day-badge-num">' . esc_html( $dia_num ) . '</div>';
			$html .= '<div class="pt-day-badge-ano">' . esc_html( $dia_ano ) . '</div>';
			$html .= '<div class="pt-day-badge-weekday">' . esc_html( ucfirst( $dia_sem ) ) . '</div>';
			$html .= '</div></div>';
		}

		if ( ! $bottom ) {
			$html .= '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	private function get_bg_url( $settings ) {
		$bg_id = isset( $settings['foto_fundo_participante'] ) ? absint( $settings['foto_fundo_participante'] ) : 0;
		return $bg_id ? wp_get_attachment_image_url( $bg_id, 'medium_large' ) : '';
	}

	private function group_by_papel( $participantes ) {
		$grouped = array();
		foreach ( $participantes as $part ) {
			$papel = ! empty( $part['papel'] ) ? ucfirst( $part['papel'] ) : 'Participante';
			if ( ! isset( $grouped[ $papel ] ) ) {
				$grouped[ $papel ] = array();
			}
			$grouped[ $papel ][] = $part;
		}
		return $grouped;
	}

	private function build_css_vars( $settings ) {
		$vars = array(
			'--pt-primaria'       => $settings['cor_primaria'],
			'--pt-primaria-claro' => $settings['cor_primaria_claro'],
			'--pt-primaria-bg'    => $settings['cor_primaria_bg'],
			'--pt-escura'         => $settings['cor_escura'],
			'--pt-dourado'        => $settings['cor_dourado'],
			'--pt-texto'          => $settings['cor_texto'],
			'--pt-fundo'          => $settings['cor_fundo'],
			'--pt-bg-sessao'      => $settings['cor_fundo_sessao'],
			'--pt-cor-nome'       => $settings['cor_nome_participante'],
			'--pt-cor-cargo'      => $settings['cor_cargo_participante'],
			'--pt-especial'       => $settings['cor_especial'],
			'--pt-border-color'   => $settings['border_color'],
			'--pt-card-nome-size'  => ( ! empty( $settings['card_nome_size'] ) ? $settings['card_nome_size'] . 'px' : '15px' ),
			'--pt-card-cargo-size' => ( ! empty( $settings['card_cargo_size'] ) ? $settings['card_cargo_size'] . 'px' : '13px' ),
		);

		$css = '';
		foreach ( $vars as $prop => $val ) {
			$css .= $prop . ':' . $val . ';';
		}
		return $css;
	}
}
