<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing an Invoice Payment, which is the link between an invoice and a Payment Intent / Charge.
 *
 * @since 6.0.0
 */
class InvoicePayment extends Base {

	public $id;
	public $object;
	public $amount_paid;
	public $amount_requested;
	public $created;
	public $currency;
	public $invoice;
	public $is_default;
	public $livemode;
	public $payment;
	public $status;
	public $status_transitions;


	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 6.0.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'invoice_payments';
	}

}
