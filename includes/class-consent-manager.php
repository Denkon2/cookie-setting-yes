<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

final class Consent_Manager {
	private $options;
	private $consent_log;

	public function __construct( Options $options, Consent_Log $consent_log ) {
		$this->options     = $options;
		$this->consent_log = $consent_log;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// Footer scripts are printed at priority 20; render the dialog before them.
		add_action( 'wp_footer', array( $this, 'render_interface' ), 5 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function enqueue_assets() {
		if ( ! $this->options->get( 'enabled' ) || is_admin() ) {
			return;
		}

		wp_enqueue_style( 'cookie-setting-yes', CSY_URL . 'assets/css/frontend.css', array(), CSY_VERSION );
		$dependencies = wp_script_is( 'wp-consent-api', 'registered' ) ? array( 'wp-consent-api' ) : array();
		wp_enqueue_script( 'cookie-setting-yes', CSY_URL . 'assets/js/frontend.js', $dependencies, CSY_VERSION, true );
		wp_localize_script(
			'cookie-setting-yes',
			'CookieSettingYes',
			array(
				'endpoint'       => esc_url_raw( rest_url( 'cookie-setting-yes/v1/consent' ) ),
				'consentVersion' => (string) $this->options->get( 'consent_version' ),
				'categories'     => $this->options->categories(),
				'googleConsentMode' => (bool) $this->options->get( 'google_consent_mode' ),
				'labels'         => array(
					'open' => __( 'Cookie settings', 'cookie-setting-yes' ),
				),
			)
		);
	}

	public function render_interface() {
		if ( ! $this->options->get( 'enabled' ) || is_admin() ) {
			return;
		}

		$options    = $this->options->all();
		$categories = $this->options->categories();
		include CSY_DIR . 'templates/consent-interface.php';
	}

	public function register_routes() {
		register_rest_route(
			'cookie-setting-yes/v1',
			'/consent',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_consent' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'visitorId' => array( 'required' => true, 'type' => 'string' ),
					'categories' => array( 'required' => true, 'type' => 'object' ),
					'action' => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);
	}

	public function save_consent( \WP_REST_Request $request ) {
		$visitor_id = $request->get_param( 'visitorId' );
		$categories = (array) $request->get_param( 'categories' );
		$action     = $request->get_param( 'action' );

		if ( ! $this->consent_log->record( $visitor_id, $categories, $action ) ) {
			return new \WP_Error( 'cookie_setting_yes_invalid_consent', __( 'Failed to save consent.', 'cookie-setting-yes' ), array( 'status' => 400 ) );
		}

		return new \WP_REST_Response( array( 'saved' => true ), 201 );
	}
}
