<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing an Invoice item.
 *
 * @since 5.5.0
 */
class InvoiceItem extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $amount;
	public $currency;
	public $customer;
	public $date;
	public $description;
	public $discounts;
	public $invoice;
	public $livemode;
	public $metadata;
	public $period;
	public $plan;
	public $price;
	public $quantity;
	public $subscription;
	public $unit_amount;
	public $discountable;
	public $proration;
	public $subscription_item;
	public $tax_rates;
	public $test_clock;
	public $unit_amount_decimal;



	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'invoiceitems';
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
			'amount',
			'description',
			'metadata',
			'period',
			'price',
			'discountable',
			'discounts',
			'price_data',
			'quantity',
			'tax_behavior',
			'tax_code',
			'tax_rates',
			'unit_amount',
			'unit_amount_decimal',
		);

		return $this->serialize_parameters( $supported_params );
	}
}
