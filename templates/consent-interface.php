<?php
defined( 'ABSPATH' ) || exit;
$csy_button_reject    = 'pro' === CSY_EDITION ? $options['pro_button_reject'] : __( 'Reject optional', 'cookie-setting-yes' );
$csy_button_customize = 'pro' === CSY_EDITION ? $options['pro_button_customize'] : __( 'Customize', 'cookie-setting-yes' );
$csy_button_accept    = 'pro' === CSY_EDITION ? $options['pro_button_accept'] : __( 'Accept all', 'cookie-setting-yes' );
$csy_modal_title      = 'pro' === CSY_EDITION ? $options['pro_modal_title'] : __( 'Cookie settings', 'cookie-setting-yes' );
?>
<section id="csy-banner" class="csy-banner csy-banner--horizontal-<?php echo esc_attr( $options['banner_horizontal'] ); ?> csy-banner--vertical-<?php echo esc_attr( $options['banner_vertical'] ); ?>" aria-label="<?php esc_attr_e( 'Cookie settings', 'cookie-setting-yes' ); ?>" hidden>
	<div class="csy-banner__content">
		<h2><?php echo esc_html( $options['banner_title'] ); ?></h2>
		<p><?php echo esc_html( $options['banner_text'] ); ?></p>
		<p class="csy-banner__links">
			<?php if ( $options['privacy_url'] ) : ?><a href="<?php echo esc_url( $options['privacy_url'] ); ?>"><?php esc_html_e( 'Privacy Policy', 'cookie-setting-yes' ); ?></a><?php endif; ?>
			<?php if ( $options['cookie_policy_url'] ) : ?><a href="<?php echo esc_url( $options['cookie_policy_url'] ); ?>"><?php esc_html_e( 'Cookie Policy', 'cookie-setting-yes' ); ?></a><?php endif; ?>
		</p>
	</div>
	<div class="csy-banner__actions">
		<button type="button" class="csy-button csy-button--secondary" data-csy-action="reject"><?php echo esc_html( $csy_button_reject ); ?></button>
		<button type="button" class="csy-button csy-button--secondary" data-csy-open><?php echo esc_html( $csy_button_customize ); ?></button>
		<button type="button" class="csy-button" data-csy-action="accept"><?php echo esc_html( $csy_button_accept ); ?></button>
	</div>
</section>

<dialog id="csy-preferences" class="csy-modal" aria-labelledby="csy-modal-title">
	<form method="dialog" class="csy-modal__inner">
		<div class="csy-modal__header"><h2 id="csy-modal-title"><?php echo esc_html( $csy_modal_title ); ?></h2><button type="button" class="csy-close" data-csy-close aria-label="<?php esc_attr_e( 'Close', 'cookie-setting-yes' ); ?>">&times;</button></div>
		<p><?php esc_html_e( 'Choose which optional categories may be used. You can change your choice at any time.', 'cookie-setting-yes' ); ?></p>
		<div class="csy-categories">
			<?php foreach ( $categories as $csy_slug => $csy_category ) : ?>
				<label class="csy-category">
					<span><strong><?php echo esc_html( $csy_category['label'] ); ?></strong><small><?php echo esc_html( $csy_category['description'] ); ?></small></span>
					<input type="checkbox" data-csy-category="<?php echo esc_attr( $csy_slug ); ?>" <?php checked( ! empty( $csy_category['required'] ) ); ?> <?php disabled( ! empty( $csy_category['required'] ) ); ?>>
				</label>
			<?php endforeach; ?>
		</div>
		<div class="csy-modal__actions"><button type="button" class="csy-button csy-button--secondary" data-csy-action="reject"><?php echo esc_html( $csy_button_reject ); ?></button><button type="button" class="csy-button" data-csy-action="save"><?php esc_html_e( 'Save selection', 'cookie-setting-yes' ); ?></button></div>
	</form>
</dialog>
<button type="button" id="csy-reopen" class="csy-reopen" aria-label="<?php esc_attr_e( 'Open cookie settings', 'cookie-setting-yes' ); ?>" hidden>&#9881;</button>
