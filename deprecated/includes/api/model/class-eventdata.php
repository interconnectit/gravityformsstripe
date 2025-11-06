<?php

namespace Gravity_Forms_Stripe\API\Model;

use Gravity_Forms_Stripe\API\Model\Base;

require_once( 'class-base.php' );

/**
 * Object representing a Webhook Event Data.
 *
 * @since 5.5.0
 */
class EventData extends Base {

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
		return new \WP_Error( 'invalid-request', __( 'Event Data cannot be updated.', 'gravityformsstripe' ) );
	}

	/**
	 * Overrides parent method to dynamically expand event "object" property into one of the supported objects.
	 *
	 * @since 5.5.0
	 *
	 * @param string $key         Name of property (array key)
	 * @param string|array $value Value to be expanded.
	 *
	 * @return mixed Returns the original value, or one of the supported objects.
	 */
	protected function maybe_expand( $key, $value ) {
		$objects = $this->get_object_map();
		$object  = rgar( $objects, rgar( $value, 'object' ) );

		if ( $key === 'object' && ! empty( $object ) ) {
			require_once( $object['file'] );

			return new $object['class']( $value, $this->api );
		}

		require_once( 'class-eventobject.php' );
		return new EventObject( $value, $this->api );
	}

	/**
	 * Returns an array mapping the object name to the class and file name.
	 *
	 * @since 5.5.0
	 *
	 * @return array[] Returns an array mapping the object name to the class and file name.
	 */
	private function get_object_map() {
		return array(
			'account'                      => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Account',
				'file'  => 'class-account.php',
			),
			'charge'                       => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Charge',
				'file'  => 'class-charge.php',
			),
			'coupon'                       => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Coupon',
				'file'  => 'class-coupon.php',
			),
			'customer'                     => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Customer',
				'file'  => 'class-customer.php',
			),
			'customer_balance_transaction' => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\CustomerBalanceTransaction',
				'file'  => 'class-customerbalancetransaction.php',
			),
			'invoice'                      => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Invoice',
				'file'  => 'class-invoice.php',
			),
			'invoiceitem'                  => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\InvoiceItem',
				'file'  => 'class-invoiceitem.php',
			),
			'payment_intent'               => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\PaymentIntent',
				'file'  => 'class-paymentintent.php',
			),
			'payment_method'               => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\PaymentMethod',
				'file'  => 'class-paymentmethod.php',
			),
			'plan'                         => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Plan',
				'file'  => 'class-plan.php',
			),
			'product'                      => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Product',
				'file'  => 'class-product.php',
			),
			'refund'                       => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Refund',
				'file'  => 'class-refund.php',
			),
			'checkout.session'             => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Session',
				'file'  => 'class-session.php',
			),
			'payment_method'               => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\PaymentMethod',
				'file'  => 'class-paymentmethod.php',
			),
			'payment_intent'               => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\PaymentIntent',
				'file'  => 'class-paymentintent.php',
			),
			'setup_intent'                 => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\SetupIntent',
				'file'  => 'class-setupintent.php',
			),
			'subscription'                 => array(
				'class' => '\Gravity_Forms_Stripe\API\Model\Subscription',
				'file'  => 'class-subscription.php',
			),
		);
	}

}
