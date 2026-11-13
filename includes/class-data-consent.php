<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Renders, validates and records explicit consent to personal data processing. */
final class Data_Consent {
	const FIELD_NAME   = 'csy_data_consent';
	const VISITOR_NAME = 'csy_data_consent_visitor';
	const WOOCOMMERCE_BLOCK_FIELD = 'cookie-setting-yes/data-consent';

	private $options;
	private $consent_log;

	public function __construct( Options $options, Data_Consent_Log $consent_log ) {
		$this->options     = $options;
		$this->consent_log = $consent_log;

		add_shortcode( 'cookie_setting_yes_data_consent', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wpcf7_init', array( $this, 'register_cf7_tag' ) );
		add_filter( 'wpcf7_validate_csy_consent', array( $this, 'validate_cf7' ), 10, 2 );
		add_action( 'wpcf7_before_send_mail', array( $this, 'record_cf7' ) );
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'render_woocommerce_field' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_woocommerce' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'record_woocommerce' ), 10, 2 );
		add_action( 'woocommerce_init', array( $this, 'register_woocommerce_block_field' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'record_woocommerce_block' ) );
		add_filter( 'render_block_woocommerce/checkout-additional-information-block', array( $this, 'render_woocommerce_block_privacy_note' ), 10, 2 );
	}

	public function enqueue_assets() {
		if ( is_admin() || ! $this->options->get( 'data_consent_enabled' ) ) {
			return;
		}

		wp_enqueue_style( 'cookie-setting-yes-data-consent', CSY_URL . 'assets/css/data-consent.css', array(), CSY_VERSION );
		wp_enqueue_script( 'cookie-setting-yes-data-consent', CSY_URL . 'assets/js/data-consent.js', array(), CSY_VERSION, true );
		wp_localize_script(
			'cookie-setting-yes-data-consent',
			'CookieSettingYesDataConsent',
			array( 'endpoint' => esc_url_raw( rest_url( 'cookie-setting-yes/v1/data-consent' ) ) )
		);
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'required' => 'true', 'label' => '' ), $atts, 'cookie_setting_yes_data_consent' );
		return $this->render_field( 'shortcode', array(), 'false' !== $atts['required'], sanitize_text_field( $atts['label'] ), true );
	}

	public function register_routes() {
		register_rest_route(
			'cookie-setting-yes/v1',
			'/data-consent',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'record_shortcode_consent' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'visitorId'    => array( 'required' => true, 'type' => 'string' ),
					'consentToken' => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);
	}

	public function record_shortcode_consent( \WP_REST_Request $request ) {
		if ( ! $this->options->get( 'data_consent_enabled' ) ) {
			return new \WP_Error( 'cookie_setting_yes_disabled', __( 'Consent disabled.', 'cookie-setting-yes' ), array( 'status' => 400 ) );
		}

		$visitor_id = sanitize_text_field( $request->get_param( 'visitorId' ) );
		$token      = sanitize_text_field( $request->get_param( 'consentToken' ) );
		$expected   = wp_hash( $visitor_id . '|shortcode|data-consent' );

		if ( ! hash_equals( $expected, $token ) ) {
			return new \WP_Error( 'cookie_setting_yes_invalid_consent', __( 'Failed to confirm consent.', 'cookie-setting-yes' ), array( 'status' => 403 ) );
		}

		$rate_limit_key = 'csy_data_consent_' . md5( $visitor_id );
		if ( get_transient( $rate_limit_key ) ) {
			return new \WP_REST_Response( array( 'saved' => true ), 200 );
		}

		$this->consent_log->record( $visitor_id, 'shortcode' );
		set_transient( $rate_limit_key, 1, 15 * MINUTE_IN_SECONDS );
		return new \WP_REST_Response( array( 'saved' => true ), 201 );
	}

	public function register_cf7_tag() {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_cf7' ) || ! function_exists( 'wpcf7_add_form_tag' ) ) {
			return;
		}

		wpcf7_add_form_tag( 'csy_consent', array( $this, 'render_cf7_tag' ), array( 'name-attr' => false ) );
	}

	public function render_cf7_tag( $tag ) {
		return $this->render_field( 'contact_form_7', array(), true, '', false );
	}

	public function validate_cf7( $result, $tag ) {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_cf7' ) ) {
			return $result;
		}

		// Contact Form 7 verifies its own submission nonce before validation hooks run.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST[ self::FIELD_NAME ] ) ) {
			$result->invalidate( $tag, __( 'Consent to the processing of personal data is required.', 'cookie-setting-yes' ) );
		}

		return $result;
	}

	public function record_cf7( $contact_form ) {
		// Contact Form 7 verifies its own submission nonce before this hook runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_cf7' ) || empty( $_POST[ self::FIELD_NAME ] ) ) {
			return;
		}

		$submission = class_exists( 'WPCF7_Submission' ) ? \WPCF7_Submission::get_instance() : null;
		$posted     = $submission ? (array) $submission->get_posted_data() : array();
		$visitor_id = sanitize_text_field( $posted[ self::VISITOR_NAME ] ?? '' );
		$this->consent_log->record(
			$visitor_id,
			'contact_form_7',
			array(
				'id'    => method_exists( $contact_form, 'id' ) ? $contact_form->id() : 0,
				'label' => method_exists( $contact_form, 'title' ) ? $contact_form->title() : '',
				'hash'  => $submission && method_exists( $submission, 'get_posted_data_hash' ) ? $submission->get_posted_data_hash() : '',
			)
		);
	}

	public function render_woocommerce_field() {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) ) {
			return;
		}

		echo $this->render_field( 'woocommerce', array(), true, '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated markup escapes every dynamic value in render_field().
	}

	public function validate_woocommerce( $data, $errors ) {
		// WooCommerce verifies the checkout nonce before validation hooks run.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) || ! empty( $_POST[ self::FIELD_NAME ] ) ) {
			return;
		}

		$errors->add( 'csy_data_consent', __( 'Confirm your consent to the processing of personal data.', 'cookie-setting-yes' ) );
	}

	public function record_woocommerce( $order, $data ) {
		// WooCommerce verifies the checkout nonce before order creation hooks run.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) || empty( $_POST[ self::FIELD_NAME ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce before this hook.
		$visitor_id = isset( $_POST[ self::VISITOR_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::VISITOR_NAME ] ) ) : '';
		$this->record_woocommerce_order( $order, $visitor_id, 'woocommerce' );
	}

	public function register_woocommerce_block_field() {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) || ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'            => self::WOOCOMMERCE_BLOCK_FIELD,
				'label'         => wp_strip_all_tags( $this->consent_text() ),
				'location'      => 'order',
				'type'          => 'checkbox',
				'required'      => true,
				'error_message' => __( 'Confirm your consent to the processing of personal data.', 'cookie-setting-yes' ),
				'show_in_order_confirmation' => false,
				'sanitize_callback' => array( $this, 'sanitize_woocommerce_block_consent' ),
			)
		);
	}

	public function sanitize_woocommerce_block_consent( $value ) {
		return in_array( $value, array( true, 1, '1' ), true ) ? '1' : '';
	}

	public function record_woocommerce_block( $order ) {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) || ! $this->woocommerce_block_consent_is_accepted( $order ) || $order->get_meta( '_csy_data_consent_blocks_recorded', true ) ) {
			return;
		}

		$visitor_id = function_exists( 'WC' ) && WC()->session ? WC()->session->get_customer_id() : '';
		$this->record_woocommerce_order( $order, $visitor_id, 'woocommerce_blocks' );
		$order->update_meta_data( '_csy_data_consent_blocks_recorded', 1 );
		$order->save();
	}

	public function render_woocommerce_block_privacy_note( $content, $block ) {
		if ( ! $this->options->get( 'data_consent_enabled' ) || ! $this->options->get( 'data_consent_woocommerce' ) || ! $this->options->get( 'privacy_url' ) ) {
			return $content;
		}

		return $content . '<p class="csy-woocommerce-block-consent-note"><a href="' . esc_url( $this->options->get( 'privacy_url' ) ) . '">' . esc_html__( 'Privacy Policy', 'cookie-setting-yes' ) . '</a></p>';
	}

	private function record_woocommerce_order( $order, $visitor_id, $source ) {
		$this->consent_log->record(
			$visitor_id,
			$source,
			array(
				'id'    => $order->get_id(),
				'label' => __( 'WooCommerce Order', 'cookie-setting-yes' ),
				'hash'  => hash_hmac( 'sha256', $order->get_id() . '|' . $order->get_order_key(), wp_salt( 'cookie_setting_yes_order' ) ),
			)
		);
		$order->update_meta_data( '_csy_data_consent_version', $this->options->get( 'data_consent_version' ) );
		$order->update_meta_data( '_csy_data_consent_hash', $this->options->data_consent_hash() );
	}

	private function woocommerce_block_consent_is_accepted( $order ) {
		$stored_value = $order->get_meta( '_wc_other/' . self::WOOCOMMERCE_BLOCK_FIELD, true );
		if ( '1' === $stored_value || true === $stored_value ) {
			return true;
		}

		$service_class = '\\Automattic\\WooCommerce\\Blocks\\Domain\\Services\\CheckoutFields';
		$package_class = '\\Automattic\\WooCommerce\\Blocks\\Package';

		if ( class_exists( $service_class ) && class_exists( $package_class ) ) {
			try {
				$checkout_fields = $package_class::container()->get( $service_class );
				return (bool) $checkout_fields->get_field_from_object( self::WOOCOMMERCE_BLOCK_FIELD, $order, 'other' );
			} catch ( \Throwable $exception ) {
				return false;
			}
		}

		return false;
	}

	private function render_field( $source, array $context, $required, $label, $log_on_change ) {
		if ( ! $this->options->get( 'data_consent_enabled' ) ) {
			return '';
		}

		$label         = $label ? esc_html( $label ) : $this->consent_text();
		$input_id      = 'csy-data-consent-' . wp_generate_uuid4();
		$visitor_id    = wp_generate_uuid4();
		$log_attribute = $log_on_change ? ' data-csy-log-on-change="true" data-csy-consent-token="' . esc_attr( wp_hash( $visitor_id . '|shortcode|data-consent' ) ) . '"' : '';
		return sprintf(
			'<span class="csy-data-consent" data-csy-data-consent data-csy-source="%1$s"%2$s><input type="hidden" name="%3$s" value="%4$s" data-csy-data-consent-visitor><label for="%5$s"><input id="%5$s" type="checkbox" name="%6$s" value="1"%7$s data-csy-data-consent-checkbox><span>%8$s</span></label></span>',
			esc_attr( $source ),
			$log_attribute,
			esc_attr( self::VISITOR_NAME ),
			esc_attr( $visitor_id ),
			esc_attr( $input_id ),
			esc_attr( self::FIELD_NAME ),
			$required ? ' required' : '',
			$label
		);
	}

	private function consent_text() {
		$text = esc_html( $this->options->get( 'data_consent_text' ) );
		$policy_label = esc_html__( 'Privacy Policy', 'cookie-setting-yes' );
		$policy_url   = $this->options->get( 'privacy_url' );
		$policy       = $policy_url ? '<a href="' . esc_url( $policy_url ) . '">' . $policy_label . '</a>' : $policy_label;

		return str_replace( '%privacy_policy%', $policy, $text );
	}
}
