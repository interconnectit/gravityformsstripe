<?php
/**
 * Handles logic for the the Stripe Elements service.
 *
 * @package Gravity_Forms_Stripe\Elements;
 */

namespace Gravity_forms\Gravity_Forms_Stripe\Elements;

/**
 * Class class Experimental_GF_Elements_Handler {
 *
 * @since 5.8.0
 *
 * Provides functionality for handling payments made with the Elements Credit Card field.
 * Note: Do not use this class directly. It is currently in experimental phase and will most likely have breaking changes in the near future.
 */
class Experimental_GF_Elements_Handler {

	/**
	 * @var \GFStripe
	 */
	private $addon;

	//write a constructor
	public function __construct( $api = null, $addon = null ) {

		$this->addon = $addon ? $addon : gf_stripe();
	}

	/**
	 * Get the Stripe API object.
	 *
	 * @since 6.0
	 *
	 * @return \GFStripeAPI
	 */
	private function get_api( $feed ) {

		// Return API connection configured in feed settings (if enabled).
		$is_feed_api_enabled = ! empty( $feed ) && $this->addon->is_feed_stripe_connect_enabled( $feed['id'] );

		// If feed specific API connection is enabled, use it.
		if ( $is_feed_api_enabled ) {
			$mode = $this->addon->get_api_mode( $feed['meta'], $feed['id'] );
			return $this->addon->include_stripe_api( $mode, $feed['meta'] );
		}

		// If feed specific API connection is not enabled, use the global settings API connection.
		return $this->addon->include_stripe_api();
	}

