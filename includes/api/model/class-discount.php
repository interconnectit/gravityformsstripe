<?php

namespace Gravity_Forms_Stripe\API\Model;

use Gravity_Forms_Stripe\API\Model\Base;

require_once( 'class-base.php' );
require_once( 'class-coupon.php' );

/**
 * Object representing a Discount.
 *
 * @since 5.5.0
 */
class Discount extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $id;
	public $checkout_session;
	public $coupon;
	public $customer;
    public $end;
    public $invoice;
    public $invoice_item;
    public $promotion_code;
    public $start;
    public $subscription;


	/**
	 * Gets the nested object that should be expanded when this object is created.
	 *
	 * @since 5.5.0
	 *
	 * @return array Returns an array of nested objects that should be expanded when this object is created.
	 */
	public function get_nested_objects() {

		return array(
			'coupon' => '\Gravity_Forms_Stripe\API\Model\Coupon',
		);
	}

}
