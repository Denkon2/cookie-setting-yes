<?php
/**
 * Plugin Name: Cookie Setting Yes
 * Description: Cookie and personal data consent management for 152-FZ, GDPR, and other privacy frameworks.
 * Version: 0.5.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Denkon
 * Author URI: https://denkon.ru
 * Plugin URI: https://denkon.ru/quick-start/cookie-setting-yes-soglasie-na-obrabotku-dannyx
 * Text Domain: cookie-setting-yes
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

// Prevent a second copy from booting when an old Free and a Pro package overlap.
if ( defined( 'CSY_LOADED' ) ) {
	return;
}
define( 'CSY_LOADED', true );

define( 'CSY_VERSION', '0.5.0' );
define( 'CSY_FILE', __FILE__ );
define( 'CSY_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSY_URL', plugin_dir_url( __FILE__ ) );
define( 'CSY_EDITION', 'free' );
define( 'CSY_PRO_URL', 'https://denkon.ru/quick-start/cookie-setting-yes-soglasie-na-obrabotku-dannyx' );

require_once CSY_DIR . 'includes/class-plugin.php';
require_once CSY_DIR . 'includes/class-activator.php';

register_activation_hook( CSY_FILE, array( 'Cookie_Setting_Yes\\Activator', 'activate' ) );

Cookie_Setting_Yes\Plugin::instance();
