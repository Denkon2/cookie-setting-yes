<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

require_once CSY_DIR . 'includes/class-options.php';
require_once CSY_DIR . 'includes/class-service-catalog.php';
require_once CSY_DIR . 'includes/class-consent-log.php';
require_once CSY_DIR . 'includes/class-data-consent-log.php';
require_once CSY_DIR . 'includes/class-consent-manager.php';
require_once CSY_DIR . 'includes/class-data-consent.php';
require_once CSY_DIR . 'includes/class-script-blocker.php';
require_once CSY_DIR . 'includes/class-raw-script-blocker.php';
require_once CSY_DIR . 'includes/class-iframe-blocker.php';
require_once CSY_DIR . 'includes/class-google-consent-mode.php';
require_once CSY_DIR . 'includes/class-wp-consent-api.php';
require_once CSY_DIR . 'includes/class-policy-generator.php';
require_once CSY_DIR . 'includes/class-admin.php';
require_once CSY_DIR . 'includes/class-pro.php';

/** Coordinates the plugin services. */
final class Plugin {
	private static $instance;

	/** @var Options */
	public $options;

	/** @var Consent_Log */
	public $consent_log;

	/** @var Data_Consent_Log */
	public $data_consent_log;

	/** @var Service_Catalog */
	public $service_catalog;

	private function __construct() {
		$this->load_textdomain();

		$this->options     = new Options();
		$this->consent_log = new Consent_Log( $this->options );
		$this->data_consent_log = new Data_Consent_Log( $this->options );
		$this->service_catalog = new Service_Catalog();

		new Consent_Manager( $this->options, $this->consent_log );
		new Data_Consent( $this->options, $this->data_consent_log );
		new Script_Blocker( $this->options, $this->service_catalog );
		new Raw_Script_Blocker( $this->options, $this->service_catalog );
		new Iframe_Blocker( $this->options );
		new Google_Consent_Mode( $this->options );
		new WP_Consent_API( $this->options );
		new Policy_Generator( $this->options );
		if ( 'pro' === CSY_EDITION ) {
			new Pro( $this->options );
		}

		if ( is_admin() ) {
			new Admin( $this->options, $this->consent_log, $this->data_consent_log, $this->service_catalog );
		}

		add_action( 'admin_init', array( 'Cookie_Setting_Yes\\Activator', 'maybe_upgrade' ) );
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_textdomain() {
		$locale = determine_locale();
		$mofile = CSY_DIR . 'languages/cookie-setting-yes-' . $locale . '.mo';

		if ( is_readable( $mofile ) ) {
			load_textdomain( 'cookie-setting-yes', $mofile );
		}
	}
}
