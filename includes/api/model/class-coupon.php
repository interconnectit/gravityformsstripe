<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing a Coupon.
 *
 * @since 5.5.0
 */
class Coupon extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $amount_off;
	public $currency;
	public $duration;
	public $livemode;
	public $metadata;
	public $name;
	public $percent_off;
	public $valid;
	public $applies_to;
	public $currency_options;
	public $duration_in_months;
	public $max_redemptions;
	public $redeem_by;
	public $times_redeemed;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'coupons';
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
			'name',
			'currency_options',
		);

		return $this->serialize_parameters( $supported_params );
	}
}
