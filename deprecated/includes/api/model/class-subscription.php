<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );
require_once( 'class-customer.php' );
require_once( 'class-invoice.php' );
require_once( 'class-plan.php' );

/**
 * Object representing a Subscription.
 *
 * @since 5.5.0
 */
class Subscription extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $application;

	public $cancel_at_period_end;
	public $currency;
	public $customer;
	public $discount;
	public $discounts;
	public $items;
	public $livemode;
	public $metadata;
	public $quantity;
	public $schedule;
	public $start_date;
	public $status;
	public $application_fee_percent;
	public $automatic_tax;
	public $billing_cycle_anchor_confiig;
	public $billing_cycle_anchor;
	public $billing_thresholds;
	public $cancel_at;
	public $canceled_at;
	public $cancellation_details;
	public $collection_method;
	public $current_period_end;
	public $current_period_start;
	public $days_until_due;
	public $default_payment_method;
	public $default_source;
	public $default_tax_rates;
	public $ended_at;
	public $invoice_settings;
	public $latest_invoice;
	public $on_behalf_of;
	public $next_pending_invoice_item_invoice;
	public $pause_collection;
	public $payment_settings;
	public $pending_invoice_item_interval;
	public $pending_setup_intent;
	public $pending_update;
	public $test_clock;
	public $transfer_data;
	public $trial_end;
	public $trial_start;
	public $trial_settings;


	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'subscriptions';
	}

	/**
	 * Gets the supported parameters for the update endpoint.
	 *
	 * @since 5.5.0
	 *
	 * @return array Return an array of supported parameters for the update endpoint.
	 */
	public function get_update_parameters() {

		$supported_params = array(
			'cancel_at_period_end',
			'default_payment_method',
			'description',
			'items',
			'metadata',
			'payment_behavior',
			'proration_behavior',
			'add_invoice_items',
			'application_fee_percent',
			'automatic_tax',
			'billing_cycle_anchor',
			'billing_thresholds',
			'cancel_at',
			'cancellation_details',
			'collection_method',
			'coupon',
			'days_until_due',
			'default_source',
			'default_tax_rates',
			'on_behalf_of',
			'pause_collection',
			'payment_settings',
			'pending_invoice_item_interval',
			'promotion_code',
			'proration_date',
			'transfer_data',
			'trial_end',
			'trial_from_plan',
			'trial_settings',
		);

		return $this->serialize_parameters( $supported_params );
	}

	/**
	 * Gets the nested object that should be expanded when this object is created.
	 *
	 * @since 5.5.0
	 *
	 * @return array Returns an array of nested objects that should be expanded when this object is created.
	 */
	public function get_nested_objects() {

		return array(
			'customer'       => '\Gravity_Forms_Stripe\API\Model\Customer',
			'latest_invoice' => '\Gravity_Forms_Stripe\API\Model\Invoice',
			'plan'           => '\Gravity_Forms_Stripe\API\Model\Plan',
		);
	}
}
