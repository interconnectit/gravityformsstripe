<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * The Stripe Card field is a credit card field used specifically by the Stripe Add-On.
 *
 * @since 2.6
 *
 * Class GF_Field_Stripe_CreditCard
 */
class GF_Field_Stripe_CreditCard extends GF_Field {

	/**
	 * Field type.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	public $type = 'stripe_creditcard';

	/**
	 * Get field button title.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Stripe', 'gravityformsstripe' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a dashicons class.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return gf_stripe()->get_menu_icon();
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Collects payments securely via Stripe payment gateway.', 'gravityformsstripe' );
	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @since  2.6
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {
		$multiple_payment_methods_enabled = gf_stripe()->is_payment_element_supported() ? 'true' : 'false';

		$js = sprintf( "function SetDefaultValues_%s(field) {field.label = '%s'; field.enableMultiplePaymentMethods = %s;
		field.linkEmailFieldId = '';
		field.inputs = [new Input(field.id + '.1', %s), new Input(field.id + '.4', %s), new Input(field.id + '.5', %s)];
		}", $this->type, esc_html__( 'Credit Card', 'gravityformsstripe' ), $multiple_payment_methods_enabled, json_encode( gf_apply_filters( array( 'gform_card_details', rgget( 'id' ) ), esc_html__( 'Card Details', 'gravityformsstripe' ), rgget( 'id' ) ) ), json_encode( gf_apply_filters( array( 'gform_card_type', rgget( 'id' ) ), esc_html__( 'Card Type', 'gravityformsstripe' ), rgget( 'id' ) ) ), json_encode( gf_apply_filters( array( 'gform_card_name', rgget( 'id' ) ), esc_html__( 'Cardholder Name', 'gravityformsstripe' ), rgget( 'id' ) ) ) ) . PHP_EOL;

		$unique_string = esc_html__( 'A form can only contain one Stripe field.', 'gravityformsstripe' );
		$js .= /** @lang JavaScript */ "
			gform.addFilter('gform_form_editor_can_field_be_added', function(result, type) {
				if (type === 'stripe_creditcard') {
				    if (GetFieldsByType(['stripe_creditcard']).length > 0) {
						if( typeof gform.instances.dialogAlert !== 'function' ) {
							" . sprintf( "alert(%s);", json_encode( $unique_string ) ) . "
						} else {
							gform.instances.dialogAlert( gf_vars.fieldCanBeAddedTitle, '" . $unique_string . "' );
						}
						result = false;
					}
				}
				
				return result;
			});
		";

