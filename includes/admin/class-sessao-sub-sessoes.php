<?php
namespace PTEvent\Admin;

use PTEvent\Database\Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta box "Sub-sessões da Sessão" no CPT pt_sessao.
 *
 * Permite organizar a sessão em blocos nomeados (Palestra N: ..., Debate N - ...),
 * cada um com sua própria lista de participantes. Os dados são salvos em:
 * - _pt_event_sub_sessoes (post meta, JSON) — fonte canônica da estrutura
 * - junction wp_evento_sessao_participantes — espelha participantes para shortcodes globais
 *
 * O meta box "Participantes da Sessão" (Sessao_Participantes) continua sendo a fonte de
 * Mensagem inicial e Moderador. Sub-sessões cuidam dos blocos Palestra / Debate.
 */
class Sessao_Sub_Sessoes {

	private static $instance = null;

	const META_KEY = '_pt_event_sub_sessoes';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		// Priority 30 — roda DEPOIS de Sessao_Participantes (priority 20),
		// que limpa a junction. Aqui só adicionamos (Relationship::add é upsert).
		add_action( 'save_post_pt_sessao', array( $this, 'save' ), 30 );
	}

	public function add_meta_box() {
		add_meta_box(
			'pt_event_sub_sessoes',
			__( 'Sub-sessões da Sessão', 'pt-event' ),
			array( $this, 'render' ),
			'pt_sessao',
			'normal',
			'default'
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'pt_event_sub_sessoes', 'pt_event_sub_sessoes_nonce' );

		$raw  = get_post_meta( $post->ID, self::META_KEY, true );
		$data = $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$tipos = array(
			'palestra' => __( 'Palestra', 'pt-event' ),
			'debate'   => __( 'Debate', 'pt-event' ),
		);
		?>
		<div class="pt-sub-sessoes-box">
			<p class="description">
				<?php esc_html_e( 'Sub-sessões são blocos nomeados (Palestra, Debate) com participantes próprios — útil para painéis com múltiplos segmentos. O título é livre (ex: "Palestra 1: A jornada digital europeia" ou "Debate 1 - Infraestruturas integradas").', 'pt-event' ); ?>
			</p>

			<input type="hidden" name="pt_event_sub_sessoes_json" id="pt-sub-sessoes-json" value="<?php echo esc_attr( wp_json_encode( $data ) ); ?>" />

			<div id="pt-sub-sessoes-list"></div>

			<button type="button" class="button button-primary" id="pt-add-sub-sessao">
				<?php esc_html_e( '+ Adicionar Sub-sessão', 'pt-event' ); ?>
			</button>
		</div>

		<style>
			.pt-sub-sessoes-box { padding: 8px 0; }
			.pt-sub-sessao { border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 14px; background: #fff; }
			.pt-sub-sessao-header {
				display: flex; align-items: center; gap: 8px;
				background: #2271b1; color: #fff; padding: 8px 12px; border-radius: 4px 4px 0 0;
			}
			.pt-sub-sessao-header.tipo-debate { background: #5b8def; }
			.pt-sub-sessao-handle { cursor: grab; padding: 0 6px; font-size: 16px; line-height: 1; user-select: none; }
			.pt-sub-sessao-handle:active { cursor: grabbing; }
			.pt-sub-sessao-header select.pt-ss-tipo { background: rgba(255,255,255,0.95); border: none; border-radius: 3px; padding: 3px 6px; font-size: 12px; min-width: 90px; }
			.pt-sub-sessao-header input.pt-ss-titulo { flex: 1; background: rgba(255,255,255,0.95); border: none; border-radius: 3px; padding: 4px 8px; font-size: 13px; }
			.pt-sub-sessao-remove { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 2px 10px; border-radius: 3px; cursor: pointer; font-size: 14px; line-height: 1; }
			.pt-sub-sessao-remove:hover { background: #d63638; border-color: #d63638; }

			.pt-sub-sessao-body { padding: 12px; }
			.pt-sub-search-wrapper { position: relative; margin-bottom: 8px; }
			.pt-sub-search { width: 100%; }
			.pt-sub-search-results {
				position: absolute; top: 100%; left: 0; right: 0; z-index: 100;
				background: #fff; border: 1px solid #c3c4c7; max-height: 240px; overflow-y: auto;
				display: none;
			}
			.pt-sub-search-results .pt-sub-search-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f1; }
			.pt-sub-search-results .pt-sub-search-item:hover { background: #f6f7f7; }
			.pt-sub-search-results .pt-sub-search-item strong { color: #2271b1; }
			.pt-sub-search-results .pt-sub-search-item small { color: #646970; display: block; font-size: 11px; }

			.pt-sub-parts { width: 100%; border-collapse: collapse; margin-top: 4px; }
			.pt-sub-parts th { text-align: left; font-size: 12px; padding: 6px 8px; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; }
			.pt-sub-parts td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
			.pt-sub-parts .col-handle { width: 24px; cursor: grab; color: #999; }
			.pt-sub-parts .col-nome { font-weight: 600; }
			.pt-sub-parts .col-cargo input { width: 100%; }
			.pt-sub-parts .col-actions { width: 40px; text-align: right; }
			.pt-sub-parts-empty { padding: 12px; color: #646970; font-style: italic; text-align: center; }

			.pt-ss-tipo-badge { display: inline-block; padding: 1px 6px; font-size: 10px; border-radius: 3px; background: rgba(0,0,0,0.15); margin-right: 4px; }
		</style>

		<script>
		(function($) {
			var $hidden = $('#pt-sub-sessoes-json');
			var state = [];
			try { state = JSON.parse($hidden.val() || '[]') || []; } catch(e) { state = []; }

			var tipos = <?php echo wp_json_encode( $tipos ); ?>;
			var nonce = (typeof ptEventAdmin !== 'undefined') ? ptEventAdmin.nonce : '';
			var ajaxUrl = (typeof ptEventAdmin !== 'undefined') ? ptEventAdmin.ajaxUrl : ajaxurl;

			function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
			function escA(s) { return (s == null ? '' : s).toString().replace(/"/g, '&quot;'); }

			function syncHidden() {
				$hidden.val(JSON.stringify(state));
			}

			function render() {
				var $list = $('#pt-sub-sessoes-list').empty();
				if (!state.length) {
					$list.append('<div class="pt-sub-parts-empty">Nenhuma sub-sessão. Use o botão abaixo para adicionar.</div>');
					return;
				}
				$.each(state, function(idx, sub) {
					$list.append(renderSubSessao(idx, sub));
				});
				initSortableSubSessoes();
				$list.find('.pt-sub-parts-tbody').each(function() {
					initSortableParts(this);
				});
			}

			function renderSubSessao(idx, sub) {
				var tipo = sub.tipo || 'palestra';
				var headerClass = tipo === 'debate' ? 'tipo-debate' : '';
				var html = '<div class="pt-sub-sessao" data-idx="' + idx + '">';
				html += '<div class="pt-sub-sessao-header ' + headerClass + '">';
				html += '<span class="pt-sub-sessao-handle dashicons dashicons-menu" title="Arrastar para reordenar"></span>';
				html += '<select class="pt-ss-tipo">';
				$.each(tipos, function(val, label) {
					html += '<option value="' + val + '"' + (val === tipo ? ' selected' : '') + '>' + esc(label) + '</option>';
				});
				html += '</select>';
				html += '<input type="text" class="pt-ss-titulo" value="' + escA(sub.titulo || '') + '" placeholder="Título completo (ex: Palestra 1: A jornada digital europeia)" />';
				html += '<button type="button" class="pt-sub-sessao-remove" title="Remover sub-sessão">&times;</button>';
				html += '</div>';

				html += '<div class="pt-sub-sessao-body">';
				html += '<div class="pt-sub-search-wrapper">';
				html += '<input type="text" class="pt-sub-search regular-text" placeholder="Buscar participante por nome..." autocomplete="off" />';
				html += '<div class="pt-sub-search-results"></div>';
				html += '</div>';

				var hasParts = sub.participantes && sub.participantes.length;
				html += '<table class="pt-sub-parts">';
				html += '<thead><tr><th class="col-handle"></th><th class="col-nome">Participante</th><th class="col-cargo">Cargo nesta sub-sessão</th><th class="col-actions"></th></tr></thead>';
				html += '<tbody class="pt-sub-parts-tbody">';
				if (hasParts) {
					$.each(sub.participantes, function(pi, p) {
						html += renderPartRow(p);
					});
				}
				html += '</tbody></table>';
				if (!hasParts) {
					html += '<div class="pt-sub-parts-empty">Nenhum participante. Use a busca acima para adicionar.</div>';
				}
				html += '</div></div>';
				return html;
			}

			function renderPartRow(p) {
				var html = '<tr data-pid="' + (p.participante_id || '') + '">';
				html += '<td class="col-handle dashicons dashicons-menu"></td>';
				html += '<td class="col-nome">' + esc(p.nome || '(sem nome)') + '</td>';
				html += '<td class="col-cargo"><input type="text" class="pt-sub-part-cargo" value="' + escA(p.cargo || '') + '" placeholder="Cargo" /></td>';
				html += '<td class="col-actions"><button type="button" class="button-link pt-sub-remove-part" title="Remover">&times;</button></td>';
				html += '</tr>';
				return html;
			}

			function initSortableSubSessoes() {
				var listEl = document.getElementById('pt-sub-sessoes-list');
				if (!listEl || typeof Sortable === 'undefined') return;
				if (Sortable.get(listEl)) Sortable.get(listEl).destroy();
				new Sortable(listEl, {
					handle: '.pt-sub-sessao-handle',
					draggable: '.pt-sub-sessao',
					animation: 150,
					onEnd: function() { collectFromDom(); render(); syncHidden(); }
				});
			}

			function initSortableParts(tbody) {
				if (typeof Sortable === 'undefined') return;
				if (Sortable.get(tbody)) Sortable.get(tbody).destroy();
				new Sortable(tbody, {
					handle: '.col-handle',
					draggable: 'tr',
					animation: 120,
					onEnd: function() { collectFromDom(); render(); syncHidden(); }
				});
			}

			// Re-coleta o estado a partir do DOM (após sortable ou edits de input)
			function collectFromDom() {
				var next = [];
				$('#pt-sub-sessoes-list .pt-sub-sessao').each(function() {
					var $ss = $(this);
					var participantes = [];
					$ss.find('.pt-sub-parts-tbody tr').each(function() {
						var $tr = $(this);
						var pid = parseInt($tr.data('pid'), 10) || 0;
						if (!pid) return;
						participantes.push({
							participante_id: pid,
							nome: $tr.find('.col-nome').text().trim(),
							cargo: $tr.find('.pt-sub-part-cargo').val() || ''
						});
					});
					next.push({
						tipo: $ss.find('.pt-ss-tipo').val() || 'palestra',
						titulo: $ss.find('.pt-ss-titulo').val() || '',
						participantes: participantes
					});
				});
				state = next;
			}

			// ---- Event handlers ----

			$('#pt-add-sub-sessao').on('click', function() {
				collectFromDom();
				state.push({ tipo: 'palestra', titulo: '', participantes: [] });
				syncHidden();
				render();
			});

			$('#pt-sub-sessoes-list').on('click', '.pt-sub-sessao-remove', function() {
				if (!confirm('Remover esta sub-sessão?')) return;
				var $ss = $(this).closest('.pt-sub-sessao');
				$ss.remove();
				collectFromDom();
				syncHidden();
				render();
			});

			$('#pt-sub-sessoes-list').on('click', '.pt-sub-remove-part', function() {
				$(this).closest('tr').remove();
				collectFromDom();
				syncHidden();
				// Re-render só este sub-sessão para mostrar "vazio" se necessário
				render();
			});

			// Tipo / título / cargo: atualiza header class + sync
			$('#pt-sub-sessoes-list').on('change', '.pt-ss-tipo', function() {
				var $header = $(this).closest('.pt-sub-sessao-header');
				$header.toggleClass('tipo-debate', $(this).val() === 'debate');
				collectFromDom();
				syncHidden();
			});

			$('#pt-sub-sessoes-list').on('input change', '.pt-ss-titulo, .pt-sub-part-cargo', function() {
				collectFromDom();
				syncHidden();
			});

			// ---- Busca de participantes (AJAX) ----

			var searchTimer = null;
			$('#pt-sub-sessoes-list').on('input', '.pt-sub-search', function() {
				var $input = $(this);
				var $results = $input.siblings('.pt-sub-search-results');
				var term = $input.val().trim();
				clearTimeout(searchTimer);
				if (term.length < 2) {
					$results.hide().empty();
					return;
				}
				searchTimer = setTimeout(function() {
					$.get(ajaxUrl, {
						action: 'pt_event_search_participantes',
						nonce: nonce,
						term: term
					}).done(function(res) {
						$results.empty();
						if (res && res.success && res.data && res.data.length) {
							$.each(res.data, function(_, p) {
								var $item = $('<div class="pt-sub-search-item" />')
									.data('pid', p.id)
									.data('nome', p.nome)
									.data('cargo', p.cargo || '')
									.html('<strong>' + esc(p.nome) + '</strong>' + (p.cargo ? '<small>' + esc(p.cargo) + '</small>' : ''));
								$results.append($item);
							});
							$results.show();
						} else {
							$results.html('<div class="pt-sub-search-item" style="color:#999;cursor:default;">Nenhum participante encontrado.</div>').show();
						}
					});
				}, 250);
			});

			$('#pt-sub-sessoes-list').on('click', '.pt-sub-search-item', function() {
				var pid = parseInt($(this).data('pid'), 10);
				if (!pid) return;
				var nome = $(this).data('nome') || '';
				var cargo = $(this).data('cargo') || '';
				var $ss = $(this).closest('.pt-sub-sessao');
				var idx = parseInt($ss.data('idx'), 10);

				collectFromDom();
				// Evita duplicado dentro da mesma sub-sessão
				var already = state[idx] && state[idx].participantes &&
					state[idx].participantes.some(function(p) { return parseInt(p.participante_id, 10) === pid; });
				if (already) {
					alert('Este participante já está nesta sub-sessão.');
					return;
				}
				if (!state[idx]) return;
				state[idx].participantes.push({ participante_id: pid, nome: nome, cargo: cargo });
				syncHidden();

				$ss.find('.pt-sub-search').val('').siblings('.pt-sub-search-results').hide().empty();
				render();
			});

			// Fecha resultados ao clicar fora
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.pt-sub-search-wrapper').length) {
					$('.pt-sub-search-results').hide();
				}
			});

			// Init
			$(document).ready(function() {
				render();
				syncHidden();
			});

		})(jQuery);
		</script>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['pt_event_sub_sessoes_nonce'] ) ||
			! wp_verify_nonce( $_POST['pt_event_sub_sessoes_nonce'], 'pt_event_sub_sessoes' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw  = isset( $_POST['pt_event_sub_sessoes_json'] ) ? wp_unslash( $_POST['pt_event_sub_sessoes_json'] ) : '[]';
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		// Sanitizar e validar
		$clean = array();
		foreach ( $data as $sub ) {
			$tipo   = isset( $sub['tipo'] ) ? sanitize_key( $sub['tipo'] ) : 'palestra';
			$tipo   = in_array( $tipo, array( 'palestra', 'debate' ), true ) ? $tipo : 'palestra';
			$titulo = isset( $sub['titulo'] ) ? sanitize_text_field( $sub['titulo'] ) : '';
			$parts  = array();
			if ( ! empty( $sub['participantes'] ) && is_array( $sub['participantes'] ) ) {
				foreach ( $sub['participantes'] as $p ) {
					$pid = isset( $p['participante_id'] ) ? absint( $p['participante_id'] ) : 0;
					if ( ! $pid ) {
						continue;
					}
					$parts[] = array(
						'participante_id' => $pid,
						'cargo'           => isset( $p['cargo'] ) ? sanitize_text_field( $p['cargo'] ) : '',
					);
				}
			}
			// Pular sub-sessões vazias (sem título e sem participantes)
			if ( '' === $titulo && empty( $parts ) ) {
				continue;
			}
			$clean[] = array(
				'tipo'          => $tipo,
				'titulo'        => $titulo,
				'participantes' => $parts,
			);
		}

		update_post_meta( $post_id, self::META_KEY, wp_json_encode( $clean ) );

		// Sincronizar junction: adicionar participantes das sub-sessões com papel inferido.
		// Relationship::add usa REPLACE (UNIQUE sessao+participante), então é idempotente.
		// O meta box "Participantes da Sessão" (priority 20) já rodou e limpou + adicionou
		// os principais (Mensagem inicial, Moderador, etc.). Aqui só anexamos os de sub-sessões.
		$ordem_offset = 100; // sub-sessões ficam após os principais (que usam 0..N)
		$ordem        = $ordem_offset;
		foreach ( $clean as $sub ) {
			$papel = ( 'debate' === $sub['tipo'] ) ? 'debatedor' : 'palestrante';
			foreach ( $sub['participantes'] as $p ) {
				Relationship::add( $post_id, $p['participante_id'], $papel, $ordem, $p['cargo'] );
				$ordem++;
			}
		}
	}
}
