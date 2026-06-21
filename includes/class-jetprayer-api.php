<?php
/**
 * AlAdhan API sync engine for JetPrayer.
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

class JetPrayer_API {

	private static $api_base = 'https://api.aladhan.com/v1';

	/**
	 * Single source of truth for AlAdhan calculation method IDs and names.
	 * Used by the admin settings UI, the shortcode/block "method" override,
	 * and the REST/editor localization so the ID list never drifts between them.
	 *
	 * @return array<int, string> Map of method_id => human-readable name.
	 */
	public static function get_calculation_methods() {
		return array(
			0  => __( 'Jafari / Shia Ithna-Ashari', 'jetprayer' ),
			1  => __( 'University of Islamic Sciences, Karachi', 'jetprayer' ),
			2  => __( 'Islamic Society of North America', 'jetprayer' ),
			3  => __( 'Muslim World League', 'jetprayer' ),
			4  => __( 'Umm Al-Qura University, Makkah', 'jetprayer' ),
			5  => __( 'Egyptian General Authority of Survey', 'jetprayer' ),
			7  => __( 'Institute of Geophysics, University of Tehran', 'jetprayer' ),
			8  => __( 'Gulf Region', 'jetprayer' ),
			9  => __( 'Kuwait', 'jetprayer' ),
			10 => __( 'Qatar', 'jetprayer' ),
			11 => __( 'Majlis Ugama Islam Singapura, Singapore', 'jetprayer' ),
			12 => __( 'Union Organization islamic de France', 'jetprayer' ),
			13 => __( 'Diyanet İşleri Başkanlığı, Turkey', 'jetprayer' ),
			14 => __( 'Spiritual Administration of Muslims of Russia', 'jetprayer' ),
			15 => __( 'Moonsighting Committee Worldwide', 'jetprayer' ),
			16 => __( 'Dubai (Experimental)', 'jetprayer' ),
			17 => __( 'JAKIM, Malaysia', 'jetprayer' ),
			18 => __( 'Tunisia', 'jetprayer' ),
			19 => __( 'Algeria', 'jetprayer' ),
			20 => __( 'KEMENAG, Indonesia', 'jetprayer' ),
			21 => __( 'Morocco', 'jetprayer' ),
			22 => __( 'Comunidade Islamica de Lisboa', 'jetprayer' ),
			23 => __( 'Ministry of Awqaf, Jordan', 'jetprayer' ),
		);
	}

	/**
	 * Sync prayer times for a specific year and location settings.
	 *
	 * @param array $settings Plugin settings containing location credentials.
	 * @param int   $year     Year to sync.
	 * @return array|WP_Error Array containing count of synced days on success, WP_Error on failure.
	 */
	public static function sync_year( $settings, $year ) {
		$type      = isset( $settings['type'] ) ? sanitize_text_field( $settings['type'] ) : 'city';
		$method    = isset( $settings['method'] ) && '' !== $settings['method'] ? intval( $settings['method'] ) : null;
		if ( null === $method ) {
			return new WP_Error( 'invalid_method', __( 'Lütfen geçerli bir hesaplama yöntemi seçin. / Please select a valid calculation method.', 'jetprayer' ) );
		}
		$school    = isset( $settings['school'] ) ? intval( $settings['school'] ) : 0;
		$timezone  = isset( $settings['timezone'] ) ? sanitize_text_field( $settings['timezone'] ) : '';

		$query_args = array(
			'method' => $method,
			'school' => $school,
		);

		if ( ! empty( $timezone ) ) {
			$query_args['timezone'] = $timezone;
		}

		if ( 'coords' === $type ) {
			$latitude  = isset( $settings['latitude'] ) ? floatval( $settings['latitude'] ) : 0.0;
			$longitude = isset( $settings['longitude'] ) ? floatval( $settings['longitude'] ) : 0.0;

			$query_args['latitude']  = $latitude;
			$query_args['longitude'] = $longitude;
			$url = self::$api_base . '/calendar/' . $year;
		} else {
			$city    = isset( $settings['city'] ) ? sanitize_text_field( $settings['city'] ) : '';
			$country = isset( $settings['country'] ) ? sanitize_text_field( $settings['country'] ) : '';

			if ( empty( $city ) || empty( $country ) ) {
				return new WP_Error( 'invalid_location', __( 'Please provide a valid city and country.', 'jetprayer' ) );
			}

			$query_args['city']    = $city;
			$query_args['country'] = $country;
			$url = self::$api_base . '/calendarByCity/' . $year;
		}

		$request_url = add_query_arg( $query_args, $url );

		// Execute request using WordPress native HTTP API
		$response = wp_remote_get( $request_url, array(
			'timeout'    => 30,
			'user-agent' => 'WordPress/JetPrayer-' . JETPRAYER_VERSION,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP Response code */
					__( 'AlAdhan API returned response code %d', 'jetprayer' ),
					$response_code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) || empty( $decoded['data'] ) ) {
			return new WP_Error( 'api_invalid_response', __( 'Invalid response structure from AlAdhan API.', 'jetprayer' ) );
		}

		$data = $decoded['data'];
		$synced_count = 0;

		// The data could be nested by month numbers "1" - "12"
		if ( self::is_assoc( $data ) ) {
			foreach ( $data as $month_num => $month_days ) {
				if ( is_array( $month_days ) ) {
					$synced_count += self::process_days_array( $month_days, $method, $city, $country );
				}
			}
		} elseif ( is_array( $data ) ) {
			$synced_count += self::process_days_array( $data, $method, $city, $country );
		}

		if ( $synced_count > 0 ) {
			update_option( 'jetprayer_last_sync', current_time( 'mysql' ) );
		}

		return array(
			'success' => true,
			'count'   => $synced_count,
		);
	}

	/**
	 * Check if array is associative.
	 */
	private static function is_assoc( array $arr ) {
		if ( array() === $arr ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	/**
	 * Process and save days array to local database.
	 */
	private static function process_days_array( array $days, $method_id, $city = '', $country = '' ) {
		$count = 0;
		foreach ( $days as $day_data ) {
			if ( empty( $day_data['timings'] ) || empty( $day_data['date']['gregorian']['date'] ) ) {
				continue;
			}

			// Clean timestamps or timezones out of timing strings (e.g. "05:14 (EEST)" -> "05:14")
			$raw_timings = $day_data['timings'];
			$cleaned_timings = array();
			foreach ( $raw_timings as $key => $val ) {
				$cleaned_timings[ strtolower( $key ) ] = self::clean_time_string( $val );
			}

			// Reformat gregorian date from DD-MM-YYYY to YYYY-MM-DD
			$greg_date_raw = $day_data['date']['gregorian']['date'];
			$date_parts = explode( '-', $greg_date_raw );
			if ( 3 !== count( $date_parts ) ) {
				continue;
			}
			$formatted_date = sprintf( '%04d-%02d-%02d', intval( $date_parts[2] ), intval( $date_parts[1] ), intval( $date_parts[0] ) );

			// Get Hijri date string
			$hijri_day    = isset( $day_data['date']['hijri']['day'] ) ? $day_data['date']['hijri']['day'] : '';
			$hijri_month  = isset( $day_data['date']['hijri']['month']['en'] ) ? $day_data['date']['hijri']['month']['en'] : '';
			$hijri_year   = isset( $day_data['date']['hijri']['year'] ) ? $day_data['date']['hijri']['year'] : '';
			$hijri_string = trim( "$hijri_day $hijri_month $hijri_year" );

			$db_data = array(
				'prayer_date' => $formatted_date,
				'method_id'   => $method_id,
				'city'        => $city,
				'country'     => $country,
				'fajr'        => isset( $cleaned_timings['fajr'] ) ? $cleaned_timings['fajr'] : '',
				'sunrise'     => isset( $cleaned_timings['sunrise'] ) ? $cleaned_timings['sunrise'] : '',
				'dhuhr'       => isset( $cleaned_timings['dhuhr'] ) ? $cleaned_timings['dhuhr'] : '',
				'asr'         => isset( $cleaned_timings['asr'] ) ? $cleaned_timings['asr'] : '',
				'sunset'      => isset( $cleaned_timings['sunset'] ) ? $cleaned_timings['sunset'] : '',
				'maghrib'     => isset( $cleaned_timings['maghrib'] ) ? $cleaned_timings['maghrib'] : '',
				'isha'        => isset( $cleaned_timings['isha'] ) ? $cleaned_timings['isha'] : '',
				'imsak'       => isset( $cleaned_timings['imsak'] ) ? $cleaned_timings['imsak'] : '',
				'midnight'    => isset( $cleaned_timings['midnight'] ) ? $cleaned_timings['midnight'] : '',
				'hijri_date'  => $hijri_string,
				'is_custom'   => 0, // Fresh sync gets marked as original timing
			);

			$saved = JetPrayer_DB::upsert_timing( $db_data );
			if ( $saved ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Strip timezone or extra notation from timing strings (e.g. "05:14 (EEST)" -> "05:14")
	 */
	private static function clean_time_string( $time_str ) {
		// Regex to capture HH:MM pattern
		if ( preg_match( '/\b\d{2}:\d{2}\b/', $time_str, $matches ) ) {
			return $matches[0];
		}
		return sanitize_text_field( $time_str );
	}
}
