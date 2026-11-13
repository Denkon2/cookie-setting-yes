<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Premium-only runtime features. The Free build never instantiates this class. */
final class Pro {
	const CLEANUP_HOOK = 'csy_pro_cleanup_logs';

	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
		add_action( 'init', array( $this, 'schedule_cleanup' ), 20 );
		add_action( self::CLEANUP_HOOK, array( $this, 'cleanup_logs' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_styles' ), 30 );
	}

	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	public function cleanup_logs() {
		$days = absint( $this->options->get( 'pro_log_retention_days' ) );
		if ( $days < 1 ) {
			return;
		}

		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( DAY_IN_SECONDS * min( 3650, $days ) ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Scheduled retention cleanup uses trusted prefixed audit table names.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Consent_Log::table_name() . ' WHERE created_at < %s', $cutoff ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Scheduled retention cleanup uses trusted prefixed audit table names.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Data_Consent_Log::table_name() . ' WHERE created_at < %s', $cutoff ) );
	}

	public function enqueue_custom_styles() {
		if ( is_admin() || ! $this->options->get( 'enabled' ) ) {
			return;
		}

		$background = sanitize_hex_color( $this->options->get( 'pro_banner_background' ) ) ?: '#ffffff';
		$text       = sanitize_hex_color( $this->options->get( 'pro_banner_text_color' ) ) ?: '#142028';
		$accent     = sanitize_hex_color( $this->options->get( 'pro_accent_color' ) ) ?: '#126782';
		$button_text = sanitize_hex_color( $this->options->get( 'pro_button_text_color' ) ) ?: '#ffffff';
		$radius     = min( 32, max( 0, absint( $this->options->get( 'pro_border_radius' ) ) ) );
		$custom_css = $this->sanitize_css( $this->options->get( 'pro_custom_css' ) );
		$css        = '#csy-banner{color:' . $text . ';background:' . $background . ';border-radius:' . $radius . 'px}#csy-banner a{color:' . $accent . '}#csy-banner .csy-button,#csy-preferences .csy-button{color:' . $button_text . ';background:' . $accent . ';border-color:' . $accent . '}' . $custom_css;

		wp_add_inline_style( 'cookie-setting-yes', $css );
	}

	private function sanitize_css( $css ) {
		$css = substr( (string) $css, 0, 20000 );
		return str_ireplace( array( '<style', '</style', 'javascript:', 'expression(' ), '', $css );
	}
}
