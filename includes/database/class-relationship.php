<?php
namespace PTEvent\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Relationship {

	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'evento_sessao_participantes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sessao_id bigint(20) unsigned NOT NULL,
			participante_id bigint(20) unsigned NOT NULL,
			papel varchar(100) NOT NULL DEFAULT '',
			ordem int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY sessao_id (sessao_id),
			KEY participante_id (participante_id),
			UNIQUE KEY sessao_participante (sessao_id, participante_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function add( $sessao_id, $participante_id, $papel = '', $ordem = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		return $wpdb->replace(
			$table,
			array(
				'sessao_id'       => absint( $sessao_id ),
				'participante_id' => absint( $participante_id ),
				'papel'           => sanitize_text_field( $papel ),
				'ordem'           => absint( $ordem ),
			),
			array( '%d', '%d', '%s', '%d' )
		);
	}

	public static function remove( $sessao_id, $participante_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		return $wpdb->delete(
			$table,
			array(
				'sessao_id'       => absint( $sessao_id ),
				'participante_id' => absint( $participante_id ),
			),
			array( '%d', '%d' )
		);
	}

	public static function remove_all_by_sessao( $sessao_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		return $wpdb->delete(
			$table,
			array( 'sessao_id' => absint( $sessao_id ) ),
			array( '%d' )
		);
	}

	public static function get_by_sessao( $sessao_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE sessao_id = %d ORDER BY ordem ASC",
				$sessao_id
			)
		);
	}

	public static function get_by_participante( $participante_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'evento_sessao_participantes';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE participante_id = %d ORDER BY ordem ASC",
				$participante_id
			)
		);
	}
}
