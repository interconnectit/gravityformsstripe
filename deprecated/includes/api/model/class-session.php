<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );

/**
 * Object representing a Checkout Session.
 *
 * @since 5.5.0
 */
class Session extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $consent;
	public $currency;
	public $custom_fields;
	public $customer;
	public $expires_at;
	public $invoice;
	public $line_items;
	public $livemode;
	public $locale;
	public $metadata;
	public $mode;
	public $payment_intent;
	public $payment_method_types;
	public $payment_status;
	public $return_url;
	public $status;
	public $subscription;
	public $url;
	public $after_expiration;
	public $allow_promotion_codes;
	public $amount_subtotal;
	public $amount_total;
	public $automatic_tax;
	public $billing_address_collection;
	public $cancel_url;
	public $client_reference_id;
	public $consent_collection;
	public $custom_text;
	public $customer_creation;
	public $customer_details;
	public $customer_email;
	public $invoice_creation;
	public $payment_link;
	public $payment_method_collection;
	public $oayment_method_configuration_details;
	public $payment_method_options;
	public $phone_number_collection;
	public $recovered_from;
	public $redirect_on_completion;
	public $setup_intent;
	public $shipping_cost;
	public $shipping_details;
	public $shipping_address_collection;
	public $shipping_options;
	public $submit_type;
	public $success_url;
	public $tax_id_collection;
	public $total_details;
	public $ui_mode;

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
		return new \WP_Error( 'invalid-request', __( 'Checkout Sessions cannot be updated.', 'gravityformsstripe' ) );
	}

}
