<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

final class Consent_Log {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'csy_consent_log';
	}

	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			visitor_id char(64) NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ip_value varchar(128) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			consent_version varchar(64) NOT NULL,
			consent_hash char(64) NOT NULL DEFAULT '',
			action varchar(20) NOT NULL,
			categories longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY created_at (created_at)
		) {$charset};";

		dbDelta( $sql );
	}

	public function record( $visitor_id, array $categories, $action ) {
		global $wpdb;

		$visitor_id = sanitize_text_field( $visitor_id );
		if ( strlen( $visitor_id ) < 16 || strlen( $visitor_id ) > 128 ) {
			return false;
		}

		$clean_categories = array();
		foreach ( $this->options->categories() as $slug => $category ) {
			$clean_categories[ $slug ] = ! empty( $category['required'] ) || ! empty( $categories[ $slug ] );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( 'hash' === $this->options->get( 'log_ip_mode' ) && $ip ) {
			$ip = hash_hmac( 'sha256', $ip, wp_salt( 'cookie_setting_yes_ip' ) );
		} elseif ( 'none' === $this->options->get( 'log_ip_mode' ) ) {
			$ip = '';
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- A dedicated append-only audit table cannot use the options API.
		return $wpdb->insert(
			self::table_name(),
			array(
				'created_at'      => current_time( 'mysql', true ),
				'visitor_id'      => hash_hmac( 'sha256', $visitor_id, wp_salt( 'cookie_setting_yes_visitor' ) ),
				'user_id'         => get_current_user_id(),
				'ip_value'        => $ip,
				'user_agent'      => $user_agent,
				'consent_version' => sanitize_text_field( $this->options->get( 'consent_version' ) ),
				'consent_hash'    => $this->options->consent_hash(),
				'action'          => in_array( $action, array( 'accept_all', 'reject_all', 'save', 'withdraw' ), true ) ? $action : 'save',
				'categories'      => wp_json_encode( $clean_categories ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function get_recent( $limit = 100 ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from the trusted WordPress prefix; audit records must be current.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", absint( $limit ) ) );
	}

	public function count() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Trusted prefixed table name; the live audit count must not be cached.
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table_name() );
	}

	/** Clear the proof log after an explicit administrator action. */
	public function clear() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Explicit administrator action against the trusted audit table.
		return false !== $wpdb->query( 'DELETE FROM ' . self::table_name() );
	}
}