		$js .= /** @lang JavaScript */ "
			jQuery(document).bind('gform_load_field_settings', function(event, field, form) {
				var activeToggle = 'active1.png',
					inactiveToggle = 'active0.png',
					imagesUrl, input, isHidden, title, img;

				if ( field['type'] !=='stripe_creditcard' ) {
					return;
				}

				imagesUrl = '" . GFCommon::get_base_url() . '/images/' . "';
				input = field['inputs'][2];
				isHidden = typeof input.isHidden != 'undefined' && input.isHidden ? true : false;
				title = isHidden ? " . json_encode( esc_html__( 'Inactive', 'gravityforms' ) ) . ':' . json_encode( esc_html__( 'Active', 'gravityforms' ) ) . ";
				img = isHidden ? inactiveToggle : activeToggle;
				
				// In GF 2.9, the sub-labels setting markup changed from a table to a fieldset. Implementing similar idea as seen in core & ppcp. 
				isFieldset = jQuery( '.sub_labels_setting fieldset' ).length > 0;
				cardHolderNameInput = jQuery( '.sub_labels_setting .field_custom_inputs_ui .field_custom_input_row_input_' + field['id'] + '_5 input' );
				
				if( isFieldset ) {
					jQuery( '.sub_labels_setting .field_custom_inputs_ui .field_custom_input_row_input_' + field['id'] + '_5 span' ).toggle( ! isHidden );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui' ).removeClass( 'gform-sidebar-setting-grid-wrapper__two-column' ).addClass( 'gform-sidebar-setting-grid-wrapper__three-column' );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui .gform-sidebar-setting-grid-header' ).prepend( '<span>" . esc_html__( 'Show', 'gravityforms' ) . "</span>' );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui .field_custom_input_row' ).prepend( '<span></span>' );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui .field_custom_input_row_input_' + field['id'] + '_5 span' ).prepend( '<img data-input_id=\'' + field['id'] + '.5\' alt=\'' + title + '\' class=\'input_active_icon cardholder_name\' src=\'' + imagesUrl + img + '\'/>' );
					jQuery( '#input_placeholder_row_input_' + field['id'] + '_1' ).remove();
					
					// Toggle Sub-Label disabled status.
					cardHolderNameInput.prop ( 'disabled', isHidden );
				} else {
					jQuery( '.input_placeholders tr:eq(1)' ).toggle( ! isHidden );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui tr:eq(0)' ).prepend( '<td><strong>" . esc_html__( 'Show', 'gravityforms' ) . "</strong></td>' );
					jQuery( '.sub_labels_setting .field_custom_inputs_ui tr:eq(1)' ).prepend( '<td></td>' );
					jQuery('.input_placeholders tr:eq(1)').remove();
					jQuery( '.sub_labels_setting .field_custom_inputs_ui tr:eq(2)' ).prepend( '<td><img data-input_id=\'' + field['id'] + '.5\' alt=\'' + title + '\' class=\'input_active_icon cardholder_name\' src=\'' + imagesUrl + img + '\'/></td>' );
					
					// Toggle Sub-Label disabled status.
					cardHolderNameInput.prop ( 'disabled', isHidden );
				}

