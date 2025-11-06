<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing a Refund.
 *
 * @since 5.5.0
 */
class Refund extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $amount;
	public $balance_transaction;
	public $charge;
	public $currency;
	public $description;
	public $metadata;
	public $payment_intent;
	public $reason;
	public $receipt_number;
	public $status;
	public $destination_details;
	public $failure_balance_transaction;
	public $failure_reason;
	public $instructions_email;
	public $next_action;
	public $source_transfer_reversal;
	public $transfer_reversal;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'refunds';
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
			'metadata',
		);

		return $this->serialize_parameters( $supported_params );
	}
}
