<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing a Setup Intent.
 *
 * @since 5.5.0
 */
class SetupIntent extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $application;
	public $client_secret;
	public $customer;
	public $description;
	public $livemode;
	public $metadata;
	public $payment_method;
	public $payment_method_types;
	public $status;
	public $usage;
	public $attach_to_self;
	public $automatic_payment_methods;
	public $cancellation_reason;
	public $flow_direction;
	public $last_setup_error;
	public $latest_attempt;
	public $mandate;
	public $next_action;
	public $on_behalf_of;
	public $payment_method_configuration_details;
	public $payment_method_options;
	public $single_use_mandate;

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'setup_intents';
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
			'customer',
			'description',
			'metadata',
			'payment_method',
			'attach_to_self',
			'flow_directions',
			'payment_method_configuration',
			'payment_method_data',
			'payment_method_options',
			'payment_method_types',
		);

		return $this->serialize_parameters( $supported_params );
	}
}