				jQuery('.sub_labels_setting').on('click keypress', '.input_active_icon.cardholder_name', function( e ) {
					e.stopImmediatePropagation();
					this.src = isHidden ? this.src.replace(inactiveToggle, activeToggle) : this.src.replace(activeToggle, inactiveToggle);
					
					// Hides the name input if toggled off. 
					cardHolderNameInput.prop('disabled', !isHidden);
					
					SetInputHidden( !isHidden, input.id );
					
					// Toggle the state of isHidden for the next click or keypress.
					isHidden = !isHidden;
		        });
			});
		";

		$js .= /** @lang JavaScript */ "
			gform.addAction('gform_post_load_field_settings', function ([field, form]) {
				if ( field['type'] === 'stripe_creditcard' ) {	        
					// Hide #field_settings when the field has error conditions.
					// This is called right after the settings are shown. So that makes it feel like there's no settings.
					if ( jQuery('.gform_stripe_card_error').length ) {
						HideSettings( 'field_settings' );
					}
				}
			});";

		return $js;
	}

	/**
	 * Get field settings in the form editor.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'force_ssl_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
			'enable_multiple_payment_methods_setting',
			'sub_labels_setting',
			'sub_label_placement_setting',
			'input_placeholders_setting',
		);
	}

	/**
	 * Get form editor button.
	 *
	 * @since 2.6
	 * @since 3.4 Add the Stripe Card field only when checkout method is not Checkout.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		if ( gf_stripe()->get_plugin_setting( 'checkout_method' ) !== 'stripe_checkout' ) {
			return array(
				'group' => 'pricing_fields',
				'text'  => $this->get_form_editor_field_title(),
			);
		} else {
			return array();
		}
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @since 2.6
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		// If payment element is used, validation already happened in the front end, and the field has no value now.
		if ( gf_stripe()->is_payment_element_enabled( GFAPI::get_form( $form_id ) ) ) {
			return false;
		}
		// check only the cardholder name.
		return $this->is_cardholder_name_empty();
	}

	/**
	 * Updates the field is required message to show cardholder name is required instead.
	 *
	 * @since 5.8
	 *
	 * @param mixed $value                   The field value.
	 * @param bool  $require_complex_message Indicates if the field must have a complex validation message for the error to be set.
	 *
	 * @return void
	 */
	public function set_required_error( $value, $require_complex_message = false ) {
		parent::set_required_error( $value, $require_complex_message );
		if ( $this->isRequired && $this->is_cardholder_name_empty() ) {
			$this->validation_message = __( 'Cardholder name is required.', 'gravityformsstripe' );
		}
	}

	/**
	 * Checks if the cardholder name is not hidden and is empty.
	 *
	 * @since 5.8
	 *
	 * @return bool
	 */
	public function is_cardholder_name_empty() {
		// check only the cardholder name.
		$cardholder_name_input = GFFormsModel::get_input( $this, $this->id . '.5' );
		$hide_cardholder_name  = rgar( $cardholder_name_input, 'isHidden' );
		$cardholder_name       = rgpost( 'input_' . $this->id . '_5' );
		if ( ! $hide_cardholder_name && empty( $cardholder_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get submission value.
	 *
	 * @since 2.6
	 *
	 * @param array $field_values Field values.
	 * @param bool  $get_from_post_global_var True if get from global $_POST.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		if ( $get_from_post_global_var ) {
			$value[ $this->id . '.1' ] = $this->get_input_value_submission( 'input_' . $this->id . '_1', rgar( $this->inputs[0], 'name' ), $field_values, true );
			$value[ $this->id . '.4' ] = $this->get_input_value_submission( 'input_' . $this->id . '_4', rgar( $this->inputs[1], 'name' ), $field_values, true );
			$value[ $this->id . '.5' ] = $this->get_input_value_submission( 'input_' . $this->id . '_5', rgar( $this->inputs[2], 'name' ), $field_values, true );
		} else {
			$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		}

		return $value;
	}

	/**
	 * Get field input.
	 *
	 * @since 2.6
	 *
	 * @param array      $form  The Form Object currently being processed.
	 * @param array      $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = array(), $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id === 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above        = $field_sub_label_placement === 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement === 'above' );
		$sub_label_class_attribute = $field_sub_label_placement === 'hidden_label' ? " class='hidden_sub_label screen-reader-text'" : " class='gform-field-label gform-field-label--type-sub'";

		$card_details_input     = GFFormsModel::get_input( $this, $this->id . '.1' );
		$card_details_sub_label = rgar( $card_details_input, 'customLabel' ) !== '' ? $card_details_input['customLabel'] : esc_html__( 'Card Details', 'gravityformsstripe' );
		$card_details_sub_label = gf_apply_filters( array( 'gform_card_details', $form_id, $this->id ), $card_details_sub_label, $form_id );

		$cardholder_name_input       = GFFormsModel::get_input( $this, $this->id . '.5' );
		$hide_cardholder_name        = rgar( $cardholder_name_input, 'isHidden' );
		$cardholder_name_sub_label   = rgar( $cardholder_name_input, 'customLabel' ) !== '' ? $cardholder_name_input['customLabel'] : esc_html__( 'Cardholder Name', 'gravityformsstripe' );
		$cardholder_name_sub_label   = gf_apply_filters( array( 'gform_card_name', $form_id, $this->id ), $cardholder_name_sub_label, $form_id );
		$cardholder_name_placeholder = $this->get_input_placeholder_attribute( $cardholder_name_input );

		if ( $cardholder_name_placeholder ) {
			$cardholder_name_placeholder = ' ' . $cardholder_name_placeholder;
		}

		// Prepare the values for checking the Stripe Card field error.
		$api_key        = gf_stripe()->get_publishable_api_key();
		$no_stripe_feed = ! gf_stripe()->has_feed( $form_id );

		// If we are in the form editor, display a placeholder field.
		if ( $is_admin ) {
			$validation_check = $this->admin_field_validation_check( $api_key, $no_stripe_feed, $form_id );
			if ( true !== $validation_check ) {
				return $validation_check;
			}

			return $this->get_admin_card_field( $field_id, $is_sub_label_above, $card_details_sub_label, $sub_label_class_attribute, $cardholder_name_placeholder, $hide_cardholder_name, $cardholder_name_sub_label, $class_suffix );
		}

		$cardholder_name = '';
		if ( ! empty( $value ) ) {
			$cardholder_name = esc_attr( rgget( $this->id . '.5', $value ) );
		}

		$card_error = '';

		// Display the no API connection error.
		if ( empty( $api_key ) ) {
			$card_error           = $this->get_card_error_message( $this->get_api_error_message() );
			$hide_cardholder_name = true;
		} elseif ( gf_stripe()->is_stripe_checkout_enabled() ) {
			// Display the Stripe Checkout error.
			/* translators: 1. Open div tag 2. Close div tag */
			$stripe_checkout_enabled_error = $is_form_editor ? esc_html__( 'Incompatible Payment Collection Method%1$sTo use the Stripe field, set your Payment Collection Method to Stripe Field, or to continue using the Stripe Payment Form (Stripe Checkout), remove the Stripe field.%2$s', 'gravityformsstripe' ) : esc_html__( '%1$sIncompatible Payment Collection Method: To use the Stripe field, set your Payment Collection Method to Stripe Field, or to continue using the Stripe Payment Form (Stripe Checkout), remove the Stripe field.%2$s', 'gravityformsstripe' );
			$card_error                    = $this->get_card_error_message( $stripe_checkout_enabled_error );
			$hide_cardholder_name          = true;
		} elseif ( $no_stripe_feed ) {
			// Display the no Stripe feed error.
			/* translators: 1. Open div tag 2. Close div tag */
			$no_stripe_feed_error = $is_form_editor ? esc_html__( 'Feed Required%1$sTo use the Stripe field, please create a Stripe feed for this form.%2$s', 'gravityformsstripe' ) : esc_html__( '%1$sFeed Required: To use the Stripe field, please create a Stripe feed for this form.%2$s', 'gravityformsstripe' );
			$card_error           = $this->get_card_error_message( $no_stripe_feed_error );
			$hide_cardholder_name = true;
		} elseif ( $this->enableMultiplePaymentMethods && gf_stripe()->is_stripe_connect_enabled() === true ) {
			$hide_cardholder_name = true;
		}

		$cc_input = '';

		if ( gf_stripe()->is_gravityforms_supported( '2.8.8' ) && $this->is_validation_above( $form ) ) {
			$cc_input .= $card_error;
		}

		$cc_input .= "<div class='ginput_complex{$class_suffix} ginput_container ginput_container_creditcard ginput_stripe_creditcard gform-grid-row' id='{$field_id}'>";

		$is_payment_element = ( $this->enableMultiplePaymentMethods && gf_stripe()->is_stripe_connect_enabled() === true ) ? 'true' : 'false';
		$field_control_class = $this->enableMultiplePaymentMethods ? 'StripeElement--payment-element' : 'gform-theme-field-control StripeElement--card';

		if ( $is_sub_label_above ) {
			$cc_input .= "<div class='ginput_full gform-grid-col' id='{$field_id}_1_container' data-payment-element='{$is_payment_element}'>";

			if ( ! $hide_cardholder_name ) {
				$cc_input .= "<label for='{$field_id}_1' id='{$field_id}_1_label'{$sub_label_class_attribute}>" . $card_details_sub_label . '</label>';
			}

			$cc_input .= "<div id='{$field_id}_1' class='{$field_control_class}'></div>";

			$cc_input .= '</div><!-- .ginput_full -->';

			if ( ! $hide_cardholder_name ) {
				$cc_input .= "<div class='ginput_full gform-grid-col' id='{$field_id}_5_container'>
					<label for='{$field_id}_5' id='{$field_id}_5_label'{$sub_label_class_attribute}>" . $cardholder_name_sub_label . "</label>
					<input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$cardholder_name}'{$cardholder_name_placeholder}>
				</div>";
			}
		} else {

			$cc_input .= "<div class='ginput_full gform-grid-col' id='{$field_id}_1_container' data-payment-element='{$is_payment_element}'>";
			$cc_input .= "<div id='{$field_id}_1' class='{$field_control_class}'></div>";

			if ( ! $hide_cardholder_name ) {
				$cc_input .= "<label for='{$field_id}_1' id='{$field_id}_1_label'{$sub_label_class_attribute}>" . $card_details_sub_label . '</label>';
			}

			$cc_input .= '</div><!-- .ginput_full -->';

			if ( ! $hide_cardholder_name ) {
				$cc_input .= "<div class='ginput_full gform-grid-col' id='{$field_id}_5_container'>
					<input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$cardholder_name}'{$cardholder_name_placeholder}>
					<label for='{$field_id}_5' id='{$field_id}_5_label'{$sub_label_class_attribute}>" . $cardholder_name_sub_label . '</label>
				</div>';
			}
		}

		$cc_input .= '</div><!-- .ginput_container -->';

		if ( gf_stripe()->is_gravityforms_supported( '2.8.8' ) && ! $this->is_validation_above( $form ) ) {
			$cc_input .= $card_error;
		}

		$cc_input .= '
			<style type="text/css">
				:root {
  					--link-login-string: "' . wp_strip_all_tags( __( 'Link login', 'gravityformsstripe' ) ) . '"
				}
			</style>';
		return $cc_input;
	}

	/**
	 * Returns the warning message to be displayed in the form editor sidebar.
	 *
	 * @since 5.3
	 *
	 * @return string
	 */
	public function get_field_sidebar_messages() {
		$form_id        = rgpost( 'formId' ) ? rgpost( 'formId') : rgar( gf_stripe()->get_current_form(), 'id' );
		$api_key        = gf_stripe()->get_publishable_api_key();
		$no_stripe_feed = ! gf_stripe()->has_feed( $form_id );

		$warning = $this->admin_field_validation_check( $api_key, $no_stripe_feed, $form_id, true );

		if ( $warning !== true ) {
			return array(
				'type'             => 'notice',
				'content'          => $warning,
				'icon_helper_text' => __( 'This field requires additional configuration', 'gravityformsstripe' ),
			);
		}

		return '';
	}

	/**
	 * Check API Key, Stripe Checkout, and Feed Settings for admin validation.
	 *
	 * @since 5.9 Added $is_editor_sidebar param.
	 * @since 3.9
	 *
	 * @param string $api_key        The Stripe API key
	 * @param bool   $no_stripe_feed If no Stripe feed has been setup.
	 * @param int    $form_id        GF Form ID.
	 * @param bool   $is_editor_sidebar If the validation is for the form editor sidebar.
	 *
	 * @return bool|string True if passes validation, error string if failed.
	 */
	private function admin_field_validation_check( $api_key, $no_stripe_feed, $form_id, $is_editor_sidebar = false ) {
		$settings_url = add_query_arg(
			array(
				'page'    => 'gf_settings',
				'subview' => gf_stripe()->get_slug(),
			),
			admin_url( 'admin.php' )
		);

		// Display errors if there is no API key.
		if ( empty( $api_key ) ) {
			return $this->get_card_error_message( $this->get_api_error_message(), $settings_url, $is_editor_sidebar );
		}

		// Display the Stripe Checkout error.
		if ( gf_stripe()->is_stripe_checkout_enabled() ) {
			/* translators: 1. Open div tag 2. Close div tag 3. Open link tag 4. Close link tag */
			$stripe_checkout_enabled_error = esc_html__( 'Incompatible Payment Collection Method%1$s To use the Stripe field, set your %3$sPayment Collection Method%4$s to Stripe Field, or to continue using the Stripe Payment Form (Stripe Checkout), remove the Stripe field.%2$s', 'gravityformsstripe' );

			return $this->get_card_error_message( $stripe_checkout_enabled_error, $settings_url, $is_editor_sidebar );
		}

		// Display the no Stripe feed error.
		if ( $no_stripe_feed ) {
			$feed_url = add_query_arg(
				array(
					'page'    => 'gf_edit_forms',
					'view'    => 'settings',
					'subview' => gf_stripe()->get_slug(),
					'id'      => $form_id,
				),
				admin_url( 'admin.php' )
			);

			/* translators: 1. Open div tag 2. Close div tag 3. Open link tag 4. Close link tag */
			$no_stripe_feed_error = esc_html__( 'Feed Required%1$s To use the Stripe field, please create a %3$sStripe feed%4$s for this form.%2$s', 'gravityformsstripe' );

			return $this->get_card_error_message( $no_stripe_feed_error, $feed_url, $is_editor_sidebar );
		}

		return true;
	}

	/**
	 * Generate and return the HTML for the Credit Card field in the Admin.
	 *
	 * @since 3.9
	 *
	 * @param string $field_id                    The field ID.
	 * @param bool   $is_sub_label_above          If sublabel is above inputs.
	 * @param string $card_details_sub_label      Sublabel for card details.
	 * @param string $sub_label_class_attribute   Sublabel class.
	 * @param string $cardholder_name_placeholder Cardholder name placeholder.
	 * @param bool   $hide_cardholder_name        If cardholder name should be hidden.
	 * @param string $cardholder_name_sub_label   Cardholder name sublabel.
	 * @param string $class_suffix                CSS Class suffix.
	 *
	 * @return string
	 */
	private function get_admin_card_field( $field_id, $is_sub_label_above, $card_details_sub_label, $sub_label_class_attribute, $cardholder_name_placeholder, $hide_cardholder_name, $cardholder_name_sub_label, $class_suffix ) {
		$disabled_text = $this->is_form_editor() ? " disabled='disabled'" : '';
		$style         = ( $hide_cardholder_name ) ? " style='display:none;'" : '';
		$id            = intval( $this->id );
		$cc_input      = '<div class="ginput_complex' . $class_suffix . ' ginput_container ginput_container_creditcard ginput_stripe_creditcard gform-grid-row">';

		$cc_details_input = '<span class="cc-details-container">
			<span class="cc-placeholders">
				<span class="cc-mmdd-placeholder">' . esc_html__( 'MM/YY', 'gravityformsstripe' ) . '</span>
				<span class="cc-cvc-placeholder">' . esc_html__( 'CVC', 'gravityformsstripe' ) . '</span>
            </span>
			<input id="' . esc_attr( $field_id ) . '_1"' . $disabled_text . ' type="text" class="cc-cardnumber" placeholder="' . esc_attr__( 'Card Number', 'gravityformsstripe' ) . '">
		</span>';

		if ( $is_sub_label_above ) {
			$cc_input .= '<span class="cc-group ginput_full gform-grid-col" id="' . esc_attr( $field_id ) . '_1_container">
				<label for="' . esc_attr( $field_id ) . '_1" id="' . esc_attr( $field_id ) . '_1_label"' . $sub_label_class_attribute . $style . '>' . $card_details_sub_label . '</label>
				' . $cc_details_input . '
			</span>
			<span class="ginput_full gform-grid-col" id="' . esc_attr( $field_id ) . '_5_container"' . $style . '>
				<label for="' . esc_attr( $field_id ) . '_5" id="' . esc_attr( $field_id ) . '_5_label"' . $sub_label_class_attribute . '>' . $cardholder_name_sub_label . '</label>
				<input type="text" class="ginput_full" name="input_' . esc_attr( $id ) . '.5" id="' . esc_attr( $field_id ) . '_5" value=""' . $disabled_text . $cardholder_name_placeholder . '>
			</span>';
		} else {
			$cc_input .= '<span class="cc-group ginput_full gform-grid-col" id="' . esc_attr( $field_id ) . '_1_container">
				' . $cc_details_input . '
				<label for="' . esc_attr( $field_id ) . '_1" id="' . esc_attr( $field_id ) . '_1_label"' . $sub_label_class_attribute . $style . '>' . $card_details_sub_label . '</label>			
			</span>
			<span class="ginput_full gform-grid-col" id="' . esc_attr( $field_id ) . '_5_container"' . $style . '>
				<input type="text" class="ginput_full" name="input_' . esc_attr( $id ) . '.5" id="' . esc_attr( $field_id ) . '_5" value=""' . $disabled_text . $cardholder_name_placeholder . '>
				<label for="' . esc_attr( $field_id ) . '_5" id="' . esc_attr( $field_id ) . '_5_label"' . $sub_label_class_attribute . '>' . $cardholder_name_sub_label . '</label>
			</span>';
		}

		$cc_input .= '</div>';

		$cc_input .= '<div class="stripe-payment-element-container"></div>';

		return $cc_input;
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @since 2.6
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$field_content = parent::get_field_content( $value, $force_frontend_label, $form );

		if ( ! GFCommon::is_ssl() && ! $is_admin ) {
			$field_content = "<div class='gfield_description gfield_validation_message gfield_creditcard_warning_message'><span>" . esc_html__( 'This page is unsecured. Do not enter a real credit card number! Use this field only for testing purposes. ', 'gravityformsstripe' ) . '</span></div>' . $field_content;
		}

		return $field_content;
	}


	/**
	 * Returns the HTML markup for the field's containing element.
	 *
	 * @since 2.5
	 *
	 * @param array $atts Container attributes.
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container( $atts, $form ) {
		$atts['class'] .= $this->enableMultiplePaymentMethods ? ' gfield--type-stripe_creditcard-payment-element' : ' gfield--type-stripe_creditcard-card';

		return parent::get_field_container( $atts, $form );
	}

	/**
	 * Get field label class.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_field_label_class() {
		return 'gfield_label gfield_label_before_complex gform-field-label';
	}

	/**
	 * Get entry inputs.
	 *
	 * @since 2.6
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		$inputs = array();
		foreach ( $this->inputs as $input ) {
			if ( in_array( $input['id'], array( $this->id . '.1', $this->id . '.4' ), true ) ) {
				$inputs[] = $input;
			}
		}

		return $inputs;
	}

	/**
	 * Get the value in entry details.
	 *
	 * @since 2.6
	 *
	 * @param string|array $value    The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		if ( is_array( $value ) ) {
			$card_number = trim( rgget( $this->id . '.1', $value ) );
			$card_type   = trim( rgget( $this->id . '.4', $value ) );
			$card_number = $format === 'html' ? nl2br( $card_number ) : $card_number;
			$separator   = $format === 'html' ? '<br/>' : "\n";

			return empty( $card_number ) ? '' : $card_type . $separator . $card_number;
		} else {
			return '';
		}
	}

	/**
	 * Get the value when saving to an entry.
	 *
	 * @since 2.6
	 *
	 * @param string $value      The value to be saved.
	 * @param array  $form       The Form Object currently being processed.
	 * @param string $input_name The input name used when accessing the $_POST.
	 * @param int    $lead_id    The ID of the Entry currently being processed.
	 * @param array  $lead       The Entry Object currently being processed.
	 *
	 * @return array|string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		if ( gf_stripe()->is_payment_element_enabled( $form ) ) {
			return $this->sanitize_entry_value( $value, $form['id'] );
		}
		// saving last 4 digits of credit card.
		list( $input_token, $field_id_token, $input_id ) = rgexplode( '_', $input_name, 3 );
		if ( $input_id == '1' ) {
			$value              = str_replace( ' ', '', $value );
			$card_number_length = strlen( $value );
			$value              = substr( $value, - 4, 4 );
			$value              = str_pad( $value, $card_number_length, 'X', STR_PAD_LEFT );
		} elseif ( $input_id == '4' ) {

			$value = $this->get_card_name( rgpost( "input_{$field_id_token}_4" ) );

			if ( ! $value ) {
				$card_number = rgpost( "input_{$field_id_token}_1" );
				$card_type   = GFCommon::get_card_type( $card_number );
				$value       = $card_type ? $card_type['name'] : '';
			}
		} else {
			$value = '';
		}

		return $this->sanitize_entry_value( $value, $form['id'] );
	}

	/**
	 * Returns the full name for the supplied card brand.
	 *
	 * @since 3.5
	 *
	 * @param string $slug The card brand supplied by Stripe.js.
	 *
	 * @return string
	 */
	public function get_card_name( $slug ) {
		if ( empty( $slug ) ) {
			return $slug;
		}

		$card_types = GFCommon::get_card_types();

		foreach ( $card_types as $card ) {
			if ( rgar( $card, 'slug' ) === $slug ) {
				return rgar( $card, 'name' );
			}
		}

		return $slug;
	}

	/**
	 * Display the Stripe Card error message.
	 *
	 * @since 5.9 Updated to support different types/contexts of messages.
	 * @since 3.5
	 *
	 * @param string $message The error message.
	 * @param string $url The error message URL.
	 * @param bool $is_editor_sidebar Whether the error message is displayed in the form editor sidebar.
	 *
	 * @return string
	 */
	private function get_card_error_message( $message, $url = '', $is_editor_sidebar = false ) {
		$classes = $url ? ' gform_stripe_card_error' : '';

		// Form Editor Sidebar Messages
		if ( $is_editor_sidebar ) {
			return sprintf( $message, '<div class="gform-spacing gform-spacing--top-1">', '</div>', '<a href="' . esc_attr( $url ) . '" target="_blank">', '</a>' );
		}

		// Form Editor Messages
		if ( $this->is_form_editor() ) {
			return '
				<div class="ginput_container ginput_container_addon_message ginput_container_addon_message_stripe'. $classes .'">
		            <div class="gform-alert gform-alert--info gform-alert--theme-cosmos gform-spacing gform-spacing--bottom-0 gform-theme__disable">
		                <span
		                    class="gform-icon gform-icon--information-simple gform-icon--preset-active gform-icon-preset--status-info gform-alert__icon"
		                    aria-hidden="true"
		                ></span>
		                <div class="gform-alert__message-wrap">
		                    <div class="gform-alert__message">
		                        '. sprintf( $message, '<div class="gform-spacing gform-spacing--top-1">', '</div>', '<a href="' . esc_attr( $url ) . '" target="_blank">', '</a>' ) .'
		                    </div>
		                </div>
		            </div>
		        </div>';
		}

		// Frontend Messages
		return sprintf( $message, '<div class="gfield_description validation_message gfield_validation_message">', '</div>', '<a href="' . esc_attr( $url ) . '" target="_blank">', '</a>' );
	}

	/**
	 * Get an error message based on the type of authentication used.
	 *
	 * @since 4.1
	 *
	 * @return string
	 */
	private function get_api_error_message() {
		$is_form_editor = $this->is_form_editor();

		if ( gf_stripe()->is_stripe_connect_enabled() ) {
			/* translators: 1. Open div tag 2. Close div tag 3. Open link tag 4. Close link tag */
			$api_key_error = $is_form_editor ? esc_html__( 'Configuration Required%1$sTo use the Stripe field, please configure your %3$sStripe Settings%4$s.%2$s', 'gravityformsstripe' ) : esc_html__( '%1$sConfiguration Required: To use the Stripe field, please configure your %3$sStripe Settings%4$s.%2$s', 'gravityformsstripe' );
		} else {
			/* translators: 1. Open div tag 2. Close div tag 3. Open link tag 4. Close link tag */
			$api_key_error = $is_form_editor ? esc_html__( 'Configuration Required%1$sPlease check your %3$sStripe API Settings%4$s. Your Publishable Key is empty.%2$s', 'gravityformsstripe' ) : esc_html__( '%1$sConfiguration Required: Please check your %3$sStripe API Settings%4$s. Your Publishable Key is empty.%2$s', 'gravityformsstripe' );
		}

		return $api_key_error;
	}

}

GF_Fields::register( new GF_Field_Stripe_CreditCard() );
