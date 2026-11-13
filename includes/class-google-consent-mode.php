<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Provides the initial Google Consent Mode v2 state before Google tags run. */
final class Google_Consent_Mode {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
		add_action( 'wp_head', array( $this, 'print_default_consent' ), -100 );
	}

	public function print_default_consent() {
		if ( ! $this->options->get( 'enabled' ) || ! $this->options->get( 'google_consent_mode' ) || is_admin() ) {
			return;
		}

		$categories = $this->current_categories();
		$consent = $this->google_values( $categories );
		if ( empty( $this->options->consent_cookie() ) ) {
			$consent['wait_for_update'] = absint( $this->options->get( 'google_wait_for_update', 500 ) );
		}

		$script = 'window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){window.dataLayer.push(arguments);};window.gtag("consent","default",' . wp_json_encode( $consent ) . ');';
		wp_print_inline_script_tag( $script, array( 'id' => 'cookie-setting-yes-google-consent' ) );
	}

	private function current_categories() {
		$data = $this->options->consent_cookie();
		if ( ! is_array( $data ) || empty( $data['version'] ) || ! hash_equals( (string) $this->options->get( 'consent_version' ), (string) $data['version'] ) ) {
			return array();
		}

		return ! empty( $data['categories'] ) && is_array( $data['categories'] ) ? $data['categories'] : array();
	}

	private function google_values( array $categories ) {
		return array(
			'analytics_storage'      => ! empty( $categories['analytics'] ) ? 'granted' : 'denied',
			'ad_storage'             => ! empty( $categories['marketing'] ) ? 'granted' : 'denied',
			'ad_user_data'           => ! empty( $categories['marketing'] ) ? 'granted' : 'denied',
			'ad_personalization'     => ! empty( $categories['marketing'] ) ? 'granted' : 'denied',
			'functionality_storage'  => ! empty( $categories['functional'] ) ? 'granted' : 'denied',
			'personalization_storage' => ! empty( $categories['functional'] ) ? 'granted' : 'denied',
			'security_storage'       => 'granted',
		);
	}
}
