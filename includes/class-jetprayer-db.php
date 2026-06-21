<?php
/**
 * Database abstraction layer for JetPrayer.
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

class JetPrayer_DB {

	/**
	 * Get the table name with proper prefix.
	 *
	 * @return string Table name.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'jetprayer_times';
	}

	/**
	 * Fetch timings for a specific date and calculation method.
	 *
	 * @param string $date      Date string (YYYY-MM-DD).
	 * @param int    $method_id Calculation method ID.
	 * @return array|null Timings array or null.
	 */
	public static function get_timings_for_date( $date, $method_id = null, $city = '', $country = '' ) {
		global $wpdb;
		$table = self::get_table_name();

		if ( empty( $city ) ) {
			$city = get_option( 'jetprayer_city', '' );
		}
		if ( empty( $country ) ) {
			$country = get_option( 'jetprayer_country', '' );
		}

		$city_normalized    = trim( strtolower( $city ) );
		$country_normalized = trim( strtolower( $country ) );

		if ( 'all' === $method_id || 'any' === $method_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_row( $wpdb->prepare(
				'SELECT * FROM %i WHERE prayer_date = %s AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s LIMIT 1',
				$table,
				$date,
				$city_normalized,
				$country_normalized
			), ARRAY_A );
		} else {
			if ( null === $method_id || '' === $method_id ) {
				$saved_method = get_option( 'jetprayer_method' );
				if ( ! empty( $saved_method ) ) {
					$method_id = intval( $saved_method );
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$db_method = $wpdb->get_var( $wpdb->prepare(
						'SELECT method_id FROM %i WHERE TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s LIMIT 1',
						$table,
						$city_normalized,
						$country_normalized
					) );
					$method_id = ! empty( $db_method ) ? intval( $db_method ) : 13;
				}
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_row( $wpdb->prepare(
				'SELECT * FROM %i WHERE prayer_date = %s AND method_id = %d AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s LIMIT 1',
				$table,
				$date,
				$method_id,
				$city_normalized,
				$country_normalized
			), ARRAY_A );
		}
	}

	/**
	 * Fetch timings for a range of dates and a calculation method.
	 *
	 * @param string $start_date Start date (YYYY-MM-DD).
	 * @param string $end_date   End date (YYYY-MM-DD).
	 * @param int    $method_id  Calculation method ID.
	 * @return array Array of timing rows.
	 */
	public static function get_timings_for_range( $start_date, $end_date, $method_id = null, $city = '', $country = '' ) {
		global $wpdb;
		$table = self::get_table_name();

		if ( empty( $city ) ) {
			$city = get_option( 'jetprayer_city', '' );
		}
		if ( empty( $country ) ) {
			$country = get_option( 'jetprayer_country', '' );
		}

		$city_normalized    = trim( strtolower( $city ) );
		$country_normalized = trim( strtolower( $country ) );

		if ( 'all' === $method_id || 'any' === $method_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare(
				'SELECT * FROM %i WHERE prayer_date >= %s AND prayer_date <= %s AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s GROUP BY prayer_date ORDER BY prayer_date ASC',
				$table,
				$start_date,
				$end_date,
				$city_normalized,
				$country_normalized
			), ARRAY_A );
		} else {
			if ( null === $method_id || '' === $method_id ) {
				$saved_method = get_option( 'jetprayer_method' );
				if ( ! empty( $saved_method ) ) {
					$method_id = intval( $saved_method );
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$db_method = $wpdb->get_var( $wpdb->prepare(
						'SELECT method_id FROM %i WHERE TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s LIMIT 1',
						$table,
						$city_normalized,
						$country_normalized
					) );
					$method_id = ! empty( $db_method ) ? intval( $db_method ) : 13;
				}
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare(
				'SELECT * FROM %i WHERE prayer_date >= %s AND prayer_date <= %s AND method_id = %d AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s ORDER BY prayer_date ASC',
				$table,
				$start_date,
				$end_date,
				$method_id,
				$city_normalized,
				$country_normalized
			), ARRAY_A );
		}
	}

	/**
	 * Save or update timing row with calculation method.
	 *
	 * @param array $data Input timings data.
	 * @return bool True if successfully saved/updated, false otherwise.
	 */
	public static function upsert_timing( $data ) {
		global $wpdb;
		$table = self::get_table_name();

		if ( empty( $data['prayer_date'] ) ) {
			return false;
		}

		if ( isset( $data['method_id'] ) && '' !== $data['method_id'] && null !== $data['method_id'] ) {
			$method_id = intval( $data['method_id'] );
		} else {
			$saved_method = get_option( 'jetprayer_method' );
			$method_id = ( ! empty( $saved_method ) ) ? intval( $saved_method ) : 13;
		}

		$city    = isset( $data['city'] ) ? $data['city'] : get_option( 'jetprayer_city', '' );
		$country = isset( $data['country'] ) ? $data['country'] : get_option( 'jetprayer_country', '' );

		// Check if record exists
		$existing = self::get_timings_for_date( $data['prayer_date'], $method_id, $city, $country );

		if ( $existing ) {
			// If existing timing is custom and we are not forcing an overwrite, do not update.
			if ( ! empty( $existing['is_custom'] ) && empty( $data['force_overwrite'] ) ) {
				return false;
			}

			// Update existing record
			$where = array(
				'prayer_date' => $data['prayer_date'],
				'method_id'   => $method_id,
				'city'        => $city,
				'country'     => $country,
			);
			$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );
			$where_format = array( '%s', '%d', '%s', '%s' );

			$update_data = array(
				'fajr'       => sanitize_text_field( $data['fajr'] ),
				'sunrise'    => sanitize_text_field( $data['sunrise'] ),
				'dhuhr'      => sanitize_text_field( $data['dhuhr'] ),
				'asr'        => sanitize_text_field( $data['asr'] ),
				'sunset'     => sanitize_text_field( $data['sunset'] ),
				'maghrib'    => sanitize_text_field( $data['maghrib'] ),
				'isha'       => sanitize_text_field( $data['isha'] ),
				'imsak'      => sanitize_text_field( $data['imsak'] ),
				'midnight'   => sanitize_text_field( $data['midnight'] ),
				'hijri_date' => sanitize_text_field( $data['hijri_date'] ),
				'is_custom'  => isset( $data['is_custom'] ) ? intval( $data['is_custom'] ) : 0,
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, $update_data, $where, $format, $where_format );
		} else {
			// Insert new record
			$format = array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );
			$insert_data = array(
				'prayer_date' => sanitize_text_field( $data['prayer_date'] ),
				'method_id'   => $method_id,
				'city'        => sanitize_text_field( $city ),
				'country'     => sanitize_text_field( $country ),
				'fajr'        => sanitize_text_field( $data['fajr'] ),
				'sunrise'     => sanitize_text_field( $data['sunrise'] ),
				'dhuhr'       => sanitize_text_field( $data['dhuhr'] ),
				'asr'         => sanitize_text_field( $data['asr'] ),
				'sunset'      => sanitize_text_field( $data['sunset'] ),
				'maghrib'     => sanitize_text_field( $data['maghrib'] ),
				'isha'        => sanitize_text_field( $data['isha'] ),
				'imsak'       => sanitize_text_field( $data['imsak'] ),
				'midnight'    => sanitize_text_field( $data['midnight'] ),
				'hijri_date'  => sanitize_text_field( $data['hijri_date'] ),
				'is_custom'   => isset( $data['is_custom'] ) ? intval( $data['is_custom'] ) : 0,
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $table, $insert_data, $format );
		}

		return true;
	}

	/**
	 * Get the distinct calculation method IDs that currently have at least
	 * one synced row in the database table.
	 *
	 * @return array<int> Sorted list of method IDs.
	 */
	public static function get_synced_method_ids() {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids   = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT method_id FROM %i ORDER BY method_id ASC', $table ) );
		return array_map( 'intval', $ids );
	}

	/**
	 * Clear all stored timings in the database table.
	 */
	public static function clear_all_timings() {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table ) );
	}

	/**
	 * Get the distinct list of synced cities and countries in the database.
	 *
	 * @return array Array of associative arrays containing city and country.
	 */
	public static function get_synced_locations() {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT city, country, method_id FROM %i WHERE city != \'\' AND country != \'\' ORDER BY city ASC, method_id ASC',
				$table
			),
			ARRAY_A
		);
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get the distinct list of synced countries in the database.
	 *
	 * @return array List of country names.
	 */
	public static function get_synced_countries() {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT country FROM %i WHERE country != \'\' ORDER BY country ASC', $table ) );
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get the distinct list of synced cities for a specific country.
	 *
	 * @param string $country Country name.
	 * @return array List of city names.
	 */
	public static function get_synced_cities( $country = '' ) {
		global $wpdb;
		$table = self::get_table_name();
		if ( ! empty( $country ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_col( $wpdb->prepare(
				'SELECT DISTINCT city FROM %i WHERE TRIM(LOWER(country)) = %s AND city != \'\' ORDER BY city ASC',
				$table,
				trim( strtolower( $country ) )
			) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT city FROM %i WHERE city != \'\' ORDER BY city ASC', $table ) );
		}
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Delete timings for multiple dates for a specific method, city, and country.
	 *
	 * @param array  $dates     List of date strings (YYYY-MM-DD).
	 * @param int    $method_id Calculation method ID.
	 * @param string $city      City name.
	 * @param string $country   Country name.
	 * @return int|bool Number of deleted rows or false on failure.
	 */
	public static function delete_timings_bulk( $dates, $method_id, $city, $country ) {
		global $wpdb;
		$table = self::get_table_name();
		if ( empty( $dates ) || ! is_array( $dates ) ) {
			return false;
		}

		$city_normalized    = trim( strtolower( $city ) );
		$country_normalized = trim( strtolower( $country ) );

		$placeholders = implode( ',', array_fill( 0, count( $dates ), '%s' ) );
		$params = array_merge(
			array( $table, intval( $method_id ), $city_normalized, $country_normalized ),
			$dates
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE method_id = %d AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s AND prayer_date IN ($placeholders)", ...$params ) );
	}

	/**
	 * Delete timings for an entire year for a specific method, city, and country.
	 *
	 * @param int    $year      Year to delete (e.g. 2026).
	 * @param int    $method_id Calculation method ID.
	 * @param string $city      City name.
	 * @param string $country   Country name.
	 * @return int|bool Number of deleted rows or false on failure.
	 */
	public static function delete_year_timings( $year, $method_id, $city, $country ) {
		global $wpdb;
		$table = self::get_table_name();
		$city_normalized    = trim( strtolower( $city ) );
		$country_normalized = trim( strtolower( $country ) );
		$year_pattern       = intval( $year ) . '-%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare(
			'DELETE FROM %i WHERE method_id = %d AND TRIM(LOWER(city)) = %s AND TRIM(LOWER(country)) = %s AND prayer_date LIKE %s',
			$table,
			intval( $method_id ),
			$city_normalized,
			$country_normalized,
			$year_pattern
		) );
	}

	/**
	 * Recursively ensure all string values in an array or object are valid UTF-8.
	 *
	 * @param mixed $data Data to clean.
	 * @return mixed Cleaned data.
	 */
	public static function clean_utf8( $data ) {
		if ( is_string( $data ) ) {
			if ( ! mb_check_encoding( $data, 'UTF-8' ) ) {
				// Try converting from ISO-8859-1 (latin1) to UTF-8
				return mb_convert_encoding( $data, 'UTF-8', 'ISO-8859-1' );
			}
			// Replace any invalid UTF-8 sequences with fallback/empty
			return mb_convert_encoding( $data, 'UTF-8', 'UTF-8' );
		} elseif ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::clean_utf8( $value );
			}
		} elseif ( is_object( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data->$key = self::clean_utf8( $value );
			}
		}
		return $data;
	}
}
