<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );
require_once( 'class-product.php' );

/**
 * Object representing a Plan.
 *
 * @since 5.5.0
 */
class Plan extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $active;
	public $amount;
	public $currency;
	public $interval;
	public $livemode;
	public $metadata;
	public $nickname;
	public $product;
	public $aggregate_usage;
	public $amount_decimal;
	public $billing_scheme;
	public $interval_count;
	public $tiers;
	public $tiers_mode;
	public $transform_usage;
	public $trial_period_days;
	public $usage_type;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'plans';
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
			'active',
			'metadata',
			'nickname',
			'product',
			'trial_period_days',
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
			'product' => '\Gravity_Forms_Stripe\API\Model\Product',
		);
	}
}
