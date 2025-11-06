import request from './request';
import { clearErrors } from './../error-handler';
export default class StripePaymentsHandler {

	/**
	 * StripePaymentsHandler constructor
	 *
	 * @since 5.0
	 *
	 * @param {String} apiKey The stripe API key.
	 * @param {Object} GFStripeObj The stripe addon JS object.
	 */
	constructor( apiKey, GFStripeObj ) {
		this.GFStripeObj = GFStripeObj;
		this.apiKey = apiKey;
		this.stripe = null;
		this.elements = null;
		this.card = null;
		this.paymentMethod = null;
		this.draftId = null;
		// A workaround so we can call validate method from outside this class while still accessing the correct scope.
		this.validateForm = this.validate.bind( this );
		this.handlelinkEmailFieldChange = this.reInitiateLinkWithEmailAddress.bind( this );
		this.order = {
			'recurringAmount': 0,
			'paymentAmount': 0,
		};
		// The object gets initialized everytime frontend feeds are evaluated so we need to clear any previous errors.
		clearErrors();

		if ( ! this.initStripe() || gforms_stripe_frontend_strings.stripe_connect_enabled !== "1" ) {
			return;
		}

		// Create the payment element and mount it.
		this.card = this.elements.create( 'payment' );

		// If an email field is mapped to link, bind it to initiate link on change.
		if ( GFStripeObj.activeFeed.link_email_field_id ) {
			const emailField = document.querySelector( '#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id );
			const email      = emailField ? emailField.value : '';
			this.handlelinkEmailFieldChange( { target: { value: email } } );
		} else {
			this.link = null;
		}

		this.mountCard();
		this.bindEvents();
	}

	/**
	 * @function getStripeCoupon
	 * @description Retrieves the cached coupon associated with the entered coupon code.
	 *
	 * @since 5.1
	 * @returns {object} Returns the cached coupon object or undefined if the coupon is not found.
	 */
	getStripeCoupon	() {
		const coupons = window.stripeCoupons || {};
		const currentCoupon = this.getStripeCouponCode();
		const foundCoupon = Object.keys(coupons).find(coupon => {
			return coupon.localeCompare(currentCoupon, undefined, { sensitivity: 'accent' }) === 0;
		});

		return foundCoupon ? coupons[foundCoupon] : undefined;
	}

	/**
	 * @function getStripeCouponInput
	 * @description Retrieves the coupon input associated with the active feed.
	 *
	 * @since 5.1
	 *
	 * @returns {HTMLInputElement} Returns the coupon input or null if the coupon input is not found.
	 */
	getStripeCouponInput() {
		const couponField = document.querySelector( '#field_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.coupon );
		return couponField ? couponField.querySelector( 'input' ) : null;
	}

	/**
	 * @function getStripeCouponCode
	 * @description Retrieves the coupon code from the coupon input associated with the active feed.
	 *
	 * @since 5.1
	 *
	 * @returns {string} Returns the coupon code or an empty string if the coupon input is not found.
	 */
	getStripeCouponCode() {
		const couponInput = this.getStripeCouponInput();
		if ( ! couponInput ) {
			return '';
		}
		if ( couponInput.className === 'gf_coupon_code' ) {
			const couponCode = couponInput ? document.querySelector( '#gf_coupon_codes_' + this.GFStripeObj.formId ).value : null;
			return couponCode;
		}

		return couponInput ? couponInput.value : '';
	}

	/**
	 * @function bindStripeCoupon
	 * @description Binds the coupon input change event.
	 *
	 * @since 5.1
	 *
	 * @returns {void}
	 */
	bindStripeCoupon() {

		// Binding coupon input event if it has not been bound before.
		const couponInput = this.getStripeCouponInput();
		if ( couponInput && ! couponInput.getAttribute( 'data-listener-added' ) ) {
			couponInput.addEventListener( 'blur', this.handleCouponChange.bind( this ) );
			couponInput.setAttribute( 'data-listener-added', true );
		}
	}

	/**
	 * @function handleCouponChange
	 * @description Handles the coupon input change event.
	 *
	 * @since 5.1
	 *
	 * @param event The event object.
	 * @returns {Promise<void>}
	 */
	async handleCouponChange( event ) {

		if( this.getStripeCouponInput() !== event.target ) {
			return;
		}

		if ( event.target.classList.contains( 'gf_coupon_code' ) ) {
			event.target.value = event.target.value.toUpperCase();
		}

		await this.updateStripeCoupon( event.target.value );

		gformCalculateTotalPrice( this.GFStripeObj.formId );
	}

	/**
	 * @function updateStripeCoupon
	 * @description Retrieves a coupon from Stripe based on the coupon_code specified and caches it in the window object.
	 *
	 * @since 5.1
	 *
	 * @param {string} coupon_code The coupon code
	 * @returns {Promise<void>}
	 */
	async updateStripeCoupon( coupon_code ) {

		// If the coupon code is empty, we don't need to do anything.
		if ( ! coupon_code ) {
			return;
		}

		// Initializing stripeCoupons object if it doesn't exist.
		if( ! window.stripeCoupons ){
			window.stripeCoupons = {};
		}

		// If coupon has already been retrieved from Stripe, abort.
		if ( window.stripeCoupons[ coupon_code ] ) {
			return;
		}

		// Retreive coupon from Stripe and store it in the window object.
		const response = await request(
			JSON.stringify( {
				'coupon'  : coupon_code,
				'feed_id' : this.GFStripeObj.activeFeed.feedId,
			} ),
			true,
			'gfstripe_get_stripe_coupon',
			gforms_stripe_frontend_strings.get_stripe_coupon_nonce,
		);

		window.stripeCoupons[ coupon_code ] = response.data;
	}

	/**
	 * Creates the Stripe object with the given API key.
	 *
	 * @since 5.0
	 *
	 * @return {boolean}
	 */
	async initStripe() {
		this.stripe = Stripe( this.apiKey );

		const initialPaymentInformation = this.GFStripeObj.activeFeed.initial_payment_information;
		// Round the minimum amount to prevent an error on the stripe side.
		initialPaymentInformation.amount = Math.round( initialPaymentInformation.amount );

		const appearance = this.GFStripeObj.cardStyle;

		if ( 'payment_method_types' in initialPaymentInformation ) {
			initialPaymentInformation.payment_method_types = Object.values( initialPaymentInformation.payment_method_types );
		}

		this.elements = this.stripe.elements( { ...initialPaymentInformation, appearance } );

		return true;
	}

	/**
	 * Mounts the card element to the field node.
	 *
	 * @since 5.0
	 */
	mountCard() {
		this.card.mount( '#' + this.GFStripeObj.GFCCField.attr('id') );
	}

	/**
	 * Creates a container node for the link element and mounts it.
	 *
	 * @since 5.0
	 */
	mountLink() {
		if ( this.link === null ) {
			return;
		}
		if ( document.querySelectorAll( '.stripe-payment-link' ).length <= 0 ) {
			const linkDiv = document.createElement( 'div' );
			linkDiv.setAttribute( 'id', 'stripe-payment-link' );
			linkDiv.classList.add( 'StripeElement--link' );
			this.GFStripeObj.GFCCField.before( jQuery( linkDiv ) );
		}

		this.link.mount( '#stripe-payment-link' );
	}

	/**
	 * Binds event listeners.
	 *
	 * @since 5.0
	 */
	async bindEvents() {
		if ( this.card ) {
			this.card.on( 'change', ( event ) => {
					if ( this.paymentMethod !== null ) {
						clearErrors();
					}
					this.paymentMethod = event;
				}
			);
		}

		// Binding events for Stripe Coupon.
		this.bindStripeCoupon();

		const emailField = document.querySelector( '#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id );

		if ( emailField === null ) {
			return;
		}

		emailField.addEventListener( 'blur', this.handlelinkEmailFieldChange );

		window.addEventListener( 'load', async function () {
		const emailField = document.querySelector( '#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id );
			if (
				(
					String( emailField.value )
						.toLowerCase()
						.match(
							/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
						)
				)
				&& this.GFStripeObj.isLastPage()
			) {
				this.handlelinkEmailFieldChange( { target: { value: emailField.value } } );
			}

		}.bind( this ) );

	}

	/**
	 * Destroys the current instance of link and creates a new one with value extracted from the passed event.
	 *
	 * @since 5
	 *
	 * @param {Object} event an object that contains information about the email input.
	 * @return {Promise<void>}
	 */
	async reInitiateLinkWithEmailAddress( event ) {

		if ( this.GFStripeObj.isCreditCardOnPage() === false ) {
			return;
		}

		// If there is a Link instance, destroy it.
		this.destroyLink();

		const emailAddress = event.target.value;
		if ( emailAddress ) {
			this.link = await this.elements.create( "linkAuthentication", { defaultValues: { email: emailAddress } } );
			this.mountLink();
			this.GFStripeObj.GFCCField.siblings( '.gfield #stripe-payment-link' ).addClass( 'visible' );
		}
	}
	/**
	 * Validates the form.
	 *
	 * @since 5.0
	 *
	 * @param {Object} event The form event object.
	 *
	 * @return {Promise<boolean>}
	 */
	async validate( event ) {
		// If this is an ajax form submission, we just need to submit the form as everything has already been handled.
		const form = jQuery( '#gform_' + this.GFStripeObj.formId );
		if ( form.data( 'isAjaxSubmitting' ) ) {
			form.submit();
			return;
		}

		// Make sure the required information are entered.
		// Link stays incomplete even when email is entered, and it will fail with a friendly message when the confirmation request fails, so skip its frontend validation.
		if ( ! this.paymentMethod.complete && this.paymentMethod.value.type !== 'link' ) {
			this.failWithMessage( gforms_stripe_frontend_strings.payment_incomplete, this.GFStripeObj.formId )
			return false;
		}

		gformAddSpinner( this.GFStripeObj.formId );
		const response = await request( this.getFormData( event.target ) );

		if ( response === -1 ) {
			this.failWithMessage( gforms_stripe_frontend_strings.invalid_nonce, this.GFStripeObj.formId );

			return false;
		}

		if ( 'success' in response && response.success === false ) {
			this.failWithMessage( gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId )

			return false;
		}

		// Invoice for trials are automatically paid.
		if ( 'invoice_id' in response.data && response.data.invoice_id !== null && 'resume_token' in response.data ) {
			const redirect_url = new URL( window.location.href );
			redirect_url.searchParams.append( 'resume_token', response.data.resume_token );
			redirect_url.searchParams.append( 'tracking_id', response.data.tracking_id );
			window.location.href = redirect_url.href;
		}

		const is_valid_intent = 'intent' in response.data
									&& response.data.intent !== false
									&& response.data.intent != null
									&& 'client_secret' in response.data.intent;


		const is_valid_submission = 'data' in response
									&& 'is_valid' in response.data
									&& response.data.is_valid
									&& 'resume_token' in response.data

		const is_spam = 'is_spam' in response.data && response.data.is_spam;

		if ( ! is_valid_intent && ! is_spam && is_valid_submission  ) {
			this.failWithMessage( gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId )
			return false;
		}


		if ( is_valid_submission ) {

			// Reset any errors.
			this.resetFormValidationErrors();
			this.draftId = response.data.resume_token;
			// Validate Stripe coupon, if there is a setup fee or trial, the coupon won't be applied to the current payment, so pass validation as it is all handled in the backend.
			if (
				this.GFStripeObj.activeFeed.hasTrial !== '1' &&
				! this.GFStripeObj.activeFeed.setupFee &&
				! this.isValidCoupon( response.data.total )
			) {
				this.failWithMessage( gforms_stripe_frontend_strings.coupon_invalid, this.GFStripeObj.formId )

				return false;
			}

			// Do not confirm payment if this is a spam submission.
			if ( is_spam ) {
				// For spam submissions, redirect to the confirmation page without confirming the payment. This will process the submission as a spam entry without capturing the payment.
				this.handleRedirect( this.getRedirectUrl( response.data.resume_token ) );
			} else {
				// For non-spam submissions, confirm the payment and redirect to the confirmation page.
				this.confirm( response.data );
			}

		} else {
			// Form is not valid, do a normal submit to render the validation errors markup in backend.
			event.target.submit();
		}
	}


	/**
	 * @function isValidCoupon
	 * @description Validates the coupon code.
	 *
	 * @since 5.1
	 *
	 * @param {number} payment_amount Payment amount calculated by Stripe.
	 *
	 * @returns {boolean} Returns true if the coupon is valid, returns false otherwise.
	 */
	isValidCoupon( payment_amount ) {
		const coupon = this.getStripeCoupon();
		if ( ! coupon ) {
			return true;
		}

		return coupon.is_valid && payment_amount == this.order.paymentAmount;
	}

	/**
	 * Creates a FormData object containing the information required to validate the form and start the checkout process on the backend.
	 *
	 * @since 5.0
	 *
	 * @param {Object} form The form object.
	 *
	 * @return {FormData}
	 */
	getFormData( form ) {
		const formData = new FormData( form );
		// if gform_submit exist in the request, GFFormDisplay::process_form() will be called even before the AJAX handler.
		formData.delete( 'gform_submit' );
		// Append the payment data to the form.
		const appendParams = {
			'action': 'gfstripe_validate_form',
			'feed_id': this.GFStripeObj.activeFeed.feedId,
			'form_id': this.GFStripeObj.formId,
			'tracking_id': Math.random().toString( 36 ).slice( 2, 10 ),
			'payment_method': this.paymentMethod.value.type,
			'nonce': gforms_stripe_frontend_strings.validate_form_nonce
		}

		Object.keys( appendParams ).forEach( ( key ) => {
			formData.append( key, appendParams[ key ] );
		} );

		return formData;
	}

	/**
	 * Updates the payment information amount.
	 *
	 * @since 5.1
	 * @since 5.3 Added the updatedPaymentInformation filter.
	 *
	 * @param {Double} newAmount The updated amount.
	 */
	updatePaymentAmount( newAmount ) {
		if ( newAmount <= 0 || this.GFStripeObj.activeFeed.initial_payment_information.mode === 'setup' ) {
			return;
		}
		// Get amount in cents (or the equivalent subunit for other currencies)
		let total = newAmount * 100;
		// Round total to two decimal places.
		total = Math.round( total * 100 ) / 100;

		let updatedPaymentInformation = {
			amount: total,
		};

		/**
		 * Filters the payment information before updating it.
		 *
		 * @since 5.3
		 *
		 * @param {Object} updatedPaymentInformation The object that contains the updated payment information, for possible values, @see https://docs.stripe.com/js/elements_object/update#elements_update-options
		 * @param {Object} initialPaymentInformation The initial payment information.
		 * @param {int} feedId The feed ID.
		 * @param {int} formId The form ID.
		 *
		 * @return {Object} The updated payment information.
		 */
		updatedPaymentInformation = window.gform.applyFilters( 'gform_stripe_payment_element_updated_payment_information', updatedPaymentInformation, this.GFStripeObj.activeFeed.initial_payment_information, this.GFStripeObj.activeFeed.feedId, this.GFStripeObj.formId );

		this.elements.update( updatedPaymentInformation );
	}

	/**
	 * @function applyStripeCoupon
	 * @description Applies the coupon discount to the total.
	 *
	 * @since 5.1
	 *
	 * @param {number} total The payment amount.
	 * @returns {number} Returns the updated total.
	 */
	applyStripeCoupon( total ) {

		const coupon = this.getStripeCoupon();
		if ( ! coupon || ! coupon.is_valid ) {
			return total;
		}

		if( coupon.percentage_off ) {
			total = total - ( total * ( coupon.percentage_off / 100 ) );
		} else if ( coupon.amount_off ) {
			total = total - coupon.amount_off;
		}

		return total;
	}

	/**
	 * Calls stripe confirm payment or confirm setup to attempt capturing the payment after form validation passed.
	 *
	 * @since 5.0
	 * @since 5.4.0 Updated the method parameter, so it received the whole confirmData object instead of just the resume_token and client secret.
	 *
	 * @param {Object} confirmData The confirmData object that contains the resume_token, client secret and intent information.
	 *
	 * @return {Promise<void>}
	 */
	async confirm( confirmData ) {

		// Prepare the return URL.
		const redirect_url = this.getRedirectUrl( confirmData.resume_token );
		redirect_url.searchParams.append( 'tracking_id', confirmData.tracking_id );

		const { error: submitError } = await this.elements.submit();
		if ( submitError ) {
			this.failWithMessage( submitError.message , this.GFStripeObj.formId )
			return;
		}
		// Gather the payment data.
		const paymentData = {
			elements: this.elements,
			clientSecret: confirmData.intent.client_secret,
			confirmParams: {
				return_url: redirect_url.toString(),
				payment_method_data: {
					billing_details: {
						address: {
							line1: GFMergeTag.replaceMergeTags( this.GFStripeObj.formId, this.getBillingAddressMergeTag( this.GFStripeObj.activeFeed.address_line1 ) ),
							line2: GFMergeTag.replaceMergeTags( this.GFStripeObj.formId, this.getBillingAddressMergeTag( this.GFStripeObj.activeFeed.address_line2 ) ),
							city: GFMergeTag.replaceMergeTags( this.GFStripeObj.formId, this.getBillingAddressMergeTag( this.GFStripeObj.activeFeed.address_city ) ),
							state: GFMergeTag.replaceMergeTags( this.GFStripeObj.formId, this.getBillingAddressMergeTag( this.GFStripeObj.activeFeed.address_state ) ),
							postal_code: GFMergeTag.replaceMergeTags( this.GFStripeObj.formId, this.getBillingAddressMergeTag( this.GFStripeObj.activeFeed.address_zip ) ),
						},
					},
				},
			},
			// let Stripe handle redirection only if the payment method  requires redirection to a third party page,
			// Otherwise, the add-on will handle the redirection.
			redirect: 'if_required',
		};


		/**
		 * The promise that returns from calling stripe.confirmPayment or stripe.confirmSetup.
		 *
		 * If the payment method used requires redirecting the user to a third party page,
		 * this promise will never resolve, as confirmPayment or confirmSetup redirect the user to the third party page.
		 *
		 * @since 5.0.0
		 *
		 * @type {Promise}
		 */
		let paymentResult = {};
		let isSetupIntent = confirmData.intent.id.indexOf( 'seti_' ) === 0;
		try {
			paymentResult = isSetupIntent ? await this.stripe.confirmSetup( paymentData ) : await this.stripe.confirmPayment( paymentData );
		} catch ( e ) {
			console.log( e );
			this.failWithMessage( gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId )
		}

		// If we have a paymentIntent or a setupIntent in the result, the process was successful.
		// Note that confirming could be successful but the intent status is still 'processing' or 'pending'.
		if ( 'paymentIntent' in paymentResult || 'setupIntent' in paymentResult ) {
			this.handlePaymentRedirect( paymentResult, redirect_url );
		} else {
			await this.handleFailedPayment( paymentResult );
		}
	}

	/**
	 * Redirects the user to the confirmation page after the payment intent is confirmed.
	 *
	 * This method will never be executed if the payment method used requires redirecting the user to a third party page.
	 *
	 * @since 5.0
	 * @since 5.2 Added the redirect_url parameter.
	 * @since 5.4 Renamed the function from handlePayment to handlePaymentRedirect.
	 *
	 * @param {Object} paymentResult The result of confirming a payment intent or a setup intent.
	 * @param {URL} redirect_url  The redirect URL the user will be taken to after confirmation.
	 */
	handlePaymentRedirect( paymentResult, redirect_url ) {
		const intent = paymentResult.paymentIntent ? paymentResult.paymentIntent : paymentResult.setupIntent;
		// Add parameters required for entry processing in the backend.
		const intentTypeString = intent.id.indexOf( 'seti_' ) === 0 ? 'setup' : 'payment'
		redirect_url.searchParams.append( intentTypeString + '_intent', intent.id );
		redirect_url.searchParams.append( intentTypeString + '_intent_client_secret', intent.client_secret );
		redirect_url.searchParams.append( 'redirect_status', intent.status ? 'succeeded' : 'pending' );

		this.handleRedirect( redirect_url );
	}

	/**
	 * Redirects the user to the confirmation page.
	 *
	 * @since 5.4.1
	 *
	 * @param {URL} redirect_url  The redirect URL the user will be taken to after confirmation.
	 */
	handleRedirect( redirect_url ) {

		// If this is not an AJAX embedded form, redirect the user to the confirmation page.
		if ( ! this.isAjaxEmbed( this.GFStripeObj.formId ) ) {
			window.location.href = redirect_url.toString();
		} else {
			// AJAX embeds are handled differently, we need to update the form's action with the redirect URL, and submit it inside the AJAX IFrame.
			jQuery( '#gform_' + this.GFStripeObj.formId ).attr( 'action' , redirect_url.toString() );
			// Prevent running same logic again after submitting the form.
			jQuery( '#gform_' + this.GFStripeObj.formId ).data( 'isAjaxSubmitting' , true );
			// Keeping this input makes the backend thinks it is not an ajax form, so we need to remove it.
			jQuery( '#gform_' + this.GFStripeObj.formId ).find( '[name="gform_submit"]' ).remove();
			// Form will be submitted inside the IFrame, once IFrame content is updated, the form element will be replaced with the content of the IFrame.
			jQuery( '#gform_' + this.GFStripeObj.formId ).submit();
		}
	}

	/**
	 * Returns the URL with the resume token appended to it.
	 *
	 * @since 5.4.1
	 *
	 * @param resume_token The resume token to append to the URL.
	 *
	 * @returns {URL} The URL with the resume token appended to it.
	 */
	getRedirectUrl( resume_token ) {
		const redirect_url = new URL( window.location.href );
		redirect_url.searchParams.append( 'resume_token', resume_token );
		redirect_url.searchParams.append( 'feed_id', this.GFStripeObj.activeFeed.feedId );
		redirect_url.searchParams.append( 'form_id', this.GFStripeObj.formId );
		return redirect_url;
	}

	/**
	 * Handles a failed payment attempt.
	 *
	 * @since 5.0
	 *
	 * @param {Object} paymentResult The result of confirming a payment intent or a setup intent.
	 */
	async handleFailedPayment( paymentResult ) {
		let errorMessage = '';
		if ( 'error' in paymentResult && 'message' in paymentResult.error ) {
			errorMessage = paymentResult.error.message;
		}
		this.failWithMessage( errorMessage, this.GFStripeObj.formId );
		// Delete the draft entry created.
		let response = request(
			JSON.stringify( { 'draft_id': this.draftId } ),
			true,
			'gfstripe_delete_draft_entry',
			gforms_stripe_frontend_strings.delete_draft_nonce
		);
		// If rate limiting is enabled, increase the errors number at the backend side, and set the new count here.
		if ( this.GFStripeObj.hasOwnProperty( 'cardErrorCount' ) ) {
			response = await request(
				JSON.stringify( { 'increase_count': true } ),
				true ,
				'gfstripe_payment_element_check_rate_limiting',
				gforms_stripe_frontend_strings.rate_limiting_nonce
			);
			this.GFStripeObj.cardErrorCount = response.data.error_count;
		}
	}

	/**
	 * Destroys the stripe objects and removes any DOM nodes created while initializing them.
	 *
	 * @since 5.0
	 */
	destroy() {
		if ( this.card ) {
			this.card.destroy();
		}

		this.destroyLink();
	}

	/**
	 *  Destroys the Stripe payment link and removes any DOM nodes created while initializing it.
	 *
	 *  @since 5.4.0
	 */
	destroyLink() {
		if ( this.link ) {
			this.link.destroy();
			this.link = null;

			const linkContainer = this.GFStripeObj.GFCCField.siblings( '.gfield #stripe-payment-link' );
			if ( linkContainer ) {
				linkContainer.remove();
			}
		}
	}

	/**
	 * Removes the validation error messages from the form fields.
	 *
	 * @since 5.0
	 */
	resetFormValidationErrors() {
		document.querySelectorAll( '.gform_validation_errors, .validation_message' ).forEach( ( el ) => { el.remove() } );
		document.querySelectorAll( '.gfield_error' ).forEach( ( el ) => { el.classList.remove( 'gfield_error' ) } );
	}

	/**
	 * Displays an error message if the flow failed at any point, also clears the loading indicator and resets the form data attributes.
	 *
	 * @since 5.0
	 *
	 * @param {String} message The error message to display.
	 * @param {int}    formId The form ID.
	 */
	failWithMessage( message, formId ) {
		message = message ? message : gforms_stripe_frontend_strings.failed_to_process_payment;
		this.GFStripeObj.displayStripeCardError( { error : { message : message } } );
		this.GFStripeObj.resetStripeStatus( jQuery( '#gform_' + formId ), formId, true );
		jQuery( '#gform_ajax_spinner_' + formId ).remove();
	}

	/**
	 * Returns the merge tag for the billing address.
	 *
	 * @since 5.0
	 *
	 * @param field The billing address field.
	 *
	 * @return {string} The merge tag for the billing address.
	 */
	getBillingAddressMergeTag( field ) {

		if ( field === '' ) {
			return '';
		}

		return '{:' + field + ':value}';
	}

	/**
	 * Gets the order data.
	 *
	 * The order contains the following properties
	 * 	paymentAmount: The amount of the payment that will be charged after form submission.
	 * 	recurringAmount: If this is a subscription, this is the recurring amount.
	 *
	 * @since 5.1
	 *
	 * @param total The form total.
	 * @param formId The current form id.
	 *
	 * @return {Object} The order data.
	 */
	getOrderData( total, formId ) {

		if ( ! _gformPriceFields[ formId ] || this.GFStripeObj.activeFeed === null ) {
			return this.order;
		}

		const setUpFieldId = this.GFStripeObj.activeFeed.setupFee;
		let setupFee = 0;
		let productTotal = 0;
		const isTrial = this.GFStripeObj.activeFeed.hasTrial;


		// If this is the setup fee field, or the shipping field, don't add to total.
		if ( setUpFieldId ) {
			const setupFeeInfo = this.GFStripeObj.getProductFieldPrice( formId, this.GFStripeObj.activeFeed.setupFee );
			setupFee = setupFeeInfo.price * setupFeeInfo.qty;
			// If this field is a setup fee, subtract it from total, so it is not added to the recurring amount.
			total -= setupFee;
		}

		if ( this.GFStripeObj.activeFeed.paymentAmount === 'form_total' ) {
			this.order.recurringAmount = total;
			if ( this.isTextCoupon() ) {
				this.order.recurringAmount = this.applyStripeCoupon( this.order.recurringAmount );
			}
		} else {
			this.order.recurringAmount = gformCalculateProductPrice( formId, this.GFStripeObj.activeFeed.paymentAmount );
			this.order.recurringAmount = this.applyStripeCoupon( this.order.recurringAmount );
		}

		if ( isTrial === '1' ) {
			this.order.paymentAmount = setupFee;
		} else {
			this.order.paymentAmount = this.order.recurringAmount + setupFee;
		}

		return this.order;
	}

	isTextCoupon() {
		const coupon = this.getStripeCouponInput();
		if ( ! coupon ) {
			return false;
		}

		return ! coupon.classList.contains( 'gf_coupon_code' )
	}

	/**
	 * Decides whether the form is embedded with the AJAX option on or not.
	 *
	 * Since 5.2
	 *
	 * @param {integer} formId The form ID.
	 * @returns {boolean}
	 */
	isAjaxEmbed( formId ) {
		return jQuery( '#gform_ajax_frame_' + formId ).length >= 1;
	}
}
