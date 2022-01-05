<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/vermadarsh/
 * @since             1.0.0
 * @package           Carorbis_Ithink_Delhivery_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Carorbis-iThink-Delhivery Shipping
 * Plugin URI:        https://github.com/vermadarsh/carorbis-ithink-delhivery-shipping/
 * Description:       This is a custom plugin for managing the shipping options for Carorbis.
 * Version:           1.0.0
 * Author:            Adarsh Verma
 * Author URI:        https://github.com/vermadarsh/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       carorbis-ithink-delhivery-shipping
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CIDS_PLUGIN_VERSION', '1.0.0' );

// Plugin path.
if ( ! defined( 'CIDS_PLUGIN_PATH' ) ) {
	define( 'CIDS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Plugin URL.
if ( ! defined( 'CIDS_PLUGIN_URL' ) ) {
	define( 'CIDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-carorbis-ithink-delhivery-shipping-activator.php
 */
function activate_carorbis_ithink_delhivery_shipping() {
	require_once CIDS_PLUGIN_PATH . 'includes/class-carorbis-ithink-delhivery-shipping-activator.php';
	Carorbis_Ithink_Delhivery_Shipping_Activator::activate();
}

register_activation_hook( __FILE__, 'activate_carorbis_ithink_delhivery_shipping' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-carorbis-ithink-delhivery-shipping-deactivator.php
 */
function deactivate_carorbis_ithink_delhivery_shipping() {
	require_once CIDS_PLUGIN_PATH . 'includes/class-carorbis-ithink-delhivery-shipping-deactivator.php';
	Carorbis_Ithink_Delhivery_Shipping_Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_carorbis_ithink_delhivery_shipping' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function cids_run_carorbis_ithink_delhivery_shipping() {
	// The core plugin class that is used to define internationalization, admin-specific hooks, and public-facing site hooks.
	require CIDS_PLUGIN_PATH . 'includes/class-carorbis-ithink-delhivery-shipping.php';
	$plugin = new Carorbis_Ithink_Delhivery_Shipping();
	$plugin->run();
}

/**
 * Check plugin initial requirements.
 */
function cids_plugins_loaded_callback() {
	$active_plugins = get_option( 'active_plugins' );
	$is_wc_active   = in_array( 'woocommerce/woocommerce.php', $active_plugins, true );

	if ( current_user_can( 'activate_plugins' ) && false === $is_wc_active ) {
		add_action( 'admin_notices', 'cids_admin_notices_callback' );
	} else {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cids_plugin_actions_callback' );
		cids_run_carorbis_ithink_delhivery_shipping();
	}
}

add_action( 'plugins_loaded', 'cids_plugins_loaded_callback' );

/**
 * Show admin notice for the required plugins not active or installed.
 *
 * @since 1.0.0
 */
function cids_admin_notices_callback() {
	$this_plugin_data = get_plugin_data( __FILE__ );
	$this_plugin      = $this_plugin_data['Name'];
	$wc_plugin        = 'WooCommerce';
	?>
	<div class="error">
		<p>
			<?php
			/* translators: 1: %s: strong tag open, 2: %s: strong tag close, 3: %s: this plugin, 4: %s: woocommerce plugin, 5: anchor tag for woocommerce plugin, 6: anchor tag close */
			echo wp_kses_post( sprintf( __( '%1$s%3$s%2$s is ineffective as it requires %1$s%4$s%2$s to be installed and active. Click %5$shere%6$s to install or activate it.', 'carorbis-ithink-delhivery-shipping' ), '<strong>', '</strong>', esc_html( $this_plugin ), esc_html( $wc_plugin ), '<a target="_blank" href="' . admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) . '">', '</a>' ) );
			?>
		</p>
	</div>
	<?php
}

/**
 * This function adds custom plugin actions.
 *
 * @param array $links Links array.
 * @return array
 * @since 1.0.0
 */
function cids_plugin_actions_callback( $links ) {
	$this_plugin_links = array(
		'<a title="' . __( 'Settings', 'carorbis-ithink-delhivery-shipping' ) . '" href="' . esc_url( admin_url( 'admin.php?page=wc-settings' ) ) . '">' . __( 'Settings', 'carorbis-ithink-delhivery-shipping' ) . '</a>',
	);

	return array_merge( $this_plugin_links, $links );
}
