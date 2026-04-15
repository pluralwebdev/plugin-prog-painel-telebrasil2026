<?php
namespace PTEvent\Admin;

use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ordenar_Participantes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_pt_event_salvar_ordem_participantes', array( $this, 'ajax_salvar_ordem' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=pt_participante',
			__( 'Ordenar Participantes', 'pt-event' ),
			__( 'Ordenar', 'pt-event' ),
			'manage_options',
			'pt-event-ordenar-participantes',
			array( $this, 'render_page' )
		);
	}

	// =========================================================================
	// Render Page
	// =========================================================================

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$posts = get_posts( array(
			'post_type'      => 'pt_participante',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		) );

		$nonce = wp_create_nonce( 'pt_event_ordenar_participantes' );
		?>
		<div class="wrap pt-ord-wrap">
			<h1><?php esc_html_e( 'Ordenar Participantes', 'pt-event' ); ?></h1>
			<p class="description">
				Arraste os participantes para definir a ordem de exibição nos shortcodes
				<code>[event_debatedores]</code> e <code>[event_palestrantes_home]</code>.
				Participantes com a mesma posição inicial (0) aparecerão na ordem cadastro até você salvar.
			</p>

			<div class="pt-ord-toolbar">
				<button id="pt-ord-salvar" class="button button-primary button-large">
					Salvar Ordem
				</button>
				<span id="pt-ord-status" style="margin-left:12px;font-size:13px;color:#666;"></span>
			</div>

			<div id="pt-ord-list">
				<?php foreach ( $posts as $i => $post ) :
					$nome  = get_post_meta( $post->ID, '_pt_event_nome', true ) ?: $post->post_title;
					$cargo = get_post_meta( $post->ID, '_pt_event_cargo', true );
					$foto_id = get_post_meta( $post->ID, '_pt_event_foto', true );
					$foto_url = $foto_id ? wp_get_attachment_image_url( absint( $foto_id ), array( 48, 48 ) ) : '';
					$confirmado = get_post_meta( $post->ID, '_pt_event_confirmado', true );
					$home       = get_post_meta( $post->ID, '_pt_event_exibir_home', true );
				?>
				<div class="pt-ord-item" data-id="<?php echo esc_attr( $post->ID ); ?>">
					<span class="pt-ord-handle" title="Arrastar para reordenar">&#9776;</span>

					<div class="pt-ord-foto">
						<?php if ( $foto_url ) : ?>
							<img src="<?php echo esc_url( $foto_url ); ?>" alt="" />
						<?php else : ?>
							<div class="pt-ord-initials"><?php echo esc_html( Helpers::get_initials( $nome ) ); ?></div>
						<?php endif; ?>
					</div>

					<div class="pt-ord-info">
						<strong><?php echo esc_html( $nome ); ?></strong>
						<?php if ( $cargo ) : ?>
							<span class="pt-ord-cargo"><?php echo esc_html( $cargo ); ?></span>
						<?php endif; ?>
					</div>

					<div class="pt-ord-badges">
						<?php if ( 'sim' === $confirmado ) : ?>
							<span class="pt-ord-badge pt-ord-badge-ok">Confirmado</span>
						<?php endif; ?>
						<?php if ( 'sim' === $home ) : ?>
							<span class="pt-ord-badge pt-ord-badge-home">Home</span>
						<?php endif; ?>
					</div>

					<span class="pt-ord-pos"><?php echo esc_html( $i + 1 ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>

			<?php if ( empty( $posts ) ) : ?>
				<p><?php esc_html_e( 'Nenhum participante publicado encontrado.', 'pt-event' ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.pt-ord-wrap { max-width: 720px; }
			.pt-ord-toolbar { margin: 16px 0; }

			#pt-ord-list {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				overflow: hidden;
			}

			.pt-ord-item {
				display: flex;
				align-items: center;
				gap: 12px;
				padding: 10px 14px;
				border-bottom: 1px solid #f0f0f1;
				background: #fff;
				user-select: none;
				transition: background 0.1s;
			}
			.pt-ord-item:last-child { border-bottom: none; }
			.pt-ord-item.pt-sortable-ghost { opacity: 0.4; background: #e8f0fe; }
			.pt-ord-item.pt-sortable-chosen { box-shadow: 0 3px 16px rgba(0,0,0,0.15); z-index: 10; }

			.pt-ord-handle {
				cursor: grab;
				color: #bbb;
				font-size: 18px;
				padding: 0 4px;
				flex-shrink: 0;
			}
			.pt-ord-handle:active { cursor: grabbing; }

			.pt-ord-foto {
				width: 40px;
				height: 40px;
				border-radius: 50%;
				overflow: hidden;
				flex-shrink: 0;
				background: #f0f0f1;
				display: flex;
				align-items: center;
				justify-content: center;
				border: 1px solid #ddd;
			}
			.pt-ord-foto img { width: 100%; height: 100%; object-fit: cover; }
			.pt-ord-initials { font-size: 13px; font-weight: 700; color: #666; }

			.pt-ord-info {
				flex: 1;
				min-width: 0;
			}
			.pt-ord-info strong { display: block; font-size: 14px; color: #1d2327; }
			.pt-ord-cargo { font-size: 12px; color: #777; }

			.pt-ord-badges { display: flex; gap: 4px; flex-shrink: 0; }
			.pt-ord-badge {
				font-size: 11px;
				font-weight: 600;
				padding: 2px 7px;
				border-radius: 3px;
			}
			.pt-ord-badge-ok   { background: #d1fae5; color: #065f46; }
			.pt-ord-badge-home { background: #dbeafe; color: #1e40af; }

			.pt-ord-pos {
				width: 24px;
				text-align: right;
				font-size: 12px;
				color: #bbb;
				flex-shrink: 0;
			}
		</style>

		<?php
		// Attach init JS to the sortable handle so it runs AFTER sortable.min.js loads in the footer.
		$init_js = "(function($) {
			var nonce = '" . esc_js( $nonce ) . "';

			var list = document.getElementById('pt-ord-list');
			if (list) {
				new Sortable(list, {
					handle:     '.pt-ord-handle',
					draggable:  '.pt-ord-item',
					animation:  150,
					ghostClass: 'pt-sortable-ghost',
					chosenClass:'pt-sortable-chosen',
					onEnd: function() {
						\$('#pt-ord-list .pt-ord-item').each(function(i) {
							\$(this).find('.pt-ord-pos').text(i + 1);
						});
					}
				});
			}

			\$('#pt-ord-salvar').on('click', function() {
				var \$btn = \$(this).prop('disabled', true).text('Salvando...');
				var ids = [];
				\$('#pt-ord-list .pt-ord-item').each(function() {
					ids.push(\$(this).data('id'));
				});

				\$.post(ajaxurl, {
					action: 'pt_event_salvar_ordem_participantes',
					nonce:  nonce,
					ids:    ids.join(',')
				}, function(res) {
					\$btn.prop('disabled', false).text('Salvar Ordem');
					if (res.success) {
						\$('#pt-ord-status').text('Ordem salva com sucesso!').css('color', '#00a32a');
						setTimeout(function() { \$('#pt-ord-status').text(''); }, 3000);
					} else {
						\$('#pt-ord-status').text('Erro ao salvar.').css('color', '#d63638');
					}
				}).fail(function() {
					\$btn.prop('disabled', false).text('Salvar Ordem');
					\$('#pt-ord-status').text('Erro na requisição.').css('color', '#d63638');
				});
			});
		})(jQuery);";
		wp_add_inline_script( 'pt-event-sortable', $init_js );
		?>
		<?php
	}

	// =========================================================================
	// AJAX: Save order
	// =========================================================================

	public function ajax_salvar_ordem() {
		check_ajax_referer( 'pt_event_ordenar_participantes', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão.' );
		}

		$ids_raw = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		if ( empty( $ids_raw ) ) {
			wp_send_json_error( 'Nenhum dado recebido.' );
		}

		$ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );

		foreach ( $ids as $menu_order => $post_id ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'menu_order' => $menu_order,
			) );
		}

		wp_send_json_success( array( 'saved' => count( $ids ) ) );
	}
}
