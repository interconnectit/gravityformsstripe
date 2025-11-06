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
	public $adaptive_pricing;
	public $after_expiration;
	public $allow_promotion_codes;
	public $amount_subtotal;
	public $amount_total;
	public $automatic_tax;
	public $billing_address_collection;
	public $cancel_url;
	public $client_reference_id;
	public $client_secret;
	public $collected_information;
	public $consent;
	public $consent_collection;
	public $currency;
	public $currency_conversion;
	public $custom_fields;
	public $custom_text;
	public $customer;
	public $customer_creation;
	public $customer_details;
	public $customer_email;
	public $discounts;
	public $expires_at;
	public $invoice;
	public $invoice_creation;
	public $line_items;
	public $livemode;
	public $locale;
	public $metadata;
	public $mode;
	public $payment_intent;
	public $payment_link;
	public $payment_method_collection;
	public $payment_method_configuration_details;
	public $payment_method_options;
	public $payment_method_types;
	public $payment_status;
	public $permissions;
	public $phone_number_collection;
	public $recovered_from;
	public $redirect_on_completion;
	public $return_url;
	public $saved_payment_method_options;
	public $setup_intent;
	public $shipping_address_collection;
	public $shipping_cost;
	public $shipping_details;
	public $shipping_options;
	public $status;
	public $submit_type;
	public $subscription;
	public $success_url;
	public $tax_id_collection;
	public $total_details;
	public $ui_mode;
	public $url;
	public $wallet_options;


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
