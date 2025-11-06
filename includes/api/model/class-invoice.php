<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );
require_once( 'class-paymentintent.php' );
require_once( 'class-invoicepayment.php' );

/**
 * Object representing an Invoice.
 *
 * @since 5.5.0
 */
class Invoice extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $account_country;
	public $account_name;
	public $account_tax_ids;
	public $amount_due;
	public $amount_overpaid;
	public $amount_paid;
	public $amount_remaining;
	public $amount_shipping;
	public $application;
	public $application_fee_amount;
	public $attempt_count;
	public $attempted;
	public $auto_advance;
	public $automatic_tax;
	public $automatically_finalizes_at;
	public $billing;
	public $billing_reason;
	public $charge;
	public $collection_method;
	public $currency;
	public $custom_fields;
	public $customer;
	public $customer_address;
	public $customer_email;
	public $customer_name;
	public $customer_phone;
	public $customer_shipping;
	public $customer_tax_exempt;
	public $customer_tax_ids;
	public $days_until_due;
	public $default_payment_method;
	public $default_source;
	public $default_tax_rates;
	public $description;
	public $discount;
	public $discounts;
	public $due_date;
	public $effective_at;
	public $ending_balance;
	public $footer;
	public $from_invoice;
	public $hosted_invoice_url;
	public $invoice_pdf;
	public $issuer;
	public $last_finalization_error;
	public $latest_revision;
	public $lines;
	public $livemode;
	public $metadata;
	public $next_payment_attempt;
	public $number;
	public $on_behalf_of;
	public $paid;
	public $paid_out_of_band;
	public $payment_intent;
	public $payment_settings;
	public $parent;
	public $period_end;
	public $period_start;
	public $post_payment_credit_notes_amount;
	public $pre_payment_credit_notes_amount;
	public $quote;
	public $receipt_number;
	public $rendering;
	public $rendering_options;
	public $shipping_cost;
	public $shipping_details;
	public $starting_balance;
	public $statement_descriptor;
	public $status;
	public $status_transitions;
	public $subtotal;
	public $subtotal_excluding_tax;
	public $subscription;
	public $subscription_details;
	public $subscription_proration_date;
	public $tax;
	public $tax_percent;
	public $test_clock;
	public $threshold_reason;
	public $total;
	public $total_discount_amounts;
	public $total_excluding_tax;
	public $total_pretax_credit_amounts;
	public $total_taxes;
	public $total_tax_amounts;
	public $transfer_data;
	public $webhooks_delivered_at;



	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'invoices';
	}

	/**
	 * Gets the supported update endpoint parameters.
	 *
	 * @since 5.5.0
	 *
	 * @return array Return an array of supported parameters for the update endpoint.
	 */
	public function get_update_parameters() {

		$supported_params = array(
			'auto_advance',
			'collection_method',
			'description',
			'metadata',
			'account_tax_ids',
			'automatic_tax',
			'custom_fields',
			'days_until_due',
			'default_payment_method',
			'default_source',
			'default_tax_rates',
			'discounts',
			'due_date',
			'effective_at',
			'footer',
			'payment_settings',
			'rendering',
			'shipping_cost',
			'shipping_details',
			'statement_descriptor',
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
			'payment_intent' => '\Gravity_Forms_Stripe\API\Model\PaymentIntent',
			'payments[]'     => '\Gravity_Forms_Stripe\API\Model\InvoicePayment',
		);
	}
}
