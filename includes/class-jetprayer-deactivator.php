<?php
/**
 * Fired during plugin deactivation.
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

class JetPrayer_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Do not delete database tables here to prevent loss of custom timings.
		// Uninstallation cleanup is handled in uninstall.php if required.
	}
}
