<?php
/**
 * The file that defines the public plugin class.
 *
 * A class definition that holds all the hooks regarding all the functionalities that happen in the public.
 *
 * @link       https://github.com/vermadarsh/
 * @since      1.0.0
 *
 * @package    WCFMOS_Order_Splitter
 * @subpackage WCFMOS_Order_Splitter/includes
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Delhivery_Express_Shipping_Method extends WC_Shipping_Method {
	/**
	 * Constructor for your shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'delhivery';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __('Delhivery Express Shipping', 'delhivery');
		$this->method_description = __('Custom Shipping Method for Delhivery', 'delhivery');
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
		$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
		$this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Delhivery Express Shipping', 'delhivery');
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		global $wpdb;
		// Load the settings API
		$this->instance_form_fields = $this->get_delhivery_shipping_admin_settings_form_fields();
		$this->title                = $this->get_option('title');
		$this->tax_status           = $this->get_option('tax_status');
		$this->cost                 = $this->get_option('cost');
		$this->type                 = $this->get_option('type', 'class');

		$data = $wpdb->get_results('SELECT * FROM `wp_woocommerce_shipping_zones` WHERE `zone_name` = "Delhivery Express"', OBJECT);
		if (empty($data)) {
			$wpdb->insert('wp_woocommerce_shipping_zones', array('zone_name' => 'Delhivery Express', 'zone_order' => 0));

			echo $zone_id = $wpdb->insert_id;
			$wpdb->insert('wp_woocommerce_shipping_zone_methods', array('zone_id' => $zone_id, 'method_id' => $this->id, 'method_order' => 1, 'is_enabled' => 1));
			$url = "https://track.delhivery.com/c/api/pin-codes/json/?token=ef1757c20c08cc418d0adac5b3e1be35cad6435c";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			// curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data  = curl_exec($ch);
			$pin_codes = json_decode($data);
			$codes = $pin_codes->delivery_codes;

			$insert_string = "($zone_id, 'IN', 'country'),";
			foreach ($codes as $pin_code) {
				$code = $pin_code->postal_code->pin;
				$insert_string .= "($zone_id, '$code', 'postcode'),";
			}
			$insert_string = rtrim($insert_string, ", ");
			$query = "INSERT into `wp_woocommerce_shipping_zone_locations` (`zone_id`,`location_code`,`location_type`) VALUES" . $insert_string;
			$wpdb->query($query);
		}
		$_SESSION['vendor_shipping_cost'] = array();
		// Save settings in admin if you have any defined
		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$rate = array(
			'id'        => $this->get_rate_id(),
			'label'     => $this->title,
			'cost'      => 0,
			'package'   => $package,
		);

		$wcfm_address = get_user_meta($package['vendor_id'], 'wcfmvm_static_infos');
		$o_pin = $wcfm_address[0]['address']['zip'];

		if (empty($t))
			$o_pin = 110001;

		foreach ( $package['contents'] as $pkg ) {

			$product = wc_get_product($pkg['product_id']);
			$weight = $product->get_weight();
			$accesstoken = 'Token ef1757c20c08cc418d0adac5b3e1be35cad6435c';
			$url = "https://track.delhivery.com/api/kinko/v1/invoice/charges/.json";
			$args = array('cl' => 'Rishab Jain', 'md' => 'S', 'ss' => 'Delivered', 'cgm' => $weight, 'o_pin' => $o_pin, 'd_pin' => $package['destination']['postcode']);
			$query_str = http_build_query($args);
			$get_url = $url . '?' . $query_str;
			$header = array();
			$header[] = 'Content-type: application/json';
			$header[] = 'Accept: application/json';
			$header[] = 'Authorization:' . $accesstoken;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $get_url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data  = curl_exec($ch);
			$cost = json_decode($data)[0]->total_amount;
			$rate['meta_data'][$pkg['product_id']] = $cost;
			$rate['cost'] = $rate['cost'] + $cost;
		}

		if ($package['contents_cost'] > 1000) {
			$rate['cost'] = 0;
			$rate['label'] = $this->title . ': Free';
		}

		$this->add_rate($rate);
		do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
	}

	/**
	 * Get items in package.
	 *
	 * @param  array $package Package of items from cart.
	 * @return int
	 */
	public function get_package_item_qty( $package ) {
		$total_quantity = 0;
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$total_quantity += $values['quantity'];
			}
		}

		return $total_quantity;
	}

	/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @param mixed $package Package of items from cart.
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ($package['contents'] as $item_id => $values) {
			if ($values['data']->needs_shipping()) {
				$found_class = $values['data']->get_shipping_class();

				if (!isset($found_shipping_classes[$found_class])) {
					$found_shipping_classes[$found_class] = array();
				}

				$found_shipping_classes[$found_class][$item_id] = $values;
			}
		}

		return $found_shipping_classes;
	}

	public function get_delhivery_shipping_admin_settings_form_fields() {
		$cost_desc = __('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce') . '<br/><br/>' . __('Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce');

		$settings = array(
			'title'      => array(
				'title'       => __('Method title', 'delhivery'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'delhivery'),
				'default'     => __('Delhivery Express Shipping', 'delhivery'),
				'desc_tip'    => true,
			),
			'tax_status' => array(
				'title'   => __('Tax status', 'delhivery'),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __('Taxable', 'delhivery'),
					'none'    => _x('None', 'Tax status', 'delhivery'),
				),
			),
			'cost'       => array(
				'title'             => __('Cost', 'delhivery'),
				'type'              => 'text',
				'placeholder'       => '',
				'description'       => $cost_desc,
				'default'           => '0',
				'desc_tip'          => true,
				'sanitize_callback' => array($this, 'sanitize_cost'),
			),
		);

		$shipping_classes = WC()->shipping()->get_shipping_classes();

		if (!empty($shipping_classes)) {
			$settings['class_costs'] = array(
				'title'       => __('Shipping class costs', 'delhivery'),
				'type'        => 'title',
				'default'     => '',
				/* translators: %s: URL for link. */
				'description' => sprintf(__('These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=shipping&section=classes')),
			);
			foreach ($shipping_classes as $shipping_class) {
				if (!isset($shipping_class->term_id)) {
					continue;
				}
				$settings['class_cost_' . $shipping_class->term_id] = array(
					/* translators: %s: shipping class name */
					'title'             => sprintf(__('"%s" shipping class cost', 'delhivery'), esc_html($shipping_class->name)),
					'type'              => 'text',
					'placeholder'       => __('N/A', 'delhivery'),
					'description'       => $cost_desc,
					'default'           => $this->get_option('class_cost_' . $shipping_class->slug), // Before 2.5.0, we used slug here which caused issues with long setting names.
					'desc_tip'          => true,
					'sanitize_callback' => array($this, 'sanitize_cost'),
				);
			}

			$settings['no_class_cost'] = array(
				'title'             => __('No shipping class cost', 'delhivery'),
				'type'              => 'text',
				'placeholder'       => __('N/A', 'delhivery'),
				'description'       => $cost_desc,
				'default'           => '',
				'desc_tip'          => true,
				'sanitize_callback' => array($this, 'sanitize_cost'),
			);

			$settings['type'] = array(
				'title'   => __('Calculation type', 'delhivery'),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'class',
				'options' => array(
					'class' => __('Per class: Charge shipping for each shipping class individually', 'delhivery'),
					'order' => __('Per order: Charge shipping for the most expensive shipping class', 'delhivery'),
				),
			);
		}

		return $settings;
	}
}
