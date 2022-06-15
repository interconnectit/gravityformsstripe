<?php

/**
 * Gravity Forms Stripe API Library.
 *
 * @since     3.4
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2020, Rocketgenius
 */
class GF_Stripe_API {
	/**
	 * Stripe API key.
	 *
	 * @since 3.4
	 * @var   string $api_key Stripe API key.
	 */
	protected $api_key;

	/**
	 * Stripe API version.
	 *
	 * @since 3.4
	 * @var   string $api_version Stripe API version.
	 */
	protected $api_version;

	/**
	 * Stripe API Client.
	 *
	 * @since 4.2
	 *
	 * @var \Stripe\StripeClient  $stripe_client Stripe API Client
	 */
	protected $stripe_client;

	/**
	 * Null or an instance of the Gravity Forms Stripe Add-On.
	 *
	 * @since 4.3
	 *
	 * @var GFStripe
	 */
	protected $addon;

	/**
	 * Initialize Stripe API library.
	 *
	 * @since 3.4
	 * @since 4.3 Added the $addon param.
	 *
	 * @param string        $api_key Stripe API key.
	 * @param null|GFStripe $addon   Null or an instance of the Gravity Forms Stripe Add-On.
	 */
	public function __construct( $api_key, $addon = null ) {

		$this->api_key     = $api_key;
		$this->api_version = '2020-03-02';
		$this->addon       = empty( $addon ) ? gf_stripe() : $addon;

		// If Stripe class does not exist, load Stripe API library.
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once 'autoload.php';
		}

		require_once 'deprecated.php';
		$this->init_client();

		// Set Stripe API key.
		Stripe\Stripe::setApiKey( $api_key );
		// Set API version.
		\Stripe\Stripe::setApiVersion( $this->api_version );

