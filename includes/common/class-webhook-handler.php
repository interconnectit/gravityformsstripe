<?php
/**
 * Handles logic for the the Stripe Elements service.
 *
 * @package Gravity_Forms_Stripe\Elements;
 */

namespace Gravity_forms\Gravity_Forms_Stripe\Common;

/**
 * Class GF_Elements_Handler
 *
 * @since 5.8.0
 *
 * Provides functionality for handling payments made with the Elements Credit Card field.
 */
class Webhook_Handler {

	private $addon;
	private $api;

	//write a constructor
	public function __construct( $api = null, $addon = null ) {

		$this->addon = $addon ? $addon : gf_stripe();
		$this->api   = $api ? $api : $this->get_api();
	}

	/**
	 * Get the Stripe API object.
	 *
	 * @since 6.0
	 *
	 * @return \GFStripeAPI
	 */
	public function get_api() {
		$feed_id = $this->addon->get_callback_feed_id();
		if ( empty( $feed_id ) ) {
			return $this->addon->include_stripe_api();
		}

		$feed                = $this->addon->get_feed( $feed_id );
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
	 * Handle the webhook event.
	 *
	 * @since 6.0
	 *
	 * @param array $action The action to be performed.
	 * @param array $event The webhook event.
	 *
	 * @return array|\WP_Error The action to be performed or WP_Error if there was an error.
	 */
	public function handle_webhook( $action, $event ){

		$supported_events = array(
			'payment_intent.succeeded',
			'payment_intent.payment_failed',
			'charge.failed',
			'charge.succeeded',
			'charge.refunded',
			'invoice.payment_succeeded',
			'invoice.payment_failed',
			'setup_intent.setup_failed',
			'setup_intent.succeeded',
			'customer.subscription.deleted',
			'customer.subscription.updated'
		);

		// Only process supported events.
		if ( ! in_array( $event->type, $supported_events ) ) {
			return $action;
		}
		$this->addon->log_debug( __METHOD__ . '(): Handling the ' . $event->type . ' webhook event.' );

		$entry = $this->get_entry_by_event( $event );

		// If $entry is false, that means this is a webhook from an older entry. Simply bypass it so that it can be handled by the legacy webhook handler.
		if ( $entry === false ) {
			$this->addon->log_debug( __METHOD__ . '(): Could not find the entry.  This event will be handled by the legacy webhook handler.' );
			return $action;
		}

		// Could not find the entry associated with the webhook. Return the entry not found error.
		if ( is_wp_error( $entry ) ) {
			$this->addon->log_error( __METHOD__ . '(): Could not find the entry associated with the webhook event. ' . $entry->get_error_message() );
			return $this->get_webhook_error( $entry, $action, $event );
		}

		switch ( $event->type ) {

			// Handle single payments.
			case 'payment_intent.succeeded':
			case 'payment_intent.payment_failed':
				$payment_intent = rgars( $event, 'data/object' );
				$action += array(
					'type'             => $event->type == 'payment_intent.succeeded' ? 'complete_payment' : 'fail_payment',
					'amount'           => $this->addon->get_amount_import( $payment_intent->amount, strtoupper( $payment_intent->currency ) ),
					'transaction_id'   => $payment_intent->id,
					'entry_id'         => $entry['id'],
				);

				break;

			// Handle single payments with authorize only enabled.
			// Also update the payment details.
			case 'charge.succeeded' :
			case 'charge.failed' :
				$charge = rgars( $event, 'data/object' );
				$is_authorization = $charge->status === 'succeeded' && ! $charge->captured;
				if ( $is_authorization ) {

					$action += array(
						'amount'         => $this->addon->get_amount_import( $charge->amount, strtoupper( $charge->currency ) ),
						'transaction_id' => $charge->payment_intent,
					);

					// Completing the authorization.
					$this->addon->complete_authorization( $entry, $action );

					// Abort the callback since we have already processed the authorization.
					$action['abort_callback'] = true;
				}

				// Update the entry with the payment information.
				$this->update_payment_details( $entry, rgars( $charge, 'payment_method_details' ) );

				break;

			// Handle refunds
			case 'charge.refunded' :
				$charge = rgars( $event, 'data/object' );
				if ( $charge->status === 'succeeded' ) {
					$action += array(
						'type'           => 'refund_payment',
						'amount'         => $this->addon->get_amount_import( $charge->amount_refunded, strtoupper( $charge->currency ) ),
						'transaction_id' => $charge->payment_intent,
						'entry_id'       => $entry['id'],
					);
				}
				break;

			// Handle subscription payments.
			case 'invoice.payment_succeeded':
			case 'invoice.payment_failed':
				$invoice = rgars( $event, 'data/object' );

				// Ignore invoices with a zero amount due. This is likely a $0 invoice for a trial period or a payment that is below Stripe's minimum payment amount.
				if ( $invoice->amount_due === 0 ){
					$action['abort_callback'] = true;
					return $action;
				}

				$action += array(
					'type'            => $event->type == 'invoice.payment_succeeded' ? 'add_subscription_payment' : 'fail_subscription_payment',
					'subscription_id' => rgars( $invoice, 'parent/subscription_details/subscription' ),
					'amount'          => $this->addon->get_amount_import( $invoice->amount_due, strtoupper( $invoice->currency ) ),
					'transaction_id'  => $invoice->payment_intent,
					'entry_id'        => $entry['id'],
				);

				// Getting charge object associated with the invoice.
				$payment_method = $this->get_payment_method( $invoice );

				if ( ! $payment_method || is_wp_error( $payment_method ) ) {
					$this->addon->log_error( __METHOD__ . '(): Unable to update entry payment information. Could not retrieve invoice payment method. ' . print_r( $payment_method, true ) );
				} else {
					$this->update_payment_details( $entry, $payment_method );
				}

				break;

			// Handle subscription with trial enabled.
			case 'setup_intent.setup_failed':
				$this->addon->log_error( __METHOD__ . '(): Setup intent setup failed. This is likely due to a failed card authorization for future subscription payments for entry ID:' . rgar( $entry, 'id' ) );
				$action += array(
					'type'            => 'fail_subscription_payment',
					'subscription_id' => rgars( $event, 'data/object/metadata/gf_parent_subscription_id' ),
					'entry_id'        => $entry['id'],
					'note'            => esc_html__( 'Failed to authorize card for future subscription payments.', 'gravityformsstripe' ),
				);

				// Updating the entry with the payment information.
				$this->update_payment_details( $entry, rgars( $event, 'data/object/last_setup_error/payment_method' ) );

				break;

			// Handles updating the payment information of subscriptions with trial enabled.
			case 'setup_intent.succeeded':

				// Updating the entry with the payment information.
				$this->update_payment_details( $entry, rgars( $event, 'data/object/payment_method' ) );

				// Only need to update the Card field with payment information. Abort the callback so that nothing else is processed.
				$action['abort_callback'] = true;
				break;

			// Handle subscription cancellations.
			case 'customer.subscription.deleted':
				$action += array(
					'type'     => 'cancel_subscription',
					'entry_id' => $entry['id'],
				);
				break;

			// Subscriptions in Stripe are created without payment information. When payment is confirmed, the subscriptions is updated to active.
			// Also, when a subscription has a free trial, it is created with a status of trialing. When the trial ends, the subscription is updated to active.
			// This event determines when a subscription has been activated and payment has been confirmed. This is when we create the subscription in Gravity Forms.
			// This is also used to determine when a subscription has been canceled.
			case 'customer.subscription.updated':
  				$subscription    = rgars( $event, 'data/object' );

				$has_activated          = $this->subscription_status_updated_to( 'active', $event );
				$has_canceled           = $this->subscription_status_updated_to( 'canceled', $event );
				$has_started_paid_trial = $this->subscription_status_updated_to( 'trialing', $event );
				$has_confirmed_payment  = $subscription->status = 'active' && empty( $subscription->pending_setup_intent ) && ! empty( rgars( $event, 'data/previous_attributes/pending_setup_intent' ) );

				if ( $has_activated || $has_started_paid_trial || $has_confirmed_payment ) {
					$action += array(
						'type'            => 'create_subscription',
						'subscription_id' => $subscription->id,
						'amount'          => $this->get_recurring_amount( $subscription->id ),
						'entry_id'        => $entry['id'],
					);
				} elseif ( $has_canceled ) {
					$action += array(
						'type'     => 'cancel_subscription',
						'entry_id' => $entry['id'],
					);
				} else {
					$action['abort_callback'] = true;
				}
				break;
		}

		// Increase the number of failed attempts for the rate limit functionality if this is a payment failure event.
		$this->maybe_increase_card_error_count( $event->type );

		return $action;
	}

	/**
	 * Get the payment method object associated with the invoice.
	 *
	 * @since 6.0
	 *
	 * @param object $invoice The invoice object.
	 *
	 * @return object|WP_Error The charge object or WP_Error if the charge is not found.
	 */
	public function get_payment_method( $invoice ) {

		// If the invoice object does not have a payments property, we need to retrieve it via the API with expanded "payments" property.
		$payments = is_array( rgar( $invoice, 'payments' ) ) ? rgar( $invoice, 'payments' ) : $this->api->get_invoice( $invoice->id )->payments;

		// Getting last $invoice payment.
		$last_payment = end( $payments );

		//does not have a charge property (invoice objects from certain webhooks don't come with the charge property set), we need to retrieve it via the API.
		if ( rgars( $last_payment, 'payment/type' ) === 'payment_intent' ) {
			$payment_intent = is_array( rgars( $last_payment, 'payment/payment_intent' ) ) ? rgars( $last_payment, 'payment/payment_intent' ) : $this->api->get_payment_intent( rgars( $last_payment, 'payment/payment_intent' ) );

			$payment_method = rgars( $payment_intent, 'last_payment_error/payment_method' ) ? rgars( $payment_intent, 'last_payment_error/payment_method' ) : rgars( $payment_intent, 'payment_method' );
			return is_string( $payment_method ) ? $this->api->get_payment_method( $payment_method ): $payment_method;

		} else {
			$charge = is_string( rgars( $last_payment, 'payment/charge' ) ) ? $this->api->get_charge( rgars( $last_payment, 'payment/charge' ) ): rgars( $last_payment, 'payment/charge' );
			return rgar( $charge, 'payment_method_details' );
		}

		return new \WP_Error( 'not_found', 'Unable to retrieve payment method from invoice', $invoice );
	}

	/**
	 * Update the entry (Stripe field data) with the payment details.
	 *
	 * @since 6.0
	 *
	 * @param array        $entry          The entry to update.
	 * @param array|string $payment_method The Stripe payment method object.
	 */
	public function update_payment_details( $entry, $payment_method ) {
		$form         = \GFAPI::get_form( $entry['form_id'] );
		$stripe_field = $this->addon->get_stripe_card_field( $form );
		if ( ! $stripe_field ) {
			// Form does not have a Stripe field. Nothing to update, abort
			return;
		}

		// Using payment method array, or getting payment method via API by payment method ID.
		$payment_method = is_string( $payment_method ) ? $this->api->get_payment_method( $payment_method ) : $payment_method;

		// If the payment method is not found, log an error and abort.
		if ( ! $payment_method || is_wp_error( $payment_method ) ) {
			$this->addon->log_error(__METHOD__ . '(): Unable to update entry payment information. Could not retrieve payment method object. ' . print_r($payment_method, true));
			return;
		}

		// Updating payment details. Only credit card is supported currently.
		$this->addon->log_debug( __METHOD__ . '(): Updating entry with payment details: ' . rgar( $entry, 'id' ) );
		\GFAPI::update_entry_field( $entry['id'], "{$stripe_field['id']}.4", rgars( $payment_method, 'card/brand' ) );
		\GFAPI::update_entry_field( $entry['id'], "{$stripe_field['id']}.1", 'XXXXXXXXXXXX' . rgars( $payment_method, 'card/last4' ) );
	}

	/**
	 * Increase the card error count if this is a failed payment.
	 *
	 * @since 6.0
	 *
	 * @param array $event_type The event type.
	 */
	public function maybe_increase_card_error_count( $event_type ) {
		$is_payment_failure = in_array( $event_type, array( 'payment_intent.payment_failed', 'invoice.payment_failed' ) );
		if ( $is_payment_failure ) {
			$this->addon->get_card_error_count( true );
		}
	}

	/**
	 * Handle delayed feeds.
	 *
	 * @since 6.0
	 *
	 * @param array $entry The entry associated with the webhook event.
	 */
	public function handle_delayed_feeds( $entry ) {
		$feed = $this->addon->get_payment_feed( $entry );

		// If there isn't a Stripe feed associated with the entry, abort.
		if ( empty( $feed ) ) {
			return;
		}

		$this->addon->trigger_payment_delayed_feeds( $entry['transaction_id'], $feed, $entry, \GFAPI::get_form( $entry['form_id'] ) );
	}

	/**
	 * Get the recurring amount for the subscription.
	 *
	 * @since 6.0
	 *
	 * @param int $subscription_id The subscription id.
	 *
	 * @return float The recurring amount.
	 */
	private function get_recurring_amount( $subscription_id ) {

		$subscription = $this->api->get_subscription( $subscription_id, array( 'expand' => array( 'plan', 'discounts' ) ) );
		$discount_amount = 0;
		foreach( $subscription->discounts as $discount ) {
			$discount_amount += $discount->coupon->amount_off ? $discount->coupon->amount_off : $subscription->plan->amount * ( $discount->coupon->percent_off / 100 );
		}

		return $this->addon->get_amount_import( $subscription->plan->amount - $discount_amount, strtoupper( $subscription->plan->currency ) );
	}

	/**
	 * Wether or not the subscription was updated to the specified status.
	 *
	 * @since 6.0
	 *
	 * @param string   $status The status to check.
	 * @param array    $event   The webhook event.
	 *
	 * @return boolean Returns true if the subscription was updated to the specified status. Returns false otherwise.
	 */
	private function subscription_status_updated_to( $status, $event ) {

		$subscription    = rgars( $event, 'data/object' );
		$previous_status = rgars( $event, 'data/previous_attributes/status' );

		return $subscription->status === $status && ! empty( $previous_status ) && $subscription->status !== $previous_status;
	}

	/**
	 * Get the entry associated with the webhook event.
	 * This is also used to determine if the webhook should be processed by this handler.
	 * 1- We need to get subscription ID associated with this event. This will be different depending on the event type.
	 *    a- For setup_intent events, we get the subscription ID from the setup intent 'gf_parent_subscription_id' metadata.
	 *    b- For invoice events, we get the subscription ID from the 'subscription' property of the invoice.
	 *    c- For subscription events, we get the subscription object.
	 * 2- We then get the entry associated with this subscription by transaction ID. The subscription ID is stored in the transaction_id field of the entry.
	 * 3- Lastly, we validate that this webhook event should be processed by this handler. This is done by checking if the subscription has the gf_entry_id meta that matches the entry ID.
	 *    Only new subscriptions created by the refactored Stripe Element will have this meta.

	 * @since 6.0
	 *
	 * @param array $event The webhook event.
	 *
	 * @return array|false The entry associated with the webhook event or false if the event should not be processed by this handler.
	 */
	private function get_entry_by_event( $event ) {

		$event_object_type = rgars( $event, 'data/object/object' );

		switch( $event_object_type ) {

			case 'charge':
				return $this->get_entry_by_payment_intent( rgars( $event, 'data/object/payment_intent' ) );

			case 'payment_intent':
				return $this->get_entry_by_payment_intent( rgars( $event, 'data/object' ) );

			case 'setup_intent':
				return $this->get_entry_by_subscription( rgars( $event, 'data/object/metadata/gf_parent_subscription_id' ) );

			case 'invoice':
				$subscription = rgars( $event, 'data/object/subscription' ) ? rgars( $event, 'data/object/subscription' ) : rgars( $event, 'data/object/parent/subscription_details/subscription' );
				return $this->get_entry_by_subscription( $subscription );

			case 'subscription':
				return $this->get_entry_by_subscription( rgars( $event, 'data/object' ) );
		}

		return false;
	}

	/**
	 * Get the entry associated with the subscription.
	 *
	 * @since 6.0
	 *
	 * @param object|string $subscription The subscription object or ID.
	 *
	 * @return array|false|\WP_Error The entry associated with the subscription, false if the subscription is not associated with an entry or WP_Error if the subscription is not found.
	 */
	private function get_entry_by_subscription( $subscription ) {

		$subscription_id = is_object( $subscription ) ? $subscription->id : $subscription;

		// Getting entry by transaction ID.
		$entry_id = $this->addon->get_entry_by_transaction_id( $subscription_id );
		if ( ! $entry_id ) {
			$this->addon->log_error( __METHOD__ . '(): Could not find the entry associated with the subscription ID: ' . $subscription_id );
			return new \WP_Error( 'not_found', '', array( 'type' => 'subscription', 'id' => $subscription_id ) );
		}
		$entry = \GFAPI::get_entry( $entry_id );

		// Getting subscription object from Stripe when needed.
		if ( ! is_object( $subscription ) ) {
			$subscription = $this->api->get_subscription( $subscription );
		}
		// Ensure subscription has the gf_entry_id meta, which means it was created by the refactored Stripe Element and should be processed by this webhook handler.
		$can_process_webhook = rgars( $subscription, 'metadata/gf_entry_id' ) === $entry_id;

		return $can_process_webhook ? $entry : false;
	}

	/**
	 * Get the entry associated with the payment intent.
	 *
	 * @since 6.0
	 *
	 * @param object|string $payment_intent The payment intent object or ID.
	 *
	 * @return array|false The entry associated with the payment intent or false if the payment intent is not associated with an entry.
	 */
	private function get_entry_by_payment_intent( $payment_intent ) {

		// Ensuring we have a payment intent object.
		$payment_intent = is_string( $payment_intent ) ? $this->api->get_payment_intent( $payment_intent ) : $payment_intent;

		// Ensure payment intent has the gf_entry_id meta.
		if ( empty( rgars( $payment_intent, 'metadata/gf_entry_id' ) ) ) {
			// The payment intent does not have the gf_entry_id meta, which means it was not created by the refactored Stripe Element and should not be processed by this webhook handler. Return false.
			return false;
		}

		$payment_intent_id = rgar( $payment_intent, 'id' );

		// Getting entry by transaction ID.
		$entry_id = $this->addon->get_entry_by_transaction_id( $payment_intent_id );
		if ( ! $entry_id ) {
			$this->addon->log_error( __METHOD__ . '(): Could not find the entry associated with the payment intent ID: ' . $payment_intent_id );
			return new \WP_Error( 'not_found', '', array( 'type' => 'transaction', 'id' => $payment_intent_id ) );
		}
		$entry = \GFAPI::get_entry( $entry_id );

		// Ensure gf_meta_id matches the entry ID. Abort if not.
		return rgars( $payment_intent, 'metadata/gf_entry_id' ) === $entry_id ? $entry : false;
	}

	/**
	 * Get the error to return when there is a webhook error (i.e. entry associated with the webhook event is not found).
	 *
	 * @since STRIPE_VERSION
	 *
	 * @param array $action The action array.
	 * @param array $event  The webhook event.
	 *
	 * @return \WP_Error The error to return.
	 */
	private function get_webhook_error( $wp_error, $action, $event ) {
		$this->addon->log_error( __METHOD__ . '(): ' . $wp_error->get_error_message() . ' for action: ' . print_r( $action, true ) . ' and event: ' . rgar( $event, 'type' ) . ' (' . rgar( $event, 'id' ) . ' )' );

		// If the error is due to an entry not being found, return the entry not found error.
		if ( $wp_error->get_error_code() === 'not_found' ) {
			$error_data = $wp_error->get_error_data();
			$action    += array( rgar( $error_data, 'type' ) . '_id' => rgar( $error_data, 'id' ) );
			return $this->addon->get_entry_not_found_wp_error( rgar( $error_data, 'type' ), $action, $event );
		}

		// If the error is not due to an entry not being found, simply return the error.
		return $wp_error;
	}
}
