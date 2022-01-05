<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/vermadarsh/
 * @since      1.0.0
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/includes
 * @author     Adarsh Verma <adarsh.srmcem@gmail.com>
 */
class Carorbis_Ithink_Delhivery_Shipping {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Carorbis_Ithink_Delhivery_Shipping_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = ( defined( 'CIDS_PLUGIN_VERSION' ) ) ? CIDS_PLUGIN_VERSION : '1.0.0';
		$this->plugin_name = 'carorbis-ithink-delhivery-shipping';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Carorbis_Ithink_Delhivery_Shipping_Loader. Orchestrates the hooks of the plugin.
	 * - Carorbis_Ithink_Delhivery_Shipping_i18n. Defines internationalization functionality.
	 * - Carorbis_Ithink_Delhivery_Shipping_Admin. Defines all hooks for the admin area.
	 * - Carorbis_Ithink_Delhivery_Shipping_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once CIDS_PLUGIN_PATH . 'includes/class-carorbis-ithink-delhivery-shipping-loader.php';

		// The class responsible for defining internationalization functionality of the plugin.
		require_once CIDS_PLUGIN_PATH . 'includes/class-carorbis-ithink-delhivery-shipping-i18n.php';

		// The file is responsible for defining all custom functions.
		require_once CIDS_PLUGIN_PATH . 'includes/carorbis-ithink-delhivery-shipping-functions.php';

		// The class responsible for defining all actions that occur in the admin area.
		require_once CIDS_PLUGIN_PATH . 'admin/class-carorbis-ithink-delhivery-shipping-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once CIDS_PLUGIN_PATH . 'public/class-carorbis-ithink-delhivery-shipping-public.php';

		$this->loader = new Carorbis_Ithink_Delhivery_Shipping_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Carorbis_Ithink_Delhivery_Shipping_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Carorbis_Ithink_Delhivery_Shipping_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Carorbis_Ithink_Delhivery_Shipping_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'cids_admin_enqueue_scripts_callback' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'cids_add_meta_boxes_callback' );
		$this->loader->add_action( 'wp_ajax_manifest_carorbis_order', $plugin_admin, 'cids_manifest_carorbis_order_callback' );
		$this->loader->add_filter( 'woocommerce_get_sections_shipping', $plugin_admin, 'cids_woocommerce_get_sections_shipping_callback' );
		$this->loader->add_filter( 'woocommerce_get_settings_shipping', $plugin_admin, 'cids_woocommerce_get_settings_shipping_callback', 10, 2 );
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'cids_manage_edit_shop_order_columns_callback' );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'cids_manage_shop_order_posts_custom_column_callback', 20, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Carorbis_Ithink_Delhivery_Shipping_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'cids_wp_enqueue_scripts_callback' );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'cids_woocommerce_thankyou_callback' );
		$this->loader->add_filter( 'woocommerce_my_account_my_orders_query', $plugin_public, 'cids_woocommerce_my_account_my_orders_query_callback', 99 );
		$this->loader->add_filter( 'wcfmmp_order_label_display', $plugin_public, 'cids_wcfmmp_order_label_display_callback', 20, 2 );
		$this->loader->add_action( 'wcfm_vendor_settings_update', $plugin_public, 'cids_wcfm_vendor_settings_update_callback', 20, 2 );
		$this->loader->add_action( 'user_register', $plugin_public, 'cids_user_register_callback' );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $plugin_public, 'cids_woocommerce_shipping_methods_callback' );
		$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'cids_woocommerce_shipping_init_callback' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Carorbis_Ithink_Delhivery_Shipping_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
