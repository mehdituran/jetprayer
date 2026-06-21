<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wordpress.org/plugins/jetprayer/
 * @since      1.0.0
 *
 * @package    JetPrayer
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Drop the custom prayer times table
$jetprayer_table_name = $wpdb->prefix . 'jetprayer_times';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $jetprayer_table_name ) );

// 2. Delete all saved plugin options from wp_options
$jetprayer_options = array(
	'jetprayer_type',
	'jetprayer_city',
	'jetprayer_country',
	'jetprayer_latitude',
	'jetprayer_longitude',
	'jetprayer_method',
	'jetprayer_school',
	'jetprayer_timezone',
	'jetprayer_last_sync',
	'jetprayer_db_version',
	'jetprayer_display_card',
	'jetprayer_display_grid',
	'jetprayer_display_slider',
	'jetprayer_display_ticker',
	'jetprayer_display_modal',
);

foreach ( $jetprayer_options as $jetprayer_option ) {
	delete_option( $jetprayer_option );
}

// 3. Delete any active transients
delete_transient( 'jetprayer_sync_lock' );
