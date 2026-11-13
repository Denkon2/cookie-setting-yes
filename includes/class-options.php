<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

final class Options {
	const OPTION_KEY = 'csy_options';

	public function defaults() {
		return array(
			'enabled'              => true,
			'law_mode'             => 'ru_152fz',
			'consent_version'      => '1.0',
			'banner_title'         => __( 'We use cookies', 'cookie-setting-yes' ),
			'banner_text'          => __( 'We use cookies for site operation, analytics and service improvement. You can accept all cookies or select categories.', 'cookie-setting-yes' ),
			'privacy_url'          => '',
			'operator_name'        => '',
			'operator_contact'     => '',
			'personal_data_purposes' => __( 'Processing requests from site visitors, preparing and executing contracts, providing feedback.', 'cookie-setting-yes' ),
			'personal_data_categories' => __( 'Name, phone number, email address and other data that the visitor voluntarily provides in the site forms.', 'cookie-setting-yes' ),
			'personal_data_retention' => __( 'Until the purposes of processing are achieved or consent is withdrawn, unless a longer period is required by the legislation of the Russian Federation.', 'cookie-setting-yes' ),
			'data_consent_enabled' => true,
			'data_consent_version' => '1.0',
			'data_consent_text'    => __( 'I consent to the processing of personal data in accordance with %privacy_policy%.', 'cookie-setting-yes' ),
			'data_consent_cf7'     => true,
			'data_consent_woocommerce' => true,
			'cookie_policy_url'    => '',
			'position'             => 'bottom',
			'banner_horizontal'   => 'center',
			'banner_vertical'     => 'bottom',
			'selected_services'    => array(),
			'script_handles'       => array(),
			'google_consent_mode'  => false,
			'google_wait_for_update' => 500,
			'log_ip_mode'          => 'hash',
			'remove_on_uninstall'  => false,
			'manual_checks'        => array(),
			'last_verified_at'     => '',
			'verification_context_hash' => '',
			'pro_banner_background' => '#ffffff',
			'pro_banner_text_color' => '#142028',
			'pro_accent_color'      => '#126782',
			'pro_button_text_color' => '#ffffff',
			'pro_border_radius'     => 8,
			'pro_custom_css'        => '',
			'pro_log_retention_days' => 0,
			'pro_button_reject'     => __( 'Reject optional', 'cookie-setting-yes' ),
			'pro_button_customize'  => __( 'Customize', 'cookie-setting-yes' ),
			'pro_button_accept'     => __( 'Accept all', 'cookie-setting-yes' ),
			'pro_modal_title'       => __( 'Cookie settings', 'cookie-setting-yes' ),
		);
	}

	public function all() {
		$stored  = get_option( self::OPTION_KEY, array() );
		$options = wp_parse_args( is_array( $stored ) ? $stored : array(), $this->defaults() );
		if ( is_array( $stored ) && ! array_key_exists( 'banner_vertical', $stored ) && array_key_exists( 'position', $stored ) ) {
			$options['banner_vertical'] = 'top' === $stored['position'] ? 'top' : 'bottom';
		}
		return $options;
	}

	public function get( $key, $default = null ) {
		$options = $this->all();
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	public function update( array $options ) {
		$stored  = get_option( self::OPTION_KEY, array() );
		$current = wp_parse_args( is_array( $stored ) ? $stored : array(), $this->defaults() );
		update_option( self::OPTION_KEY, wp_parse_args( $options, $current ), false );
	}

	public function categories() {
		$categories = array(
			'necessary' => array(
				'label'       => __( 'Necessary', 'cookie-setting-yes' ),
				'description' => __( 'Required for basic site operation and always enabled.', 'cookie-setting-yes' ),
				'required'    => true,
			),
			'functional' => array(
				'label'       => __( 'Functional', 'cookie-setting-yes' ),
				'description' => __( 'Remember preferences and make the site easier to use.', 'cookie-setting-yes' ),
				'required'    => false,
			),
			'analytics' => array(
				'label'       => __( 'Analytics', 'cookie-setting-yes' ),
				'description' => __( 'Helps understand how visitors use the site.', 'cookie-setting-yes' ),
				'required'    => false,
			),
			'marketing' => array(
				'label'       => __( 'Marketing', 'cookie-setting-yes' ),
				'description' => __( 'Used to personalize advertising and measure campaigns.', 'cookie-setting-yes' ),
				'required'    => false,
			),
			'external' => array(
				'label'       => __( 'External content', 'cookie-setting-yes' ),
				'description' => __( 'Needed for maps, videos, widgets and other materials from third-party services.', 'cookie-setting-yes' ),
				'required'    => false,
			),
		);

		return apply_filters( 'cookie_setting_yes_categories', $categories );
	}

	/** Return the validated consent cookie payload. */
	public function consent_cookie() {
		if ( empty( $_COOKIE['csy_consent'] ) ) {
			return array();
		}

		$raw     = sanitize_text_field( wp_unslash( $_COOKIE['csy_consent'] ) );
		$consent = json_decode( rawurldecode( $raw ), true );

		return is_array( $consent ) ? $consent : array();
	}

	/** A stable proof of the visitor-facing terms for the current consent version. */
	public function consent_hash() {
		$options = $this->all();
		$terms = array(
			'law_mode'          => $options['law_mode'],
			'consent_version'   => $options['consent_version'],
			'banner_title'      => $options['banner_title'],
			'banner_text'       => $options['banner_text'],
			'privacy_url'       => $options['privacy_url'],
			'cookie_policy_url' => $options['cookie_policy_url'],
			'categories'        => $this->categories(),
		);

		return hash( 'sha256', wp_json_encode( $terms ) );
	}

	/** A stable proof of the terms displayed next to personal data form consent. */
	public function data_consent_hash() {
		$options = $this->all();
		$terms = array(
			'law_mode'             => $options['law_mode'],
			'data_consent_version' => $options['data_consent_version'],
			'data_consent_text'    => $options['data_consent_text'],
			'privacy_url'          => $options['privacy_url'],
		);

		return hash( 'sha256', wp_json_encode( $terms ) );
	}
}