	/**
	 * AJAX action to create a payment intent.
	 *
	 * @since 6.0
	 */
	public function ajax_create_payment_intent() {

		$this->addon->log_debug( __METHOD__ . '(): Creating payment intent via AJAX.' );

		check_ajax_referer( 'gfstripe_create_payment_intent', 'nonce' );

		$entry = \GFAPI::get_entry( intval( rgpost( 'entry_id' ) ) );

		// Checking rate limits.
		$rate_limit = $this->addon->maybe_hit_rate_limits( $entry['form_id'] );
		if ( rgar( $rate_limit, 'error_message' ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Error creating payment intent via AJAX: ' . rgar( $rate_limit, 'error_message' ) );
			wp_send_json_error( array( 'message' => rgar( $rate_limit, 'error_message' ) ) );
		}

		$form            = \GFAPI::get_form( $entry['form_id'] );
		$feed            = $this->addon->get_feed( intval( rgpost( 'feed_id' ) ) );
		$submission_data = $this->addon->get_submission_data( $feed, $form, $entry );

		$args = array(
			'amount'         => intval( $this->addon->get_amount_export( $submission_data['payment_amount'], $entry['currency'] ) ),
			'currency'       => $entry['currency'],
			'description'    => $this->get_payment_description( $entry, $submission_data, $feed ),
			'capture_method' => $this->addon->get_capture_method( $feed, $submission_data, $form, $entry ),
			'metadata'       => $this->addon->get_stripe_meta_data( $feed, $entry, $form ),
		);

		// Adding entry id to metadata.
		$args['metadata']['gf_entry_id'] = rgar( $entry, 'id' );

		/**
		 * Filter to change the payment intent data before creating it.
		 *
		 * @since 3.5
		 *
		 * @param array $args The payment intent data.
		 * @param array $feed The feed object.
		 */
		$args = apply_filters( 'gform_stripe_payment_intent_pre_create', $args, $feed );

		$payment_intent = $this->get_api( $feed )->create_payment_intent( $args );

		if ( is_wp_error( $payment_intent ) ) {
			$this->addon->log_error( __METHOD__ . '(): Error creating payment intent. ' . $payment_intent->get_error_message() );
			wp_send_json_error( array( 'message' => $payment_intent->get_error_messages() ) );
		}

		// Mark entry as processing.
		$this->mark_entry_processing( $entry, $payment_intent->id, 1 );

		$this->addon->log_debug( __METHOD__ . '(): Payment intent ' . $payment_intent->id . ' created successfully.' );

		wp_send_json_success( array( 'client_secret' => $payment_intent->client_secret ) );
	}

	/**
	 * AJAX action to create a subscription.
	 *
	 * @since 6.0
	 */
	public function ajax_create_subscription() {

		$this->addon->log_debug( __METHOD__ . '(): Creating subscription via AJAX.' );

		check_ajax_referer( 'gfstripe_create_subscription', 'nonce' );

		$entry_id = intval( rgpost( 'entry_id' ) );
		$feed_id  = intval( rgpost( 'feed_id' ) );
		$entry    = \GFAPI::get_entry( $entry_id );

		// Checking rate limits.
		$rate_limit = $this->addon->maybe_hit_rate_limits( $entry['form_id'] );
		if ( rgar( $rate_limit, 'error_message' ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Error creating payment intent via AJAX: ' . rgar( $rate_limit, 'error_message' ) );
			wp_send_json_error( array( 'message' => rgar( $rate_limit, 'error_message' ) ) );
		}

		$feed = $this->addon->get_feed( $feed_id );
		$api  = $this->get_api( $feed );

		$subscription = $this->create_subscription( $entry, $feed, $api );

		if ( is_wp_error( $subscription ) ) {
			$this->addon->log_error( __METHOD__ . '(): Error creating subscription. ' . $subscription->get_error_message() );
			wp_send_json_error( array( 'message' => $subscription->get_error_messages() ) );
		}

		// If subscription has a pending setup intent (i.e. subscriptions with trial enabled), add the subscription id to the setup intent metadata.
		// This is used in the setup_intent.setup_failed webhook to cancel the associated subscription.
		if ( $subscription->pending_setup_intent ) {
			$api->update_setup_intent( $subscription->pending_setup_intent->id, array( 'metadata' => array( 'gf_parent_subscription_id' => $subscription->id ) ) );
		}

		// Mark entry as processing.
		$this->mark_entry_processing( $entry, $subscription->id, 2 );

		$this->addon->log_debug( __METHOD__ . '(): Subscription ' . $subscription->id . ' created successfully.' );

		wp_send_json_success( array( 'subscription' => $subscription ) );
	}

	/**
	 * AJAX action to get an entry.
	 *
	 * @since 6.0
	 */
	public function ajax_get_entry() {

		check_ajax_referer( 'gfstripe_get_entry', 'nonce' );

		$entry_id = intval( rgpost( 'entry_id' ) );
		$entry = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			$this->addon->log_error( __METHOD__ . '(): Error retrieving entry. ' . $entry->get_error_message() );
			wp_send_json_error( array( 'message' => $entry->get_error_messages() ) );
		}

		wp_send_json_success( array( 'entry' => $entry ) );
	}

	/**
	 * AJAX action to get form confirmation data.
	 *
	 * @since 6.0
	 */
	public function ajax_handle_successful_entry() {

		check_ajax_referer( 'gfstripe_handle_successful_entry', 'nonce' );

		$entry = \GFAPI::get_entry( intval( rgpost( 'entry_id' ) ) );

		if ( is_wp_error( $entry ) ) {
			$this->addon->log_error( __METHOD__ . '(): Error retrieving entry. ' . $entry->get_error_message() );
			wp_send_json_error( array( 'message' => $entry->get_error_messages() ) );
		}

		// Linking failed entries
		if ( ! rgempty( 'stripe_failed_entries' ) ) {
			$this->link_failed_entries( $entry, json_decode( rgpost( 'stripe_failed_entries' ), true ) );
		}

		// Getting fresh confirmation.
		$form = \GFAPI::get_form( $entry['form_id'] );
		$confirmation = \GFFormDisplay::handle_confirmation( $form, $entry, false );
		$response     = is_array( $confirmation ) ? array( 'confirmation_redirect' => $confirmation['redirect'] ) : array( 'confirmation_markup' => $this->get_confirmation_markup_with_styling( $form, $confirmation ) );
		wp_send_json_success( $response );
	}

	/**
	 * AJAX action to increase the rate limiting error count.
	 *
	 * @since 6.0
	 */
	public function ajax_increase_error_count() {
		check_ajax_referer( 'gfstripe_increase_error_count', 'nonce' );

		wp_send_json_success( $this->addon->get_card_error_count( true ) );
	}

	/**
	 * Links failed entries with successfull entries via entry meta and entry notes.
	 *
	 * @since 6.0
	 *
	 * @param array $entry          The successfull entry object
	 * @param array $failed_entries Array of failed entry IDs.
	 */
	private function link_failed_entries( $entry, $failed_entries ) {
		if ( ! is_array( $failed_entries ) ) {
			return;
		}
		$sanitized_failed_entries = [];
		foreach( $failed_entries as $failed_entry_id ) {

			// Make sure entry exists. Ignore entries that do not exist.
			$failed_entry_id = absint( $failed_entry_id );
			$failed_entry = \GFAPI::get_entry( $failed_entry_id );
			if ( is_wp_error( $failed_entry ) ) {
				continue;
			}
			$sanitized_failed_entries[] = $failed_entry_id;

			// Adding entry note
			$successful_entry_url = esc_url( admin_url( "admin.php?page=gf_entries&view=entry&id={$entry['form_id']}&lid={$entry['id']}" ) );
			$note = sprintf( esc_html__( 'This form was resubmitted and the payment succeeded.  %sView successful entry%s.', 'gravityforms' ), "<a href='{$successful_entry_url}'>", '</a>' );
			$this->addon->add_note( $failed_entry_id, $note, 'success' );

			// Adding entry meta.
			gform_add_meta( $failed_entry_id, 'stripe_successful_entry', $entry['id'] );
		}

		// Adding entry meta for successfull entry.
		gform_add_meta( $entry['id'], 'stripe_failed_entries', $sanitized_failed_entries );
	}

	/**
	 * Adds the form styling to the confirmation markup.
	 *
	 * @since 6.0
	 *
	 * @param array  $form The current form being processed.
	 * @param string $confirmation The confirmation markup.
	 *
	 * @return string
	 */
	private function get_confirmation_markup_with_styling( $form, $confirmation ) {
		$form_theme          = rgpost( 'form_theme' );
		$form_style_settings = rgpost( 'form_style_settings' );
		$form_style_settings = empty( $form_style_settings ) ? false : $form_style_settings;

		return \GFFormDisplay::get_confirmation_markup( $form, $confirmation, false, $form_style_settings, $form_theme );
	}

	/**
	 * Adds a confirmation to handle the timeout status.
	 *
	 * @since 6.0
	 *
	 * @param string   $feed_id  The ID of the feed which was saved.
	 * @param int      $form_id  The current form ID associated with the feed.
	 * @param array    $settings An array containing the settings and mappings for the feed.
	 * @param \GFAddOn $addon    The addon class.
	 */
	public function maybe_add_timeout_confirmation( $feed_id, $form_id, $settings, $addon ) {
		$form = \GFAPI::get_form( $form_id );
		$feed = \GFAPI::get_feed( $feed_id );

		// If this is not a Stripe feed, abort.
		if ( $feed['addon_slug'] !== $this->addon->get_slug() ) {
			return;
		}

		// If the form doesn't have a Stripe Card element field, abort.
		if ( ! $this->addon->has_stripe_card_element( $form ) ) {
			return;
		}

		$confirmations = rgar( $form, 'confirmations' );
		foreach ( $confirmations as $key => $confirmation ) {

			// If there is already a confirmation with conditional logic based on payment status == "Processing", no need to create a new one. Abort.
			$rule = rgars( $confirmation, 'conditionalLogic/rules/0' );
			if ( $rule && $rule['fieldId'] === 'payment_status' && $rule['operator'] === 'is' && $rule['value'] === 'Processing' ) {
				return;
			}
		}

		$timeout_confirmation = $this->get_default_timeout_confirmation( $form, $feed );

		$form['confirmations'][ $timeout_confirmation['id'] ] = $timeout_confirmation;
		$result = \GFFormsModel::save_form_confirmations( $form['id'], $form['confirmations'] );
	}


	/**
	 * Gets the default timeout confirmation object.
	 *
	 * @since 6.0
	 *
	 * @param $form
	 * @param $feed
	 * @return mixed|null
	 */
	private function get_default_timeout_confirmation( $form, $feed ) {
		$conditional_logic    = array(
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => array(
				array(
					'fieldId'  => 'payment_status',
					'operator' => 'is',
					'value'    => 'Processing',
				),
			),
		);
		$timeout_confirmation = array(
			'id'                                    => uniqid(),
			'name'                                  => esc_html__( 'Timeout Confirmation', 'gravityfromsstripe' ),
			'type'                                  => 'message',
			'message'                               => esc_html__( 'The process of completing your payment is taking longer than expected. Contact us if you have any questions or concerns.', 'gravityformsstripe' ),
			'disableAutoformat'                     => false,
			'page'                                  => '',
			'url'                                   => '',
			'queryString'                           => '',
			'confirmation_conditional_logic_object' => $conditional_logic,
			'confirmation_conditional_logic'        => '1',
			'conditionalLogic'                      => $conditional_logic,
		);

		/**
		 * Filters the default timeout confirmation properties.
		 *
		 * @since
		 *
		 * @param array $timeout_confirmation The timeout confirmation object.
		 * @param array $form                 The current form being processed.
		 * @param array $feed                 The current feed being processed.
		 */
		return apply_filters( 'gform_stripe_default_timeout_confirmation', $timeout_confirmation, $form, $feed );
	}

	/**
	 * Mark an entry as "Processing" and update the transaction ID and type.
	 *
	 * @since 6.0
	 *
	 * @param array  $entry            The entry to be marked as processing.
	 * @param string $transaction_id   The transaction ID.
	 * @param int    $transaction_type The transaction type. 1 = product, 2 = subscription.
	 */
	private function mark_entry_processing( $entry, $transaction_id, $transaction_type ) {

		$entry['payment_status']   = 'Processing';
		$entry['transaction_id']   = $transaction_id;
		$entry['transaction_type'] = intval( $transaction_type ) === 2 ? 2 : 1; // 1 = product, 2 = subscription

		\GFAPI::update_entry( $entry );
	}

	/**
	 * Create a subscription.
	 *
	 * @since 6.0
	 *
	 * @param array $entry The entry object.
	 * @param array $feed  The feed object.
	 * @param object $api  The Stripe API object.
	 *
	 * @return \WP_Error|\Stripe\Subscription
	 */
	private function create_subscription( $entry, $feed, $api ) {

		$subscription_data = $this->get_subscription_data( $entry, $feed, $api );

		if ( ! $subscription_data ) {
			return new \WP_Error( 'gf_stripe_subscription_payment_amount_error', esc_html__( 'Error retrieving subscription data.', 'gravityformsstripe' ) );
		}

		$customer_id = $this->get_stripe_customer_id( $subscription_data, $entry, $feed, $api );

		if ( ! $customer_id ) {
			return new \WP_Error( 'gf_stripe_subscription_customer_id_error', esc_html__( 'Error retrieving customer ID.', 'gravityformsstripe' ) );
		}

		// Adding setup fee if configured in feed.
		if ( $subscription_data['setup_fee'] ) {
			// Adding invoice item for setup fee.
			$invoice = $api->add_invoice_item( array(
				'customer'   => $customer_id,
				'price_data' => array(
					'currency'    => $entry['currency'],
					'unit_amount' => $subscription_data['setup_fee'],
					'product'     => $this->get_stripe_product( esc_html__( 'Setup fee for: ', 'gravityforms' ) . $subscription_data['subscription_name'], $api )->id,
				),
			) );
		}

		// Retrieving the product matching the subscription name configured in the feed. Or create a new one if not found.
		$product = $this->get_stripe_product( $subscription_data['subscription_name'], $api );

		if ( ! $product ) {
			return new \WP_Error( 'gf_stripe_subscription_product_error', esc_html__( 'Error retrieving product.', 'gravityformsstripe' ) );
		}

		// Subscription creation arguments.
		$sub_args = array(
			'customer' => $customer_id,
			'items'    => array(
				array(
					'price_data' => array(
						'currency'    => $entry['currency'],
						'product'     => $product->id,
						'unit_amount' => $subscription_data['payment_amount'],
						'recurring'   => array(
							'interval'       => $subscription_data['recurring_interval'],
							'interval_count' => $subscription_data['recurring_interval_count'],
						),
					),
				),
			),
			'metadata'         => $subscription_data['metadata'],
			'payment_behavior' => 'default_incomplete',
			'payment_settings' => array( 'save_default_payment_method' => 'on_subscription' ),
			'expand'           => array( 'latest_invoice.payment_intent', 'pending_setup_intent' ),
		);

		// Adding coupon if configured in feed.
		if ( $subscription_data['customer_coupon'] ) {
			$sub_args['coupon'] = $subscription_data['customer_coupon'];
		}

		// Adding trial if configured in feed.
		if ( $subscription_data['trial_days'] ) {
			$sub_args['trial_period_days'] = $subscription_data['trial_days'];
		}

		/**
		 * Filters the arguments used to create a Stripe subscription.
		 *
		 * @since 6.0
		 *
		 * @param array   $subscription_data Subscription data compiled from the entry and feed objects. It has subscription related data in an easy to consume format.
		 * @param array   $entry             The current entry.
		 * @param array   $feed              The current feed.
		 * @param int     $customer_id       The Stripe customer ID that will be associated with the subscription.
		 * @param Product $product           The Stripe product object that will be associated with the subscription.
		 */
		$sub_args = apply_filters( 'gform_stripe_elements_subscription_args', $sub_args, $subscription_data, $entry, $feed, $customer_id, $product );

		// Running through legacy subscription filter
		if ( has_filter( 'gform_stripe_subscription_params_pre_update_customer' ) ) {
			_deprecated_hook( 'gform_stripe_subscription_params_pre_update_customer', '6.0', 'gform_stripe_elements_subscription_args' );

			$customer          = $api->get_customer( $customer_id );
			$plan              = array();
			$form              = \GFAPI::get_form( $entry['form_id'] );
			$trial_period_days = (string) $subscription_data[ 'trial_days' ];
			$originial_items   = $sub_args['items'];

			/**
			 * Allow the subscription parameters to be overridden before the customer is subscribed to the plan.
			 * @deprecated 6.0
			 *
			 * @since 2.3.4
			 * @since 2.5.2 Added the $trial_period_days param.
			 * @since 6.0   $plan no longer applies and is now an empty array().
			 *
			 * @param array            $subscription_params The subscription parameters.
			 * @param \Stripe\Customer $customer            The Stripe customer object.
			 * @param \Stripe\Plan     $plan                The Stripe plan object. This is no longer supported for Stripe Element. An empty array will be pass as this argument.
			 * @param array            $feed                The feed currently being processed.
			 * @param array            $entry               The entry currently being processed.
			 * @param array            $form                The form which created the current entry.
			 * @param int              $trial_period_days   The number of days the trial should last.
			 */
			$sub_args = apply_filters( 'gform_stripe_subscription_params_pre_update_customer', $sub_args, $customer, $plan, $feed, $entry, $form, $trial_period_days );

			// Ensure plan is not set via this filter.
			if ( isset( $sub_args['plan'] ) ) {
				_doing_it_wrong( 'gform_stripe_subscription_params_pre_update_customer', 'Setting a subscription plan property is no longer supported when creating a subscription. To change subscription details, use the items.price_data property. Refer to the following Stripe API document for more information: https://docs.stripe.com/api/subscriptions/create', '6.0' );
				unset( $sub_args['plan'] );
			}

			// Ensure items.plan is not set via this filter.
			if ( isset( $sub_args['items'][0]['plan'] ) ) {
				_doing_it_wrong( 'gform_stripe_subscription_params_pre_update_customer', 'Setting a subscription items.plan property is no longer supported when creating a subscription. To change subscription details, use the items.price_data property. Refer to the following Stripe API document for more information: https://docs.stripe.com/api/subscriptions/create', '6.0' );
				$sub_args['items'] = $originial_items; //restoring original items array
			}
		}

		$this->addon->log_debug( __METHOD__ . '(): Creating subscription for form id: "' . $entry['form_id'] . ', feed : "' . rgars( $feed, 'meta/feedName' ) . ', entry id: ' . $entry['id'] . ' => ' . print_r( $sub_args, true ) );

		return $api->create_subscription( $sub_args );
	}

	/**
	 * Get subscription data.
	 *
	 * @since 6.0
	 *
	 * @param array $entry The entry object.
	 * @param array $feed  The feed object.
	 * @param object $api  The Stripe API object.
	 *
	 * @return array
	 */
	private function get_subscription_data( $entry, $feed, $api ) {

		$form            = \GFAPI::get_form( $entry['form_id'] );
		$coupon_field_id = rgars( $feed, 'meta/customerInformation_coupon' );

		if ( ! $form ) {
			return false;
		}

		// Order data with billing information.
		$subscription_data = $this->addon->get_submission_data( $feed, $form, $entry );

		// Calculate subscription recurring amount. When the coupon is mapped to a coupon field, remove the discount from the payment amount to avoid double discounting.
		$subscription_data['payment_amount'] = $this->calculate_subscription_payment( $form, $coupon_field_id, $subscription_data );

		// Adding customer information
		$subscription_data['customer_email']       = $this->addon->get_field_value( $form, $entry, rgars( $feed, 'meta/customerInformation_email' ) );
		$subscription_data['customer_name']        = $this->addon->get_field_value( $form, $entry, rgars( $feed, 'meta/customerInformation_name' ) );
		$subscription_data['customer_description'] = $this->addon->get_field_value( $form, $entry, rgars( $feed, 'meta/customerInformation_description' ) );
		$subscription_data['customer_coupon']      = $this->get_stripe_coupon( $form, $entry, $coupon_field_id, $api );

		// Adding other feed settings
		$subscription_data['recurring_interval']       = rgars( $feed, 'meta/billingCycle_unit' );
		$subscription_data['recurring_interval_count'] = rgars( $feed, 'meta/billingCycle_length' );
		$subscription_data['trial_days']               = rgars( $feed, 'meta/trial_enabled' ) ? intval( rgars( $feed, 'meta/trialPeriod' ) ) : 0;
		$subscription_data['setup_fee']                = rgars( $subscription_data, 'setup_fee' ) ? floatval( rgars( $subscription_data, 'setup_fee' ) ) * 100 : 0;
		$subscription_data['subscription_name']        = rgars( $feed, 'meta/subscription_name' ) ? \GFCommon::replace_variables( rgars( $feed, 'meta/subscription_name' ), $form, $entry, false, false, false, 'text' ) : $form['title'];

		// Adding metadata
		$subscription_data['metadata']                = $this->addon->get_stripe_meta_data( $feed, $entry, $form );
		$subscription_data['metadata']['gf_entry_id'] = $entry['id'];

		return $subscription_data;
	}

	/**
	 * Get the Stripe product.
	 *
	 * @since 6.0
	 *
	 * @param string $product_name The product name.
	 * @param object $api          The Stripe API object.
	 *
	 * @return \Stripe\Product
	 */
	private function get_stripe_product( $product_name, $api ) {

		$sanitized_product_name = sanitize_title_with_dashes( $product_name );
		$products               = $api->search_products_by_metadata( 'gf_product_name', $sanitized_product_name, 1 );
		$this->addon->log_debug( __METHOD__ . '(): Searching for product with metadata "gf_product_name" and value "' . $sanitized_product_name );

		if ( is_wp_error( $products ) || empty( $products ) ) {
			$product = $api->create_product( array(
				'name'     => $product_name,
				'metadata' => array(
					'gf_product_name' => $sanitized_product_name,
				),
			) );
			$this->addon->log_debug( __METHOD__ . '(): Product not found. Creating new product with name: ' . $product_name . '. Result: ' . print_r( $product, true ) );
			return $product;
		}

		return $products[0];
	}

	/**
	 * Get the Stripe customer ID.
	 *
	 * @since 6.0
	 *
	 * @param array          $subscription_data The subscription data.
	 * @param array          $entry            The entry object.
	 * @param array          $feed             The feed object.
	 * @param \GF_Stripe_API $api             The Stripe API object.
	 *
	 * @return string
	 */
	private function get_stripe_customer_id( $subscription_data, $entry, $feed, $api ) {

		$form = \GFAPI::get_form( $entry['form_id'] );

		/**
		 * Allow an existing customer ID to be specified for use when processing the submission.
		 *
		 * @since  2.1.0
		 * @access public
		 *
		 * @param string $customer_id       The identifier of the customer to be retrieved. Default is empty string.
		 * @param array  $feed              The feed currently being processed.
		 * @param array  $entry             The entry currently being processed.
		 * @param array  $form              The form which created the current entry.
		 * @param array  $subscription_data The subscription data for the current entry, containing the customer email, name, description, and coupon as well as all other subscription related information.
		 */
		$customer_id = apply_filters( 'gform_stripe_customer_id', '', $feed, $entry, $form, $subscription_data );

		if ( ! $customer_id ) {
			$customer    = $api->create_customer(
				array(
					'email'       => $subscription_data['customer_email'],
					'name'        => $subscription_data['customer_name'],
					'description' => $subscription_data['customer_description'],
					'metadata'    => $subscription_data['metadata'],
				)
			);
			$customer_id = $customer->id;
		} else {
			$customer = $api->get_customer( $customer_id );
		}

		$this->after_create_customer( $customer, $feed, $entry, $form );

		return $customer_id;
	}

	/**
	 * Run action hook after a customer is created.
	 *
	 * @since 6.0
	 *
	 * @param Stripe\Customer $customer The customer object.
	 * @param array           $feed     The feed object.
	 * @param array           $entry    The entry object.
	 * @param array           $form     The form object.
	 */
	private function after_create_customer( $customer, $feed, $entry, $form ) {
		if ( has_filter( 'gform_stripe_customer_after_create' ) ) {
			// Log that filter will be executed.
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_customer_after_create.' );

			/**
			 * Allow custom actions to be performed between the customer being created and subscribed to the plan.
			 *
			 * @since 2.0.1
			 *
			 * @param Stripe\Customer $customer The Stripe customer object.
			 * @param array           $feed     The feed currently being processed.
			 * @param array           $entry    The entry currently being processed.
			 * @param array           $form     The form currently being processed.
			 */
			do_action( 'gform_stripe_customer_after_create', $customer, $feed, $entry, $form );
		}
	}

	/**
	 * Handle the validation of the coupon field.
	 *
	 * @since 6.0
	 *
	 * @param array $result The validation result.
	 * @param mixed $value  The field value.
	 * @param array $form   The form object.
	 * @param array $field  The field object.
	 *
	 * @return array
	 */
	public function handle_gform_field_validation( $result, $value, $form, $field ) {

		static $feeds = array();
		if ( ! isset( $feeds[ $form['id'] ] ) ) {
			$feeds[ $form['id'] ] = $this->addon->get_payment_feed( array(), $form );
		}
		$feed = $feeds[ $form['id'] ];

		// If the feed is not a subscription feed, no need to validate the coupon.
		if ( rgars( $feed, 'meta/transactionType') !== 'subscription' ) {
			return $result;
		}

		$coupon_field_id = rgars( $feed, 'meta/customerInformation_coupon' );
		if ( intval( $field->id ) === intval( $coupon_field_id ) ) {
			$api    = $this->get_api( $feed );
			$result = $this->validate_coupon( $result, $form, $field->id, $api );
		}

		return $result;
	}

	/**
	 * Hydrate the frontend feed with the publishable key.
	 *
	 * @since 6.0
	 *
	 * @param array $frontend_feed The frontend feed object.
	 * @param array $form          The form object.
	 * @param array $feed          The feed object.
	 *
	 * @return array Returns the fronedend feed object with the publishable key added.
	 */
	public function hydrate_frontend_feed( $frontend_feed, $form, $feed ) {
		$feed_api_mode   = rgar( $feed['meta'], 'api_mode' );
		$publishable_key = $feed_api_mode ? rgar( $feed['meta'], "{$feed_api_mode}_publishable_key" ) : false;
		if ( $publishable_key ) {
			$frontend_feed['publishableKey'] = $publishable_key;
		}

		return $frontend_feed;
	}

	/**
	 * Return the description to be used with the Stripe Payment Intent.
	 *
	 * @since  6.0
	 *
	 * @param array $entry           The entry object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $feed            The feed object currently being processed.
	 *
	 * @return string
	 */
	private function get_payment_description( $entry, $submission_data, $feed ) {

		// Charge description format:
		// Entry ID: 123, Products: Product A, Product B, Product C

		$strings = array();

		if ( $entry['id'] ) {
			$strings['entry_id'] = sprintf( esc_html__( 'Entry ID: %d', 'gravityformsstripe' ), $entry['id'] );
		}

		$strings['products'] = sprintf(
			_n( 'Product: %s', 'Products: %s', count( $submission_data['line_items'] ), 'gravityformsstripe' ),
			implode( ', ', wp_list_pluck( $submission_data['line_items'], 'name' ) )
		);

		$description = implode( ', ', $strings );

		/**
		 * Allow the charge description to be overridden.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description     The charge description.
		 * @param array  $strings         Contains the Entry ID and Products. The array which was imploded to create the description.
		 * @param array  $entry           The entry object currently being processed.
		 * @param array  $submission_data The customer and transaction data.
		 * @param array  $feed            The feed object currently being processed.
		 */
		return apply_filters( 'gform_stripe_charge_description', $description, $strings, $entry, $submission_data, $feed );
	}

	// ------------------------------------------
	// Coupon logic
	//-------------------------------------------


	/**
	 * Validate the coupon field.
	 *
	 * @since 6.0
	 *
	 * @param array $result The validation result.
	 * @param array $form   The form object.
	 * @param array $field  The field object.
	 *
	 * @return array
	 */
	private function validate_coupon( $result, $form, $coupon_field_id, $api ) {
		$entry       = \GFFormsModel::create_lead( $form );
		$coupon_code = $this->get_coupon_code( $form, $entry, $coupon_field_id );
		if ( ! $coupon_code ) {
			return $result;
		}

		$coupon = $api->get_coupon( $coupon_code );

		if ( ! $coupon || is_wp_error( $coupon ) ) {
			$result['is_valid'] = false;
			$result['message']  = esc_html__( 'Invalid coupon code.', 'gravityformsstripe' );
		}

		return $result;
	}

	/**
	 * Calculate the subscription payment amount with a coupon.
	 *
	 * @since 6.0
	 *
	 * @param array $form            The form object.
	 * @param int   $coupon_field_id The coupon field ID.
	 * @param array $submission_data The submission data.
	 *
	 * @return int
	 */
	private function calculate_subscription_payment( $form, $coupon_field_id, $submission_data ) {
		return $this->is_coupon_field( $form, $coupon_field_id ) ? $this->revert_payment_discounts( $submission_data ) : intval( $submission_data['payment_amount'] * 100 );
	}

	/**
	 * Revert the payment discounts.
	 *
	 * @since 6.0
	 *
	 * @param array $submission_data The submission data.
	 *
	 * @return int
	 */
	private function revert_payment_discounts( $submission_data ) {

		// Revert any discounts that have been applied to the payment amount.
		$payment_amount = $submission_data['payment_amount'];
		foreach ( $submission_data['discounts'] as $discount_data ) {
			$payment_amount -= $discount_data['unit_price'];
		}
		return intval( $payment_amount * 100 );
	}

	/**
	 * Get the Stripe coupon.
	 *
	 * @since 6.0
	 *
	 * @param object $form            The form object.
	 * @param object $entry           The entry object.
	 * @param int   $coupon_field_id The coupon field ID.
	 * @param object $api            The Stripe API object.
	 *
	 * @return \Stripe\Coupon|null
	 */
	private function get_stripe_coupon( $form, $entry, $coupon_field_id, $api ) {
		$coupon_code = $this->get_coupon_code( $form, $entry, $coupon_field_id );
		$coupon      = $api->get_coupon( $coupon_code );
		return $coupon && ! is_wp_error( $coupon ) ? $coupon : null;
	}

	/**
	 * Get the coupon code from the Gravity Forms Coupon Add-On.
	 *
	 * @since 6.0
	 *
	 * @param object $form            The form object.
	 * @param object $entry           The entry object.
	 * @param int   $coupon_field_id The coupon field ID.
	 *
	 * @return string
	 */
	private function get_coupon_code( $form, $entry, $coupon_field_id ) {

		// When coupon is mapped to a text field, simply return the value.
		if ( ! $this->is_coupon_field( $form, $coupon_field_id ) ) {
			return $this->addon->get_field_value( $form, $entry, $coupon_field_id );
		}

		// Stripe's coupons are case-sensitive and the Coupon field sets the code do upper case when the form is submitted.
		// We need to get the coupon code in its original case so that it matches the coupon code in Stripe.
		$coupon_code = $this->addon->maybe_override_field_value( rgar( $entry, $coupon_field_id ), $form, $entry, $coupon_field_id );
		$coupon = gf_coupons()->get_config( $form, $coupon_code );
		return $coupon ? rgars( $coupon, 'meta/couponCode' ) : $coupon_code;
	}

	/**
	 * Check if the field is a coupon field.
	 *
	 * @since 6.0
	 *
	 * @param object $form     The form object.
	 * @param int    $field_id The field ID.
	 *
	 * @return bool
	 */
	private function is_coupon_field( $form, $field_id ) {
		$field = \GFFormsModel::get_field( $form, $field_id );
		return $field && $field->type === 'coupon';
	}

	// ------------------------------------------
}
