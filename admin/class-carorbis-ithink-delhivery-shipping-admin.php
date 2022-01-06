<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/vermadarsh/
 * @since      1.0.0
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/admin
 * @author     Adarsh Verma <adarsh.srmcem@gmail.com>
 */
class Carorbis_Ithink_Delhivery_Shipping_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function cids_admin_enqueue_scripts_callback() {
		// Custom admin style.
		wp_enqueue_style(
			$this->plugin_name,
			CIDS_PLUGIN_URL . 'admin/css/carorbis-ithink-delhivery-shipping-admin.css'
		);

		// Custom admin script.
		wp_enqueue_script(
			$this->plugin_name,
			CIDS_PLUGIN_URL . 'admin/js/carorbis-ithink-delhivery-shipping-admin.js',
			array( 'jquery' ),
			filemtime( CIDS_PLUGIN_PATH . 'admin/js/carorbis-ithink-delhivery-shipping-admin.js' ),
			true
		);

		// Localized admin script.
		wp_localize_script(
			$this->plugin_name,
			'CIDS_Admin_Script_Vars',
			array()
		);
	}

	/**
	 * Add custom metaboxes for managing the shipping options for orders.
	 */
	public function cids_add_meta_boxes_callback() {
		add_meta_box(
			'cids-choose-shipping-metabox',
			__( 'Choose Logistics', 'carorbis-ithink-delhivery-shipping' ),
			array( $this, 'cids_choose_order_logistics_callback' ),
			'shop_order',
			'side'
		);
	}

	/**
	 * Custom metabox callback to choose logistics when the order is placed.
	 *
	 * @since 1.0.0
	 */
	public function cids_choose_order_logistics_callback() {
		$post_id           = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		$selected_logistics = get_post_meta( $post_id, 'carorbis_selected_logistics_service', true );
		?>
		<p><?php esc_html_e( 'Choose the options from the following to manifest the order', 'carorbis-ithink-delhivery-shipping' ); ?></p>
		<div class="cids-order-manifest-options">
			<div class="delhivery">
				<input type="radio" id="delhivery-shipping-option" name="cids-order-logistics-service" value="delhivery" />
				<label for="delhivery-shipping-option"><?php esc_html_e( 'Delhivery', 'carorbis-ithink-delhivery-shipping' ); ?></label>
			</div>
			<div class="ithink">
				<input type="radio" id="ithink-shipping-option" name="cids-order-logistics-service" value="ithink" />
				<label for="ithink-shipping-option"><?php esc_html_e( 'iThink', 'carorbis-ithink-delhivery-shipping' ); ?></label>
			</div>
			<div class="vendor-shipped">
				<input type="radio" id="vendor-shipped-shipping-option" name="cids-order-logistics-service" value="vendor-shipped" />
				<label for="vendor-shipped-shipping-option"><?php esc_html_e( 'Vendor Shipped', 'carorbis-ithink-delhivery-shipping' ); ?></label>
			</div>
		</div>
		<div class="cids-order-manifest-actions">
			<button type="button" class="button button-secondary"><?php esc_html_e( 'Request Manifest', 'carorbis-ithink-delhivery-shipping' ); ?></button>
		</div>
		<?php
	}

	/**
	 * AJAX to request order manifestation.
	 *
	 * @since 1.0.0
	 */
	public function cids_manifest_carorbis_order_callback() {
		$selected_logistics = filter_input( INPUT_POST, 'selected_logistics', FILTER_SANITIZE_STRING );
		$order_id           = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );

		// If the selected logistics is ithink.
		if ( 'ithink' === $selected_logistics ) {
			cids_manifest_order_to_ithink( $order_id );
		} elseif ( 'delhivery' === $selected_logistics ) {
			cids_manifest_order_to_delhivery( $order_id );
		}
		die("here");
	}

	/**
	 * Add custom section under the shipping tab for managing logistics settings.
	 *
	 * @param array $sections Settings sections.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_woocommerce_get_sections_shipping_callback( $sections ) {
		$sections['ithink_logistics']    = __( 'iThink Logistics', 'carorbis-ithink-delhivery-shipping' );
		$sections['delhivery_logistics'] = __( 'Delhivery Logistics', 'carorbis-ithink-delhivery-shipping' );

		return $sections;
	}

	/**
	 * Add custom settings under the ithink logistics section.
	 *
	 * @param array $settings iThink logistics settings.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_woocommerce_get_settings_shipping_callback( $settings, $section ) {
		// Create the settings only when the current section is ithink logistics.
		if ( 'ithink_logistics' === $section ) {
			// Blank the settings array.
			$settings = array();

			// Add title to the settings.
			$settings[] = array(
				'type' => 'title',
				'name' => __( 'iThink Logistics Settings', 'carorbis-ithink-delhivery-shipping' ),
				'desc' => __( 'Following options are used to configure the iThink logistics API integration with the orders.', 'carorbis-ithink-delhivery-shipping' ),
				'id'   => 'ithink-logistics'
			);

			// Add access token to the settings.
			$settings[] = array(
				'type'     => 'text',
				'name'     => __( 'Access Token', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'iThink logistics access token.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'ithink_logistics_access_token',
			);

			// Add secret key to the settings.
			$settings[] = array(
				'type'     => 'text',
				'name'     => __( 'Secret Key', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'iThink logistics secret key.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'ithink_logistics_secret_key',
			);

			// Add staging access token to the settings.
			$settings[] = array(
				'type'     => 'text',
				'name'     => __( 'Access Token - Staging', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'iThink logistics access token for sandbox purpose.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'ithink_logistics_access_token_staging',
			);

			// Add staging secret key to the settings.
			$settings[] = array(
				'type'     => 'text',
				'name'     => __( 'Secret Key - Staging', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'iThink logistics secret key for sandbox purpose.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'ithink_logistics_secret_key_staging',
			);

			// Add staging pickup address ID to the settings.
			$settings[] = array(
				'type'     => 'number',
				'name'     => __( 'Warehouse Pickup Address ID - Staging', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'iThink logistics warehouse pickup address ID for sandbox purpose.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'ithink_logistics_warehouse_pickup_address_id_staging',
			);

			// Add sandbox mode to the settings.
			$settings[] = array(
				'type'     => 'checkbox',
				'name'     => __( 'Sandbox Mode?', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'Check this box to enable the sandbox mode. When enabled, all the API requests will goto "https://pre-alpha.ithinklogistics.com/api_v3/" which will otherwise goto "https://manage.ithinklogistics.com/api_v3/".', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => false,
				'id'       => 'ithink_logistics_sandbox_mode',
			);

			// End the settings.
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'ithink-logistics',
			);
		} elseif ( 'delhivery_logistics' === $section ) { // Create the settings only when the current section is delhivery logistics.
			// Blank the settings array.
			$settings = array();

			// Add title to the settings.
			$settings[] = array(
				'type' => 'title',
				'name' => __( 'Delhivery Logistics Settings', 'carorbis-ithink-delhivery-shipping' ),
				'desc' => __( 'Following options are used to configure the delhivery logistics API integration with the orders.', 'carorbis-ithink-delhivery-shipping' ),
				'id'   => 'delhivery-logistics'
			);

			// Add access token to the settings.
			$settings[] = array(
				'type'     => 'text',
				'name'     => __( 'Access Token', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'Delhivery logistics access token.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => true,
				'id'       => 'delhivery_logistics_access_token',
			);

			// Add sandbox mode to the settings.
			$settings[] = array(
				'type'     => 'checkbox',
				'name'     => __( 'Sandbox Mode?', 'carorbis-ithink-delhivery-shipping' ),
				'desc'     => __( 'Check this box to enable the sandbox mode.', 'carorbis-ithink-delhivery-shipping' ),
				'desc_tip' => false,
				'id'       => 'delhivery_logistics_sandbox_mode',
			);

			// End the settings.
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'delhivery-logistics',
			);
		}

		return $settings;
	}

	/**
	 * Add custom columns to the order listing page in admin.
	 *
	 * @param array $cols Order list table columns.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_manage_edit_shop_order_columns_callback( $cols ) {
		$custom_cols = array(
			'order_type' => __( 'Order Type', 'carorbis-ithink-delhivery-shipping' ),
		);
		$left_cols   = array_slice( $cols, 0, 2, true );
		$right_cols  = array_slice( $cols, 2, ( count( $cols ) - 2 ), true );
		$cols        = array_merge( $left_cols, $custom_cols, $right_cols );

		return $cols;
	}

	/**
	 * Add data to the custom added columns.
	 *
	 * @param string $column_name Custom column name.
	 * @param int    $post_id Post ID.
	 * @since 1.0.0
	 */
	public function cids_manage_shop_order_posts_custom_column_callback( $column_name, $post_id ) {
		// Check for the order type column.
		if ( 'order_type' === $column_name ) {
			// If the order has splitted meya key.
			$order_splitted = get_post_meta( $post_id, 'wcfm_order_splitted', true );

			if ( ! empty( $order_splitted ) && '1' === $order_splitted ) {
				$order_splitted_data = get_post_meta( $post_id, 'wcfm_order_splitted_data', true );
				$child_order_ids     = array_keys( $order_splitted_data );
				$tip = sprintf( __( 'This is a parent from multiple vendors. The child order IDs are: %1$s', 'carorbis-ithink-delhivery-shipping' ), implode( ', ', $child_order_ids ) );
				echo '<mark data-tip="' . $tip . '" class="order-status wcfmos-parent-order tips"><span>' . __( 'Parent Order', 'carorbis-ithink-delhivery-shipping' ) . '</span></mark>';
			}

			// Get the order parent ID.
			$parent_order_id = get_post_field( 'post_parent', $post_id );
			if ( 0 !== $parent_order_id ) {
				$tip = sprintf( __( 'This is a splitted order. Parent order ID: #%1$d', 'carorbis-ithink-delhivery-shipping' ), $parent_order_id );
				echo '<mark data-tip="' . $tip . '" class="order-status wcfmos-child-order tips"><span>' . __( 'Child Order', 'carorbis-ithink-delhivery-shipping' ) . '</span></mark>';
			}
		}
	}
}
