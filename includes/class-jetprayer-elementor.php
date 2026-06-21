<?php
/**
 * Elementor integration bootstrap for JetPrayer.
 *
 * Hooking these Elementor-specific actions is safe even when Elementor is
 * not installed at all: add_action() only stores a callback against a hook
 * name, it does not require the hook to ever fire. The Elementor classes
 * themselves are only touched inside the callbacks below, which only run
 * if Elementor actually fires them (i.e. Elementor is active).
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

class JetPrayer_Elementor {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register a dedicated "JetPrayer" category so the widget doesn't get
	 * lost among Elementor's general/basic widgets.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'jetprayer',
			array(
				'title' => __( 'JetPrayer', 'jetprayer' ),
				'icon'  => 'eicon-clock-o',
			)
		);
	}

	/**
	 * Register the JetPrayer widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widget( $widgets_manager ) {
		require_once JETPRAYER_PATH . 'includes/elementor/class-jetprayer-elementor-widget.php';
		$widgets_manager->register( new JetPrayer_Elementor_Widget() );
	}
}
