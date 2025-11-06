<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing a Payment Method.
 *
 * @since 5.5.0
 */
class PaymentMethod extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $card;
	public $customer;
	public $livemode;
	public $metadata;
	public $type;
	public $billing_details;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'payment_methods';
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
			'billing_details',
			'metadata',
			'card',
			'link',
			'us_bank_account',
		);

		return $this->serialize_parameters( $supported_params );
	}

}
