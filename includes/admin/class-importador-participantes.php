<?php
namespace PTEvent\Admin;

use PTEvent\Database\Relationship;
use PTEvent\Helpers\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Importador_Participantes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_pt_event_parse_participantes_ia', array( $this, 'ajax_parse_ia' ) );
		add_action( 'wp_ajax_pt_event_importar_participantes', array( $this, 'ajax_importar' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'pt-event-settings',
			__( 'Importar Participantes', 'pt-event' ),
			__( 'Importar Participantes', 'pt-event' ),
			'manage_options',
			'pt-event-importar-participantes',
			array( $this, 'render_page' )
		);
	}

	// =========================================================================
	// Load sessions for the session picker
	// =========================================================================

	private function load_sessoes_para_picker() {
		$posts = get_posts( array(
			'post_type'      => 'pt_sessao',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => '_pt_event_ordem',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		) );

		$sessoes = array();
		foreach ( $posts as $post ) {
			$dia         = get_post_meta( $post->ID, '_pt_event_dia', true );
			$hora_inicio = get_post_meta( $post->ID, '_pt_event_hora_inicio', true );
			$hora_fim    = get_post_meta( $post->ID, '_pt_event_hora_fim', true );
			$titulo      = get_post_meta( $post->ID, '_pt_event_titulo', true );

			$sessoes[] = array(
				'id'         => $post->ID,
				'dia'        => $dia,
				'dia_label'  => Helpers::format_dia_label( $dia ),
				'hora_inicio'=> $hora_inicio,
				'hora_fim'   => $hora_fim,
				'titulo'     => $titulo,
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

		$nonce        = wp_create_nonce( 'pt_event_importar_participantes' );
		$sessoes_json = wp_json_encode( $this->load_sessoes_para_picker() );
		$groq         = new \PTEvent\Helpers\Groq_Client();
		?>
		<div class="wrap pt-event-imp-part">
			<h1><?php esc_html_e( 'Importar Participantes', 'pt-event' ); ?></h1>

			<!-- STEP 1 -->
			<div id="pt-ip-step-1" class="pt-ip-step">
				<h2>1. Cole a lista de participantes</h2>
				<p class="description">
					Cole o texto com os participantes. Formatos aceitos por linha:
					<code>Nome, Cargo</code>, <code>Nome - Cargo</code> ou <code>Nome | Cargo</code>.
				</p>

				<textarea id="pt-ip-texto" rows="15" class="large-text code"
					placeholder="Frederico de Siqueira Filho, Ministro das Comunicações&#10;Carlos Baigorri, Presidente da Anatel&#10;Alberto Griselli, CEO da TIM Brasil"></textarea>

				<p style="margin-top: 16px; display: flex; gap: 12px; align-items: center;">
					<?php if ( $groq->is_configured() ) : ?>
					<button type="button" id="pt-ip-processar" class="button button-primary button-hero"
						style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-shadow: none;">
						&#129302; Processar com IA
					</button>
					<span id="pt-ip-status" style="font-size:13px;color:#666;"></span>
					<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pt-event-settings&tab=ia' ) ); ?>"
						class="button button-hero" style="opacity:0.8;">
						&#129302; Configurar IA para importar
					</a>
					<?php endif; ?>
				</p>
			</div>

			<!-- STEP 2 -->
			<div id="pt-ip-step-2" class="pt-ip-step" style="display:none;">
				<h2>2. Revise e vincule às sessões</h2>
				<p class="description">
					Confira nome e cargo, adicione a foto e marque em quais sessões cada participante aparece.
				</p>

				<div id="pt-ip-preview" class="pt-ip-cards-grid"></div>

				<p style="margin-top: 20px;">
					<button type="button" id="pt-ip-voltar" class="button">&#8592; Voltar</button>
					<button type="button" id="pt-ip-confirmar" class="button button-primary button-hero">Importar Participantes</button>
				</p>
			</div>

			<!-- STEP 3 -->
			<div id="pt-ip-step-3" class="pt-ip-step" style="display:none;">
				<div class="notice notice-success"><p id="pt-ip-resultado"></p></div>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pt_participante' ) ); ?>" class="button button-primary">Ver Participantes</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pt-event-editor' ) ); ?>" class="button">Abrir Editor</a>
					<button type="button" id="pt-ip-nova" class="button">Nova Importação</button>
				</p>
			</div>
		</div>

		<style>
			.pt-event-imp-part { max-width: 1000px; }
			.pt-ip-step { background: #fff; padding: 20px 24px; border: 1px solid #c3c4c7; margin-top: 16px; }

			/* Grid de cards */
			.pt-ip-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 8px; }
			.pt-ip-card { border: 1px solid #c3c4c7; border-radius: 6px; background: #fafafa; overflow: hidden; }
			.pt-ip-card-header { background: #2271b1; color: #fff; padding: 8px 12px; display: flex; align-items: center; gap: 8px; }
			.pt-ip-card-num { background: rgba(255,255,255,0.2); border-radius: 3px; padding: 1px 7px; font-size: 11px; font-weight: 700; flex-shrink: 0; }
			.pt-ip-dup-badge { background: #fef3c7; color: #92400e; border-radius: 3px; padding: 1px 6px; font-size: 11px; font-weight: 600; }
			.pt-ip-card-body { padding: 12px; }
			.pt-ip-foto-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
			.pt-ip-foto { width: 64px; height: 64px; border: 2px dashed #c3c4c7; border-radius: 50%; overflow: hidden; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #f0f0f1; flex-shrink: 0; }
			.pt-ip-foto img { width: 100%; height: 100%; object-fit: cover; }
			.pt-ip-foto .pt-ip-foto-ph { font-size: 22px; color: #999; }
			.pt-ip-foto:hover { border-color: #2271b1; background: #e8f0fe; }
			.pt-ip-field { margin-bottom: 8px; }
			.pt-ip-field label { display: block; font-size: 11px; font-weight: 600; color: #50575e; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.4px; }
			.pt-ip-field input, .pt-ip-field select { width: 100%; padding: 5px 8px; font-size: 13px; }
			.pt-ip-sessoes-label { font-size: 11px; font-weight: 600; color: #50575e; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }
			.pt-ip-sessoes-list { max-height: 180px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; padding: 4px 0; background: #fff; }
			.pt-ip-sessao-dia { padding: 4px 8px; font-size: 11px; font-weight: 700; background: #f0f0f1; color: #50575e; border-bottom: 1px solid #e0e0e0; text-transform: uppercase; letter-spacing: 0.5px; }
			.pt-ip-sessao-item { display: flex; align-items: flex-start; gap: 6px; padding: 5px 8px; border-bottom: 1px dotted #eee; }
			.pt-ip-sessao-item:last-child { border-bottom: none; }
			.pt-ip-sessao-item input[type="checkbox"] { margin-top: 2px; flex-shrink: 0; }
			.pt-ip-sessao-item label { font-size: 12px; line-height: 1.4; cursor: pointer; }
			.pt-ip-sessao-hora { color: #2271b1; font-weight: 600; }
			.pt-ip-sessao-titulo { color: #1d2327; }
			.pt-ip-no-sessoes { padding: 10px 8px; font-size: 12px; color: #999; text-align: center; }
			.pt-ip-card-remove { margin-left: auto; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 1px 8px; border-radius: 3px; cursor: pointer; font-size: 13px; flex-shrink: 0; }
			.pt-ip-card-remove:hover { background: #d63638; border-color: #d63638; }
		</style>

		<script>
		(function($) {
			var nonce    = '<?php echo esc_js( $nonce ); ?>';
			var sessoes  = <?php echo $sessoes_json; ?>;

			// ---- Step 1: Parse com IA ----

			$('#pt-ip-processar').on('click', function() {
				var texto = $('#pt-ip-texto').val().trim();
				if (!texto) { alert('Cole o texto com os participantes.'); return; }
				var $btn = $(this).prop('disabled', true);
				var origHtml = $btn.html();
				$btn.html('&#129302; Analisando...');
				$('#pt-ip-status').text('Enviando para Groq... aguarde.');

				$.post(ajaxurl, {
					action: 'pt_event_parse_participantes_ia',
					nonce:  nonce,
					texto:  texto
				}, function(res) {
					$btn.prop('disabled', false).html(origHtml);
					$('#pt-ip-status').text('');
					if (res.success && res.data && res.data.length) {
						renderCards(res.data);
						$('#pt-ip-step-1').slideUp(200);
						$('#pt-ip-step-2').slideDown(200);
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Nenhum participante encontrado.';
						alert(msg);
					}
				}).fail(function(xhr) {
					$btn.prop('disabled', false).html(origHtml);
					$('#pt-ip-status').text('');
					var msg = 'Erro ao processar com IA.';
					try { var r = JSON.parse(xhr.responseText); if (r.data && r.data.message) msg = r.data.message; } catch(e) {}
					alert(msg);
				});
			});

			// ---- Rendering ----

			function renderCards(participantes) {
				var html = '';
				$.each(participantes, function(i, p) {
					html += renderCard(i, p);
				});
				$('#pt-ip-preview').html(html);

				// Foto upload
				$('#pt-ip-preview').off('click.foto').on('click.foto', '.pt-ip-foto', function() {
					var $div = $(this);
					var frame = wp.media({ title: 'Selecionar foto', button: { text: 'Usar esta foto' }, multiple: false, library: { type: 'image' } });
					frame.on('select', function() {
						var att = frame.state().get('selection').first().toJSON();
						var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
						$div.find('.pt-ip-foto-ph').remove();
						if ($div.find('img').length) { $div.find('img').attr('src', url); }
						else { $div.prepend('<img src="' + url + '" alt="" />'); }
						$div.find('.pt-ip-foto-id').val(att.id);
					});
					frame.open();
				});

				// Remover card
				$('#pt-ip-preview').off('click.remove').on('click.remove', '.pt-ip-card-remove', function() {
					$(this).closest('.pt-ip-card').slideUp(200, function() { $(this).remove(); });
				});
			}

			function renderCard(i, p) {
				var dupBadge = p.dup_status === 'existe_db'
					? '<span class="pt-ip-dup-badge">&#9888; Já cadastrado</span>'
					: '';
				var fotoHtml = (p.dup_status === 'existe_db' && p.dup_foto_url)
					? '<img src="' + escA(p.dup_foto_url) + '" alt="" />'
					: '<span class="pt-ip-foto-ph">+</span>';
				var fotoId = (p.dup_status === 'existe_db' && p.dup_foto_id) ? p.dup_foto_id : '';
				var dbId   = (p.dup_status === 'existe_db' && p.dup_existing_id) ? p.dup_existing_id : '';

				var h = '<div class="pt-ip-card" data-db-id="' + dbId + '">';
				h += '<div class="pt-ip-card-header">';
				h += '<span class="pt-ip-card-num">' + (i + 1) + '</span>';
				h += '<span style="flex:1;font-weight:600;font-size:13px;">' + esc(p.nome) + '</span>';
				h += dupBadge;
				h += '<button type="button" class="pt-ip-card-remove" title="Remover">&times;</button>';
				h += '</div>';
				h += '<div class="pt-ip-card-body">';

				// Foto + nome/cargo
				h += '<div class="pt-ip-foto-row">';
				h += '<div class="pt-ip-foto" title="Clique para adicionar foto">';
				h += '<input type="hidden" class="pt-ip-foto-id" value="' + escA(fotoId) + '" />';
				h += fotoHtml;
				h += '</div>';
				h += '<div style="flex:1;">';
				h += '<div class="pt-ip-field"><label>Nome</label><input type="text" class="pt-ip-nome" value="' + escA(p.nome) + '" /></div>';
				h += '</div>';
				h += '</div>';

				h += '<div class="pt-ip-field"><label>Cargo / Empresa</label><input type="text" class="pt-ip-cargo" value="' + escA(p.cargo || '') + '" /></div>';

				// Papel e confirmação
				h += '<div style="display:flex;gap:8px;">';
				h += '<div class="pt-ip-field" style="flex:1;"><label>Papel</label><select class="pt-ip-papel">';
				var papeis = ['', 'palestrante', 'moderador', 'debatedor', 'keynote', 'abertura'];
				$.each(papeis, function(_, v) {
					var lbl = v ? v.charAt(0).toUpperCase() + v.slice(1) : '-- Papel --';
					h += '<option value="' + v + '"' + (v === (p.papel || '') ? ' selected' : '') + '>' + lbl + '</option>';
				});
				h += '</select></div>';
				h += '<div class="pt-ip-field" style="flex:1;"><label>Confirmado</label><select class="pt-ip-confirmado">';
				h += '<option value="sim"' + (p.confirmado === 'sim' ? ' selected' : '') + '>Confirmado</option>';
				h += '<option value="nao"' + (!p.confirmado || p.confirmado === 'nao' ? ' selected' : '') + '>Não confirmado</option>';
				h += '<option value="cancelado"' + (p.confirmado === 'cancelado' ? ' selected' : '') + '>Cancelado</option>';
				h += '</select></div>';
				h += '</div>';

				// Exibir na home
				h += '<div class="pt-ip-field"><label style="display:flex;align-items:center;gap:6px;text-transform:none;letter-spacing:0;">';
				h += '<input type="checkbox" class="pt-ip-home" value="sim" /> Exibir no carrossel da home</label></div>';

				// Session picker
				h += '<div style="margin-top:8px;">';
				h += '<div class="pt-ip-sessoes-label">Sessões em que participa</div>';
				h += renderSessoesPicker(i);
				h += '</div>';

				h += '</div></div>';
				return h;
			}

			function renderSessoesPicker(cardIdx) {
				if (!sessoes || !sessoes.length) {
					return '<div class="pt-ip-sessoes-list"><div class="pt-ip-no-sessoes">Nenhuma sessão cadastrada ainda.<br>Importe a programação primeiro.</div></div>';
				}

				// Group by day
				var byDay = {}, dayOrder = [];
				$.each(sessoes, function(i, s) {
					var dia = s.dia_label || s.dia || 'Sem data';
					if (!byDay[dia]) { byDay[dia] = []; dayOrder.push(dia); }
					byDay[dia].push(s);
				});

				var h = '<div class="pt-ip-sessoes-list">';
				$.each(dayOrder, function(i, dia) {
					h += '<div class="pt-ip-sessao-dia">' + esc(dia) + '</div>';
					$.each(byDay[dia], function(j, s) {
						var checkId = 'pt-ip-s-' + cardIdx + '-' + s.id;
						h += '<div class="pt-ip-sessao-item">';
						h += '<input type="checkbox" class="pt-ip-sessao-check" id="' + checkId + '" value="' + s.id + '" />';
						h += '<label for="' + checkId + '">';
						h += '<span class="pt-ip-sessao-hora">' + esc(s.hora_inicio) + '–' + esc(s.hora_fim) + '</span> ';
						h += '<span class="pt-ip-sessao-titulo">' + esc(s.titulo) + '</span>';
						h += '</label>';
						h += '</div>';
					});
				});
				h += '</div>';
				return h;
			}

			// ---- Navigation ----

			$('#pt-ip-voltar').on('click', function() {
				$('#pt-ip-step-2').slideUp(200);
				$('#pt-ip-step-1').slideDown(200);
			});

			$('#pt-ip-nova').on('click', function() {
				$('#pt-ip-texto').val('');
				$('#pt-ip-preview').html('');
				$('#pt-ip-step-3').slideUp(200);
				$('#pt-ip-step-1').slideDown(200);
			});

			// ---- Import ----

			$('#pt-ip-confirmar').on('click', function() {
				var $btn = $(this).prop('disabled', true).text('Importando...');
				var participantes = [];

				$('#pt-ip-preview .pt-ip-card').each(function() {
					var $card  = $(this);
					var sessaoIds = [];
					$card.find('.pt-ip-sessao-check:checked').each(function() {
						sessaoIds.push($(this).val());
					});

					participantes.push({
						db_id:      $card.data('db-id') || '',
						nome:       $card.find('.pt-ip-nome').val(),
						cargo:      $card.find('.pt-ip-cargo').val(),
						papel:      $card.find('.pt-ip-papel').val(),
						confirmado: $card.find('.pt-ip-confirmado').val(),
						home:       $card.find('.pt-ip-home').is(':checked') ? 'sim' : 'nao',
						foto_id:    $card.find('.pt-ip-foto-id').val(),
						sessoes:    sessaoIds
					});
				});

				if (!participantes.length) {
					alert('Nenhum participante para importar.');
					$btn.prop('disabled', false).text('Importar Participantes');
					return;
				}

				$.post(ajaxurl, {
					action:        'pt_event_importar_participantes',
					nonce:         nonce,
					participantes: JSON.stringify(participantes)
				}, function(res) {
					$btn.prop('disabled', false).text('Importar Participantes');
					if (res.success) {
						$('#pt-ip-resultado').html(res.data.message);
						$('#pt-ip-step-2').slideUp(200);
						$('#pt-ip-step-3').slideDown(200);
					} else {
						alert('Erro: ' + (res.data || 'Erro desconhecido'));
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('Importar Participantes');
					alert('Erro na requisição.');
				});
			});

			function esc(s)  { return $('<div>').text(s || '').html(); }
			function escA(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

		})(jQuery);
		</script>
		<?php
	}

	// =========================================================================
	// AJAX: Parse com IA (Groq)
	// =========================================================================

	public function ajax_parse_ia() {
		check_ajax_referer( 'pt_event_importar_participantes', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissão.' ) );
		}

		$texto = isset( $_POST['texto'] ) ? wp_unslash( $_POST['texto'] ) : '';
		if ( empty( trim( $texto ) ) ) {
			wp_send_json_error( array( 'message' => 'Texto vazio.' ) );
		}

		$groq = new \PTEvent\Helpers\Groq_Client();
		if ( ! $groq->is_configured() ) {
			wp_send_json_error( array( 'message' => 'Chave de API do Groq não configurada. Acesse Config. Evento > IA / API.' ) );
		}

		$prompt = $this->get_parse_prompt();
		$result = $groq->request( $prompt, $texto );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// request() already returns a decoded PHP value
		$parsed = $result;

		// Handle object wrapper e.g. { "participantes": [...] }
		if ( is_array( $parsed ) && isset( $parsed['participantes'] ) && is_array( $parsed['participantes'] ) ) {
			$parsed = $parsed['participantes'];
		}

		if ( ! is_array( $parsed ) || empty( $parsed ) ) {
			wp_send_json_error( array( 'message' => 'A IA não encontrou participantes no texto. Verifique o formato.' ) );
		}

		// Enrich with duplicate detection
		$enriched = $this->enrich_with_duplicates( $parsed );

		wp_send_json_success( $enriched );
	}

	private function get_parse_prompt() {
		return 'Você é um extrator de dados. Analise o texto abaixo e extraia a lista de participantes/palestrantes.

Cada participante deve ter:
- "nome": nome completo da pessoa
- "cargo": cargo, título e/ou empresa (tudo que vem depois do separador: vírgula, hífen ou traço)
- "confirmado": "sim" se o texto indicar confirmação, "nao" caso contrário

Regras:
- Ignore linhas que não sejam nomes de pessoas (datas, títulos de seção, cabeçalhos, etc.)
- Se uma linha tiver só um nome sem cargo, retorne o cargo como string vazia
- Retorne APENAS o array JSON, sem texto adicional, sem markdown

Formato de retorno:
[{"nome": "...", "cargo": "...", "confirmado": "nao"}, ...]';
	}

	private function enrich_with_duplicates( $participantes ) {
		$enriched = array();
		foreach ( $participantes as $p ) {
			$nome  = isset( $p['nome'] ) ? trim( $p['nome'] ) : '';
			$cargo = isset( $p['cargo'] ) ? trim( $p['cargo'] ) : '';
			if ( empty( $nome ) ) {
				continue;
			}

			$existing = $this->find_existing_participante( $nome );
			if ( $existing ) {
				$foto_id  = get_post_meta( $existing, '_pt_event_foto', true );
				$p['dup_status']      = 'existe_db';
				$p['dup_existing_id'] = $existing;
				$p['dup_cargo_db']    = get_post_meta( $existing, '_pt_event_cargo', true );
				$p['dup_foto_id']     = $foto_id ? absint( $foto_id ) : 0;
				$p['dup_foto_url']    = $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';
				// Keep the parsed cargo (user can decide which to use)
				$p['cargo'] = $cargo ?: $p['dup_cargo_db'];
			} else {
				$p['dup_status'] = 'novo';
				$p['cargo']      = $cargo;
			}

			$p['nome'] = $nome;
			$enriched[] = $p;
		}
		return $enriched;
	}

	// =========================================================================
	// AJAX: Import
	// =========================================================================

	public function ajax_importar() {
		check_ajax_referer( 'pt_event_importar_participantes', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão.' );
		}

		$raw = isset( $_POST['participantes'] ) ? json_decode( wp_unslash( $_POST['participantes'] ), true ) : array();
		if ( empty( $raw ) || ! is_array( $raw ) ) {
			wp_send_json_error( 'Nenhum dado recebido.' );
		}

		$count_created  = 0;
		$count_updated  = 0;
		$count_linked   = 0;

		foreach ( $raw as $p ) {
			$nome       = sanitize_text_field( $p['nome'] ?? '' );
			$cargo      = sanitize_text_field( $p['cargo'] ?? '' );
			$papel      = sanitize_text_field( $p['papel'] ?? '' );
			$confirmado = sanitize_text_field( $p['confirmado'] ?? 'nao' );
			$home       = sanitize_text_field( $p['home'] ?? 'nao' );
			$foto_id    = ! empty( $p['foto_id'] ) ? absint( $p['foto_id'] ) : 0;
			$db_id      = ! empty( $p['db_id'] ) ? absint( $p['db_id'] ) : 0;
			$sessao_ids = isset( $p['sessoes'] ) && is_array( $p['sessoes'] ) ? array_map( 'absint', $p['sessoes'] ) : array();

			if ( empty( $nome ) ) {
				continue;
			}

			// Create or update participant
			if ( $db_id ) {
				// Update existing
				wp_update_post( array( 'ID' => $db_id, 'post_title' => $nome ) );
				update_post_meta( $db_id, '_pt_event_nome', $nome );
				update_post_meta( $db_id, '_pt_event_cargo', $cargo );
				update_post_meta( $db_id, '_pt_event_confirmado', $confirmado );
				update_post_meta( $db_id, '_pt_event_exibir_home', $home );
				if ( $foto_id ) {
					update_post_meta( $db_id, '_pt_event_foto', $foto_id );
					set_post_thumbnail( $db_id, $foto_id );
				}
				$part_id = $db_id;
				$count_updated++;
			} else {
				// Try to find by name first
				$existing = $this->find_existing_participante( $nome );
				if ( $existing ) {
					update_post_meta( $existing, '_pt_event_cargo', $cargo );
					update_post_meta( $existing, '_pt_event_confirmado', $confirmado );
					update_post_meta( $existing, '_pt_event_exibir_home', $home );
					if ( $foto_id ) {
						update_post_meta( $existing, '_pt_event_foto', $foto_id );
						set_post_thumbnail( $existing, $foto_id );
					}
					$part_id = $existing;
					$count_updated++;
				} else {
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
					update_post_meta( $part_id, '_pt_event_exibir_home', $home );
					if ( $foto_id ) {
						update_post_meta( $part_id, '_pt_event_foto', $foto_id );
						set_post_thumbnail( $part_id, $foto_id );
					}
					$count_created++;
				}
			}

			// Link to sessions
			if ( ! empty( $sessao_ids ) && $part_id ) {
				foreach ( $sessao_ids as $sessao_id ) {
					if ( ! $sessao_id ) {
						continue;
					}
					// Get current max order for this session
					global $wpdb;
					$table = $wpdb->prefix . 'evento_sessao_participantes';
					$max_ordem = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COALESCE(MAX(ordem), -1) FROM {$table} WHERE sessao_id = %d",
						$sessao_id
					) );
					// Only add if not already linked
					$exists = $wpdb->get_var( $wpdb->prepare(
						"SELECT id FROM {$table} WHERE sessao_id = %d AND participante_id = %d",
						$sessao_id, $part_id
					) );
					if ( ! $exists ) {
						Relationship::add( $sessao_id, $part_id, $papel, $max_ordem + 1 );
						$count_linked++;
					}
				}
			}
		}

		$parts = array();
		if ( $count_created > 0 ) $parts[] = $count_created . ' participante(s) criado(s)';
		if ( $count_updated > 0 ) $parts[] = $count_updated . ' participante(s) atualizado(s)';
		if ( $count_linked  > 0 ) $parts[] = $count_linked  . ' vínculo(s) com sessão criado(s)';
		$msg = ! empty( $parts ) ? implode( ', ', $parts ) . '.' : 'Nenhuma alteração.';

		wp_send_json_success( array( 'message' => $msg ) );
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
