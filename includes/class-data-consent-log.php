<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Stores proof of personal data consent separately from cookie preferences. */
final class Data_Consent_Log {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'csy_data_consent_log';
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
			source varchar(32) NOT NULL,
			context_id bigint(20) unsigned NOT NULL DEFAULT 0,
			context_label varchar(255) NOT NULL DEFAULT '',
			context_hash char(64) NOT NULL DEFAULT '',
			consent_version varchar(64) NOT NULL,
			consent_hash char(64) NOT NULL,
			policy_url text NOT NULL,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY source (source),
			KEY created_at (created_at)
		) {$charset};";

		dbDelta( $sql );
	}

	public function record( $visitor_id, $source, array $context = array() ) {
		global $wpdb;

		$visitor_id = sanitize_text_field( $visitor_id );
		if ( strlen( $visitor_id ) < 16 || strlen( $visitor_id ) > 128 ) {
			$visitor_id = wp_generate_uuid4();
		}

		$allowed_sources = array( 'shortcode', 'contact_form_7', 'woocommerce', 'woocommerce_blocks' );
		$source          = in_array( $source, $allowed_sources, true ) ? $source : 'shortcode';
		$ip              = $this->ip_value();
		$user_agent      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- A dedicated append-only audit table cannot use the options API.
		return $wpdb->insert(
			self::table_name(),
			array(
				'created_at'      => current_time( 'mysql', true ),
				'visitor_id'      => hash_hmac( 'sha256', $visitor_id, wp_salt( 'cookie_setting_yes_data_visitor' ) ),
				'user_id'         => get_current_user_id(),
				'ip_value'        => $ip,
				'user_agent'      => $user_agent,
				'source'          => $source,
				'context_id'      => absint( $context['id'] ?? 0 ),
				'context_label'   => substr( sanitize_text_field( $context['label'] ?? '' ), 0, 255 ),
				'context_hash'    => substr( sanitize_text_field( $context['hash'] ?? '' ), 0, 64 ),
				'consent_version' => sanitize_text_field( $this->options->get( 'data_consent_version' ) ),
				'consent_hash'    => $this->options->data_consent_hash(),
				'policy_url'      => esc_url_raw( $this->options->get( 'privacy_url' ) ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function get_recent( $limit = 100 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Trusted prefixed table name; audit records must be current.
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' ORDER BY id DESC LIMIT %d', absint( $limit ) ) );
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

	private function ip_value() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( 'hash' === $this->options->get( 'log_ip_mode' ) && $ip ) {
			return hash_hmac( 'sha256', $ip, wp_salt( 'cookie_setting_yes_data_ip' ) );
		}

		return 'plain' === $this->options->get( 'log_ip_mode' ) ? $ip : '';
	}
}
