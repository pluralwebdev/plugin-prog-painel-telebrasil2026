<?php
namespace PTEvent\Admin;

use PTEvent\Database\Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sessao_Participantes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_pt_sessao', array( $this, 'save' ), 20 );
		add_action( 'wp_ajax_pt_event_search_participantes', array( $this, 'ajax_search' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			'pt_event_sessao_participantes',
			__( 'Participantes da Sessão', 'pt-event' ),
			array( $this, 'render' ),
			'pt_sessao',
			'normal',
			'default'
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'pt_event_sessao_parts', 'pt_event_sessao_parts_nonce' );

		$current = Relationship::get_by_sessao( $post->ID );
		$papeis  = array(
			''           => __( '— Selecionar —', 'pt-event' ),
			'palestrante'   => __( 'Palestrante', 'pt-event' ),
			'conferencista' => __( 'Conferencista', 'pt-event' ),
			'moderador'     => __( 'Moderador', 'pt-event' ),
			'debatedor'     => __( 'Debatedor', 'pt-event' ),
			'convidado'     => __( 'Convidado', 'pt-event' ),
		);

		?>
		<div class="pt-event-participantes-box">
			<div class="pt-event-search-bar">
				<input type="text" id="pt-event-search-input" placeholder="<?php esc_attr_e( 'Buscar participante...', 'pt-event' ); ?>" autocomplete="off" />
				<div id="pt-event-search-results" class="pt-event-search-results"></div>
			</div>

			<table class="wp-list-table widefat striped pt-event-participantes-table">
				<thead>
					<tr>
						<th class="pt-event-col-order"><?php esc_html_e( 'Ordem', 'pt-event' ); ?></th>
						<th><?php esc_html_e( 'Participante', 'pt-event' ); ?></th>
						<th><?php esc_html_e( 'Cargo nesta sessão', 'pt-event' ); ?></th>
						<th><?php esc_html_e( 'Papel', 'pt-event' ); ?></th>
						<th class="pt-event-col-actions"><?php esc_html_e( 'Ações', 'pt-event' ); ?></th>
					</tr>
				</thead>
				<tbody id="pt-event-participantes-list">
					<?php
					if ( ! empty( $current ) ) :
						foreach ( $current as $index => $rel ) :
							$nome = get_post_meta( $rel->participante_id, '_pt_event_nome', true );
							if ( ! $nome ) {
								$nome = get_the_title( $rel->participante_id );
							}
							?>
							<tr data-participante-id="<?php echo esc_attr( $rel->participante_id ); ?>">
								<td class="pt-event-col-order">
									<span class="dashicons dashicons-menu pt-event-sortable-handle"></span>
									<input type="hidden" name="pt_event_parts[<?php echo $index; ?>][id]" value="<?php echo esc_attr( $rel->participante_id ); ?>" />
									<input type="hidden" name="pt_event_parts[<?php echo $index; ?>][ordem]" value="<?php echo esc_attr( $rel->ordem ); ?>" class="pt-event-ordem" />
								</td>
								<td><?php echo esc_html( $nome ); ?></td>
								<td>
									<input type="text"
										name="pt_event_parts[<?php echo $index; ?>][cargo]"
										value="<?php echo esc_attr( $rel->cargo ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'Deixe em branco para usar o padrão', 'pt-event' ); ?>"
										class="regular-text" />
								</td>
								<td>
									<select name="pt_event_parts[<?php echo $index; ?>][papel]">
										<?php foreach ( $papeis as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $rel->papel, $val ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="pt-event-col-actions">
									<button type="button" class="button pt-event-remove-participante">&times;</button>
								</td>
							</tr>
						<?php
						endforeach;
					endif;
					?>
				</tbody>
			</table>
		</div>

		<script type="text/html" id="tmpl-pt-event-participante-row">
			<tr data-participante-id="{{data.id}}">
				<td class="pt-event-col-order">
					<span class="dashicons dashicons-menu pt-event-sortable-handle"></span>
					<input type="hidden" name="pt_event_parts[{{data.index}}][id]" value="{{data.id}}" />
					<input type="hidden" name="pt_event_parts[{{data.index}}][ordem]" value="{{data.index}}" class="pt-event-ordem" />
				</td>
				<td>{{data.nome}}</td>
				<td>
					<input type="text"
						name="pt_event_parts[{{data.index}}][cargo]"
						value="{{data.cargo}}"
						placeholder="<?php esc_attr_e( 'Deixe em branco para usar o padrão', 'pt-event' ); ?>"
						class="regular-text" />
				</td>
				<td>
					<select name="pt_event_parts[{{data.index}}][papel]">
						<?php foreach ( $papeis as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="pt-event-col-actions">
					<button type="button" class="button pt-event-remove-participante">&times;</button>
				</td>
			</tr>
		</script>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['pt_event_sessao_parts_nonce'] ) || ! wp_verify_nonce( $_POST['pt_event_sessao_parts_nonce'], 'pt_event_sessao_parts' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		Relationship::remove_all_by_sessao( $post_id );

		if ( ! empty( $_POST['pt_event_parts'] ) && is_array( $_POST['pt_event_parts'] ) ) {
			foreach ( $_POST['pt_event_parts'] as $index => $part ) {
				$participante_id = absint( $part['id'] );
				$papel           = sanitize_text_field( $part['papel'] );
				$cargo           = sanitize_text_field( $part['cargo'] ?? '' );
				$ordem           = absint( $part['ordem'] );

				if ( $participante_id > 0 ) {
					Relationship::add( $post_id, $participante_id, $papel, $ordem, $cargo );
				}
			}
		}
	}

	public function ajax_search() {
		check_ajax_referer( 'pt_event_admin', 'nonce' );

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$args = array(
			'post_type'      => 'pt_participante',
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			's'              => $term,
		);

		$query   = new \WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id   = get_the_ID();
				$nome = get_post_meta( $id, '_pt_event_nome', true );
				if ( ! $nome ) {
					$nome = get_the_title();
				}
				$cargo = get_post_meta( $id, '_pt_event_cargo', true );

				$results[] = array(
					'id'    => $id,
					'nome'  => $nome,
					'cargo' => $cargo,
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $results );
	}
}
