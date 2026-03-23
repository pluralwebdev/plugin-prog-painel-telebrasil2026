<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Seeder {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_post_pt_event_seed', array( $this, 'run_seed' ) );
	}

	/**
	 * Import seed photo into WP Media Library via URL sideload.
	 */
	private function import_seed_photo() {
		$url = 'https://paineltelebrasil.org.br/wp-content/uploads/2026/03/debatedor-fallback.png';

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$file_array = array(
			'name'     => 'debatedor-fallback.png',
			'tmp_name' => $tmp,
		);

		$attach_id = media_handle_sideload( $file_array, 0, 'Debatedor Fallback' );

		if ( is_wp_error( $attach_id ) ) {
			@unlink( $tmp );
			return 0;
		}

		return $attach_id;
	}

	public function run_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}

		check_admin_referer( 'pt_event_seed_action' );

		$seed_home           = ! empty( $_POST['seed_home'] );
		$seed_debatedores    = ! empty( $_POST['seed_debatedores'] );
		$seed_patrocinadores = ! empty( $_POST['seed_patrocinadores'] );

		if ( ! $seed_home && ! $seed_debatedores && ! $seed_patrocinadores ) {
			wp_redirect( admin_url( 'admin.php?page=pt-event-settings&tab=ferramentas&seeded=0&seed_error=nenhum' ) );
			exit;
		}

		// Limpar seeds anteriores de participantes
		if ( $seed_home || $seed_debatedores ) {
			$old = get_posts( array(
				'post_type'      => 'pt_participante',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'meta_key'       => '_pt_event_is_seed',
				'meta_value'     => '1',
			) );
			foreach ( $old as $old_id ) {
				wp_delete_post( $old_id, true );
			}
		}

		// Limpar seeds anteriores de patrocinadores
		if ( $seed_patrocinadores ) {
			$old_pat = get_posts( array(
				'post_type'      => 'pt_patrocinador',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'meta_key'       => '_pt_event_is_seed',
				'meta_value'     => '1',
			) );
			foreach ( $old_pat as $old_id ) {
				wp_delete_post( $old_id, true );
			}
		}

		$foto_id = ( $seed_home || $seed_debatedores ) ? $this->import_seed_photo() : 0;

		$participantes = array(
			array(
				'nome'    => 'Ricardo Almeida',
				'cargo'   => 'CEO',
				'empresa' => 'TeleBrasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Especialista em telecomunicações com mais de 20 anos de experiência no setor.',
			),
			array(
				'nome'    => 'Fernanda Costa',
				'cargo'   => 'Diretora de Inovação',
				'empresa' => 'Vivo Telefônica',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Líder em transformação digital e inovação em telecomunicações.',
			),
			array(
				'nome'    => 'Carlos Eduardo Santos',
				'cargo'   => 'VP de Tecnologia',
				'empresa' => 'Claro Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Responsável pela expansão da rede 5G no Brasil.',
			),
			array(
				'nome'    => 'Ana Paula Rodrigues',
				'cargo'   => 'Superintendente',
				'empresa' => 'Anatel',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Superintendente de Planejamento e Regulamentação da Anatel.',
			),
			array(
				'nome'    => 'Marcos Oliveira',
				'cargo'   => 'Diretor de Redes',
				'empresa' => 'TIM Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Engenheiro de telecomunicações, especialista em infraestrutura de redes.',
			),
			array(
				'nome'    => 'Juliana Mendes',
				'cargo'   => 'Head de Regulação',
				'empresa' => 'Oi S.A.',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Advogada especialista em direito regulatório de telecomunicações.',
			),
			array(
				'nome'    => 'Roberto Figueiredo',
				'cargo'   => 'CTO',
				'empresa' => 'Huawei Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Diretor de Tecnologia com foco em soluções 5G e IoT.',
			),
			array(
				'nome'    => 'Patrícia Lima',
				'cargo'   => 'Diretora Executiva',
				'empresa' => 'Conexis Brasil Digital',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Representante do setor de telecomunicações junto ao governo federal.',
			),
			array(
				'nome'    => 'Thiago Barbosa',
				'cargo'   => 'Gerente de Projetos',
				'empresa' => 'Ericsson',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Gerente de projetos de implantação de infraestrutura 5G.',
			),
			array(
				'nome'    => 'Luciana Ferreira',
				'cargo'   => 'Diretora de Operações',
				'empresa' => 'Nokia Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Especialista em redes de próxima geração e cloud.',
			),
			array(
				'nome'    => 'Eduardo Nascimento',
				'cargo'   => 'Diretor de Infraestrutura',
				'empresa' => 'Embratel',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Responsável pela modernização da infraestrutura de backbone nacional.',
			),
			array(
				'nome'    => 'Renata Vieira',
				'cargo'   => 'VP de Estratégia',
				'empresa' => 'Oi S.A.',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Lidera a transformação estratégica e reestruturação da companhia.',
			),
			array(
				'nome'    => 'Felipe Monteiro',
				'cargo'   => 'Diretor de Espectro',
				'empresa' => 'Anatel',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Especialista em gestão e regulamentação do espectro radioelétrico.',
			),
			array(
				'nome'    => 'Daniela Cunha',
				'cargo'   => 'Head de IoT',
				'empresa' => 'Vivo Telefônica',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Pioneira em soluções de Internet das Coisas para o mercado brasileiro.',
			),
			array(
				'nome'    => 'Henrique Bastos',
				'cargo'   => 'Diretor Técnico',
				'empresa' => 'Furukawa Electric',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Engenheiro com foco em soluções de fibra óptica e conectividade.',
			),
			array(
				'nome'    => 'Tatiana Moreira',
				'cargo'   => 'Gerente de Inovação',
				'empresa' => 'TIM Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Responsável por projetos de inovação aberta e parcerias tecnológicas.',
			),
			array(
				'nome'    => 'Leonardo Duarte',
				'cargo'   => 'CTO',
				'empresa' => 'V.tal',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Diretor de tecnologia focado em redes neutras e infraestrutura compartilhada.',
			),
			array(
				'nome'    => 'Vanessa Ribeiro',
				'cargo'   => 'Diretora de Regulação',
				'empresa' => 'Claro Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Especialista em políticas regulatórias e relações governamentais.',
			),
			array(
				'nome'    => 'Rodrigo Campos',
				'cargo'   => 'VP de Engenharia',
				'empresa' => 'Samsung Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Vice-presidente de engenharia com foco em dispositivos 5G e IA.',
			),
			array(
				'nome'    => 'Aline Machado',
				'cargo'   => 'Diretora de Políticas Públicas',
				'empresa' => 'Conexis Brasil Digital',
				'tipo'    => 'debatedor',
				'home'    => 'sim',
				'bio'     => 'Atua na articulação de políticas públicas para o setor de telecomunicações.',
			),
			array(
				'nome'    => 'André Souza',
				'cargo'   => 'Conselheiro',
				'empresa' => 'Anatel',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Conselheiro da Anatel com atuação em políticas de espectro.',
			),
			array(
				'nome'    => 'Beatriz Carvalho',
				'cargo'   => 'VP Comercial',
				'empresa' => 'Algar Telecom',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Vice-presidente comercial com experiência em mercado regional.',
			),
			array(
				'nome'    => 'Camila Neves',
				'cargo'   => 'Coordenadora de Pesquisa',
				'empresa' => 'CPqD',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Pesquisadora em redes ópticas e comunicações avançadas.',
			),
			array(
				'nome'    => 'Diego Ramos',
				'cargo'   => 'Diretor Jurídico',
				'empresa' => 'SindiTelebrasil',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Advogado com experiência em regulação do setor de telecom.',
			),
			array(
				'nome'    => 'Marina Azevedo',
				'cargo'   => 'Head de Produto',
				'empresa' => 'Qualcomm Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Especialista em chipsets e tecnologia de comunicação móvel.',
			),
			array(
				'nome'    => 'Gabriel Teixeira',
				'cargo'   => 'Engenheiro Sênior',
				'empresa' => 'NEC Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Engenheiro especialista em soluções de backhaul e fronthaul.',
			),
			array(
				'nome'    => 'Isabela Prado',
				'cargo'   => 'Diretora de Sustentabilidade',
				'empresa' => 'Vivo Telefônica',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Especialista em ESG e sustentabilidade no setor de telecomunicações.',
			),
			array(
				'nome'    => 'Paulo Henrique Martins',
				'cargo'   => 'Diretor de Estratégia',
				'empresa' => 'Samsung Brasil',
				'tipo'    => 'debatedor',
				'home'    => 'nao',
				'bio'     => 'Diretor de estratégia para dispositivos conectados.',
			),
		);

		$created = 0;

		foreach ( $participantes as $p ) {
			// Pular conforme seleção do usuário
			$is_home = ( $p['home'] === 'sim' );
			if ( $is_home && ! $seed_home ) {
				continue;
			}
			if ( ! $is_home && ! $seed_debatedores ) {
				continue;
			}

			// Se só quer home, forçar exibir_home=sim; se só debatedores, forçar nao
			$exibir_home = $p['home'];
			if ( $seed_home && ! $seed_debatedores ) {
				$exibir_home = 'sim';
			}

			$post_id = wp_insert_post( array(
				'post_type'   => 'pt_participante',
				'post_title'  => $p['nome'],
				'post_status' => 'publish',
			) );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_pt_event_nome', $p['nome'] );
			update_post_meta( $post_id, '_pt_event_cargo', $p['cargo'] );
			update_post_meta( $post_id, '_pt_event_empresa', $p['empresa'] );
			update_post_meta( $post_id, '_pt_event_tipo_participante', $p['tipo'] );
			update_post_meta( $post_id, '_pt_event_exibir_home', $exibir_home );
			update_post_meta( $post_id, '_pt_event_confirmado', 'sim' );
			update_post_meta( $post_id, '_pt_event_bio', $p['bio'] );
			update_post_meta( $post_id, '_pt_event_foto', $foto_id ? $foto_id : '' );
			update_post_meta( $post_id, '_pt_event_links', '' );
			update_post_meta( $post_id, '_pt_event_is_seed', '1' );

			$created++;
		}

		// Seed patrocinadores
		$created_pat = 0;
		if ( $seed_patrocinadores ) {
			$created_pat = $this->seed_patrocinadores();
		}

		$total = $created + $created_pat;
		wp_redirect( admin_url( 'admin.php?page=pt-event-settings&tab=ferramentas&seeded=' . $total . '&seeded_pat=' . $created_pat ) );
		exit;
	}

	/**
	 * Seed patrocinadores with placeholder logos.
	 */
	private function seed_patrocinadores() {
		// Ensure default cotas exist
		\PTEvent\PostTypes\Patrocinador::seed_default_cotas();

		$cotas = get_terms( array(
			'taxonomy'   => 'pt_cota_patrocinio',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num',
			'meta_key'   => '_pt_cota_order',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $cotas ) || empty( $cotas ) ) {
			return 0;
		}

		// Map cota name => term_id
		$cota_map = array();
		foreach ( $cotas as $c ) {
			$cota_map[ $c->name ] = $c->term_id;
		}

		$sponsors = array(
			// Diamante
			array( 'nome' => 'TeleBrasil Telecom',     'cota' => 'Diamante', 'cor_bg' => '0A1E3D', 'cor_txt' => 'FFFFFF', 'ordem' => 1 ),
			array( 'nome' => 'Conexis Digital',         'cota' => 'Diamante', 'cor_bg' => '006B3F', 'cor_txt' => 'FFFFFF', 'ordem' => 2 ),
			// Platinum
			array( 'nome' => 'Vivo Telefônica',         'cota' => 'Platinum', 'cor_bg' => '660099', 'cor_txt' => 'FFFFFF', 'ordem' => 1 ),
			array( 'nome' => 'Claro Brasil',            'cota' => 'Platinum', 'cor_bg' => 'DA291C', 'cor_txt' => 'FFFFFF', 'ordem' => 2 ),
			array( 'nome' => 'TIM Brasil',              'cota' => 'Platinum', 'cor_bg' => '003D7C', 'cor_txt' => 'FFFFFF', 'ordem' => 3 ),
			// Ouro
			array( 'nome' => 'Huawei Technologies',     'cota' => 'Ouro', 'cor_bg' => 'CF0A2C', 'cor_txt' => 'FFFFFF', 'ordem' => 1 ),
			array( 'nome' => 'Ericsson Brasil',         'cota' => 'Ouro', 'cor_bg' => '002F6C', 'cor_txt' => 'FFFFFF', 'ordem' => 2 ),
			array( 'nome' => 'Nokia Networks',          'cota' => 'Ouro', 'cor_bg' => '124191', 'cor_txt' => 'FFFFFF', 'ordem' => 3 ),
			array( 'nome' => 'Samsung Brasil',          'cota' => 'Ouro', 'cor_bg' => '1428A0', 'cor_txt' => 'FFFFFF', 'ordem' => 4 ),
			// Prata
			array( 'nome' => 'Qualcomm',                'cota' => 'Prata', 'cor_bg' => '3253DC', 'cor_txt' => 'FFFFFF', 'ordem' => 1 ),
			array( 'nome' => 'NEC Brasil',              'cota' => 'Prata', 'cor_bg' => '1414A0', 'cor_txt' => 'FFFFFF', 'ordem' => 2 ),
			array( 'nome' => 'Algar Telecom',           'cota' => 'Prata', 'cor_bg' => '00A651', 'cor_txt' => 'FFFFFF', 'ordem' => 3 ),
			array( 'nome' => 'Oi S.A.',                 'cota' => 'Prata', 'cor_bg' => 'F5A623', 'cor_txt' => '333333', 'ordem' => 4 ),
			array( 'nome' => 'Furukawa Electric',       'cota' => 'Prata', 'cor_bg' => '0066B3', 'cor_txt' => 'FFFFFF', 'ordem' => 5 ),
			array( 'nome' => 'Padtec',                  'cota' => 'Prata', 'cor_bg' => '00427A', 'cor_txt' => 'FFFFFF', 'ordem' => 6 ),
			// Bronze
			array( 'nome' => 'CPqD',                    'cota' => 'Bronze', 'cor_bg' => '009B3A', 'cor_txt' => 'FFFFFF', 'ordem' => 1 ),
			array( 'nome' => 'V.tal',                   'cota' => 'Bronze', 'cor_bg' => '5B2D8E', 'cor_txt' => 'FFFFFF', 'ordem' => 2 ),
			array( 'nome' => 'Cisco Brasil',            'cota' => 'Bronze', 'cor_bg' => '049FD9', 'cor_txt' => 'FFFFFF', 'ordem' => 3 ),
			array( 'nome' => 'ZTE Corporation',         'cota' => 'Bronze', 'cor_bg' => '0057A4', 'cor_txt' => 'FFFFFF', 'ordem' => 4 ),
			array( 'nome' => 'Amdocs',                  'cota' => 'Bronze', 'cor_bg' => '003A5C', 'cor_txt' => 'FFFFFF', 'ordem' => 5 ),
			array( 'nome' => 'Multilaser Pro',          'cota' => 'Bronze', 'cor_bg' => 'E31837', 'cor_txt' => 'FFFFFF', 'ordem' => 6 ),
		);

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$created = 0;

		foreach ( $sponsors as $s ) {
			$cota_term_id = isset( $cota_map[ $s['cota'] ] ) ? $cota_map[ $s['cota'] ] : 0;
			if ( ! $cota_term_id ) {
				continue;
			}

			// Download placeholder logo
			$logo_id = $this->import_placeholder_logo( $s['nome'], $s['cor_bg'], $s['cor_txt'] );

			$post_id = wp_insert_post( array(
				'post_type'   => 'pt_patrocinador',
				'post_title'  => $s['nome'],
				'post_status' => 'publish',
			) );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_pt_patrocinador_logo', $logo_id );
			update_post_meta( $post_id, '_pt_patrocinador_cota', $cota_term_id );
			update_post_meta( $post_id, '_pt_patrocinador_ordem', $s['ordem'] );
			update_post_meta( $post_id, '_pt_patrocinador_url', 'https://example.com' );
			update_post_meta( $post_id, '_pt_event_is_seed', '1' );

			wp_set_object_terms( $post_id, array( (int) $cota_term_id ), 'pt_cota_patrocinio' );

			$created++;
		}

		return $created;
	}

	/**
	 * Download a placeholder logo from placehold.co and import to media library.
	 */
	private function import_placeholder_logo( $name, $bg_color, $txt_color ) {
		$text     = urlencode( $name );
		$filename = sanitize_file_name( 'logo-seed-' . sanitize_title( $name ) . '.png' );
		$url      = 'https://placehold.co/400x200/' . $bg_color . '/' . $txt_color . '/png?text=' . $text . '&font=roboto';

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);

		$attach_id = media_handle_sideload( $file_array, 0, 'Logo Seed: ' . $name );

		if ( is_wp_error( $attach_id ) ) {
			@unlink( $tmp );
			return 0;
		}

		return $attach_id;
	}
}
