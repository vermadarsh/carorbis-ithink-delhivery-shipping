<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/vermadarsh/
 * @since      1.0.0
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/includes
 * @author     Adarsh Verma <adarsh.srmcem@gmail.com>
 */
class Carorbis_Ithink_Delhivery_Shipping_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'carorbis-ithink-delhivery-shipping',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
