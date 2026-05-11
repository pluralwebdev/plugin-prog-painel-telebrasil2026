<?php
namespace PTEvent\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Groq_Client {

	private $api_key;
	private $model;
	private $api_url = 'https://api.groq.com/openai/v1/chat/completions';

	public function __construct() {
		$settings      = Helpers::get_settings();
		$this->api_key = isset( $settings['groq_api_key'] ) ? $settings['groq_api_key'] : '';
		$this->model   = isset( $settings['groq_model'] ) && ! empty( $settings['groq_model'] )
			? $settings['groq_model']
			: 'llama-3.3-70b-versatile';
	}

	/**
	 * Check if the API key is configured.
	 */
	public function is_configured() {
		return ! empty( $this->api_key ) && strlen( $this->api_key ) > 10;
	}

	/**
	 * Parse event programming text into structured JSON using Groq AI.
	 *
	 * @param string $texto Raw text with event programming.
	 * @return array|WP_Error Parsed sessions array or WP_Error on failure.
	 */
	public function parse_programacao( $texto ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'groq_no_key', 'Chave de API do Groq não configurada. Acesse Config. Evento > IA / API.' );
		}

		$system_prompt = $this->build_system_prompt();
		$user_prompt   = "Analise o texto abaixo e extraia a programação completa em JSON.\n\n---\n" . $texto . "\n---";

		$body = array(
			'model'       => $this->model,
			'messages'    => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user',   'content' => $user_prompt ),
			),
			'temperature' => 0.1,
			'max_tokens'  => 8000,
		);

		$response = wp_remote_post( $this->api_url, array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'groq_request_failed', 'Erro na requisição ao Groq: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code !== 200 ) {
			$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
			return new \WP_Error( 'groq_api_error', 'Erro da API Groq: ' . $error_msg );
		}

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new \WP_Error( 'groq_empty', 'Resposta vazia do Groq.' );
		}

		$content = $data['choices'][0]['message']['content'];

		// Extract JSON from response (may be wrapped in ```json ... ```)
		$json_str = $content;
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $content, $m ) ) {
			$json_str = trim( $m[1] );
		}

		$parsed = json_decode( $json_str, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'groq_json_error', 'Erro ao decodificar JSON do Groq: ' . json_last_error_msg() . "\n\nResposta raw:\n" . mb_substr( $content, 0, 500 ) );
		}

		// Normalize: accept both { "sessoes": [...] } and direct array
		if ( isset( $parsed['sessoes'] ) && is_array( $parsed['sessoes'] ) ) {
			$parsed = $parsed['sessoes'];
		}

		return $this->normalize_parsed_sessoes( $parsed );
	}

	/**
	 * Send a generic request to Groq with a custom system prompt and user text.
	 *
	 * @param string $system_prompt The system instruction.
	 * @param string $user_text     The user-provided content.
	 * @return array|\WP_Error      Decoded JSON array or WP_Error on failure.
	 */
	public function request( $system_prompt, $user_text ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'groq_no_key', 'Chave de API do Groq não configurada. Acesse Config. Evento > IA / API.' );
		}

		$body = array(
			'model'       => $this->model,
			'messages'    => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user',   'content' => $user_text ),
			),
			'temperature' => 0.1,
			'max_tokens'  => 4000,
		);

		$response = wp_remote_post( $this->api_url, array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'groq_request_failed', 'Erro na requisição ao Groq: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code !== 200 ) {
			$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
			return new \WP_Error( 'groq_api_error', 'Erro da API Groq: ' . $msg );
		}

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new \WP_Error( 'groq_empty', 'Resposta vazia do Groq.' );
		}

		$content  = $data['choices'][0]['message']['content'];
		$json_str = $content;
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $content, $m ) ) {
			$json_str = trim( $m[1] );
		}

		$parsed = json_decode( $json_str, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'groq_json_error', 'JSON inválido: ' . json_last_error_msg() . "\n\nResposta raw:\n" . mb_substr( $content, 0, 500 ) );
		}

		return $parsed;
	}

	/**
	 * Get the default system prompt.
	 */
	public static function get_default_prompt() {
		return 'Você é um parser especializado em programação de eventos brasileiros. Sua tarefa é extrair dados estruturados de textos de programação de eventos.

REGRAS IMPORTANTES:
1. Identifique DIAS (datas) no texto. Formatos possíveis: "Dia 25/11", "25 de novembro", "25/11/2026", "Segunda-feira, 25/11", etc.
2. Identifique SESSÕES com horário de início, fim e título. Formatos: "10:40 - 11:40 | Título", "10h40 às 11h40 - Título", "10:40-11:40: Título", etc. O título da sessão pode aparecer em linha separada do horário (sem o pipe "|") quando o texto está formatado livremente.
3. Identifique SUBTÍTULO: linha curta logo após o título principal que complementa o assunto da sessão. Ex.: título="Painel União Europeia", subtítulo="A infraestrutura digital e a transformação da IA: a experiência Europeia".
4. Identifique PARTICIPANTES de cada sessão. Um participante é uma PESSOA com nome próprio. Formatos possíveis:
   - "Nome Sobrenome, Cargo/Empresa"
   - "Nome Sobrenome - Empresa"
   - "Nome Sobrenome (Empresa)"
   - "Nome - Sigla" (ex: "Ulisses - ANM" = participante com nome "Ulisses" e cargo/empresa "ANM")
   - Apenas "Nome Sobrenome" sem cargo
5. Linhas com nomes próprios (começando com letra maiúscula, 1+ palavras) seguidas de traço/vírgula e sigla ou empresa curta SÃO PARTICIPANTES, não descrições.
6. Descrições são textos longos explicativos sobre o tema da sessão, não contêm nomes de pessoas.
7. Se uma linha tem formato "Nome - Organização/Sigla", é SEMPRE um participante.
8. Detecte PAPÉIS pelos marcadores antes do nome ou antes de um grupo de nomes:
   - "Mensagem inicial:" → papel="mensagem_inicial" (geralmente 1 participante)
   - "Moderador:" / "Moderadora:" / "Moderação:" → papel="moderador"
   - "Abertura:" → papel="abertura"
   - "Debatedores:" → papel="debatedor" (para a lista que segue)
   - "Conferencista:" → papel="conferencista"
   - "Keynote:" → papel="keynote"
   - Sem marcador → papel="palestrante" (default)
9. Detecte SUB-SESSÕES dentro de uma sessão (blocos nomeados internos). Marcadores:
   - "Palestra N: [título]" ou "Palestra N - [título]" → sub_sessao com tipo="palestra" e titulo igual à LINHA INTEIRA (ex: "Palestra 1: A jornada digital europeia e a EU Tech Offer")
   - "Debate N - [título]" ou "Debate N: [título]" → sub_sessao com tipo="debate" e titulo igual à LINHA INTEIRA (ex: "Debate 1 - Infraestruturas integradas: reflexões sobre a experiência europeia e brasileira")
   Os participantes que aparecem LOGO DEPOIS desse marcador (até próxima sub-sessão, próximo marcador de papel, ou linha em branco dupla) pertencem àquela sub-sessão e NÃO entram no array principal "participantes".
10. Em uma sessão com sub-sessões: o array "participantes" principal contém SOMENTE Mensagem inicial e Moderador (e outros papéis fora de sub-sessões). Os palestrantes/debatedores das sub-sessões vão DENTRO de sub_sessoes[].participantes.
11. Detecte confirmação: se a linha contém "Confirmado/a", marque confirmado="sim". Se contém "CANCELADO", marque confirmado="cancelado". Caso contrário, confirmado="nao".

FORMATO DE SAÍDA (JSON puro, sem markdown):
{
  "sessoes": [
    {
      "dia": "2026-11-25",
      "dia_label": "Dia 25/11 - Terça",
      "hora_inicio": "10:40",
      "hora_fim": "11:40",
      "titulo": "Título da sessão",
      "subtitulo": "Subtítulo curto, se houver",
      "descricao": "Texto descritivo opcional",
      "participantes": [
        {
          "nome": "Nome Completo",
          "cargo": "Cargo ou Empresa",
          "papel": "palestrante",
          "confirmado": "nao"
        }
      ],
      "sub_sessoes": [
        {
          "tipo": "palestra",
          "titulo": "Palestra 1: A jornada digital europeia e a EU Tech Offer",
          "participantes": [
            { "nome": "Robert Steinlechner", "cargo": "Delegação da União Europeia", "confirmado": "nao" }
          ]
        },
        {
          "tipo": "debate",
          "titulo": "Debate 1 - Infraestruturas integradas: reflexões sobre a experiência europeia e brasileira",
          "participantes": [
            { "nome": "Cristiane Rauen", "cargo": "Diretora de Transformação Digital e Inovação do MDIC", "confirmado": "nao" }
          ]
        }
      ]
    }
  ]
}

REGRAS DE FORMATO:
- dia: formato ISO "YYYY-MM-DD". Se o ano não aparece, use 2026.
- dia_label: texto original do dia como aparece no texto.
- hora_inicio e hora_fim: formato "HH:MM" (24h).
- subtitulo: se a sessão não tem subtítulo claro, omita o campo ou use "".
- papel: um de "mensagem_inicial", "palestrante", "conferencista", "moderador", "debatedor", "abertura", "keynote".
- confirmado: "sim", "nao" ou "cancelado".
- sub_sessoes: array vazio [] ou omitido quando não há sub-sessões.
- Dentro de sub_sessoes, "tipo" é "palestra" ou "debate". O "titulo" deve preservar o texto completo da linha marcadora ("Palestra 1: ..." ou "Debate 1 - ...").
- Se não houver participantes na sessão, retorne array vazio [].
- NÃO invente dados. Extraia SOMENTE o que está no texto.
- Retorne APENAS o JSON, sem explicações.';
	}

	/**
	 * Build the system prompt, reading from settings or falling back to default.
	 */
	private function build_system_prompt() {
		$settings = Helpers::get_settings();
		if ( ! empty( $settings['groq_system_prompt'] ) ) {
			return $settings['groq_system_prompt'];
		}
		return self::get_default_prompt();
	}

	/**
	 * Normalize the parsed sessions array to match the format expected by the importer frontend.
	 */
	private function normalize_parsed_sessoes( $sessoes ) {
		$normalized = array();
		$ordem = 0;

		foreach ( $sessoes as $s ) {
			$sessao = array(
				'dia'           => isset( $s['dia'] ) ? sanitize_text_field( $s['dia'] ) : '',
				'dia_label'     => isset( $s['dia_label'] ) ? sanitize_text_field( $s['dia_label'] ) : '',
				'hora_inicio'   => isset( $s['hora_inicio'] ) ? $this->normalize_time( $s['hora_inicio'] ) : '',
				'hora_fim'      => isset( $s['hora_fim'] ) ? $this->normalize_time( $s['hora_fim'] ) : '',
				'titulo'        => isset( $s['titulo'] ) ? sanitize_text_field( $s['titulo'] ) : '',
				'descricao'     => isset( $s['descricao'] ) ? sanitize_textarea_field( $s['descricao'] ) : '',
				'participantes' => array(),
				'ordem'         => $ordem,
			);

			if ( ! empty( $s['participantes'] ) && is_array( $s['participantes'] ) ) {
				foreach ( $s['participantes'] as $p ) {
					$sessao['participantes'][] = array(
						'nome'       => isset( $p['nome'] ) ? sanitize_text_field( $p['nome'] ) : '',
						'cargo'      => isset( $p['cargo'] ) ? sanitize_text_field( $p['cargo'] ) : '',
						'papel'      => isset( $p['papel'] ) ? sanitize_text_field( $p['papel'] ) : 'palestrante',
						'confirmado' => isset( $p['confirmado'] ) ? sanitize_text_field( $p['confirmado'] ) : 'nao',
					);
				}
			}

			$normalized[] = $sessao;
			$ordem++;
		}

		return $normalized;
	}

	/**
	 * Normalize time to HH:MM format.
	 */
	private function normalize_time( $time ) {
		$time = trim( $time );
		// Handle "10h40" format
		if ( preg_match( '/^(\d{1,2})h(\d{2})$/i', $time, $m ) ) {
			$time = $m[1] . ':' . $m[2];
		}
		$parts = explode( ':', $time );
		if ( count( $parts ) >= 2 ) {
			return str_pad( $parts[0], 2, '0', STR_PAD_LEFT ) . ':' . $parts[1];
		}
		return $time;
	}
}