		if ( method_exists( '\Stripe\Stripe', 'setAppInfo' ) ) {
			// Send plugin title, version and site url along with API calls.
			\Stripe\Stripe::setAppInfo( gf_stripe()->get_short_title(), gf_stripe()->get_version(), esc_url( site_url() ) );
		}

	}

	/**
	 * Initializes the StripeClient with the API key.
	 *
	 * @since 4.3
	 */
	private function init_client() {
		if ( ! class_exists( '\Stripe\StripeClient' ) ) {
			$reflector = new ReflectionClass( \Stripe\Stripe::class );
			$this->addon->log_debug( __METHOD__ . '(): \Stripe\StripeClient does not exist. An older or incomplete version of the Stripe PHP SDK is being included from ' . dirname( $reflector->getFileName() ) );

			return;
		}

		if ( empty( $this->api_key ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Unable to initialize the Stripe API Client. The add-on is not connected to a Stripe account.' );

			return;
		}

		$this->stripe_client = new \Stripe\StripeClient( $this->api_key );
	}

	/**
	 * Get Stripe account info.
	 *
	 * @since 3.4
	 *
	 * @return bool|WP_Error|\Stripe\Account Return WP_Error if exceptions thrown.
	 */
	public function get_account() {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			// Attempt to retrieve account details.
			return $this->stripe_client->accounts->retrieve();
		} catch ( \Exception $e ) {

			// Log that key validation failed.
			gf_stripe()->log_error( __METHOD__ . '(): ' . $e->getMessage() );

			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Create a new charge.
	 *
	 * @since 3.4
	 *
	 * @param array $charge_meta The charge meta.
	 *
	 * @return Stripe\Charge|WP_Error
	 */
	public function create_charge( $charge_meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->charges->create( $charge_meta );
		} catch ( \Exception $e ) {

			return $this->get_error( $e );
		}
	}

	/**
	 * Retrieve a charge by transaction ID.
	 *
	 * @since 3.4
	 *
	 * @param string $transaction_id The transaction ID.
	 *
	 * @return \Stripe\Charge|WP_Error
	 */
	public function get_charge( $transaction_id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->charges->retrieve( $transaction_id );
		} catch ( \Exception $e ) {

			return $this->get_error( $e );
		}
	}

	/**
	 * Save a charge.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\Charge $charge The charge.
	 *
	 * @return \Stripe\Charge|WP_Error
	 */
	public function save_charge( $charge ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->charges->update( $charge->id, $charge->serializeParameters() );
		} catch ( \Exception $e ) {

			return $this->get_error( $e );
		}
	}

	/**
	 * Capture a charge.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\Charge $charge The charge.
	 *
	 * @return \Stripe\Charge|WP_Error
	 */
	public function capture_charge( $charge ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->charges->capture( $charge->id, $charge->serializeParameters() );
		} catch ( \Exception $e ) {

			return $this->get_error( $e );
		}
	}

	/**
	 * Create a new plan.
	 *
	 * @since 3.4
	 *
	 * @param array $plan_meta The plan meta.
	 *
	 * @return Stripe\Plan|WP_Error
	 */
	public function create_plan( $plan_meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->plans->create( $plan_meta );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the Stripe Plan.
	 *
	 * @since 3.4
	 *
	 * @param string $id The Stripe plan ID.
	 *
	 * @return bool|\Stripe\Plan|WP_Error
	 */
	public function get_plan( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->plans->retrieve( $id, array( 'expand' => array( 'product' ) ) );
		} catch ( \Exception $e ) {
			/**
			 * There is no error type specific to failing to retrieve a subscription when an invalid plan ID is passed. We assume here
			 * that any 'invalid_request_error' means that the subscription does not exist even though other errors (like providing
			 * incorrect API keys) will also generate the 'invalid_request_error'. There is no way to differentiate these requests
			 * without relying on the error message which is more likely to change and not reliable.
			 */

			// Get error response.
			$response = $e->getJsonBody();

			// If error is an invalid request error, return error message.
			if ( rgars( $response, 'error/type' ) !== 'invalid_request_error' ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			return false;
		}
	}

	/**
	 * Get the Stripe Product.
	 *
	 * @since 4.2
	 *
	 * @param string $id The Stripe Product ID.
	 *
	 * @return bool|\Stripe\Product|WP_Error
	 */
	public function get_product( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->products->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Create the Stripe Customer.
	 *
	 * @since 3.4
	 *
	 * @param array $customer_meta The customer metadata.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function create_customer( $customer_meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->customers->create( $customer_meta );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the Stripe Customer.
	 *
	 * @since 3.4
	 *
	 * @param string $id The Stripe customer ID.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function get_customer( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->customers->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Save the Stripe Customer object.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\Customer $customer The Stripe customer object.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function save_customer( $customer ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->customers->update( $customer->id, $customer->serializeParameters() );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Update a Stripe Customer.
	 *
	 * @since 3.4
	 *
	 * @param string $id   The customer ID.
	 * @param array  $meta The customer meta.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function update_customer( $id, $meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->customers->update( $id, $meta );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Create Stripe payment intent.
	 *
	 * @since 3.4
	 *
	 * @param array $data The payment intent data.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function create_payment_intent( $data ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->create( $data );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Update Stripe payment intent.
	 *
	 * @since 3.4
	 *
	 * @param string $id   The payment intent ID.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function get_payment_intent( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Update Stripe payment intent.
	 *
	 * @since 3.4
	 *
	 * @param string $id   The payment intent ID.
	 * @param array  $data The payment intent data.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function update_payment_intent( $id, $data ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->update( $id, $data );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Confirm the payment intent.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\PaymentIntent $intent The payment intent object.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function confirm_payment_intent( $intent ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->confirm( $intent->id, $intent->serializeParameters() );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Save the payment intent.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\PaymentIntent $intent The payment intent object.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function save_payment_intent( $intent ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->update( $intent->id, $intent->serializeParameters() );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Capture the payment intent.
	 *
	 * @since 3.4
	 *
	 * @param \Stripe\PaymentIntent $intent The payment intent object.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function capture_payment_intent( $intent ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->paymentIntents->capture( $intent->id, $intent->serializeParameters() );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Cancels the payment intent.
	 *
	 * @since 4.2
	 *
	 * @param string $id     The payment intent id.
	 * @param string $reason The optional reason for cancelling. Possible values are duplicate, fraudulent, requested_by_customer, or abandoned.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error Return WP_Error if exceptions thrown.
	 */
	public function cancel_payment_intent( $id, $reason = '' ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		$params = array();

		if ( ! empty( $reason ) ) {
			$params['cancellation_reason'] = $reason;
		}

		try {
			return $this->stripe_client->paymentIntents->cancel( $id, $params );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Create the checkout session.
	 *
	 * @since 3.4
	 *
	 * @param array $data The data to create teh session.
	 *
	 * @return \Stripe\Checkout\Session|WP_Error
	 */
	public function create_checkout_session( $data ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->checkout->sessions->create( $data );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Create the checkout session.
	 *
	 * @since 3.4
	 *
	 * @param string $id The session ID.
	 *
	 * @return \Stripe\Checkout\Session|WP_Error
	 */
	public function get_checkout_session( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->checkout->sessions->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the coupon.
	 *
	 * @since 3.4
	 *
	 * @param string $coupon The coupon code.
	 *
	 * @return \Stripe\Coupon|WP_Error
	 */
	public function get_coupon( $coupon ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->coupons->retrieve( $coupon );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Create the subscription.
	 *
	 * @since 3.4
	 *
	 * @param array $meta The subscription metadata.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function create_subscription( $meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->subscriptions->create( $meta );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the subscription.
	 *
	 * @since 3.4
	 *
	 * @param string $id The subscription ID.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function get_subscription( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->subscriptions->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Update a Stripe Subscription.
	 *
	 * @since 3.4
	 *
	 * @param string $id   The subscription ID.
	 * @param array  $meta The subscription meta.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function update_subscription( $id, $meta ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->subscriptions->update( $id, $meta );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Save a subscription.
	 *
	 * @since 3.5
	 *
	 * @param \Stripe\Subscription $subscription The subscription object.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function save_subscription( $subscription ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->subscriptions->update( $subscription->id, $subscription->serializeParameters() );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Cancel a subscription.
	 *
	 * @since 3.5
	 *
	 * @param \Stripe\Subscription $subscription The subscription object.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function cancel_subscription( $subscription ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->subscriptions->cancel( $subscription->id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the invoice.
	 *
	 * @since 3.4
	 *
	 * @param string $id The invoice ID.
	 *
	 * @return \Stripe\Invoice|WP_Error
	 */
	public function get_invoice( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->invoices->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Pay an invoice.
	 *
	 * @since 3.5
	 *
	 * @param \Stripe\Invoice $invoice The invoice object.
	 * @param array           $params  Params to setup the invoice.
	 *
	 * @return \Stripe\Invoice|WP_Error
	 */
	public function pay_invoice( $invoice, $params = array() ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->invoices->pay( $invoice->id, $params );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Add invoice item to a customer.
	 *
	 * @since 3.5
	 *
	 * @param array $params The params.
	 *
	 * @return \Stripe\InvoiceItem|WP_Error
	 */
	public function add_invoice_item( $params = array() ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->invoiceItems->create( $params );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the event.
	 *
	 * @since 3.4
	 *
	 * @param string $id The event ID.
	 *
	 * @return \Stripe\Event|WP_Error
	 */
	public function get_event( $id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return $this->stripe_client->events->retrieve( $id );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the event.
	 *
	 * @since 3.4
	 *
	 * @param string $body            The body object.
	 * @param string $sig_header      The signature header.
	 * @param string $endpoint_secret The endpoint secret.
	 *
	 * @return \Stripe\Event|WP_Error
	 */
	public function construct_event( $body, $sig_header, $endpoint_secret ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {
			return \Stripe\Webhook::constructEvent( $body, $sig_header, $endpoint_secret );
		} catch ( \Exception $e ) {

			return $this->get_error( $e );
		}
	}


	/**
	 * Create a billing portal link for the provided customer id.
	 *
	 * @since 4.2
	 *
	 * @param string $customer_id The customer id.
	 *
	 * @return string|WP_Error
	 */
	public function get_billing_portal_link( $customer_id ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		try {

			$response = $this->stripe_client->billingPortal->sessions->create(
				array(
					'customer'   => $customer_id,
					'return_url' => get_site_url(),
				)
			);

			return $response->url;

		} catch ( \Stripe\Exception\ApiErrorException $e ) {

			return $this->get_error( $e );

		}
	}

	/**
	 * Refund a payment.
	 *
	 * @since 4.2
	 *
	 * @param string  $transaction_id The transaction ID to refund
	 * @param boolean $payment_intent Whether the payment was created with the payment intents API (true) or charges API (false)
	 *
	 * @return \Stripe\Refund|WP_Error
	 */
	public function create_refund( $transaction_id, $payment_intent ) {
		if ( empty( $this->stripe_client ) ) {
			return $this->get_client_error();
		}

		$key = $payment_intent ? 'payment_intent' : 'charge';

		try {
			return $this->stripe_client->refunds->create( [ $key => $transaction_id ] );
		} catch ( \Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the exception and return WP_Error.
	 *
	 * @param Exception $e The exception.
	 *
	 * @return WP_Error
	 */
	private function get_error( $e ) {
		$error_code = $e->getCode();

		if ( is_int( $error_code ) ) {
			// If error code is 0, it means it's a general exception, otherwise, it's a Stripe exception.
			if ( $error_code === 0 ) {
				// WP_Error returns early when the code is empty().
				$error_code = 'zero';
			}
			return new WP_Error( $error_code, $e->getMessage() );
		} else {
			return new WP_Error( $e->getError()->code, $e->getError()->message );
		}
	}

	/**
	 * Return the WP_Error for when the Stripe API client wasn't initialized.
	 *
	 * @since 4.3
	 *
	 * @return WP_Error
	 */
	private function get_client_error() {
		if ( ! class_exists( '\Stripe\StripeClient' ) ) {
			return new WP_Error( 'stripe_client_missing', __( 'The Stripe API Client is missing. The theme or another plugin is including an older or incomplete version of the Stripe PHP SDK.', 'gravityformsstripe' ) );
		}

		return new WP_Error( 'stripe_client_not_connected', __( 'The Stripe API Client is not initialized. Please connect the add-on to a Stripe account.', 'gravityformsstripe' ) );
	}

}
