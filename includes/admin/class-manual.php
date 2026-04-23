<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manual {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'pt-event-settings',
			__( 'Manual & Ajuda', 'pt-event' ),
			__( 'Manual & Ajuda', 'pt-event' ),
			'manage_options',
			'pt-event-manual',
			array( $this, 'render_page' )
		);
	}

	private function get_articles() {
		return array(

			// ── SESSÕES ──────────────────────────────────────────────────────────
			array(
				'id'       => 'sessao-criar',
				'titulo'   => 'Como criar uma nova sessão',
				'categoria'=> 'Sessões',
				'tags'     => 'sessão criar nova adicionar',
				'corpo'    => '
					<p>Cada sessão representa um bloco da programação: uma palestra, painel, abertura, intervalo etc.</p>
					<ol>
						<li>No menu lateral do WordPress, clique em <strong>Programação de Eventos → Sessões</strong>.</li>
						<li>Clique no botão <strong>Adicionar nova</strong>, no topo da página.</li>
						<li>Preencha os campos na caixa <strong>Detalhes da Sessão</strong>:
							<ul>
								<li><strong>Título:</strong> nome da sessão (ex: "Abertura do Evento").</li>
								<li><strong>Subtítulo:</strong> complemento opcional.</li>
								<li><strong>Descrição:</strong> texto explicativo sobre o tema da sessão.</li>
								<li><strong>Dia:</strong> data no formato <code>AAAA-MM-DD</code> (ex: 2026-11-25).</li>
								<li><strong>Hora Início / Hora Fim:</strong> horário no formato <code>HH:MM</code>.</li>
								<li><strong>Trilha:</strong> nome da trilha ou sala (ex: "Sala A"), se o evento tiver trilhas paralelas.</li>
								<li><strong>Ordem:</strong> número que define a posição desta sessão na programação (menor número = primeiro).</li>
							</ul>
						</li>
						<li>Clique em <strong>Publicar</strong> para salvar.</li>
					</ol>',
			),
			array(
				'id'       => 'sessao-editar',
				'titulo'   => 'Como editar uma sessão existente',
				'categoria'=> 'Sessões',
				'tags'     => 'sessão editar alterar modificar',
				'corpo'    => '
					<ol>
						<li>Acesse <strong>Programação de Eventos → Sessões</strong>.</li>
						<li>Passe o mouse sobre o nome da sessão desejada e clique em <strong>Editar</strong>.</li>
						<li>Altere os campos desejados na caixa <strong>Detalhes da Sessão</strong>.</li>
						<li>Clique em <strong>Atualizar</strong> para salvar as alterações.</li>
					</ol>',
			),
			array(
				'id'       => 'sessao-participantes',
				'titulo'   => 'Como adicionar participantes a uma sessão',
				'categoria'=> 'Sessões',
				'tags'     => 'sessão participante adicionar vincular associar',
				'corpo'    => '
					<p>Na tela de edição de uma sessão existe a caixa <strong>Participantes da Sessão</strong>, logo abaixo dos campos de detalhes.</p>
					<ol>
						<li>No campo de busca da caixa, comece a digitar o nome do participante (mínimo 2 letras).</li>
						<li>Selecione o participante na lista que aparecer.</li>
						<li>O participante é adicionado à tabela. Preencha:
							<ul>
								<li><strong>Cargo nesta sessão:</strong> cargo que será exibido para este participante nesta sessão específica. Deixe em branco para usar o cargo padrão do cadastro dele.</li>
								<li><strong>Papel:</strong> função na sessão (Palestrante, Moderador, Debatedor ou Convidado).</li>
							</ul>
						</li>
						<li>Para reordenar, arraste as linhas pelo ícone de grade à esquerda.</li>
						<li>Para remover um participante da sessão, clique no botão <strong>×</strong> à direita da linha.</li>
						<li>Clique em <strong>Atualizar</strong> para salvar.</li>
					</ol>
					<p><strong>Dica:</strong> o participante precisa estar previamente cadastrado para aparecer na busca.</p>',
			),
			array(
				'id'       => 'sessao-trilha',
				'titulo'   => 'O que é "Trilha" e quando usar',
				'categoria'=> 'Sessões',
				'tags'     => 'sessão trilha sala track paralelo',
				'corpo'    => '
					<p>O campo <strong>Trilha</strong> é usado quando o evento tem sessões simultâneas em salas ou espaços diferentes.</p>
					<p>Exemplos de valores: <em>"Sala Principal"</em>, <em>"Auditório A"</em>, <em>"Área de Exposição"</em>.</p>
					<p>Se o evento não tiver sessões paralelas, deixe este campo em branco.</p>',
			),

			// ── PARTICIPANTES ────────────────────────────────────────────────────
			array(
				'id'       => 'part-criar',
				'titulo'   => 'Como cadastrar um participante',
				'categoria'=> 'Participantes',
				'tags'     => 'participante cadastrar criar novo palestrante',
				'corpo'    => '
					<ol>
						<li>No menu lateral, clique em <strong>Programação de Eventos → Participantes</strong>.</li>
						<li>Clique em <strong>Adicionar novo</strong>.</li>
						<li>Preencha os campos na caixa <strong>Detalhes do Participante</strong>:
							<ul>
								<li><strong>Foto:</strong> clique em "Selecionar Foto" para escolher ou enviar uma imagem da Biblioteca de Mídia. Tamanho recomendado: 600 × 550 px.</li>
								<li><strong>Nome:</strong> nome completo do participante.</li>
								<li><strong>Cargo:</strong> cargo padrão (aparece em todas as sessões, a menos que seja sobrescrito).</li>
								<li><strong>Empresa:</strong> organização à qual pertence.</li>
								<li><strong>Tipo de Participante:</strong> classificação geral (Debatedor, Keynote, Moderador etc.).</li>
								<li><strong>Exibir na Home:</strong> marque se este participante deve aparecer no carrossel da página inicial.</li>
								<li><strong>Bio:</strong> texto de apresentação do participante.</li>
								<li><strong>Links:</strong> um link por linha (ex: perfil LinkedIn).</li>
								<li><strong>Confirmado:</strong> selecione "Sim" quando a presença estiver confirmada. Participantes não confirmados não aparecem no site.</li>
							</ul>
						</li>
						<li>Clique em <strong>Publicar</strong>.</li>
					</ol>',
			),
			array(
				'id'       => 'part-foto',
				'titulo'   => 'Como adicionar ou trocar a foto de um participante',
				'categoria'=> 'Participantes',
				'tags'     => 'participante foto imagem upload trocar',
				'corpo'    => '
					<ol>
						<li>Abra o cadastro do participante (menu <strong>Participantes → Editar</strong>).</li>
						<li>Na seção <strong>Foto</strong>, clique em <strong>Selecionar Foto</strong>.</li>
						<li>Na Biblioteca de Mídia, escolha uma imagem existente ou clique em <strong>Enviar arquivos</strong> para subir uma nova.</li>
						<li>Clique em <strong>Usar esta foto</strong>.</li>
						<li>Para remover a foto atual sem substituir, clique em <strong>Remover</strong>.</li>
						<li>Clique em <strong>Atualizar</strong> para salvar.</li>
					</ol>
					<p><strong>Dica de tamanho:</strong> use imagens de pelo menos 600 × 550 px, com enquadramento da cintura para cima, para melhor resultado visual.</p>',
			),
			array(
				'id'       => 'part-cargo-sessao',
				'titulo'   => 'Como definir um cargo diferente por sessão',
				'categoria'=> 'Participantes',
				'tags'     => 'participante cargo sessão diferente sobrescrever personalizar',
				'corpo'    => '
					<p>Um mesmo participante pode aparecer com cargos diferentes em sessões distintas. Por exemplo: "Presidente da Telebrasil" na Abertura e "CEO da TIM Brasil" no Painel Temático.</p>
					<p>Para isso, o cargo é definido <strong>dentro da sessão</strong>, e não no cadastro do participante:</p>
					<ol>
						<li>Acesse a sessão em que o participante tem um cargo específico (<strong>Sessões → Editar</strong>).</li>
						<li>Na caixa <strong>Participantes da Sessão</strong>, localize a linha do participante.</li>
						<li>Preencha o campo <strong>Cargo nesta sessão</strong> com o cargo desejado.</li>
						<li>Clique em <strong>Atualizar</strong>.</li>
					</ol>
					<p>Se o campo "Cargo nesta sessão" estiver em branco, o sistema exibirá automaticamente o cargo padrão do cadastro do participante.</p>',
			),
			array(
				'id'       => 'part-confirmado',
				'titulo'   => 'Como confirmar a presença de um participante',
				'categoria'=> 'Participantes',
				'tags'     => 'participante confirmado confirmar presença cancelado',
				'corpo'    => '
					<p>Somente participantes com status <strong>Confirmado = Sim</strong> aparecem no site.</p>
					<ol>
						<li>Abra o cadastro do participante.</li>
						<li>No campo <strong>Confirmado</strong>, selecione <strong>Sim</strong>.</li>
						<li>Clique em <strong>Atualizar</strong>.</li>
					</ol>
					<p>Para cancelar uma participação, selecione <strong>Não</strong> ou <strong>Cancelado</strong>. O participante deixa de aparecer no site imediatamente.</p>',
			),
			array(
				'id'       => 'part-nao-aparece',
				'titulo'   => 'Por que um participante não aparece no site?',
				'categoria'=> 'Participantes',
				'tags'     => 'participante sumir não aparece site visível oculto',
				'corpo'    => '
					<p>Verifique os itens abaixo:</p>
					<ul>
						<li><strong>Status "Confirmado":</strong> o campo deve estar como <em>Sim</em>. Qualquer outro valor oculta o participante.</li>
						<li><strong>Status de publicação:</strong> o cadastro precisa estar como <em>Publicado</em>, não Rascunho.</li>
						<li><strong>Vínculo com a sessão:</strong> o participante precisa estar adicionado à sessão desejada (via caixa "Participantes da Sessão" ou pelo Editor Visual).</li>
					</ul>',
			),

			// ── IMPORTAÇÃO DE PROGRAMAÇÃO ────────────────────────────────────────
			array(
				'id'       => 'import-prog-visao',
				'titulo'   => 'Como importar a programação com Inteligência Artificial',
				'categoria'=> 'Importação',
				'tags'     => 'importar programação IA inteligência artificial groq sessão',
				'corpo'    => '
					<p>O importador de programação lê um texto colado (ex: agenda de e-mail ou documento) e cria automaticamente as sessões e participantes no sistema.</p>
					<h4>Passo 1 — Cole o texto</h4>
					<ol>
						<li>Acesse <strong>Programação de Eventos → Importar Programação</strong>.</li>
						<li>Cole o texto com a programação no campo de texto. Formatos aceitos por linha:
							<ul>
								<li><code>10:00 – 11:00 | Título da Sessão</code></li>
								<li><code>Nome do Participante, Cargo</code></li>
								<li><code>Nome do Participante - Empresa</code></li>
							</ul>
						</li>
						<li>Clique em <strong>Processar com IA</strong>. A IA analisa o texto e identifica sessões, horários e participantes.</li>
					</ol>
					<h4>Passo 2 — Revise os resultados</h4>
					<ol>
						<li>O sistema exibe as sessões e participantes encontrados.</li>
						<li>Revise e edite os campos diretamente na tela: título, horário, cargo de cada participante etc.</li>
						<li>Para cada participante já cadastrado no sistema, aparece um aviso <em>"Já existe no banco"</em> com opções: <strong>Pular</strong>, <strong>Sobrescrever</strong> ou <strong>Criar novo</strong>.</li>
						<li>Quando estiver satisfeito, clique em <strong>Importar Tudo</strong>.</li>
					</ol>',
			),
			array(
				'id'       => 'import-prog-duplicata',
				'titulo'   => 'O que significa "Já existe no banco" na importação de programação?',
				'categoria'=> 'Importação',
				'tags'     => 'importar duplicata já existe sobrescrever pular criar',
				'corpo'    => '
					<p>Quando o sistema encontra um participante ou sessão que já foi cadastrado anteriormente, exibe um aviso para evitar duplicações.</p>
					<p>Você tem três opções:</p>
					<ul>
						<li><strong>Pular:</strong> não altera o cadastro existente, mas ainda vincula o participante à sessão.</li>
						<li><strong>Sobrescrever:</strong> atualiza os dados do cadastro existente com as informações do texto importado.</li>
						<li><strong>Criar novo:</strong> cria um cadastro separado, mesmo que o nome seja igual.</li>
					</ul>
					<p>Na maioria dos casos, use <strong>Pular</strong> para manter os dados já cadastrados intactos.</p>',
			),

			// ── IMPORTAÇÃO DE PARTICIPANTES ──────────────────────────────────────
			array(
				'id'       => 'import-part-visao',
				'titulo'   => 'Como importar participantes em lote',
				'categoria'=> 'Importação',
				'tags'     => 'importar participantes lote lista em massa',
				'corpo'    => '
					<p>Use este importador quando você tem uma lista de participantes sem a programação completa — por exemplo, uma lista de palestrantes confirmados.</p>
					<h4>Passo 1 — Cole a lista</h4>
					<ol>
						<li>Acesse <strong>Programação de Eventos → Importar Participantes</strong>.</li>
						<li>Cole os nomes no campo de texto. Um por linha. Formatos aceitos:
							<ul>
								<li><code>Nome Completo, Cargo</code></li>
								<li><code>Nome Completo - Cargo</code></li>
								<li><code>Nome Completo | Cargo</code></li>
							</ul>
						</li>
						<li>Clique em <strong>Processar com IA</strong>.</li>
					</ol>
					<h4>Passo 2 — Revise os cards</h4>
					<ol>
						<li>Cada participante aparece como um card. Você pode editar nome, cargo, papel e status de confirmação.</li>
						<li>Clique na foto de cada card para adicionar uma imagem.</li>
						<li>Na seção <strong>Sessões em que participa</strong>, marque as sessões correspondentes.</li>
						<li>Clique em <strong>Revisar importação →</strong>.</li>
					</ol>
					<h4>Passo 3 — Confirme</h4>
					<ol>
						<li>Uma tabela mostra o resumo: quem será criado (verde) e quem já existe no sistema (amarelo).</li>
						<li>Participantes em amarelo terão seus dados <strong>sobrescritos</strong>. Volte e corrija se necessário.</li>
						<li>Clique em <strong>Confirmar e Importar</strong> para salvar tudo.</li>
					</ol>',
			),
			array(
				'id'       => 'import-part-duplicata',
				'titulo'   => 'O que acontece quando um participante já está cadastrado?',
				'categoria'=> 'Importação',
				'tags'     => 'importar participante duplicata já cadastrado atualizar',
				'corpo'    => '
					<p>Na tela de revisão (Passo 3), participantes já cadastrados aparecem com o aviso <strong>"Já cadastrado — atualizar"</strong> em amarelo.</p>
					<p>Se confirmar a importação, os dados desses participantes serão sobrescritos com as informações do card (nome, cargo, foto).</p>
					<p>Se não quiser atualizar um participante já existente, volte ao Passo 2 e remova o card dele clicando no <strong>×</strong> no canto superior direito do card.</p>',
			),

			// ── EDITOR VISUAL ────────────────────────────────────────────────────
			array(
				'id'       => 'editor-visao',
				'titulo'   => 'O que é o Editor Visual e como acessá-lo',
				'categoria'=> 'Editor Visual',
				'tags'     => 'editor visual arrastar reordenar sessão participante',
				'corpo'    => '
					<p>O Editor Visual é uma tela que exibe toda a programação em um único lugar, permitindo reorganizar sessões e editar participantes sem precisar abrir cada cadastro individualmente.</p>
					<ol>
						<li>Acesse <strong>Programação de Eventos → Editor Visual</strong>.</li>
						<li>A programação é exibida como cards organizados por dia.</li>
					</ol>',
			),
			array(
				'id'       => 'editor-reordenar',
				'titulo'   => 'Como reorganizar a ordem das sessões',
				'categoria'=> 'Editor Visual',
				'tags'     => 'editor reordenar sessão ordem arrastar',
				'corpo'    => '
					<ol>
						<li>No Editor Visual, localize a sessão que deseja mover.</li>
						<li>Clique e arraste pelo ícone de grade (⠿) no cabeçalho da sessão.</li>
						<li>Solte na posição desejada.</li>
						<li>Clique em <strong>Salvar tudo</strong> para confirmar.</li>
					</ol>',
			),
			array(
				'id'       => 'editor-participante',
				'titulo'   => 'Como editar um participante pelo Editor Visual',
				'categoria'=> 'Editor Visual',
				'tags'     => 'editor participante editar cargo papel foto',
				'corpo'    => '
					<ol>
						<li>No Editor Visual, localize a sessão e o participante desejado.</li>
						<li>Edite diretamente os campos: <strong>Nome</strong>, <strong>Cargo</strong>, <strong>Papel</strong> e <strong>Confirmado</strong>.</li>
						<li>Para alterar a foto, clique na imagem do participante.</li>
						<li>Para remover o participante da sessão, clique no botão <strong>×</strong> ao lado dele.</li>
						<li>Clique em <strong>Salvar tudo</strong> para confirmar todas as alterações.</li>
					</ol>
					<p><strong>Atenção:</strong> o cargo editado no Editor Visual é o cargo específico para aquela sessão. Para alterar o cargo padrão do participante, edite pelo cadastro em <strong>Participantes → Editar</strong>.</p>',
			),
			array(
				'id'       => 'editor-adicionar-part',
				'titulo'   => 'Como adicionar um participante a uma sessão pelo Editor Visual',
				'categoria'=> 'Editor Visual',
				'tags'     => 'editor adicionar participante sessão novo',
				'corpo'    => '
					<ol>
						<li>No Editor Visual, localize a sessão desejada.</li>
						<li>Clique no botão <strong>+ Participante</strong> dentro do card da sessão.</li>
						<li>Uma nova linha em branco aparece. Preencha o nome e os demais campos.</li>
						<li>Se o participante já existir no sistema, ele será vinculado automaticamente ao salvar. Se for novo, será criado.</li>
						<li>Clique em <strong>Salvar tudo</strong>.</li>
					</ol>',
			),

			// ── PATROCINADORES ───────────────────────────────────────────────────
			array(
				'id'       => 'pat-criar',
				'titulo'   => 'Como cadastrar um patrocinador',
				'categoria'=> 'Patrocinadores',
				'tags'     => 'patrocinador cadastrar criar logo cota',
				'corpo'    => '
					<ol>
						<li>Acesse <strong>Programação de Eventos → Patrocinadores</strong>.</li>
						<li>Clique em <strong>Adicionar novo</strong>.</li>
						<li>Preencha os campos:
							<ul>
								<li><strong>Nome:</strong> nome da empresa patrocinadora.</li>
								<li><strong>Logo:</strong> imagem do logotipo (recomendado: fundo transparente, PNG).</li>
								<li><strong>URL:</strong> endereço do site do patrocinador.</li>
								<li><strong>Cota:</strong> nível de patrocínio — Diamante, Platina, Ouro, Prata ou Bronze.</li>
							</ul>
						</li>
						<li>Clique em <strong>Publicar</strong>.</li>
					</ol>',
			),
			array(
				'id'       => 'pat-cotas',
				'titulo'   => 'O que são as cotas de patrocínio?',
				'categoria'=> 'Patrocinadores',
				'tags'     => 'patrocinador cota diamante platina ouro prata bronze nível',
				'corpo'    => '
					<p>As cotas organizam os patrocinadores por nível de investimento. O sistema tem cinco cotas pré-definidas:</p>
					<ul>
						<li><strong>Diamante</strong> — maior nível</li>
						<li><strong>Platina</strong></li>
						<li><strong>Ouro</strong></li>
						<li><strong>Prata</strong></li>
						<li><strong>Bronze</strong> — menor nível</li>
					</ul>
					<p>No site, os patrocinadores são agrupados e exibidos por cota, do maior para o menor nível.</p>',
			),

			// ── CONFIGURAÇÕES ────────────────────────────────────────────────────
			array(
				'id'       => 'config-ia',
				'titulo'   => 'Como configurar a chave da Inteligência Artificial',
				'categoria'=> 'Configurações',
				'tags'     => 'configuração IA groq chave API inteligência artificial',
				'corpo'    => '
					<p>O importador de programação e o importador de participantes usam a IA Groq para processar textos. É necessário configurar uma chave de acesso.</p>
					<ol>
						<li>Acesse <strong>Programação de Eventos → Configurações → IA / API</strong>.</li>
						<li>Cole a chave da API do Groq no campo <strong>Chave de API Groq</strong>.</li>
						<li>Clique em <strong>Salvar configurações</strong>.</li>
					</ol>
					<p>Se não tiver uma chave, acesse <strong>console.groq.com</strong> para criar uma conta gratuita e gerar a chave.</p>',
			),
			array(
				'id'       => 'config-shortcodes',
				'titulo'   => 'Como exibir a programação e participantes no site',
				'categoria'=> 'Configurações',
				'tags'     => 'shortcode programação site exibir página inserir',
				'corpo'    => '
					<p>Para exibir os dados do plugin em qualquer página do WordPress, insira um dos shortcodes abaixo no editor de conteúdo da página:</p>
					<ul>
						<li><code>[event_programacao]</code> — exibe a programação completa, organizada por dia e sessão.</li>
						<li><code>[event_palestrantes_home]</code> — exibe o carrossel de participantes marcados para aparecer na home.</li>
						<li><code>[event_debatedores]</code> — exibe os debatedores do evento.</li>
						<li><code>[event_patrocinadores]</code> — exibe a grade de patrocinadores por cota.</li>
					</ul>
					<p><strong>Como inserir:</strong> edite a página desejada, adicione um bloco de <em>Shortcode</em> (ou <em>HTML Personalizado</em>) e cole o código acima.</p>',
			),
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$articles   = $this->get_articles();
		$categories = array_unique( array_column( $articles, 'categoria' ) );
		?>
		<div class="wrap pt-help-wrap">

			<div class="pt-help-hero">
				<h1>Manual & Ajuda</h1>
				<p>Encontre rapidamente como fazer qualquer coisa no plugin de Programação de Eventos.</p>
				<div class="pt-help-search-wrapper">
					<input type="text" id="pt-help-search" placeholder="O que você quer fazer? Ex: adicionar foto, importar participante..." autocomplete="off" />
					<span class="pt-help-search-icon">&#128269;</span>
				</div>
			</div>

			<div class="pt-help-cats" id="pt-help-cats">
				<button class="pt-help-cat-btn active" data-cat="">Todos</button>
				<?php foreach ( $categories as $cat ) : ?>
					<button class="pt-help-cat-btn" data-cat="<?php echo esc_attr( $cat ); ?>">
						<?php echo esc_html( $cat ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div id="pt-help-articles" class="pt-help-articles">
				<?php foreach ( $articles as $art ) : ?>
					<div class="pt-help-article"
						data-cat="<?php echo esc_attr( $art['categoria'] ); ?>"
						data-tags="<?php echo esc_attr( strtolower( $art['titulo'] . ' ' . $art['tags'] . ' ' . $art['categoria'] ) ); ?>">
						<button class="pt-help-article-header" aria-expanded="false">
							<span class="pt-help-article-cat-badge"><?php echo esc_html( $art['categoria'] ); ?></span>
							<span class="pt-help-article-title"><?php echo esc_html( $art['titulo'] ); ?></span>
							<span class="pt-help-article-arrow">&#8250;</span>
						</button>
						<div class="pt-help-article-body" hidden>
							<?php echo wp_kses_post( $art['corpo'] ); ?>
						</div>
					</div>
				<?php endforeach; ?>

				<div id="pt-help-empty" class="pt-help-empty" hidden>
					<span>&#128269;</span>
					<p>Nenhum resultado encontrado para "<span id="pt-help-empty-term"></span>".</p>
					<p style="font-size:13px;color:#999;">Tente outras palavras como: <em>criar, editar, foto, importar, sessão, participante</em>.</p>
				</div>
			</div>

		</div>

		<style>
			.pt-help-wrap { max-width: 860px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

			/* Hero */
			.pt-help-hero { background: linear-gradient(135deg, #1d2327 0%, #2c3338 100%); color: #fff; padding: 36px 40px; border-radius: 8px; margin: 16px 0 20px; }
			.pt-help-hero h1 { color: #fff; font-size: 26px; margin: 0 0 8px; }
			.pt-help-hero > p { margin: 0 0 20px; color: #c3c4c7; font-size: 14px; }

			/* Search */
			.pt-help-search-wrapper { position: relative; }
			.pt-help-search-wrapper input { width: 100%; padding: 12px 16px 12px 44px; font-size: 15px; border: none; border-radius: 6px; background: rgba(255,255,255,0.12); color: #fff; box-sizing: border-box; outline: none; }
			.pt-help-search-wrapper input::placeholder { color: rgba(255,255,255,0.5); }
			.pt-help-search-wrapper input:focus { background: rgba(255,255,255,0.2); }
			.pt-help-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 18px; pointer-events: none; }

			/* Category filters */
			.pt-help-cats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
			.pt-help-cat-btn { background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 20px; padding: 5px 14px; font-size: 13px; cursor: pointer; color: #3c434a; transition: all .15s; }
			.pt-help-cat-btn:hover { background: #e0e0e0; }
			.pt-help-cat-btn.active { background: #2271b1; border-color: #2271b1; color: #fff; }

			/* Articles */
			.pt-help-articles { display: flex; flex-direction: column; gap: 6px; }
			.pt-help-article { border: 1px solid #dcdcde; border-radius: 6px; overflow: hidden; background: #fff; transition: box-shadow .15s; }
			.pt-help-article:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }

			.pt-help-article-header { width: 100%; background: none; border: none; padding: 14px 18px; display: flex; align-items: center; gap: 10px; cursor: pointer; text-align: left; }
			.pt-help-article-header[aria-expanded="true"] { background: #f6f7f7; border-bottom: 1px solid #dcdcde; }

			.pt-help-article-cat-badge { background: #e8f0fe; color: #2271b1; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600; white-space: nowrap; flex-shrink: 0; }
			.pt-help-article-title { flex: 1; font-size: 14px; font-weight: 600; color: #1d2327; }
			.pt-help-article-arrow { font-size: 20px; color: #8c8f94; transition: transform .2s; flex-shrink: 0; }
			.pt-help-article-header[aria-expanded="true"] .pt-help-article-arrow { transform: rotate(90deg); }

			.pt-help-article-body { padding: 20px 24px; color: #3c434a; font-size: 14px; line-height: 1.7; }
			.pt-help-article-body h4 { margin: 16px 0 8px; color: #1d2327; font-size: 13px; text-transform: uppercase; letter-spacing: .5px; }
			.pt-help-article-body ol, .pt-help-article-body ul { margin: 8px 0 8px 20px; padding: 0; }
			.pt-help-article-body li { margin-bottom: 6px; }
			.pt-help-article-body p { margin: 0 0 10px; }
			.pt-help-article-body code { background: #f0f0f1; padding: 1px 6px; border-radius: 3px; font-size: 13px; }
			.pt-help-article-body strong { color: #1d2327; }

			/* Highlight */
			.pt-help-highlight { background: #fef9c3; border-radius: 2px; }

			/* Empty */
			.pt-help-empty { text-align: center; padding: 48px 20px; color: #50575e; }
			.pt-help-empty span { font-size: 36px; display: block; margin-bottom: 12px; }
			.pt-help-empty p { margin: 4px 0; font-size: 14px; }

			/* Hidden */
			.pt-help-article[hidden], #pt-help-empty[hidden] { display: none !important; }
		</style>

		<script>
		(function() {
			var search  = document.getElementById('pt-help-search');
			var cats    = document.querySelectorAll('.pt-help-cat-btn');
			var arts    = document.querySelectorAll('.pt-help-article');
			var empty   = document.getElementById('pt-help-empty');
			var emptyT  = document.getElementById('pt-help-empty-term');
			var activeCat = '';

			// ── Accordion ────────────────────────────────────────────────────────
			arts.forEach(function(art) {
				var btn  = art.querySelector('.pt-help-article-header');
				var body = art.querySelector('.pt-help-article-body');
				btn.addEventListener('click', function() {
					var open = btn.getAttribute('aria-expanded') === 'true';
					btn.setAttribute('aria-expanded', open ? 'false' : 'true');
					body.hidden = open;
				});
			});

			// ── Category filter ───────────────────────────────────────────────
			cats.forEach(function(btn) {
				btn.addEventListener('click', function() {
					cats.forEach(function(b) { b.classList.remove('active'); });
					btn.classList.add('active');
					activeCat = btn.dataset.cat;
					filter();
				});
			});

			// ── Search ────────────────────────────────────────────────────────
			var timer;
			search.addEventListener('input', function() {
				clearTimeout(timer);
				timer = setTimeout(filter, 150);
			});

			function normalize(str) {
				return str.toLowerCase()
					.normalize('NFD').replace(/[̀-ͯ]/g, '');
			}

			function filter() {
				var term    = normalize(search.value.trim());
				var words   = term ? term.split(/\s+/) : [];
				var visible = 0;

				arts.forEach(function(art) {
					var tags = normalize(art.dataset.tags || '');
					var cat  = art.dataset.cat;

					var catOk  = !activeCat || cat === activeCat;
					var termOk = words.length === 0 || words.every(function(w) { return tags.indexOf(w) !== -1; });

					if ( catOk && termOk ) {
						art.hidden = false;
						visible++;
					} else {
						art.hidden = true;
						// Close accordion
						var btn  = art.querySelector('.pt-help-article-header');
						var body = art.querySelector('.pt-help-article-body');
						btn.setAttribute('aria-expanded', 'false');
						body.hidden = true;
					}
				});

				// Open single result automatically
				if ( visible === 1 ) {
					var shown = document.querySelector('.pt-help-article:not([hidden])');
					if ( shown ) {
						shown.querySelector('.pt-help-article-header').setAttribute('aria-expanded', 'true');
						shown.querySelector('.pt-help-article-body').hidden = false;
					}
				}

				empty.hidden = visible > 0;
				if ( !empty.hidden ) {
					emptyT.textContent = search.value.trim();
				}
			}
		})();
		</script>
		<?php
	}
}
