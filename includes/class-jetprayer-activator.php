<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://wordpress.org/plugins/jetprayer/
 * @since      1.0.0
 *
 * @package    JetPrayer
 * @subpackage JetPrayer/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JetPrayer_Activator {

	/**
	 * Activate the plugin and build db table.
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'jetprayer_times';
		$charset_collate = $wpdb->get_charset_collate();

		// Drop old unique key if it exists to allow method_id separation
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		if ( $table_exists ) {
			// Check if method_id column exists, if not add it directly via raw query to prevent dbDelta issues
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$column_exists = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'method_id' ) );
			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN method_id int(11) DEFAULT 13 NOT NULL AFTER prayer_date', $table_name ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$city_exists = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'city' ) );
			if ( empty( $city_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN city varchar(100) DEFAULT \'\' NOT NULL AFTER method_id', $table_name ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN country varchar(100) DEFAULT \'\' NOT NULL AFTER city', $table_name ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$index_exists = $wpdb->get_row( $wpdb->prepare(
				'SHOW INDEX FROM %i WHERE Key_name = %s',
				$table_name,
				'prayer_date'
			) );
			if ( $index_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP INDEX prayer_date', $table_name ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_index_exists = $wpdb->get_row( $wpdb->prepare(
				'SHOW INDEX FROM %i WHERE Key_name = %s',
				$table_name,
				'prayer_date_method'
			) );
			if ( $old_index_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP INDEX prayer_date_method', $table_name ) );
			}
		}

		// Custom DB table with indexes for faster lookup and query optimization
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			prayer_date date NOT NULL,
			method_id int(11) DEFAULT 13 NOT NULL,
			city varchar(100) DEFAULT '' NOT NULL,
			country varchar(100) DEFAULT '' NOT NULL,
			fajr varchar(10) NOT NULL,
			sunrise varchar(10) NOT NULL,
			dhuhr varchar(10) NOT NULL,
			asr varchar(10) NOT NULL,
			sunset varchar(10) NOT NULL,
			maghrib varchar(10) NOT NULL,
			isha varchar(10) NOT NULL,
			imsak varchar(10) NOT NULL,
			midnight varchar(10) NOT NULL,
			hijri_date varchar(100) NOT NULL,
			is_custom tinyint(1) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY prayer_date_method_location (prayer_date, method_id, city, country)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Set default settings
		add_option( 'jetprayer_city', '' );
		add_option( 'jetprayer_country', '' );
		add_option( 'jetprayer_method', '' );
		add_option( 'jetprayer_school', '0' ); // 0 = Standard (Shafi, Maliki, Hanbali), 1 = Hanafi
		add_option( 'jetprayer_timezone', '' );
		add_option( 'jetprayer_last_sync', '' );
	}
}
