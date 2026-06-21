<?php
/**
 * REST API routes for JetPrayer.
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

class JetPrayer_REST {

	/**
	 * Register REST hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$namespace = 'jetprayer/v1';

		// GET timings for admin CRUD list
		register_rest_route( $namespace, '/timings', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_timings' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'start_date' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => array( $this, 'validate_date' ),
				),
				'end_date'   => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => array( $this, 'validate_date' ),
				),
				'method_id'  => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) {
						if ( 'all' === $val || 'any' === $val ) {
							return 'all';
						}
						return '' === $val ? '' : intval( $val );
					},
				),
				'city'       => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'country'    => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// GET list of calculation methods that already have synced data
		register_rest_route( $namespace, '/synced-methods', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_synced_methods' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// GET distinct list of synced cities/countries
		register_rest_route( $namespace, '/synced-locations', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_synced_locations_callback' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// POST sync trigger
		register_rest_route( $namespace, '/sync', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_sync' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
			'args'                => array(
				'year' => array(
					'required'          => true,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'type' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'city' => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'country' => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'latitude' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return floatval( $val ); },
				),
				'longitude' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return floatval( $val ); },
				),
				'method' => array(
					'required'          => true,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'school' => array(
					'required'          => true,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'timezone' => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'bulk_sync' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return intval( $val ) ? 1 : 0; },
				),
			),
		) );

		// POST update single date timings (CRUD update)
		register_rest_route( $namespace, '/update', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_update' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
			'args'                => array(
				'prayer_date' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => array( $this, 'validate_date' ),
				),
				'method_id'   => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'fajr'        => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'sunrise'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'dhuhr'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'asr'         => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'sunset'      => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'maghrib'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'isha'        => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'imsak'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'midnight'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'hijri_date'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'city'        => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'country'     => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// POST bulk delete timings (CRUD delete)
		register_rest_route( $namespace, '/delete', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_delete' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
			'args'                => array(
				'dates'     => array(
					'required'          => false,
					'validate_callback' => function( $val ) { return is_array( $val ); },
				),
				'delete_all_year' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return intval( $val ) ? 1 : 0; },
				),
				'year' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'method_id' => array(
					'required'          => false,
					'sanitize_callback' => function( $val ) { return intval( $val ); },
				),
				'city'      => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'country'   => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );


		// POST update display (toggle/color/style) settings for a layout
		register_rest_route( $namespace, '/display-settings', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_display_settings' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
			'args'                => array(
				'layout'   => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'settings' => array(
					'required' => true,
				),
			),
		) );
	}

	/**
	 * Check permission callback.
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate date query parameter format (YYYY-MM-DD).
	 */
	public function validate_date( $value, $request, $param ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
	}

	/**
	 * GET Timings handler.
	 */
	public function get_timings( WP_REST_Request $request ) {
		try {
			$start_date = $request->get_param( 'start_date' );
			$end_date   = $request->get_param( 'end_date' );
			$method_id  = $request->get_param( 'method_id' );
			$city       = $request->get_param( 'city' );
			$country    = $request->get_param( 'country' );

			if ( 'all' === $method_id || 'any' === $method_id ) {
				$method_id = 'all';
			} elseif ( null === $method_id || '' === $method_id ) {
				$saved_method = get_option( 'jetprayer_method' );
				$method_id = ( ! empty( $saved_method ) ) ? intval( $saved_method ) : 13;
			} else {
				$method_id = intval( $method_id );
			}

			if ( empty( $city ) ) {
				$city = get_option( 'jetprayer_city', '' );
			}
			if ( empty( $country ) ) {
				$country = get_option( 'jetprayer_country', '' );
			}

			$results = JetPrayer_DB::get_timings_for_range( $start_date, $end_date, $method_id, $city, $country );
			$results = JetPrayer_DB::clean_utf8( $results );

			return new WP_REST_Response( array(
				'success' => true,
				'data'    => $results,
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Get the list of calculation methods (id + name) that already have at
	 * least one synced row, so the admin UI can show "what's in the DB"
	 * separately from "what's currently selected as default".
	 */
	public function get_synced_methods() {
		try {
			$ids          = JetPrayer_DB::get_synced_method_ids();
			$method_names = JetPrayer_API::get_calculation_methods();

			$data = array();
			foreach ( $ids as $id ) {
				$data[] = array(
					'id'   => $id,
					'name' => isset( $method_names[ $id ] ) ? $method_names[ $id ] : __( 'Unknown', 'jetprayer' ),
				);
			}

			return new WP_REST_Response( array(
				'success' => true,
				'data'    => $data,
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Handle API Sync trigger.
	 */
	public function handle_sync( WP_REST_Request $request ) {
		try {
			$year = intval( $request->get_param( 'year' ) );
			if ( $year < 2000 || $year > 2100 ) {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => __( 'Invalid year value.', 'jetprayer' ),
				), 400 );
			}

			// Rate limit check: Allow sync once every 1 minute
			$bulk_sync     = (int) $request->get_param( 'bulk_sync' );
			$transient_key = 'jetprayer_sync_lock';
			$lock          = get_transient( $transient_key );
			if ( $lock && ! $bulk_sync ) {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => __( 'Senkronizasyon limiti aşıldı. Lütfen tekrar senkronize etmeden önce 1 dakika bekleyin. / Rate limit exceeded. Please wait 1 minute before syncing again.', 'jetprayer' ),
				), 429 );
			}

			$settings = array(
				'type'      => sanitize_text_field( $request->get_param( 'type' ) ),
				'city'      => sanitize_text_field( $request->get_param( 'city' ) ),
				'country'   => sanitize_text_field( $request->get_param( 'country' ) ),
				'latitude'  => floatval( $request->get_param( 'latitude' ) ),
				'longitude' => floatval( $request->get_param( 'longitude' ) ),
				'method'    => intval( $request->get_param( 'method' ) ),
				'school'    => intval( $request->get_param( 'school' ) ),
				'timezone'  => sanitize_text_field( $request->get_param( 'timezone' ) ),
			);

			$sync_result = JetPrayer_API::sync_year( $settings, $year );

			if ( is_wp_error( $sync_result ) ) {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => $sync_result->get_error_message(),
				), 500 );
			}

			// Save settings options for manual sync only
			if ( ! $bulk_sync ) {
				update_option( 'jetprayer_type', $settings['type'] );
				update_option( 'jetprayer_city', $settings['city'] );
				update_option( 'jetprayer_country', $settings['country'] );
				update_option( 'jetprayer_latitude', $settings['latitude'] );
				update_option( 'jetprayer_longitude', $settings['longitude'] );
				update_option( 'jetprayer_method', $settings['method'] );
				update_option( 'jetprayer_school', $settings['school'] );
				update_option( 'jetprayer_timezone', $settings['timezone'] );
			}

			// Update last sync date option
			$last_sync_time = current_time( 'mysql' );
			update_option( 'jetprayer_last_sync', $last_sync_time );

			// Set rate limit lock for 60 seconds (only if not bulk sync)
			if ( ! $bulk_sync ) {
				set_transient( $transient_key, true, 60 );
			}

			return new WP_REST_Response( array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: count of synced dates */
					__( 'Sync completed successfully! %d days imported/updated.', 'jetprayer' ),
					$sync_result['count']
				),
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Handle timings custom update.
	 */
	public function handle_update( WP_REST_Request $request ) {
		try {
			$method_id = $request->get_param( 'method_id' );
			if ( null === $method_id || '' === $method_id ) {
				$saved_method = get_option( 'jetprayer_method' );
				$method_id = ( ! empty( $saved_method ) ) ? intval( $saved_method ) : 13;
			} else {
				$method_id = intval( $method_id );
			}

			$data = array(
				'prayer_date'     => $request->get_param( 'prayer_date' ),
				'method_id'       => $method_id,
				'city'            => $request->get_param( 'city' ),
				'country'         => $request->get_param( 'country' ),
				'fajr'            => $request->get_param( 'fajr' ),
				'sunrise'         => $request->get_param( 'sunrise' ),
				'dhuhr'           => $request->get_param( 'dhuhr' ),
				'asr'             => $request->get_param( 'asr' ),
				'sunset'          => $request->get_param( 'sunset' ),
				'maghrib'         => $request->get_param( 'maghrib' ),
				'isha'            => $request->get_param( 'isha' ),
				'imsak'           => $request->get_param( 'imsak' ),
				'midnight'        => $request->get_param( 'midnight' ),
				'hijri_date'      => $request->get_param( 'hijri_date' ),
				'is_custom'       => 1, // Set custom flag since it is user-saved
				'force_overwrite' => true,
			);

			$result = JetPrayer_DB::upsert_timing( $data );

			if ( ! $result ) {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => __( 'Could not update timing in database.', 'jetprayer' ),
				), 500 );
			}

			return new WP_REST_Response( array(
				'success' => true,
				'message' => __( 'Prayer times updated successfully!', 'jetprayer' ),
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Handle timings custom bulk delete.
	 */
	public function handle_delete( WP_REST_Request $request ) {
		try {
			$dates           = $request->get_param( 'dates' );
			$delete_all_year = (int) $request->get_param( 'delete_all_year' );
			$year            = intval( $request->get_param( 'year' ) );
			$method_id       = $request->get_param( 'method_id' );
			$city            = $request->get_param( 'city' );
			$country         = $request->get_param( 'country' );

			if ( null === $method_id || '' === $method_id ) {
				$saved_method = get_option( 'jetprayer_method' );
				$method_id = ( ! empty( $saved_method ) ) ? intval( $saved_method ) : 13;
			} else {
				$method_id = intval( $method_id );
			}

			if ( empty( $city ) ) {
				$city = get_option( 'jetprayer_city', '' );
			}
			if ( empty( $country ) ) {
				$country = get_option( 'jetprayer_country', '' );
			}

			if ( $delete_all_year ) {
				if ( $year < 2000 || $year > 2100 ) {
					return new WP_REST_Response( array(
						'success' => false,
						'message' => __( 'Invalid year value.', 'jetprayer' ),
					), 400 );
				}

				$result = JetPrayer_DB::delete_year_timings( $year, $method_id, $city, $country );

				if ( false === $result ) {
					return new WP_REST_Response( array(
						'success' => false,
						'message' => __( 'Could not delete year timings from database.', 'jetprayer' ),
					), 500 );
				}

				return new WP_REST_Response( array(
					'success' => true,
					'message' => sprintf(
						/* translators: %d: year */
						__( 'Successfully deleted all timings for the year %d!', 'jetprayer' ),
						$year
					),
				), 200 );
			} else {
				if ( ! is_array( $dates ) || empty( $dates ) ) {
					return new WP_REST_Response( array(
						'success' => false,
						'message' => __( 'No dates provided for deletion.', 'jetprayer' ),
					), 400 );
				}

				$sanitized_dates = array();
				foreach ( $dates as $date ) {
					if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
						$sanitized_dates[] = sanitize_text_field( $date );
					}
				}

				if ( empty( $sanitized_dates ) ) {
					return new WP_REST_Response( array(
						'success' => false,
						'message' => __( 'Invalid date formats provided.', 'jetprayer' ),
					), 400 );
				}

				$result = JetPrayer_DB::delete_timings_bulk( $sanitized_dates, $method_id, $city, $country );

				if ( false === $result ) {
					return new WP_REST_Response( array(
						'success' => false,
						'message' => __( 'Could not delete timings from database.', 'jetprayer' ),
					), 500 );
				}

				return new WP_REST_Response( array(
					'success' => true,
					'message' => sprintf(
						/* translators: %d: count of deleted dates */
						__( 'Successfully deleted %d dates!', 'jetprayer' ),
						count( $sanitized_dates )
					),
				), 200 );
			}
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}



	/**
	 * Handle display (toggle/color/style) settings update for a layout.
	 */
	public function handle_display_settings( WP_REST_Request $request ) {
		try {
			$layout = sanitize_text_field( $request->get_param( 'layout' ) );

			if ( ! in_array( $layout, JetPrayer_Display_Settings::get_layout_keys(), true ) ) {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => __( 'Invalid layout key.', 'jetprayer' ),
				), 400 );
			}

			$raw_settings = $request->get_param( 'settings' );
			$sanitized    = JetPrayer_Display_Settings::sanitize_settings( $layout, $raw_settings );

			update_option( 'jetprayer_display_' . $layout, $sanitized );

			return new WP_REST_Response( array(
				'success' => true,
				'message' => __( 'Display settings saved successfully!', 'jetprayer' ),
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * GET distinct synced locations callback.
	 */
	public function get_synced_locations_callback( WP_REST_Request $request ) {
		try {
			$locations = JetPrayer_DB::get_synced_locations();
			$locations = JetPrayer_DB::clean_utf8( $locations );

			return new WP_REST_Response( array(
				'success' => true,
				'data'    => $locations,
			), 200 );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}
