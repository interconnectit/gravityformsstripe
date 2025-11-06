<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * This is an object representing a Stripe account.
 * @since 5.5.0
 */
class Account extends Base {

	/**
	 *  Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $capabilities;
	public $company;
	public $controller;
	public $country;
	public $email;
	public $individual;
	public $metadata;
	public $requirements;
	public $settings;
	public $type;
	public $business_type;
	public $business_profile;
	public $charges_enabled;
	public $default_currency;
	public $details_submitted;
	public $external_accounts;
	public $future_requirements;
	public $payouts_enabled;
	public $tos_acceptance;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'accounts';
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
			'business_type',
			'capabilities',
			'metadata',
			'account_token',
			'settings',
		);

		return $this->serialize_parameters( $supported_params );
	}
}
