<?php
/**
 * Plugin Name:  JetPrayer - Islamic Prayer Times
 * Plugin URI:   https://github.com/mehdituran/jetprayer
 * Description:  A performance-optimized, secure, and robust WordPress plugin that displays Islamic prayer times using local caching to avoid external API calls. Features gorgeous grids, sliders, tickers, and modals.
 * Version:      1.0.3
 * Author:       Mehdi Turan
 * Author URI:   https://www.linkedin.com/in/mehdituran/
 * Text Domain:  jetprayer
 * Domain Path:  /languages
 * License:      GPL-2.0-or-later
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Guard: bail before declaring any functions if Pro is already loaded in this
// request. Both plugins use identical global function/class names (no PHP
// namespacing); redefining them would fatal with "Cannot redeclare". Pro has
// the JetPrayer_License class (Lite does not) — its presence means Pro's
// file was already include()'d in this request. We also catch the edge case
// where Pro is listed in active_plugins but its file hasn't been loaded yet.
if ( class_exists( 'JetPrayer_License' ) || in_array( 'jetprayer-pro/jetprayer-pro.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'jetprayer-pro/jetprayer-pro.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		set_transient( 'jetprayer_free_activation_blocked_pro_active', true, 30 );
	}
	// Pro's classes are in memory; loading Lite's same-named functions/classes
	// now would fatal. Bail for this one request — Lite is now deactivated, so
	// the next page load is clean. Pro's admin_notices hook will show the notice.
	return;
}

// Define plugin constants
define( 'JETPRAYER_VERSION', '1.0.3' );
define( 'JETPRAYER_PATH', plugin_dir_path( __FILE__ ) );
define( 'JETPRAYER_URL', plugin_dir_url( __FILE__ ) );
define( 'JETPRAYER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation Hook logic
 */
function jetprayer_activate() {
	require_once JETPRAYER_PATH . 'includes/class-jetprayer-activator.php';
	JetPrayer_Activator::activate();
}

/**
 * Deactivation Hook logic
 */
function jetprayer_deactivate() {
	require_once JETPRAYER_PATH . 'includes/class-jetprayer-deactivator.php';
	JetPrayer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'jetprayer_activate' );
register_deactivation_hook( __FILE__, 'jetprayer_deactivate' );

// Load components
require_once JETPRAYER_PATH . 'includes/class-jetprayer-db.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-display-settings.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-api.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-rest.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-admin.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-shortcodes.php';
require_once JETPRAYER_PATH . 'includes/class-jetprayer-elementor.php';

/**
 * Initialize components on plugins_loaded
 */
function jetprayer_init() {
	// Auto migrate database if version mismatch
	if ( get_option( 'jetprayer_db_version' ) !== JETPRAYER_VERSION ) {
		require_once JETPRAYER_PATH . 'includes/class-jetprayer-activator.php';
		JetPrayer_Activator::activate();
		update_option( 'jetprayer_db_version', JETPRAYER_VERSION );
	}

	// Initialize REST routes
	$rest = new JetPrayer_REST();
	$rest->init();

	// Initialize Admin Panel
	if ( is_admin() ) {
		$admin = new JetPrayer_Admin();
		$admin->init();
	}

	// Initialize Shortcodes
	$shortcodes = new JetPrayer_Shortcodes();
	$shortcodes->init();

	// Initialize Elementor widget integration (no-op if Elementor isn't installed/active).
	$elementor_integration = new JetPrayer_Elementor();
	$elementor_integration->init();
}
add_action( 'plugins_loaded', 'jetprayer_init' );

/**
 * Register Gutenberg blocks
 */
function jetprayer_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// Check if compilation build is complete
	if ( file_exists( JETPRAYER_PATH . 'build/block.json' ) ) {
		register_block_type( JETPRAYER_PATH . 'build/block.json', array(
			'render_callback' => 'jetprayer_render_gutenberg_block',
		) );
	}
}
add_action( 'init', 'jetprayer_register_blocks' );

/**
 * Render Gutenberg block via PHP Shortcodes class
 */
function jetprayer_render_gutenberg_block( $attributes ) {
	$layout  = isset( $attributes['layout'] ) ? $attributes['layout'] : 'card';
	$method  = isset( $attributes['method'] ) ? $attributes['method'] : '';
	$city    = isset( $attributes['city'] ) ? $attributes['city'] : '';
	$country = isset( $attributes['country'] ) ? $attributes['country'] : '';
	$shortcodes = new JetPrayer_Shortcodes();
	return $shortcodes->render_shortcode( array(
		'layout'  => $layout,
		'method'  => $method,
		'city'    => $city,
		'country' => $country,
	) );
}

/**
 * Expose the calculation method ID list to the block editor so the
 * "Method" control can show names without the user needing to know IDs.
 */
function jetprayer_localize_block_editor_methods() {
	$synced_ids = JetPrayer_DB::get_synced_method_ids();
	$all_methods = JetPrayer_API::get_calculation_methods();
	$synced_methods = array();
	if ( ! empty( $synced_ids ) ) {
		foreach ( $synced_ids as $id ) {
			if ( isset( $all_methods[ $id ] ) ) {
				$synced_methods[ $id ] = $all_methods[ $id ];
			}
		}
	} else {
		$synced_methods = $all_methods;
	}

	wp_localize_script(
		'jetprayer-prayer-times-editor-script',
		'jetprayerMethods',
		$synced_methods
	);

	$synced_countries = JetPrayer_DB::get_synced_countries();
	wp_localize_script(
		'jetprayer-prayer-times-editor-script',
		'jetprayerSyncedCountries',
		$synced_countries
	);
}
add_action( 'enqueue_block_editor_assets', 'jetprayer_localize_block_editor_methods' );
