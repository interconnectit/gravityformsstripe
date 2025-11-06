<?php
/**
 * Gravity Forms Stripe Payment Element Intents manager.
 *
 * This class acts as a wrapper for all the logic required to create and update the payment element intents depending on different feed settings.
 *
 * @since     5.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2021, Rocketgenius
 */
class GF_Payment_Element_Payment {

	/**
	 * Instance of a GFStripe object.
	 *
	 * @since 5.0
	 *
	 * @var GFStripe
	 */
	protected $addon;

	/**
	 * The currency used in the current transaction.
	 *
	 * @since 5.0
	 *
	 * @var string
	 */
	protected $currency;

	/**
	 * The id of the payment intent being processed.
	 *
	 * @since 5.0
	 *
	 * @var string
	 */
	public $intent_id;

	/**
	 * The id of the invoice, used only for subscriptions with `send-invoice` collection method.
	 *
	 * @since 5.0
	 *
	 * @var string
	 */
	public $invoice_id;

	/**
	 * The customer related to the payment being made.
	 *
	 * @since 5.1
	 *
	 * @var \Stripe\Customer
	 */
	public $customer;

	/**
	 * GF_Stripe_Payment_Element_Intent constructor.
	 *
	 * @since 5.0
	 *
	 * @param GFStripe $addon Instance of a GFStripe object.
	 */
	public function __construct( $addon ) {
		$this->addon    = $addon;
		$this->currency = GFCommon::get_currency();
	}

	/**
	 * Returns a list of the payment methods available to be used in the payment element.
	 *
	 * If this filter returns an empty list, automatic payment methods will be used.
	 *
	 * @since 5.0
	 *
	 * @param array $feed The current feed object being processed.
	 * @param array $form The current form object being processed.
	 *
	 * @return array
	 */
	public function get_payment_methods( $feed, $form ) {
		/**
		 * Allow Manually setting the payment methods used by the payment element.
		 *
		 * @since  5.0
		 *
		 * @param array $payment_methods An array of payment methods to be used.
		 * @param array $feed The feed currently being processed.
		 * @param array $form The form which created the current entry.
		 */
		$methods = apply_filters( 'gform_stripe_payment_element_payment_methods', array(), $feed, $form );

		if ( ! empty( $methods ) ) {
			$this->addon->log_debug( __METHOD__ . '() - Payment methods filter is being used for submission with form_id: ' . rgar( $form, 'id' ) . ' and feed_id: ' . rgar( $feed, 'id' ) . ', feed type is: ' . $feed['meta']['transactionType'] . ',  payment methods: ' . json_encode( $methods ) . ',  tracking id: ' . rgpost( 'tracking_id' ) );
		}

		return $methods;
	}

	/**
	 * Returns a list of payment methods that use automatic charge as collection method.
	 *
	 *
	 * @since 5.0
	 *
	 * @param array $feed  The current feed object being processed.
	 * @param array $form  The current form object being processed.
	 *
	 * @return array
	 */
	public function get_subscriptions_automatic_charge_methods( $feed, $form ) {
		$methods = array(
			'card',
			'sepa_debit',
			'bancontact',
			'eps',
			'ideal',
			'us_bank_account',
			'link',
			'bacs_debit',
			'boleto',
			'fpx',
			'au_becs_debit',
			'acss_debit'
		);
		/**
		 * Get a list of ist of the payment methods that use automatic charge as collection method.
		 *
		 * @since  5.0
		 *
		 * @param array $mthods An array of payment methods to be used.
		 * @param array $feed   The feed currently being processed.
		 * @param array $form   The form which created the current entry.
		 */
		return apply_filters( 'gform_stripe_payment_element_subscriptions_automatic_charge_methods', $methods, $feed, $form );
	}

