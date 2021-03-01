<?php
/*
 * Plugin Name: Pay with Paxful for WooCommerce
 * Description: Provides a Payment Gateway through Paxful for WooCommerce.
 * Author: Paxful
 * Author URI: http://paxful.com/
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Version: 1.0.0
 * Text Domain: paxful-payments
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Paxful_Payments {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ), 20 );
		add_action( 'add_meta_boxes', __CLASS__ . '::add_meta_boxes' );
	}

	/**
	 * Add relevant links to plugins page
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paxful' ) . '">' . __( 'Settings', 'paxful-payments' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 *
	 * @return void
	 */
	public function init() {
		// Localization
		load_plugin_textdomain( 'paxful-payments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * WooCommerce Loaded: load classes
	 *
	 * @return void
	 */
	public function woocommerce_loaded() {
		if ( ! class_exists( 'WC_Payment_Gateway', false ) ) {
			return;
		}

		include_once dirname(__FILE__) . '/includes/class-wc-gateway-paxful.php';
	}


	/**
	 * Add meta boxes in admin
	 *
	 * @return void
	 */
	public static function add_meta_boxes() {
		global $post_id;

		$order = wc_get_order( $post_id );
		if ( $order ) {
			if ( $order->get_payment_method() === 'paxful' ) {
				$payment_id = get_post_meta( $post_id, '_paxful_track_id', true );
				if ( ! empty( $payment_id ) ) {
					add_meta_box(
						'paxful_payment_info',
						__( 'Paxful Payment Info', 'paxful-payments' ),
						__CLASS__ . '::order_meta_box_payment_info',
						'shop_order',
						'side',
						'default'
					);
				}
			}
		}
	}

	/**
	 * MetaBox for Payment Info
	 *
	 * @return void
	 */
	public static function order_meta_box_payment_info() {
		global $post_id;

		$order    = wc_get_order( $post_id );
		$track_id = get_post_meta( $post_id, '_paxful_track_id', true );
		if ( empty( $track_id ) ) {
			return;
		}

		wc_get_template(
			'admin/payment-info.php',
			array(
				'order'    => $order,
				'order_id' => $post_id,
				'track_id' => $track_id,
			),
			'',
			dirname( __FILE__ ) . '/templates/'
		);
	}
}

new Paxful_Payments();
