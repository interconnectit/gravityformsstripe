
/**
 * Mounts the card element into the container.
 *
 * @since 5.0
 */
const mountCard = () => {
	if ( jQuery( '.stripe-payment-element-container' ).length <= 0 || gform_stripe_payment_element_form_editor_strings.stripe_connect_enabled !== "1" ) {
		return;
	}
	const stripe = Stripe( gform_stripe_payment_element_form_editor_strings.api_key );

	if ( ! stripe) {
		jQuery( '.stripe-payment-element-container' ).text( gform_stripe_payment_element_form_editor_strings.payment_element_error );
	} else {
		const elements = stripe.elements( {
			amount: Math.round( gform_stripe_payment_element_form_editor_strings.payment_element_amount ),
			currency: gform_stripe_payment_element_form_editor_strings.payment_element_currency,
			mode:"payment",
			payment_method_types: ['card'],
		} );

		const card = elements.create( 'payment',  { 'readOnly': true } );
		card.mount( '.stripe-payment-element-container' );
		//Prevents users from interacting with the Stripe Field in the form editor
		jQuery( '.stripe-payment-element-container' ).prepend( '<div id="stripe-field-top-layer" style="position:absolute; height:100%; width:100%; z-index:100; opacity:0"></div>' );


	}
}

/**
 * Shows or hides the payment element.
 *
 * @since 4.3
 *
 * @param enabled Whether the element should be shown or hidden.
 */
const showHideElement = ( enabled = null ) => {

	if( enabled === null && gform_stripe_payment_element_form_editor_strings.stripe_connect_enabled === "1" ) {
		enabled = false;
		form.fields.forEach( ( field ) => {
			if( field.type === 'stripe_creditcard' ) {
				enabled = field.enableMultiplePaymentMethods;
			}
		} )
	}

	const $elementContainer = jQuery( '.stripe-payment-element-container' );
	const $cardContainer = jQuery( '.ginput_stripe_creditcard' );
	const elementContainerStyle = enabled ? 'block' : 'none';
	const cardContainerStyle = enabled ? 'none' : 'flex';
	const $linkEmailField = jQuery( '#link_email_field_container' );

	if ( ! enabled ) {
		$linkEmailField.hide();
	} else {
		$linkEmailField.show();
	}

	if ( $elementContainer.length ) {
		$elementContainer.get(0).style.display = elementContainerStyle;
	}
	if ( $cardContainer.length ) {
		$cardContainer.get(0).style.display = cardContainerStyle;
	}
}

/**
 * Shows or hides the sub labels and input placeholders setting as it is not needed for the payment element when its setting is on.
 *
 * Can not call this inside showHideElement because it messes up the other field while the editor is still loading, and showHideElement is called on load.
 *
 * since 4.3
 */
const showHideFieldSettings = () => {
	jQuery( '.field_setting' ).hide();
	let allSettings = getAllFieldSettings( GetSelectedField() );
	jQuery( allSettings ).show();
}

/**
 * Hooks to the field settings filter to remove the sub label classes and input placeholder classes if needed.
 *
 * @since 4.3
 *
 * @param {Array} settingsArray The field settings classes.
 * @param {Object} field        The field object.
 *
 * @return {Array}
 */
const filterSubLabelsSettingClasses = ( settingsArray, field ) => {
	if ( field.type !== 'stripe_creditcard' ) {
		return settingsArray;
	}
	const paymentElementEnabled = jQuery( '#field_enable_multiple_payment_methods' ).is(':checked');
	if (  paymentElementEnabled ) {
		settingsArray = settingsArray.filter( item => ! ['.sub_label_placement_setting', '.sub_labels_setting', '.input_placeholders_setting'].includes( item ) );
	} else {
		settingsArray.push( '.sub_labels_setting', '.sub_label_placement_setting', '.input_placeholders_setting' );
	}

	return settingsArray;
}

/**
 * Hooks to the form validation filter and validates that the field is in the last page of a multi-page form.
 *
 * @since 4.3
 *
 * @param {String}  error       The error message provided by the filter that will be returned.
 * @param {Object}  form        The form object.
 * @param {Boolean} has_product Whether the form has a product or not.
 * @param {Boolean} has_option  Whether the form has an option field or not.
 *
 * @return {String}
 */
