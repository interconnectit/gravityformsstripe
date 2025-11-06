<?php

namespace Gravity_forms\Gravity_Forms_Stripe\Elements\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;
use GFCache;
use GFCommon;


class GF_Stripe_Elements_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	protected $addon;

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {

		if ( ! rgar( $this->args, 'form_ids' ) ) {
			return array();
		}

		$addon = gf_stripe();

		$form_stripe_arrays = array();
		foreach ( $this->args['form_ids'] as $form_id ) {
			$form = \GFAPI::get_form( $form_id );

			if ( ! $addon->has_stripe_card_element( $form ) ) {
				continue;
			}

			// Prepare Stripe Javascript arguments.
			$args = array(
				'formId'         => $form_id,
				'pageInstance'   => isset( $form['page_instance'] ) ? $form['page_instance'] : 0,
				'stripe_payment' => 'elements',
			);

			$cc_field           = $addon->get_stripe_card_field( $form );
			$args['ccFieldId']  = $cc_field->id;

			/**
			 * This filter allows classes to be used with Stripe Elements
			 * to control the look of the Credit Card field.
			 *
			 * @since 2.6
			 *
			 * @link https://stripe.com/docs/js/elements_object/create#elements_create-options-classes
			 *
			 * @param array The list of classes to be passed along to the Stripe element.
			 */
			$args['cardClasses'] = apply_filters( 'gform_stripe_elements_classes', array(), $form_id );

			/**
			 * This filter allows styles to be used with Stripe Elements
			 * to control the look of the Credit Card field and/or Payment Element.
			 *
			 * @since 2.6
			 * @since 5.0 Added an argument to know whether or not the payment element is enabled.
			 *
			 * @link https://stripe.com/docs/js/elements_object/create_element?type=card#elements_create-options-style
			 * @link https://stripe.com/docs/elements/appearance-api
			 *
			 * @param array The list of styles to be passed along to the Stripe element.
			 */
			$args['styles'] = apply_filters( 'gform_stripe_elements_style', $addon->get_stripe_card_styles(), $form_id, false );

			/**
			 * This filter enables users to change the amount of time (in seconds) that the form submission will wait for the Stripe webhook before displaying the Timeout confirmation.
			 *
			 * @since 6.0
			 *
			 * @param int $timeout_seconds The timeout in seconds that the form submission will wait for a response from the Stripe webhook.
			 * @param array $form          The form object.
			 */
			$args['timeout_seconds'] = gf_apply_filters( array( 'gform_stripe_submission_timeout_seconds', $form_id ), 20, $form );

			$form_stripe_arrays[ $form_id ] = $args;
		}

		return array(
			'addon' => array(
				'stripe' => array(
					'elements' => $form_stripe_arrays,
				),
			),
		);
	}

	/**
	 * Enable ajax loading for the "gform_theme_config/addon/stripe/elements" config path.
	 *
	 * @since 5.8.0
	 *
	 * @param string $config_path The full path to the config item when stored in the browser's window object, for example: "gform_theme_config/common/form/product_meta"
	 * @param array  $args        The args used to load the config data. This will be empty for generic config items. For form specific items will be in the format: array( 'form_ids' => array(123,222) ).
	 *
	 * @return bool Return true if the provided $config_path is the product_meta path. Return false otherwise.
	 */
	public function enable_ajax( $config_path, $args ) {
		if ( str_starts_with( $config_path, 'gform_theme_config/addon/stripe/elements' ) ) {
			return true;
		}
		return false;
	}
}
