<?php
/**
 * Display (per-layout) appearance settings schema and storage helpers.
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

class JetPrayer_Display_Settings {

	/**
	 * Per-layout schema of toggle and color/number fields with defaults
	 * matching the current hardcoded values in public/css/public.css.
	 *
	 * @return array
	 */
	public static function get_schema() {
		$prayer_toggles = array(
			'show_imsak'   => true,
			'show_fajr'    => true,
			'show_sunrise' => true,
			'show_dhuhr'   => true,
			'show_asr'     => true,
			'show_maghrib' => true,
			'show_isha'    => true,
		);

		$card_advanced = array(
			'name_size'      => '14px',
			'name_weight'    => '600',
			'name_padding'   => '12px 16px',
			'name_margin'    => '4px',
			'name_radius'    => 8,
			'name_font'      => '',
			'name_align'     => 'flex-start',
			'time_size'      => '16px',
			'time_weight'    => '700',
			'time_padding'   => '12px 16px',
			'time_margin'    => '4px',
			'time_radius'    => 8,
			'time_font'      => 'monospace',
			'time_align'     => 'flex-end',
			'layout_max_width' => '380px',
			'flex_ratio'     => '1:1',
		);

		$grid_advanced = array(
			'name_size'      => '13px',
			'name_weight'    => '600',
			'name_padding'   => '',
			'name_margin'    => '8px',
			'name_radius'    => 0,
			'name_font'      => '',
			'name_align'     => 'center',
			'time_size'      => '18px',
			'time_weight'    => '800',
			'time_padding'   => '',
			'time_margin'    => '',
			'time_radius'    => 0,
			'time_font'      => '',
			'time_align'     => 'center',
			'layout_max_width' => '900px',
		);

		$slider_advanced = array(
			'name_size'      => '13px',
			'name_weight'    => '600',
			'name_padding'   => '',
			'name_margin'    => '',
			'name_radius'    => 0,
			'name_font'      => '',
			'name_align'     => 'center',
			'time_size'      => '18px',
			'time_weight'    => '800',
			'time_padding'   => '',
			'time_margin'    => '',
			'time_radius'    => 0,
			'time_font'      => '',
			'time_align'     => 'center',
			'layout_max_width' => '500px',
		);

		return array(
			'card'   => array_merge(
				array(
					'show_location'    => true,
					'show_hijri'       => true,
					'show_gregorian'   => true,
					'show_next_badge'  => true,
				),
				$prayer_toggles,
				$card_advanced
			),
			'grid'   => array_merge(
				array(
					'show_location'   => true,
					'show_hijri'      => true,
					'show_next_badge' => true,
				),
				$prayer_toggles,
				$grid_advanced
			),
			'slider' => array_merge(
				array(
					'show_location'   => true,
					'show_next_badge' => true,
				),
				$prayer_toggles,
				$slider_advanced
			),
			'ticker' => array_merge(
				array(
					'show_location' => true,
					'show_hijri'    => true,
				),
				$prayer_toggles
			),
			'modal'  => array_merge(
				array(
					'show_location'   => true,
					'show_hijri'      => true,
					'show_gregorian'  => true,
					'show_next_badge' => true,
				),
				$prayer_toggles,
				$card_advanced
			),
		);
	}

	/**
	 * Get the list of valid layout keys.
	 *
	 * @return array
	 */
	public static function get_layout_keys() {
		return array_keys( self::get_schema() );
	}

	/**
	 * Get the saved (or default) settings for a layout.
	 *
	 * @param string $layout Layout key.
	 * @return array
	 */
	public static function get_settings( $layout ) {
		$schema = self::get_schema();
		if ( ! isset( $schema[ $layout ] ) ) {
			return array();
		}

		$defaults = $schema[ $layout ];
		$saved    = get_option( 'jetprayer_display_' . $layout, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return array_merge( $defaults, array_intersect_key( $saved, $defaults ) );
	}

	/**
	 * Whether a layout's settings have ever been explicitly saved.
	 * Used to decide whether to inject CSS overrides on the frontend at all,
	 * so fresh installs render pixel-identical to the original hardcoded look.
	 *
	 * @param string $layout Layout key.
	 * @return bool
	 */
	public static function has_saved_settings( $layout ) {
		return false !== get_option( 'jetprayer_display_' . $layout, false );
	}

	/**
	 * Sanitize raw incoming settings against the schema for a layout.
	 * Unknown keys are ignored; missing keys fall back to schema defaults.
	 *
	 * @param string $layout Layout key.
	 * @param array  $raw    Raw input data.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $layout, $raw ) {
		$schema = self::get_schema();
		if ( ! isset( $schema[ $layout ] ) ) {
			return array();
		}

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$defaults  = $schema[ $layout ];
		$sanitized = array();

		foreach ( $defaults as $key => $default_value ) {
			$incoming = isset( $raw[ $key ] ) ? $raw[ $key ] : null;

			if ( is_bool( $default_value ) ) {
				// Toggle field.
				if ( null === $incoming ) {
					$sanitized[ $key ] = false;
				} else {
					$sanitized[ $key ] = in_array( $incoming, array( true, 1, '1', 'true', 'on' ), true );
				}
			} elseif ( is_numeric( $default_value ) ) {
				// Numeric field, clamped.
				$sanitized[ $key ] = null !== $incoming ? max( 0, min( 100, intval( $incoming ) ) ) : $default_value;
			} elseif ( strpos( $key, 'font' ) !== false || strpos( $key, 'size' ) !== false || strpos( $key, 'padding' ) !== false || strpos( $key, 'margin' ) !== false || strpos( $key, 'weight' ) !== false || strpos( $key, 'align' ) !== false || strpos( $key, 'width' ) !== false || strpos( $key, 'ratio' ) !== false ) {
				// Advanced CSS text fields
				$sanitized[ $key ] = null !== $incoming ? sanitize_text_field( $incoming ) : $default_value;
			} else {
				// Color field.
				$color = is_string( $incoming ) ? sanitize_hex_color( $incoming ) : '';
				$sanitized[ $key ] = $color ? $color : $default_value;
			}
		}

		return $sanitized;
	}
}
