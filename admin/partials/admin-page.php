<?php
/**
 * Admin partial view for settings page.
 *
 * @link       https://wordpress.org/plugins/jetprayer/
 * @since      1.0.0
 *
 * @package    JetPrayer
 * @subpackage JetPrayer/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

// Clear default options to start fresh and empty
if ( 'Turkey' === get_option( 'jetprayer_country' ) ) {
	delete_option( 'jetprayer_country' );
}
if ( 'Istanbul' === get_option( 'jetprayer_city' ) ) {
	delete_option( 'jetprayer_city' );
}
if ( 13 === (int) get_option( 'jetprayer_method' ) ) {
	delete_option( 'jetprayer_method' );
}

// Fetch saved values
$jp_type      = get_option( 'jetprayer_type', 'city' );
$jp_city      = get_option( 'jetprayer_city', '' );
$jp_country   = get_option( 'jetprayer_country', '' );
$jp_latitude  = get_option( 'jetprayer_latitude', '41.0082' );
$jp_longitude = get_option( 'jetprayer_longitude', '28.9784' );
$jp_method    = get_option( 'jetprayer_method', '' );
$jp_school    = get_option( 'jetprayer_school', '0' );
$jp_timezone  = get_option( 'jetprayer_timezone', '' );
$jp_last_sync = get_option( 'jetprayer_last_sync', '' );

$methods = JetPrayer_API::get_calculation_methods();

// Shared per-prayer visibility toggle fields, reused across the Displays layout tabs below.
$jp_prayer_toggles = array(
	'show_imsak'   => __( 'Imsak', 'jetprayer' ),
	'show_fajr'    => __( 'Fajr', 'jetprayer' ),
	'show_sunrise' => __( 'Sunrise', 'jetprayer' ),
	'show_dhuhr'   => __( 'Dhuhr', 'jetprayer' ),
	'show_asr'     => __( 'Asr', 'jetprayer' ),
	'show_maghrib' => __( 'Maghrib', 'jetprayer' ),
	'show_isha'    => __( 'Isha', 'jetprayer' ),
);

// Field definitions for the Displays tab, data-driven per layout to avoid 5x markup duplication.
$jp_display_layouts = array(
	'card'   => array(
		'label'   => __( 'Card', 'jetprayer' ),
		'toggles' => array_merge(
			array(
				'show_location'   => __( 'Location', 'jetprayer' ),
				'show_hijri'      => __( 'Hijri Date', 'jetprayer' ),
				'show_gregorian'  => __( 'Gregorian Date', 'jetprayer' ),
				'show_next_badge' => __( '"Next" Badge', 'jetprayer' ),
			),
			$jp_prayer_toggles
		),
		'advanced_css' => array(
			'layout' => array(
				'title'  => __( 'Layout Settings', 'jetprayer' ),
				'fields' => array(
					'layout_max_width' => array( 'label' => __( 'Max Width', 'jetprayer' ), 'type' => 'text', 'placeholder' => '380px' ),
				),
			),
			'name' => array(
				'title'  => __( 'Prayer Name Settings', 'jetprayer' ),
				'fields' => array(
					'name_size'    => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '14px' ),
					'name_weight'  => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'name_padding' => array( 'label' => __( 'Padding', 'jetprayer' ), 'type' => 'text', 'placeholder' => '12px 16px' ),
					'name_margin'  => array( 'label' => __( 'Margin Right', 'jetprayer' ), 'type' => 'text', 'placeholder' => '4px' ),
					'name_radius'  => array( 'label' => __( 'Border Radius', 'jetprayer' ), 'type' => 'range', 'min' => 0, 'max' => 50 ),
					'name_align'   => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'name_font'    => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'time' => array(
				'title'  => __( 'Prayer Time Settings', 'jetprayer' ),
				'fields' => array(
					'time_size'    => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '16px' ),
					'time_weight'  => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'time_padding' => array( 'label' => __( 'Padding', 'jetprayer' ), 'type' => 'text', 'placeholder' => '12px 16px' ),
					'time_margin'  => array( 'label' => __( 'Margin Left', 'jetprayer' ), 'type' => 'text', 'placeholder' => '4px' ),
					'time_radius'  => array( 'label' => __( 'Border Radius', 'jetprayer' ), 'type' => 'range', 'min' => 0, 'max' => 50 ),
					'time_align'   => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'time_font'    => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'ratio' => array(
				'title'  => __( 'Width Sharing', 'jetprayer' ),
				'fields' => array(
					'flex_ratio' => array( 'label' => __( 'Name vs Time Ratio', 'jetprayer' ), 'type' => 'ratio' ),
				),
			),
		),
	),
	'grid'   => array(
		'label'   => __( 'Grid', 'jetprayer' ),
		'toggles' => array_merge(
			array(
				'show_location'   => __( 'Location', 'jetprayer' ),
				'show_hijri'      => __( 'Hijri Date', 'jetprayer' ),
				'show_next_badge' => __( '"Next" Indicator Dot', 'jetprayer' ),
			),
			$jp_prayer_toggles
		),
		'advanced_css' => array(
			'layout' => array(
				'title'  => __( 'Layout Settings', 'jetprayer' ),
				'fields' => array(
					'layout_max_width' => array( 'label' => __( 'Max Width', 'jetprayer' ), 'type' => 'text', 'placeholder' => '900px' ),
				),
			),
			'name' => array(
				'title'  => __( 'Prayer Name Settings', 'jetprayer' ),
				'fields' => array(
					'name_size'   => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '13px' ),
					'name_weight' => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'name_margin' => array( 'label' => __( 'Margin Bottom', 'jetprayer' ), 'type' => 'text', 'placeholder' => '8px' ),
					'name_align'  => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'name_font'   => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'time' => array(
				'title'  => __( 'Prayer Time Settings', 'jetprayer' ),
				'fields' => array(
					'time_size'   => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '18px' ),
					'time_weight' => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'time_align'  => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'time_font'   => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
		),
	),
	'slider' => array(
		'label'   => __( 'Slider', 'jetprayer' ),
		'toggles' => array_merge(
			array(
				'show_location'   => __( 'Location', 'jetprayer' ),
				'show_next_badge' => __( '"Next" Badge', 'jetprayer' ),
			),
			$jp_prayer_toggles
		),
		'advanced_css' => array(
			'layout' => array(
				'title'  => __( 'Layout Settings', 'jetprayer' ),
				'fields' => array(
					'layout_max_width' => array( 'label' => __( 'Max Width', 'jetprayer' ), 'type' => 'text', 'placeholder' => '500px' ),
				),
			),
			'name' => array(
				'title'  => __( 'Prayer Name Settings', 'jetprayer' ),
				'fields' => array(
					'name_size'   => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '13px' ),
					'name_weight' => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'name_align'  => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'name_font'   => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'time' => array(
				'title'  => __( 'Prayer Time Settings', 'jetprayer' ),
				'fields' => array(
					'time_size'   => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '18px' ),
					'time_weight' => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'time_align'  => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'time_font'   => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
		),
	),
	'ticker' => array(
		'label'   => __( 'Ticker', 'jetprayer' ),
		'toggles' => array_merge(
			array(
				'show_location' => __( 'Location Badge', 'jetprayer' ),
				'show_hijri'    => __( 'Hijri Date', 'jetprayer' ),
			),
			$jp_prayer_toggles
		),
	),
	'modal'  => array(
		'label'   => __( 'Modal', 'jetprayer' ),
		'toggles' => array_merge(
			array(
				'show_location'   => __( 'Location', 'jetprayer' ),
				'show_hijri'      => __( 'Hijri Date', 'jetprayer' ),
				'show_gregorian'  => __( 'Gregorian Date', 'jetprayer' ),
				'show_next_badge' => __( '"Next" Badge', 'jetprayer' ),
			),
			$jp_prayer_toggles
		),
		'advanced_css' => array(
			'layout' => array(
				'title'  => __( 'Layout Settings', 'jetprayer' ),
				'fields' => array(
					'layout_max_width' => array( 'label' => __( 'Max Width', 'jetprayer' ), 'type' => 'text', 'placeholder' => '380px' ),
				),
			),
			'name' => array(
				'title'  => __( 'Prayer Name Settings', 'jetprayer' ),
				'fields' => array(
					'name_size'    => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '14px' ),
					'name_weight'  => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'name_padding' => array( 'label' => __( 'Padding', 'jetprayer' ), 'type' => 'text', 'placeholder' => '12px 16px' ),
					'name_margin'  => array( 'label' => __( 'Margin Right', 'jetprayer' ), 'type' => 'text', 'placeholder' => '4px' ),
					'name_radius'  => array( 'label' => __( 'Border Radius', 'jetprayer' ), 'type' => 'range', 'min' => 0, 'max' => 50 ),
					'name_align'   => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'name_font'    => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'time' => array(
				'title'  => __( 'Prayer Time Settings', 'jetprayer' ),
				'fields' => array(
					'time_size'    => array( 'label' => __( 'Font Size', 'jetprayer' ), 'type' => 'text', 'placeholder' => '16px' ),
					'time_weight'  => array( 'label' => __( 'Font Weight', 'jetprayer' ), 'type' => 'weight' ),
					'time_padding' => array( 'label' => __( 'Padding', 'jetprayer' ), 'type' => 'text', 'placeholder' => '12px 16px' ),
					'time_margin'  => array( 'label' => __( 'Margin Left', 'jetprayer' ), 'type' => 'text', 'placeholder' => '4px' ),
					'time_radius'  => array( 'label' => __( 'Border Radius', 'jetprayer' ), 'type' => 'range', 'min' => 0, 'max' => 50 ),
					'time_align'   => array( 'label' => __( 'Text Alignment', 'jetprayer' ), 'type' => 'alignment' ),
					'time_font'    => array( 'label' => __( 'Font Family', 'jetprayer' ), 'type' => 'font' ),
				),
			),
			'ratio' => array(
				'title'  => __( 'Width Sharing', 'jetprayer' ),
				'fields' => array(
					'flex_ratio' => array( 'label' => __( 'Name vs Time Ratio', 'jetprayer' ), 'type' => 'ratio' ),
				),
			),
		),
	),
);
?>

<div class="wrap jetprayer-admin-wrap">
	<header class="jetprayer-admin-header">
		<div class="jetprayer-logo">
			<span class="dashicons dashicons-clock"></span>
			<h1>JetPrayer</h1>
		</div>
		<p class="jetprayer-subtitle"><?php esc_html_e( 'Islamic Prayer Times - Performance Caching & Timetable Manager', 'jetprayer' ); ?></p>
	</header>

	<h2 class="nav-tab-wrapper jetprayer-tabs">
		<a href="#settings-tab" class="nav-tab nav-tab-active" data-tab="settings"><?php esc_html_e( 'Settings & Sync', 'jetprayer' ); ?></a>
		<a href="#editor-tab" class="nav-tab" data-tab="editor"><?php esc_html_e( 'Prayer Times Editor (CRUD)', 'jetprayer' ); ?></a>
		<a href="#displays-tab" class="nav-tab" data-tab="displays"><?php esc_html_e( 'Displays', 'jetprayer' ); ?></a>
	</h2>

	<div id="jetprayer-tab-settings" class="jetprayer-tab-content active">
		<div class="jetprayer-grid">
			<!-- Settings Column -->
			<div class="jetprayer-card">
				<h2 class="jp-flex-header">
					<span><?php esc_html_e( 'Configuration', 'jetprayer' ); ?></span>
					<button type="button" id="jp-bulk-sync-trigger" class="button button-secondary jp-bulk-btn-header">
						<span class="dashicons dashicons-database-add jp-header-icon"></span>
						<?php esc_html_e( 'Bulk Add & Sync', 'jetprayer' ); ?>
					</button>
				</h2>
				<form id="jetprayer-settings-form">
					<div class="jp-form-group">
						<label><?php esc_html_e( 'Location Method', 'jetprayer' ); ?></label>
						<div class="radio-group">
							<label>
								<input type="radio" name="type" value="city" <?php checked( $jp_type, 'city' ); ?>>
								<span><?php esc_html_e( 'City & Country', 'jetprayer' ); ?></span>
							</label>
							<label>
								<input type="radio" name="type" value="coords" <?php checked( $jp_type, 'coords' ); ?>>
								<span><?php esc_html_e( 'Latitude & Longitude', 'jetprayer' ); ?></span>
							</label>
						</div>
					</div>

					<!-- City Fields -->
					<div class="location-fields-city <?php echo esc_attr( 'city' === $jp_type ? '' : 'hidden' ); ?>">
						<div class="jp-form-row">
							<div class="jp-form-group jp-col-6">
								<label for="jp_country"><?php esc_html_e( 'Country', 'jetprayer' ); ?></label>
								<input type="text" id="jp_country" name="country" list="jp-country-list" value="<?php echo esc_attr( $jp_country ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Select or type country...', 'jetprayer' ); ?>">
								<datalist id="jp-country-list"></datalist>
							</div>
							<div class="jp-form-group jp-col-6">
								<label for="jp_city"><?php esc_html_e( 'City', 'jetprayer' ); ?></label>
								<input type="text" id="jp_city" name="city" list="jp-city-list" value="<?php echo esc_attr( $jp_city ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Select or type city...', 'jetprayer' ); ?>">
								<datalist id="jp-city-list"></datalist>
							</div>
						</div>
						<p class="description jp-city-note">
							<strong><?php esc_html_e( 'Note:', 'jetprayer' ); ?></strong> <?php esc_html_e( 'If the country or city you are looking for is not in the list, you can type it manually in English/International format (e.g., Country: "Madagascar", City: "Antananarivo"). The plugin will automatically recognize and sync your location.', 'jetprayer' ); ?>
						</p>
					</div>

					<!-- Coords Fields -->
					<div class="location-fields-coords <?php echo esc_attr( 'coords' === $jp_type ? '' : 'hidden' ); ?>">
						<div class="jp-form-row">
							<div class="jp-form-group jp-col-6">
								<label for="jp_latitude"><?php esc_html_e( 'Latitude', 'jetprayer' ); ?></label>
								<input type="number" step="any" id="jp_latitude" name="latitude" value="<?php echo esc_attr( $jp_latitude ); ?>" class="regular-text">
							</div>
							<div class="jp-form-group jp-col-6">
								<label for="jp_longitude"><?php esc_html_e( 'Longitude', 'jetprayer' ); ?></label>
								<input type="number" step="any" id="jp_longitude" name="longitude" value="<?php echo esc_attr( $jp_longitude ); ?>" class="regular-text">
							</div>
						</div>
					</div>

					<div class="jp-form-row">
						<div class="jp-form-group jp-col-6">
							<label for="jp_method"><?php esc_html_e( 'Calculation Method', 'jetprayer' ); ?></label>
							<select id="jp_method" name="method" class="widefat">
								<option value="" <?php selected( $jp_method, '' ); ?>><?php esc_html_e( 'Select calculation method...', 'jetprayer' ); ?></option>
								<?php foreach ( $methods as $key => $name ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $jp_method, $key ); ?>><?php echo esc_html( $key ) . ' &mdash; ' . esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="jp-form-group jp-col-6">
							<label for="jp_school"><?php esc_html_e( 'Asr Calculation School', 'jetprayer' ); ?></label>
							<select id="jp_school" name="school" class="widefat">
								<option value="0" <?php selected( $jp_school, 0 ); ?>><?php esc_html_e( 'Standard (Shafi, Maliki, Hanbali)', 'jetprayer' ); ?></option>
								<option value="1" <?php selected( $jp_school, 1 ); ?>><?php esc_html_e( 'Hanafi', 'jetprayer' ); ?></option>
							</select>
						</div>
					</div>

					<div class="jp-form-row">
						<div class="jp-form-group jp-col-6">
							<label for="jp_timezone"><?php esc_html_e( 'Timezone (Optional)', 'jetprayer' ); ?></label>
							<input type="text" id="jp_timezone" name="timezone" value="<?php echo esc_attr( $jp_timezone ); ?>" placeholder="Europe/Istanbul" class="regular-text">
						</div>
						<div class="jp-form-group jp-col-6">
							<label for="jp_sync_year"><?php esc_html_e( 'Year to Sync', 'jetprayer' ); ?></label>
							<select id="jp_sync_year" name="sync_year" class="widefat">
								<?php
								$current_year = intval( gmdate( 'Y' ) );
								for ( $y = $current_year; $y <= $current_year + 1; $y++ ) {
									echo '<option value="' . esc_attr( $y ) . '">' . esc_html( $y ) . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="jp-sync-btn-wrapper">
						<button type="submit" id="jetprayer-sync-btn" class="button button-primary jetprayer-btn-sync widefat jp-sync-btn-style">
							<span class="dashicons dashicons-update jp-sync-btn-icon"></span>
							<?php esc_html_e( 'Sync Entire Year Now', 'jetprayer' ); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- Sync Column -->
			<div class="jetprayer-card">
				<h2><?php esc_html_e( 'Database Synchronization', 'jetprayer' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Sync the cached database timings with the AlAdhan API. The frontend will ONLY use cached local values to prevent external HTTP delays and quota ban limits.', 'jetprayer' ); ?>
				</p>
				<div class="sync-status">
					<div class="status-indicator <?php echo esc_attr( $jp_last_sync ? 'synced' : 'pending' ); ?>"></div>
					<div>
						<strong><?php esc_html_e( 'Last Sync Date:', 'jetprayer' ); ?></strong>
						<span id="jetprayer-sync-time"><?php echo esc_html( $jp_last_sync ? $jp_last_sync : __( 'Never Synced', 'jetprayer' ) ); ?></span>
					</div>
				</div>

				<div class="jetprayer-synced-methods">
					<strong><?php esc_html_e( 'Locations & Methods in Database:', 'jetprayer' ); ?></strong>
					<div id="jetprayer-synced-methods-list">
						<?php
						$jp_synced_locations = JetPrayer_DB::get_synced_locations();
						if ( empty( $jp_synced_locations ) ) :
							?>
							<span class="jetprayer-no-sync"><?php esc_html_e( 'No data synced yet.', 'jetprayer' ); ?></span>
							<?php
						else :
							$jp_locations_count = count( $jp_synced_locations );
							?>
							<select class="widefat jp-synced-methods-select" style="margin-top: 10px;">
								<option value=""><?php
									/* translators: %d: number of locations */
									printf( esc_html( _n( '%d Location in Database', '%d Locations in Database', $jp_locations_count, 'jetprayer' ) ), absint( $jp_locations_count ) );
								?></option>
								<?php
								foreach ( $jp_synced_locations as $jp_loc ) :
									?>
									<option disabled>
										<?php echo esc_html( $jp_loc['city'] . ', ' . $jp_loc['country'] . ' (' . $jp_loc['method_id'] . ')' ); ?>
									</option>
									<?php
								endforeach;
								?>
							</select>
							<?php
						endif;
						?>
					</div>
				</div>

				<div id="jetprayer-sync-log" class="jetprayer-log-box hidden jp-sync-log-style"></div>
			</div>
		</div>

		<!-- Shortcodes Info Card -->
		<div class="jetprayer-card jp-card-margin-top">
			<h2><?php esc_html_e( 'How to Display Prayer Times', 'jetprayer' ); ?></h2>
			<p><?php esc_html_e( 'You can copy and paste the following shortcodes into any post, page, widget, or page builder (such as Elementor, Divi, Beaver, Gutenberg shortcode block).', 'jetprayer' ); ?></p>
			<p class="description"><?php esc_html_e( 'Using Elementor or Gutenberg? Look for the native "JetPrayer - Prayer Times" widget/block (under the "JetPrayer" category) instead — it exposes Layout, Calculation Method and Date as visual dropdowns, no shortcode typing needed.', 'jetprayer' ); ?></p>
			
			<div class="shortcode-showcase-grid">
				<div class="shortcode-item">
					<strong><?php esc_html_e( '1. Default Card Layout', 'jetprayer' ); ?></strong>
					<code>[jetprayer layout="card"]</code>
				</div>
				<div class="shortcode-item">
					<strong><?php esc_html_e( '2. Responsive Grid Layout', 'jetprayer' ); ?></strong>
					<code>[jetprayer layout="grid"]</code>
				</div>
				<div class="shortcode-item">
					<strong><?php esc_html_e( '3. Dynamic Slider Layout', 'jetprayer' ); ?></strong>
					<code>[jetprayer layout="slider"]</code>
				</div>
				<div class="shortcode-item">
					<strong><?php esc_html_e( '4. Scrolling Ticker Layout', 'jetprayer' ); ?></strong>
					<code>[jetprayer layout="ticker"]</code>
				</div>
				<div class="shortcode-item">
					<strong><?php esc_html_e( '5. Monthly Table Trigger Modal', 'jetprayer' ); ?></strong>
					<code>[jetprayer layout="modal"]</code>
				</div>
			</div>

			<div class="jetprayer-shortcode-guide jp-shortcode-guide-box">
				<h3 class="jp-shortcode-guide-title"><?php esc_html_e( 'Shortcode Parameters & Advanced Guide', 'jetprayer' ); ?></h3>
				<p class="description jp-shortcode-guide-desc">
					<?php esc_html_e( 'You can customize the shortcode on different pages using parameter attributes. These parameters fetch synced database records dynamically:', 'jetprayer' ); ?>
				</p>
				
				<ul class="jp-shortcode-guide-list">
					<li><strong><code>layout</code></strong>: <?php esc_html_e( 'The display layout style (card, grid, slider, ticker, modal). Defaults to card.', 'jetprayer' ); ?></li>
					<li><strong><code>city</code></strong>: <?php esc_html_e( 'The city name (e.g. "Istanbul"). You can also supply a comma-separated list of multiple cities (e.g. "Istanbul,Ankara") to limit the dropdown switcher. Defaults to the alphabetically first synced city.', 'jetprayer' ); ?></li>
					<li><strong><code>country</code></strong>: <?php esc_html_e( 'The country name (e.g. "Turkey"). Defaults to the globally synced country or first synced record.', 'jetprayer' ); ?></li>
					<li><strong><code>method</code></strong>: <?php esc_html_e( 'The calculation method ID (e.g. "13" for Diyanet, "4" for Makkah). Use "all" or "any" to query any synced method. Defaults to the default synced method or auto-detects synced data.', 'jetprayer' ); ?></li>
					<li><strong><code>date</code></strong>: <?php esc_html_e( 'The display date. Use "today" (default), "tomorrow", or a specific date in YYYY-MM-DD format (e.g. "2026-06-21").', 'jetprayer' ); ?></li>
				</ul>

				<p class="description jp-shortcode-guide-desc" style="margin-top: 15px;">
					<strong><?php esc_html_e( 'Automatic Switcher Dropdown:', 'jetprayer' ); ?></strong>
					<?php esc_html_e( 'A location switcher dropdown is automatically rendered on the frontend if more than one city is synced under the resolved country, or if you explicitly specify multiple cities in the city parameter.', 'jetprayer' ); ?>
				</p>

				<h4 class="jp-shortcode-guide-example-title"><?php esc_html_e( 'Usage Examples:', 'jetprayer' ); ?></h4>
				<div class="jp-shortcode-guide-example-box">
					<code>[jetprayer layout="card" city="Istanbul" country="Turkey" method="13"]</code><br>
					<span class="description" style="font-size: 11px; margin-bottom: 12px; display: inline-block;"><?php esc_html_e( 'Shows card layout for Istanbul, Turkey using calculation method 13 (Diyanet).', 'jetprayer' ); ?></span><br>
					
					<code>[jetprayer layout="grid" date="tomorrow"]</code><br>
					<span class="description" style="font-size: 11px; margin-bottom: 12px; display: inline-block;"><?php esc_html_e( 'Shows tomorrow\'s prayer times in a responsive grid layout using default synced location.', 'jetprayer' ); ?></span><br>
					
					<code>[jetprayer layout="slider" city="Istanbul,Ankara,Izmir" country="Turkey"]</code><br>
					<span class="description" style="font-size: 11px; margin-bottom: 12px; display: inline-block;"><?php esc_html_e( 'Shows slider layout with an automatic city switcher containing only Istanbul, Ankara, and Izmir.', 'jetprayer' ); ?></span><br>
					
					<code>[jetprayer layout="slider" country="Turkey"]</code><br>
					<span class="description" style="font-size: 11px; margin-bottom: 12px; display: inline-block;"><?php esc_html_e( 'Shows slider layout with an automatic city switcher containing ALL synced cities under Turkey.', 'jetprayer' ); ?></span><br>
					
					<code>[jetprayer layout="card" city="Berlin" country="Germany" method="all"]</code><br>
					<span class="description" style="font-size: 11px; margin-bottom: 12px; display: inline-block;"><?php esc_html_e( 'Queries Berlin, Germany and automatically resolves and displays whichever calculation method is synced for this location in the database.', 'jetprayer' ); ?></span><br>
					
					<code>[jetprayer layout="ticker" date="2026-06-21"]</code><br>
					<span class="description" style="font-size: 11px; display: inline-block;"><?php esc_html_e( 'Shows scrolling ticker for a specific custom date.', 'jetprayer' ); ?></span>
				</div>
			</div>

			<p class="description jp-desc-margin-top">
				<?php esc_html_e( 'Calculation Method ID list is below. After syncing a location with a specific method, you can use method="ID" in the shortcode:', 'jetprayer' ); ?>
			</p>
			<table class="wp-list-table widefat striped jp-table-max-width">
				<thead>
					<tr>
						<th class="jp-table-id-header"><?php esc_html_e( 'ID', 'jetprayer' ); ?></th>
						<th><?php esc_html_e( 'Calculation Method', 'jetprayer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $methods as $key => $name ) : ?>
						<tr>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td><?php echo esc_html( $name ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div id="jetprayer-tab-editor" class="jetprayer-tab-content">
		<div class="jetprayer-card">
			<div class="jp-editor-filters-wrap">
				<div class="jp-editor-filter-group">
					<label for="jp_edit_country"><?php esc_html_e( 'Country', 'jetprayer' ); ?></label>
					<select id="jp_edit_country" class="jp-filter">
						<option value=""><?php esc_html_e( 'Loading countries...', 'jetprayer' ); ?></option>
					</select>
				</div>
				<div class="jp-editor-filter-group">
					<label for="jp_edit_city"><?php esc_html_e( 'City', 'jetprayer' ); ?></label>
					<select id="jp_edit_city" class="jp-filter">
						<option value=""><?php esc_html_e( 'Loading cities...', 'jetprayer' ); ?></option>
					</select>
				</div>
				<div class="jp-editor-filter-group jp-editor-filter-method">
					<label for="jp_edit_method"><?php esc_html_e( 'Method', 'jetprayer' ); ?></label>
					<select id="jp_edit_method" class="jp-filter">
						<option value=""><?php esc_html_e( 'Loading methods...', 'jetprayer' ); ?></option>
					</select>
				</div>
				<div class="jp-editor-filter-group">
					<label for="jp_edit_month"><?php esc_html_e( 'Month', 'jetprayer' ); ?></label>
					<select id="jp_edit_month" class="jp-filter">
						<?php
						for ( $m = 1; $m <= 12; $m++ ) {
							$date_obj = DateTime::createFromFormat( '!m', $m );
							$month_name = $date_obj->format( 'F' );
							echo '<option value="' . esc_attr( sprintf( '%02d', $m ) ) . '" ' . selected( gmdate( 'm' ), sprintf( '%02d', $m ), false ) . '>' . esc_html( $month_name ) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="jp-editor-filter-group">
					<label for="jp_edit_year"><?php esc_html_e( 'Year', 'jetprayer' ); ?></label>
					<select id="jp_edit_year" class="jp-filter">
						<?php
						$current_year = intval( gmdate( 'Y' ) );
						for ( $y = $current_year; $y <= $current_year + 1; $y++ ) {
							echo '<option value="' . esc_attr( $y ) . '">' . esc_html( $y ) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="jp-editor-filter-buttons">
					<button type="button" id="jp-load-timings" class="button jetprayer-btn-secondary"><?php esc_html_e( 'Load Month', 'jetprayer' ); ?></button>
					<button type="button" id="jp-load-year-timings" class="button button-primary jetprayer-btn-primary"><?php esc_html_e( 'Load Year', 'jetprayer' ); ?></button>
					<button type="button" id="jp-delete-selected-btn" class="button button-link-delete hidden jp-btn-danger"><?php esc_html_e( 'Delete Selected', 'jetprayer' ); ?></button>
				</div>
			</div>

			<div class="jetprayer-table-responsive">
				<table class="wp-list-table widefat fixed striped table-view-list jetprayer-crud-table">
					<thead>
						<tr>
							<th class="jp-col-cb"><input type="checkbox" id="jp-cb-select-all"></th>
							<th class="jp-col-date"><?php esc_html_e( 'Date', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Fajr', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Sunrise', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Dhuhr', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Asr', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Sunset', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Maghrib', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Isha', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Imsak', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Midnight', 'jetprayer' ); ?></th>
							<th><?php esc_html_e( 'Hijri Date', 'jetprayer' ); ?></th>
							<th class="jp-col-status"><?php esc_html_e( 'Status', 'jetprayer' ); ?></th>
							<th class="jp-col-actions"><?php esc_html_e( 'Actions', 'jetprayer' ); ?></th>
						</tr>
					</thead>
					<tbody id="jetprayer-table-body">
						<tr>
							<td colspan="14" class="text-center"><?php esc_html_e( 'Click "Load Month" or "Load Year" to display database rows.', 'jetprayer' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<div id="jp-pagination-container" class="jp-pagination-nav hidden"></div>
		</div>
	</div>

	<div id="jetprayer-tab-displays" class="jetprayer-tab-content">
		<div class="jetprayer-card">
			<h2><?php esc_html_e( 'Layout Display Settings', 'jetprayer' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Toggle which elements are visible for each shortcode layout. Changes apply site-wide to every use of that layout.', 'jetprayer' ); ?>
			</p>

			<div class="jetprayer-display-subtabs">
				<?php foreach ( $jp_display_layouts as $jp_layout_key => $jp_layout_conf ) : ?>
					<a href="#" class="jp-subtab-link<?php echo ( 'card' === $jp_layout_key ) ? ' jp-subtab-active' : ''; ?>" data-sublayout="<?php echo esc_attr( $jp_layout_key ); ?>"><?php echo esc_html( $jp_layout_conf['label'] ); ?></a>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $jp_display_layouts as $jp_layout_key => $jp_layout_conf ) :
				$jp_current = JetPrayer_Display_Settings::get_settings( $jp_layout_key );
				?>
				<div class="jp-display-panel<?php echo ( 'card' === $jp_layout_key ) ? ' active' : ''; ?>" id="jp-display-panel-<?php echo esc_attr( $jp_layout_key ); ?>" data-layout="<?php echo esc_attr( $jp_layout_key ); ?>">
					<form class="jp-display-form" data-layout="<?php echo esc_attr( $jp_layout_key ); ?>">
						<div class="jp-display-section">
							<h3><?php esc_html_e( 'Visibility', 'jetprayer' ); ?></h3>
							<div class="jp-toggle-grid">
								<?php foreach ( $jp_layout_conf['toggles'] as $jp_field => $jp_field_label ) : ?>
									<label class="jp-toggle">
										<input type="checkbox" name="<?php echo esc_attr( $jp_field ); ?>" <?php checked( ! empty( $jp_current[ $jp_field ] ) ); ?>>
										<span class="jp-toggle-slider"></span>
										<span class="jp-toggle-label"><?php echo esc_html( $jp_field_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>



						<?php if ( ! empty( $jp_layout_conf['advanced_css'] ) ) : ?>
						<div class="jp-display-section jp-advanced-section" style="border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 20px;">
							<h3 style="font-size: 16px; margin-bottom: 20px; font-weight: 700; color: #1e293b;"><?php esc_html_e( 'Advanced CSS', 'jetprayer' ); ?></h3>
							
							<?php foreach ( $jp_layout_conf['advanced_css'] as $jp_group_key => $jp_group ) : ?>
								<div class="jp-advanced-group" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
									<h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;"><?php echo esc_html( $jp_group['title'] ); ?></h4>
									<div class="jp-form-row">
										<?php foreach ( $jp_group['fields'] as $jp_field => $jp_opts ) : ?>
											<div class="jp-form-group jp-col-4">
												<?php if ( 'range' === $jp_opts['type'] ) : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?> (<span class="jp-range-value"><?php echo esc_html( isset( $jp_current[ $jp_field ] ) ? $jp_current[ $jp_field ] : 0 ); ?></span>px)</label>
													<input type="range" class="jp-range-field" name="<?php echo esc_attr( $jp_field ); ?>" min="<?php echo esc_attr( $jp_opts['min'] ); ?>" max="<?php echo esc_attr( $jp_opts['max'] ); ?>" value="<?php echo esc_attr( isset( $jp_current[ $jp_field ] ) ? $jp_current[ $jp_field ] : 0 ); ?>" style="width: 100%;">
												<?php elseif ( 'weight' === $jp_opts['type'] ) : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?></label>
													<select class="widefat" name="<?php echo esc_attr( $jp_field ); ?>" style="width: 100%;">
														<?php
														$weights = array(
															'inherit' => __( 'Inherit / Default', 'jetprayer' ),
															'300'     => __( 'Light (300)', 'jetprayer' ),
															'400'     => __( 'Normal (400)', 'jetprayer' ),
															'500'     => __( 'Medium (500)', 'jetprayer' ),
															'600'     => __( 'Semi-Bold (600)', 'jetprayer' ),
															'700'     => __( 'Bold (700)', 'jetprayer' ),
															'800'     => __( 'Extra-Bold (800)', 'jetprayer' ),
															'900'     => __( 'Black (900)', 'jetprayer' ),
														);
														foreach ( $weights as $w_val => $w_label ) {
															$is_sel = ( isset( $jp_current[ $jp_field ] ) && (string) $jp_current[ $jp_field ] === (string) $w_val );
															echo '<option value="' . esc_attr( $w_val ) . '" ' . selected( $is_sel, true, false ) . '>' . esc_html( $w_label ) . '</option>';
														}
														?>
													</select>
												<?php elseif ( 'alignment' === $jp_opts['type'] ) : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?></label>
													<select class="widefat" name="<?php echo esc_attr( $jp_field ); ?>" style="width: 100%;">
														<?php
														$alignments = array(
															'flex-start' => __( 'Left', 'jetprayer' ),
															'center'     => __( 'Center', 'jetprayer' ),
															'flex-end'   => __( 'Right', 'jetprayer' ),
														);
														foreach ( $alignments as $a_val => $a_label ) {
															$is_sel = ( isset( $jp_current[ $jp_field ] ) && (string) $jp_current[ $jp_field ] === (string) $a_val );
															echo '<option value="' . esc_attr( $a_val ) . '" ' . selected( $is_sel, true, false ) . '>' . esc_html( $a_label ) . '</option>';
														}
														?>
													</select>
												<?php elseif ( 'ratio' === $jp_opts['type'] ) : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?></label>
													<select class="widefat" name="<?php echo esc_attr( $jp_field ); ?>" style="width: 100%;">
														<?php
														$ratios = array(
															'1:1' => __( 'Equal (50% - 50%)', 'jetprayer' ),
															'3:2' => __( 'More Name (60% - 40%)', 'jetprayer' ),
															'2:1' => __( 'Much More Name (67% - 33%)', 'jetprayer' ),
															'3:1' => __( 'Dominant Name (75% - 25%)', 'jetprayer' ),
															'2:3' => __( 'More Time (40% - 60%)', 'jetprayer' ),
															'1:2' => __( 'Much More Time (33% - 67%)', 'jetprayer' ),
															'1:3' => __( 'Dominant Time (25% - 75%)', 'jetprayer' ),
														);
														foreach ( $ratios as $r_val => $r_label ) {
															$is_sel = ( isset( $jp_current[ $jp_field ] ) && (string) $jp_current[ $jp_field ] === (string) $r_val );
															echo '<option value="' . esc_attr( $r_val ) . '" ' . selected( $is_sel, true, false ) . '>' . esc_html( $r_label ) . '</option>';
														}
														?>
													</select>
												<?php elseif ( 'font' === $jp_opts['type'] ) : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?></label>
													<input type="text" class="regular-text" name="<?php echo esc_attr( $jp_field ); ?>" value="<?php echo esc_attr( isset( $jp_current[ $jp_field ] ) ? $jp_current[ $jp_field ] : '' ); ?>" placeholder="e.g. system-ui, sans-serif" list="jp-fonts-list-<?php echo esc_attr( $jp_layout_key ); ?>-<?php echo esc_attr( $jp_field ); ?>" style="width: 100%; height: 30px;">
													<datalist id="jp-fonts-list-<?php echo esc_attr( $jp_layout_key ); ?>-<?php echo esc_attr( $jp_field ); ?>">
														<option value="system-ui, -apple-system, sans-serif">
														<option value="monospace">
														<option value="inherit">
														<option value="'Inter', sans-serif">
														<option value="'Roboto', sans-serif">
														<option value="Georgia, serif">
														<option value="'Courier New', monospace">
													</datalist>
												<?php else : ?>
													<label style="font-weight: 500; color: #475569;"><?php echo esc_html( $jp_opts['label'] ); ?></label>
													<input type="text" class="regular-text" name="<?php echo esc_attr( $jp_field ); ?>" value="<?php echo esc_attr( isset( $jp_current[ $jp_field ] ) ? $jp_current[ $jp_field ] : '' ); ?>" placeholder="<?php echo esc_attr( isset( $jp_opts['placeholder'] ) ? $jp_opts['placeholder'] : '' ); ?>" style="width: 100%; height: 30px;">
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>

						<button type="submit" class="button button-primary jetprayer-btn"><?php esc_html_e( 'Save Display Settings', 'jetprayer' ); ?></button>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Bulk Sync Modal -->
	<div id="jp-bulk-modal" class="jp-modal-overlay hidden">
		<div class="jp-modal-card">
			<div class="jp-modal-header">
				<h3><span class="dashicons dashicons-database-add"></span> <?php esc_html_e( 'Bulk Add & Sync Locations', 'jetprayer' ); ?></h3>
				<button type="button" class="jp-modal-close">&times;</button>
			</div>
			<div class="jp-modal-body">
				<!-- Phase 1: Upload and Configuration -->
				<div id="jp-bulk-phase-upload">
					<p class="description">
						<?php esc_html_e( 'You can upload a JSON file containing countries and cities to sync them in bulk. Download the sample template below, prepare your list, and upload it.', 'jetprayer' ); ?>
					</p>

					<div class="jp-bulk-actions-row">
						<button type="button" id="jp-bulk-download-template" class="button">
							<span class="dashicons dashicons-download jp-vertical-align-middle"></span>
							<?php esc_html_e( 'Download Sample Template', 'jetprayer' ); ?>
						</button>
						<label class="button button-primary jp-cursor-pointer">
							<span class="dashicons dashicons-upload jp-vertical-align-middle"></span>
							<?php esc_html_e( 'Choose & Upload JSON File', 'jetprayer' ); ?>
							<input type="file" id="jp-bulk-file-input" accept=".json" class="jp-display-none">
						</label>
					</div>

					<div class="jp-bulk-instructions">
						<h4 class="jp-bulk-inst-title"><?php esc_html_e( 'JSON Template Guide & Formats / Şablon Rehberi:', 'jetprayer' ); ?></h4>
						
						<p class="jp-bulk-inst-format-title"><strong><?php esc_html_e( 'Format 1 (Simple City List):', 'jetprayer' ); ?></strong></p>
						<pre><code>{
  "Turkey": ["Istanbul", "Ankara", "Izmir"]
}</code></pre>
						<p class="desc-small"><?php esc_html_e( 'Uses the default method and year configured in the main form for all cities.', 'jetprayer' ); ?></p>

						<p class="jp-bulk-inst-format-title-alt"><strong><?php esc_html_e( 'Format 2 (Custom Method & Year per City or Group):', 'jetprayer' ); ?></strong></p>
						<pre><code>{
  "Yemen": [
    "Sanaa", "7", "2026",
    "Aden",
    "Taiz", "4", "2026"
  ]
}</code></pre>
						<p class="desc-small"><?php esc_html_e( 'Sanaa uses Method 7 & Year 2026. Aden and Taiz use Method 4 & Year 2026. If no values follow, it defaults to the main form method and year.', 'jetprayer' ); ?></p>
					</div>
				</div>

				<!-- Phase 2: Progress and Log -->
				<div id="jp-bulk-phase-progress" class="hidden">
					<div class="jp-progress-container">
						<div class="jp-progress-bar-wrapper">
							<div id="jp-bulk-progress-bar" class="jp-progress-bar" style="width: 0%;"></div>
						</div>
						<div class="jp-progress-stats">
							<span id="jp-bulk-progress-text">0 / 0</span>
							<span id="jp-bulk-progress-pct">0%</span>
						</div>
					</div>

					<div id="jp-bulk-progress-log" class="jetprayer-log-box jp-sync-log-style"></div>

					<div class="jp-bulk-footer-actions">
						<button type="button" id="jp-bulk-cancel-btn" class="button button-link-delete jp-bulk-cancel-btn-style"><?php esc_html_e( 'Cancel remaining syncs', 'jetprayer' ); ?></button>
						<button type="button" id="jp-bulk-done-btn" class="button button-primary hidden"><?php esc_html_e( 'Done', 'jetprayer' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Delete Confirmation Modal -->
	<div id="jp-delete-confirm-modal" class="jp-modal-overlay hidden">
		<div class="jp-modal-card jp-delete-confirm-card" style="max-width: 420px;">
			<div class="jp-modal-header">
				<h3><span class="dashicons dashicons-trash jp-vertical-align-middle"></span> <?php esc_html_e( 'Delete Records Options', 'jetprayer' ); ?></h3>
				<button type="button" class="jp-delete-modal-close jp-modal-close">&times;</button>
			</div>
			<div class="jp-modal-body">
				<p style="margin-bottom: 20px; font-weight: 500; font-size: 14px;"><?php esc_html_e( 'Bu kayıtları veritabanından nasıl silmek istersiniz?', 'jetprayer' ); ?></p>
				
				<div class="jp-form-group" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--jp-border); margin-bottom: 20px;">
					<label class="jp-radio-label" style="font-weight: normal; margin-bottom: 15px; display: block; cursor: pointer;">
						<input type="radio" name="jp-delete-type" value="selected" checked style="margin-right: 8px;">
						<strong><?php esc_html_e( 'Sadece seçilen günleri sil', 'jetprayer' ); ?></strong>
						<span style="display: block; font-size: 12px; color: #64748b; margin-left: 28px; margin-top: 4px;" id="jp-delete-selected-desc"></span>
					</label>
					
					<hr style="border: 0; border-top: 1px solid var(--jp-border); margin: 15px 0;">
					
					<label class="jp-radio-label" style="font-weight: normal; display: block; cursor: pointer;">
						<input type="radio" name="jp-delete-type" value="year" style="margin-right: 8px;">
						<strong style="color: #dc2626;"><?php esc_html_e( 'Tüm yılı sil (Bütün aylar)', 'jetprayer' ); ?></strong>
						<span style="display: block; font-size: 12px; color: #64748b; margin-left: 28px; margin-top: 4px;" id="jp-delete-year-desc"></span>
					</label>
				</div>
				
				<div style="display: flex; justify-content: flex-end; gap: 10px;">
					<button type="button" class="button jp-delete-modal-close"><?php esc_html_e( 'Cancel', 'jetprayer' ); ?></button>
					<button type="button" id="jp-confirm-delete-action-btn" class="button jp-btn-danger" style="margin: 0;"><?php esc_html_e( 'Confirm Delete', 'jetprayer' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
