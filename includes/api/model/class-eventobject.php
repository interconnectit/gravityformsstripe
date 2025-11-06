<?php

namespace Gravity_Forms_Stripe\API\Model;

use Gravity_Forms_Stripe\API\Model\Base;

require_once( 'class-base.php' );

/**
 * Object representing a Webhook Event Object.
 *
 * @since 5.5.0
 */
class EventObject extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 6.0
	 */
	public $amount_paid;
	public $amount_remaining;
	public $attempt_count;
	public $attempted;
	public $charge;
	public $currency;
	public $default_payment_method;
	public $paid;
	public $status;
	public $status_transitions;


	/**
	 * This method is not supported by this object
	 *
	 * @since 5.5.0
	 *
	 * @param $id
	 * @param $params
	 * @param $opts
	 * @return \WP_Error
	 */
	public function update( $id, $params = null, $opts = null ) {
		return new \WP_Error( 'invalid-request', __( 'Event Object cannot be updated.', 'gravityformsstripe' ) );
	}

}
