<?php
/**
 * Shortcodes and layout rendering engine for JetPrayer.
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

class JetPrayer_Shortcodes {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'jetprayer', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register public style and script files.
	 */
	public function register_assets() {
		$css_file = JETPRAYER_PATH . 'public/css/public.css';
		$js_file  = JETPRAYER_PATH . 'public/js/public.js';

		$css_ver = file_exists( $css_file ) ? filemtime( $css_file ) : JETPRAYER_VERSION;
		$js_ver  = file_exists( $js_file ) ? filemtime( $js_file ) : JETPRAYER_VERSION;

		wp_register_style(
			'jetprayer-public-css',
			JETPRAYER_URL . 'public/css/public.css',
			array(),
			$css_ver
		);

		wp_register_script(
			'jetprayer-public-js',
			JETPRAYER_URL . 'public/js/public.js',
			array( 'jquery' ),
			$js_ver,
			true
		);

		// Give the city switcher's AJAX call the real REST base URL instead of
		// a hardcoded "/wp-json/..." path, which 404s on sites where pretty
		// permalinks aren't rewriting REST routes (rest_url() falls back to
		// the ?rest_route= query form in that case).
		wp_localize_script( 'jetprayer-public-js', 'jetprayerPublic', array(
			'restUrl' => esc_url_raw( rest_url( 'jetprayer/v1' ) ),
		) );

		// Attach every layout's saved display-settings override CSS to the
		// registered stylesheet right now, while still inside wp_enqueue_scripts.
		// This must happen before the stylesheet is ever printed: Elementor's
		// widget get_style_depends() preloads/prints this handle early (in time
		// for wp_head), so attaching the override later from inside the actual
		// shortcode/widget render() call is too late - WP_Styles has already
		// printed the handle by then and silently drops any inline data added
		// after the fact.
		foreach ( JetPrayer_Display_Settings::get_layout_keys() as $layout ) {
			if ( ! JetPrayer_Display_Settings::has_saved_settings( $layout ) ) {
				continue;
			}
			$css = $this->build_override_css( $layout, JetPrayer_Display_Settings::get_settings( $layout ) );
			if ( $css ) {
				wp_add_inline_style( 'jetprayer-public-css', $css );
			}
		}
	}

	/**
	 * Render the shortcode [jetprayer layout="..."]
	 */
	public function render_shortcode( $atts ) {
		$args = shortcode_atts( array(
			'layout'             => 'card', // card, grid, slider, ticker, modal
			'date'               => 'today',
			'method'             => '', // AlAdhan calculation method ID; empty = use the default from Settings & Sync
			'city'               => '',
			'country'            => '',
			'show_switcher'      => 'false',
			'switcher_countries' => '',
			'switcher_cities'    => '',
		), $atts, 'jetprayer' );

		$layout = in_array( $args['layout'], JetPrayer_Display_Settings::get_layout_keys(), true )
			? $args['layout']
			: 'card';

		// An empty/invalid method falls back to JetPrayer_DB's own default (the saved jetprayer_method option).
		$method_id = null;
		if ( 'all' === $args['method'] || 'any' === $args['method'] ) {
			$method_id = 'all';
		} elseif ( '' !== $args['method'] && array_key_exists( intval( $args['method'] ), JetPrayer_API::get_calculation_methods() ) ) {
			$method_id = intval( $args['method'] );
		}

		// Enqueue the registered frontend scripts/styles dynamically
		wp_enqueue_style( 'jetprayer-public-css' );
		wp_enqueue_script( 'jetprayer-public-js' );

		$settings = JetPrayer_Display_Settings::get_settings( $layout );

		// Determine target date
		$target_date = current_time( 'Y-m-d' );
		if ( 'tomorrow' === $args['date'] ) {
			$target_date = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $args['date'] ) ) {
			$target_date = $args['date'];
		}

		$locations = JetPrayer_DB::get_synced_locations();
		if ( empty( $locations ) ) {
			return '<div class="jetprayer-alert jetprayer-alert-warning">' . esc_html__( 'No prayer times found in database. Please sync in the dashboard.', 'jetprayer' ) . '</div>';
		}

		// get_synced_locations() is DISTINCT per city+country+method_id (the
		// admin "Locations & Methods" panel needs that granularity), so the
		// same city synced under multiple methods yields duplicate rows here.
		// The switcher only cares about unique city/country pairs.
		$jp_seen_locations = array();
		$locations = array_values( array_filter( $locations, function( $loc ) use ( &$jp_seen_locations ) {
			$key = trim( strtolower( $loc['city'] ) ) . '|' . trim( strtolower( $loc['country'] ) );
			if ( isset( $jp_seen_locations[ $key ] ) ) {
				return false;
			}
			$jp_seen_locations[ $key ] = true;
			return true;
		} ) );

		// Resolve Country
		$resolved_country = ! empty( $args['country'] ) ? trim( $args['country'] ) : get_option( 'jetprayer_country', '' );
		if ( empty( $resolved_country ) && ! empty( $locations ) ) {
			$resolved_country = $locations[0]['country'];
		}

		// Resolve Cities based on resolved country
		$candidate_cities = array();
		if ( ! empty( $args['city'] ) ) {
			$input_cities = array_map( 'trim', explode( ',', $args['city'] ) );
			$seen_cities = array();
			foreach ( $input_cities as $c ) {
				if ( empty( $c ) ) {
					continue;
				}
				$c_lower = strtolower( $c );
				foreach ( $locations as $loc ) {
					if ( trim( strtolower( $loc['country'] ) ) === trim( strtolower( $resolved_country ) ) && trim( strtolower( $loc['city'] ) ) === $c_lower ) {
						if ( ! in_array( $c_lower, $seen_cities, true ) ) {
							$seen_cities[] = $c_lower;
							$candidate_cities[] = $loc['city'];
						}
					}
				}
			}
		} else {
			// No city specified: Load all synced cities for that country
			$seen_cities = array();
			foreach ( $locations as $loc ) {
				if ( trim( strtolower( $loc['country'] ) ) === trim( strtolower( $resolved_country ) ) ) {
					$c_lower = strtolower( trim( $loc['city'] ) );
					if ( ! in_array( $c_lower, $seen_cities, true ) ) {
						$seen_cities[] = $c_lower;
						$candidate_cities[] = $loc['city'];
					}
				}
			}
		}

		if ( empty( $candidate_cities ) ) {
			// translators: %s: country name.
			return '<div class="jetprayer-alert jetprayer-alert-warning">' . sprintf( esc_html__( 'No synced cities found in database for country: %s. Please sync in the dashboard.', 'jetprayer' ), esc_html( $resolved_country ) ) . '</div>';
		}

		// Automatically show switcher if there are multiple cities
		$show_switcher = ( count( $candidate_cities ) > 1 );
		$current_city  = $candidate_cities[0];

		$timings = JetPrayer_DB::get_timings_for_date( $target_date, $method_id, $current_city, $resolved_country );

		if ( ! $timings ) {
			// translators: 1: city name, 2: country name.
			return '<div class="jetprayer-alert jetprayer-alert-warning">' . sprintf( esc_html__( 'No prayer times found in database for location: %1$s, %2$s. Please sync in the dashboard.', 'jetprayer' ), esc_html( $current_city ), esc_html( $resolved_country ) ) . '</div>';
		}

		// Calculate Next Prayer
		$next_prayer_key = $this->get_next_prayer( $timings );

		$location_name = ! empty( $timings['city'] )
			? $timings['city'] . ( ! empty( $timings['country'] ) ? ', ' . $timings['country'] : '' )
			: ( get_option( 'jetprayer_type', 'city' ) === 'coords'
				? get_option( 'jetprayer_latitude', '' ) . ', ' . get_option( 'jetprayer_longitude', '' )
				: $current_city . ( ! empty( $resolved_country ) ? ', ' . $resolved_country : '' ) );

		// Build the switcher options array matching the structure of $locations
		$switcher_locations = array();
		foreach ( $candidate_cities as $city_name ) {
			$switcher_locations[] = array(
				'city'    => $city_name,
				'country' => $resolved_country
			);
		}

		// Build City Switcher HTML if enabled
		$switcher_html = $this->render_city_switcher( $show_switcher, $current_city, $resolved_country, $switcher_locations );

		ob_start();

		// Set up list of prayers to show, filtered by this layout's visibility toggles
		$prayers_list = array(
			'imsak'   => __( 'Imsak', 'jetprayer' ),
			'fajr'    => __( 'Fajr', 'jetprayer' ),
			'sunrise' => __( 'Sunrise', 'jetprayer' ),
			'dhuhr'   => __( 'Dhuhr', 'jetprayer' ),
			'asr'     => __( 'Asr', 'jetprayer' ),
			'maghrib' => __( 'Maghrib', 'jetprayer' ),
			'isha'    => __( 'Isha', 'jetprayer' ),
		);
		$prayers_list = $this->filter_visible_prayers( $prayers_list, $settings );

		switch ( $layout ) {
			case 'grid':
				$this->render_grid_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, $switcher_html, $method_id );
				break;
			case 'slider':
				$this->render_slider_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, $switcher_html, $method_id );
				break;
			case 'ticker':
				$this->render_ticker_layout( $prayers_list, $timings, $location_name, $settings, $switcher_html, $method_id );
				break;
			case 'modal':
				$this->render_modal_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $target_date, $settings, $method_id, $switcher_html );
				break;
			case 'card':
			default:
				$this->render_card_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, '', $switcher_html, $method_id );
				break;
		}

		$html = ob_get_clean();

		// Escape returning markup with late sanitization
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['select'] = array(
			'class'      => true,
			'id'         => true,
			'name'       => true,
			'aria-label' => true,
		);
		$allowed_html['option'] = array(
			'value'    => true,
			'selected' => true,
		);

		if ( ! isset( $allowed_html['div'] ) ) {
			$allowed_html['div'] = array();
		}
		$allowed_html['div'] = array_merge( $allowed_html['div'], array(
			'class'        => true,
			'id'           => true,
			'data-layout'  => true,
			'data-city'    => true,
			'data-country' => true,
			'data-method'  => true,
			'data-date'    => true,
		) );

		if ( ! isset( $allowed_html['button'] ) ) {
			$allowed_html['button'] = array();
		}
		$allowed_html['button'] = array_merge( $allowed_html['button'], array(
			'type'      => true,
			'class'     => true,
			'data-date' => true,
			'id'        => true,
		) );

		if ( ! isset( $allowed_html['th'] ) ) {
			$allowed_html['th'] = array();
		}
		$allowed_html['th'] = array_merge( $allowed_html['th'], array(
			'data-prayer' => true,
		) );

		if ( ! isset( $allowed_html['td'] ) ) {
			$allowed_html['td'] = array();
		}
		$allowed_html['td'] = array_merge( $allowed_html['td'], array(
			'data-prayer' => true,
		) );

		if ( ! isset( $allowed_html['span'] ) ) {
			$allowed_html['span'] = array();
		}
		$allowed_html['span'] = array_merge( $allowed_html['span'], array(
			'class'       => true,
			'data-prayer' => true,
			'hidden'      => true,
		) );

		if ( ! isset( $allowed_html['li'] ) ) {
			$allowed_html['li'] = array();
		}
		$allowed_html['li'] = array_merge( $allowed_html['li'], array(
			'class'       => true,
			'data-prayer' => true,
		) );

		return wp_kses( $html, $allowed_html );
	}

	/**
	 * Filter the prayers list down to only the ones toggled visible in settings.
	 */
	private function filter_visible_prayers( $prayers_list, $settings ) {
		return array_filter( $prayers_list, function( $key ) use ( $settings ) {
			$toggle_key = 'show_' . $key;
			return ! array_key_exists( $toggle_key, $settings ) || ! empty( $settings[ $toggle_key ] );
		}, ARRAY_FILTER_USE_KEY );
	}

	/**
	 * Calculate which is the next prayer.
	 */
	private function get_next_prayer( $timings ) {
		$current_time = current_time( 'H:i' );
		$prayers = array( 'imsak', 'fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha' );

		foreach ( $prayers as $p ) {
			if ( ! empty( $timings[ $p ] ) ) {
				if ( $timings[ $p ] > $current_time ) {
					return $p;
				}
			}
		}
		// If past Isha, the next is Imsak (of next day, but visually we highlight imsak on card)
		return 'imsak';
	}

	/**
	 * Render the city switcher select element.
	 */
	private function render_city_switcher( $show_switcher, $current_city, $current_country, $locations ) {
		if ( 'true' !== $show_switcher && true !== $show_switcher ) {
			return '';
		}

		if ( empty( $locations ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="jp-switcher-container">
			<select class="jp-city-switcher" aria-label="<?php esc_attr_e( 'Select City', 'jetprayer' ); ?>">
				<?php foreach ( $locations as $loc ) :
					$val = $loc['city'] . '|' . $loc['country'];
					$label = $loc['city'] . ( ! empty( $loc['country'] ) ? ', ' . $loc['country'] : '' );
					$is_selected = ( trim( strtolower( $loc['city'] ) ) === trim( strtolower( $current_city ) ) && trim( strtolower( $loc['country'] ) ) === trim( strtolower( $current_country ) ) );
					?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $is_selected ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Card Layout
	 */
	private function render_card_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, $extra_class = '', $switcher_html = '', $method_id = '' ) {
		$wrapper_class = 'jp-container jp-card-layout' . ( $extra_class ? ' ' . $extra_class : '' );
		$show_header   = ! empty( $settings['show_location'] ) || ! empty( $settings['show_hijri'] ) || ! empty( $settings['show_gregorian'] ) || ! empty( $switcher_html );
		$data_method   = ( null !== $method_id && '' !== $method_id ) ? $method_id : $timings['method_id'];
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>"
			data-layout="card"
			data-city="<?php echo esc_attr( $timings['city'] ); ?>"
			data-country="<?php echo esc_attr( $timings['country'] ); ?>"
			data-method="<?php echo esc_attr( $data_method ); ?>"
			data-date="<?php echo esc_attr( $timings['prayer_date'] ); ?>">
			<?php if ( $show_header ) : ?>
			<div class="jp-header">
				<?php if ( ! empty( $switcher_html ) ) : ?>
					<div class="jp-switcher-title-wrapper">
						<?php echo wp_kses( $switcher_html, $this->get_switcher_allowed_html() ); ?>
					</div>
				<?php elseif ( ! empty( $settings['show_location'] ) ) : ?>
					<h3 class="jp-title"><?php echo esc_html( $location_name ); ?></h3>
				<?php endif; ?>
				<?php if ( ! empty( $settings['show_hijri'] ) ) : ?>
					<span class="jp-hijri-date"><?php echo esc_html( $timings['hijri_date'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $settings['show_gregorian'] ) ) : ?>
					<span class="jp-greg-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $timings['prayer_date'] ) ) ); ?></span>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			<div class="jp-body">
				<ul class="jp-prayer-list">
					<?php foreach ( $prayers_list as $key => $label ) :
						$is_next = ( $key === $next_prayer_key );
						?>
						<li class="jp-prayer-item <?php echo esc_attr( $is_next ? 'jp-next-prayer' : '' ); ?>" data-prayer="<?php echo esc_attr( $key ); ?>">
							<span class="jp-prayer-name">
								<?php echo esc_html( $label ); ?>
								<?php if ( ! empty( $settings['show_next_badge'] ) ) : ?>
									<span class="jp-next-badge <?php echo esc_attr( $is_next ? '' : 'hidden' ); ?>" <?php echo $is_next ? '' : 'hidden'; ?>><?php esc_html_e( 'Next', 'jetprayer' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="jp-prayer-time font-mono"><?php echo esc_html( $timings[ $key ] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Grid Layout
	 */
	private function render_grid_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, $switcher_html = '', $method_id = '' ) {
		$show_header = ! empty( $settings['show_location'] ) || ! empty( $settings['show_hijri'] ) || ! empty( $switcher_html );
		$data_method = ( null !== $method_id && '' !== $method_id ) ? $method_id : $timings['method_id'];
		?>
		<div class="jp-container jp-grid-layout"
			data-layout="grid"
			data-city="<?php echo esc_attr( $timings['city'] ); ?>"
			data-country="<?php echo esc_attr( $timings['country'] ); ?>"
			data-method="<?php echo esc_attr( $data_method ); ?>"
			data-date="<?php echo esc_attr( $timings['prayer_date'] ); ?>">
			<?php if ( $show_header ) : ?>
			<div class="jp-header">
				<?php if ( ! empty( $switcher_html ) ) : ?>
					<div class="jp-switcher-title-wrapper">
						<?php echo wp_kses( $switcher_html, $this->get_switcher_allowed_html() ); ?>
					</div>
				<?php elseif ( ! empty( $settings['show_location'] ) ) : ?>
					<h3 class="jp-title"><?php echo esc_html( $location_name ); ?></h3>
				<?php endif; ?>
				<?php if ( ! empty( $settings['show_hijri'] ) ) : ?>
					<span class="jp-hijri-date"><?php echo esc_html( $timings['hijri_date'] ); ?></span>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			<div class="jp-grid-body">
				<?php foreach ( $prayers_list as $key => $label ) :
					$is_next = ( $key === $next_prayer_key );
					?>
					<div class="jp-grid-item <?php echo esc_attr( $is_next ? 'jp-next-prayer' : '' ); ?>" data-prayer="<?php echo esc_attr( $key ); ?>">
						<span class="jp-grid-label"><?php echo esc_html( $label ); ?></span>
						<span class="jp-grid-time font-mono"><?php echo esc_html( $timings[ $key ] ); ?></span>
						<?php if ( ! empty( $settings['show_next_badge'] ) ) : ?>
							<span class="jp-next-indicator <?php echo esc_attr( $is_next ? '' : 'hidden' ); ?>" <?php echo $is_next ? '' : 'hidden'; ?>></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Slider Layout (Vanilla JS powered Carousel)
	 */
	private function render_slider_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, $switcher_html = '', $method_id = '' ) {
		$data_method = ( null !== $method_id && '' !== $method_id ) ? $method_id : $timings['method_id'];
		?>
		<div class="jp-container jp-slider-layout"
			data-layout="slider"
			data-city="<?php echo esc_attr( $timings['city'] ); ?>"
			data-country="<?php echo esc_attr( $timings['country'] ); ?>"
			data-method="<?php echo esc_attr( $data_method ); ?>"
			data-date="<?php echo esc_attr( $timings['prayer_date'] ); ?>">
			<div class="jp-header">
				<?php if ( ! empty( $switcher_html ) ) : ?>
					<div class="jp-switcher-title-wrapper">
						<?php echo wp_kses( $switcher_html, $this->get_switcher_allowed_html() ); ?>
					</div>
				<?php elseif ( ! empty( $settings['show_location'] ) ) : ?>
					<h3 class="jp-title"><?php echo esc_html( $location_name ); ?></h3>
				<?php endif; ?>
				<div class="jp-slider-arrows">
					<button type="button" class="jp-slider-prev">&larr;</button>
					<button type="button" class="jp-slider-next">&rarr;</button>
				</div>
			</div>
			<div class="jp-slider-viewport">
				<div class="jp-slider-track">
					<?php foreach ( $prayers_list as $key => $label ) :
						$is_next = ( $key === $next_prayer_key );
						?>
						<div class="jp-slider-item <?php echo esc_attr( $is_next ? 'jp-next-prayer' : '' ); ?>" data-prayer="<?php echo esc_attr( $key ); ?>">
							<span class="jp-slider-label"><?php echo esc_html( $label ); ?></span>
							<span class="jp-slider-time font-mono"><?php echo esc_html( $timings[ $key ] ); ?></span>
							<?php if ( ! empty( $settings['show_next_badge'] ) ) : ?>
								<span class="jp-next-badge <?php echo esc_attr( $is_next ? '' : 'hidden' ); ?>" <?php echo $is_next ? '' : 'hidden'; ?>><?php esc_html_e( 'Next', 'jetprayer' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Ticker Layout (Scrolling ticker band)
	 */
	private function render_ticker_layout( $prayers_list, $timings, $location_name, $settings, $switcher_html = '', $method_id = '' ) {
		$data_method = ( null !== $method_id && '' !== $method_id ) ? $method_id : $timings['method_id'];
		?>
		<div class="jp-ticker-layout"
			data-layout="ticker"
			data-city="<?php echo esc_attr( $timings['city'] ); ?>"
			data-country="<?php echo esc_attr( $timings['country'] ); ?>"
			data-method="<?php echo esc_attr( $data_method ); ?>"
			data-date="<?php echo esc_attr( $timings['prayer_date'] ); ?>">
			<?php if ( ! empty( $switcher_html ) ) : ?>
				<div class="jp-ticker-title jp-switcher-title">
					<?php echo wp_kses( $switcher_html, $this->get_switcher_allowed_html() ); ?>
				</div>
			<?php elseif ( ! empty( $settings['show_location'] ) ) : ?>
				<div class="jp-ticker-title"><?php echo esc_html( $location_name ); ?></div>
			<?php endif; ?>
			<div class="jp-ticker-track-viewport">
				<div class="jp-ticker-track">
					<div class="jp-ticker-items">
						<?php for ( $i = 0; $i < 2; $i++ ) : ?>
							<span class="jp-ticker-scroll-group">
								<?php foreach ( $prayers_list as $key => $label ) : ?>
									<span class="jp-ticker-item" data-prayer="<?php echo esc_attr( $key ); ?>">
										<span class="jp-ticker-item-label"><?php echo esc_html( $label ); ?>:</span>
										<span class="jp-ticker-item-time font-mono"><?php echo esc_html( $timings[ $key ] ); ?></span>
									</span>
									<span class="jp-ticker-sep">|</span>
								<?php endforeach; ?>
								<?php if ( ! empty( $settings['show_hijri'] ) ) : ?>
									<span class="jp-ticker-hijri"><?php echo esc_html( $timings['hijri_date'] ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( 0 === $i ) : ?>
								<span class="jp-ticker-bullet">&bull;</span>
							<?php endif; ?>
						<?php endfor; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Modal Layout (Today's Card + Trigger for monthly timings overlay)
	 */
	private function render_modal_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $target_date, $settings, $method_id = null, $switcher_html = '' ) {
		// Render the mini-card portion with the Modal tab's own (independent) settings
		$this->render_card_layout( $prayers_list, $timings, $next_prayer_key, $location_name, $settings, 'jp-modal-mini-card', $switcher_html, $method_id );

		// Render Modal trigger button
		?>
		<div class="jp-modal-trigger-wrapper">
			<button type="button" class="jp-modal-open-btn jetprayer-btn" data-date="<?php echo esc_attr( $target_date ); ?>">
				<span class="dashicons dashicons-calendar-alt"></span>
				<?php esc_html_e( 'View Monthly Timetable', 'jetprayer' ); ?>
			</button>
		</div>

		<!-- Modal Overlay Markup -->
		<div class="jp-modal-overlay hidden">
			<div class="jp-modal-box">
				<div class="jp-modal-header">
					<h4><?php
						// translators: %s: location name.
						echo esc_html( sprintf( __( 'Monthly Prayer Timetable - %s', 'jetprayer' ), $location_name ) );
					?></h4>
					<button type="button" class="jp-modal-close">&times;</button>
				</div>
				<div class="jp-modal-body">
					<?php
					// Fetch month timings
					$year = gmdate( 'Y', strtotime( $target_date ) );
					$month = gmdate( 'm', strtotime( $target_date ) );
					$days_in_month = gmdate( 't', strtotime( $target_date ) );
					$start = "$year-$month-01";
					$end = "$year-$month-$days_in_month";

					$monthly_timings = JetPrayer_DB::get_timings_for_range( $start, $end, $method_id, $timings['city'], $timings['country'] );

					// The monthly table never includes an Imsak column (matches the
					// original fixed table layout); other toggled-off prayers are
					// excluded from both the mini-card and this table.
					$table_columns = $prayers_list;
					unset( $table_columns['imsak'] );

					if ( empty( $monthly_timings ) ) {
						echo '<p class="text-center">' . esc_html__( 'No monthly data found. Please run sync from admin settings.', 'jetprayer' ) . '</p>';
					} else {
						?>
						<table class="jp-modal-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'jetprayer' ); ?></th>
									<?php foreach ( $table_columns as $key => $label ) : ?>
										<th data-prayer="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $monthly_timings as $row ) :
									$is_today = ( $row['prayer_date'] === current_time( 'Y-m-d' ) );
									?>
									<tr class="<?php echo esc_attr( $is_today ? 'jp-modal-today-row' : '' ); ?>" data-date="<?php echo esc_attr( $row['prayer_date'] ); ?>">
										<td class="font-mono">
											<strong><?php echo esc_html( gmdate( 'd', strtotime( $row['prayer_date'] ) ) ); ?></strong>
											<small><?php echo esc_html( gmdate( 'M', strtotime( $row['prayer_date'] ) ) ); ?></small>
										</td>
										<?php foreach ( $table_columns as $key => $label ) : ?>
											<td class="font-mono" data-prayer="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $row[ $key ] ); ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Map of layout => [ CSS selector => [ css-var => settings key ] ].
	 * Settings keys suffixed with ":px" are rendered as integer pixel values.
	 */
	private function get_override_map() {
		$card_vars = array(
			'--jp-layout-bg'        => 'bg_color',
			'--jp-layout-border'    => 'border_color',
			'--jp-layout-radius'    => 'radius:px',
			'--jp-location-color'   => 'location_color',
			'--jp-hijri-color'      => 'hijri_color',
			'--jp-greg-color'       => 'gregorian_color',
			'--jp-label-color'      => 'label_color',
			'--jp-time-color'       => 'time_color',
			'--jp-next-color'       => 'next_color',
			'--jp-name-bg'          => 'name_bg_color',
			'--jp-time-bg'          => 'time_bg_color',
			'--jp-name-size'        => 'name_size',
			'--jp-name-weight'      => 'name_weight',
			'--jp-name-padding'     => 'name_padding',
			'--jp-name-margin'      => 'name_margin',
			'--jp-name-radius'      => 'name_radius:px',
			'--jp-name-font'        => 'name_font',
			'--jp-name-align'       => 'name_align',
			'--jp-time-size'        => 'time_size',
			'--jp-time-weight'      => 'time_weight',
			'--jp-time-padding'     => 'time_padding',
			'--jp-time-margin'      => 'time_margin',
			'--jp-time-radius'      => 'time_radius:px',
			'--jp-time-font'        => 'time_font',
			'--jp-time-align'       => 'time_align',
			'--jp-layout-max-width' => 'layout_max_width',
		);

		return array(
			'card'   => array(
				'.jp-card-layout:not(.jp-modal-mini-card)' => $card_vars,
			),
			'grid'   => array(
				'.jp-grid-layout' => array(
					'--jp-layout-bg'        => 'bg_color',
					'--jp-layout-border'    => 'border_color',
					'--jp-layout-radius'    => 'radius:px',
					'--jp-location-color'   => 'location_color',
					'--jp-hijri-color'      => 'hijri_color',
					'--jp-label-color'      => 'label_color',
					'--jp-time-color'       => 'time_color',
					'--jp-next-color'       => 'next_color',
					'--jp-name-size'        => 'name_size',
					'--jp-name-weight'      => 'name_weight',
					'--jp-name-padding'     => 'name_padding',
					'--jp-name-margin'      => 'name_margin',
					'--jp-name-radius'      => 'name_radius:px',
					'--jp-name-font'        => 'name_font',
					'--jp-name-align'       => 'name_align',
					'--jp-time-size'        => 'time_size',
					'--jp-time-weight'      => 'time_weight',
					'--jp-time-padding'     => 'time_padding',
					'--jp-time-margin'      => 'time_margin',
					'--jp-time-radius'      => 'time_radius:px',
					'--jp-time-font'        => 'time_font',
					'--jp-time-align'       => 'time_align',
					'--jp-layout-max-width' => 'layout_max_width',
				),
			),
			'slider' => array(
				'.jp-slider-layout' => array(
					'--jp-layout-bg'        => 'bg_color',
					'--jp-layout-border'    => 'border_color',
					'--jp-layout-radius'    => 'radius:px',
					'--jp-location-color'   => 'location_color',
					'--jp-label-color'      => 'label_color',
					'--jp-time-color'       => 'time_color',
					'--jp-next-color'       => 'next_color',
					'--jp-name-size'        => 'name_size',
					'--jp-name-weight'      => 'name_weight',
					'--jp-name-padding'     => 'name_padding',
					'--jp-name-margin'      => 'name_margin',
					'--jp-name-radius'      => 'name_radius:px',
					'--jp-name-font'        => 'name_font',
					'--jp-name-align'       => 'name_align',
					'--jp-time-size'        => 'time_size',
					'--jp-time-weight'      => 'time_weight',
					'--jp-time-padding'     => 'time_padding',
					'--jp-time-margin'      => 'time_margin',
					'--jp-time-radius'      => 'time_radius:px',
					'--jp-time-font'        => 'time_font',
					'--jp-time-align'       => 'time_align',
					'--jp-layout-max-width' => 'layout_max_width',
				),
			),
			'ticker' => array(
				'.jp-ticker-layout' => array(
					'--jp-ticker-bg'         => 'bg_color',
					'--jp-ticker-border'     => 'border_color',
					'--jp-ticker-radius'     => 'radius:px',
					'--jp-ticker-title-bg'   => 'title_bg_color',
					'--jp-ticker-title-color' => 'title_text_color',
					'--jp-ticker-text-color' => 'text_color',
				),
			),
			'modal'  => array(
				'.jp-modal-mini-card' => $card_vars,
				'.jp-modal-open-btn'  => array(
					'--jp-modal-trigger-bg'    => 'trigger_btn_bg',
					'--jp-modal-trigger-color' => 'trigger_btn_text',
				),
				'.jp-modal-box' => array(
					'--jp-modal-box-bg'     => 'box_bg',
					'--jp-modal-box-border' => 'box_border_color',
					'--jp-modal-box-radius' => 'box_radius:px',
				),
				'.jp-modal-table th' => array(
					'--jp-modal-table-header-color' => 'table_header_color',
				),
				'.jp-modal-table tr.jp-modal-today-row' => array(
					'--jp-modal-today-color' => 'table_today_highlight_color',
				),
			),
		);
	}

	/**
	 * Get the allowed HTML tags whitelist for the city switcher select element.
	 *
	 * @return array Whitelist array.
	 */
	private function get_switcher_allowed_html() {
		return array(
			'div'    => array(
				'class' => true,
			),
			'select' => array(
				'class'      => true,
				'aria-label' => true,
			),
			'option' => array(
				'value'    => true,
				'selected' => true,
			),
		);
	}

	/**
	 * Resolve a single settings value into its CSS value string, honoring the
	 * ":px" suffix convention used by get_override_map().
	 */
	private function format_css_value( $settings, $settings_key ) {
		$is_px = false;
		if ( ':px' === substr( $settings_key, -3 ) ) {
			$settings_key = substr( $settings_key, 0, -3 );
			$is_px        = true;
		}

		if ( ! isset( $settings[ $settings_key ] ) ) {
			return null;
		}

		$value = $settings[ $settings_key ];

		if ( $is_px ) {
			return intval( $value ) . 'px';
		}

		// Tighten sanitization for advanced text-based CSS variable outputs to prevent CSS injection.
		// Color values are hex verified (e.g. #ffffff) by schema sanitization, so we only need regex check for text.
		if ( is_string( $value ) && '#' !== substr( $value, 0, 1 ) ) {
			if ( ! preg_match( '/^[a-zA-Z0-9\s.,()%\!\'\"\-_]+$/', $value ) ) {
				return ''; // Block invalid characters to avoid CSS injection
			}
		}

		return $value;
	}

	/**
	 * Build the CSS override string for a layout's saved settings.
	 */
	private function build_override_css( $layout, $settings ) {
		$map = $this->get_override_map();
		if ( ! isset( $map[ $layout ] ) ) {
			return '';
		}

		$css = '';
		foreach ( $map[ $layout ] as $selector => $vars ) {
			$decls = '';
			foreach ( $vars as $css_var => $settings_key ) {
				$value = $this->format_css_value( $settings, $settings_key );
				if ( null === $value ) {
					continue;
				}
				// Normalize flex alignments to standard text-align for block elements in Grid/Slider
				if ( ( '--jp-name-align' === $css_var || '--jp-time-align' === $css_var ) && ( '.jp-grid-layout' === $selector || '.jp-slider-layout' === $selector ) ) {
					if ( 'flex-start' === $value ) {
						$value = 'left';
					} elseif ( 'flex-end' === $value ) {
						$value = 'right';
					}
				}
				$decls .= $css_var . ':' . $value . ';';
			}
			// Dynamic flex ratio override for name and time spans
			if ( isset( $settings['flex_ratio'] ) && ( '.jp-card-layout:not(.jp-modal-mini-card)' === $selector || '.jp-modal-mini-card' === $selector ) ) {
				$parts = explode( ':', $settings['flex_ratio'] );
				if ( 2 === count( $parts ) ) {
					$decls .= '--jp-name-flex:' . intval( $parts[0] ) . ' 1 0% !important;';
					$decls .= '--jp-time-flex:' . intval( $parts[1] ) . ' 1 0% !important;';
				}
			}
			if ( $decls ) {
				$css .= $selector . '{' . $decls . '}';
			}
		}

		return $css;
	}
}
