<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

require_once CSY_DIR . 'includes/class-options.php';
require_once CSY_DIR . 'includes/class-consent-log.php';
require_once CSY_DIR . 'includes/class-data-consent-log.php';

final class Activator {
	const DB_VERSION = '3';

	public static function activate() {
		$options = new Options();
		if ( false === get_option( Options::OPTION_KEY, false ) ) {
			add_option( Options::OPTION_KEY, $options->defaults(), '', false );
		}

		Consent_Log::create_table();
		Data_Consent_Log::create_table();
		update_option( 'csy_db_version', self::DB_VERSION, false );
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( self::DB_VERSION === get_option( 'csy_db_version' ) ) {
			return;
		}

		Consent_Log::create_table();
		Data_Consent_Log::create_table();
		update_option( 'csy_db_version', self::DB_VERSION, false );
	}
}
