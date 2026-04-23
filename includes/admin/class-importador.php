<?php
namespace PTEvent\Admin;

use PTEvent\Database\Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Importador {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_pt_event_parse_programacao', array( $this, 'ajax_parse' ) );
		add_action( 'wp_ajax_pt_event_parse_ia', array( $this, 'ajax_parse_ia' ) );
		add_action( 'wp_ajax_pt_event_importar_programacao', array( $this, 'ajax_importar' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'pt-event-settings',
			__( 'Importar Programacao', 'pt-event' ),
			__( 'Importar Programacao', 'pt-event' ),
			'manage_options',
			'pt-event-importar',
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_media();
		$nonce = wp_create_nonce( 'pt_event_importar' );
		?>
		<div class="wrap pt-event-importador">
			<h1><?php esc_html_e( 'Importar Programacao', 'pt-event' ); ?></h1>

			<!-- STEP 1: Colar texto -->
			<div id="pt-import-step-1" class="pt-import-step">
				<h2>1. Cole o texto da programacao</h2>
				<p class="description">Cole o texto com a programacao do evento. O sistema detecta dias, horarios, titulos, descricoes e participantes automaticamente.</p>

				<textarea id="pt-import-texto" rows="20" class="large-text code" placeholder="Dia 19/05 - Segunda&#10;&#10;09:00 - 10:00 | Abertura&#10;&#10;Nome do Palestrante, Cargo - Confirmado"></textarea>

				<p style="margin-top: 12px;">
					<label><input type="checkbox" id="pt-import-limpar-sessoes" value="1" /> Limpar todas as sessoes existentes antes de importar</label>
				</p>
				<p>
					<label><input type="checkbox" id="pt-import-limpar-participantes" value="1" /> Limpar todos os participantes existentes antes de importar</label>
				</p>

				<p style="margin-top: 16px; display: flex; gap: 12px; align-items: center;">
					<?php
					$groq = new \PTEvent\Helpers\Groq_Client();
					if ( $groq->is_configured() ) :
					?>
					<button type="button" id="pt-import-processar-ia" class="button button-primary button-hero" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-shadow: none;">
						&#129302; Processar com IA
					</button>
					<span id="pt-ia-status" style="font-size: 13px; color: #666;"></span>
					<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pt-event-settings&tab=ia' ) ); ?>" class="button button-hero" style="opacity: 0.8;">
						&#129302; Configurar IA para importar
					</a>
					<?php endif; ?>
				</p>
			</div>

			<!-- STEP 2: Preview -->
			<div id="pt-import-step-2" class="pt-import-step" style="display:none;">
				<h2>2. Revise antes de importar</h2>
				<p class="description">Confira sessoes e participantes. Voce pode editar os dados e adicionar fotos antes de importar.</p>

				<div id="pt-import-preview"></div>

				<button type="button" id="pt-btn-add-sessao" class="pt-btn-add-sessao">+ Adicionar Sessao</button>

				<p style="margin-top: 20px;">
					<button type="button" id="pt-import-voltar" class="button">Voltar</button>
					<button type="button" id="pt-import-confirmar" class="button button-primary button-hero">Importar Tudo</button>
				</p>
			</div>

			<!-- STEP 3: Resultado -->
			<div id="pt-import-step-3" class="pt-import-step" style="display:none;">
				<div class="notice notice-success"><p id="pt-import-resultado"></p></div>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pt_sessao' ) ); ?>" class="button button-primary">Ver Sessoes</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pt_participante' ) ); ?>" class="button">Ver Participantes</a>
					<button type="button" id="pt-import-nova" class="button">Nova Importacao</button>
				</p>
			</div>
		</div>

		<style>
			.pt-event-importador { max-width: 1100px; }
			.pt-import-step { background: #fff; padding: 20px 24px; border: 1px solid #c3c4c7; margin-top: 16px; }
			.pt-sessao-block { border: 1px solid #c3c4c7; margin-bottom: 16px; background: #fafafa; }
			.pt-sessao-header { background: #2271b1; color: #fff; padding: 10px 14px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
			.pt-sessao-header .pt-sessao-badge { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px; font-size: 12px; }
			.pt-sessao-fields { padding: 12px 14px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
			.pt-sessao-fields label { display: block; font-size: 12px; font-weight: 600; color: #50575e; margin-bottom: 3px; }
			.pt-sessao-fields input[type="text"],
			.pt-sessao-fields input[type="time"],
			.pt-sessao-fields input[type="date"] { padding: 4px 8px; }
			.pt-sessao-fields .pt-field-titulo { flex: 1; min-width: 250px; }
			.pt-sessao-fields .pt-field-titulo input { width: 100%; }
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
			.pt-dup-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 6px; vertical-align: middle; }
			.pt-dup-novo { background: #d1fae5; color: #065f46; }
			.pt-dup-existe { background: #fef3c7; color: #92400e; }
			.pt-dup-repetido { background: #fee2e2; color: #991b1b; }
			.pt-dup-info { font-size: 11px; color: #6b7280; margin-top: 2px; font-style: italic; }
			.pt-sessao-header.pt-sessao-existe { background: #d97706; }
			.pt-part-action { width: 130px; }
			.pt-part-action select { width: 100%; font-size: 12px; }
			.pt-sessao-action { margin-left: auto; }
			.pt-sessao-action select { background: rgba(255,255,255,0.9); border: none; padding: 3px 6px; border-radius: 3px; font-size: 12px; font-weight: 600; }
			.pt-part-db-info { width: 100%; padding: 4px 0 0 58px; }
			.pt-part-db-info span { font-size: 11px; color: #92400e; background: #fef9e7; padding: 2px 8px; border-radius: 3px; display: inline-block; }
			.pt-btn-remove-sessao { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 2px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; margin-left: 8px; }
			.pt-btn-remove-sessao:hover { background: #d63638; border-color: #d63638; }
			.pt-btn-remove-part { background: #f0f0f1; border: 1px solid #c3c4c7; color: #d63638; padding: 2px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; flex-shrink: 0; }
			.pt-btn-remove-part:hover { background: #d63638; color: #fff; border-color: #d63638; }
			.pt-btn-add-part { background: #f0f6fc; border: 1px dashed #2271b1; color: #2271b1; padding: 6px 14px; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 600; margin-top: 8px; display: inline-block; }
			.pt-btn-add-part:hover { background: #2271b1; color: #fff; }
			.pt-btn-add-sessao { background: #f0f6fc; border: 2px dashed #2271b1; color: #2271b1; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; display: block; width: 100%; margin-top: 16px; text-align: center; }
			.pt-btn-add-sessao:hover { background: #2271b1; color: #fff; }
			.pt-drag-handle-sessao { cursor: grab; padding: 0 10px 0 2px; font-size: 18px; color: rgba(255,255,255,0.65); user-select: none; flex-shrink: 0; line-height: 1; letter-spacing: -1px; }
			.pt-drag-handle-sessao:active { cursor: grabbing; }
			.pt-drag-handle-part { cursor: grab; padding: 0 6px; font-size: 14px; color: #bbb; user-select: none; flex-shrink: 0; line-height: 1; letter-spacing: -1px; }
			.pt-drag-handle-part:active { cursor: grabbing; }
			.pt-sortable-ghost { opacity: 0.35 !important; background: #c8ebfb !important; }
			.pt-sortable-ghost.pt-participante-row { background: #e8f0fe !important; border: 2px dashed #2271b1 !important; }
			.pt-sortable-chosen.pt-sessao-block { box-shadow: 0 4px 24px rgba(0,0,0,0.2); }
		</style>

		<script>
		(function($) {
			var parsedData = [];
			var nonce = '<?php echo $nonce; ?>';

			// STEP 1a: Processar com IA
			$('#pt-import-processar-ia').on('click', function() {
				var texto = $('#pt-import-texto').val().trim();
				if (!texto) { alert('Cole o texto da programacao.'); return; }
				var $btn = $(this).prop('disabled', true);
				var originalText = $btn.html();
				$btn.html('&#129302; Analisando com IA...');
				$('#pt-ia-status').text('Enviando para Groq... aguarde.');

				$.post(ajaxurl, {
					action: 'pt_event_parse_ia',
					nonce: nonce,
					texto: texto
				}, function(res) {
					$btn.prop('disabled', false).html(originalText);
					$('#pt-ia-status').text('');
					if (res.success && res.data.length) {
						parsedData = res.data;
						renderPreview(res.data);
						$('#pt-import-step-1').slideUp(200);
						$('#pt-import-step-2').slideDown(200);
					} else {
						var errMsg = (res.data && res.data.message) ? res.data.message : 'Nenhuma sessao encontrada pela IA.';
						alert(errMsg);
					}
				}).fail(function(xhr) {
					$btn.prop('disabled', false).html(originalText);
					$('#pt-ia-status').text('');
					var errMsg = 'Erro ao processar com IA.';
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.data && resp.data.message) errMsg = resp.data.message;
					} catch(e) {}
					alert(errMsg);
				});
			});

			// STEP 1: Processar
			$('#pt-import-processar').on('click', function() {
				var texto = $('#pt-import-texto').val().trim();
				if (!texto) { alert('Cole o texto da programacao.'); return; }
				var $btn = $(this).prop('disabled', true).text('Processando...');

				$.post(ajaxurl, {
					action: 'pt_event_parse_programacao',
					nonce: nonce,
					texto: texto
				}, function(res) {
					$btn.prop('disabled', false).text('Processar');
					if (res.success && res.data.length) {
						parsedData = res.data;
						renderPreview(res.data);
						$('#pt-import-step-1').slideUp(200);
						$('#pt-import-step-2').slideDown(200);
					} else {
						alert('Nenhuma sessao encontrada. Verifique o formato.');
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('Processar');
					alert('Erro ao processar.');
				});
			});

			// Render preview
			function renderPreview(sessoes) {
				var html = '';
				var currentDia = '';

				$.each(sessoes, function(si, s) {
					if (s.dia !== currentDia) {
						currentDia = s.dia;
						html += '<div class="pt-dia-divider">' + esc(s.dia_label || s.dia) + '</div>';
					}

					html += '<div class="pt-sessao-block" data-idx="' + si + '">';
					var headerClass = 'pt-sessao-header' + (s.dup_status === 'existe_db' ? ' pt-sessao-existe' : '');
					html += '<div class="' + headerClass + '">';
					html += '<span class="pt-drag-handle-sessao" title="Arrastar para reordenar">&#10783;</span>';
					html += '<span>' + esc(s.hora_inicio) + ' - ' + esc(s.hora_fim) + ' | ' + esc(s.titulo);
					if (s.dup_status === 'existe_db') {
						html += ' <span class="pt-dup-badge pt-dup-existe">Ja existe no banco (ID ' + s.dup_existing_id + ')</span>';
					} else {
						html += ' <span class="pt-dup-badge pt-dup-novo">Novo</span>';
					}
					html += '</span>';
					var partCount = (s.participantes && s.participantes.length) ? s.participantes.length : 0;
					html += '<span class="pt-sessao-badge">' + partCount + ' participante(s)</span>';
					if (s.dup_status === 'existe_db') {
						html += '<div class="pt-sessao-action"><select class="pt-s-dup-action">';
						html += '<option value="pular" selected>Pular</option>';
						html += '<option value="sobrescrever">Sobrescrever</option>';
						html += '<option value="criar">Criar novo</option>';
						html += '</select></div>';
					} else {
						html += '<input type="hidden" class="pt-s-dup-action" value="criar" />';
					}
					html += '<button type="button" class="pt-btn-remove-sessao" title="Remover sessao">&times;</button>';
					html += '</div>';

					// Campos editaveis da sessao
					html += '<div class="pt-sessao-fields">';
					html += '<div><label>Dia</label><input type="date" class="pt-s-dia" value="' + escA(s.dia) + '" /></div>';
					html += '<div><label>Inicio</label><input type="time" class="pt-s-inicio" value="' + escA(s.hora_inicio) + '" /></div>';
					html += '<div><label>Fim</label><input type="time" class="pt-s-fim" value="' + escA(s.hora_fim) + '" /></div>';
					html += '<div class="pt-field-titulo"><label>Titulo</label><input type="text" class="pt-s-titulo" value="' + escA(s.titulo) + '" /></div>';
					html += '<div><label>Ordem</label><input type="number" class="pt-s-ordem" value="' + si + '" style="width:55px" /></div>';
					html += '</div>';

					if (s.descricao) {
						html += '<div class="pt-sessao-desc"><label style="font-size:12px;font-weight:600;color:#50575e;display:block;margin-bottom:3px;">Descricao</label><textarea class="pt-s-desc">' + esc(s.descricao) + '</textarea></div>';
					} else {
						html += '<div class="pt-sessao-desc"><label style="font-size:12px;font-weight:600;color:#50575e;display:block;margin-bottom:3px;">Descricao</label><textarea class="pt-s-desc"></textarea></div>';
					}

					// Participantes (sempre mostrar secao)
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
				});

				$('#pt-import-preview').html(html);
				initImportSortables();

				// Bind foto upload
				$('#pt-import-preview').on('click', '.pt-part-foto', function() {
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
			}

			function renderParticipanteRow(si, pi, p) {
				var statusClass = p.confirmado === 'sim' ? 'pt-status-confirmado' : (p.confirmado === 'cancelado' ? 'pt-status-cancelado' : '');
				var h = '<div class="pt-participante-row">';
				h += '<span class="pt-drag-handle-part" title="Arrastar para reordenar">&#10783;</span>';

				// Foto - se ja existe no banco, mostrar a foto existente
				h += '<div class="pt-part-foto" title="Clique para adicionar foto">';
				if (p.dup_status === 'existe_db' && p.dup_foto_url) {
					h += '<input type="hidden" class="pt-part-foto-id" value="' + (p.dup_foto_id || '') + '" />';
					h += '<img src="' + escA(p.dup_foto_url) + '" alt="" />';
				} else {
					h += '<input type="hidden" class="pt-part-foto-id" value="" />';
					h += '<span class="pt-foto-placeholder">+</span>';
				}
				h += '</div>';

				h += '<div class="pt-part-nome"><input type="text" class="pt-p-nome" value="' + escA(p.nome) + '" placeholder="Nome" />';
				// Badge de status
				if (p.dup_status === 'existe_db') {
					h += '<span class="pt-dup-badge pt-dup-existe">Ja cadastrado</span>';
				} else {
					h += '<span class="pt-dup-badge pt-dup-novo">Novo</span>';
				}
				if (p.dup_repetido_texto) {
					h += '<span class="pt-dup-badge pt-dup-repetido">Repetido no texto (' + p.dup_count_texto + 'x)</span>';
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
				h += '<option value="nao"' + (p.confirmado === 'nao' ? ' selected' : '') + '>Nao confirmado</option>';
				h += '<option value="cancelado"' + (p.confirmado === 'cancelado' ? ' selected' : '') + '>Cancelado</option>';
				h += '</select></div>';

				// Acao para duplicados
				if (p.dup_status === 'existe_db') {
					h += '<div class="pt-part-action"><select class="pt-p-dup-action">';
					h += '<option value="sobrescrever" selected>Atualizar</option>';
					h += '<option value="pular">Pular</option>';
					h += '<option value="criar">Criar novo</option>';
					h += '</select></div>';
				} else {
					h += '<input type="hidden" class="pt-p-dup-action" value="criar" />';
				}

				h += '<button type="button" class="pt-btn-remove-part" title="Remover participante">&times;</button>';
				h += '</div>';

				// Info do banco se existir
				if (p.dup_status === 'existe_db' && p.dup_cargo_db) {
					h += '<div class="pt-part-db-info"><span>No banco: ' + esc(p.dup_cargo_db) + '</span></div>';
				}

				return h;
			}

			// Voltar
			$('#pt-import-voltar').on('click', function() {
				$('#pt-import-step-2').slideUp(200);
				$('#pt-import-step-1').slideDown(200);
			});

			// Importar
			$('#pt-import-confirmar').on('click', function() {
				var $btn = $(this).prop('disabled', true).text('Importando...');
				var limparSessoes = $('#pt-import-limpar-sessoes').is(':checked') ? 1 : 0;
				var limparParts = $('#pt-import-limpar-participantes').is(':checked') ? 1 : 0;

				var sessoes = [];
				$('#pt-import-preview .pt-sessao-block').each(function() {
					var $block = $(this);
					var sessaoAction = $block.find('.pt-s-dup-action').val() || 'criar';

					var sessao = {
						dia: $block.find('.pt-s-dia').val(),
						hora_inicio: $block.find('.pt-s-inicio').val(),
						hora_fim: $block.find('.pt-s-fim').val(),
						titulo: $block.find('.pt-s-titulo').val(),
						descricao: $block.find('.pt-s-desc').val(),
						ordem: $block.find('.pt-s-ordem').val(),
						dup_action: sessaoAction,
						participantes: []
					};

					$block.find('.pt-participante-row').each(function() {
						var $row = $(this);
						var partAction = $row.closest('.pt-participante-row').find('.pt-p-dup-action').val()
							|| $row.siblings('.pt-part-db-info').prev().find('.pt-p-dup-action').val()
							|| 'criar';
						// Buscar na row e nos hidden
						partAction = $row.find('.pt-p-dup-action').val() || 'criar';

						var part = {
							nome: $row.find('.pt-p-nome').val(),
							cargo: $row.find('.pt-p-cargo').val(),
							papel: $row.find('.pt-p-papel').val(),
							confirmado: $row.find('.pt-p-confirmado').val(),
							foto_id: $row.find('.pt-part-foto-id').val(),
							dup_action: partAction
						};
						if (part.nome) sessao.participantes.push(part);
					});

					if (sessao.titulo) sessoes.push(sessao);
				});

				if (!sessoes.length) { alert('Nenhuma sessao para importar.'); $btn.prop('disabled', false).text('Importar Tudo'); return; }

				$.post(ajaxurl, {
					action: 'pt_event_importar_programacao',
					nonce: nonce,
					limpar_sessoes: limparSessoes,
					limpar_participantes: limparParts,
					sessoes: JSON.stringify(sessoes)
				}, function(res) {
					$btn.prop('disabled', false).text('Importar Tudo');
					if (res.success) {
						$('#pt-import-resultado').html(res.data.message);
						$('#pt-import-step-2').slideUp(200);
						$('#pt-import-step-3').slideDown(200);
					} else {
						alert('Erro: ' + (res.data || 'Erro desconhecido'));
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('Importar Tudo');
					alert('Erro na requisicao.');
				});
			});

			// Nova importacao
			$('#pt-import-nova').on('click', function() {
				$('#pt-import-texto').val('');
				$('#pt-import-preview').html('');
				$('#pt-import-step-3').slideUp(200);
				$('#pt-import-step-1').slideDown(200);
			});

			// Remover sessao
			$('#pt-import-preview').on('click', '.pt-btn-remove-sessao', function() {
				if (confirm('Remover esta sessao?')) {
					$(this).closest('.pt-sessao-block').slideUp(200, function() { $(this).remove(); });
				}
			});

			// Remover participante
			$('#pt-import-preview').on('click', '.pt-btn-remove-part', function() {
				var $row = $(this).closest('.pt-participante-row');
				var $dbInfo = $row.next('.pt-part-db-info');
				$row.slideUp(200, function() { $(this).remove(); });
				if ($dbInfo.length) $dbInfo.slideUp(200, function() { $(this).remove(); });
			});

			// Adicionar participante
			$('#pt-import-preview').on('click', '.pt-btn-add-part', function() {
				var $block = $(this).closest('.pt-sessao-block');
				var si = $('#pt-import-preview .pt-sessao-block').index($block);
				var pi = $(this).closest('.pt-participantes-section').find('.pt-participante-row').length;
				var newRow = renderParticipanteRow(si, pi, {
					nome: '', cargo: '', confirmado: 'nao', papel: '', dup_status: 'novo'
				});
				var $newRow = $(newRow).hide().insertBefore($(this));
				$newRow.slideDown(200, function() {
					var $section = $newRow.closest('.pt-participantes-section')[0];
					initImportPartSortable($section);
				});
			});

			// Adicionar sessao
			$('#pt-btn-add-sessao').on('click', function() {
				var idx = $('#pt-import-preview .pt-sessao-block').length;
				var dia = getImportFirstDay();
				var html = '<div class="pt-sessao-block" data-idx="' + idx + '">';
				html += '<div class="pt-sessao-header">';
				html += '<span class="pt-drag-handle-sessao" title="Arrastar para reordenar">&#10783;</span>';
				html += '<span>Nova sessao <span class="pt-dup-badge pt-dup-novo">Manual</span></span>';
				html += '<input type="hidden" class="pt-s-dup-action" value="criar" />';
				html += '<button type="button" class="pt-btn-remove-sessao" title="Remover sessao">&times;</button>';
				html += '</div>';
				html += '<div class="pt-sessao-fields">';
				html += '<div><label>Dia</label><input type="date" class="pt-s-dia" value="' + dia + '" /></div>';
				html += '<div><label>Inicio</label><input type="time" class="pt-s-inicio" value="09:00" /></div>';
				html += '<div><label>Fim</label><input type="time" class="pt-s-fim" value="10:00" /></div>';
				html += '<div class="pt-field-titulo"><label>Titulo</label><input type="text" class="pt-s-titulo" value="" placeholder="Titulo da sessao" /></div>';
				html += '<div><label>Ordem</label><input type="number" class="pt-s-ordem" value="' + idx + '" style="width:55px" /></div>';
				html += '</div>';
				html += '<div class="pt-sessao-desc"><label style="font-size:12px;font-weight:600;color:#50575e;display:block;margin-bottom:3px;">Descricao</label><textarea class="pt-s-desc"></textarea></div>';
				html += '<div class="pt-participantes-section">';
				html += '<h4>Participantes</h4>';
				html += '<button type="button" class="pt-btn-add-part">+ Adicionar Participante</button>';
				html += '</div>';
				html += '</div>';
				var $el = $(html).hide();
				$('#pt-import-preview').append($el);
				$el.slideDown(200, function() {
					renumberImportOrdens();
					initImportPartSortable($el.find('.pt-participantes-section')[0]);
				});
				$el.find('.pt-s-titulo').focus();
			});

			// SortableJS
			function initImportSortables() {
				if (typeof Sortable === 'undefined') return;
				var container = document.getElementById('pt-import-preview');
				if (!container) return;
				if (Sortable.get(container)) Sortable.get(container).destroy();
				new Sortable(container, {
					handle: '.pt-drag-handle-sessao',
					draggable: '.pt-sessao-block',
					filter: '.pt-dia-divider',
					preventOnFilter: false,
					animation: 150,
					ghostClass: 'pt-sortable-ghost',
					chosenClass: 'pt-sortable-chosen',
					onEnd: function() { renumberImportOrdens(); }
				});
				$('#pt-import-preview .pt-participantes-section').each(function() {
					initImportPartSortable(this);
				});
			}

			function initImportPartSortable(sectionEl) {
				if (typeof Sortable === 'undefined' || !sectionEl) return;
				if (Sortable.get(sectionEl)) Sortable.get(sectionEl).destroy();
				new Sortable(sectionEl, {
					group: { name: 'import-participantes', pull: true, put: true },
					handle: '.pt-drag-handle-part',
					draggable: '.pt-participante-row',
					filter: '.pt-btn-add-part',
					preventOnFilter: false,
					animation: 100,
					ghostClass: 'pt-sortable-ghost'
				});
			}

			function renumberImportOrdens() {
				$('#pt-import-preview .pt-sessao-block').each(function(i) {
					$(this).find('.pt-s-ordem').val(i);
				});
			}

			function getImportFirstDay() {
				var d = $('#pt-import-preview .pt-sessao-block').first().find('.pt-s-dia').val();
				return d || new Date().toISOString().slice(0, 10);
			}

			function esc(s) { return $('<div>').text(s || '').html(); }
			function escA(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

		})(jQuery);
		</script>
		<?php
	}

	// =========================================================================
	// AJAX: Parse
	// =========================================================================

	public function ajax_parse_ia() {
		check_ajax_referer( 'pt_event_importar', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissao.' ) );
		}

		$texto = isset( $_POST['texto'] ) ? wp_unslash( $_POST['texto'] ) : '';
		if ( empty( trim( $texto ) ) ) {
			wp_send_json_error( array( 'message' => 'Texto vazio.' ) );
		}

		$groq = new \PTEvent\Helpers\Groq_Client();
		if ( ! $groq->is_configured() ) {
			wp_send_json_error( array( 'message' => 'Chave de API do Groq nao configurada. Acesse Config. Evento > IA / API.' ) );
		}

		$result = $groq->parse_programacao( $texto );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( empty( $result ) ) {
			wp_send_json_error( array( 'message' => 'A IA nao encontrou sessoes no texto.' ) );
		}

		// Enrich with duplicate detection (same as regular parse)
		$result = $this->enrich_with_duplicates( $result );
		wp_send_json_success( $result );
	}

	public function ajax_parse() {
		check_ajax_referer( 'pt_event_importar', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissao.' );
		}

		$texto = isset( $_POST['texto'] ) ? wp_unslash( $_POST['texto'] ) : '';
		if ( empty( trim( $texto ) ) ) {
			wp_send_json_error( 'Texto vazio.' );
		}

		$sessoes = $this->parse_texto( $texto );
		$sessoes = $this->enrich_with_duplicates( $sessoes );
		wp_send_json_success( $sessoes );
	}

	// =========================================================================
	// AJAX: Importar
	// =========================================================================

	public function ajax_importar() {
		check_ajax_referer( 'pt_event_importar', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissao.' );
		}

		$limpar_sessoes = ! empty( $_POST['limpar_sessoes'] ) && '1' === $_POST['limpar_sessoes'];
		$limpar_parts   = ! empty( $_POST['limpar_participantes'] ) && '1' === $_POST['limpar_participantes'];
		$sessoes_raw    = isset( $_POST['sessoes'] ) ? json_decode( wp_unslash( $_POST['sessoes'] ), true ) : array();

		if ( empty( $sessoes_raw ) ) {
			wp_send_json_error( 'Nenhuma sessao recebida.' );
		}

		if ( $limpar_sessoes ) {
			$this->limpar_posts( 'pt_sessao' );
		}
		if ( $limpar_parts ) {
			$this->limpar_posts( 'pt_participante' );
		}

		$count_sessoes  = 0;
		$count_parts    = 0;
		$count_puladas  = 0;
		$count_atualizadas = 0;

		foreach ( $sessoes_raw as $s ) {
			$titulo      = sanitize_text_field( $s['titulo'] );
			$dia         = sanitize_text_field( $s['dia'] );
			$hora_inicio = sanitize_text_field( $s['hora_inicio'] );
			$hora_fim    = sanitize_text_field( $s['hora_fim'] );
			$descricao   = sanitize_textarea_field( $s['descricao'] );
			$ordem       = isset( $s['ordem'] ) ? absint( $s['ordem'] ) : $count_sessoes;
			$dup_action  = isset( $s['dup_action'] ) ? sanitize_text_field( $s['dup_action'] ) : 'criar';

			// Pular sessao se usuario escolheu
			if ( 'pular' === $dup_action ) {
				$count_puladas++;
				// Mesmo pulando a sessao, processar participantes para vinculo
				$sessao_id = $this->find_existing_sessao( $titulo, $dia, $hora_inicio );
				if ( $sessao_id && ! empty( $s['participantes'] ) ) {
					$this->process_participantes( $sessao_id, $s['participantes'], $count_parts );
				}
				continue;
			}

			// Sobrescrever sessao existente
			if ( 'sobrescrever' === $dup_action ) {
				$sessao_id = $this->find_existing_sessao( $titulo, $dia, $hora_inicio );
				if ( $sessao_id ) {
					wp_update_post( array( 'ID' => $sessao_id, 'post_title' => $titulo ) );
					update_post_meta( $sessao_id, '_pt_event_titulo', $titulo );
					update_post_meta( $sessao_id, '_pt_event_dia', $dia );
					update_post_meta( $sessao_id, '_pt_event_hora_inicio', $hora_inicio );
					update_post_meta( $sessao_id, '_pt_event_hora_fim', $hora_fim );
					update_post_meta( $sessao_id, '_pt_event_descricao', $descricao );
					update_post_meta( $sessao_id, '_pt_event_ordem', $ordem );
					Relationship::remove_all_by_sessao( $sessao_id );
					$count_atualizadas++;
				} else {
					$dup_action = 'criar';
				}
			}

			// Criar nova sessao
			if ( 'criar' === $dup_action ) {
				$sessao_id = wp_insert_post( array(
					'post_type'   => 'pt_sessao',
					'post_title'  => $titulo,
					'post_status' => 'publish',
				) );

				if ( is_wp_error( $sessao_id ) ) {
					continue;
				}

				update_post_meta( $sessao_id, '_pt_event_titulo', $titulo );
				update_post_meta( $sessao_id, '_pt_event_dia', $dia );
				update_post_meta( $sessao_id, '_pt_event_hora_inicio', $hora_inicio );
				update_post_meta( $sessao_id, '_pt_event_hora_fim', $hora_fim );
				update_post_meta( $sessao_id, '_pt_event_descricao', $descricao );
				update_post_meta( $sessao_id, '_pt_event_ordem', $ordem );
				$count_sessoes++;
			}

			// Participantes da sessao
			if ( $sessao_id && ! empty( $s['participantes'] ) ) {
				$this->process_participantes( $sessao_id, $s['participantes'], $count_parts );
			}
		}

		$parts = array();
		if ( $count_sessoes > 0 )      $parts[] = $count_sessoes . ' sessoes criadas';
		if ( $count_atualizadas > 0 )  $parts[] = $count_atualizadas . ' sessoes atualizadas';
		if ( $count_puladas > 0 )      $parts[] = $count_puladas . ' sessoes puladas';
		if ( $count_parts > 0 )        $parts[] = $count_parts . ' novos participantes';

		$msg = implode( ', ', $parts ) . '.';
		wp_send_json_success( array( 'message' => $msg ) );
	}

	// =========================================================================
	// Processar participantes de uma sessao
	// =========================================================================

	private function process_participantes( $sessao_id, $participantes_raw, &$count_new ) {
		$p_ordem = 0;
		foreach ( $participantes_raw as $p ) {
			$nome       = sanitize_text_field( $p['nome'] );
			$cargo      = sanitize_text_field( $p['cargo'] );
			$papel      = sanitize_text_field( $p['papel'] );
			$confirmado = sanitize_text_field( $p['confirmado'] );
			$foto_id    = ! empty( $p['foto_id'] ) ? absint( $p['foto_id'] ) : 0;
			$dup_action = isset( $p['dup_action'] ) ? sanitize_text_field( $p['dup_action'] ) : 'criar';

			if ( empty( $nome ) ) {
				continue;
			}

			// Pular participante
			if ( 'pular' === $dup_action ) {
				// Ainda vincula se existir
				$existing_id = $this->find_existing_participante( $nome );
				if ( $existing_id ) {
					Relationship::add( $sessao_id, $existing_id, $papel, $p_ordem, $cargo );
					$p_ordem++;
				}
				continue;
			}

			// Buscar existente
			$existing_id = $this->find_existing_participante( $nome );

			if ( $existing_id && 'sobrescrever' === $dup_action ) {
				// Atualizar dados
				wp_update_post( array( 'ID' => $existing_id, 'post_title' => $nome ) );
				update_post_meta( $existing_id, '_pt_event_nome', $nome );
				if ( $cargo ) {
					update_post_meta( $existing_id, '_pt_event_cargo', $cargo );
				}
				update_post_meta( $existing_id, '_pt_event_confirmado', $confirmado );
				if ( $foto_id ) {
					update_post_meta( $existing_id, '_pt_event_foto', $foto_id );
					set_post_thumbnail( $existing_id, $foto_id );
				}
				Relationship::add( $sessao_id, $existing_id, $papel, $p_ordem, $cargo );
				$p_ordem++;
				continue;
			}

			if ( $existing_id && 'criar' !== $dup_action ) {
				// Default: vincular existente
				Relationship::add( $sessao_id, $existing_id, $papel, $p_ordem, $cargo );
				$p_ordem++;
				continue;
			}

			// Criar novo participante — cargo vai para post meta como padrão
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

			Relationship::add( $sessao_id, $part_id, $papel, $p_ordem, $cargo );
			$p_ordem++;
			$count_new++;
		}
	}

	// =========================================================================
	// Buscar sessao/participante existente
	// =========================================================================

	private function find_existing_sessao( $titulo, $dia, $hora_inicio ) {
		$posts = get_posts( array(
			'post_type'      => 'pt_sessao',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => '_pt_event_titulo', 'value' => $titulo ),
				array( 'key' => '_pt_event_dia', 'value' => $dia ),
				array( 'key' => '_pt_event_hora_inicio', 'value' => $hora_inicio ),
			),
			'fields' => 'ids',
		) );
		return ! empty( $posts ) ? $posts[0] : 0;
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

	// =========================================================================
	// Limpar posts
	// =========================================================================

	private function limpar_posts( $post_type ) {
		$posts = get_posts( array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );

		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}

		// Limpar relacionamentos se for sessao
		if ( 'pt_sessao' === $post_type ) {
			global $wpdb;
			$table = $wpdb->prefix . 'evento_sessao_participantes';
			$wpdb->query( "TRUNCATE TABLE {$table}" );
		}

		return count( $posts );
	}

	// =========================================================================
	// Parser de texto
	// =========================================================================

	private function enrich_with_duplicates( $sessoes ) {
		// Contar participantes repetidos dentro do proprio texto
		$nomes_no_texto = array();
		foreach ( $sessoes as $s ) {
			if ( ! empty( $s['participantes'] ) ) {
				foreach ( $s['participantes'] as $p ) {
					$key = mb_strtolower( trim( $p['nome'] ), 'UTF-8' );
					if ( ! isset( $nomes_no_texto[ $key ] ) ) {
						$nomes_no_texto[ $key ] = 0;
					}
					$nomes_no_texto[ $key ]++;
				}
			}
		}

		// Buscar sessoes existentes no banco
		$sessoes_db = get_posts( array(
			'post_type'      => 'pt_sessao',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );
		$sessoes_db_map = array();
		foreach ( $sessoes_db as $sid ) {
			$t = get_post_meta( $sid, '_pt_event_titulo', true );
			$d = get_post_meta( $sid, '_pt_event_dia', true );
			$h = get_post_meta( $sid, '_pt_event_hora_inicio', true );
			$key = mb_strtolower( $t, 'UTF-8' ) . '|' . $d . '|' . $h;
			$sessoes_db_map[ $key ] = array( 'id' => $sid, 'titulo' => $t );
		}

		// Buscar participantes existentes no banco
		$parts_db = get_posts( array(
			'post_type'      => 'pt_participante',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );
		$parts_db_map = array();
		foreach ( $parts_db as $pid ) {
			$nome = get_post_meta( $pid, '_pt_event_nome', true );
			$key  = mb_strtolower( trim( $nome ), 'UTF-8' );
			$foto_id = get_post_meta( $pid, '_pt_event_foto', true );
			$parts_db_map[ $key ] = array(
				'id'       => $pid,
				'nome'     => $nome,
				'cargo'    => get_post_meta( $pid, '_pt_event_cargo', true ),
				'foto_url' => $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '',
				'foto_id'  => $foto_id ? absint( $foto_id ) : 0,
			);
		}

		// Enriquecer cada sessao e participante
		foreach ( $sessoes as &$s ) {
			$skey = mb_strtolower( $s['titulo'], 'UTF-8' ) . '|' . $s['dia'] . '|' . $s['hora_inicio'];
			if ( isset( $sessoes_db_map[ $skey ] ) ) {
				$s['dup_status']      = 'existe_db';
				$s['dup_existing_id'] = $sessoes_db_map[ $skey ]['id'];
				$s['dup_action']      = 'pular';
			} else {
				$s['dup_status'] = 'novo';
				$s['dup_action'] = 'criar';
			}

			if ( ! empty( $s['participantes'] ) ) {
				foreach ( $s['participantes'] as &$p ) {
					$pkey = mb_strtolower( trim( $p['nome'] ), 'UTF-8' );
					$p['dup_count_texto'] = isset( $nomes_no_texto[ $pkey ] ) ? $nomes_no_texto[ $pkey ] : 1;

					if ( isset( $parts_db_map[ $pkey ] ) ) {
						$existing = $parts_db_map[ $pkey ];
						$p['dup_status']       = 'existe_db';
						$p['dup_existing_id']  = $existing['id'];
						$p['dup_cargo_db']     = $existing['cargo'];
						$p['dup_foto_url']     = $existing['foto_url'];
						$p['dup_foto_id']      = $existing['foto_id'];
						$p['dup_action']       = 'sobrescrever';
					} else {
						$p['dup_status'] = 'novo';
						$p['dup_action'] = 'criar';
					}

					if ( $p['dup_count_texto'] > 1 ) {
						$p['dup_repetido_texto'] = true;
					}
				}
				unset( $p );
			}
		}
		unset( $s );

		return $sessoes;
	}

	private function parse_texto( $texto ) {
		$linhas  = preg_split( '/\r?\n/', $texto );
		$sessoes = array();
		$dia_atual       = '';
		$dia_label_atual = '';
		$ordem   = 0;

		$i     = 0;
		$total = count( $linhas );

		while ( $i < $total ) {
			$linha = trim( $linhas[ $i ] );

			// Pular linhas vazias e separadores
			if ( empty( $linha ) || preg_match( '/^[_\-=]{3,}$/', $linha ) || preg_match( '/^https?:\/\//', $linha ) ) {
				$i++;
				continue;
			}

			// Detectar dia
			if ( $this->is_dia( $linha ) ) {
				$dia_atual       = $this->parse_dia( $linha );
				$dia_label_atual = $linha;
				$i++;
				continue;
			}

			// Detectar sessao (horario | titulo) ou (horario - titulo)
			if ( preg_match( '/^(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})\s*[\|:\-–]\s*(.+)$/u', $linha, $m ) ) {
				$hora_inicio = $this->normalize_time( $m[1] );
				$hora_fim    = $this->normalize_time( $m[2] );
				$titulo      = trim( $m[3] );
				// Remover possivel status no titulo
				$titulo = preg_replace( '/\s*[-–]\s*(Confirmad[oa]|CANCELADO)\s*$/ui', '', $titulo );

				$descricao     = '';
				$participantes = array();
				$papel_atual   = 'palestrante';
				$i++;

				// Ler linhas seguintes ate encontrar outra sessao ou dia
				while ( $i < $total ) {
					$next = trim( $linhas[ $i ] );

					// Linha vazia - pular
					if ( empty( $next ) ) {
						$i++;
						continue;
					}

					// Separador ou URL - pular
					if ( preg_match( '/^[_\-=]{3,}$/', $next ) || preg_match( '/^https?:\/\//', $next ) ) {
						$i++;
						continue;
					}

					// Proximo dia
					if ( $this->is_dia( $next ) ) {
						break;
					}

					// Proxima sessao
					if ( preg_match( '/^\d{1,2}:\d{2}\s*[-–]\s*\d{1,2}:\d{2}\s*[\|:\-–]/u', $next ) ) {
						break;
					}

					// Marcador de papel
					if ( preg_match( '/^(Moderador|Moderadora|Moderação|Moderacao|Abertura|Debatedores?)\s*:?\s*$/iu', $next, $pm ) ) {
						$papel_atual = $this->normalize_papel( $pm[1] );
						$i++;
						continue;
					}

					// Linha com prefixo de papel: "Moderador: Nome, Cargo"
					if ( preg_match( '/^(Moderador|Moderadora|Moderação|Moderacao|Abertura)\s*:\s*(.+)$/iu', $next, $pm ) ) {
						$papel_linha = $this->normalize_papel( $pm[1] );
						$part = $this->parse_participante_line( $pm[2] );
						if ( $part ) {
							$part['papel'] = $papel_linha;
							$participantes[] = $part;
						}
						$i++;
						continue;
					}

					// Tentar detectar se e participante
					$part = $this->parse_participante_line( $next );
					if ( $part ) {
						if ( empty( $part['papel'] ) ) {
							$part['papel'] = $papel_atual;
						}
						$participantes[] = $part;
						$i++;
						continue;
					}

					// Se nao e participante, e descricao
					$descricao .= ( $descricao ? "\n" : '' ) . $next;
					$i++;
				}

				$sessoes[] = array(
					'dia'           => $dia_atual,
					'dia_label'     => $dia_label_atual,
					'hora_inicio'   => $hora_inicio,
					'hora_fim'      => $hora_fim,
					'titulo'        => $titulo,
					'descricao'     => trim( $descricao ),
					'participantes' => $participantes,
					'ordem'         => $ordem,
				);
				$ordem++;
				continue;
			}

			$i++;
		}

		return $sessoes;
	}

	private function parse_participante_line( $linha ) {
		$linha = trim( $linha );

		// Ignorar linhas que parecem descricao (muito longas sem virgula perto do inicio)
		if ( mb_strlen( $linha ) > 120 && false === mb_strpos( mb_substr( $linha, 0, 60 ), ',' ) ) {
			return null;
		}

		// Ignorar linhas que sao claramente paragrafos descritivos
		if ( preg_match( '/^(Para |Com |A |O |As |Os |Este |Esta |Quais |Como |De que |Servi|Neste )/u', $linha ) ) {
			return null;
		}

		// Ignorar labels sozinhas
		if ( preg_match( '/^(Debatedores?|Programação|Programacao)\s*:?\s*$/iu', $linha ) ) {
			return null;
		}

		// Detectar status de confirmacao
		$confirmado = 'nao';
		if ( preg_match( '/\s*[-–]\s*(Confirmad[oa])\s*$/iu', $linha ) ) {
			$confirmado = 'sim';
			$linha = preg_replace( '/\s*[-–]\s*Confirmad[oa]\s*$/iu', '', $linha );
		} elseif ( preg_match( '/\s*[-–]\s*CANCELADO\s*$/iu', $linha ) ) {
			$confirmado = 'cancelado';
			$linha = preg_replace( '/\s*[-–]\s*CANCELADO\s*$/iu', '', $linha );
		}

		// Detectar "(em video)" ou similar
		$linha = preg_replace( '/\s*\(em v[ií]deo\)\s*/iu', ' ', $linha );
		$linha = trim( $linha );

		// Formato: "Nome, Cargo/Funcao"
		if ( preg_match( '/^([^,]+),\s*(.+)$/u', $linha, $m ) ) {
			$nome  = trim( $m[1] );
			$cargo = trim( $m[2] );

			// Nome nao pode comecar com letra minuscula (indicaria frase)
			$first_char = mb_substr( $nome, 0, 1, 'UTF-8' );
			if ( $first_char === mb_strtolower( $first_char, 'UTF-8' ) && ! preg_match( '/^\d/', $nome ) ) {
				return null;
			}

			// Nome precisa ter pelo menos 2 palavras para ser valido como nome de pessoa
			// Excecao: "Representante da X" nao e nome
			if ( str_word_count( $nome ) < 2 && mb_strlen( $nome ) < 15 ) {
				return null;
			}

			return array(
				'nome'       => $nome,
				'cargo'      => $cargo,
				'confirmado' => $confirmado,
				'papel'      => '',
			);
		}

		// Formato sem virgula mas com confirmacao: "Nome Sobrenome - Confirmado"
		// Ja removemos o status, entao se tinha confirmacao e um nome simples
		if ( 'sim' === $confirmado || 'cancelado' === $confirmado ) {
			$nome = trim( $linha );
			if ( str_word_count( $nome ) >= 2 || mb_strlen( $nome ) >= 10 ) {
				return array(
					'nome'       => $nome,
					'cargo'      => '',
					'confirmado' => $confirmado,
					'papel'      => '',
				);
			}
		}

		// "Representante da X"
		if ( preg_match( '/^Representante\s+(da|de|do)\s+(.+)$/iu', $linha, $m ) ) {
			return array(
				'nome'       => trim( $linha ),
				'cargo'      => '',
				'confirmado' => 'nao',
				'papel'      => 'palestrante',
			);
		}

		return null;
	}

	private function normalize_papel( $papel ) {
		$lower = mb_strtolower( trim( $papel ), 'UTF-8' );
		$map = array(
			'moderador'  => 'moderador',
			'moderadora' => 'moderador',
			'moderação'  => 'moderador',
			'moderacao'  => 'moderador',
			'abertura'   => 'abertura',
			'debatedor'  => 'debatedor',
			'debatedores' => 'debatedor',
		);
		return isset( $map[ $lower ] ) ? $map[ $lower ] : 'palestrante';
	}

	// =========================================================================
	// Helpers de data/hora
	// =========================================================================

	private function is_dia( $linha ) {
		// "Dia 2/09" ou "Dia 02/09 - Terca"
		if ( preg_match( '/^Dia\s+\d{1,2}[\/-]\d{1,2}/iu', $linha ) ) {
			return true;
		}

		// "19 de maio", "2 de setembro"
		$meses = 'janeiro|fevereiro|mar[cç]o|abril|maio|junho|julho|agosto|setembro|outubro|novembro|dezembro';
		if ( preg_match( '/\d{1,2}\s+de\s+(' . $meses . ')/iu', $linha ) ) {
			return true;
		}

		// "02/09/2026"
		if ( preg_match( '/^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}$/', $linha ) ) {
			return true;
		}

		return false;
	}

	private function parse_dia( $linha ) {
		// "Dia 2/09" ou "Dia 02/09 - Terca"
		if ( preg_match( '/(\d{1,2})[\/-](\d{1,2})(?:[\/-](\d{2,4}))?/', $linha, $m ) ) {
			$d = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
			$mo = str_pad( $m[2], 2, '0', STR_PAD_LEFT );
			$y = ! empty( $m[3] ) ? $m[3] : date( 'Y' );
			if ( strlen( $y ) === 2 ) {
				$y = '20' . $y;
			}
			return $y . '-' . $mo . '-' . $d;
		}

		// "19 de maio"
		$meses_map = array(
			'janeiro' => '01', 'fevereiro' => '02', 'marco' => '03', 'março' => '03',
			'abril' => '04', 'maio' => '05', 'junho' => '06',
			'julho' => '07', 'agosto' => '08', 'setembro' => '09',
			'outubro' => '10', 'novembro' => '11', 'dezembro' => '12',
		);

		$lower = mb_strtolower( $linha, 'UTF-8' );
		foreach ( $meses_map as $nome => $num ) {
			if ( false !== mb_strpos( $lower, $nome, 0, 'UTF-8' ) ) {
				if ( preg_match( '/(\d{1,2})/', $linha, $m ) ) {
					$d = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
					return date( 'Y' ) . '-' . $num . '-' . $d;
				}
			}
		}

		return $linha;
	}

	private function normalize_time( $time ) {
		$parts = explode( ':', $time );
		return str_pad( $parts[0], 2, '0', STR_PAD_LEFT ) . ':' . $parts[1];
	}
}
