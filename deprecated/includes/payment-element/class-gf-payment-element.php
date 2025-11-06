<?php

/**
 * Gravity Forms Stripe Payment Element Integration handler.
 *
 * This class acts as a wrapper for all the logic required to integrate the Stripe Payment Element.
 *
 * @see https://stripe.com/docs/payments/payment-element
 *
 * @since     5.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2021, Rocketgenius
 */
class GF_Stripe_Payment_Element {
	/**
	 * Instance of a GFStripe object.
	 *
	 * @since 5.0
	 *
	 * @var GFStripe
	 */
	protected $addon;

	/**
	 * The Payment element intent manager.
	 *
	 * @since 5.0
	 *
	 * @var GF_Payment_Element_Payment
	 */
	protected $payment;

	/**
	 * The payment element submission manager.
	 *
	 * @since 5.0
	 *
	 * @var GF_Payment_Element_Submission
	 */
	protected $submission;

	/**
	 * GF_Stripe_Payment_Elements constructor.
	 *
	 * @since 5.0
	 *
	 * @param GFStripe $addon Instance of a GFStripe object.
	 */
	public function __construct( $addon ) {

		$this->addon = $addon;

		$this->payment    = new GF_Payment_Element_Payment( $addon );
		$this->submission = new GF_Payment_Element_Submission( $addon );

		// Disable recaptcha v3 if an older version of the recaptcha add-on is installed.
		add_filter( 'gform_form_post_get_meta', array( $this, 'maybe_disable_recaptcha_v3' ), 10 );
	}

	/**
	 * Disable recaptcha v3 if an older version of the recaptcha add-on is installed (which isn't compatible with this version of Stripe)
	 *
	 * @since 5.5
	 *
	 * @filter gform_validation
	 *
	 * @param array $form The form object.
	 *
	 * @return array Returns the submission data, with the form object updated to disable recaptcha v3 if needed.
	 */
	public function maybe_disable_recaptcha_v3( $form ) {
		$is_older_recaptcha = function_exists( 'gf_recaptcha' ) && version_compare( gf_recaptcha()->get_version(), '1.3.2', '<' );
		if ( $is_older_recaptcha ) {
			$form['gravityformsrecaptcha'] = array( 'disable-recaptchav3' => '1' );
		}
		return $form;
	}