	/**
	 * Created an intent after front end validation.
	 *
	 * @since 5.0
	 *
	 * @param array         $order_data The order data exctracted from the user's submission.
	 * @param int           $feed_id    The id of the feed being processed.
	 * @param int           $form_id    The id of the form being processed.
	 * @param GF_Stripe_API $api        The stripe API instance used to communicate with the stripe API.
	 * @param array         $entry      If the entry is already created, pass it on.
	 *
	 * @return \Stripe\PaymentIntent|\Stripe\SetupIntent|WP_Error
	 */
	public function create_payment_intent( $order_data, $feed_id, $form_id, $api, $entry = null ) {
		$feed = $this->addon->get_feed( $feed_id );
		$form = GFAPI::get_form( $form_id );

		if ( $entry === null ) {
			$temp_entry = isset( $order_data['temp_lead'] ) ? $order_data['temp_lead'] : $order_data;
		} else {
			$temp_entry = $entry;
		}

		$intent_meta     = array();
		$amount          = rgar( $order_data, 'total', 0 );
		$payment_methods = $this->get_payment_methods( $feed, $form );
		if ( ! empty( $payment_methods ) ) {
			// When payment method types are explicitly defined, 'card' must be one of the methods.
			$payment_methods                     = array_unique( array_merge( $payment_methods, array( 'card' ) ) );
			$intent_meta['payment_method_types'] = $payment_methods;
		} else {
			$intent_meta['automatic_payment_methods'] = array( 'enabled' => true );
		}
		$intent_meta['description'] = $this->addon->get_payment_description(
			$temp_entry,
			$order_data,
			$this->addon->get_feed( $feed_id )
		);

		$intent_meta['customer']       = $this->get_customer( $feed, $form, $api, $temp_entry );
		$intent_meta['amount']         = $this->addon->get_amount_export( $amount, $this->currency );
		$intent_meta['currency']       = strtolower( $this->currency );
		$intent_meta['capture_method'] = $this->addon->get_payment_element_capture_method( $form, $feed );

		// Run intent meta through product payment data filter.
		$intent_meta = $this->addon->get_product_payment_data( $intent_meta, $feed, $order_data, $form, $temp_entry );

		// Add link token if it exists to show saved payment details.
		$link_token = sanitize_text_field( rgar( $_COOKIE, 'gf_stripe_token' ) );
		if ( $link_token ) {
			$intent_meta['payment_method_options'] = array(
				'link' => array(
					'persistent_token' => $link_token,
				),
			);
		}


		$this->addon->log_debug( __METHOD__ . '(): Creating intent for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) );
		$this->addon->log_debug( __METHOD__ . '(): Intent meta to be created: ' . print_r( $intent_meta, true ) );

		return $api->create_payment_intent( $intent_meta );
	}

	/**
	 * Creates a subscription.
	 *
	 * @since 5.0
	 *
	 * @param array         $order_data The submitted order information.
	 * @param int           $feed_id    The current feed id.
	 * @param int           $form_id    The current form id.
	 * @param GF_Stripe_API $api        An instance of the Stripe API.
	 * @param null|array    $entry      If the entry has been already created before, pass it on.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function create_subscription( $order_data, $feed_id, $form_id, $api, $entry = null ) {

		if ( $entry === null ) {
			$temp_entry = isset( $order_data['temp_lead'] ) ? $order_data['temp_lead'] : $order_data;
		} else {
			$temp_entry = $entry;
		}

		$feed           = $this->addon->get_feed( $feed_id );
		$form           = GFAPI::get_form( $form_id );
		$currency       = rgar( $temp_entry, 'currency' );
		$plan           = $this->addon->get_plan_for_feed( $feed, $order_data['total'], $order_data['trial_days'], $currency );
		$setup_fee      = $this->addon->get_amount_export( rgar( $order_data, 'setup_fee', 0 ), $currency );
		$customer       = $this->get_customer( $feed, $form, $api, $temp_entry, $setup_fee );
		$payment_method = rgar( $order_data, 'payment_method' );


		$subscription_data = array(
			'description'      => $this->addon->get_payment_description( $temp_entry, $order_data['submission'], $feed ),
			'customer'         => $customer,
			'items'            => array(
				array(
					'plan' => $plan->id,
				),
			),
			'payment_behavior' => 'default_incomplete',
			'payment_settings' => array( 'save_default_payment_method' => 'on_subscription' ),
			'expand'           => array( 'latest_invoice.payment_intent' ),
		);

		$coupon_field_id   = rgar( $feed['meta'], 'customerInformation_coupon' );
		$coupon            = $this->addon->maybe_override_field_value( rgar( $temp_entry, $coupon_field_id ), $form, $temp_entry, $coupon_field_id );
		$coupon_field_type = $this->get_coupon_field_type( $form, $coupon_field_id );

		if ( $coupon_field_type === 'coupon' ) {
			$subscription_data['coupon'] = strtoupper( $coupon );
		} else {
			$subscription_data['coupon'] = $coupon;
		}

		// Some payment methods work by sending an invoice to the customer and not charging automatically.
		$automatic_charge_methods = $this->get_subscriptions_automatic_charge_methods( $feed, $form );
		if ( ! in_array( $payment_method, $automatic_charge_methods ) ) {
			$subscription_data['collection_method'] = 'send_invoice';
			$subscription_data['days_until_due']    = 1;
		}

		$payment_methods = $this->get_payment_methods( $feed, $form );
		if ( ! empty( $payment_methods ) ) {
			// When payment method types are explicitly defined, 'card' must be one of the methods.
			$payment_methods                                               = array_unique( array_merge( $payment_methods, array( 'card' ) ) );
			$subscription_data['payment_settings']['payment_method_types'] = $payment_methods;
		} else {
			$subscription_data['payment_settings']['payment_method_types'] = array();
		}

		if ( $order_data['trial_days'] > 0 ) {
			$subscription_data['trial_from_plan'] = true;
		}

		// Run the subscription through the filters used by the Stripe Add-On.
		$subscription_data = $this->addon->get_subscription_params( $subscription_data, $customer, $plan, $feed, $temp_entry, $form, $order_data['trial_days'] );

		// If the trial period is different from the plan after filtering subscription data, don't use the plan's trial period.
		$is_trial_filtered = isset( $subscription_data['trial_period_days'] );
		if ( $is_trial_filtered ) {
			$subscription_data['trial_from_plan'] = false;
		}

		return $api->create_subscription( $subscription_data );
	}

	/**
	 * Creates or gets a customer.
	 *
	 * @since 5.0
	 * @since 5.1 Added the balance param.
	 *
	 * @param array         $feed       The current feed being processed.
	 * @param array         $form       The current form being processed.
	 * @param GF_Stripe_API $api        An instance of the Stripe API.
	 * @param array         $entry      The current entry being processed.
	 * @param float         $balance    Balance to be credited or debited from customer, used to add one time fees like a setup fee.
	 *
	 * @return \Stripe\Customer
	 */
	public function get_customer( $feed, $form, $api, $entry, $balance = 0 ) {

		$customer_meta = array(
			'description' => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_description' ) ),
			'email'       => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_email' ) ),
			'name'        => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_name' ) ),
			'address'     => array(
				'city'        => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_city' ) ),
				'country'     => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_country' ) ),
				'line1'       => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_line1' ) ),
				'line1'       => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_line1' ) ),
				'line2'       => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_line2' ) ),
				'postal_code' => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_zip' ) ),
				'state'       => $this->addon->get_field_value( $form, $entry, rgar( $feed['meta'], 'billingInformation_address_state' ) ),
			),
		);

		if ( $balance ) {
			$customer_meta['balance'] = $balance;
		}

		$customer = $this->addon->get_customer( $this->customer ? $this->customer->id : '' , $feed, $entry, $form );

		if ( $customer ) {
			$api->update_customer( $customer->id, $customer_meta );
		} else {
			$customer = $this->addon->create_customer( $customer_meta, $feed, $entry, $form );
		}

		if ( is_wp_error( $customer ) ) {
			$customer = $api->create_customer( array() );
		}

		$this->customer = $customer;

		return $this->customer;
	}

	/**
	 * Calculates the discount amount a stripe coupon can apply to the total of an order.
	 *
	 * @since 5.0
	 *
	 * @param double         $order_total   The order total.
	 * @param \Stripe\Coupon $stripe_coupon The stripe coupon.
	 *
	 * @return float|int
	 */
	public function get_coupon_discount( $order_total, \Stripe\Coupon $stripe_coupon ) {
		if ( ! $stripe_coupon->valid ) {
			return 0;
		}

		if ( $stripe_coupon->amount !== null ) {
			return $this->addon->get_amount_import( $stripe_coupon->amount, $this->currency );
		}

		if ( $stripe_coupon->percent_off !== null ) {
			return ( $order_total * $stripe_coupon->percent_off ) / 100;
		}

		return 0;
	}

	/**
	 * Retrieves the intent for the current subscription.
	 *
	 * This intent could be a payment intent, or a setup intent, depending on whether the subscription has a trial or not.
	 *
	 * @since 5.0.0
	 *
	 * @param $subscription \Stripe\Subscription The Stripe subscription object.
	 * @param $feed         array                The current feed being processed.
	 * @param $api          GF_Stripe_API        The stripe API instance used to communicate with the stripe API.
	 *
	 * @return \Stripe\Invoice|\Stripe\PaymentIntent|\Stripe\SetupIntent|WP_Error|null
	 */
	public function get_subscription_intent( $subscription, $feed, $api ) {
		$intent_id       = null;
		$subscription_id = $subscription->id;

		if ( rgar( $subscription, 'collection_method' ) === 'send_invoice' ) {
			return $this->get_subscription_invoice_intent( $subscription, $feed, $api );
		}

		if ( rgars( $subscription, 'latest_invoice/payment_intent/id' ) !== null ) {
			$intent_id = $subscription->latest_invoice->payment_intent->id;
		} else {
			$intent_id = $subscription->pending_setup_intent;
		}

		return $this->get_stripe_payment_object( $feed, $intent_id, $api );

	}

	/**
	 * Retrieves the intent for a subscription with `send-invoice` collection method.
	 *
	 * @since 5.4
	 *
	 * @param $subscription \Stripe\Subscription The Stripe subscription object.
	 * @param $feed         array                The current feed being processed.
	 * @param $api          GF_Stripe_API        The stripe API instance used to communicate with the stripe API.
	 *
	 * @return \Stripe\Invoice|WP_Error|null
	 */
	public function get_subscription_invoice_intent( $subscription, $feed, $api ) {
		$invoice           = rgar( $subscription, 'latest_invoice' );
		$finalized_invoice = $api->finalize_invoice( $invoice_id );
		$form_id           = rgpost( 'form_id' );

		if ( is_wp_error( $finalized_invoice ) ) {
			$this->log_debug( __METHOD__ . '(): Unable to finalize invoice; ' . $finalized_invoice->get_error_message() . ',  tracking id: ' . rgpost( 'tracking_id' ) );

			return $finalized_invoice;
		}

		$invoice_intent_id = rgobj( $finalized_invoice, 'payment_intent' );

		return $invoice_intent_id ? $this->get_stripe_payment_object( $feed, $invoice_intent_id, $api ) : null;
	}

	/**
	 * Retrieves a payment intent, a setup intent or an invoice for the current payment.
	 *
	 * @since 5.0
	 *
	 * @param array         $feed The current feed being processed.
	 * @param string        $id   The ID of the intent.
	 * @param GF_Stripe_API $api  The stripe API instance used to communicate with the stripe API.
	 *
	 * @return \Stripe\PaymentIntent|\Stripe\SetupIntent|\Stripe\Invoice|WP_Error
	 */
	public function get_stripe_payment_object( $feed, $id, $api ) {
		if ( strpos( $id, 'in' ) === 0 ) {
			return $api->get_invoice( $id );
		}

		if ( strpos( $id, 'seti_' ) === 0 ) {
			return $api->get_setup_intent( $id, array( 'expand' => array( 'payment_method' ) ) );
		} else {
			return $api->get_payment_intent( $id, array( 'expand' => array( 'payment_method', 'invoice' ) ) );
		}
	}

	/**
	 * Determines if the mapped coupon is a coupon field or a text field.
	 *
	 * @since 5.5.0
	 *
	 * @param array $form            The current form object.
	 * @param int   $coupon_field_id The id of the coupon field.
	 *
	 * @return string The type of the coupon field.
	 */
	public function get_coupon_field_type( $form, $coupon_field_id ) {
		$field = GFFormsModel::get_field( $form, $coupon_field_id );
		if ( $field ) {
			return $field->type;
		}

		return '';
	}
}
