<?php
/**
 * Elementor widget for JetPrayer.
 *
 * Wraps the same JetPrayer_Shortcodes::render_shortcode() engine used by the
 * [jetprayer] shortcode and the Gutenberg block, so all three surfaces stay
 * in sync automatically and share every layout/method/date feature.
 *
 * @link       https://wordpress.org/plugins/jetprayer/
 * @since      1.0.0
 *
 * @package    JetPrayer
 * @subpackage JetPrayer/includes/elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class JetPrayer_Elementor_Widget extends Widget_Base {

	/**
	 * Widget name (internal slug).
	 */
	public function get_name() {
		return 'jetprayer';
	}

	/**
	 * Widget title shown in the Elementor panel.
	 */
	public function get_title() {
		return __( 'JetPrayer - Prayer Times', 'jetprayer' );
	}

	/**
	 * Widget icon.
	 */
	public function get_icon() {
		return 'eicon-clock-o';
	}

	/**
	 * Widget category (registered in JetPrayer_Elementor::register_category()).
	 */
	public function get_categories() {
		return array( 'jetprayer' );
	}

	/**
	 * Search keywords.
	 */
	public function get_keywords() {
		return array( 'prayer', 'namaz', 'islamic', 'jetprayer', 'vakit', 'ezan' );
	}

	/**
	 * Build the registered hooks/scripts this widget depends on, so Elementor
	 * preloads them instead of relying on the late wp_enqueue_* calls inside
	 * the shortcode renderer (keeps editor preview snappy).
	 */
	public function get_style_depends() {
		return array( 'jetprayer-public-css' );
	}

	public function get_script_depends() {
		return array( 'jetprayer-public-js' );
	}

	/**
	 * Build the panel controls. Mirrors every attribute the [jetprayer]
	 * shortcode and the Gutenberg block support: layout, method, date.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'jetprayer_content_section',
			array(
				'label' => __( 'Prayer Times Settings', 'jetprayer' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'jetprayer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card'   => __( 'Premium Card', 'jetprayer' ),
					'grid'   => __( 'Responsive Grid', 'jetprayer' ),
					'slider' => __( 'Interactive Slider', 'jetprayer' ),
					'ticker' => __( 'Scrolling Ticker', 'jetprayer' ),
					'modal'  => __( 'Monthly Modal', 'jetprayer' ),
				),
			)
		);

		$this->add_control(
			'method',
			array(
				'label'       => __( 'Calculation Method', 'jetprayer' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->get_method_options(),
				'description' => __( 'Overrides the default method from Settings & Sync for this widget only. Shows only synced methods.', 'jetprayer' ),
			)
		);

		$this->add_control(
			'country',
			array(
				'label'       => __( 'Country Selection', 'jetprayer' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->get_country_options(),
				'description' => __( 'Select the country. Shows only synced countries from database.', 'jetprayer' ),
			)
		);

		$this->add_control(
			'city',
			array(
				'label'       => __( 'City Override(s)', 'jetprayer' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'e.g. Istanbul or Istanbul, Izmir', 'jetprayer' ),
				'description' => __( 'Optional. Leave blank to load all cities under the selected country. Or enter a single city (no dropdown) or comma-separated cities (custom dropdown). Only synced cities are supported.', 'jetprayer' ),
			)
		);

		$this->add_control(
			'date_mode',
			array(
				'label'   => __( 'Date', 'jetprayer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'today',
				'options' => array(
					'today'    => __( 'Today', 'jetprayer' ),
					'tomorrow' => __( 'Tomorrow', 'jetprayer' ),
					'custom'   => __( 'Custom Date', 'jetprayer' ),
				),
			)
		);

		$this->add_control(
			'custom_date',
			array(
				'label'          => __( 'Custom Date', 'jetprayer' ),
				'type'           => Controls_Manager::DATE_TIME,
				'picker_options' => array(
					'enableTime' => false,
				),
				'condition'      => array(
					'date_mode' => 'custom',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build the Calculation Method dropdown options showing only synced methods.
	 */
	private function get_method_options() {
		$options = array(
			''    => __( 'Use Default (Settings & Sync)', 'jetprayer' ),
			'all' => __( 'All Methods (No Method Constraint)', 'jetprayer' ),
		);
		$synced_ids = JetPrayer_DB::get_synced_method_ids();
		$all_methods = JetPrayer_API::get_calculation_methods();

		if ( ! empty( $synced_ids ) ) {
			foreach ( $synced_ids as $id ) {
				if ( isset( $all_methods[ $id ] ) ) {
					$options[ (string) $id ] = $id . ' — ' . $all_methods[ $id ];
				}
			}
		} else {
			// Fallback: If nothing synced yet, show all so they can sync/select
			foreach ( $all_methods as $id => $name ) {
				$options[ (string) $id ] = $id . ' — ' . $name;
			}
		}
		return $options;
	}

	/**
	 * Build the Country dropdown options from synced locations in database.
	 */
	private function get_country_options() {
		$options = array( '' => __( 'Use Default (Settings & Sync)', 'jetprayer' ) );
		$countries = JetPrayer_DB::get_synced_countries();
		if ( ! empty( $countries ) && is_array( $countries ) ) {
			foreach ( $countries as $country ) {
				$options[ $country ] = $country;
			}
		}
		return $options;
	}

	/**
	 * Render the widget by delegating to the shared shortcode renderer.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$date = 'today';
		if ( 'tomorrow' === $settings['date_mode'] ) {
			$date = 'tomorrow';
		} elseif ( 'custom' === $settings['date_mode'] && ! empty( $settings['custom_date'] ) ) {
			$date = gmdate( 'Y-m-d', strtotime( $settings['custom_date'] ) );
		}

		$shortcodes = new JetPrayer_Shortcodes();

		$output = $shortcodes->render_shortcode( array(
			'layout'  => $settings['layout'],
			'date'    => $date,
			'method'  => isset( $settings['method'] ) ? $settings['method'] : '',
			'city'    => isset( $settings['city'] ) ? $settings['city'] : '',
			'country' => isset( $settings['country'] ) ? $settings['country'] : '',
		) );

		// Custom allowed html list to support select/option and interactive data-attributes
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['select'] = array(
			'class'      => true,
			'id'         => true,
			'name'       => true,
			'aria-label' => true,
		);
		$allowed_html['optgroup'] = array(
			'label' => true,
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

		echo wp_kses( $output, $allowed_html );
	}
}
