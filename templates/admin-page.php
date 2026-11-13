<?php
defined( 'ABSPATH' ) || exit;

$csy_handle_lines = array();
foreach ( (array) $options['script_handles'] as $csy_handle => $csy_category ) {
	$csy_handle_lines[] = $csy_handle . '=' . $csy_category;
}

$csy_law_labels = array(
	'ru_152fz' => __( '152-FZ', 'cookie-setting-yes' ),
	'gdpr'     => 'GDPR',
	'ccpa'     => 'CCPA/CPRA',
	'custom'   => __( 'Custom mode', 'cookie-setting-yes' ),
);
$csy_law_label          = $csy_law_labels[ $options['law_mode'] ] ?? $csy_law_labels['custom'];
$csy_pro_url            = defined( 'CSY_PRO_URL' ) ? CSY_PRO_URL : 'https://denkon.ru/quick-start/cookie-setting-yes-soglasie-na-obrabotku-dannyx';
$csy_edition_label      = 'pro' === CSY_EDITION ? 'Pro' : 'Free';
$csy_edition_link_label = 'pro' === CSY_EDITION ? __( 'Open product page', 'cookie-setting-yes' ) : __( 'View Pro Features', 'cookie-setting-yes' );
$csy_verification_notice = sanitize_key( (string) filter_input( INPUT_GET, 'csy_verification_saved', FILTER_SANITIZE_SPECIAL_CHARS ) );
$csy_updated_notice      = (bool) filter_input( INPUT_GET, 'csy_updated', FILTER_SANITIZE_NUMBER_INT );
$csy_log_cleared_notice  = sanitize_key( (string) filter_input( INPUT_GET, 'csy_log_cleared', FILTER_SANITIZE_SPECIAL_CHARS ) );
$csy_setup_steps = array(
	array( 'anchor' => 'csy-general', 'label' => __( 'Legal framework', 'cookie-setting-yes' ), 'text' => __( 'Select a mode and turn on the banner.', 'cookie-setting-yes' ), 'ready' => ! empty( $options['enabled'] ) ),
	array( 'anchor' => 'csy-content', 'label' => __( 'Policies', 'cookie-setting-yes' ), 'text' => __( 'Fill in the operator details and provide links.', 'cookie-setting-yes' ), 'ready' => ! empty( $options['privacy_url'] ) && ! empty( $options['cookie_policy_url'] ) ),
	array( 'anchor' => 'csy-data-consent', 'label' => __( 'Forms', 'cookie-setting-yes' ), 'text' => __( 'Configure personal data consent.', 'cookie-setting-yes' ), 'ready' => ! empty( $options['data_consent_enabled'] ) && ! empty( $options['data_consent_text'] ) ),
	array( 'anchor' => 'csy-checks', 'label' => __( 'Check', 'cookie-setting-yes' ), 'text' => __( 'Complete automatic and manual checks.', 'cookie-setting-yes' ), 'ready' => $verification['complete'] ),
);
$csy_setup_ready_count = count( array_filter( $csy_setup_steps, static function ( $csy_step ) { return $csy_step['ready']; } ) );
/* translators: %d: number of completed setup steps. */
$csy_setup_progress = sprintf( __( '%d of 4 steps', 'cookie-setting-yes' ), $csy_setup_ready_count );
if ( $verification['complete'] && ! empty( $options['last_verified_at'] ) ) {
	/* translators: %s: date and time when verification was completed. */
	$csy_setup_status = sprintf( __( 'Check completed: %s', 'cookie-setting-yes' ), $options['last_verified_at'] );
} else {
	/* translators: 1: number of incomplete automatic checks, 2: number of incomplete manual checks. */
	$csy_setup_status = sprintf( __( 'Remaining: automatic - %1$d, manual - %2$d.', 'cookie-setting-yes' ), count( array_filter( $verification['automatic'], static function ( $csy_check ) { return empty( $csy_check['ready'] ); } ) ), $verification['manual_total'] - $verification['manual_completed'] );
}
/* translators: 1: completed manual checks, 2: total manual checks. */
$csy_verification_status = $verification['automatic_ready'] ? sprintf( __( 'Automatic checks passed. Manual checks: %1$d of %2$d.', 'cookie-setting-yes' ), $verification['manual_completed'], $verification['manual_total'] ) : sprintf( __( 'Complete the required settings. Manual checks: %1$d of %2$d.', 'cookie-setting-yes' ), $verification['manual_completed'], $verification['manual_total'] );
/* translators: 1: completed manual checks, 2: total manual checks. */
$csy_manual_count = sprintf( __( '%1$d of %2$d', 'cookie-setting-yes' ), $verification['manual_completed'], $verification['manual_total'] );
?>
<div class="wrap csy-admin" data-csy-admin-theme="light">
	<header class="csy-admin__hero">
		<div class="csy-admin__brand">
			<span class="csy-admin__mark" aria-hidden="true">CSY</span>
			<div><p class="csy-admin__eyebrow">Denkon / WordPress plugin</p><div class="csy-admin__title"><h1><?php esc_html_e( 'Cookie Setting Yes', 'cookie-setting-yes' ); ?></h1><span class="csy-edition"><?php echo esc_html( $csy_edition_label ); ?></span></div></div>
		</div>
		<div class="csy-admin__hero-actions">
			<span class="csy-admin__status <?php echo $options['enabled'] ? 'is-active' : 'is-paused'; ?>"><i></i><?php echo $options['enabled'] ? esc_html__( 'Protection enabled', 'cookie-setting-yes' ) : esc_html__( 'Banner is turned off', 'cookie-setting-yes' ); ?></span>
			<button id="csy-admin-theme-toggle" class="csy-admin__theme-toggle" type="button" aria-pressed="false" title="<?php esc_attr_e( 'Switch color theme', 'cookie-setting-yes' ); ?>"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Switch color theme', 'cookie-setting-yes' ); ?></span></button>
		</div>
		<p class="csy-admin__lead"><?php esc_html_e( 'Consent, trackers and proof of visitor choice in one local dashboard.', 'cookie-setting-yes' ); ?> <a href="<?php echo esc_url( $csy_pro_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $csy_edition_link_label ); ?></a></p>
	</header>

	<?php if ( $csy_updated_notice ) : ?><div class="csy-admin__notice" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'The settings are saved. If you change the terms of consent, increase its version.', 'cookie-setting-yes' ); ?></div><?php endif; ?>
	<?php if ( $csy_verification_notice ) : ?><div class="csy-admin__notice <?php echo 'complete' === $csy_verification_notice ? '' : 'is-warning'; ?>" role="status"><span class="dashicons <?php echo 'complete' === $csy_verification_notice ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>" aria-hidden="true"></span><?php echo 'complete' === $csy_verification_notice ? esc_html__( 'The check is completed and the date is saved.', 'cookie-setting-yes' ) : esc_html__( 'The checks have been saved. Complete the automatic checks and all manual scenarios.', 'cookie-setting-yes' ); ?></div><?php endif; ?>
	<?php if ( $csy_log_cleared_notice ) : ?><div class="csy-admin__notice" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'The log has been cleared.', 'cookie-setting-yes' ); ?></div><?php endif; ?>

	<section class="csy-admin__metrics" aria-label="<?php esc_attr_e( 'Summary', 'cookie-setting-yes' ); ?>">
		<div class="csy-metric"><span><?php esc_html_e( 'Legal regime', 'cookie-setting-yes' ); ?></span><strong><?php echo esc_html( $csy_law_label ); ?></strong></div>
		<div class="csy-metric"><span><?php esc_html_e( 'Version of consent', 'cookie-setting-yes' ); ?></span><strong><?php echo esc_html( $options['consent_version'] ); ?></strong></div>
		<div class="csy-metric"><span><?php esc_html_e( 'Log entries', 'cookie-setting-yes' ); ?></span><strong><?php echo esc_html( number_format_i18n( $log_count ) ); ?></strong></div>
		<div class="csy-metric"><span><?php esc_html_e( 'Protected services', 'cookie-setting-yes' ); ?></span><strong><?php echo esc_html( count( (array) $options['selected_services'] ) ); ?></strong></div>
	</section>

	<section class="csy-setup" id="csy-setup" aria-label="<?php esc_attr_e( 'Launch Wizard', 'cookie-setting-yes' ); ?>">
		<div class="csy-setup__heading"><div><p><?php esc_html_e( 'Launch Wizard Free', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Prepare your site for publication', 'cookie-setting-yes' ); ?></h2></div><strong><?php echo esc_html( $csy_setup_progress ); ?></strong></div>
		<ol class="csy-setup__steps">
			<?php foreach ( $csy_setup_steps as $csy_index => $csy_step ) : ?><li class="<?php echo $csy_step['ready'] ? 'is-ready' : ''; ?>"><a href="#<?php echo esc_attr( $csy_step['anchor'] ); ?>"><span><?php echo esc_html( $csy_index + 1 ); ?></span><strong><?php echo esc_html( $csy_step['label'] ); ?></strong><small><?php echo esc_html( $csy_step['text'] ); ?></small></a></li><?php endforeach; ?>
		</ol>
		<div class="csy-setup__footer"><span><?php echo esc_html( $csy_setup_status ); ?></span><a href="#csy-checks" class="csy-setup__link"><?php esc_html_e( 'Open checks', 'cookie-setting-yes' ); ?></a></div>
	</section>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="csy-admin__form">
		<?php wp_nonce_field( 'csy_save_settings' ); ?><input type="hidden" name="action" value="csy_save_settings">
		<div class="csy-admin__workspace">
			<div class="csy-admin__main">
				<div class="csy-admin__layout">
			<nav class="csy-admin__nav" aria-label="<?php esc_attr_e( 'Settings navigation', 'cookie-setting-yes' ); ?>">
				<a href="#csy-general"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e( 'Basics', 'cookie-setting-yes' ); ?></a>
				<a href="#csy-content"><span class="dashicons dashicons-media-text" aria-hidden="true"></span><?php esc_html_e( 'Texts and policies', 'cookie-setting-yes' ); ?></a>
				<a href="#csy-data-consent"><span class="dashicons dashicons-forms" aria-hidden="true"></span><?php esc_html_e( 'Data consent', 'cookie-setting-yes' ); ?></a>
				<a href="#csy-services"><span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span><?php esc_html_e( 'Services', 'cookie-setting-yes' ); ?></a>
				<a href="#csy-integrations"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span><?php esc_html_e( 'Integrations', 'cookie-setting-yes' ); ?></a>
				<?php if ( 'pro' === CSY_EDITION ) : ?><a href="#csy-pro"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><?php esc_html_e( 'Pro features', 'cookie-setting-yes' ); ?></a><?php endif; ?>
				<a href="#csy-data"><span class="dashicons dashicons-database" aria-hidden="true"></span><?php esc_html_e( 'Data and log', 'cookie-setting-yes' ); ?></a>
				<a href="#csy-checks"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Check', 'cookie-setting-yes' ); ?></a>
			</nav>

			<div class="csy-admin__content">
				<section id="csy-general" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Consent framework', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Basics', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-shield" aria-hidden="true"></span></div>
					<div class="csy-field-grid">
						<label class="csy-switch-row"><span><strong><?php esc_html_e( 'Enable banner', 'cookie-setting-yes' ); ?></strong><small><?php esc_html_e( 'Show a consent request to site visitors.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[enabled]" value="1" <?php checked( $options['enabled'] ); ?>><i aria-hidden="true"></i></label>
						<label><span><?php esc_html_e( 'Legal regime', 'cookie-setting-yes' ); ?></span><select name="csy[law_mode]"><option value="ru_152fz" <?php selected( $options['law_mode'], 'ru_152fz' ); ?>><?php esc_html_e( 'Russia: 152-FZ', 'cookie-setting-yes' ); ?></option><option value="gdpr" <?php selected( $options['law_mode'], 'gdpr' ); ?>>GDPR</option><option value="ccpa" <?php selected( $options['law_mode'], 'ccpa' ); ?>>CCPA/CPRA</option><option value="custom" <?php selected( $options['law_mode'], 'custom' ); ?>><?php esc_html_e( 'Custom mode', 'cookie-setting-yes' ); ?></option></select><small><?php esc_html_e( 'Check the texts and legal basis with a lawyer for your project.', 'cookie-setting-yes' ); ?></small></label>
						<label><span><?php esc_html_e( 'Version of consent', 'cookie-setting-yes' ); ?></span><input name="csy[consent_version]" type="text" value="<?php echo esc_attr( $options['consent_version'] ); ?>"><small><?php esc_html_e( 'Increase when goals, services, or text change.', 'cookie-setting-yes' ); ?></small></label>
					</div>
				</section>

				<section id="csy-content" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Visible to the visitor', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Texts and policies', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></div>
					<div class="csy-field-grid csy-field-grid--wide">
						<label><span><?php esc_html_e( 'Banner title', 'cookie-setting-yes' ); ?></span><input name="csy[banner_title]" type="text" value="<?php echo esc_attr( $options['banner_title'] ); ?>"></label>
						<label class="csy-field--full"><span><?php esc_html_e( 'Banner text', 'cookie-setting-yes' ); ?></span><textarea name="csy[banner_text]" rows="3"><?php echo esc_textarea( $options['banner_text'] ); ?></textarea></label>
						<label><span><?php esc_html_e( 'Privacy Policy', 'cookie-setting-yes' ); ?></span><input name="csy[privacy_url]" type="url" value="<?php echo esc_attr( $options['privacy_url'] ); ?>" placeholder="https://"></label>
						<label><span><?php esc_html_e( 'Cookie Policy', 'cookie-setting-yes' ); ?></span><input name="csy[cookie_policy_url]" type="url" value="<?php echo esc_attr( $options['cookie_policy_url'] ); ?>" placeholder="https://"><small><code>[cookie_setting_yes_cookie_policy]</code></small></label>
						<label><span><?php esc_html_e( 'Operator name', 'cookie-setting-yes' ); ?></span><input name="csy[operator_name]" type="text" value="<?php echo esc_attr( $options['operator_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></label>
						<label><span><?php esc_html_e( 'Operator contact', 'cookie-setting-yes' ); ?></span><input name="csy[operator_contact]" type="text" value="<?php echo esc_attr( $options['operator_contact'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"></label>
						<label class="csy-field--full"><span><?php esc_html_e( 'Purposes of processing personal data', 'cookie-setting-yes' ); ?></span><textarea name="csy[personal_data_purposes]" rows="3"><?php echo esc_textarea( $options['personal_data_purposes'] ); ?></textarea></label>
						<label><span><?php esc_html_e( 'Personal data categories', 'cookie-setting-yes' ); ?></span><textarea name="csy[personal_data_categories]" rows="3"><?php echo esc_textarea( $options['personal_data_categories'] ); ?></textarea></label>
						<label><span><?php esc_html_e( 'Retention period', 'cookie-setting-yes' ); ?></span><textarea name="csy[personal_data_retention]" rows="3"><?php echo esc_textarea( $options['personal_data_retention'] ); ?></textarea><small><code>[cookie_setting_yes_privacy_policy]</code></small></label>
					</div>
				</section>

				<section id="csy-data-consent" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Forms and orders', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Personal data consent', 'cookie-setting-yes' ); ?></h2></div><div class="csy-panel__actions"><a class="csy-export" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=csy_export_data_consent_log' ), 'csy_export_data_consent_log' ) ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'CSV', 'cookie-setting-yes' ); ?></a><?php if ( 'pro' === CSY_EDITION ) : ?><button type="submit" form="csy-clear-data-consent-log" class="csy-export csy-export--danger" onclick="return confirm('<?php echo esc_js( __( 'Clear all records of consent to data processing? This action cannot be undone.', 'cookie-setting-yes' ) ); ?>');"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Clear data consent log', 'cookie-setting-yes' ); ?></span></button><?php endif; ?></div></div>
					<div class="csy-field-grid csy-field-grid--wide">
						<label class="csy-switch-row"><span><strong><?php esc_html_e( 'Enable Data Consent', 'cookie-setting-yes' ); ?></strong><small><?php esc_html_e( 'Adds a separate confirmation for forms and captures the proof of choice.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[data_consent_enabled]" value="1" <?php checked( $options['data_consent_enabled'] ); ?>><i aria-hidden="true"></i></label>
						<label><span><?php esc_html_e( 'Data consent version', 'cookie-setting-yes' ); ?></span><input name="csy[data_consent_version]" type="text" value="<?php echo esc_attr( $options['data_consent_version'] ); ?>"><small><?php esc_html_e( 'Increase when text, goals, or policies change.', 'cookie-setting-yes' ); ?></small></label>
						<label class="csy-field--full"><span><?php esc_html_e( 'Consent text', 'cookie-setting-yes' ); ?></span><textarea name="csy[data_consent_text]" rows="3"><?php echo esc_textarea( $options['data_consent_text'] ); ?></textarea><small><?php esc_html_e( 'Use %privacy_policy% to link to the privacy policy above.', 'cookie-setting-yes' ); ?></small></label>
						<label class="csy-switch-row"><span><strong>Contact Form 7</strong><small><?php esc_html_e( 'Add the [csy_consent] tag to your form template. Sending without a mark will be stopped.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[data_consent_cf7]" value="1" <?php checked( $options['data_consent_cf7'] ); ?>><i aria-hidden="true"></i></label>
						<label class="csy-switch-row"><span><strong>WooCommerce</strong><small><?php esc_html_e( 'Show mandatory consent in classic checkout and Checkout Block.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[data_consent_woocommerce]" value="1" <?php checked( $options['data_consent_woocommerce'] ); ?>><i aria-hidden="true"></i></label>
					</div>
					<div class="csy-log"><div class="csy-log__title"><strong><?php esc_html_e( 'Latest data consents', 'cookie-setting-yes' ); ?></strong><span><?php echo esc_html( number_format_i18n( $data_consent_log_count ) ); ?></span></div><p class="csy-log__hint"><?php esc_html_e( 'A record is created when a visitor agrees and submits a form or places an order. The choice in the cookie banner is recorded separately in the adjacent log.', 'cookie-setting-yes' ); ?></p><div class="csy-log__table"><table><thead><tr><th>UTC</th><th><?php esc_html_e( 'Source', 'cookie-setting-yes' ); ?></th><th><?php esc_html_e( 'Context', 'cookie-setting-yes' ); ?></th><th><?php esc_html_e( 'Version', 'cookie-setting-yes' ); ?></th></tr></thead><tbody><?php if ( $data_consent_logs ) : foreach ( $data_consent_logs as $csy_log ) : ?><tr><td><?php echo esc_html( $csy_log->created_at ); ?></td><td><span class="csy-log__action"><?php echo esc_html( $csy_log->source ); ?></span></td><td><?php echo esc_html( $csy_log->context_label ?: '-' ); ?></td><td><?php echo esc_html( $csy_log->consent_version ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4" class="csy-log__empty"><?php esc_html_e( 'There are no entries yet.', 'cookie-setting-yes' ); ?></td></tr><?php endif; ?></tbody></table></div></div>
				</section>

				<section id="csy-services" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Blocked until consent', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Services and blocking', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-lock" aria-hidden="true"></span></div>
					<div class="csy-service-grid">
						<?php foreach ( $services as $csy_service_id => $csy_service ) : ?>
							<label class="csy-service"><input type="checkbox" name="csy[selected_services][]" value="<?php echo esc_attr( $csy_service_id ); ?>" <?php checked( in_array( $csy_service_id, (array) $options['selected_services'], true ) ); ?>><span class="csy-service__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span><span><strong><?php echo esc_html( $csy_service['label'] ); ?></strong><small><?php echo esc_html( $csy_service['description'] ); ?></small><em><?php echo esc_html( $categories[ $csy_service['category'] ]['label'] ?? $csy_service['category'] ); ?></em></span></label>
						<?php endforeach; ?>
					</div>
						<label class="csy-code-field"><span><?php esc_html_e( 'Manual WordPress script handles', 'cookie-setting-yes' ); ?></span><textarea name="csy[script_handles]" rows="6" class="code"><?php echo esc_textarea( implode( "\n", $csy_handle_lines ) ); ?></textarea><small><?php esc_html_e( 'One line: handle=category. The manual rule takes precedence over the directory.', 'cookie-setting-yes' ); ?></small></label>
				</section>

				<section id="csy-integrations" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'State sharing', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Integrations', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span></div>
					<div class="csy-field-grid">
						<label class="csy-switch-row"><span><strong>Google Consent Mode v2</strong><small><?php esc_html_e( 'Pass user selection to Google tag and Google Tag Manager.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[google_consent_mode]" value="1" <?php checked( $options['google_consent_mode'] ); ?>><i aria-hidden="true"></i></label>
						<label><span><?php esc_html_e( 'Waiting for update', 'cookie-setting-yes' ); ?></span><div class="csy-input-unit"><input name="csy[google_wait_for_update]" type="number" min="0" max="2000" step="50" value="<?php echo esc_attr( $options['google_wait_for_update'] ); ?>"><b><?php esc_html_e( 'ms', 'cookie-setting-yes' ); ?></b></div><small><?php esc_html_e( 'Only used before the first selection. 500ms is recommended.', 'cookie-setting-yes' ); ?></small></label>
					</div>
					<div class="csy-integration-note"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><div><strong>WP Consent API</strong><p><?php esc_html_e( 'Compatibility is automatically enabled when the API is installed on the site.', 'cookie-setting-yes' ); ?></p></div></div>
				</section>

				<?php if ( 'pro' === CSY_EDITION ) : ?>
				<section id="csy-pro" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Advanced Control', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Pro Features', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-star-filled" aria-hidden="true"></span></div>
					<div class="csy-field-grid">
						<label><span><?php esc_html_e( 'Banner background', 'cookie-setting-yes' ); ?></span><input name="csy[pro_banner_background]" type="color" value="<?php echo esc_attr( $options['pro_banner_background'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Text color', 'cookie-setting-yes' ); ?></span><input name="csy[pro_banner_text_color]" type="color" value="<?php echo esc_attr( $options['pro_banner_text_color'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Accent and buttons', 'cookie-setting-yes' ); ?></span><input name="csy[pro_accent_color]" type="color" value="<?php echo esc_attr( $options['pro_accent_color'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Button text color', 'cookie-setting-yes' ); ?></span><input name="csy[pro_button_text_color]" type="color" value="<?php echo esc_attr( $options['pro_button_text_color'] ); ?>"><small><?php esc_html_e( 'It can be configured separately so that the inscription does not merge with the background of the button.', 'cookie-setting-yes' ); ?></small></label>
						<label><span><?php esc_html_e( 'Banner border radius', 'cookie-setting-yes' ); ?></span><div class="csy-input-unit"><input name="csy[pro_border_radius]" type="number" min="0" max="32" value="<?php echo esc_attr( $options['pro_border_radius'] ); ?>"><b><?php esc_html_e( 'px', 'cookie-setting-yes' ); ?></b></div></label>
						<label><span><?php esc_html_e( 'Reject button', 'cookie-setting-yes' ); ?></span><input name="csy[pro_button_reject]" type="text" value="<?php echo esc_attr( $options['pro_button_reject'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Customize button', 'cookie-setting-yes' ); ?></span><input name="csy[pro_button_customize]" type="text" value="<?php echo esc_attr( $options['pro_button_customize'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Accept button', 'cookie-setting-yes' ); ?></span><input name="csy[pro_button_accept]" type="text" value="<?php echo esc_attr( $options['pro_button_accept'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Settings window title', 'cookie-setting-yes' ); ?></span><input name="csy[pro_modal_title]" type="text" value="<?php echo esc_attr( $options['pro_modal_title'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Store logs', 'cookie-setting-yes' ); ?></span><div class="csy-input-unit"><input name="csy[pro_log_retention_days]" type="number" min="0" max="3650" value="<?php echo esc_attr( $options['pro_log_retention_days'] ); ?>"><b><?php esc_html_e( 'days', 'cookie-setting-yes' ); ?></b></div><small><?php esc_html_e( '0 means keep without automatic deletion.', 'cookie-setting-yes' ); ?></small></label>
						<label class="csy-field--full"><span><?php esc_html_e( 'Custom banner CSS', 'cookie-setting-yes' ); ?></span><textarea name="csy[pro_custom_css]" rows="6" class="code"><?php echo esc_textarea( $options['pro_custom_css'] ); ?></textarea><small><?php esc_html_e( 'Loads only on the public part of the site after the plugin styles.', 'cookie-setting-yes' ); ?></small></label>
					</div>
					<div class="csy-setting-group">
						<div class="csy-setting-group__heading"><strong><?php esc_html_e( 'Banner positioning', 'cookie-setting-yes' ); ?></strong><small><?php esc_html_e( 'With a vertical center, the banner becomes compact, and the buttons are located below the text.', 'cookie-setting-yes' ); ?></small></div>
						<div class="csy-field-grid">
							<label><span><?php esc_html_e( 'Horizontal', 'cookie-setting-yes' ); ?></span><select name="csy[banner_horizontal]"><option value="left" <?php selected( $options['banner_horizontal'], 'left' ); ?>><?php esc_html_e( 'Left', 'cookie-setting-yes' ); ?></option><option value="center" <?php selected( $options['banner_horizontal'], 'center' ); ?>><?php esc_html_e( 'Center', 'cookie-setting-yes' ); ?></option><option value="right" <?php selected( $options['banner_horizontal'], 'right' ); ?>><?php esc_html_e( 'Right', 'cookie-setting-yes' ); ?></option></select></label>
							<label><span><?php esc_html_e( 'Vertical', 'cookie-setting-yes' ); ?></span><select name="csy[banner_vertical]"><option value="top" <?php selected( $options['banner_vertical'], 'top' ); ?>><?php esc_html_e( 'Top', 'cookie-setting-yes' ); ?></option><option value="center" <?php selected( $options['banner_vertical'], 'center' ); ?>><?php esc_html_e( 'Center', 'cookie-setting-yes' ); ?></option><option value="bottom" <?php selected( $options['banner_vertical'], 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'cookie-setting-yes' ); ?></option></select></label>
						</div>
					</div>
				</section>
				<?php endif; ?>

				</div>
			</div>

			<aside class="csy-admin__aside" aria-label="<?php esc_attr_e( 'Checks and logs', 'cookie-setting-yes' ); ?>">
				<section id="csy-data" class="csy-panel">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Control and evidence', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Data and log', 'cookie-setting-yes' ); ?></h2></div><div class="csy-panel__actions"><a class="csy-export" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=csy_export_log' ), 'csy_export_log' ) ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'CSV', 'cookie-setting-yes' ); ?></a><?php if ( 'pro' === CSY_EDITION ) : ?><button type="submit" form="csy-clear-consent-log" class="csy-export csy-export--danger" onclick="return confirm('<?php echo esc_js( __( 'Clear all cookie log entries? This action cannot be undone.', 'cookie-setting-yes' ) ); ?>');"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Clear cookie log', 'cookie-setting-yes' ); ?></span></button><?php endif; ?></div></div>
					<div class="csy-field-grid">
						<label><span><?php esc_html_e( 'IP address in log', 'cookie-setting-yes' ); ?></span><select name="csy[log_ip_mode]"><option value="hash" <?php selected( $options['log_ip_mode'], 'hash' ); ?>><?php esc_html_e( 'Hash', 'cookie-setting-yes' ); ?></option><option value="plain" <?php selected( $options['log_ip_mode'], 'plain' ); ?>><?php esc_html_e( 'Store in full', 'cookie-setting-yes' ); ?></option><option value="none" <?php selected( $options['log_ip_mode'], 'none' ); ?>><?php esc_html_e( 'Do not store', 'cookie-setting-yes' ); ?></option></select><small><?php esc_html_e( 'Hashing is suitable for most sites.', 'cookie-setting-yes' ); ?></small></label>
						<label class="csy-switch-row"><span><strong><?php esc_html_e( 'Deleting data', 'cookie-setting-yes' ); ?></strong><small><?php esc_html_e( 'Delete settings and log when uninstalling a plugin.', 'cookie-setting-yes' ); ?></small></span><input type="checkbox" name="csy[remove_on_uninstall]" value="1" <?php checked( $options['remove_on_uninstall'] ); ?>><i aria-hidden="true"></i></label>
					</div>
					<div class="csy-log"><div class="csy-log__title"><strong><?php esc_html_e( 'Latest consents', 'cookie-setting-yes' ); ?></strong><span><?php echo esc_html( number_format_i18n( $log_count ) ); ?></span></div><div class="csy-log__table"><table><thead><tr><th>UTC</th><th><?php esc_html_e( 'Action', 'cookie-setting-yes' ); ?></th><th><?php esc_html_e( 'Version', 'cookie-setting-yes' ); ?></th><th><?php esc_html_e( 'Categories', 'cookie-setting-yes' ); ?></th></tr></thead><tbody><?php if ( $logs ) : foreach ( $logs as $csy_log ) : ?><tr><td><?php echo esc_html( $csy_log->created_at ); ?></td><td><span class="csy-log__action"><?php echo esc_html( $csy_log->action ); ?></span></td><td><?php echo esc_html( $csy_log->consent_version ); ?></td><td><code><?php echo esc_html( $csy_log->categories ); ?></code></td></tr><?php endforeach; else : ?><tr><td colspan="4" class="csy-log__empty"><?php esc_html_e( 'There are no entries yet.', 'cookie-setting-yes' ); ?></td></tr><?php endif; ?></tbody></table></div></div>
				</section>

				<section id="csy-checks" class="csy-panel csy-checks">
					<div class="csy-panel__heading"><div><p><?php esc_html_e( 'Before publication', 'cookie-setting-yes' ); ?></p><h2><?php esc_html_e( 'Checking readiness', 'cookie-setting-yes' ); ?></h2></div><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span></div>
					<div class="csy-checks__summary <?php echo $verification['complete'] ? 'is-ready' : ''; ?>" data-csy-verification data-csy-automatic-ready="<?php echo $verification['automatic_ready'] ? 'true' : 'false'; ?>">
						<span class="dashicons <?php echo $verification['complete'] ? 'dashicons-yes-alt' : 'dashicons-clipboard'; ?>" aria-hidden="true"></span>
						<div><strong><?php echo $verification['complete'] ? esc_html__( 'Verification completed', 'cookie-setting-yes' ) : esc_html__( 'The check is not yet completed', 'cookie-setting-yes' ); ?></strong><span data-csy-verification-status><?php echo esc_html( $csy_verification_status ); ?></span></div>
					</div>
					<div class="csy-checks__group">
						<div class="csy-checks__group-heading"><div><strong><?php esc_html_e( 'Automatic checks', 'cookie-setting-yes' ); ?></strong><span><?php esc_html_e( 'The state is calculated based on the saved settings fields.', 'cookie-setting-yes' ); ?></span></div></div>
						<ul class="csy-checks__list csy-checks__list--automatic">
							<?php foreach ( $verification['automatic'] as $csy_check ) : ?><li class="<?php echo $csy_check['ready'] ? 'is-ready' : 'is-pending'; ?>"><span class="csy-checks__status dashicons <?php echo $csy_check['ready'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span><div><strong><?php echo esc_html( $csy_check['label'] ); ?></strong><span><?php echo esc_html( $csy_check['text'] ); ?></span></div><a href="#<?php echo esc_attr( $csy_check['anchor'] ); ?>"><?php esc_html_e( 'Open', 'cookie-setting-yes' ); ?></a></li><?php endforeach; ?>
						</ul>
					</div>
					<div class="csy-checks__group">
						<div class="csy-checks__group-heading"><div><strong><?php esc_html_e( 'Manual scenarios', 'cookie-setting-yes' ); ?></strong><span><?php esc_html_e( 'Execute each scenario on the site and mark only after actual verification.', 'cookie-setting-yes' ); ?></span></div><span class="csy-checks__count" data-csy-manual-count><?php echo esc_html( $csy_manual_count ); ?></span></div>
						<ul class="csy-checks__list csy-checks__list--manual">
							<?php foreach ( $verification['manual'] as $csy_key => $csy_check ) : ?><li class="<?php echo $csy_check['checked'] ? 'is-ready' : ''; ?>"><label class="csy-checks__manual"><input type="checkbox" name="csy[manual_checks][]" value="<?php echo esc_attr( $csy_key ); ?>" data-csy-manual-check <?php checked( $csy_check['checked'] ); ?>><span class="csy-checks__checkbox dashicons dashicons-yes" aria-hidden="true"></span><span><strong><?php echo esc_html( $csy_check['label'] ); ?></strong><small><?php echo esc_html( $csy_check['text'] ); ?></small></span></label></li><?php endforeach; ?>
						</ul>
					</div>
					<div class="csy-checks__footer"><span><?php esc_html_e( 'Marks are saved separately from automatic checking. If you change the conditions, services or text of the consent, they will be reset.', 'cookie-setting-yes' ); ?></span><button type="submit" name="csy[save_verification]" value="1" class="button button-secondary"><?php esc_html_e( 'Save marks', 'cookie-setting-yes' ); ?></button></div>
				</section>
			</aside>
			</div>
		</div>
		<div class="csy-admin__save"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save changes', 'cookie-setting-yes' ); ?></button><span><?php esc_html_e( 'Changes are applied after saving.', 'cookie-setting-yes' ); ?></span></div>
	</form>
	<?php if ( 'pro' === CSY_EDITION ) : ?>
	<form id="csy-clear-data-consent-log" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="csy-log-action-form"><input type="hidden" name="action" value="csy_clear_data_consent_log"><?php wp_nonce_field( 'csy_clear_data_consent_log' ); ?></form>
	<form id="csy-clear-consent-log" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="csy-log-action-form"><input type="hidden" name="action" value="csy_clear_log"><?php wp_nonce_field( 'csy_clear_log' ); ?></form>
	<?php endif; ?>
</div>