	/**
	 * Initializes an API object using the settings found in the feed or the general settings if no feeds found or if no settings were saved in the feed.
	 *
	 * Stripe has a feature that allows admins to connect to a different stripe account on feed level.
	 *
	 * @since 5.0
	 *
	 * @param array $feed_id The feed ID to get API settings from.
	 *
	 * @return GF_Stripe_API
	 */
	private function get_api_for_feed( $feed_id = null ) {

		$feed = $this->addon->get_feed( $feed_id );

		if ( ! empty( $feed ) && $this->addon->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$api = $this->addon->include_stripe_api( $this->addon->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$api = $this->addon->include_stripe_api();
		}

		return $api;
	}

	/**
	 * Gets the initial payment properties depending on the feed.
	 *
	 * @since 5.1
	 *
	 * @param array $feed The current feed object being processed.
	 * @param arrar $form The current form object being processed.
	 *
	 * @return \Stripe\SetupIntent|\Stripe\PaymentIntent|WP_Error
	 */
	public function get_initial_payment_information( $feed, $form ) {
		$currency           = strtolower( GFCommon::get_currency() );
		$min_initial_amount = $currency === 'usd' ? 1 : $this->get_minimum_amount( $currency );
		$intent_information = array(
			// Instead of 1 we can calculate the minimum amount that can be charged on page load without the user changing anything.
			'amount'   => $this->addon->get_amount_export( $min_initial_amount ),
			'currency' => $currency,
		);

		$intent_information['mode'] = rgars( $feed, 'meta/transactionType' ) === 'product' ? 'payment' : 'subscription';

		if ( rgars( $feed, 'meta/trial_enabled' ) && ! rgars( $feed, 'meta/setupFee_enabled' ) ) {
			$intent_information['amount'] = 0;
		}

		$payment_methods = $this->payment->get_payment_methods( $feed, $form );
		if ( ! empty( $payment_methods ) ) {
			// When payment method types are explicitly defined, 'card' must be one of the methods.
			$intent_information['payment_method_types'] = array_unique( array_merge( $payment_methods, array( 'card' ) ) );
		}

		if ( $intent_information['mode'] === 'subscription' ) {
			$intent_information['setup_future_usage'] = 'off_session';
		} else {
			$intent_information['capture_method'] = $this->addon->get_payment_element_capture_method( $form, $feed );
		}

		// Forces immediate verification for ACH payments by preventing customer from being able to manually enter their bank account details.
		$intent_information['payment_method_options'] = array(
			'us_bank_account' => array( 'verification_method' => 'instant' ),
		);

		/**
		 * Allow the initial payment information used to render the payment element to be overridden.
		 *
		 * @param array $intent_information The initial payment information.
		 * @param array $feed               The feed object currently being processed.
		 * @param array $form               The form object currently being processed.
		 *
		 * @since 5.2
		 */
		$intent_information = apply_filters( 'gform_stripe_payment_element_initial_payment_information', $intent_information, $feed, $form );

		return $intent_information;
	}

	/**
	 * Get the minimum amount that could be used to create an intent for a given currency.
	 *
	 * @since 5.8.0
	 *
	 * @param string $currency The currency code in lowercase.
	 *
	 * @return int The minimum amount for the currency.
	 */
	public function get_minimum_amount( $currency ) {
		// Define the minimum amounts for each currency.
		$minimum_amounts = array(
			'usd' => 1,
			'gbp' => 1,
			'eur' => 1,
			'aud' => 2,
			'brl' => 5,
			'cad' => 2,
			'czk' => 22,
			'dkk' => 7,
			'hkd' => 8,
			'huf' => 365,
			'ils' => 4,
			'jpy' => 162,
			'myr' => 5,
			'mxn' => 18,
			'nzd' => 2,
			'nok' => 11,
			'php' => 59,
			'pln' => 4,
			'rub' => 90,
			'sgd' => 2,
			'zar' => 19,
			'sek' => 11,
			'chf' => 1,
			'twd' => 33,
			'thb' => 37,
		);

		// Multiply by 1.5 to protect from any unpredicted changes in currency exchange rates.
		// If currency is not found (maybe using a currency addon, return 250 as a fallback, this value provides a reasonable threshold to protect from low value currencies.
		return rgar( $minimum_amounts, strtolower( $currency ), 250 ) * 1.5;
	}

	/**
	 * Checks whether link is supported as a payment method.
	 *
	 * @since 5.0
	 *
	 * @param \Stripe\PaymentIntent $intent The payment intent that will be used to initiate the payment.
	 *
	 * @return bool
	 */
	public function is_link_enabled( $intent ) {
		return GFCommon::get_currency() === 'USD' && in_array( 'link', $intent->payment_method_types );
	}

	/**
	 * Validates the nonce for the "Require user to be logged in" form setting.
	 *
	 * The check performed by GFFormDisplay::process_form() is disabled via filter in GF_Payment_Element_Submission::process_submission().
	 *
	 * @since 5.3
	 *
	 * @param int $form_id The ID of the form being processed.
	 *
	 * @return void
	 */
	private function check_form_requires_login_nonce( $form_id ) {
		if ( ! method_exists( 'GFCommon', 'form_requires_login' ) ) {
			return;
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! $form || ! GFCommon::form_requires_login( $form ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_die( -1, 401 );
		}

		check_ajax_referer( "gform_submit_{$form_id}", "_gform_submit_nonce_{$form_id}" );
	}

	/**
	 * Handles the AJAX call that starts the payment element checkout process.
	 *
	 * This function will validate the submission, create a draft entry and update the payment intent if submission is valid.
	 *
	 * @since 5.0
	 */
	public function start_checkout() {

		check_ajax_referer( 'gfstripe_validate_form', 'nonce' );

		$form_id = absint( rgpost( 'form_id' ) );
		$feed_id = absint( rgpost( 'feed_id' ) );
		$feed    = $this->addon->get_feed( $feed_id );
		$form    = GFAPI::get_form( $form_id );
		if ( empty( $form_id ) || empty( $feed_id ) ) {
			wp_send_json_error( 'missing required parameters', 400 );
			return;
		}

		$this->addon->log_debug( __METHOD__ . '() - Starting checkout for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) );

		$this->check_form_requires_login_nonce( $form_id );
		GFCommon::log_debug( __METHOD__ . '() - Validating form: ' . rgar( $form, 'title' ) . ' and feed: ' . rgars( $feed, 'meta/feedName' ) . ',  tracking id: ' . rgpost( 'tracking_id' ) );
		$validation_result = $this->submission->validate( $form_id );
		$is_spam           = rgar( $validation_result, 'is_spam', false );
		$is_valid          = rgar( $validation_result, 'is_valid' );
		$payment_method    = sanitize_text_field( rgpost( 'payment_method' ) );
		$payment_method    = $payment_method === 'google_pay' || $payment_method === 'apple_pay' ? 'card' : $payment_method;
		$order_data        = $this->submission->extract_order_from_submission( $feed_id, $form_id );

		if ( ! $is_valid ) {
			$this->addon->log_debug( __METHOD__ . '() - Submission is not valid, aborting submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) );
			wp_send_json_success( array( 'is_valid' => false ) );
			return;
		} elseif ( $is_spam ) {
			$this->create_draft_and_send_json_success( $form_id, $feed_id, '', '', '', true, '', $order_data['total'], null );
			return;
		}

		$order_data['payment_method'] = $payment_method;
		$subscription_id              = null;
		$api                          = $this->get_api_for_feed( $feed_id );
		$subscription                 = null;
		$intent                       = null;
		if ( rgars( $feed, 'meta/transactionType' ) === 'subscription' ) {
			$subscription = $this->payment->create_subscription( $order_data, $feed_id, $form_id, $api );
			if ( is_wp_error( $subscription ) ) {
				$this->addon->log_debug( __METHOD__ . '() - Could not create subscription, aborting submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) );
				wp_send_json_error( $subscription->get_error_message(), 400 );
				return;
			}
			$intent          = $this->payment->get_subscription_intent( $subscription, $feed, $api );
			$subscription_id = $subscription->id;

		} else {
			$intent = $this->payment->create_payment_intent( $order_data, $feed_id, $form_id, $api );
		}

		if ( is_wp_error( $intent ) ) {
				$this->addon->log_debug( __METHOD__ . '() - Could not create payment intent, aborting submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) );
			wp_send_json_error( $intent );
			return;
		}

		$client_secret = null;
		$invoice_id    = null;

		// If this is a `send_invoice` subscription, we need an invoice instead of an intent.
		if ( $subscription && $intent === null ) {
			$invoice_id                = $subscription->latest_invoice->id;
			$this->payment->invoice_id = $invoice_id;
		} else {
			$this->payment->intent_id = $intent->id;
			$client_secret            = $intent->client_secret;
		}

		if ( $subscription && $intent ) {
			// Set the created intent's future usage to off_session, since the intent is created automatically while creating the subscription, we can't set this before this point.
			$api->update_payment_intent(
				$intent->id,
				array(
					'setup_future_usage' => 'off_session',
				)
			);
		}

		$this->create_draft_and_send_json_success( $form_id, $feed_id, $client_secret, $subscription_id, $invoice_id, $is_spam, $payment_method, $order_data['total'], $intent );
	}

	/**
	 * Handles processing the form submission after user is redirected to redirect URL generated when starting the checkout process.
	 *
	 * At this point, the payment is completed successfully or is still processing, but immediately failed payments don't reach point as they are handled on the front end.
	 *
	 * @since 5.0
	 *
	 * @return false|void
	 */
	public function handle_redirect() {
		$source_redirect = rgget( 'source_redirect_slug' );

		$tracking_id = rgget( 'tracking_id' );
		$form_id     = rgget( 'form_id' );
		$feed_id     = rgget( 'feed_id' );
		$form        = GFAPI::get_form( $form_id );
		$feed        = $this->addon->get_feed( $feed_id );

		$this->addon->log_debug( __METHOD__ . '() - Handling redirect for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );

		if ( ! empty( $source_redirect ) ) {
			gf_stripe()->log_debug( __METHOD__ . '() - Request is a redirect from SCA. Ignore submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
			return false;
		}

		$resume_token = rgget( 'resume_token' );
		GFCommon::log_debug( __METHOD__ . '() - Getting draft for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id . ', resume token: ' . $resume_token );
		$draft      = GFFormsModel::get_draft_submission_values( $resume_token );
		$submission = json_decode( rgar( $draft, 'submission' ), true );

		if ( ! $submission ) {
			gf_stripe()->log_debug( __METHOD__ . '() - No submission found for resume token: ' . $resume_token . ', aborting submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
			return false;
		}
		$params = $this->decrypt_return_params( rgars( $submission, 'partial_entry/stripe_encrypted_params' ) );

		gf_stripe()->log_debug( __METHOD__ . '() - Stripe encrypted params for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id . ' :- ' . print_r( $params, true ) );
		$intent = $this->validate_redirect_intent( $params, $this->get_api_for_feed( $feed_id ) );
		if ( $intent ) {
			$this->maybe_set_link_cookie( $intent );
		} elseif ( $intent === false ) {
			return;
		}

		add_filter( 'gform_entry_id_pre_save_lead', array( $this->submission, 'get_pending_entry_id' ), 10, 2 );
		add_filter( 'gform_field_validation', array( $this->submission, 'maybe_skip_field_validation' ), 10, 4 );

		gf_stripe()->log_debug( __METHOD__ . '() - Processing submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
		// Remove this action to prevent processing the submission twice.
		remove_action( 'wp', array( 'GFForms', 'maybe_process_form' ), 9 );
		// Simulate the submission.
		$this->submission->process_submission(
			$form_id,
			$feed_id,
			$resume_token,
			$intent,
			rgar( $params, 'subscription_id' )
		);
	}

	/**
	 * Checks if the payment intent contains a valid token that we can set it as a cookie to be used later to log in the user to the link network.
	 *
	 * @param \Stripe\PaymentIntent $intent The intent to get the token from.
	 *
	 * @return void
	 */
	public function maybe_set_link_cookie( $intent ) {
		if (
			isset( $intent->payment_method )
			&& isset( $intent->payment_method->link )
			&& isset( $intent->payment_method->link->persistent_token )
			&& ! is_null( $intent->payment_method->link->persistent_token )
		) {
			setcookie(
				'gf_stripe_token',
				$intent->payment_method->link->persistent_token,
				time() + 90 * 24 * 60 * 60,
				'/',
				sanitize_text_field( wp_unslash( rgar( $_SERVER, 'SERVER_NAME' ) ) ),
				true,
				true
			);
		}
	}

	/**
	 * Starts the subscription process for a given submission and intent.
	 *
	 * @since 5.0
	 *
	 * @param array $feed            The current feed being processed.
	 * @param array $submission_data The submission data extracted from the draft entry.
	 * @param array $form            The current form being processed.
	 *
	 * @return array
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {
		$api          = $this->get_api_for_feed( rgar( $feed, 'id' ) );
		$intent_id    = $this->get_stripe_payment_object_id();
		$intent       = $this->payment->get_stripe_payment_object( $feed, $this->get_stripe_payment_object_id(), $api );
		$order        = $this->submission->extract_order_from_submission( rgar( $feed, 'id' ), rgar( $form, 'id' ), $entry );
		$subscription = $api->get_subscription( $this->get_subscription_id() );

		// If the subscription is using `send_invoice` and has a trial, the intent will be null because there wasn't anything paid until this point.
		if ( $intent === null || rgobj( $intent, 'status' ) === 'processing' ) {
			return array(
				'is_success'      => true,
				'subscription_id' => $subscription->id,
				'customer_id'     => $intent !== null ? $intent->customer : $subscription->customer,
				'amount'          => $order['total'],
				'status'          => 'pending',
				'order'           => $order,
			);
		}

		if ( $subscription && ! is_wp_error( $subscription ) ) {

			$subscription_data = array(
				'is_success'      => true,
				'subscription_id' => $subscription->id,
				'customer_id'     => $intent->customer,
				'amount'          => $order['total'],
			);

		} else {
			$subscription_data = array(
				'is_success' => false,
			);
		}

		return $subscription_data;
	}


	/**
	 * Picks up and completes the flow of an entry in a processing status.
	 *
	 * This is called from a webhook event and handles an entry created by an asynchronous payment method like a bank transfer.
	 *
	 * @since 5.0
	 *
	 * @param array $entry  The current entry being processed.
	 * @param array $action The action being performed.
	 * @param array $event  The event fired.
	 *
	 * @return array|false[]
	 */
	public function complete_processing_entry( $entry, $action, $event ) {
		$this->submission->feed_id           = gform_get_meta( $entry['id'], 'payment_element_feed_id' );
		$this->submission->payment_object_id = gform_get_meta( $entry['id'], 'payment_element_intent_id' );
		$this->submission->draft_id          = gform_get_meta( $entry['id'], 'payment_element_draft_id' );
		$this->submission->subscription_id   = gform_get_meta( $entry['id'], 'payment_element_subscription_id' );
		$this->submission->entry_id          = $entry['id'];

		$feed = $this->addon->get_feed( $this->submission->feed_id );
		if ( rgars( $feed, 'meta/transactionType' ) === 'subscription' ) {
			return $this->complete_subscription( $entry, $feed, $action, $event );
		} else {
			return $this->complete_single_purchase( $entry, $feed, $action, $event );
		}
	}

	/**
	 * Completes a pending single payment.
	 *
	 * @since 5.0
	 *
	 * @param array $entry  The entry being processed.
	 * @param array $feed   The feed being processed.
	 * @param array $action The action being performed.
	 * @param array $event  The event fired.
	 *
	 * @return array
	 */
	public function complete_single_purchase( $entry, $feed, $action, $event ) {
		$this->addon->log_debug( __METHOD__ . '() - Completing single purchase for entry ID: ' . $entry['id'] . ' and feed ID: ' . $feed['id'] );
		$action['entry_id']       = $entry['id'];
		$action['type']           = 'complete_payment';
		$action['amount']         = $this->addon->get_amount_import( rgars( $event, 'data/object/amount' ), $entry['currency'] );
		$action['transaction_id'] = rgars( $event, 'data/object/id' );

		return $action;
	}

	/**
	 * Completes a pending subscription payment.
	 *
	 * @since 5.0
	 *
	 * @param array $entry  The entry being processed.
	 * @param array $feed   The feed being processed.
	 * @param array $action The action being performed.
	 * @param array $event  The event fired.
	 *
	 * @return array
	 */
	public function complete_subscription( $entry, $feed, $action, $event ) {
		$this->addon->log_debug( __METHOD__ . '() - Completing subscription for entry ID: ' . $entry['id'] . ' and feed ID: ' . $feed['id'] );
		$api    = $this->get_api_for_feed( $feed['id'] );
		$intent = $this->payment->get_stripe_payment_object(
			$feed,
			$this->get_stripe_payment_object_id(),
			$api
		);

		$order              = gform_get_meta( $entry['id'], 'payment_element_order' );
		$action['entry_id'] = $entry['id'];

		$subscription = $api->get_subscription( $this->get_subscription_id() );

		if ( ! is_wp_error( $subscription ) && in_array( $subscription->status, array( 'active', 'trialing' ), true ) ) {
			$subscription_data = array(
				'is_success'      => true,
				'subscription_id' => $subscription->id,
				'customer_id'     => $intent->customer,
				'amount'          => $order['total'],
			);
		} else {
			$subscription_data = array(
				'is_success' => false,
			);
		}

		$action             = array_merge( $action, $subscription_data );
		$action['entry_id'] = $entry['id'];
		$action['type']     = $subscription_data['is_success'] ? 'create_subscription' : 'fail_subscription_payment';

		return $action;

	}


	/**
	 * Encrypts the sensitive payment information so it can be passed in the redirect URL.
	 *
	 * @param int         $form_id         The id of the form being processed.
	 * @param int         $feed_id         The id of the feed being processed.
	 * @param string      $intent_secret   The secret of the intent being processed.
	 * @param null|string $subscription_id The id of the subscription if this is a subscription payment.
	 * @param null|string $invoice_id      The id of an invoice if the subscription isn't charged automatically.
	 *
	 * @return string
	 */
	public function get_encrypted_return_params( $form_id, $feed_id, $intent_secret, $subscription_id = null, $invoice_id = null ) {

		$string = json_encode(
			array(
				'form_id'         => $form_id,
				'feed_id'         => $feed_id,
				'secret'          => $intent_secret,
				'subscription_id' => $subscription_id,
				'intent_id'       => $this->payment->intent_id,
				'invoice_id'      => $invoice_id
			)
		);

		$key = $this->addon->get_secret_api_key();

		return base64_encode( GFCommon::openssl_encrypt( $string, $key ) );
	}

	/**
	 * Decrypts the payment information from the return URL param.
	 *
	 * @param string $encrypted_params The encrypted payment information.
	 *
	 * @return array The decrypted payment information which include the form id, feed id, draft if and the intent secret.
	 */
	public function decrypt_return_params( $encrypted_params ) {
		$params = base64_decode( $encrypted_params );
		$key    = $this->addon->get_secret_api_key();

		return json_decode( GFCommon::openssl_decrypt( $params, $key ), true );
	}

	/**
	 * Validates that the intent id found in the URL matches the given intent secret and that its status is valid for processing.
	 *
	 * @since 5.0
	 *
	 * @param array         $parameters The decrypted payment parameters stored in the draft.
	 * @param GF_Stripe_API $api        The stripe API instance used to communicate with the stripe API.
	 *
	 * @return \Stripe\PaymentIntent|\Stripe\SetupIntent|false
	 */
	public function validate_redirect_intent( $parameters, $api ) {

		$form_id                   = rgar( $parameters, 'form_id' );
		$feed_id                   = rgar( $parameters, 'feed_id' );
		$form                      = GFAPI::get_form( $form_id );
		$feed                      = $this->addon->get_feed( $feed_id );
		$intent_id                 = rgar( $parameters, 'intent_id' );
		$invoice_id                = rgar( $parameters, 'invoice_id' );
		$this->payment->intent_id  = $intent_id;
		$this->payment->invoice_id = $invoice_id;
		$request_client_secret     = '';
		if ( empty( $intent_id ) && ! empty( $invoice_id ) ) {
			return $api->get_invoice( $invoice_id );
		} elseif ( strpos( $intent_id, 'seti' ) === 0 ) {
			$intent = $api->get_setup_intent( $intent_id, array( 'expand' => array( 'payment_method' ) ) );
			$request_client_secret = rgget( 'setup_intent_client_secret' );
		} elseif ( strpos( $intent_id, 'pi' ) === 0 ) {
			$intent = $api->get_payment_intent( $intent_id, array( 'expand' => array( 'payment_method' ) ) );
			
			if ( is_wp_error( $intent ) ) {
				gf_stripe()->log_debug( __METHOD__ . '() - Payment intent returned the following WP_Error: ' . $intent->get_error_message() );
				return false;
			}

			$request_client_secret = rgget( 'payment_intent_client_secret' );
		} else {
			gf_stripe()->log_debug( __METHOD__ . '() - Invalid intent id. Aborting tracking id:' . rgget( 'tracking_id' ) . ', for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ')' );

			return false;
		}

		// This happens when a payment method that has an intermediate step fails.
		if ( $intent->status === 'requires_payment_method' ) {
			gf_stripe()->log_debug( __METHOD__ . '() - Payment method failed after going to intermediate step, intent: ' . print_r( $intent, true ) );
			gf_stripe()->log_debug( __METHOD__ . '() - Aborting tracking id:' . rgget( 'tracking_id' ) . ', for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ')' );

			GFCache::set( 'payment_element_intent_failure', true );
			return false;
		}

		if (
			$intent->client_secret !== $request_client_secret ||
			! in_array( $intent->status, array( 'succeeded', 'processing', 'requires_capture' ) )
		) {
			gf_stripe()->log_debug( __METHOD__ . '() - Invalid intent client secret or status, Aborting tracking id:' . rgget( 'tracking_id' ) . ', for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ')' );

			return false;
		}

		return $intent;
	}

	/**
	 * Returns the id of the current payment object being processed (Payment intent ID, Setup intent ID, or invoice ID)
	 *
	 * @since 5.0
	 *
	 * @return string
	 */
	public function get_stripe_payment_object_id() {
		return $this->submission->payment_object_id;
	}

	/**
	 * Returns the current payment intent being processed.
	 *
	 * @since 5.0
	 *
	 * @return string
	 */
	public function get_subscription_id() {
		$query_subscription_id = rgget( 'subscription_id' );
		return $query_subscription_id ? $query_subscription_id : $this->submission->subscription_id;
	}

	/**
	 * Returns the current feed id being processed.
	 *
	 * @since 5.0
	 *
	 * @return int
	 */
	public function get_feed_id() {
		return $this->submission->feed_id;
	}

	/**
	 * Returns the id of the draft being used to create an entry.
	 *
	 * @since 5.0
	 *
	 * @return int
	 */
	public function get_draft_id() {
		return $this->submission->draft_id;
	}


	/**
	 * Ajax handler to retrieve a stripe coupon given a coupon code.
	 *
	 * @since 5.1
	 *
	 * @return void
	 */
	public function get_stripe_coupon() {
		check_ajax_referer( 'gfstripe_get_stripe_coupon', 'nonce' );

		$request_data = json_decode( file_get_contents( 'php://input' ), true );

		$feed_id     = absint( rgar( $request_data, 'feed_id' ) );
		$coupon_code = sanitize_text_field( rgar( $request_data, 'coupon' ) );

		$api      = $this->get_api_for_feed( $feed_id );
		$coupon   = $api->get_coupon( $coupon_code );
		$is_valid = $coupon && ! is_wp_error( $coupon ) && $coupon->valid;

		$response = array(
			'is_valid' => $is_valid,
			'amount_off' => $is_valid ? $coupon->amount_off : 0,
			'percentage_off' => $is_valid ? $coupon->percent_off: 0,
		);

		wp_send_json_success( $response );
	}

	/**
	 * Deletes a draft entry.
	 *
	 * This is called when the user quits the payment process in the middle before actually paying.
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function delete_draft_entry() {
		check_ajax_referer( 'gfstripe_delete_draft_entry', 'nonce' );

		$request_data = json_decode( file_get_contents( 'php://input' ), true );

		$draft_id = rgar( $request_data, 'draft_id' );

		wp_send_json_success( GFFormsModel::delete_draft_submission( $draft_id ) );

	}

	public function check_rate_limiting() {

		check_ajax_referer( 'gfstripe_payment_element_check_rate_limiting', 'nonce' );

		$request_data   = json_decode( file_get_contents( 'php://input' ), true );
		$increase_count = rgar( $request_data, 'increase_count' );

		wp_send_json_success(
			array(
				'error_count' => $this->addon->get_card_error_count( $increase_count ),
			)
		);
	}

	/**
	 * Updates the customer information field in the feed settings.
	 *
	 * @since 5.0
	 *
	 * @param array $customer_info_field The current customer information field map.
	 *
	 * @return array
	 */
	public function update_customer_info_field( $customer_info_field ) {
		// Allow customer information to appear for product feeds.
		unset( $customer_info_field['dependency']['value'] );
		$customer_info_field['dependency']['values'] = array( 'subscription', 'product' );
		// Add name field.
		$customer_info_field['field_map'][] = array(
			'name'       => 'name',
			'label'      => esc_html__( 'Name', 'gravityformsstripe' ),
			'field_type' => array( 'name', 'text' ),
			'tooltip'    => '<h6>' . esc_html__( 'Name', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'You can specify a name field and it will be sent to Stripe as the customer\'s name.', 'gravityformsstripe' ),
		);
		if ( $this->addon->get_setting( 'transactionType' ) === 'subscription' ) {
			return $customer_info_field;
		}
		// Remove coupon field.
		$customer_info_field['field_map'] = array_filter(
			$customer_info_field['field_map'],
			function( $field ) {
				return $field['name'] !== 'coupon';
			}
		);
		// Email is not required for single payments.
		foreach ( $customer_info_field['field_map'] as &$field ) {
			if ( $field['name'] === 'email' ) {
				$field['required'] = false;
			}
		}

		return $customer_info_field;
	}


	/**
	 * Creates the draft submission and sends the json success result.
	 *
	 * @since 5.5
	 *
	 * @param int    $form_id         The form ID.
	 * @param int    $feed_id         The feed ID.
	 * @param string $client_secret   The intent's client secret.
	 * @param string $subscription_id The Stripe's subscription ID (if this is a subscription feed)
	 * @param string $invoice_id      The Stripe's subscription invoice ID.
	 * @param bool   $is_spam         Whether the submission is spam or not.
	 * @param string $payment_method  The payment method.
	 * @param float  $total           The payment amount.
	 * @param object $intent          The Stripe's payment itent.
	 *
	 * @return void
	 */
	private function create_draft_and_send_json_success( $form_id, $feed_id, $client_secret, $subscription_id, $invoice_id, $is_spam, $payment_method, $total, $intent ) {

		$stripe_encrypted_params = $this->get_encrypted_return_params(
			$form_id,
			$feed_id,
			$client_secret,
			$subscription_id,
			$invoice_id
		);

		$resume_token = $this->submission->create_draft_submission( $form_id, $stripe_encrypted_params );

		$confirm_data = array(
			'is_valid'       => true,
			'is_spam'        => $is_spam,
			'resume_token'   => $resume_token,
			'payment_method' => $payment_method,
			'tracking_id'    => rgpost( 'tracking_id' ),
		);

		if ( $client_secret === null && $invoice_id ) {
			$confirm_data['invoice_id'] = $invoice_id;
			$confirm_data['total']      = $total;
		} else {
			$confirm_data['intent'] = $intent;
			$confirm_data['total']  = rgar( $intent, 'amount' ) ? $this->addon->get_amount_import( $intent->amount ) : $total;
		}

		wp_send_json_success( $confirm_data );
	}

}
