<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

final class Admin {
	private $options;
	private $consent_log;
	private $data_consent_log;
	private $service_catalog;

	public function __construct( Options $options, Consent_Log $consent_log, Data_Consent_Log $data_consent_log, Service_Catalog $service_catalog ) {
		$this->options          = $options;
		$this->consent_log      = $consent_log;
		$this->data_consent_log = $data_consent_log;
		$this->service_catalog  = $service_catalog;

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_csy_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_csy_export_log', array( $this, 'export_log' ) );
		add_action( 'admin_post_csy_export_data_consent_log', array( $this, 'export_data_consent_log' ) );
		add_action( 'admin_post_csy_clear_log', array( $this, 'clear_log' ) );
		add_action( 'admin_post_csy_clear_data_consent_log', array( $this, 'clear_data_consent_log' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'Cookie Setting Yes', 'cookie-setting-yes' ),
			__( 'Cookie Setting Yes', 'cookie-setting-yes' ),
			'manage_options',
			'cookie-setting-yes',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_cookie-setting-yes' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'cookie-setting-yes-admin', CSY_URL . 'assets/css/admin.css', array(), CSY_VERSION );
		wp_enqueue_script( 'cookie-setting-yes-admin', CSY_URL . 'assets/js/admin.js', array(), CSY_VERSION, true );
		wp_localize_script(
			'cookie-setting-yes-admin',
			'CookieSettingYesAdmin',
			array(
				'i18n' => array(
					'expandSection'   => __( 'Expand section', 'cookie-setting-yes' ),
					'collapseSection' => __( 'Collapse section', 'cookie-setting-yes' ),
					'countSeparator'  => __( 'of', 'cookie-setting-yes' ),
					'checksChanged'   => __( 'The checks have changed. Click "Save checks".', 'cookie-setting-yes' ),
				),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options                = $this->options->all();
		$categories             = $this->options->categories();
		$services               = $this->service_catalog->all();
		$logs                   = $this->consent_log->get_recent();
		$log_count              = $this->consent_log->count();
		$data_consent_logs      = $this->data_consent_log->get_recent();
		$data_consent_log_count = $this->data_consent_log->count();
		$verification           = $this->verification_state( $options );
		include CSY_DIR . 'templates/admin-page.php';
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'cookie-setting-yes' ) );
		}
		check_admin_referer( 'csy_save_settings' );

		// The array is sanitized field-by-field below because it contains URLs, multiline text, keys, and CSS.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input            = isset( $_POST['csy'] ) ? (array) wp_unslash( $_POST['csy'] ) : array();
		$previous_options = $this->options->all();
		$script_handles = array();
		$lines = preg_split( '/\r\n|\r|\n/', isset( $input['script_handles'] ) ? $input['script_handles'] : '' );
		foreach ( $lines as $line ) {
			$pair = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( 2 === count( $pair ) && isset( $this->options->categories()[ sanitize_key( $pair[1] ) ] ) ) {
				$script_handles[ sanitize_key( $pair[0] ) ] = sanitize_key( $pair[1] );
			}
		}
		$selected_services = array();
		foreach ( (array) ( $input['selected_services'] ?? array() ) as $service_id ) {
			$service_id = sanitize_key( $service_id );
			if ( isset( $this->service_catalog->all()[ $service_id ] ) ) {
				$selected_services[] = $service_id;
			}
		}

		$this->options->update(
			array(
				'enabled'             => ! empty( $input['enabled'] ),
				'law_mode'            => in_array( $input['law_mode'] ?? '', array( 'ru_152fz', 'gdpr', 'ccpa', 'custom' ), true ) ? $input['law_mode'] : 'ru_152fz',
				'consent_version'     => substr( sanitize_text_field( $input['consent_version'] ?? '1.0' ), 0, 64 ),
				'banner_title'        => sanitize_text_field( $input['banner_title'] ?? '' ),
				'banner_text'         => sanitize_textarea_field( $input['banner_text'] ?? '' ),
				'privacy_url'         => esc_url_raw( $input['privacy_url'] ?? '' ),
				'operator_name'       => sanitize_text_field( $input['operator_name'] ?? '' ),
				'operator_contact'    => sanitize_text_field( $input['operator_contact'] ?? '' ),
				'personal_data_purposes' => sanitize_textarea_field( $input['personal_data_purposes'] ?? '' ),
				'personal_data_categories' => sanitize_textarea_field( $input['personal_data_categories'] ?? '' ),
				'personal_data_retention' => sanitize_textarea_field( $input['personal_data_retention'] ?? '' ),
				'cookie_policy_url'   => esc_url_raw( $input['cookie_policy_url'] ?? '' ),
				'position'            => in_array( $input['banner_vertical'] ?? '', array( 'bottom', 'top', 'center' ), true ) ? $input['banner_vertical'] : 'bottom',
				'banner_horizontal'  => in_array( $input['banner_horizontal'] ?? '', array( 'left', 'center', 'right' ), true ) ? $input['banner_horizontal'] : 'center',
				'banner_vertical'    => in_array( $input['banner_vertical'] ?? '', array( 'bottom', 'top', 'center' ), true ) ? $input['banner_vertical'] : 'bottom',
				'selected_services'   => array_values( array_unique( $selected_services ) ),
				'script_handles'      => $script_handles,
				'google_consent_mode' => ! empty( $input['google_consent_mode'] ),
				'google_wait_for_update' => min( 2000, absint( $input['google_wait_for_update'] ?? 500 ) ),
				'data_consent_enabled' => ! empty( $input['data_consent_enabled'] ),
				'data_consent_version' => substr( sanitize_text_field( $input['data_consent_version'] ?? '1.0' ), 0, 64 ),
				'data_consent_text'    => sanitize_textarea_field( $input['data_consent_text'] ?? '' ),
				'data_consent_cf7'     => ! empty( $input['data_consent_cf7'] ),
				'data_consent_woocommerce' => ! empty( $input['data_consent_woocommerce'] ),
				'log_ip_mode'         => in_array( $input['log_ip_mode'] ?? '', array( 'hash', 'plain', 'none' ), true ) ? $input['log_ip_mode'] : 'hash',
				'remove_on_uninstall' => ! empty( $input['remove_on_uninstall'] ),
			)
		);

		if ( 'pro' === CSY_EDITION ) {
			$this->options->update(
				array(
					'pro_banner_background'  => sanitize_hex_color( $input['pro_banner_background'] ?? '' ) ?: '#ffffff',
					'pro_banner_text_color'  => sanitize_hex_color( $input['pro_banner_text_color'] ?? '' ) ?: '#142028',
					'pro_accent_color'       => sanitize_hex_color( $input['pro_accent_color'] ?? '' ) ?: '#126782',
					'pro_button_text_color'  => sanitize_hex_color( $input['pro_button_text_color'] ?? '' ) ?: '#ffffff',
					'pro_border_radius'      => min( 32, absint( $input['pro_border_radius'] ?? 8 ) ),
					'pro_custom_css'         => substr( str_ireplace( array( '<style', '</style', 'javascript:', 'expression(' ), '', (string) ( $input['pro_custom_css'] ?? '' ) ), 0, 20000 ),
					'pro_log_retention_days' => min( 3650, absint( $input['pro_log_retention_days'] ?? 0 ) ),
					'pro_button_reject'      => sanitize_text_field( $input['pro_button_reject'] ?? '' ),
					'pro_button_customize'   => sanitize_text_field( $input['pro_button_customize'] ?? '' ),
					'pro_button_accept'      => sanitize_text_field( $input['pro_button_accept'] ?? '' ),
					'pro_modal_title'        => sanitize_text_field( $input['pro_modal_title'] ?? '' ),
				)
			);
		}

		$options         = $this->options->all();
		$verification    = $this->verification_state( $options );
		$manual_checks   = $this->sanitize_manual_checks( $input['manual_checks'] ?? array(), $verification['manual'] );
		$previous_hash   = (string) ( $previous_options['verification_context_hash'] ?? '' );
		$context_changed = '' !== $previous_hash && ! hash_equals( $previous_hash, $verification['context_hash'] );

		if ( $context_changed ) {
			$manual_checks = array();
		}

		$options['manual_checks']              = $manual_checks;
		$options['verification_context_hash']  = $verification['context_hash'];
		$verification                          = $this->verification_state( $options );
		$options['last_verified_at']           = $verification['complete'] ? ( $context_changed || empty( $previous_options['last_verified_at'] ) ? current_time( 'mysql' ) : $previous_options['last_verified_at'] ) : '';
		$this->options->update( $options );

		$redirect_args = array( 'page' => 'cookie-setting-yes' );
		if ( ! empty( $input['save_verification'] ) ) {
			$redirect_args['csy_verification_saved'] = $verification['complete'] ? 'complete' : 'incomplete';
		} else {
			$redirect_args['csy_updated'] = '1';
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	private function verification_state( array $options ) {
		$automatic = array(
			'banner' => array(
				'anchor' => 'csy-general',
				'label'  => __( 'Banner enabled', 'cookie-setting-yes' ),
				'text'   => __( 'The public consent interface is available to website visitors.', 'cookie-setting-yes' ),
				'ready'  => ! empty( $options['enabled'] ),
			),
			'consent_text' => array(
				'anchor' => 'csy-general',
				'label'  => __( 'Cookie terms are complete', 'cookie-setting-yes' ),
				'text'   => __( 'The legal regime and version are selected, the banner title and text are filled in.', 'cookie-setting-yes' ),
				'ready'  => ! empty( $options['consent_version'] ) && ! empty( $options['banner_title'] ) && ! empty( $options['banner_text'] ),
			),
			'documents' => array(
				'anchor' => 'csy-content',
				'label'  => __( 'Links to policies are provided', 'cookie-setting-yes' ),
				'text'   => __( 'The privacy policy and cookie policy addresses are filled in.', 'cookie-setting-yes' ),
				'ready'  => ! empty( $options['privacy_url'] ) && ! empty( $options['cookie_policy_url'] ),
			),
			'operator' => array(
				'anchor' => 'csy-content',
				'label'  => __( 'Operator details are complete', 'cookie-setting-yes' ),
				'text'   => __( 'The operator, contact, purposes, data categories, and retention period are complete.', 'cookie-setting-yes' ),
				'ready'  => ! empty( $options['operator_name'] ) && ! empty( $options['operator_contact'] ) && ! empty( $options['personal_data_purposes'] ) && ! empty( $options['personal_data_categories'] ) && ! empty( $options['personal_data_retention'] ),
			),
			'data_consent' => array(
				'anchor' => 'csy-data-consent',
				'label'  => __( 'Data consent configured', 'cookie-setting-yes' ),
				'text'   => __( 'A separate data consent flow is enabled and its version and text are complete.', 'cookie-setting-yes' ),
				'ready'  => ! empty( $options['data_consent_enabled'] ) && ! empty( $options['data_consent_version'] ) && ! empty( $options['data_consent_text'] ),
			),
		);

		$manual = array(
			'first_visit' => array(
				'label' => __( 'First visit', 'cookie-setting-yes' ),
				'text'  => __( 'In the private window, a banner is shown, and optional services are not loaded until selected.', 'cookie-setting-yes' ),
			),
			'cookie_choice' => array(
				'label' => __( 'Cookie selection', 'cookie-setting-yes' ),
				'text'  => __( 'Checked "Reject", saving selected categories and reopening settings.', 'cookie-setting-yes' ),
			),
			'documents' => array(
				'label' => __( 'Documents and logs', 'cookie-setting-yes' ),
				'text'  => __( 'Both policies are opened, links from the banner are checked and CSV logs are downloaded.', 'cookie-setting-yes' ),
			),
		);

		if ( ! empty( $options['selected_services'] ) || ! empty( $options['script_handles'] ) ) {
			$manual['services'] = array(
				'label' => __( 'Connected services', 'cookie-setting-yes' ),
				'text'  => __( 'Checked that selected script handles are not executed before consent and are run after it.', 'cookie-setting-yes' ),
			);
		}

		if ( ! empty( $options['data_consent_enabled'] ) ) {
			$manual['forms'] = array(
				'label' => __( 'Forms', 'cookie-setting-yes' ),
				'text'  => __( 'The mandatory mark and the appearance of an entry in the data consent log were checked.', 'cookie-setting-yes' ),
			);
		}

		if ( ! empty( $options['data_consent_woocommerce'] ) ) {
			$manual['woocommerce'] = array(
				'label' => __( 'WooCommerce Order', 'cookie-setting-yes' ),
				'text'  => __( 'Verified that consent is required in the active checkout and that an order-linked record is created.', 'cookie-setting-yes' ),
			);
		}

		if ( ! empty( $options['google_consent_mode'] ) ) {
			$manual['google'] = array(
				'label' => __( 'Google Consent Mode', 'cookie-setting-yes' ),
				'text'  => __( 'Checked initial denied state and updated consent after user selection.', 'cookie-setting-yes' ),
			);
		}

		$marked_checks = array_fill_keys( array_map( 'sanitize_key', (array) ( $options['manual_checks'] ?? array() ) ), true );
		foreach ( $manual as $key => &$check ) {
			$check['checked'] = ! empty( $marked_checks[ $key ] );
		}
		unset( $check );

		$automatic_ready = ! in_array( false, wp_list_pluck( $automatic, 'ready' ), true );
		$manual_ready    = count( $manual ) === count( array_filter( $manual, static function ( $check ) { return ! empty( $check['checked'] ); } ) );

		return array(
			'automatic'        => $automatic,
			'manual'           => $manual,
			'automatic_ready'  => $automatic_ready,
			'manual_ready'     => $manual_ready,
			'complete'         => $automatic_ready && $manual_ready,
			'manual_completed' => count( array_filter( $manual, static function ( $check ) { return ! empty( $check['checked'] ); } ) ),
			'manual_total'     => count( $manual ),
			'context_hash'     => $this->verification_context_hash( $options ),
		);
	}

	private function sanitize_manual_checks( $values, array $manual ) {
		$allowed = array_keys( $manual );
		$checked = array();
		foreach ( (array) $values as $value ) {
			$value = sanitize_key( $value );
			if ( in_array( $value, $allowed, true ) ) {
				$checked[] = $value;
			}
		}

		return array_values( array_unique( $checked ) );
	}

	private function verification_context_hash( array $options ) {
		$services = array_values( array_unique( array_map( 'sanitize_key', (array) ( $options['selected_services'] ?? array() ) ) ) );
		$handles  = (array) ( $options['script_handles'] ?? array() );
		sort( $services );
		ksort( $handles );

		return hash(
			'sha256',
			wp_json_encode(
				array(
					'law_mode'                  => $options['law_mode'] ?? '',
					'enabled'                   => ! empty( $options['enabled'] ),
					'consent_version'           => $options['consent_version'] ?? '',
					'banner_title'              => $options['banner_title'] ?? '',
					'banner_text'               => $options['banner_text'] ?? '',
					'privacy_url'               => $options['privacy_url'] ?? '',
					'cookie_policy_url'         => $options['cookie_policy_url'] ?? '',
					'operator_name'             => $options['operator_name'] ?? '',
					'operator_contact'          => $options['operator_contact'] ?? '',
					'personal_data_purposes'    => $options['personal_data_purposes'] ?? '',
					'personal_data_categories'  => $options['personal_data_categories'] ?? '',
					'personal_data_retention'   => $options['personal_data_retention'] ?? '',
					'data_consent_enabled'      => ! empty( $options['data_consent_enabled'] ),
					'data_consent_version'      => $options['data_consent_version'] ?? '',
					'data_consent_text'         => $options['data_consent_text'] ?? '',
					'data_consent_cf7'          => ! empty( $options['data_consent_cf7'] ),
					'data_consent_woocommerce'  => ! empty( $options['data_consent_woocommerce'] ),
					'selected_services'         => $services,
					'script_handles'            => $handles,
					'google_consent_mode'       => ! empty( $options['google_consent_mode'] ),
					'banner_horizontal'         => $options['banner_horizontal'] ?? '',
					'banner_vertical'           => $options['banner_vertical'] ?? '',
				)
			)
		);
	}

	public function export_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'cookie-setting-yes' ) );
		}
		check_admin_referer( 'csy_export_log' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=cookie-consent-log-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'UTC', 'Visitor hash', 'User ID', 'IP', 'User agent', 'Version', 'Terms hash', 'Action', 'Categories' ) );
		foreach ( $this->consent_log->get_recent( 10000 ) as $row ) {
			fputcsv( $output, array( $row->id, $row->created_at, $row->visitor_id, $row->user_id, $row->ip_value, $row->user_agent, $row->consent_version, $row->consent_hash, $row->action, $row->categories ) );
		}
		exit;
	}

	public function export_data_consent_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'cookie-setting-yes' ) );
		}
		check_admin_referer( 'csy_export_data_consent_log' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=data-consent-log-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'UTC', 'Visitor hash', 'User ID', 'IP', 'User agent', 'Source', 'Context ID', 'Context', 'Context hash', 'Version', 'Terms hash', 'Privacy policy URL' ) );
		foreach ( $this->data_consent_log->get_recent( 10000 ) as $row ) {
			fputcsv( $output, array( $row->id, $row->created_at, $row->visitor_id, $row->user_id, $row->ip_value, $row->user_agent, $row->source, $row->context_id, $row->context_label, $row->context_hash, $row->consent_version, $row->consent_hash, $row->policy_url ) );
		}
		exit;
	}

	public function clear_log() {
		$this->clear_pro_log( 'csy_clear_log', $this->consent_log, 'cookie', 'csy-data' );
	}

	public function clear_data_consent_log() {
		$this->clear_pro_log( 'csy_clear_data_consent_log', $this->data_consent_log, 'data', 'csy-data-consent' );
	}

	private function clear_pro_log( $nonce_action, $log, $log_type, $anchor ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'cookie-setting-yes' ) );
		}
		if ( 'pro' !== CSY_EDITION ) {
			wp_die( esc_html__( 'Log cleaning is available in the Pro version.', 'cookie-setting-yes' ) );
		}
		check_admin_referer( $nonce_action );

		$log->clear();
		$url = add_query_arg(
			array(
				'page'            => 'cookie-setting-yes',
				'csy_log_cleared' => sanitize_key( $log_type ),
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url . '#' . sanitize_key( $anchor ) );
		exit;
	}
}
