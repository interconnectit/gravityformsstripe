<?php
namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );
require_once( 'class-paymentintent.php' );
require_once( 'class-invoice.php' );
require_once( 'class-customer.php' );
require_once( 'class-customerbalancetransaction.php' );

/**
 * Object representing a Charge.
 *
 * @since 5.5.0
 */
class Charge extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $amount;
	public $application;
	public $balance_transaction;
	public $captured;
	public $currency;
	public $customer;
	public $description;
	public $disputed;
	public $livemode;
	public $metadata;
	public $outcome;
	public $paid;
	public $payment_intent;
	public $payment_method;
	public $receipt_number;
	public $refunded;
	public $review;
	public $shipping;
	public $statement_descriptor;
	public $status;
	public $transfer;
	public $amount_captured;
	public $amount_refunded;
	public $application_fee;
	public $application_fee_amount;
	public $billing_details;
	public $calculated_statement_descriptor;
	public $failure_balance_transaction;
	public $failure_code;
	public $failure_message;
	public $fraud_details;
	public $on_behalf_of;
	public $payment_method_details;
	public $receipt_email;
	public $reciept_url;
	public $source_transfer;
	public $statement_descriptor_suffix;
	public $transfer_data;
	public $transfer_group;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'charges';
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
			'customer',
			'description',
			'metadata',
			'receipt_email',
			'shipping',
			'fraud_details',
		);

		return $this->serialize_parameters( $supported_params );
	}

	/**
	 * Gets the supported parameters for the capture endpoint.
	 *
	 * @since 5.5.0
	 *
	 * @return array Return an array of supported parameters for the capture endpoint.
	 */
	public function get_capture_parameters() {

		$supported_params = array(
			'amount',
			'receipt_email',
			'statement_descriptor',
			'statement_descriptor_suffix',
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
			'payment_intent'      => '\Gravity_Forms_Stripe\API\Model\PaymentIntent',
			'invoice'             => '\Gravity_Forms_Stripe\API\Model\Invoice',
			'customer'            => '\Gravity_Forms_Stripe\API\Model\Customer',
			'balance_transaction' => '\Gravity_Forms_Stripe\API\Model\CustomerBalanceTransaction',
		);
	}
}
