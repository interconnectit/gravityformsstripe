
/**
 * Error handling UI and accessibility.
 */
let formId;
let ccFieldId;
let validationPlacement = null;

/**
 * @function initErrorHandler
 * @description Initializes the error handler with the provided form and field IDs.
 *
 * @param {string} formIdParam The form ID.
 * @param {string} ccFieldIdParam The field ID.
 */
function initErrorHandler( formIdParam, ccFieldIdParam ) {
	formId = formIdParam;
	ccFieldId = ccFieldIdParam;
}

/**
 * @function displayError
 * @description Displays an error message next to the invalid element.
 *
 * @param {string} invalidElementSelector The CSS selector of the element where the error message will be displayed next to.
 * @param {string} message The error message.
 */
function displayError( message ) {
	let cardContainer = gform.utils.getNode( `#gform_${ formId } .ginput_stripe_creditcard`, document, true );
	const legacyCardContainer = gform.utils.getNode( `#gform_${ formId } .ginput_container_creditcard`, document, true );
	if ( legacyCardContainer ) {
		cardContainer = legacyCardContainer;
	}

	// Handle edge case when field description exists and is below, then validation should be displayed beneath description instead of the field.
	const descriptionElement = gform.utils.getNode( `#gfield_description_${formId}_${ccFieldId}`, document, true );
	if ( getValidationPlacement() === 'below' && descriptionElement ) {
		cardContainer = descriptionElement;
	}

	if ( ! cardContainer ) {
		return;
	}

	// Add error class to the whole field.
	const fieldContainer = getFieldContainer();
	fieldContainer.classList.add( 'gfield_error' );

	// Make sure validation container exists after field container, if not create it.
	if ( ! validationContainerExists( cardContainer ) ) {
		insertValidationContainer( cardContainer );
	}

	const validationContainer = getValidationContainer( cardContainer );
	if ( ! validationContainer ) {
		return;
	}

	validationContainer.innerText = message;

	setTimeout( () => { wp.a11y.speak( message ) }, 500 );
}

/**
 * @function clearErrors
 * @description Clears all error messages and classes from the field.
 */
function clearErrors() {
	const fieldContainer = getFieldContainer();
	fieldContainer.classList.remove( 'gfield_error' );
	const validationMessages = gform.utils.getNodes( '.stripe_validation_error.validation_message', true, fieldContainer, true );
	validationMessages.forEach( ( validationMessage ) => {
		validationMessage.remove();
	} );
}

/**
 * Gets the field container.
 *
 * @function getFieldContainer
 * @description Retrieves the field container element based on the form and field IDs.
 *
 * @return {HTMLElement|null} The field container element.
 */
function getFieldContainer() {
	return gform.utils.getNode( `#field_${ formId }_${ ccFieldId }`, document, true );
}

/**
 * @function getValidationPlacement
 * @description Determines the position of the validation message (above or below the element).
 *
 * @return {string} The placement setting, 'below' or 'above'.
 */
function getValidationPlacement() {
	if ( validationPlacement ) {
		return validationPlacement;
	}

	const field = gform.utils.getNode( `#field_${ formId }_${ ccFieldId }`, document, true );
	if ( field && gform.utils.hasClassFromArray( field, [ 'field_validation_above' ] ) ) {
		validationPlacement = 'above';
	} else {
		validationPlacement = 'below';
	}

	return validationPlacement;
}

/**
 * Inserts the validation container after or before an element.
 *
 * @function insertValidationContainer
 * @description Inserts the validation container in the appropriate position relative to the element.
 *
 * @param {HTMLElement} element The element after or before which to insert the validation container.
 */
function insertValidationContainer( element ) {
	const position = getValidationPlacement();
	const validationContainer = document.createElement( 'div' );
	validationContainer.className = 'stripe_validation_error gfield_description validation_message gfield_validation_message';
	if ( position === 'below' ) {
		element.insertAdjacentElement( 'afterend', validationContainer );
	} else {
		element.insertAdjacentElement( 'beforebegin', validationContainer );
	}
}

/**
 * @function validationContainerExists
 * @description Checks if the validation container exists next to the provided element.
 *
 * @param {HTMLElement} element The element to check.
 *
 * @return {boolean} True if the validation message container exists, false otherwise.
 */
function validationContainerExists( element ) {
	if ( ! element ) {
		return false;
	}
	return getValidationContainer( element ) !== null;
}

/**
 * @function getValidationContainer
 * @description Retrieves the validation message container element, if it exists, next to or before the provided element.
 *
 * @param {HTMLElement} element The element to check.
 *
 * @return {HTMLElement|null} The validation message container element, or null if it does not exist.
 */
function getValidationContainer( element ) {
	if ( ! element ) {
		return null;
	}

	if ( getValidationPlacement() === 'below' && element.nextElementSibling ) {
		return element.nextElementSibling.matches( '.validation_message' ) ? element.nextElementSibling : null;
	}

	if ( element.previousElementSibling ) {
		return element.previousElementSibling.matches( '.validation_message' ) ? element.previousElementSibling : null;
	}

	return null;
}

export {
	initErrorHandler,
	displayError,
	clearErrors
};
