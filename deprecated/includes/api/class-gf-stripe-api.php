<?php

use \Gravity_Forms_Stripe\API\Model;

/**
 * Gravity Forms Stripe API Library.
 *
 * @since 3.4
 * @since 5.5.0 Deprecated the Stripe SDK and moved this class to the api folder.
 *
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2020, Rocketgenius
 */
class GF_Stripe_API {

	/**
	 * The base Stripe API URL.
	 *
	 * @since 5.5.0
	 *
	 * @var string $api_url
	 */
	protected $api_url = 'https://api.stripe.com/v1/';

	/**
	 * The secret API key.
	 *
	 * @since 5.5.0
	 *
	 * @var string $api_key
	 */
	protected $secret_api_key = '';

	/**
	 * The Stripe API version.
	 *
	 * @since 5.5.0
	 *
	 * @var string $api_version
	 */
	protected $api_version = '2023-10-16';

	/**
	 * Null or an instance of the addon.
	 *
	 * @since 5.0
	 *
	 * @var null|GFStripe
	 */
	protected $addon;

	/**
	 * Null or the current instance of this class.
	 *
	 * @since 5.5.0
	 *
	 * @var null|GF_Stripe_API
	 */
	private static $_instance;

	/**
	 * Returns the current instance of this class.
	 *
	 * @since 5.5.0
	 *
	 * @param array|string  $feed_or_secret_api_key The Stripe Add-On feed or the secret API key.
	 * @param null|GFStripe $addon                  null or an instance of the Stripe Add-On.
	 *
	 * @return GF_Stripe_API
	 */
	public static function get_instance( $feed_or_secret_api_key, $addon = null ) {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self( $feed_or_secret_api_key, $addon );
		} else {
			self::$_instance->set_secret_api_key( $feed_or_secret_api_key );
		}

