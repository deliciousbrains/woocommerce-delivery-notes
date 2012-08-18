<?php

/**
 * Print class
 */
if ( ! class_exists( 'WooCommerce_Delivery_Notes_Print' ) ) {

	class WooCommerce_Delivery_Notes_Print {

		public $template_url;
		public $template_dir;
		public $template_base;
		public $template_name;
		public $template_stylesheet_url;
		public $theme_base;
		public $theme_path;
		public $order_id;

		private $order;

		/**
		 * Constructor
		 */
		public function __construct() {					
		}
		
		/**
		 * Load the class
		 */
		public function load( $order_id = 0 ) {
			global $woocommerce;
			
			$this->order_id = $order_id;
			$this->template_name = 'delivery-note';
			$this->template_base = 'templates/';
			$this->theme_base = $woocommerce->template_url;
			$this->template_dir = 'delivery-notes/';
			$this->template_url = WooCommerce_Delivery_Notes::$plugin_url . $this->template_base . $this->template_dir;
			$this->template_stylesheet_url = $this->template_url;
			$this->theme_path = trailingslashit( get_stylesheet_directory() ); 
			
			if ( $this->order_id > 0 ) {
				$this->order = new WC_Order( $this->order_id );
			}			
		}

		/**
		 * Load the admin hooks
		 */
		public function load_hooks() {
		}

		/**
		 * Read the template file
		 */
		public function get_print_page( $template_name = 'delivery-note' ) {
			$this->template_name = $template_name;
			return $this->get_template_content( 'print', $this->template_name );
		}

		/**
		 * Read the template file content
		 */
		private function get_template_content( $slug, $name = '' ) {
			$template = null;
			$template_file = null;
			
			// Look in yourtheme/woocommerce/delivery-notes/
			$template_file = $this->theme_path . $this->theme_base . $this->template_dir . $slug.'-'.$name.'.php';
			if ( !$template && $name && file_exists( $template_file) ) {
				$template = $template_file;
				$this->template_url = trailingslashit( get_stylesheet_directory_uri() ) . $this->theme_base . $this->template_dir;
				$this->template_stylesheet_url = $this->template_url;
			} 
						
			// Fall back to slug.php in yourtheme/woocommerce/delivery-notes/			
			$template_file = $this->theme_path . $this->theme_base . $this->template_dir . $slug.'.php';
			if ( !$template && file_exists( $template_file ) ) {
				$template = $template_file;
				$this->template_url = trailingslashit( get_stylesheet_directory_uri() ) . $this->theme_base . $this->template_dir;
				$this->template_stylesheet_url = $this->template_url;
			}
			
			// No php file found but maybe there is a custom css			
			$template_stylesheet_file = $this->theme_path . $this->theme_base . $this->template_dir . 'style.css';
			if ( !$template && file_exists( $template_stylesheet_file ) ) {
				$this->template_stylesheet_url = trailingslashit( get_stylesheet_directory_uri() ) . $this->theme_base . $this->template_dir;
			}
				
			// Look in pluginname/templates/delivery-notes/
			$template_file = WooCommerce_Delivery_Notes::$plugin_path . $this->template_base . $this->template_dir . $slug.'-'.$name.'.php';
			if ( !$template && $name && file_exists( $template_file ) ) {
				$template = $template_file;
			}

			// Fall back to slug.php in pluginname/templates/delivery-notes/			
			$template_file = WooCommerce_Delivery_Notes::$plugin_path . $this->template_base . $this->template_dir . $slug.'.php';
			if ( !$template && file_exists( $template_file ) ) {
				$template = $template_file;
			}
			
			// Return the content of the template
			if ( $template ) {
				ob_start();
				require_once( $template );
				$content = ob_get_clean();
				return $content;
			}
			
			// Return no content when no file was found
			return;
		}
						
		/**
		 * Get the current order
		 */
		public function get_order() {
			return $this->order;
		}

		/**
		 * Get the current order items
		 */
		public function get_order_items() {
			global $woocommerce;
			global $_product;

			$items = $this->order->get_items();
			$data_list = array();
		
			if ( sizeof( $items ) > 0 ) {
				foreach ( $items as $item ) {
					// Array with data for the printing template
					$data = array();
					
					// Create the product
					$product = $this->order->get_product_from_item( $item );
					
					// Set the variation
					if( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
						$data['variation'] = woocommerce_get_formatted_variation( $product->get_variation_attributes(), true );
					} else {
						$data['variation'] = null;
					}
					
					// Set item name
					$data['name'] = $item['name'];
					
					// Set item quantity
					$data['quantity'] = $item['qty'];
					
					// Set item meta
					$data['meta'] = new order_item_meta( $item['item_meta'] );
										
					// Set item download url					
					if( $product->exists() && $product->is_downloadable() && ( $this->order->status == 'completed' || ( get_option( 'woocommerce_downloads_grant_access_after_payment' ) == 'yes' && $this->order->status == 'processing' ) ) ) {
						$data['download_url'] = $this->order->get_downloadable_file_url( $item['id'], $item['variation_id'] );
					} else {
						$data['download_url'] = null;
					}

					// Set the price
					$data['price'] = $this->order->get_formatted_line_subtotal( $item );
					
					//print_r($item);
					
					// Set the single price
					$data['single_price'] = $product->get_price();
									
					// Set item SKU
					$data['sku'] = $product->get_sku();
	
					// Set item weight
					$data['weight'] = $product->get_weight();
					
					// Set item dimensions
					$data['dimensions'] = $product->get_dimensions();
					
	                // Pass complete item array
	                $data['item'] = $item;

					// Pass complete product object
	                $data['product'] = $product;
					
					$data_list[] = $data;
				}
			}

			return $data_list;
		}
		
		/**
		 * Get order custom field
		 */
		function get_order_field( $field ) {
			if( isset( $this->get_order()->order_custom_fields[$field] ) ) {
				return $this->get_order()->order_custom_fields[$field][0];
			} 
			return;
		}
		
		/**
		 * Get the content for an option
		 */
		public function get_setting( $name ) {
			return get_option( WooCommerce_Delivery_Notes::$plugin_prefix . $name );
		}
	
	}

}
