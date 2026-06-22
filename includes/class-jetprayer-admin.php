<?php
/**
 * Admin Panel initialization and menus for JetPrayer.
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

class JetPrayer_Admin {

	/**
	 * Hook admin menus and assets.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register settings page.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'JetPrayer Settings & Timings', 'jetprayer' ),
			'JetPrayer',
			'manage_options',
			'jetprayer',
			array( $this, 'render_admin_page' ),
			'dashicons-clock',
			40
		);
	}

	/**
	 * Enqueue styles and scripts only on our page.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_jetprayer' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'jetprayer-admin-css',
			JETPRAYER_URL . 'admin/css/admin.css',
			array(),
			filemtime( JETPRAYER_PATH . 'admin/css/admin.css' )
		);

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'jetprayer-countries-data',
			JETPRAYER_URL . 'admin/js/countries-data.js',
			array(),
			JETPRAYER_VERSION,
			true
		);

		wp_enqueue_script(
			'jetprayer-admin-js',
			JETPRAYER_URL . 'admin/js/admin.js',
			array( 'jquery', 'jetprayer-countries-data', 'wp-color-picker' ),
			filemtime( JETPRAYER_PATH . 'admin/js/admin.js' ),
			true
		);

		// Localize parameters for secure REST interactions and JS translations
		wp_localize_script( 'jetprayer-admin-js', 'jetprayerAdmin', array(
			'restUrl'        => esc_url_raw( rest_url( 'jetprayer/v1' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'currentCountry' => get_option( 'jetprayer_country', '' ),
			'currentCity'    => get_option( 'jetprayer_city', '' ),
			'methods'        => JetPrayer_API::get_calculation_methods(),
			'i18n'           => array(
				'syncing'       => __( 'Synchronizing with AlAdhan API...', 'jetprayer' ),
				'saving'        => __( 'Saving settings...', 'jetprayer' ),
				'saved'         => __( 'Successfully saved!', 'jetprayer' ),
				'syncSuccess'   => __( 'Synchronization complete!', 'jetprayer' ),
				'error'         => __( 'Something went wrong. Please check console.', 'jetprayer' ),
				'confirmCancel' => __( 'Discard unsaved changes?', 'jetprayer' ),
				'prev'          => __( 'Previous', 'jetprayer' ),
				'next'          => __( 'Next', 'jetprayer' ),
				'loadingYear'   => __( 'Loading entire year. Please wait...', 'jetprayer' ),
				'clickToLoad'   => __( 'Click "Load Month" or "Load Year" to display database rows.', 'jetprayer' ),
				'noMethodData'  => __( 'No database timings found for this calculation method. Please go to Settings & Sync tab, select this method, click Save Settings, and run the Year Synchronization first.', 'jetprayer' ),
				'noSyncedMethods' => __( 'No data synced yet.', 'jetprayer' ),
				'loading'       => __( 'Loading timings...', 'jetprayer' ),
				'failedLoad'    => __( 'Failed to load timings database rows.', 'jetprayer' ),
				// translators: %s: month and year.
				'noDataMonth'   => __( 'No data loaded for %s.', 'jetprayer' ),
				// translators: 1: number of days, 2: month, 3: year.
				'deleteSelectedYearDesc' => __( 'Deletes the selected %1$d days inside %2$s %3$d.', 'jetprayer' ),
				// translators: 1: year, 2: city, 3: country.
				'deleteYearDesc' => __( 'Clears all timings (365 days) for the year %1$d for %2$s, %3$s.', 'jetprayer' ),
				// translators: %d: number of records.
				'confirmDeleteSelected' => __( 'Are you sure you want to delete these %d timing records?', 'jetprayer' ),
				// translators: 1: year, 2: city, 3: country.
				'confirmDeleteYear' => __( 'WARNING: Are you sure you want to delete the ENTIRE year %1$d of timings for %2$s, %3$s?', 'jetprayer' ),
				'deleteSuccess' => __( 'Timings deleted successfully!', 'jetprayer' ),
				'deleteFailed'  => __( 'Failed to delete timings.', 'jetprayer' ),
				'invalidJson'   => __( 'Invalid JSON structure. Root must be a JSON object.', 'jetprayer' ),
				'noLocationsJson' => __( 'No valid locations found in the JSON file.', 'jetprayer' ),
				// translators: %s: error message.
				'failedParseJson' => __( 'Failed to parse JSON file: %s', 'jetprayer' ),
				// translators: %d: total locations count.
				'bulkSyncStarted' => __( 'Bulk sync started. Total locations: %d', 'jetprayer' ),
				'bulkSyncCancelled' => __( 'Bulk sync cancelled by user.', 'jetprayer' ),
				// translators: 1: count of successful syncs, 2: count of failed syncs.
				'bulkSyncComplete' => __( 'Bulk sync completed! Success: %1$d, Failed: %2$d', 'jetprayer' ),
				// translators: 1: city, 2: country, 3: calculation method, 4: year.
				'syncingLocation' => __( 'Syncing %1$s, %2$s (Method: %3$s, Year: %4$s)...', 'jetprayer' ),
				'syncedSuccess' => __( 'Synced successfully.', 'jetprayer' ),
				'apiError'      => __( 'API Error', 'jetprayer' ),
				'loadingCountries' => __( 'Loading countries...', 'jetprayer' ),
				'loadingCities' => __( 'Loading cities...', 'jetprayer' ),
				'loadingMethods' => __( 'Loading methods...', 'jetprayer' ),
				'noSyncedCountries' => __( 'No synced countries', 'jetprayer' ),
				'noSyncedCities' => __( 'No synced cities', 'jetprayer' ),
				'noSyncedMethods' => __( 'No synced methods', 'jetprayer' ),
				'errorCountries' => __( 'Error loading countries', 'jetprayer' ),
				'errorCities'   => __( 'Error loading cities', 'jetprayer' ),
				'errorMethods'  => __( 'Error loading methods', 'jetprayer' ),
				'selectCountryFirst' => __( 'Select country first', 'jetprayer' ),
				'selectCityFirst' => __( 'Select city first', 'jetprayer' ),
				// translators: %s: method name or ID.
				'methodLabel'   => __( 'Method %s', 'jetprayer' ),
				'months'        => array(
					'01' => __( 'January', 'jetprayer' ),
					'02' => __( 'February', 'jetprayer' ),
					'03' => __( 'March', 'jetprayer' ),
					'04' => __( 'April', 'jetprayer' ),
					'05' => __( 'May', 'jetprayer' ),
					'06' => __( 'June', 'jetprayer' ),
					'07' => __( 'July', 'jetprayer' ),
					'08' => __( 'August', 'jetprayer' ),
					'09' => __( 'September', 'jetprayer' ),
					'10' => __( 'October', 'jetprayer' ),
					'11' => __( 'November', 'jetprayer' ),
					'12' => __( 'December', 'jetprayer' ),
				),
			)
		) );
	}

	/**
	 * Render settings page.
	 */
	public function render_admin_page() {
		require_once JETPRAYER_PATH . 'admin/partials/admin-page.php';
	}
}
