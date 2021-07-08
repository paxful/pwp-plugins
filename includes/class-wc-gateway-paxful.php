<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paxful extends WC_Payment_Gateway {
	/**
	 * Api endpoint
	 *
	 * @var string
	 */
	protected $api_endpoint = 'https://paxful.com/wallet/pay';

	/**
	 * Merchant ID
	 *
	 * @var string
	 */
	protected $merchant_id = '';

	/**
	 * Api Key
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Api Secret
	 *
	 * @var string
	 */
	protected $api_secret = '';

	/**
	 * Bitcoin address
	 *
	 * @var string
	 */
	protected $bitcoin_address = '';

	/**
	 * Debug
	 *
	 * @var string
	 */
	protected $debug = 'yes';

	/**
	 * Init
	 */
	public function __construct() {
		$this->id           = 'paxful';
		$this->has_fields   = true;
		$this->method_title = __( 'Paxful', 'paxful-payments' );
		$this->icon         = apply_filters( 'paxful_icon', plugins_url( '/assets/images/paxful.png', dirname( __FILE__ ) ) );
		$this->supports     = array(
			'products',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled           = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
		$this->title             = isset( $this->settings['title'] ) ? $this->settings['title'] : '';
		$this->description       = isset( $this->settings['description'] ) ? $this->settings['description'] : '';
		$this->merchant_id       = isset( $this->settings['merchant_id'] ) ? $this->settings['merchant_id'] : $this->merchant_id;
		$this->api_key           = isset( $this->settings['api_key'] ) ? $this->settings['api_key'] : $this->api_key;
		$this->api_secret        = isset( $this->settings['api_secret'] ) ? $this->settings['api_secret'] : $this->api_secret;
		$this->bitcoin_address   = isset( $this->settings['bitcoin_address'] ) ? $this->settings['bitcoin_address'] : $this->bitcoin_address;
		$this->debug             = isset( $this->settings['debug'] ) ? $this->settings['debug'] : $this->debug;
		$this->order_button_text = isset( $this->settings['order_button_text'] ) ? $this->settings['order_button_text'] : $this->order_button_text;

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_api_' . strtolower( __CLASS__ ), array( $this, 'return_handler' ) );

		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_cancellation' ), 10, 3 );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}
	}

	/**
	 * Admin Panel Options.
	 *
	 * @return void
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			wc_get_template(
				'admin/admin-options.php',
				array(
					'gateway' => $this,
				),
				'',
				dirname( __FILE__ ) . '/../templates/'
			);
		} else {
			?>
            <div class="inline error">
                <p>
                    <strong><?php esc_html_e( 'Gateway disabled', 'woocommerce' ); ?></strong>: <?php esc_html_e( 'Paxful does not support your store currency.', 'paxful-payments' ); ?>
                </p>
            </div>
			<?php
		}
	}

	/**
	 * Initialise Settings Form Fields
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'paxful-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable plugin', 'paxful-payments' ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Title', 'paxful-payments' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'paxful-payments' ),
				'default'     => __( 'Paxful', 'paxful-payments' ),
			),
			'description'       => array(
				'title'       => __( 'Description', 'paxful-payments' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'paxful-payments' ),
				'default'     => __( 'Paxful', 'paxful-payments' ),
			),
			'merchant_id'       => array(
				'title'       => __( 'Merchant Id', 'paxful-payments' ),
				'type'        => 'text',
				'description' => __( 'Merchant Id', 'paxful-payments' ),
				'default'     => $this->merchant_id,
			),
			'api_key'           => array(
				'title'       => __( 'API key', 'paxful-payments' ),
				'type'        => 'text',
				'description' => __( 'API key', 'paxful-payments' ),
				'default'     => $this->api_key,
			),
			'api_secret'        => array(
				'title'       => __( 'API secret', 'paxful-payments' ),
				'type'        => 'text',
				'description' => __( 'API secret', 'paxful-payments' ),
				'default'     => $this->api_secret,
			),
			'bitcoin_address'   => array(
				'title'       => __( 'External bitcoin address for payouts', 'paxful-payments' ),
				'type'        => 'text',
				'label'       => __( 'External bitcoin address for payouts', 'paxful-payments' ),
				'description' => __( 'Optional', 'paxful-payments' ),
				'default'     => $this->bitcoin_address,
			),
			'debug'             => array(
				'title'   => __( 'Debug', 'paxful-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable logging', 'paxful-payments' ),
				'default' => $this->debug,
			),
			'order_button_text' => array(
				'title'   => __( 'Text for "Place Order" button', 'paxful-payments' ),
				'type'    => 'text',
				'default' => __( 'Paxful Pay', 'paxful-payments' ),
			),
		);
	}

	public function get_currency_list() {
		$url      = "https://paxful.com/api/currency/rates";
		$response = wp_remote_post( $url, array(
				'method'  => 'POST',
				'headers' => [
					'content-type' => 'text/plain',
					'accept'       => 'application/json'
				]

			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			$responseObject = json_decode( wp_remote_retrieve_body( $response ) );
			$currencyList   = [];
			foreach ( $responseObject->data as $currency ) {
				$currencyList[] = $currency->code;
			}

			return $currencyList;
		}
	}

	/**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_paxful_supported_currencies',
				array_merge( array( 'BTC' ), $this->get_currency_list() )
			),
			true
		);
	}

	/**
	 * If There are no payment fields show the description if set.
	 *
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		return true;
	}

	/**
	 * Thank you page
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	public function thankyou_page( $order_id ) {
	}

	/**
	 * Limit length of an arg.
	 *
	 * @param string $string Argument to limit.
	 * @param integer $limit Limit size in characters.
	 *
	 * @return string
	 */
	protected function limit_length( $string, $limit = 127 ) {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $string ) > $limit ) {
				$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
			}
		} else {
			if ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $str_limit ) . '...';
			}
		}

		return $string;
	}

	protected function trim_string( $text ) {
		return $this->limit_length( html_entity_decode( wc_trim_string( wp_strip_all_tags( $text ), 127 ), ENT_NOQUOTES, 'UTF-8' ), 127 );
	}

	protected function prepare_items( $items ) {
		$order_items = [];
		foreach ( $items as $WC_order_item ) {
			$product_id       = $WC_order_item->get_data()['product_id'];
			$product_instance = wc_get_product( $product_id );

			$item          = [
				'name'        => $this->trim_string( $WC_order_item->get_name() ),
				'description' => $this->trim_string( $product_instance->get_description() ),
				'quantity'    => (int) $WC_order_item->get_quantity(),
				'price'       => wc_float_to_string( (float) $product_instance->get_price() ),
			];
			$order_items[] = $item;
		}

		return $order_items;
	}

	/**
	 * Process Payment
	 *
	 * @param int $order_id
	 *
	 * @return array|false
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Create track ID
		delete_post_meta( $order_id, '_paxful_track_id' );
		$track_id = self::get_track_id( $order_id );
		$this->log( sprintf( 'Generated track id: %s for order %s', $track_id, $order_id ) );

		$amount   = $order->get_total();
		$currency = $order->get_currency();
		$items    = $this->prepare_items( $order->get_items() );

		$payload = array(
			'merchant'   => $this->merchant_id,
			'apikey'     => $this->api_key,
			'nonce'      => time(),
			'track_id'   => $track_id,
			'amount'     => $amount,
			'user_email' => $order->get_billing_email(),
			'items'      => $items
		);

		if ( ! empty( $this->bitcoin_address ) ) {
			$payload['to'] = $this->bitcoin_address;
		}

		if ( 'BTC' === $currency ) {
			$payload['amount'] = $amount;
		} else {
			$payload['fiat_amount']   = $amount;
			$payload['fiat_currency'] = $currency;
		}

		$url = $this->get_payment_url( $payload );
		$this->log( 'Payment url: ' . $url );

		return array(
			'result'   => 'success',
			'redirect' => $url,
		);
	}

	/**
	 * Handle Complete Url
	 */
	public function handle_complete() {
		if ( ! isset( $_GET['status'] ) ) {
			return;
		}

		$order_id = absint( WC()->session->get( 'order_awaiting_payment' ) );
		if ( ! $order_id ) {
			return;
		}

		$order  = wc_get_order( $order_id );
		$status = wc_clean( $_GET['status'] );

		$this->log( sprintf( 'OrderId: %s. Status: %s', $order->get_id(), $status ) );

		switch ( $status ) {
			case 'SUCCESSFUL':
				$order->payment_complete();
				$order->add_order_note( __( 'Order has been paid.', 'paxful-payments' ) );

				wp_redirect( $this->get_return_url( $order ) );
				break;
			case 'CANCELED':
				// Cancel the order + restore stock.
				WC()->session->set( 'order_awaiting_payment', false );
				$order->update_status( 'cancelled', __( 'Order has been cancelled.', 'paxful-payments' ) );
				do_action( 'woocommerce_cancelled_order', $order->get_id() );

				wc_add_notice( apply_filters( 'woocommerce_order_cancelled_notice', __( 'Your order was cancelled.', 'woocommerce' ) ), apply_filters( 'woocommerce_order_cancelled_notice_type', 'notice' ) );
				wp_redirect( $order->get_cancel_endpoint() );
				break;
		}
	}

	/**
	 * WebHook Callback
	 *
	 * @return void
	 * @throws Exception
	 */
	public function return_handler() {
		if ( isset( $_GET['complete'] ) ) {
			$this->handle_complete();

			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! wp_verify_nonce( 'nonce', 'return_handler' ) ) {
			$payload = $_POST;
		} else {
			$payload = $_POST;
		}

		$this->log( sprintf( 'WebHook: %s', var_export( $payload, true ) ) );

		try {
			// Verify apiseal
			// $apiSeal    = $payload['apiseal'];
			// $calculated = $this->generate_apiseal( $payload );
			// if ( $calculated !== $apiSeal ) {
			// @todo Fix it
			// throw new Exception( 'apiseal verification is failed' );
			// }

			// Get order by track_id
			$order = wc_get_order( self::get_orderid_by_track_id( $payload['track_id'] ) );
			if ( ! $order ) {
				throw new Exception( sprintf( 'Unable to find order by track id %s', $payload['track_id'] ) );
			}
			$this->log( sprintf( 'OrderId: %s. Status: %s', $order->get_id(), $payload['status'] ) );

			switch ( $payload['status'] ) {
				case 'SUCCESSFUL':
					$order->payment_complete();
					$order->add_order_note( __( 'Order has been paid.', 'paxful-payments' ) );
					break;
				case 'CANCELED':
					$order->update_status( 'cancelled', __( 'Order has been cancelled by webhook.', 'paxful-payments' ) );
					break;
			}

			http_response_code( 200 );
		} catch ( Exception $e ) {
			http_response_code( 500 );
			$this->log( sprintf( 'WebHook: : %s', $e->getMessage() ) );
		}
	}

	/**
	 * Prevent pending cancellation
	 *
	 * @param bool $is_cancel
	 * @param bool $is_checkout
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function prevent_cancellation( $is_cancel, $is_checkout, $order ) {
		if ( $this->id === $order->get_payment_method() ) {
			$is_cancel = false;
		}

		return $is_cancel;
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'.
	 *     emergency|alert|critical|error|warning|notice|info|debug
	 *
	 * @return void
	 * @see WC_Log_Levels
	 */
	protected function log( $message, $level = 'info' ) {
		if ( 'yes' !== $this->debug ) {
			return;
		}

		// Get Logger instance
		$logger = wc_get_logger();

		// Write message to log
		if ( ! is_string( $message ) ) {
			$message = var_export( $message, true );
		}

		$logger->log(
			$level,
			$message,
			array(
				'source'  => $this->id,
				'_legacy' => true,
			)
		);
	}

	/**
	 * Generate Api seal
	 *
	 * @param array $payload
	 *
	 * @return string
	 */
	public function generate_apiseal( array $payload = array() ) {
		unset( $payload['apiseal'] );
		ksort( $payload );

		return hash_hmac( 'sha256', http_build_query( $payload ), $this->api_secret );
	}

	/**
	 * Get Payment Url
	 *
	 * @param array $payload
	 *
	 * @return string
	 */
	public function get_payment_url( array $payload = array() ) {
		$payload['apiseal'] = $this->generate_apiseal( $payload );

		ksort( $payload );

		return $this->api_endpoint . '?' . http_build_query( $payload, null, '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Get Track ID
	 *
	 * @param mixed $order_id
	 *
	 * @return mixed|string
	 */
	public static function get_track_id( $order_id ) {
		$track_id = get_post_meta( $order_id, '_paxful_track_id', true );
		if ( empty( $track_id ) ) {
			$track_id = 'order-' . $order_id . '-' . uniqid();
			update_post_meta( $order_id, '_paxful_track_id', $track_id );
		}

		return $track_id;
	}

	/**
	 * Get Order Id by Track ID
	 *
	 * @param string $track_id
	 *
	 * @return bool|mixed
	 */
	public static function get_orderid_by_track_id( $track_id ) {
		global $wpdb;

		$order_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT post_id FROM {$wpdb->prefix}postmeta 
			LEFT JOIN {$wpdb->prefix}posts ON ({$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id)
			WHERE meta_key = %s AND meta_value = %s;", '_paxful_track_id', $track_id ) );
		if ( ! $order_id ) {
			return false;
		}

		return $order_id;
	}
}

// Register gateway instance
add_filter(
	'woocommerce_payment_gateways',
	function ( $methods ) {
		$methods[] = new WC_Gateway_Paxful();

		return $methods;
	}
);