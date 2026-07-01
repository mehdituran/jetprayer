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

// Both plugins share the same tables and option names. If Pro is currently
// active, dropping the table or clearing those options here would silently
// destroy live data that Pro is still using. Only wipe shared state when Pro
// is not active; otherwise leave the DB alone — the folder is removed by
// WordPress regardless, which is all that is needed to hide it from the list.
$jetprayer_pro_active = in_array(
	'jetprayer-pro/jetprayer-pro.php',
	(array) get_option( 'active_plugins', array() ),
	true
);

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

if ( ! $jetprayer_pro_active ) {
	// Pro is not active — safe to drop shared tables and options.
	$jetprayer_table_name = $wpdb->prefix . 'jetprayer_times';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $jetprayer_table_name ) );

	foreach ( $jetprayer_options as $jetprayer_option ) {
		delete_option( $jetprayer_option );
	}
}

// Always clean up our own transient regardless.
delete_transient( 'jetprayer_sync_lock' );
