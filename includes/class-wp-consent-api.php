<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Declares opt-in behavior for the optional WP Consent API plugin. */
final class WP_Consent_API {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
		add_filter( 'wp_get_consent_type', array( $this, 'get_consent_type' ) );
		add_filter( 'wp_consent_api_registered_' . plugin_basename( CSY_FILE ), '__return_true' );
	}

	public function get_consent_type( $type ) {
		return $this->options->get( 'enabled' ) ? 'optin' : $type;
	}
}
