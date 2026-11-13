<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$csy_options = get_option( 'csy_options', array() );
if ( empty( $csy_options['remove_on_uninstall'] ) ) {
	return;
}

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- The administrator explicitly enabled complete data removal on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}csy_consent_log" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- The administrator explicitly enabled complete data removal on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}csy_data_consent_log" );
delete_option( 'csy_options' );
delete_option( 'csy_db_version' );
