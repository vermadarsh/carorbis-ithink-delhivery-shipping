<?php
/**
 * This file is used for writing all the re-usable custom functions.
 *
 * @since 1.0.0
 * @package Carorbis_Ithink_Delhivery_Shipping
 * @subpackage Carorbis_Ithink_Delhivery_Shipping/includes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Check if this function is not defined.
 */
if ( ! function_exists( 'cids_manifest_order_to_ithink' ) ) {
	/**
	 * Manifest the order to iThink logistics.
	 *
	 * @param int $order_id
	 * @since 1.0.0
	 */
	function cids_manifest_order_to_ithink( $order_id ) {
		$sandbox_mode = get_option( 'ithink_logistics_sandbox_mode' );
		$api_url      = ( ! empty( $sandbox_mode ) && 'yes' === $sandbox_mode ) ? 'https://pre-alpha.ithinklogistics.com/api_v3/order/add.json' : 'https://manage.ithinklogistics.com/api_v3/order/add.json';
		$api_data     = array(
			'data' => array(
				'shipments'         => array(
					array(
						'waybill'                     => '',
						'order'                       => $order_id,
						'sub_order'                   => '',
						'order_date'                  => '',
						'total_amount'                => '',
						'name'                        => '',
						'company_name'                => '',
						'add'                         => '',
						'add2'                        => '',
						'add3'                        => '',
						'pin'                         => '',
						'city'                        => '',
						'state'                       => '',
						'country'                     => '',
						'phone'                       => '',
						'alt_phone'                   => '',
						'email'                       => '',
						'is_billing_same_as_shipping' => '',
						'billing_name'                => '',
						'billing_company_name'        => '',
						'billing_add'                 => '',
						'billing_add2'                => '',
						'billing_add3'                => '',
						'billing_pin'                 => '',
						'billing_city'                => '',
						'billing_state'               => '',
						'billing_country'             => '',
						'billing_phone'               => '',
						'billing_alt_phone'           => '',
						'billing_email'               => '',
						'products'                    => array(
							array(
								'product_name'     => '',
								'product_sku'      => '',
								'product_quantity' => '',
								'product_price'    => '',
								'product_tax_rate' => '',
								'product_hsn_code' => '',
								'product_discount' => '',
							),
							array(
								'product_name'     => '',
								'product_sku'      => '',
								'product_quantity' => '',
								'product_price'    => '',
								'product_tax_rate' => '',
								'product_hsn_code' => '',
								'product_discount' => '',
							),
						),
						'shipment_length'             => '',
						'shipment_width'              => '',
						'shipment_height'             => '',
						'weight'                      => '',
						'shipping_charges'            => '',
						'giftwrap_charges'            => '',
						'transaction_charges'         => '',
						'total_discount'              => '',
						'first_attemp_discount'       => '',
						'cod_charges'                 => '',
						'advance_amount'              => '',
						'cod_amount'                  => '',
						'payment_mode'                => '',
						'reseller_name'               => '',
						'eway_bill_number'            => '',
						'gst_number'                  => '',
						'return_address_id'           => '',
					)
				),
				'pickup_address_id' => '',
				'access_token'      => get_option( 'ithink_logistics_access_token' ),
				'secret_key'        => get_option( 'ithink_logistics_secret_key' ),
				'logistics'         => '',
				's_type'            => '',
				'order_type'        => '',
			),
		);

		// Fire the API now.
		$response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'timeout' => '600',
				'body'    => wp_json_encode( $api_data ),
				'headers' => array(
					'cache-control' => 'no-cache',
					'content-type'  => 'application/json',
				),
			)
		);
		debug( $response );
		die;
	}
}

/**
 * Check if this function is not defined.
 */
