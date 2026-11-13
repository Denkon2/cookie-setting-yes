<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Known services and the handles commonly used when they are enqueued by WordPress. */
final class Service_Catalog {
	public function all() {
		$services = array(
			'yandex-metrika' => array(
				'label'       => __( 'Yandex Metrica', 'cookie-setting-yes' ),
				'description' => __( 'Visit statistics and user behavior. Connection via WordPress and code inserted into the template are supported.', 'cookie-setting-yes' ),
				'category'    => 'analytics',
				'handles'     => array( 'yandex-metrika', 'yandex_metrika', 'ym' ),
				'raw_patterns' => array( 'mc.yandex.ru', 'mc.yandex.com', 'ym(' ),
			),
			'google-analytics' => array(
				'label'       => __( 'Google Analytics / Google tag', 'cookie-setting-yes' ),
				'description' => __( 'Google Analytics connected as a separate WordPress script.', 'cookie-setting-yes' ),
				'category'    => 'analytics',
				'handles'     => array( 'google-analytics', 'google-gtag', 'gtag' ),
			),
			'google-tag-manager' => array(
				'label'       => __( 'Google Tag Manager', 'cookie-setting-yes' ),
				'description' => __( 'A container can run multiple third party tags; check its composition.', 'cookie-setting-yes' ),
				'category'    => 'marketing',
				'handles'     => array( 'google-tag-manager', 'google-gtm', 'gtm' ),
			),
			'meta-pixel' => array(
				'label'       => __( 'Meta Pixel', 'cookie-setting-yes' ),
				'description' => __( 'Advertising audiences and measurement of Meta campaigns.', 'cookie-setting-yes' ),
				'category'    => 'marketing',
				'handles'     => array( 'meta-pixel', 'facebook-pixel', 'fb-pixel' ),
			),
			'vk-pixel' => array(
				'label'       => __( 'VK Advertising Pixel', 'cookie-setting-yes' ),
				'description' => __( 'Evaluation of VK advertising campaigns.', 'cookie-setting-yes' ),
				'category'    => 'marketing',
				'handles'     => array( 'vk-pixel', 'vk_pixel' ),
			),
			'top-mail-ru' => array(
				'label'       => __( 'Top.Mail.Ru', 'cookie-setting-yes' ),
				'description' => __( 'Mail.Ru traffic statistics.', 'cookie-setting-yes' ),
				'category'    => 'analytics',
				'handles'     => array( 'top-mail-ru', 'topmail' ),
			),
			'roistat' => array(
				'label'       => __( 'Roistat', 'cookie-setting-yes' ),
				'description' => __( 'End-to-end analytics and call tracking.', 'cookie-setting-yes' ),
				'category'    => 'analytics',
				'handles'     => array( 'roistat', 'roistat-tracker' ),
			),
			'calltouch' => array(
				'label'       => __( 'Calltouch', 'cookie-setting-yes' ),
				'description' => __( 'Call tracking and analytics of requests.', 'cookie-setting-yes' ),
				'category'    => 'analytics',
				'handles'     => array( 'calltouch', 'calltouch-tracker' ),
			),
			'jivosite' => array(
				'label'       => __( 'Jivo', 'cookie-setting-yes' ),
				'description' => __( 'Online chat and communication with the visitor.', 'cookie-setting-yes' ),
				'category'    => 'functional',
				'handles'     => array( 'jivosite', 'jivo-chat' ),
			),
			'bitrix24-widget' => array(
				'label'       => __( 'Bitrix24 widget', 'cookie-setting-yes' ),
				'description' => __( 'Online chat, forms and call back.', 'cookie-setting-yes' ),
				'category'    => 'functional',
				'handles'     => array( 'bitrix24', 'bitrix24-widget' ),
			),
		);

		return apply_filters( 'cookie_setting_yes_service_catalog', $services );
	}

	public function selected_handles( array $selected_services ) {
		$handles = array();
		foreach ( $selected_services as $service_id ) {
			$service_id = sanitize_key( $service_id );
			$service = $this->all()[ $service_id ] ?? null;
			if ( empty( $service['handles'] ) || empty( $service['category'] ) ) {
				continue;
			}

			foreach ( $service['handles'] as $handle ) {
				$handles[ sanitize_key( $handle ) ] = sanitize_key( $service['category'] );
			}
		}

		return $handles;
	}

	/**
	 * Return service signatures for scripts printed directly by a theme or another plugin.
	 *
	 * Such snippets do not pass through the script_loader_tag WordPress filter and need
	 * to be held in a text/plain script until the visitor grants consent.
	 */
	public function selected_raw_patterns( array $selected_services ) {
		$patterns = array();
		$catalog  = $this->all();

		foreach ( $selected_services as $service_id ) {
			$service_id = sanitize_key( $service_id );
			$service    = $catalog[ $service_id ] ?? null;
			if ( empty( $service['raw_patterns'] ) || empty( $service['category'] ) ) {
				continue;
			}

			foreach ( (array) $service['raw_patterns'] as $pattern ) {
				$pattern = strtolower( trim( (string) $pattern ) );
				if ( '' !== $pattern ) {
					$patterns[ $pattern ] = sanitize_key( $service['category'] );
				}
			}
		}

		return apply_filters( 'cookie_setting_yes_raw_script_patterns', $patterns, $selected_services, $catalog );
	}
}