const validateFieldPosition = ( error, form, has_product, has_option ) => {
		const lastPageFieldIndex = form.fields.findLastIndex( ( field ) => field.type === 'page' );
		const stripeFieldIndex = form.fields.findIndex( ( field ) => field.type === 'stripe_creditcard' && field.enableMultiplePaymentMethods === true );
		if ( lastPageFieldIndex  === -1  || stripeFieldIndex === -1 ) {
			return error;
		}

		if ( stripeFieldIndex < lastPageFieldIndex ) {
			error = gform_stripe_payment_element_form_editor_strings.field_position_validation_error;
		}

		return error;
}

const afterRefreshField = ( fieldId ) => {
	const stripeFieldIndex = form.fields.findIndex( ( field ) => field.type === 'stripe_creditcard' && field.enableMultiplePaymentMethods === true );
	if( stripeFieldIndex < 0 || form.fields[stripeFieldIndex].id != fieldId ) {
		return;
	}

	showHideElement();
	mountCard();
}

/**
 * Binds the functionalities needed to their corresponding events.
 *
 * @since 4.3
 */
const bindEvents = () => {

	gform.addAction( 'gform_after_refresh_field_preview', afterRefreshField );

	gform.addFilter( 'gform_editor_field_settings', filterSubLabelsSettingClasses );
	gform.addFilter( 'gform_validation_error_form_editor', validateFieldPosition );

	jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {

		if ( field.type !== 'stripe_creditcard' ) {
			return;
		}
		const is_payment_element_supported = gform_stripe_payment_element_form_editor_strings.payment_element_supported === "1";

		if ( gform_stripe_payment_element_form_editor_strings.stripe_connect_enabled !== "1" || ! is_payment_element_supported ) {
			// Adding disabled class to container.
			jQuery( '.enable_multiple_payment_methods_setting' ).addClass( 'gform-stripe--disabled' );

			// Disabling the checkbox.
			jQuery( '#field_enable_multiple_payment_methods' ).prop( 'disabled', true ).prop( 'checked', false );
			jQuery( '#link_email_field' ).prop( 'disabled', true );
		}

		if ( ! is_payment_element_supported ) {
			// Show the error message.
			setFieldError( 'enable_multiple_payment_methods_setting', 'below', gform_stripe_payment_element_form_editor_strings.payment_element_disabled_message );
		}

		const linkEmailSelect = gform.utils.getNode( '#link_email_field', document, true );
		const linkEmailSelectedId = linkEmailSelect.value;
		const optionNodes = gform.utils.getNodes( 'gform-stripe-link-email-ids' );
		optionNodes.forEach( ( option ) => { option.remove() } );
		form.fields.forEach( ( field, index ) => {
			if ( field.type === 'email' ) {
				const option = document.createElement('option');
				option.value = field.id;
				option.setAttribute( 'data-js', 'gform-stripe-link-email-ids' );
				option.textContent = `${field.label} - ${gform_stripe_payment_element_form_editor_strings.email_field_id_text}: ${field.id}`;
				linkEmailSelect.appendChild( option );
			}
		});

		linkEmailSelect.value = linkEmailSelectedId;

		jQuery('#field_enable_multiple_payment_methods').prop( 'checked', field.enableMultiplePaymentMethods ? true : false );
		jQuery('#link_email_field').val( `linkEmailFieldId` in field ? field.linkEmailFieldId : 0 );
		showHideFieldSettings();
	});

	jQuery( '#field_enable_multiple_payment_methods' ).on( 'change', function ( e ) {
		showHideElement( jQuery( this ).is( ':checked' ) );
		showHideFieldSettings();
	});

	jQuery( '#link_email_field' ).on( 'change', function ( e ) {
		const field = GetSelectedField();
		field.linkEmailFieldId = jQuery( this ).val();
	});

}

/**
 * Handles the form editor UI logic for the payment element.
 *
 * @since 4.3
 */
const paymentElementFormEditorHandler = () => {

	bindEvents();
	showHideElement();
	mountCard();
}

/**
 * Launches UI handler on page ready.
 */
jQuery( document ).ready( function () {
	paymentElementFormEditorHandler();
})

/**
 * Reset the UI after adding a new field.
 */
jQuery( document ).on( 'gform_field_added',  function( event, form, field ) {
	if ( field.type === 'stripe_creditcard' ) {
		showHideElement();
		mountCard();
	}
});
