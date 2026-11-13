<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

final class Policy_Generator {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
		add_shortcode( 'cookie_setting_yes_preferences', array( $this, 'preferences_shortcode' ) );
		add_shortcode( 'cookie_setting_yes_cookie_policy', array( $this, 'cookie_policy_shortcode' ) );
		add_shortcode( 'cookie_setting_yes_privacy_policy', array( $this, 'privacy_policy_shortcode' ) );
	}

	public function preferences_shortcode() {
		return '<button class="csy-open-preferences" type="button">' . esc_html__( 'Configure cookies', 'cookie-setting-yes' ) . '</button>';
	}

	public function cookie_policy_shortcode() {
		$rows = '';
		foreach ( $this->options->categories() as $slug => $category ) {
			$rows .= '<tr><td>' . esc_html( $category['label'] ) . '</td><td>' . esc_html( $category['description'] ) . '</td></tr>';
		}

		return '<div class="csy-policy"><h2>' . esc_html__( 'Cookie Policy', 'cookie-setting-yes' ) . '</h2><p>' . esc_html__( 'The site uses cookies and similar technologies. You can change your choices at any time through your cookie settings.', 'cookie-setting-yes' ) . '</p><table><thead><tr><th>' . esc_html__( 'Category', 'cookie-setting-yes' ) . '</th><th>' . esc_html__( 'Purpose', 'cookie-setting-yes' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
	}

	public function privacy_policy_shortcode() {
		$options  = $this->options->all();
		$operator = $options['operator_name'] ? $options['operator_name'] : get_bloginfo( 'name' );
		$contact  = $options['operator_contact'] ? $options['operator_contact'] : get_option( 'admin_email' );

		/* translators: 1: personal data operator name, 2: operator contact details. */
		$operator_details = sprintf( esc_html__( 'Personal data operator: %1$s. Contact for data processing requests: %2$s.', 'cookie-setting-yes' ), esc_html( $operator ), esc_html( $contact ) );

		return '<div class="csy-policy csy-policy--privacy">'
			. '<h2>' . esc_html__( 'Personal data processing policy', 'cookie-setting-yes' ) . '</h2>'
			. '<h3>' . esc_html__( '1. Operator and contacts', 'cookie-setting-yes' ) . '</h3>'
			. '<p>' . $operator_details . '</p>'
			. '<h3>' . esc_html__( '2. Purposes of processing', 'cookie-setting-yes' ) . '</h3><p>' . nl2br( esc_html( $options['personal_data_purposes'] ) ) . '</p>'
			. '<h3>' . esc_html__( '3. Composition of processed data', 'cookie-setting-yes' ) . '</h3><p>' . nl2br( esc_html( $options['personal_data_categories'] ) ) . '</p>'
			. '<h3>' . esc_html__( '4. Processing period and rights of the subject', 'cookie-setting-yes' ) . '</h3><p>' . nl2br( esc_html( $options['personal_data_retention'] ) ) . '</p>'
			. '<p>' . esc_html__( 'Data subjects may contact the operator to request clarification, blocking, or deletion of their data where provided by law.', 'cookie-setting-yes' ) . '</p>'
			. '</div>';
	}
}