		return self::$_instance;
	}

	/**
	 * Initializes an instance of this class.
	 *
	 * @since 3.4
	 * @since 5.0   Added the $addon param.
	 * @since 5.5.0 Deprecated the Stripe SDK and moved this class to the api folder.
	 *
	 * @param array|string  $feed_or_secret_api_key The Stripe Add-On feed or the secret API key.
	 * @param null|GFStripe $addon                  Null or an instance of the addon.
	 *
	 * @return void
	 */
	public function __construct( $feed_or_secret_api_key, $addon = null ) {

		if ( $addon instanceof GFStripe ) {
			$this->addon = $addon;
		} else {
			$this->addon = gf_stripe();
		}

		// Setting api key;
		$this->set_secret_api_key( $feed_or_secret_api_key );

		// Initialize legacy Stripe SDK for backwards compatibility.
		$this->init_legacy_stripe_sdk( $feed_or_secret_api_key );
	}

	/**
	 * Initializes the Stripe SDK with the API key.
	 *
	 * @since 5.5.0
	 */
	private function init_legacy_stripe_sdk( $api_key ) {

		// Autoload Stripe SDK.
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once $this->addon->get_base_path() . '/includes/autoload.php';
		}

		// Include deprecated classes.
		require_once $this->addon->get_base_path() . '/includes/deprecated.php';

		// Set api key.
		if ( ! empty( $api_key ) ) {
			\Stripe\Stripe::setApiKey( $api_key );
		}

	}

	/**
	 * Returns the secret API key to be used for the given feed.
	 *
	 * @snce 5.5.0
	 *
	 * @param array $feed The Stripe Add-On feed.
	 *
	 * @return string
	 */
	private function get_secret_api_key_for_feed( $feed ) {
		static $keys;

		$feed_id = rgar( $feed, 'id', 0 );

		if ( ! empty( $keys[ $feed_id ] ) ) {
			return $keys[ $feed_id ];
		}

		if ( $feed_id && $this->addon->is_feed_stripe_connect_enabled( $feed_id ) ) {
			$mode     = $this->addon->get_api_mode( $feed['meta'], $feed_id );
			$settings = $feed['meta'];
		} else {
			$mode     = null;
			$settings = null;
		}

		$keys[ $feed_id ] = $this->addon->get_secret_api_key( $mode, $settings );

		return $keys[ $feed_id ];
	}

	/**
	 * Sets the secret_api_key property.
	 *
	 * @since 5.5.0
	 *
	 * @param array|string $feed_or_secret_api_key The Stripe Add-On feed or the secret API key.
	 *
	 * @return void
	 */
	public function set_secret_api_key( $feed_or_secret_api_key ) {
		if ( ! empty( $feed_or_secret_api_key ) && is_string( $feed_or_secret_api_key ) ) {
			$this->secret_api_key = $feed_or_secret_api_key;
		} else {
			$this->secret_api_key = $this->get_secret_api_key_for_feed( $feed_or_secret_api_key );
		}
	}

	/**
	 * Makes the API request.
	 *
	 * @since 5.5.0
	 *
	 * @param string $path          Request path.
	 * @param array  $args          The query arguments or data for the request body.
	 * @param string $method        Request method. Defaults to GET.
	 * @param int    $expected_code The expected response code.
	 *
	 * @return array|WP_Error
	 */
	public function make_request( $path, $args = array(), $method = 'GET', $expected_code = 200 ) {
		if ( empty( $this->secret_api_key ) ) {
			return new WP_Error( 'secret_api_key_empty', 'Set the Secret API Key using `GF_Stripe_API::get_instance( $feed_or_secret_api_key );` and then make the request again.' );
		}

		$request_url = $this->api_url . $path;

		if ( $method === 'GET' && ! empty( $args ) ) {
			$request_url = add_query_arg( $args, $request_url );
		}

		$request_args = array(
			'method'     => $method,
			'headers'    => array(
				'Accept'         => 'application/json',
				'Authorization'  => 'Basic ' . base64_encode( $this->secret_api_key . ':' ),
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => $this->api_version,
			),
			'user-agent' => sprintf( 'Gravity Forms Stripe/%s (%s)', $this->addon->get_version(), esc_url( site_url() ) ),
		);

		if ( $method !== 'GET' ) {
			$request_args['body'] = $this->encode_objects( $args );
		}

		$debug_logging = defined( 'GF_STRIPE_DEBUG' ) && GF_STRIPE_DEBUG;

		if ( $debug_logging ) {
			$this->addon->log_debug( __METHOD__ . sprintf( '(): Making request to: %s; args: %s', $request_url, print_r( $request_args, true ) ) );
		}

		$response = wp_remote_request( $request_url, $request_args );

		if ( $debug_logging ) {
			$this->addon->log_debug( __METHOD__ . '(): $response => ' . print_r( $response, true ) );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $response_code !== $expected_code ) {
			return new WP_Error( $response_code, rgars( $response_body, 'error/message' ) );
		}

		return $response_body;
	}

	/**
	 * Encodes objects to be consumed by Stripe API requests.
	 *
	 * @since 5.5.0
	 *
	 * @param Model\Base|array|bool|mixed $obj
	 *
	 * @return Model\Base|array|mixed|string
	 */
	private function encode_objects( $obj ) {
		if ( $obj instanceof Model\Base ) {
			return $obj->id;
		}
		if ( true === $obj ) {
			return 'true';
		}
		if ( false === $obj ) {
			return 'false';
		}
		if ( is_array( $obj ) ) {
			$res = array();
			foreach ( $obj as $key => $value ) {
				$res[ $key ] = $this->encode_objects( $value );
			}

			return $res;
		}

		return $obj;
	}

	## ACCOUNT ----------------------------------------------------

	/**
	 * Get Stripe account info.
	 *
	 * @since 3.4
	 * @since 5.5.0 Now returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @return WP_Error|Model\Account Return the Account object, or WP_Error if exceptions are thrown.
	 */
	public function get_account() {
		require_once( 'model/class-account.php' );

		$response = $this->make_request( 'account' );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Account( $response, $this );
	}


	## PAYMENT INTENTS ----------------------------------------------------


	/**
	 * Create Stripe payment intent.
	 *
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.

	 * @param array $data The payment intent data.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function create_payment_intent( $data ) {
		require_once( 'model/class-paymentintent.php' );

		$response = $this->make_request( 'payment_intents', $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\PaymentIntent( $response, $this );
	}

	/**
	 * Gets a payment intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id      The payment intent ID.
	 * @param array  $options Additional request options.
	 *
	 * @return Model\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function get_payment_intent( $id, $options = array() ) {
		require_once( 'model/class-paymentintent.php' );

		$response = $this->make_request( "payment_intents/{$id}", $options, 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new Model\PaymentIntent( $response, $this );
	}

	/**
	 * Confirm the payment intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\PaymentIntent $intent The payment intent object.
	 *
	 * @return Model\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */

	public function confirm_payment_intent( $intent ) {
		require_once( 'model/class-paymentintent.php' );

		$response = $this->make_request( "payment_intents/{$intent->id}/confirm", $intent->get_confirm_parameters(), 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\PaymentIntent( $response, $this );
	}


	/**
	 * Save the payment intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\PaymentIntent $intent The payment intent object.
	 *
	 * @return Model\PaymentIntent|WP_Error Return the updated Payment Intent object or WP_Error if there is an error.
	 */
	public function save_payment_intent( $intent ) {

		return $this->update_payment_intent( $intent->id, $intent->get_update_parameters() );
	}

	/**
	 * Capture the payment intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\PaymentIntent $intent The payment intent object.
	 *
	 * @return Model\PaymentIntent|WP_Error Returns the updated Payment Intent object or a WP_Error.
	 */
	public function capture_payment_intent( $intent ) {
		require_once( 'model/class-paymentintent.php' );

		$response = $this->make_request( "payment_intents/{$intent->id}/capture", $intent->get_capture_parameters(), 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\PaymentIntent( $response, $this );
	}


	/**
	 * Update Stripe payment intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id   The payment intent ID.
	 * @param array  $data The payment intent data.
	 *
	 * @return Model\PaymentIntent|WP_Error Returns the updated Payment Intent object or a WP_Error.
	 */
	public function update_payment_intent( $id, $data ) {
		require_once( 'model/class-paymentintent.php' );

		$response = $this->make_request( "payment_intents/{$id}", $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\PaymentIntent( $response, $this );
	}

	/**
	 * Cancels the payment intent.
	 *
	 * @since 4.2
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id     The payment intent id.
	 * @param string $reason The optional reason for cancelling. Possible values are duplicate, fraudulent, requested_by_customer, or abandoned.
	 *
	 * @return Model\PaymentIntent|WP_Error Returns the canceled Payment Intent object or a WP_Error.
	 */
	public function cancel_payment_intent( $id, $reason = '' ) {
		require_once( 'model/class-paymentintent.php' );

		$args     = empty( $reason ) ? array() : array( 'cancellation_reason' => $reason );
		$response = $this->make_request( "payment_intents/{$id}/cancel", $args, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\PaymentIntent( $response, $this );
	}

	## CHARGES ----------------------------------------------------


	/**
	 * Create a new charge.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $charge_meta The charge meta.
	 *
	 * @return Model\Charge|WP_Error
	 */
	public function create_charge( $charge_meta ) {
		require_once( 'model/class-charge.php' );

		$response = $this->make_request( 'charges', $charge_meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Charge( $response, $this );
	}

	/**
	 * Retrieve a charge by transaction ID.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $transaction_id The transaction ID.
	 *
	 * @return Model\Charge|WP_Error
	 */
	public function get_charge( $transaction_id ) {
		require_once( 'model/class-charge.php' );

		$response = $this->make_request( "charges/{$transaction_id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Charge( $response, $this );
	}

	/**
	 * Save a charge.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\Charge $charge The charge.
	 *
	 * @return Model\Charge|WP_Error
	 */
	public function save_charge( $charge ) {
		return $this->update_charge( $charge->id, $charge->get_update_parameters() );
	}

	/**
	 * Updates a Stripe Charge.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id   The payment intent ID.
	 * @param array  $data The payment intent data.
	 *
	 * @return Model\Charge|WP_Error Returns the updated Charge object or a WP_Error.
	 */
	private function update_charge( $id, $data ) {
		require_once( 'model/class-charge.php' );

		$response = $this->make_request( "charges/{$id}", $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Charge( $response, $this );
	}

	/**
	 * Capture a charge.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\Charge $charge The charge.
	 *
	 * @return Model\Charge|WP_Error
	 */
	public function capture_charge( $charge ) {
		require_once( 'model/class-charge.php' );

		$response = $this->make_request( "charges/{$charge->id}/capture", $charge->get_capture_parameters(), 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Charge( $response, $this );
	}


	/**
	 * Get the Stripe Plan.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The Stripe plan ID.
	 *
	 * @return bool|Model\Plan|WP_Error
	 */
	public function get_plan( $id ) {
		require_once( 'model/class-plan.php' );

		$response = $this->make_request( "plans/{$id}", array( 'expand' => array( 'product' ) ), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Plan( $response, $this );
	}

	/**
	 * Create a new plan.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $plan_meta The plan meta.
	 *
	 * @return Model\Plan|WP_Error Returns the newly created Plan object or a WP_Error.
	 */
	public function create_plan( $plan_meta ) {
		require_once( 'model/class-plan.php' );

		$response = $this->make_request( 'plans', $plan_meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Plan( $response, $this );
	}


	/**
	 * Adjusts a customer's balance.
	 *
	 * @since 5.0
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $customer_id The ID of the customer.
	 * @param double $amount      The amount to add to the customer balance.
	 * @param string $currency    The currency of the transaction.
	 *
	 * @return Model\CustomerBalanceTransaction|WP_Error
	 */
	public function adjust_customer_balance( $customer_id, $amount, $currency ) {
		require_once( 'model/class-customerbalancetransaction.php' );

		$args = array(
			'amount'   => $amount,
			'currency' => $currency,
		);

		$response = $this->make_request( "customers/{$customer_id}/balance_transactions", $args, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\CustomerBalanceTransaction( $response, $this );
	}

	/**
	 * Get the Stripe Product.
	 *
	 * @since 4.2
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The Stripe Product ID.
	 *
	 * @return Model\Product|WP_Error|bool Returns a Product object if one is found, false if not, or a WP_Error on failure.
	 */
	public function get_product( $id ) {
		require_once( 'model/class-product.php' );

		$response = $this->make_request( "products/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Product( $response, $this );
	}

	/**
	 * Create the Stripe Customer.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $customer_meta The customer metadata.
	 *
	 * @return Model\Customer|WP_Error
	 */
	public function create_customer( $customer_meta ) {
		require_once( 'model/class-customer.php' );

		$response = $this->make_request( 'customers', $customer_meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Customer( $response, $this );

	}

	/**
	 * Get the Stripe Customer.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The Stripe customer ID.
	 *
	 * @return Model\Customer|WP_Error Returns a customer object assoicated with the specified id, or a WP_Error.
	 */
	public function get_customer( $id ) {
		require_once( 'model/class-customer.php' );

		$response = $this->make_request( "customers/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Customer( $response, $this );
	}

	/**
	 * Save the Stripe Customer object.
	 *
	 * @since 3.4
	 * @since 5.5.0 Updated param and return type to the \Gravity_Forms\Stripe\API\Model namespace from the \Stripe namespace.
	 *
	 * @param Model\Customer $customer The Stripe customer object.
	 *
	 * @return Model\Customer|WP_Error
	 */
	public function save_customer( $customer ) {
		return $this->update_customer( $customer->id, $customer->get_update_parameters() );
	}

	/**
	 * Update a Stripe Customer.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id   The customer ID.
	 * @param array  $meta The customer meta.
	 *
	 * @return Model\Customer|WP_Error
	 */
	public function update_customer( $id, $meta ) {
		require_once( 'model/class-customer.php' );

		$response = $this->make_request( "customers/{$id}", $meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Customer( $response, $this );
	}


	/**
	 * Create Stripe Setup intent.
	 *
	 * @since 5.0
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $data The setup intent data.
	 *
	 * @return Model\SetupIntent|WP_Error Return the newly created Setup Intent object, or WP_Error if there is an error.
	 */
	public function create_setup_intent( $data ) {
		require_once( 'model/class-setupintent.php' );

		$response = $this->make_request( 'setup_intents', $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\SetupIntent( $response, $this );
	}


	/**
	 * Gets a setup intent.
	 *
	 * @since 5.0
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id      The setup intent ID.
	 * @param array  $options Additional request options.
	 *
	 * @return Model\SetupIntent|WP_Error Returns a Setup Intent object or WP_Error if there is an error.
	 */
	public function get_setup_intent( $id, $options = array() ) {
		require_once( 'model/class-setupintent.php' );

		$response = $this->make_request( "setup_intents/{$id}", $options, 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\SetupIntent( $response, $this );
	}


	/**
	 * Update Stripe Setup intent.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id   The setup intent ID.
	 * @param array  $data The setup intent data.
	 *
	 * @return Model\SetupIntent|WP_Error Returns the updated Setup Intent object, or WP_Error if there is an error.
	 */
	public function update_setup_intent( $id, $data ) {
		require_once( 'model/class-setupintent.php' );

		$response = $this->make_request( "setup_intents/{$id}", $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\SetupIntent( $response, $this );
	}



	/**
	 * Create the checkout session.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $data The data to create the checkout session.
	 *
	 * @return Model\Session|WP_Error
	 */
	public function create_checkout_session( $data ) {
		require_once( 'model/class-session.php' );

		$response = $this->make_request( 'checkout/sessions', $data, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Session( $response, $this );
	}

	/**
	 * Create the checkout session.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The session ID.
	 *
	 * @return Model\Session|WP_Error
	 */
	public function get_checkout_session( $id ) {
		require_once( 'model/class-session.php' );

		$response = $this->make_request( "checkout/sessions/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Session( $response, $this );

	}

	/**
	 * Get the coupon.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $coupon The coupon code.
	 *
	 * @return Model\Coupon|WP_Error
	 */
	public function get_coupon( $coupon ) {
		require_once( 'model/class-coupon.php' );

		$response = $this->make_request( "coupons/{$coupon}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Coupon( $response, $this );
	}

	/**
	 * Creates a subscription.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $meta The subscription metadata.
	 *
	 * @return Model\Subscription|WP_Error Returns the newly created subscription or WP_Error if there is an error.
	 */
	public function create_subscription( $meta ) {
		require_once( 'model/class-subscription.php' );

		$response = $this->make_request( 'subscriptions', $meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Subscription( $response, $this );
	}

	/**
	 * Get the subscription.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The subscription ID.
	 *
	 * @return Model\Subscription|WP_Error
	 */
	public function get_subscription( $id ) {
		require_once( 'model/class-subscription.php' );

		$response = $this->make_request( "subscriptions/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Subscription( $response, $this );
	}

	/**
	 * Update a Stripe Subscription.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id   The subscription ID.
	 * @param array  $meta The subscription meta.
	 *
	 * @return Model\Subscription|WP_Error
	 */
	public function update_subscription( $id, $meta ) {
		require_once( 'model/class-subscription.php' );

		$response = $this->make_request( "subscriptions/{$id}", $meta, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Subscription( $response, $this );
	}

	/**
	 * Save a subscription.
	 *
	 * @since 3.5
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param Model\Subscription $subscription The subscription object.
	 *
	 * @return Model\Subscription|WP_Error
	 */
	public function save_subscription( $subscription ) {
		return $this->update_subscription( $subscription->id, $subscription->get_update_parameters() );
	}

	/**
	 * Cancel a subscription.
	 *
	 * @since 3.5
	 * @since 5.5.0 Parameter and return type changed to an object in the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param Model\Subscription $subscription The subscription object.
	 *
	 * @return Model\Subscription|WP_Error
	 */
	public function cancel_subscription( $subscription ) {
		require_once( 'model/class-subscription.php' );

		$response = $this->make_request( "subscriptions/{$subscription->id}", array(), 'DELETE', 200 );

		return is_wp_error( $response ) ? $response : new Model\Subscription( $response, $this );

	}

	/**
	 * Get the invoice.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The invoice ID.
	 *
	 * @return Model\Invoice|WP_Error
	 */
	public function get_invoice( $id ) {
		require_once( 'model/class-invoice.php' );

		$response = $this->make_request( "invoices/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Invoice( $response, $this );
	}

	/**
	 * Pay an invoice.
	 *
	 * @since 3.5
	 * @since 5.5.0 Parameter and return type changed to an object in the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param Model\Invoice   $invoice The invoice object.
	 * @param array           $params  Params to setup the invoice.
	 *
	 * @return Model\Invoice|WP_Error Returns the updated invoice or WP_Error if there is an error.
	 */
	public function pay_invoice( $invoice, $params = array() ) {
		require_once( 'model/class-invoice.php' );

		$response = $this->make_request( "invoices/{$invoice->id}/pay", $params, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Invoice( $response, $this );
	}

	/**
	 * Add invoice item to a customer.
	 *
	 * @since 3.5
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param array $params The params.
	 *
	 * @return Model\InvoiceItem|WP_Error
	 */
	public function add_invoice_item( $params = array() ) {
		require_once( 'model/class-invoiceitem.php' );

		$response = $this->make_request( 'invoiceitems', $params, 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\InvoiceItem( $response, $this );

	}

	/**
	 * Get the event.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $id The event ID.
	 *
	 * @return Model\Event|WP_Error
	 */
	public function get_event( $id ) {
		require_once( 'model/class-event.php' );

		$response = $this->make_request( "events/{$id}", array(), 'GET', 200 );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() == '404' ? false : $response;
		}

		return new Model\Event( $response, $this );
	}

	/**
	 * Construct the event based on the specified parameters.
	 *
	 * @since 3.4
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string $body            The body object.
	 * @param string $sig_header      The signature header.
	 * @param string $endpoint_secret The endpoint secret.
	 *
	 * @return Model\Event|WP_Error
	 */
	public function construct_event( $body, $sig_header, $endpoint_secret ) {
		require_once( 'model/class-event.php' );

		return Model\Event::construct_event( $body, $sig_header, $endpoint_secret, $this );
	}


	/**
	 * Create a billing portal link for the provided customer id.
	 *
	 * @since 4.2
	 *
	 * @param string $customer_id The customer id.
	 *
	 * @return string|WP_Error Returns the billing portal URL or a WP_Error if there is an error.
	 */
	public function get_billing_portal_link( $customer_id ) {

		$params = array(
			'customer'   => $customer_id,
			'return_url' => get_site_url(),
		);

		$response = $this->make_request( 'billing_portal/sessions', $params, 'POST', 200 );

		return is_wp_error( $response ) ? $response : $response['url'];
	}

	/**
	 * Refund a payment.
	 *
	 * @since 4.2
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @param string  $transaction_id The transaction ID to refund.
	 * @param boolean $payment_intent Whether the payment was created with the payment intents API (true) or charges API (false).
	 *
	 * @return Model\Refund|WP_Error
	 */
	public function create_refund( $transaction_id, $payment_intent ) {
		require_once( 'model/class-refund.php' );

		$key = $payment_intent ? 'payment_intent' : 'charge';

		$response = $this->make_request( 'refunds', array( $key => $transaction_id ), 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Refund( $response, $this );
	}

	/**
	 * Finalize an invoice.
	 *
	 * @since 5.0
	 * @since 5.5.0 Returns an object from the \Gravity_Forms\Stripe\API\Model namespace instead of the \Stripe namespace.
	 *
	 * @return Model\Invoice|WP_Error
	 */
	public function finalize_invoice( $invoice_id ) {
		require_once( 'model/class-invoice.php' );

		$response = $this->make_request( "invoices/{$invoice_id}/finalize", array(), 'POST', 200 );

		return is_wp_error( $response ) ? $response : new Model\Invoice( $response, $this );
	}

}