if ( ! function_exists( 'cids_manifest_order_to_delhivery' ) ) {
	/**
	 * Manifest the order to delhivery logistics.
	 *
	 * @param int $order_id
	 * @since 1.0.0
	 */
	function cids_manifest_order_to_delhivery( $order_id ) {
		$order_data   = wc_get_order( $order_id );
		$order_status = $order_data->get_status();

		if ( 'processing' === $order_status ) {
			foreach ( $order_data->data['shipping_lines'] as $key => $shipping_data ) {
				$wbn = $wpdb->get_row( "SELECT * FROM 'wp_woocommerce_order_itemmeta' WHERE order_item_id = $key AND meta_key = 'wbn'" );

				if ( is_null( $wbn ) && 1 === count( $order_data->get_items() ) ) {
					$vendor_id = $wpdb->get_var( "SELECT vendor_id FROM {$wpdb->prefix}wcfm_marketplace_orders WHERE order_id = $order_id" );
					$data      = get_userdata($vendor_id);
					$warehouse = $data->data->user_login;
					$order     = array(
						'shipments' => [array(
							'add'           => str_replace("&","and", $order_data->data['shipping']['address_1']) . ' ' . str_replace("&","and",$order_data->data['shipping']['address_2']),
							'phone'         => $order_data->data['billing']['phone'],
							'payment_mode'  => $order_data->data['payment_method'] == 'cod' ? 'COD' : 'Prepaid',
							'name'          => $order_data->data['shipping']['first_name'] . ' ' . $order_data->data['shipping']['last_name'],
							'pin'           => $order_data->data['shipping']['postcode'],
							'city'          => $order_data->data['shipping']['city'],
							'state'         => $order_data->data['shipping']['state'],
							'cod_amount'    => $order_data->data['total'], //Need to Update
							'total_amount'  => $order_data->data['total'], //Need to Update
							'order'         => $order_id, //Need to Update
							'weight'        => 3000
						)],
						'pickup_location' => array(
							'name' => $warehouse
						)
					);
					$data_json = 'format=json&data=' . json_encode($order);

					$accesstoken = 'Token ef1757c20c08cc418d0adac5b3e1be35cad6435c';
					$url = "https://track.delhivery.com/api/cmu/create.json";

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
					$response = curl_exec( $ch );
					$output   = json_decode( preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $response ), true );

					// Update the database now.
					$wpdb->insert(
						'wp_woocommerce_order_itemmeta',
						array(
							'order_item_id' => $key,
							'meta_key'      => 'wbn',
							'meta_value'    => $output['upload_wbn']
						)
					);

					$wpdb->insert(
						'wp_woocommerce_order_itemmeta',
						array(
							'order_item_id' => $key,
							'meta_key' => 'waybill',
							'meta_value' => $output['packages'][0]['waybill']
						)
					);

					// Update the order status.
					$order_data->update_status( 'manifested' );
				}
			}
		} elseif ( 'ready-shipped' === $order_status ) {
			$vendor_id = $wpdb->get_var("SELECT vendor_id FROM {$wpdb->prefix}wcfm_marketplace_orders WHERE order_id = $order_id");
			$data1 = get_userdata($vendor_id);
			$warehouse = $data1->data->user_login;

			$data = array(
				'pickup_time' => "12:00:00",
				'pickup_date' => date("Y-m-d", time() + 86400),
				'pickup_location' => $warehouse,
				'expected_package_count' => 1
			);
			$wpdb->insert('test', array('data' => serialize($data)));
			$accesstoken = 'Token ef1757c20c08cc418d0adac5b3e1be35cad6435c';
			$url = "https://track.delhivery.com/fm/request/new/";
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
			$create_res_vlaue = json_encode($output);
			$wpdb->insert('test', array('data' => serialize($output)));
			
		} else if ($order_status == 'cancelled') {
			$wbn = $wpdb->get_var("SELECT meta_value FROM `wp_woocommerce_order_itemmeta` as meta JOIN wp_woocommerce_order_items as item ON item.order_item_id = meta.order_item_id WHERE meta_key = 'waybill' and order_id = $order_id GROUP BY order_id");

			$data = array(
				'waybill' => $wbn,
				'cancellation' => "true"
			);

			$accesstoken = 'Token ef1757c20c08cc418d0adac5b3e1be35cad6435c';
			$url = "https://track.delhivery.com/api/p/edit";
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
			$create_res_vlaue = json_encode($output);
			$wpdb->insert('test', array('data' => serialize($output)));
		} else if ($order_status == 'return') {
			$vendor_id = $wpdb->get_var("SELECT vendor_id FROM {$wpdb->prefix}wcfm_marketplace_orders WHERE order_id = $order_id");
			$data = get_userdata($vendor_id);
			$warehouse = $data->data->user_login;
			$order = array(
				'shipments' => [array(
					'add'           => str_replace("&","and",$order_data->data['shipping']['address_1']) . ' ' . str_replace("&","and",$order_data->data['shipping']['address_2']),
					'phone'         => $order_data->data['billing']['phone'],
					'payment_mode'  => "Pickup",
					'name'          => $order_data->data['shipping']['first_name'] . ' ' . $order_data->data['shipping']['last_name'],
					'pin'           => $order_data->data['shipping']['postcode'],
					'city'          => $order_data->data['shipping']['city'],
					'state'         => $order_data->data['shipping']['state'],
					'cod_amount'    => $order_data->data['total'],
					'total_amount'  => $order_data->data['total'],
					'order'         => $order_id,
				)],
				'pickup_location' => array(
					'name' => $warehouse
				)
			);
			$data_json = 'format=json&data=' . json_encode($order);

			$accesstoken = 'Token ef1757c20c08cc418d0adac5b3e1be35cad6435c';
			$url = "https://track.delhivery.com/api/cmu/create.json";

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
			$response  = curl_exec( $ch );
			$output = json_decode( preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $response ), true );
		}
	}
}
