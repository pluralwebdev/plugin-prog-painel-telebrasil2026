<?php
namespace PTEvent\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom fields for the pt_cota_patrocinio taxonomy (width, height, cols, logo size, order).
 */
class Taxonomy_Cota {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'pt_cota_patrocinio_add_form_fields', array( $this, 'add_fields' ) );
		add_action( 'pt_cota_patrocinio_edit_form_fields', array( $this, 'edit_fields' ), 10 );
		add_action( 'created_pt_cota_patrocinio', array( $this, 'save_fields' ) );
		add_action( 'edited_pt_cota_patrocinio', array( $this, 'save_fields' ) );

		// Custom columns
		add_filter( 'manage_edit-pt_cota_patrocinio_columns', array( $this, 'columns' ) );
		add_filter( 'manage_pt_cota_patrocinio_custom_column', array( $this, 'column_content' ), 10, 3 );
	}

	/**
	 * Fields on "Add New" screen.
	 */
	public function add_fields() {
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Largura do card (px)', 'pt-event' ); ?></label>
			<input type="number" name="_pt_cota_width" value="196" min="50" max="1000" />
			<p class="description"><?php esc_html_e( 'Largura do card em pixels.', 'pt-event' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Altura do card (px)', 'pt-event' ); ?></label>
			<input type="number" name="_pt_cota_height" value="88" min="30" max="500" />
			<p class="description"><?php esc_html_e( 'Altura do card em pixels.', 'pt-event' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Colunas no grid', 'pt-event' ); ?></label>
			<input type="number" name="_pt_cota_cols" value="6" min="1" max="12" />
			<p class="description"><?php esc_html_e( 'Quantas colunas na seção deste tipo de cota.', 'pt-event' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Tamanho da logo (%)', 'pt-event' ); ?></label>
			<input type="number" name="_pt_cota_logo_pct" value="55" min="20" max="100" />
			<p class="description"><?php esc_html_e( 'Percentual do card que a logo ocupa (largura e altura máximas).', 'pt-event' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Ordem de exibição', 'pt-event' ); ?></label>
			<input type="number" name="_pt_cota_order" value="1" min="0" max="100" />
			<p class="description"><?php esc_html_e( 'Ordem da seção no shortcode (1 = primeiro).', 'pt-event' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Fields on "Edit" screen.
	 */
	public function edit_fields( $term ) {
		$width    = get_term_meta( $term->term_id, '_pt_cota_width', true ) ?: 196;
		$height   = get_term_meta( $term->term_id, '_pt_cota_height', true ) ?: 88;
		$cols     = get_term_meta( $term->term_id, '_pt_cota_cols', true ) ?: 6;
		$logo_pct = get_term_meta( $term->term_id, '_pt_cota_logo_pct', true ) ?: 55;
		$order    = get_term_meta( $term->term_id, '_pt_cota_order', true ) ?: 1;
		?>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Largura do card (px)', 'pt-event' ); ?></label></th>
			<td>
				<input type="number" name="_pt_cota_width" value="<?php echo esc_attr( $width ); ?>" min="50" max="1000" />
				<p class="description"><?php esc_html_e( 'Largura do card em pixels.', 'pt-event' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Altura do card (px)', 'pt-event' ); ?></label></th>
			<td>
				<input type="number" name="_pt_cota_height" value="<?php echo esc_attr( $height ); ?>" min="30" max="500" />
				<p class="description"><?php esc_html_e( 'Altura do card em pixels.', 'pt-event' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Colunas no grid', 'pt-event' ); ?></label></th>
			<td>
				<input type="number" name="_pt_cota_cols" value="<?php echo esc_attr( $cols ); ?>" min="1" max="12" />
				<p class="description"><?php esc_html_e( 'Quantas colunas na seção deste tipo de cota.', 'pt-event' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Tamanho da logo (%)', 'pt-event' ); ?></label></th>
			<td>
				<input type="number" name="_pt_cota_logo_pct" value="<?php echo esc_attr( $logo_pct ); ?>" min="20" max="100" />
				<p class="description"><?php esc_html_e( 'Percentual do card que a logo ocupa.', 'pt-event' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Ordem de exibição', 'pt-event' ); ?></label></th>
			<td>
				<input type="number" name="_pt_cota_order" value="<?php echo esc_attr( $order ); ?>" min="0" max="100" />
				<p class="description"><?php esc_html_e( 'Ordem da seção no shortcode (1 = primeiro).', 'pt-event' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta.
	 */
	public function save_fields( $term_id ) {
		$fields = array( '_pt_cota_width', '_pt_cota_height', '_pt_cota_cols', '_pt_cota_logo_pct', '_pt_cota_order' );
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_term_meta( $term_id, $field, absint( $_POST[ $field ] ) );
			}
		}
	}

	/**
	 * Custom columns.
	 */
	public function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $val ) {
			$new[ $key ] = $val;
			if ( 'name' === $key ) {
				$new['card_size'] = __( 'Card (LxA)', 'pt-event' );
				$new['grid_cols'] = __( 'Colunas', 'pt-event' );
				$new['cota_order'] = __( 'Ordem', 'pt-event' );
			}
		}
		unset( $new['description'], $new['slug'] );
		return $new;
	}

	public function column_content( $content, $column_name, $term_id ) {
		if ( 'card_size' === $column_name ) {
			$w = get_term_meta( $term_id, '_pt_cota_width', true ) ?: '—';
			$h = get_term_meta( $term_id, '_pt_cota_height', true ) ?: '—';
			return $w . ' × ' . $h . 'px';
		}
		if ( 'grid_cols' === $column_name ) {
			return get_term_meta( $term_id, '_pt_cota_cols', true ) ?: '—';
		}
		if ( 'cota_order' === $column_name ) {
			return get_term_meta( $term_id, '_pt_cota_order', true ) ?: '—';
		}
		return $content;
	}
}
