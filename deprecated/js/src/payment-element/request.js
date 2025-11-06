const request = async( data, isJson = false, action = false, nonce = false ) => {

	// Delete gform_ajax if it exists in the FormData object
	if ( typeof data.has === 'function' && data.has( 'gform_ajax' ) ) {

		// Saves a temp gform_ajax so that it can be reset later during form processing.
		data.set( 'gform_ajax--stripe-temp', data.get( 'gform_ajax' ) );

		// Remove the ajax input to prevent Gravity Forms ajax submission handler from handling the submission in the backend during Stripe's validation.
		data.delete( 'gform_ajax' );
	}

	const options = {
		method: 'POST',
		credentials: 'same-origin',
		body: data,
	};

	if ( isJson ) {
		options.headers = { 'Accept': 'application/json', 'content-type': 'application/json' }
	}

	const url = new URL( gforms_stripe_frontend_strings.ajaxurl )

	if ( action ) {
		url.searchParams.set( 'action', action )
	}

	if ( nonce ) {
		url.searchParams.set( 'nonce', nonce )
	}

	if ( gforms_stripe_frontend_strings.is_preview ) {
		url.searchParams.set( 'preview', '1' )
	}

	return await fetch(
		url.toString(),
		options
	).then(
		response => response.json()
	);
}

export default request;
