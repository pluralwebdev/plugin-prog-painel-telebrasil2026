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

		$seed_home       = ! empty( $_POST['seed_home'] );
		$seed_debatedores = ! empty( $_POST['seed_debatedores'] );

		if ( ! $seed_home && ! $seed_debatedores ) {
			wp_redirect( admin_url( 'admin.php?page=pt-event-settings&seeded=0&seed_error=nenhum' ) );
			exit;
		}

		// Limpar APENAS seeds anteriores (marcados com _pt_event_is_seed)
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

		$foto_id = $this->import_seed_photo();

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

		wp_redirect( admin_url( 'admin.php?page=pt-event-settings&seeded=' . $created ) );
		exit;
	}
}
