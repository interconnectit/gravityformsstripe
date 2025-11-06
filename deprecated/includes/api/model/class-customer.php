<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Represents a Stripe Customer.
 *
 * @since 5.5.0
 */
class Customer extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $address;
	public $balance;
	public $currency;
	public $description;
	public $discount;
	public $email;
	public $livemode;
	public $metadata;
	public $name;
	public $phone;
	public $shipping;
	public $sources;
	public $subscriptions;
	public $tax;
	public $tax_ids;
	public $cash_balance;
	public $default_source;
	public $delinquent;
	public $invoice_credit_balance;
	public $invoice_prefix;
	public $invoice_settings;
	public $next_invoice_sequence;
	public $preferred_locales;
	public $tax_exempt;
	public $test_clock;

	/**
	 * Gets the supported parameters for the update endpoint.
	 *
	 * @since 5.5.0
	 *
	 * @return array Return an array of supported parameters for the update endpoint.
	 */
	public function get_update_parameters() {

		$supported_params = array(
			'address',
			'description',
			'email',
			'metadata',
			'name',
			'phone',
			'shipping',
			'balance',
			'coupon',
			'default_source',
			'invoice_prefix',
			'invoice_settings',
			'next_invoice_sequence',
			'preferred_locales',
			'promotion_code',
			'source',
			'tax',
			'tax_exempt',
		);

		return $this->serialize_parameters( $supported_params );
	}

	/**
	 * Returns the API endpoint for this object.
	 *
	 * @since 5.5.0
	 *
	 * @return string Returns the api endpoint for this object.
	 */
	public function api_endpoint() {
		return 'customers';
	}


	/**
	 * Creates a new invoice item for this customer.
	 *
	 * @since 5.5.0
	 *
	 * @param $data array The data to create the invoice item with.
	 *
	 * @return Model\InvoiceItem Returns the invoice item object, or a WP_Error if there was an error.
	 */
	public function addInvoiceItem( $data ) {

		$data['customer'] = $this->id;
		return $this->api->add_invoice_item( $data );
	}
}
