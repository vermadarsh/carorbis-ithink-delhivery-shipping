<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/vermadarsh/
 * @since      1.0.0
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/public
 * @author     Adarsh Verma <adarsh.srmcem@gmail.com>
 */
class Carorbis_Ithink_Delhivery_Shipping_Public {
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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function cids_wp_enqueue_scripts_callback() {
		// Custom public style.
		wp_enqueue_style(
			$this->plugin_name,
			CIDS_PLUGIN_URL . 'public/css/carorbis-ithink-delhivery-shipping-public.css'
		);

		// Custom public script.
		wp_enqueue_script(
			$this->plugin_name,
			CIDS_PLUGIN_URL . 'public/js/carorbis-ithink-delhivery-shipping-public.js',
			array( 'jquery' ),
			filemtime( CIDS_PLUGIN_PATH . 'public/js/carorbis-ithink-delhivery-shipping-public.js' ),
			true
		);

		// Localize public script.
		wp_localize_script(
			$this->plugin_name,
			'CIDS_Public_Script_Vars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Split the order into child orders based on multiple vendors.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @since    1.0.0
	 */
	public function cids_woocommerce_thankyou_callback( $order_id ) {
		// WooCommerce order.
		$wc_order = wc_get_order( $order_id );

		// Return, if this is an invalid order.
		if ( false === $wc_order ) {
			return;
		}

		// Get the order items now.
		$line_items = $wc_order->get_items();

		// Check if there are no items in the order, return.
		if ( empty( $line_items ) || ! is_array( $line_items ) ) {
			return;
		}

		// Vendors array.
		$wcfm_vendors = array();

		// Items.
		$wcfm_vendor_items = array();

		// Iterate through the items to grab the vendors.
		foreach ( $line_items as $line_item ) {
			$product_id                        = $line_item->get_product_id();
			$variation_id                      = $line_item->get_variation_id();
			$actual_product_id                 = ( 0 === $variation_id ) ? $product_id : $variation_id;
			$vendor_id                         = get_post_field( 'post_author', $actual_product_id );
			$wcfm_vendors[]                    = $vendor_id;
			$wcfm_vendor_items[ $vendor_id ][] = array(
				'product_id' => $product_id,
				'quantity'   => $line_item->get_quantity(),
			);
		}

		// Make the array unique.
		$wcfm_vendors = array_unique( $wcfm_vendors );

		// If there is only one vendor, no need to split.
		if ( 1 === count( $wcfm_vendors ) ) {
			return;
		}

		// Check if the order has already been splitted.
		$order_splitted = get_post_meta( $order_id, 'wcfm_order_splitted', true );

		// Return, if the order has already been splitted.
		if ( ! empty( $order_splitted ) && '1' === $order_splitted ) {
			return;
		}

		// Payment details.
		$payment_method       = $wc_order->get_payment_method();
		$payment_method_title = $wc_order->get_payment_method_title();

		// COD charges.
		$cod_charge = 0;
		if ( 'cod' === $payment_method ) {
			$cod_fee = $wc_order->get_items( 'fee' );
			if ( ! empty( $cod_fee ) && is_array( $cod_fee ) ) {
				foreach ( $cod_fee as $cod_fee_charge ) {
					$fee_name      = $cod_fee_charge->get_name(); // The fee name.
					$fee_total     = $cod_fee_charge->get_total(); // The fee total amount.
					$fee_total_tax = $cod_fee_charge->get_total_tax(); // The fee total tax amount.

					// If this fee is cash on delivery.
					if ( false !== stripos( $fee_name, 'cash on delivery' ) ) {
						$cod_charge = (float) $fee_total;
					}
				}
			}

			// If the cod charge is more than 0, divide it between the deliveries.
			if ( 0 < $cod_charge ) {
				$cod_charge /= count( $wcfm_vendors );
				$cod_charge  = round( $cod_charge, 2 );
			}
		}

		// Shipping cost dispersion.
		$shipping_total       = 0;
		$shipping_method_name = '';
		$shipping_method_id   = '';
		$shipping_items = $wc_order->get_items( 'shipping' );
		if ( ! empty( $shipping_items ) && is_array( $shipping_items ) ) {
			foreach ( $shipping_items as $shipping_item ) {
				$shipping_method_name = $shipping_item->get_name();
				$shipping_method_id   = $shipping_item->get_method_id(); // The method ID
				$shipping_total       = $shipping_item->get_total();
			}
		}

		// Divide the shipping total.
		if ( 0 < $shipping_total ) {
			$shipping_total /= count( $wcfm_vendors );
			$shipping_total  = round( $shipping_total, 2 );
		}

		$child_orders_data = array();

		/**
		 * Create child orders now.
		 * Iterate through the vendor items to create child orders.
		 */
		foreach ( $wcfm_vendor_items as $vendor_id => $vendor_items ) {
			// Billing address.
			$billing_address = array(
				'first_name' => $wc_order->get_billing_first_name(),
				'last_name'  => $wc_order->get_billing_last_name(),
				'company'    => $wc_order->get_billing_company(),
				'address_1'  => $wc_order->get_billing_address_1(),
				'address_2'  => $wc_order->get_billing_address_2(),
				'city'       => $wc_order->get_billing_city(),
				'state'      => $wc_order->get_billing_state(),
				'postcode'   => $wc_order->get_billing_postcode(),
				'country'    => $wc_order->get_billing_country(),
				'email'      => $wc_order->get_billing_email(),
				'phone'      => $wc_order->get_billing_phone(),
			);

			// Shipping address.
			$shipping_address = array(
				'first_name' => $wc_order->get_shipping_first_name(),
				'last_name'  => $wc_order->get_shipping_last_name(),
				'company'    => $wc_order->get_shipping_company(),
				'address_1'  => $wc_order->get_shipping_address_1(),
				'address_2'  => $wc_order->get_shipping_address_2(),
				'city'       => $wc_order->get_shipping_city(),
				'state'      => $wc_order->get_shipping_state(),
				'postcode'   => $wc_order->get_shipping_postcode(),
				'country'    => $wc_order->get_shipping_country(),
			);

			// Child order other arguments.
			$order_args = array(
				'status'              => $wc_order->get_status(),
				'customer_ip_address' => $wc_order->get_customer_ip_address(),
			);

			$wc_child_order = wc_create_order( $order_args );
			$wc_child_order->set_customer_id( $wc_order->get_customer_id() );
			$wc_child_order->set_customer_note( $wc_order->get_customer_note() );
			$wc_child_order->set_currency( get_woocommerce_currency() );
			$wc_child_order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		
			// For calculating taxes on items, since we sell items only in India.
			$taxes_args = array(
				'country'  => 'IN',
			);
			
			// Iterate through the items from the parent order.
			foreach ( $vendor_items as $vendor_item ) {
				$item_product_id = ( ! empty( $vendor_item['product_id'] ) ) ? $vendor_item['product_id'] : 0;
				$quantity        = ( ! empty( $vendor_item['quantity'] ) ) ? $vendor_item['quantity'] : 1;

				// Skip if there is no product ID.
				if ( 0 === $item_product_id ) {
					continue;
				}

				// Add product to the order.
				$wc_product = wc_get_product( $item_product_id );
				$item_id    = $wc_child_order->add_product( $wc_product, $quantity );
				$line_item  = $wc_child_order->get_item( $item_id, false );
				$line_item->calculate_taxes( $taxes_args );
				$line_item->save();
			}

			// Add the COD charges.
			if ( 0 < $cod_charge ) {
				$cod_item_fee = new WC_Order_Item_Fee();
				$cod_item_fee->set_name( __( 'Cash on Delivery Charge', 'wcfm-order-splitter' ) );
				$cod_item_fee->set_amount( $cod_charge );
				$cod_item_fee->set_tax_class( '' );
				$cod_item_fee->set_tax_status( 'taxable' );
				$cod_item_fee->set_total( $cod_charge );

				// Add this fee to the order.
				$wc_child_order->add_item( $cod_item_fee );
			}

			// Add shipping price.
			if ( 0 < $shipping_total ) {
				$shipping_item = new WC_Order_Item_Shipping();
				$shipping_item->set_method_title( $shipping_method_name );
				$shipping_item->set_method_id( $shipping_method_id );
				$shipping_item->set_total( $shipping_total );

				// Add shipping to the child order.
				$wc_child_order->add_item( $shipping_item );
			}

			// Set order addresses.
			$wc_child_order->set_address( $billing_address, 'billing');
			$wc_child_order->set_address( $shipping_address, 'shipping');
			$wc_child_order->calculate_totals();
			$wc_child_order->save();

			// Child order ID.
			$wc_child_order_id = $wc_child_order->get_id();

			// Update the post parent.
			wp_update_post(
				array(
					'ID'          => $wc_child_order_id,
					'post_parent' => $order_id,
				)
			);

			// Update the payment method.
			update_post_meta( $wc_child_order_id, '_payment_method', $payment_method );
			update_post_meta( $wc_child_order_id, '_payment_method_title', $payment_method_title );
			update_post_meta( $wc_child_order_id, '_created_via', 'checkout' );

			// Collect the meta data.
			$child_orders_data[ $wc_child_order_id ] = $vendor_id;
		}

		// Update the order meta that it's splitted.
		update_post_meta( $order_id, 'wcfm_order_splitted', '1' );
		update_post_meta( $order_id, 'wcfm_order_splitted_data', $child_orders_data );
	}

	/**
	 * Hide the parent orders from the customers order listing page.
	 *
	 * @param array $query_args WooCommerce order query arguments.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_woocommerce_my_account_my_orders_query_callback( $query_args ) {
		$query_args['meta_key']     = 'wcfm_order_splitted';
		$query_args['meta_compare'] = 'NOT EXISTS';

		return $query_args;
	}

	/**
	 * Hide the parent orders from the store manager order listing page.
	 *
	 * @param array $wcfm_query_args WCFM order query arguments.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_wcfmmp_order_label_display_callback( $buyer_name, $post_id ) {
		// If the order has splitted meya key.
		$order_splitted = get_post_meta( $post_id, 'wcfm_order_splitted', true );

		if ( ! empty( $order_splitted ) && '1' === $order_splitted ) {
			$order_splitted_data = get_post_meta( $post_id, 'wcfm_order_splitted_data', true );
			$child_order_ids     = array_keys( $order_splitted_data );
			$tip = sprintf( __( 'This is a parent from multiple vendors. The child order IDs are: %1$s', 'wcfm-order-splitter' ), implode( ', ', $child_order_ids ) );
			$split_html = '<span class="order-status wcfmos-parent-order tooltip"><span class="tooltiptext">' . $tip . '</span><span>' . __( 'Parent Order', 'wcfm-order-splitter' ) . '</span></span>';
		}

		// Get the order parent ID.
		$parent_order_id = get_post_field( 'post_parent', $post_id );
		if ( 0 !== $parent_order_id ) {
			$tip = sprintf( __( 'This is a splitted order. Parent order ID: #%1$d', 'wcfm-order-splitter' ), $parent_order_id );
			$split_html = '<span class="order-status wcfmos-child-order tooltip"><span class="tooltiptext">' . $tip . '</span><span>' . __( 'Child Order', 'wcfm-order-splitter' ) . '</span></span>';
		}

		// Add the split html to the data displayed.
		$buyer_name .= $split_html;

		return $buyer_name;
	}

	/**
	 * Make changes to the warehouse on delhivery when the vendor makes changes from the vendor settings.
	 *
	 * @param int   $user_id User ID.
	 * @param array $user_data User data.
	 * @since 1.0.0
	 */
	public function cids_wcfm_vendor_settings_update_callback( $user_id, $user_data ) {
		cids_update_vendor_profile_details_on_delhivery( $user_id, $wcfm_profile_form );
		cids_update_vendor_profile_details_on_ithink( $user_id, $wcfm_profile_form );
	}

	/**
	 * Update the vendor profile details on delhivery.
	 *
	 * @since 1.0.0
	 */
	public function cids_wcfm_profile_update_callback( $user_id, $wcfm_profile_form ) {
		// cids_update_vendor_profile_details_on_delhivery( $user_id, $wcfm_profile_form );
		cids_update_vendor_profile_details_on_ithink( $user_id, $wcfm_profile_form );
	}

	/**
	 * Add new vendor to the warehouse.
	 *
	 * @param int $user_id User ID.
	 */
	public function cids_user_register_callback( $user_id ) {
		if (isset($_POST['wcfmvm_static_infos']) && isset($_POST['wcfmvm_custom_infos'])) {
			global $wpdb;
	
			$data = array(
				'phone' => $_POST['wcfmvm_static_infos']['phone'],
				'city' => $_POST['wcfmvm_static_infos']['address']['city'],
				'name' => $_POST['user_name'],
				'pin' => $_POST['wcfmvm_static_infos']['address']['zip'],
				'address' => $_POST['wcfmvm_static_infos']['address']['addr_1'] . ' ' . $_POST['wcfmvm_static_infos']['address']['addr_2'],
				// 'country' => $_POST['wcfmvm_static_infos']['address']['country'],
				'contact_person' => $_POST['first_name'] . ' ' . $_POST['last_name'],
				'email' => $_POST['user_email'],
				'registered_name' => $_POST['user_name'],
				'return_address' => $_POST['wcfmvm_static_infos']['address']['addr_1'] . ' ' . $_POST['wcfmvm_static_infos']['address']['addr_2'],
				'return_pin' => $_POST['wcfmvm_static_infos']['address']['zip'],
				'return_city' => $_POST['wcfmvm_static_infos']['address']['city'],
				'return_state' => $_POST['wcfmvm_static_infos']['address']['state']
			);

			$accesstoken = get_option( 'delhivery_logistics_access_token' );
			$url = "https://track.delhivery.com/api/backend/clientwarehouse/create/";
			$data_json = json_encode($data);
			$header = array();
			$header[] = 'Content-type: application/json';
			$header[] = 'Accept: application/json';
			$header[] = 'Authorization:' . $accesstoken;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response  = curl_exec($ch);
			$output = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response), true);
		}
	}

	/**
	 * Register new shipping method.
	 *
	 * @param array $shipping_methods WooCommerce shipping methods.
	 * @return array
	 * @since 1.0.0
	 */
	public function cids_woocommerce_shipping_methods_callback( $shipping_methods ) {
		$methods['delhivery'] = 'Delhivery_Express_Shipping_Method';

		return $methods;
	}

	/**
	 * Include the woocommerce delhivery shipping method class file.
	 *
	 * @since 1.0.0
	 */
	public function cids_woocommerce_shipping_init_callback() {
		// Return, if the delhivery class is already defined.
		if ( class_exists( 'Delhivery_Express_Shipping_Method' ) ) {
			return;
		}

		// Include the delhivery class file.
		include CIDS_PLUGIN_PATH . 'includes/shipping/delhivery/class-delhivery-express-shipping-method.php';
	}

	/**
	 * Do something on wp load.
	 *
	 * @since 1.0.0
	 */
	public function cids_wp_callback() {
	}
}
