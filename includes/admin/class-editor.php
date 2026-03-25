<?php
namespace PTEvent\Admin;

use PTEvent\Database\Relationship;
use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_pt_event_salvar_programacao', array( $this, 'ajax_salvar' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'pt-event-settings',
			__( 'Editar Programação', 'pt-event' ),
			__( 'Editar Programação', 'pt-event' ),
			'manage_options',
			'pt-event-editor',
			array( $this, 'render_page' )
		);
	}

	// =========================================================================
	// Load sessions from DB
	// =========================================================================

	private function load_sessoes_from_db() {
		global $wpdb;
		$table_rel = $wpdb->prefix . 'evento_sessao_participantes';

		$sessoes_posts = get_posts( array(
			'post_type'      => 'pt_sessao',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_key'       => '_pt_event_ordem',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		) );

		$sessoes = array();
		foreach ( $sessoes_posts as $post ) {
			$sid = $post->ID;
			$dia         = get_post_meta( $sid, '_pt_event_dia', true );
			$hora_inicio = get_post_meta( $sid, '_pt_event_hora_inicio', true );
			$hora_fim    = get_post_meta( $sid, '_pt_event_hora_fim', true );
			$titulo      = get_post_meta( $sid, '_pt_event_titulo', true );
			$subtitulo   = get_post_meta( $sid, '_pt_event_subtitulo', true );
			$descricao   = get_post_meta( $sid, '_pt_event_descricao', true );
			$trilha      = get_post_meta( $sid, '_pt_event_trilha', true );
			$ordem       = get_post_meta( $sid, '_pt_event_ordem', true );

			// Get participants (all, not just confirmed)
			$rels = $wpdb->get_results( $wpdb->prepare(
				"SELECT sp.participante_id, sp.papel, sp.ordem
				FROM {$table_rel} AS sp
				INNER JOIN {$wpdb->posts} AS p ON p.ID = sp.participante_id
				WHERE sp.sessao_id = %d
				ORDER BY sp.ordem ASC",
				$sid
			) );

			$participantes = array();
			foreach ( $rels as $rel ) {
				$pid     = $rel->participante_id;
				$foto_id = get_post_meta( $pid, '_pt_event_foto', true );
				$participantes[] = array(
					'db_id'      => $pid,
					'nome'       => get_post_meta( $pid, '_pt_event_nome', true ),
					'cargo'      => get_post_meta( $pid, '_pt_event_cargo', true ),
					'papel'      => $rel->papel,
					'confirmado' => get_post_meta( $pid, '_pt_event_confirmado', true ) ?: 'nao',
					'foto_id'    => $foto_id ? absint( $foto_id ) : 0,
					'foto_url'   => $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '',
					'home'       => get_post_meta( $pid, '_pt_event_home', true ) ?: 'nao',
				);
			}

			$sessoes[] = array(
				'db_id'         => $sid,
				'dia'           => $dia,
				'dia_label'     => Helpers::format_dia_label( $dia ),
				'hora_inicio'   => $hora_inicio,
				'hora_fim'      => $hora_fim,
				'titulo'        => $titulo,
				'subtitulo'     => $subtitulo ?: '',
				'descricao'     => $descricao ?: '',
				'trilha'        => $trilha ?: '',
				'ordem'         => $ordem ?: 0,
				'participantes' => $participantes,
			);
		}

		return $sessoes;
	}

	// =========================================================================
	// Render Page
	// =========================================================================

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_media();

		$sessoes_json = wp_json_encode( $this->load_sessoes_from_db() );
		$nonce = wp_create_nonce( 'pt_event_editor' );
		?>
		<div class="wrap pt-event-editor">
			<h1><?php esc_html_e( 'Editar Programação', 'pt-event' ); ?></h1>
			<p class="description">Edite sessões e participantes visualmente. Alterações só são salvas ao clicar em "Salvar Tudo".</p>

			<input type="hidden" id="pt-editor-deleted-sessoes" value="" />
			<input type="hidden" id="pt-editor-deleted-parts" value="" />

			<div id="pt-editor-preview"></div>

			<button type="button" id="pt-editor-add-sessao" class="pt-btn-add-sessao">+ Adicionar Sessão</button>

			<p style="margin-top: 20px;">
				<button type="button" id="pt-editor-salvar" class="button button-primary button-hero">Salvar Tudo</button>
				<span id="pt-editor-status" style="margin-left: 12px; font-weight: 600;"></span>
			</p>
		</div>

		<style>
			.pt-event-editor { max-width: 1100px; }
			.pt-sessao-block { border: 1px solid #c3c4c7; margin-bottom: 16px; background: #fafafa; }
			.pt-sessao-header { background: #2271b1; color: #fff; padding: 10px 14px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
			.pt-sessao-header .pt-sessao-badge { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px; font-size: 12px; }
			.pt-sessao-header .pt-sessao-id-badge { background: rgba(255,255,255,0.15); padding: 2px 6px; border-radius: 3px; font-size: 11px; opacity: 0.7; }
			.pt-sessao-fields { padding: 12px 14px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
			.pt-sessao-fields label { display: block; font-size: 12px; font-weight: 600; color: #50575e; margin-bottom: 3px; }
			.pt-sessao-fields input[type="text"],
			.pt-sessao-fields input[type="time"],
			.pt-sessao-fields input[type="date"] { padding: 4px 8px; }
			.pt-sessao-fields .pt-field-titulo { flex: 1; min-width: 250px; }
			.pt-sessao-fields .pt-field-titulo input { width: 100%; }
			.pt-sessao-fields .pt-field-subtitulo { flex: 0.7; min-width: 180px; }
			.pt-sessao-fields .pt-field-subtitulo input { width: 100%; }
			.pt-sessao-desc { padding: 0 14px 10px; }
			.pt-sessao-desc textarea { width: 100%; min-height: 40px; resize: vertical; }
			.pt-participantes-section { padding: 0 14px 14px; }
			.pt-participantes-section h4 { margin: 0 0 8px; font-size: 13px; color: #2271b1; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
			.pt-participante-row { display: flex; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px dotted #ddd; flex-wrap: wrap; }
			.pt-participante-row:last-child { border-bottom: none; }
			.pt-part-foto { width: 50px; height: 50px; border: 2px dashed #c3c4c7; border-radius: 50%; overflow: hidden; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #f0f0f1; flex-shrink: 0; position: relative; }
			.pt-part-foto img { width: 100%; height: 100%; object-fit: cover; }
			.pt-part-foto .pt-foto-placeholder { font-size: 18px; color: #999; }
			.pt-part-foto:hover { border-color: #2271b1; background: #e8f0fe; }
			.pt-part-nome { flex: 1; min-width: 180px; }
			.pt-part-nome input { width: 100%; }
			.pt-part-cargo { flex: 1.5; min-width: 200px; }
			.pt-part-cargo input { width: 100%; }
			.pt-part-papel { width: 120px; }
			.pt-part-papel select { width: 100%; }
			.pt-part-status { width: 110px; }
			.pt-part-status select { width: 100%; }
			.pt-status-confirmado { color: #00a32a; font-weight: 600; }
			.pt-status-cancelado { color: #d63638; font-weight: 600; }
			.pt-dia-divider { background: #1d2327; color: #fff; padding: 10px 14px; font-size: 15px; font-weight: 700; margin-bottom: 16px; margin-top: 24px; }
			.pt-dia-divider:first-child { margin-top: 0; }
			.pt-btn-remove-sessao { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 2px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; margin-left: 8px; }
			.pt-btn-remove-sessao:hover { background: #d63638; border-color: #d63638; }
			.pt-btn-remove-part { background: #f0f0f1; border: 1px solid #c3c4c7; color: #d63638; padding: 2px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; flex-shrink: 0; }
			.pt-btn-remove-part:hover { background: #d63638; color: #fff; border-color: #d63638; }
			.pt-btn-add-part { background: #f0f6fc; border: 1px dashed #2271b1; color: #2271b1; padding: 6px 14px; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 600; margin-top: 8px; display: inline-block; }
			.pt-btn-add-part:hover { background: #2271b1; color: #fff; }
			.pt-btn-add-sessao { background: #f0f6fc; border: 2px dashed #2271b1; color: #2271b1; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; display: block; width: 100%; margin-top: 16px; text-align: center; }
			.pt-btn-add-sessao:hover { background: #2271b1; color: #fff; }
			.pt-btn-move { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 0 6px; border-radius: 3px; cursor: pointer; font-size: 14px; line-height: 22px; }
			.pt-btn-move:hover { background: rgba(255,255,255,0.35); }
			.pt-btn-move-part { background: #f0f0f1; border: 1px solid #c3c4c7; color: #50575e; padding: 0 5px; border-radius: 3px; cursor: pointer; font-size: 12px; line-height: 20px; flex-shrink: 0; }
			.pt-btn-move-part:hover { background: #2271b1; color: #fff; border-color: #2271b1; }
			.pt-move-group { display: flex; gap: 2px; margin-left: 4px; }
			.pt-move-group-part { display: flex; flex-direction: column; gap: 1px; flex-shrink: 0; }
			.pt-editor-saved { color: #00a32a; }
			.pt-editor-saving { color: #2271b1; }
			.pt-editor-error { color: #d63638; }
			.pt-part-id-badge { font-size: 10px; color: #999; margin-left: 4px; }
		</style>

		<script>
		(function($) {
			var sessoes = <?php echo $sessoes_json; ?>;
			var nonce = '<?php echo $nonce; ?>';
			var deletedSessoes = [];
			var deletedParts = [];

			renderEditor(sessoes);

			function renderEditor(sessoes) {
				var html = '';
				var currentDia = '';

				$.each(sessoes, function(si, s) {
					if (s.dia !== currentDia) {
						currentDia = s.dia;
						html += '<div class="pt-dia-divider">' + esc(s.dia_label || s.dia) + '</div>';
					}
					html += renderSessaoBlock(si, s);
				});

				$('#pt-editor-preview').html(html);
			}

			function renderSessaoBlock(si, s) {
				var html = '';
				html += '<div class="pt-sessao-block" data-idx="' + si + '" data-db-id="' + (s.db_id || '') + '">';
				html += '<div class="pt-sessao-header">';
				html += '<span>' + esc(s.hora_inicio || '') + ' - ' + esc(s.hora_fim || '') + ' | ' + esc(s.titulo || 'Nova sessão');
				if (s.db_id) {
					html += ' <span class="pt-sessao-id-badge">ID ' + s.db_id + '</span>';
				} else {
					html += ' <span class="pt-sessao-badge" style="background:#d1fae5;color:#065f46;">Novo</span>';
				}
				html += '</span>';
				var partCount = (s.participantes && s.participantes.length) ? s.participantes.length : 0;
				html += '<span class="pt-sessao-badge">' + partCount + ' participante(s)</span>';
				html += '<div class="pt-move-group">';
				html += '<button type="button" class="pt-btn-move pt-btn-move-sessao-up" title="Mover para cima">&#9650;</button>';
				html += '<button type="button" class="pt-btn-move pt-btn-move-sessao-down" title="Mover para baixo">&#9660;</button>';
				html += '</div>';
				html += '<button type="button" class="pt-btn-remove-sessao" title="Remover sessão">&times;</button>';
				html += '</div>';

				// Campos editáveis
				html += '<div class="pt-sessao-fields">';
				html += '<div><label>Dia</label><input type="date" class="pt-s-dia" value="' + escA(s.dia) + '" /></div>';
				html += '<div><label>Início</label><input type="time" class="pt-s-inicio" value="' + escA(s.hora_inicio) + '" /></div>';
				html += '<div><label>Fim</label><input type="time" class="pt-s-fim" value="' + escA(s.hora_fim) + '" /></div>';
				html += '<div class="pt-field-titulo"><label>Título</label><input type="text" class="pt-s-titulo" value="' + escA(s.titulo) + '" /></div>';
				html += '<div class="pt-field-subtitulo"><label>Subtítulo</label><input type="text" class="pt-s-subtitulo" value="' + escA(s.subtitulo) + '" placeholder="Ex: Mesa redonda" /></div>';
				html += '<div><label>Ordem</label><input type="number" class="pt-s-ordem" value="' + (s.ordem || si) + '" style="width:55px" /></div>';
				html += '</div>';

				html += '<div class="pt-sessao-desc"><label style="font-size:12px;font-weight:600;color:#50575e;display:block;margin-bottom:3px;">Descrição</label><textarea class="pt-s-desc">' + esc(s.descricao || '') + '</textarea></div>';

				// Participantes
				html += '<div class="pt-participantes-section">';
				html += '<h4>Participantes</h4>';
				if (s.participantes && s.participantes.length) {
					$.each(s.participantes, function(pi, p) {
						html += renderParticipanteRow(si, pi, p);
					});
				}
				html += '<button type="button" class="pt-btn-add-part">+ Adicionar Participante</button>';
				html += '</div>';

				html += '</div>';
				return html;
			}

			function renderParticipanteRow(si, pi, p) {
				var statusClass = p.confirmado === 'sim' ? 'pt-status-confirmado' : (p.confirmado === 'cancelado' ? 'pt-status-cancelado' : '');
				var h = '<div class="pt-participante-row" data-db-id="' + (p.db_id || '') + '">';

				// Foto
				h += '<div class="pt-part-foto" title="Clique para adicionar foto">';
				h += '<input type="hidden" class="pt-part-foto-id" value="' + (p.foto_id || '') + '" />';
				if (p.foto_url) {
					h += '<img src="' + escA(p.foto_url) + '" alt="" />';
				} else {
					h += '<span class="pt-foto-placeholder">+</span>';
				}
				h += '</div>';

				h += '<div class="pt-part-nome"><input type="text" class="pt-p-nome" value="' + escA(p.nome) + '" placeholder="Nome" />';
				if (p.db_id) {
					h += '<span class="pt-part-id-badge">ID ' + p.db_id + '</span>';
				}
				h += '</div>';

				h += '<div class="pt-part-cargo"><input type="text" class="pt-p-cargo" value="' + escA(p.cargo) + '" placeholder="Cargo / Empresa" /></div>';
				h += '<div class="pt-part-papel"><select class="pt-p-papel">';
				var papeis = ['', 'palestrante', 'moderador', 'debatedor', 'keynote', 'abertura'];
				$.each(papeis, function(_, v) {
					var label = v ? v.charAt(0).toUpperCase() + v.slice(1) : '-- Papel --';
					h += '<option value="' + v + '"' + (v === p.papel ? ' selected' : '') + '>' + label + '</option>';
				});
				h += '</select></div>';
				h += '<div class="pt-part-status"><select class="pt-p-confirmado ' + statusClass + '">';
				h += '<option value="sim"' + (p.confirmado === 'sim' ? ' selected' : '') + '>Confirmado</option>';
				h += '<option value="nao"' + (p.confirmado === 'nao' || !p.confirmado ? ' selected' : '') + '>Não confirmado</option>';
				h += '<option value="cancelado"' + (p.confirmado === 'cancelado' ? ' selected' : '') + '>Cancelado</option>';
				h += '</select></div>';

				h += '<div class="pt-move-group-part">';
				h += '<button type="button" class="pt-btn-move-part pt-btn-move-part-up" title="Mover para cima">&#9650;</button>';
				h += '<button type="button" class="pt-btn-move-part pt-btn-move-part-down" title="Mover para baixo">&#9660;</button>';
				h += '</div>';
				h += '<button type="button" class="pt-btn-remove-part" title="Remover participante">&times;</button>';
				h += '</div>';

				return h;
			}

			// Foto upload
			$('#pt-editor-preview').on('click', '.pt-part-foto', function() {
				var $fotoDiv = $(this);
				var frame = wp.media({
					title: 'Selecionar foto do participante',
					button: { text: 'Usar esta foto' },
					multiple: false,
					library: { type: 'image' }
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
					$fotoDiv.find('.pt-foto-placeholder').remove();
					if ($fotoDiv.find('img').length) {
						$fotoDiv.find('img').attr('src', url);
					} else {
						$fotoDiv.prepend('<img src="' + url + '" alt="" />');
					}
					$fotoDiv.find('.pt-part-foto-id').val(attachment.id);
				});
				frame.open();
			});

			// Remover sessão
			$('#pt-editor-preview').on('click', '.pt-btn-remove-sessao', function() {
				if (!confirm('Remover esta sessão?')) return;
				var $block = $(this).closest('.pt-sessao-block');
				var dbId = $block.data('db-id');
				if (dbId) {
					deletedSessoes.push(dbId);
				}
				// Also collect participant db_ids
				$block.find('.pt-participante-row').each(function() {
					var pId = $(this).data('db-id');
					if (pId) deletedParts.push(pId);
				});
				$block.slideUp(200, function() { $(this).remove(); });
			});

			// Remover participante
			$('#pt-editor-preview').on('click', '.pt-btn-remove-part', function() {
				var $row = $(this).closest('.pt-participante-row');
				var dbId = $row.data('db-id');
				if (dbId) {
					deletedParts.push(dbId);
				}
				$row.slideUp(200, function() { $(this).remove(); });
			});

			// Mover sessao para cima
			$('#pt-editor-preview').on('click', '.pt-btn-move-sessao-up', function() {
				var $block = $(this).closest('.pt-sessao-block');
				var $prev = $block.prev('.pt-sessao-block');
				if ($prev.length) {
					$block.css('transition','transform 0.15s').css('transform','translateY(-8px)');
					setTimeout(function(){ $block.insertBefore($prev).css('transform',''); }, 150);
				}
			});

			// Mover sessao para baixo
			$('#pt-editor-preview').on('click', '.pt-btn-move-sessao-down', function() {
				var $block = $(this).closest('.pt-sessao-block');
				var $next = $block.next('.pt-sessao-block');
				if ($next.length) {
					$block.css('transition','transform 0.15s').css('transform','translateY(8px)');
					setTimeout(function(){ $block.insertAfter($next).css('transform',''); }, 150);
				}
			});

			// Mover participante para cima
			$('#pt-editor-preview').on('click', '.pt-btn-move-part-up', function() {
				var $row = $(this).closest('.pt-participante-row');
				var $prev = $row.prev('.pt-participante-row');
				if ($prev.length) $row.insertBefore($prev);
			});

			// Mover participante para baixo
			$('#pt-editor-preview').on('click', '.pt-btn-move-part-down', function() {
				var $row = $(this).closest('.pt-participante-row');
				var $next = $row.next('.pt-participante-row');
				if ($next.length) $row.insertAfter($next);
			});

			// Adicionar participante
			$('#pt-editor-preview').on('click', '.pt-btn-add-part', function() {
				var si = $(this).closest('.pt-sessao-block').data('idx') || 0;
				var pi = $(this).closest('.pt-participantes-section').find('.pt-participante-row').length;
				var newRow = renderParticipanteRow(si, pi, {
					db_id: '', nome: '', cargo: '', confirmado: 'nao', papel: '', foto_id: '', foto_url: ''
				});
				$(newRow).hide().insertBefore($(this)).slideDown(200);
			});

			// Adicionar sessão
			$('#pt-editor-add-sessao').on('click', function() {
				var idx = $('#pt-editor-preview .pt-sessao-block').length;
				var today = new Date().toISOString().slice(0, 10);
				var html = renderSessaoBlock(idx, {
					db_id: '', dia: today, dia_label: '', hora_inicio: '09:00', hora_fim: '10:00',
					titulo: '', subtitulo: '', descricao: '', trilha: '', ordem: idx, participantes: []
				});
				var $el = $(html).hide();
				$('#pt-editor-preview').append($el);
				$el.slideDown(200);
				$el.find('.pt-s-titulo').focus();
			});

			// Salvar tudo
			$('#pt-editor-salvar').on('click', function() {
				var $btn = $(this).prop('disabled', true).text('Salvando...');
				$('#pt-editor-status').text('Salvando...').attr('class', 'pt-editor-saving');

				var sessoes = [];
				$('#pt-editor-preview .pt-sessao-block').each(function() {
					var $block = $(this);
					var sessao = {
						db_id: $block.data('db-id') || '',
						dia: $block.find('.pt-s-dia').val(),
						hora_inicio: $block.find('.pt-s-inicio').val(),
						hora_fim: $block.find('.pt-s-fim').val(),
						titulo: $block.find('.pt-s-titulo').val(),
						subtitulo: $block.find('.pt-s-subtitulo').val(),
						descricao: $block.find('.pt-s-desc').val(),
						ordem: $block.find('.pt-s-ordem').val(),
						participantes: []
					};

					$block.find('.pt-participante-row').each(function() {
						var $row = $(this);
						var part = {
							db_id: $row.data('db-id') || '',
							nome: $row.find('.pt-p-nome').val(),
							cargo: $row.find('.pt-p-cargo').val(),
							papel: $row.find('.pt-p-papel').val(),
							confirmado: $row.find('.pt-p-confirmado').val(),
							foto_id: $row.find('.pt-part-foto-id').val()
						};
						if (part.nome) sessao.participantes.push(part);
					});

					if (sessao.titulo) sessoes.push(sessao);
				});

				$.post(ajaxurl, {
					action: 'pt_event_salvar_programacao',
					nonce: nonce,
					sessoes: JSON.stringify(sessoes),
					deleted_sessoes: JSON.stringify(deletedSessoes),
					deleted_parts: JSON.stringify(deletedParts)
				}, function(res) {
					$btn.prop('disabled', false).text('Salvar Tudo');
					if (res.success) {
						$('#pt-editor-status').text('✓ ' + res.data.message).attr('class', 'pt-editor-saved');
						// Reload data to get updated IDs
						deletedSessoes = [];
						deletedParts = [];
						if (res.data.sessoes) {
							renderEditor(res.data.sessoes);
						}
						setTimeout(function() { $('#pt-editor-status').text(''); }, 5000);
					} else {
						$('#pt-editor-status').text('Erro: ' + (res.data || 'desconhecido')).attr('class', 'pt-editor-error');
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('Salvar Tudo');
					$('#pt-editor-status').text('Erro na requisição.').attr('class', 'pt-editor-error');
				});
			});

			function esc(s) { return $('<div>').text(s || '').html(); }
			function escA(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

		})(jQuery);
		</script>
		<?php
	}

	// =========================================================================
	// AJAX: Save
	// =========================================================================

	public function ajax_salvar() {
		check_ajax_referer( 'pt_event_editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão.' );
		}

		$sessoes_raw     = isset( $_POST['sessoes'] ) ? json_decode( wp_unslash( $_POST['sessoes'] ), true ) : array();
		$deleted_sessoes = isset( $_POST['deleted_sessoes'] ) ? json_decode( wp_unslash( $_POST['deleted_sessoes'] ), true ) : array();
		$deleted_parts   = isset( $_POST['deleted_parts'] ) ? json_decode( wp_unslash( $_POST['deleted_parts'] ), true ) : array();

		// 1. Delete removed sessions
		if ( ! empty( $deleted_sessoes ) ) {
			foreach ( $deleted_sessoes as $sid ) {
				$sid = absint( $sid );
				if ( $sid ) {
					Relationship::remove_all_by_sessao( $sid );
					wp_delete_post( $sid, true );
				}
			}
		}

		// 2. Delete removed participants (unlink only — keep the post for other sessions)
		// We'll handle unlinking per-session below. But if a participant was removed,
		// we should remove it from all sessions it was linked to via the deleted list.
		// Actually, the participant post itself should NOT be deleted (it may be in other sessions).
		// The relationship will be cleaned up per-session below.

		// 3. Process sessions
		$count_created  = 0;
		$count_updated  = 0;
		$count_parts_new = 0;
		$count_parts_upd = 0;

		foreach ( $sessoes_raw as $s ) {
			$titulo      = sanitize_text_field( $s['titulo'] );
			$dia         = sanitize_text_field( $s['dia'] );
			$hora_inicio = sanitize_text_field( $s['hora_inicio'] );
			$hora_fim    = sanitize_text_field( $s['hora_fim'] );
			$subtitulo   = sanitize_text_field( isset( $s['subtitulo'] ) ? $s['subtitulo'] : '' );
			$descricao   = sanitize_textarea_field( isset( $s['descricao'] ) ? $s['descricao'] : '' );
			$ordem       = isset( $s['ordem'] ) ? absint( $s['ordem'] ) : 0;
			$db_id       = ! empty( $s['db_id'] ) ? absint( $s['db_id'] ) : 0;

			if ( empty( $titulo ) ) {
				continue;
			}

			if ( $db_id ) {
				// Update existing session
				wp_update_post( array( 'ID' => $db_id, 'post_title' => $titulo ) );
				update_post_meta( $db_id, '_pt_event_titulo', $titulo );
				update_post_meta( $db_id, '_pt_event_subtitulo', $subtitulo );
				update_post_meta( $db_id, '_pt_event_dia', $dia );
				update_post_meta( $db_id, '_pt_event_hora_inicio', $hora_inicio );
				update_post_meta( $db_id, '_pt_event_hora_fim', $hora_fim );
				update_post_meta( $db_id, '_pt_event_descricao', $descricao );
				update_post_meta( $db_id, '_pt_event_ordem', $ordem );
				$sessao_id = $db_id;
				$count_updated++;
			} else {
				// Create new session
				$sessao_id = wp_insert_post( array(
					'post_type'   => 'pt_sessao',
					'post_title'  => $titulo,
					'post_status' => 'publish',
				) );
				if ( is_wp_error( $sessao_id ) ) {
					continue;
				}
				update_post_meta( $sessao_id, '_pt_event_titulo', $titulo );
				update_post_meta( $sessao_id, '_pt_event_subtitulo', $subtitulo );
				update_post_meta( $sessao_id, '_pt_event_dia', $dia );
				update_post_meta( $sessao_id, '_pt_event_hora_inicio', $hora_inicio );
				update_post_meta( $sessao_id, '_pt_event_hora_fim', $hora_fim );
				update_post_meta( $sessao_id, '_pt_event_descricao', $descricao );
				update_post_meta( $sessao_id, '_pt_event_ordem', $ordem );
				$count_created++;
			}

			// Rebuild relationships for this session
			Relationship::remove_all_by_sessao( $sessao_id );

			if ( ! empty( $s['participantes'] ) ) {
				$p_ordem = 0;
				foreach ( $s['participantes'] as $p ) {
					$nome       = sanitize_text_field( $p['nome'] );
					$cargo      = sanitize_text_field( $p['cargo'] );
					$papel      = sanitize_text_field( $p['papel'] );
					$confirmado = sanitize_text_field( $p['confirmado'] );
					$foto_id    = ! empty( $p['foto_id'] ) ? absint( $p['foto_id'] ) : 0;
					$p_db_id    = ! empty( $p['db_id'] ) ? absint( $p['db_id'] ) : 0;

					if ( empty( $nome ) ) {
						continue;
					}

					if ( $p_db_id ) {
						// Update existing participant
						wp_update_post( array( 'ID' => $p_db_id, 'post_title' => $nome ) );
						update_post_meta( $p_db_id, '_pt_event_nome', $nome );
						update_post_meta( $p_db_id, '_pt_event_cargo', $cargo );
						update_post_meta( $p_db_id, '_pt_event_confirmado', $confirmado );
						if ( $foto_id ) {
							update_post_meta( $p_db_id, '_pt_event_foto', $foto_id );
							set_post_thumbnail( $p_db_id, $foto_id );
						}
						$part_id = $p_db_id;
						$count_parts_upd++;
					} else {
						// Try to find existing by name
						$existing = $this->find_existing_participante( $nome );
						if ( $existing ) {
							// Link existing, update data
							update_post_meta( $existing, '_pt_event_cargo', $cargo );
							update_post_meta( $existing, '_pt_event_confirmado', $confirmado );
							if ( $foto_id ) {
								update_post_meta( $existing, '_pt_event_foto', $foto_id );
								set_post_thumbnail( $existing, $foto_id );
							}
							$part_id = $existing;
							$count_parts_upd++;
						} else {
							// Create new participant
							$part_id = wp_insert_post( array(
								'post_type'   => 'pt_participante',
								'post_title'  => $nome,
								'post_status' => 'publish',
							) );
							if ( is_wp_error( $part_id ) ) {
								continue;
							}
							update_post_meta( $part_id, '_pt_event_nome', $nome );
							update_post_meta( $part_id, '_pt_event_cargo', $cargo );
							update_post_meta( $part_id, '_pt_event_confirmado', $confirmado );
							if ( $foto_id ) {
								update_post_meta( $part_id, '_pt_event_foto', $foto_id );
								set_post_thumbnail( $part_id, $foto_id );
							}
							$count_parts_new++;
						}
					}

					Relationship::add( $sessao_id, $part_id, $papel, $p_ordem );
					$p_ordem++;
				}
			}
		}

		// Build message
		$parts = array();
		if ( $count_created > 0 )    $parts[] = $count_created . ' sessões criadas';
		if ( $count_updated > 0 )    $parts[] = $count_updated . ' sessões atualizadas';
		if ( $count_parts_new > 0 )  $parts[] = $count_parts_new . ' participantes criados';
		if ( $count_parts_upd > 0 )  $parts[] = $count_parts_upd . ' participantes atualizados';
		if ( ! empty( $deleted_sessoes ) ) $parts[] = count( $deleted_sessoes ) . ' sessões removidas';

		$msg = ! empty( $parts ) ? implode( ', ', $parts ) . '.' : 'Nenhuma alteração.';

		// Reload fresh data
		$fresh_sessoes = $this->load_sessoes_from_db();

		wp_send_json_success( array(
			'message' => $msg,
			'sessoes' => $fresh_sessoes,
		) );
	}

	private function find_existing_participante( $nome ) {
		$posts = get_posts( array(
			'post_type'      => 'pt_participante',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array( 'key' => '_pt_event_nome', 'value' => $nome ),
			),
			'fields' => 'ids',
		) );
		return ! empty( $posts ) ? $posts[0] : 0;
	}
}
