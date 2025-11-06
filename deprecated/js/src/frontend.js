/**
 * Front-end Script
 */

import StripePaymentsHandler from "./payment-element/stripe-payments-handler";
import { initErrorHandler, clearErrors, displayError } from "./error-handler";
window.GFStripe = null;

gform.extensions = gform.extensions || {};
gform.extensions.styles = gform.extensions.styles || {};
gform.extensions.styles.gravityformsstripe = gform.extensions.styles.gravityformsstripe || {};

(function ($) {

	GFStripe = function (args) {

		for ( var prop in args ) {
			if ( args.hasOwnProperty( prop ) )
				this[ prop ] = args[ prop ];
		}

		this.form = null;

		this.activeFeed = null;

		this.GFCCField = null;

		this.stripeResponse = null;

		this.hasPaymentIntent = false;

		this.stripePaymentHandlers = {};

		initErrorHandler( this.formId, this.ccFieldId );
		this.cardStyle = this.cardStyle || {};

		gform.extensions.styles.gravityformsstripe[ this.formId ] = gform.extensions.styles.gravityformsstripe[ this.formId ] || {};

		const componentStyles = Object.keys( this.cardStyle ).length > 0 ? JSON.parse( JSON.stringify( this.cardStyle ) ) : gform.extensions.styles.gravityformsstripe[ this.formId ][ this.pageInstance ] || {};

		this.setComponentStyleValue = function ( key, value, themeFrameworkStyles, manualElement ) {
			// Helper to resolve CSS property key
			const resolveKey = ( key ) => key === 'fontSmoothing' ? '-webkit-font-smoothing' : key.replace( /([a-z])([A-Z])/g, '$1-$2' ).toLowerCase();

			// If the value provided is a custom property let's begin
			if ( value.indexOf( '--' ) === 0 ) {
				const computedValue = themeFrameworkStyles.getPropertyValue( value );

				// If we have a computed end value from the custom property, let's use that
				if ( computedValue ) {
					return computedValue.trim();
				}

				// Otherwise, let's use a provided element or the form wrapper
				// along with the key to nab the computed end value for the CSS property
				const selector = manualElement ? getComputedStyle( manualElement ) : themeFrameworkStyles;
				return selector.getPropertyValue( resolveKey( key ) ).trim();
			}

			// Otherwise let's treat the provided value as the actual CSS value wanted
			return value.trim();
		};

		this.setComponentStyles = function ( obj, objKey, parentKey ) {
			// If our object doesn't have any styles specified, let's bail here
			if ( Object.keys( obj ).length === 0 ) {
				return;
			}

			// Grab the computed styles for the form, which the global CSS API and theme framework are scoped to
			const form = document.getElementById( 'gform_' + this.formId );
			const themeFrameworkStyles = getComputedStyle( form );

			// Grab the first form control in the form for fallback CSS property value computation
			const firstFormControl = form.querySelector( '.gfield input' );

			// Helper method to handle outline style properties and turn it into Stripe's
			// required shorthand outline property
			const processOutlineProperty = ( target, key, value ) => {
				if ( ! this.outlineProperties ) {
					this.outlineProperties = { value: '', count: 0 };
				}

				this.outlineProperties.value += this.outlineProperties.count > 0 ? ` ${ value }` : value;
				this.outlineProperties.count++;

				if ( this.outlineProperties.count === 3 ) {
					target[ 'outline' ] = this.outlineProperties.value;

					// Reset for future calls
					this.outlineProperties = { value: '', count: 0 };
				}
			};

			// Note, this currently only supports three levels deep of object nesting.
			Object.keys( obj ).forEach( ( key ) => {
				// Handling of keys that are objects with additional key/value pairs
				if ( typeof obj[ key ] === 'object' ) {

					// Create object for top level key
					if ( ! parentKey ) {
						this.cardStyle[ key ] = {};
					}

					// Create object for second level key
					if ( parentKey ) {
						this.cardStyle[ parentKey ][ key ] = {};
					}

					const objPath = parentKey ? parentKey : key;

					// Recursively pass each key's object through our method for continued processing
					this.setComponentStyles( obj[ key ], key, objPath );

					return;
				}

				// Handling of keys that are not objects and need their value to be set
				let value = '';
				const updateStyle = ( target, keyPath ) => {
					value = this.setComponentStyleValue( key, keyPath, themeFrameworkStyles, firstFormControl );
					if ( value ) {
						if ( [ 'outlineWidth', 'outlineStyle', 'outlineColor' ].includes( key ) ) {
							delete target[ key ];
							processOutlineProperty( target, key, value );
						} else {
							target[ key ] = value;
						}
					}
				};

				if ( parentKey && objKey && objKey !== parentKey ) {
					updateStyle( this.cardStyle[ parentKey ][ objKey ], componentStyles[ parentKey ][ objKey ][ key ] );
				} else if ( parentKey ) {
					updateStyle( this.cardStyle[ parentKey ], componentStyles[ parentKey ][ key ] );
				} else {
					updateStyle( this.cardStyle, componentStyles[ key ] );
				}
			} );
		};

		this.init = async function () {

			this.setComponentStyles( componentStyles );

			if ( !this.isCreditCardOnPage() ) {
				if ( this.stripe_payment === 'stripe.js' || ( this.stripe_payment === 'elements' && !$( '#gf_stripe_response' ).length ) ) {
					return;
				}
			}

			var GFStripeObj = this, activeFeed = null, feedActivated = false,
				hidePostalCode = false, apiKey = this.apiKey;

			this.form = $( '#gform_' + this.formId );
			this.GFCCField = $( '#input_' + this.formId + '_' + this.ccFieldId + '_1' );

			gform.addAction( 'gform_frontend_feeds_evaluated', async function ( feeds, formId ) {
				if ( formId !== GFStripeObj.formId ) {
					return;
				}

				activeFeed = null;
				feedActivated = false;
				hidePostalCode = false;

				for ( var i = 0; i < Object.keys( feeds ).length; i++ ) {
					if ( feeds[ i ].addonSlug === 'gravityformsstripe' && feeds[ i ].isActivated ) {
						feedActivated = true;

						for ( var j = 0; j < Object.keys( GFStripeObj.feeds ).length; j++ ) {
							if ( GFStripeObj.feeds[ j ].feedId === feeds[ i ].feedId ) {
								activeFeed = GFStripeObj.feeds[ j ];

								break;
							}
						}
						apiKey = activeFeed.hasOwnProperty( 'apiKey' ) ? activeFeed.apiKey : GFStripeObj.apiKey;
						GFStripeObj.activeFeed = activeFeed;

						gformCalculateTotalPrice( formId );

						if ( GFStripeObj.stripe_payment == 'payment_element' ) {
							GFStripeObj.stripePaymentHandlers[ formId ] = new StripePaymentsHandler( apiKey, GFStripeObj );
						} else if ( GFStripeObj.stripe_payment === 'elements' ) {
							stripe = Stripe( apiKey );
							elements = stripe.elements();

							hidePostalCode = activeFeed.address_zip !== '';

							// If Stripe Card is already on the page (AJAX failed validation, or switch frontend feeds),
							// Destroy the card field so we can re-initiate it.
							if ( card != null && card.hasOwnProperty( '_destroyed' ) && card._destroyed === false ) {
								card.destroy();
							}

							// Clear card field errors before initiate it.
							clearErrors();

							card = elements.create(
								'card',
								{
									classes: GFStripeObj.cardClasses,
									style: GFStripeObj.cardStyle,
									hidePostalCode: hidePostalCode
								}
							);

							if ( $( '.gform_stripe_requires_action' ).length ) {
								if ( $( '.ginput_container_creditcard > div' ).length === 2 ) {
									// Cardholder name enabled.
									$( '.ginput_container_creditcard > div:last' ).hide();
									$( '.ginput_container_creditcard > div:first' ).html( '<p><strong>' + gforms_stripe_frontend_strings.requires_action + '</strong></p>' );
								} else {
									$( '.ginput_container_creditcard' ).html( '<p><strong>' + gforms_stripe_frontend_strings.requires_action + '</strong></p>' );
								}

								// Add a spinner next to the validation message and disable the submit button until we are over with 3D Secure.
								if ( jQuery( '#gform_' + formId + '_validation_container h2 .gform_ajax_spinner').length <= 0 ) {
									jQuery( '#gform_' + formId + '_validation_container h2' ).append( '<img id="gform_ajax_spinner_' + formId + '"  class="gform_ajax_spinner" src="' + gf_global.spinnerUrl + '" alt="" />');
									jQuery( '#gform_submit_button_' + formId ).prop( 'disabled' , true );
								}

								// Update legacy close icon to an info icon.
								const $iconSpan = jQuery( '#gform_' + formId + '_validation_container h2 .gform-icon.gform-icon--close' );
								const isThemeFrameWork = jQuery( '.gform-theme--framework' ).length;
								console.log( isThemeFrameWork );
								console.log( $iconSpan );
								if ( $iconSpan.length && ! isThemeFrameWork ) {
									$iconSpan.removeClass( 'gform-icon--close' ).addClass( 'gform-icon--info' );
								}

								GFStripeObj.scaActionHandler( stripe, formId );
							} else {
								card.mount( '#' + GFStripeObj.GFCCField.attr( 'id' ) );

								card.on( 'change', function ( event ) {
									GFStripeObj.displayStripeCardError( event );
								} );
							}

						} else if ( GFStripeObj.stripe_payment == 'stripe.js' ) {
							Stripe.setPublishableKey( apiKey );
							break;
						}

						break; // allow only one active feed.
					}
				}

				if ( !feedActivated ) {
					if ( GFStripeObj.stripe_payment === 'elements' || GFStripeObj.stripe_payment === 'payment_element' ) {
						if ( elements != null && card === elements.getElement( 'card' ) ) {
							card.destroy();
						}

						if ( GFStripeObj.isStripePaymentHandlerInitiated( formId ) ) {
							GFStripeObj.stripePaymentHandlers[ formId ].destroy();
						}
						displayError( gforms_stripe_frontend_strings.no_active_frontend_feed );
					}

					// remove Stripe fields and form status when Stripe feed deactivated
					GFStripeObj.resetStripeStatus( GFStripeObj.form, formId, GFStripeObj.isLastPage() );
					apiKey = GFStripeObj.apiKey;
					GFStripeObj.activeFeed = null;
				}
			} );

			// Set priority to 51 so it will be triggered after the coupons add-on
			gform.addFilter( 'gform_product_total', function ( total, formId ) {

				if (
					GFStripeObj.stripe_payment == 'payment_element' &&
					GFStripeObj.isStripePaymentHandlerInitiated( formId )
				) {
					GFStripeObj.stripePaymentHandlers[ formId ].getOrderData( total, formId );
				}

				if ( ! GFStripeObj.activeFeed ) {
					window['gform_stripe_amount_' + formId] = 0;
					return total;
				}

				if ( GFStripeObj.activeFeed.paymentAmount !== 'form_total' ) {

					const paymentAmountInfo = GFStripeObj.getProductFieldPrice( formId, GFStripeObj.activeFeed.paymentAmount );
					window[ 'gform_stripe_amount_' + formId ] = paymentAmountInfo.price * paymentAmountInfo.qty;

					if ( GFStripeObj.activeFeed.hasOwnProperty('setupFee') ) {
						const setupFeeInfo = GFStripeObj.getProductFieldPrice( formId, GFStripeObj.activeFeed.setupFee );
						window['gform_stripe_amount_' + formId] += setupFeeInfo.price * setupFeeInfo.qty;
					}

				} else {
					window[ 'gform_stripe_amount_' + formId ] = total;
				}

				// Update elements payment amount if payment element is enabled.
				if (
					GFStripeObj.stripe_payment == 'payment_element' &&
					GFStripeObj.isStripePaymentHandlerInitiated( formId ) &&
					GFStripeObj.stripePaymentHandlers[ formId ].elements !== null &&
					gforms_stripe_frontend_strings.stripe_connect_enabled === "1"
				) {
					GFStripeObj.stripePaymentHandlers[ formId ].updatePaymentAmount( GFStripeObj.stripePaymentHandlers[ formId ].order.paymentAmount )
				}

				return total;

			}, 51 );

			switch ( this.stripe_payment ) {
				case 'elements':
					var stripe = null,
						elements = null,
						card = null,
						skipElementsHandler = false;

					if ( $( '#gf_stripe_response' ).length ) {
						this.stripeResponse = JSON.parse( $( '#gf_stripe_response' ).val() );

						if ( this.stripeResponse.hasOwnProperty( 'client_secret' ) ) {
							this.hasPaymentIntent = true;
						}
					}
					break;
			}

			// bind Stripe functionality to submit event
			$( '#gform_' + this.formId ).on( 'submit', function ( event ) {

				// If the input isn't present on the page just return, this could happen in edit entry view.
				if ( ! GFStripeObj.GFCCField || GFStripeObj.GFCCField.length === 0 ) {
					return;
				}

				// Don't proceed with payment logic if clicking on the Previous button.
				let skipElementsHandler = false;
				const sourcePage = parseInt( $( '#gform_source_page_number_' + GFStripeObj.formId ).val(), 10 )
				const targetPage = parseInt( $( '#gform_target_page_number_' + GFStripeObj.formId ).val(), 10 );
				if ( ( sourcePage > targetPage && targetPage !== 0 ) ) {
					skipElementsHandler = true;
				}

				if (
					skipElementsHandler
					|| !feedActivated
					|| $( this ).data( 'gfstripesubmitting' )
					|| $( '#gform_save_' + GFStripeObj.formId ).val() == 1
					|| ( !GFStripeObj.isLastPage() && 'elements' !== GFStripeObj.stripe_payment )
					|| gformIsHidden( GFStripeObj.GFCCField )
					|| GFStripeObj.maybeHitRateLimits()
					|| GFStripeObj.invisibleCaptchaPending()
					|| GFStripeObj.recaptchav3Pending()
					|| 'payment_element' === GFStripeObj.stripe_payment && window[ 'gform_stripe_amount_' + GFStripeObj.formId ] === 0
				) {
					return;
				} else {
					event.preventDefault();
					$( this ).data( 'gfstripesubmitting', true );
					GFStripeObj.maybeAddSpinner();
				}

				switch ( GFStripeObj.stripe_payment ) {
					case 'payment_element':
						GFStripeObj.injectHoneypot( event );
						GFStripeObj.stripePaymentHandlers[ GFStripeObj.formId ].validate( event );
						break;
					case 'elements':
						GFStripeObj.form = $( this );

						if ( ( GFStripeObj.isLastPage() && !GFStripeObj.isCreditCardOnPage() ) || gformIsHidden( GFStripeObj.GFCCField ) || skipElementsHandler ) {
							$( this ).submit();
							return;
						}

						if ( activeFeed.type === 'product' ) {
							// Create a new payment method when every time the Stripe Elements is resubmitted.
							GFStripeObj.createPaymentMethod( stripe, card );
						} else {
							GFStripeObj.createToken( stripe, card );
						}
						break;
					case 'stripe.js':
						var form = $( this ),
							ccInputPrefix = 'input_' + GFStripeObj.formId + '_' + GFStripeObj.ccFieldId + '_',
							cc = {
								number: form.find( '#' + ccInputPrefix + '1' ).val(),
								exp_month: form.find( '#' + ccInputPrefix + '2_month' ).val(),
								exp_year: form.find( '#' + ccInputPrefix + '2_year' ).val(),
								cvc: form.find( '#' + ccInputPrefix + '3' ).val(),
								name: form.find( '#' + ccInputPrefix + '5' ).val()
							};


						GFStripeObj.form = form;

						Stripe.card.createToken( cc, function ( status, response ) {
							GFStripeObj.responseHandler( status, response );
						} );
						break;
				}

			} );

			// Show validation message if a payment element payment intent failed and we coulnd't tell until the page has been reloaded
			if ( 'payment_element_intent_failure' in GFStripeObj && GFStripeObj.payment_element_intent_failure ) {
				const validationMessage = jQuery( '<div class="gform_validation_errors" id="gform_' + GFStripeObj.formId + '_validation_container" data-js="gform-focus-validation-error" tabindex="-1"><h2 class="gform_submission_error hide_summary"><span class="gform-icon gform-icon--close"></span>' + gforms_stripe_frontend_strings.payment_element_intent_failure + '</h2></div>' );
				jQuery( '#gform_wrapper_' + GFStripeObj.formId ).prepend( validationMessage );
			}
		};

		this.getProductFieldPrice = function ( formId, fieldId ) {

			var price = GFMergeTag.getMergeTagValue( formId, fieldId, ':price' ),
				qty = GFMergeTag.getMergeTagValue( formId, fieldId, ':qty' );

			if ( typeof price === 'string' ) {
				price = GFMergeTag.getMergeTagValue( formId, fieldId + '.2', ':price' );
				qty = GFMergeTag.getMergeTagValue( formId, fieldId + '.3', ':qty' );
			}

			return {
				price: price,
				qty: qty
			};
		}

		this.getBillingAddressMergeTag = function (field) {
			if (field === '') {
				return '';
			} else {
				return '{:' + field + ':value}';
			}
		};

		this.responseHandler = function (status, response) {

			var form = this.form,
				ccInputPrefix = 'input_' + this.formId + '_' + this.ccFieldId + '_',
				ccInputSuffixes = ['1', '2_month', '2_year', '3', '5'];

			// remove "name" attribute from credit card inputs
			for (var i = 0; i < ccInputSuffixes.length; i++) {

				var input = form.find('#' + ccInputPrefix + ccInputSuffixes[i]);

				if (ccInputSuffixes[i] == '1') {

					var ccNumber = $.trim(input.val()),
						cardType = gformFindCardType(ccNumber);

					if (typeof this.cardLabels[cardType] != 'undefined')
						cardType = this.cardLabels[cardType];

					form.append($('<input type="hidden" name="stripe_credit_card_last_four" />').val(ccNumber.slice(-4)));
					form.append($('<input type="hidden" name="stripe_credit_card_type" />').val(cardType));

				}

				// name attribute is now removed from markup in GFStripe::add_stripe_inputs()
				//input.attr( 'name', null );

			}

			// append stripe.js response
			form.append($('<input type="hidden" name="stripe_response" />').val($.toJSON(response)));

			// submit the form
			form.submit();

		};

		this.elementsResponseHandler = function (response) {
			var form = this.form,
				GFStripeObj = this,
				activeFeed = this.activeFeed,
			    currency = gform.applyFilters( 'gform_stripe_currency', this.currency, this.formId ),
				amount = (0 === gf_global.gf_currency_config.decimals) ? window['gform_stripe_amount_' + this.formId] : gformRoundPrice( window['gform_stripe_amount_' + this.formId] * 100 );

			if (response.error) {
				// display error below the card field.
				this.displayStripeCardError(response);
				// when Stripe response contains errors, stay on page
				// but remove some elements so the form can be submitted again
				// also remove last_4 and card type if that already exists (this happens when people navigate back to previous page and submit an empty CC field)
				this.resetStripeStatus(form, this.formId, this.isLastPage());

				return;
			}

			if (!this.hasPaymentIntent) {
				// append stripe.js response
				if (!$('#gf_stripe_response').length) {
					form.append($('<input type="hidden" name="stripe_response" id="gf_stripe_response" />').val($.toJSON(response)));
				} else {
					$('#gf_stripe_response').val($.toJSON(response));
				}

				if (activeFeed.type === 'product') {
					//set last 4
					form.append($('<input type="hidden" name="stripe_credit_card_last_four" id="gf_stripe_credit_card_last_four" />').val(response.paymentMethod.card.last4));

					// set card type
					form.append($('<input type="hidden" name="stripe_credit_card_type" id="stripe_credit_card_type" />').val(response.paymentMethod.card.brand));
					// Create server side payment intent.
					$.ajax({
						async: false,
						url: gforms_stripe_frontend_strings.ajaxurl,
						dataType: 'json',
						method: 'POST',
						data: {
							action: "gfstripe_create_payment_intent",
							nonce: gforms_stripe_frontend_strings.create_payment_intent_nonce,
							payment_method: response.paymentMethod,
							currency: currency,
							amount: amount,
							feed_id: activeFeed.feedId
						},
						success: function (response) {
							if (response.success) {
								// populate the stripe_response field again.
								if (!$('#gf_stripe_response').length) {
									form.append($('<input type="hidden" name="stripe_response" id="gf_stripe_response" />').val($.toJSON(response.data)));
								} else {
									$('#gf_stripe_response').val($.toJSON(response.data));
								}
								// submit the form
								form.submit();
							} else {
								response.error = response.data;
								delete response.data;
								GFStripeObj.displayStripeCardError(response);
								GFStripeObj.resetStripeStatus(form, GFStripeObj.formId, GFStripeObj.isLastPage());
							}
						}
					});
				} else {
					form.append($('<input type="hidden" name="stripe_credit_card_last_four" id="gf_stripe_credit_card_last_four" />').val(response.token.card.last4));
					form.append($('<input type="hidden" name="stripe_credit_card_type" id="stripe_credit_card_type" />').val(response.token.card.brand));
					form.submit();
				}
			} else {
				if (activeFeed.type === 'product') {
					if (response.hasOwnProperty('paymentMethod')) {
						$('#gf_stripe_credit_card_last_four').val(response.paymentMethod.card.last4);
						$('#stripe_credit_card_type').val(response.paymentMethod.card.brand);

						$.ajax({
							async: false,
							url: gforms_stripe_frontend_strings.ajaxurl,
							dataType: 'json',
							method: 'POST',
							data: {
								action: "gfstripe_update_payment_intent",
								nonce: gforms_stripe_frontend_strings.create_payment_intent_nonce,
								payment_intent: response.id,
								payment_method: response.paymentMethod,
								currency: currency,
								amount: amount,
								feed_id: activeFeed.feedId
							},
							success: function (response) {
								if (response.success) {
									$('#gf_stripe_response').val($.toJSON(response.data));
									form.submit();
								} else {
									response.error = response.data;
									delete response.data;
									GFStripeObj.displayStripeCardError(response);
									GFStripeObj.resetStripeStatus(form, GFStripeObj.formId, GFStripeObj.isLastPage());
								}
							}
						});
					} else if (response.hasOwnProperty('amount')) {
						form.submit();
					}
				} else {
					var currentResponse = JSON.parse($('#gf_stripe_response').val());
					currentResponse.updatedToken = response.token.id;

					$('#gf_stripe_response').val($.toJSON(currentResponse));

					form.append($('<input type="hidden" name="stripe_credit_card_last_four" id="gf_stripe_credit_card_last_four" />').val(response.token.card.last4));
					form.append($('<input type="hidden" name="stripe_credit_card_type" id="stripe_credit_card_type" />').val(response.token.card.brand));
					form.submit();
				}
			}
		};

		this.scaActionHandler = function (stripe, formId) {
			if ( ! $('#gform_' + formId).data('gfstripescaauth') ) {
				$('#gform_' + formId).data('gfstripescaauth', true);

				var GFStripeObj = this, response = JSON.parse($('#gf_stripe_response').val());
				if (this.activeFeed.type === 'product') {
					// Prevent the 3D secure auth from appearing twice, so we need to check if the intent status first.
					stripe.retrievePaymentIntent(
						response.client_secret
					).then(function(result) {
						if ( result.paymentIntent.status === 'requires_action' ) {
							stripe.handleCardAction(
								response.client_secret
							).then(function(result) {
								var currentResponse = JSON.parse($('#gf_stripe_response').val());
								currentResponse.scaSuccess = true;

								$('#gf_stripe_response').val($.toJSON(currentResponse));

								GFStripeObj.maybeAddSpinner();
								// Enable the submit button, which was disabled before displaying the SCA warning message, so we can submit the form.
								jQuery( '#gform_submit_button_' + formId ).prop( 'disabled' , false );
								$('#gform_' + formId).data('gfstripescaauth', false);
								$('#gform_' + formId).data('gfstripesubmitting', true);
								if ( GFStripeObj.isConversationalForm() ) {
									$( '.gform-conversational__field-form-footer-submit' ).attr( 'style', 'display: block' );
								}
								$('#gform_' + formId).trigger( 'submit' );
								// There are a couple of seconds delay where the button is available for clicking before the thank you page is displayed,
								// Disable the button so the user will not think it needs to be clicked again.
								jQuery( '#gform_submit_button_' + formId ).prop( 'disabled' , true );
							});
						}
					});
				} else {
					stripe.retrievePaymentIntent(
						response.client_secret
					).then(function(result) {
						if ( result.paymentIntent.status === 'requires_action' ) {
							stripe.handleCardPayment(
								response.client_secret
							).then(function(result) {
								GFStripeObj.maybeAddSpinner();
								// Enable the submit button, which was disabled before displaying the SCA warning message, so we can submit the form.
								jQuery( '#gform_submit_button_' + formId ).prop( 'disabled' , false );
								$('#gform_' + formId).data('gfstripescaauth', false);
								$('#gform_' + formId).data('gfstripesubmitting', true);
								if ( GFStripeObj.isConversationalForm() ) {
									$( '.gform-conversational__field-form-footer-submit' ).attr( 'style', 'display: block' );
								}
								$('#gform_' + formId).trigger( 'submit' );
							});
						}
					});
				}
			}
		};

		this.isLastPage = function () {

			var targetPageInput = $('#gform_target_page_number_' + this.formId);
			if (targetPageInput.length > 0)
				return targetPageInput.val() == 0;

			return true;
		};

		/**
		 * @function isConversationalForm
		 * @description Determines if we are on conversational form mode
		 *
		 * @since 5.1.0
		 *
		 * @returns {boolean}
		 */
		this.isConversationalForm = function () {
			const convoForm = $('[data-js="gform-conversational-form"]');

			return convoForm.length > 0;
		}

		/**
		 * @function isCreditCardOnPage
		 * @description Determines if the credit card field is on the current page
		 *
		 * @since 5.1.0
		 *
		 * @returns {boolean}
		 */
		this.isCreditCardOnPage = function () {

			var currentPage = this.getCurrentPageNumber();

			// if current page is false or no credit card page number or this is a convo form, assume this is not a multi-page form
			if ( ! this.ccPage || ! currentPage || this.isConversationalForm() ) {
				return true;
			}

			return this.ccPage == currentPage;
		};

		this.getCurrentPageNumber = function () {
			var currentPageInput = $('#gform_source_page_number_' + this.formId);
			return currentPageInput.length > 0 ? currentPageInput.val() : false;
		};

		this.maybeAddSpinner = function () {
			if (this.isAjax)
				return;

			if (typeof gformAddSpinner === 'function') {
				gformAddSpinner(this.formId);
			} else {
				// Can be removed after min Gravity Forms version passes 2.1.3.2.
				var formId = this.formId;

				if (jQuery('#gform_ajax_spinner_' + formId).length == 0) {
					var spinnerUrl = gform.applyFilters('gform_spinner_url', gf_global.spinnerUrl, formId),
						$spinnerTarget = gform.applyFilters('gform_spinner_target_elem', jQuery('#gform_submit_button_' + formId + ', #gform_wrapper_' + formId + ' .gform_next_button, #gform_send_resume_link_button_' + formId), formId);
					$spinnerTarget.after('<img id="gform_ajax_spinner_' + formId + '"  class="gform_ajax_spinner" src="' + spinnerUrl + '" alt="" />');
				}
			}

		};

		this.resetStripeStatus = function(form, formId, isLastPage) {
			$('#gf_stripe_response, #gf_stripe_credit_card_last_four, #stripe_credit_card_type').remove();
			form.data('gfstripesubmitting', false);
			const spinnerNodes = document.querySelectorAll( '#gform_ajax_spinner_' + formId );
			spinnerNodes.forEach( function( node ) {
				node.remove();
			} );
			// must do this or the form cannot be submitted again
			if (isLastPage) {
				window["gf_submitting_" + formId] = false;
			}
		};

		this.displayStripeCardError = function (event) {
			if (event.error) {
				if ( $('#gform_ajax_spinner_' + this.formId).length > 0 ) {
					$('#gform_ajax_spinner_' + this.formId).remove();
				}
				displayError( event.error.message );
			} else {
				clearErrors();
			}
		};

		this.createToken = function (stripe, card) {
			const GFStripeObj = this;
			const activeFeed = this.activeFeed;
			const cardholderName = $( '#input_' + GFStripeObj.formId + '_' + GFStripeObj.ccFieldId + '_5' ).val();
			const tokenData = {
					name: cardholderName,
					address_line1: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line1)),
					address_line2: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line2)),
					address_city: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_city)),
					address_state: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_state)),
					address_zip: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_zip)),
					address_country: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_country)),
					currency: gform.applyFilters( 'gform_stripe_currency', this.currency, this.formId )
				};

			stripe.createToken(card, tokenData).then(function (response) {
				GFStripeObj.elementsResponseHandler(response);
			});
		}

		this.createPaymentMethod = function (stripe, card, country) {
			var GFStripeObj = this, activeFeed = this.activeFeed, countryFieldValue = '';

			if ( activeFeed.address_country !== '' ) {
				countryFieldValue = GFMergeTag.replaceMergeTags(GFStripeObj.formId, GFStripeObj.getBillingAddressMergeTag(activeFeed.address_country));
			}

			if (countryFieldValue !== '' && ( typeof country === 'undefined' || country === '' )) {
                $.ajax({
                    async: false,
                    url: gforms_stripe_frontend_strings.ajaxurl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: "gfstripe_get_country_code",
                        nonce: gforms_stripe_frontend_strings.create_payment_intent_nonce,
                        country: countryFieldValue,
                        feed_id: activeFeed.feedId
                    },
                    success: function (response) {
                        if (response.success) {
                            GFStripeObj.createPaymentMethod(stripe, card, response.data.code);
                        }
                    }
                });
            } else {
                var cardholderName = $('#input_' + this.formId + '_' + this.ccFieldId + '_5').val(),
					line1 = GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line1)),
					line2 = GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line2)),
					city = GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_city)),
					state = GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_state)),
					postal_code = GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_zip)),
                    data = { billing_details: { name: null, address: {} } };

                if (cardholderName !== '') {
                	data.billing_details.name = cardholderName;
				}
				if (line1 !== '') {
					data.billing_details.address.line1 = line1;
				}
				if (line2 !== '') {
					data.billing_details.address.line2 = line2;
				}
				if (city !== '') {
					data.billing_details.address.city = city;
				}
				if (state !== '') {
					data.billing_details.address.state = state;
				}
				if (postal_code !== '') {
					data.billing_details.address.postal_code = postal_code;
				}
				if (country !== '') {
					data.billing_details.address.country = country;
				}

				if (data.billing_details.name === null) {
					delete data.billing_details.name;
				}
				if (data.billing_details.address === {}) {
					delete data.billing_details.address;
				}

				stripe.createPaymentMethod('card', card, data).then(function (response) {
					if (GFStripeObj.stripeResponse !== null) {
						response.id = GFStripeObj.stripeResponse.id;
						response.client_secret = GFStripeObj.stripeResponse.client_secret;
					}

					GFStripeObj.elementsResponseHandler(response);
				});
            }
		};

		this.maybeHitRateLimits = function() {
			if (this.hasOwnProperty('cardErrorCount')) {
				if (this.cardErrorCount >= 5) {
					return true;
				}
			}

			return false;
		};

		this.invisibleCaptchaPending = function () {
			var form = this.form,
				reCaptcha = form.find('.ginput_recaptcha');

			if (!reCaptcha.length || reCaptcha.data('size') !== 'invisible') {
				return false;
			}

			var reCaptchaResponse = reCaptcha.find('.g-recaptcha-response');

			return !(reCaptchaResponse.length && reCaptchaResponse.val());
		}

		/**
		 * @function recaptchav3Pending
		 * @description Check if recaptcha v3 is enabled and pending a response.
		 *
		 * @since 5.5.0
		 */
		this.recaptchav3Pending = function () {
			const form = this.form;
			const recaptchaField = form.find( '.ginput_recaptchav3' );
			if ( ! recaptchaField.length ) {
				return false;
			}

			const recaptchaResponse = recaptchaField.find( '.gfield_recaptcha_response' );

			return ! ( recaptchaResponse && recaptchaResponse.val() );
		};

		/**
		 * This is duplicated honeypot logic from core that can be removed once Stripe can consume the core honeypot js.
		 */


		/**
		 * @function injectHoneypot
		 * @description Duplicated from core. Injects the honeypot field when appropriate.
		 *
		 * @since 5.0
		 *
		 * @param {jQuery.Event} event Form submission event.
		 */
		this.injectHoneypot = ( event ) => {
			const form = event.target;
			const shouldInjectHoneypot = ( this.isFormSubmission( form ) || this.isSaveContinue( form ) ) && ! this.isHeadlessBrowser();

			if ( shouldInjectHoneypot ) {
				const hashInput = `<input type="hidden" name="version_hash" value="${ gf_global.version_hash }" />`;
				form.insertAdjacentHTML( 'beforeend', hashInput );
			}
		};

		/**
		 * @function isSaveContinue
		 * @description Duplicated from core. Determines if this submission is from a Save and Continue click.
		 *
		 * @since 5.0
		 *
		 * @param {HTMLFormElement} form The form that was submitted.
		 *
		 * @return {boolean} Returns true if this submission was initiated via a Save a Continue button click. Returns false otherwise.
		 */
		this.isSaveContinue = ( form ) => {
			const formId = form.dataset.formid;
			const nodes = this.getNodes( `#gform_save_${ formId }`, true, form, true );
			return nodes.length > 0 && nodes[ 0 ].value === '1';
		};

		/**
		 * @function isFormSubmission
		 * @description Duplicated from core. Determines if this is a standard form submission (ie. not a next or previous page submission, and not a save and continue submission).
		 *
		 * @since 5.0
		 *
		 * @param {HTMLFormElement} form The form that was submitted.
		 *
		 * @return {boolean} Returns true if this is a standard form submission. Returns false otherwise.
		 */
		this.isFormSubmission = ( form ) => {
			const formId = form.dataset.formid;
			const targetEl = this.getNodes( `input[name = "gform_target_page_number_${ formId }"]`, true, form, true )[ 0 ];
			if ( 'undefined' === typeof targetEl ) {
				return false;
			}
			const targetPage = parseInt( targetEl.value );
			return targetPage === 0;
		};

		/**
		 * @function isHeadlessBrowser.
		 * @description Determines if the currently browser is headless.
		 *
		 * @since 5.0
		 *
		 * @return {boolean} Returns true for headless browsers. Returns false otherwise.
		 */
		this.isHeadlessBrowser = () => {
			return window._phantom || window.callPhantom || // phantomjs.
				window.__phantomas || // PhantomJS-based web perf metrics + monitoring tool.
				window.Buffer || // nodejs.
				window.emit || // couchjs.
				window.spawn || // rhino.
				window.webdriver || window._selenium || window._Selenium_IDE_Recorder || window.callSelenium || // selenium.
				window.__nightmare ||
				window.domAutomation ||
				window.domAutomationController || // chromium based automation driver.
				window.document.__webdriver_evaluate || window.document.__selenium_evaluate ||
				window.document.__webdriver_script_function || window.document.__webdriver_script_func || window.document.__webdriver_script_fn ||
				window.document.__fxdriver_evaluate ||
				window.document.__driver_unwrapped ||
				window.document.__webdriver_unwrapped ||
				window.document.__driver_evaluate ||
				window.document.__selenium_unwrapped ||
				window.document.__fxdriver_unwrapped ||
				window.document.documentElement.getAttribute( 'selenium' ) ||
				window.document.documentElement.getAttribute( 'webdriver' ) ||
				window.document.documentElement.getAttribute( 'driver' );
		};

		/**
		 * @function getNodes.
		 * @description Duplicated from core until the build system can use Gravity Forms utilities.
		 *
		 * @since 5.
		 */
		this.getNodes = (
		selector = '',
		convert = false,
		node = document,
		custom = false
		) => {
			const selectorString = custom ? selector : `[data-js="${ selector }"]`;
			let nodes = node.querySelectorAll( selectorString );
			if ( convert ) {
				nodes = this.convertElements( nodes );
			}
			return nodes;
		}

		/**
		 * @function convertElements.
		 * @description Duplicated from core until the build system can use Gravity Forms utilities.
		 *
		 * @since 5.0
		 */
		this.convertElements = ( elements = [] ) => {
			const converted = [];
			let i = elements.length;
			for ( i; i--; converted.unshift( elements[ i ] ) ); // eslint-disable-line

			return converted;
		}

		/**
		 * @function isStripePaymentHandlerInitiated.
		 * @description Checks if a Stripe payment handler has been initiated for a form.
		 *
		 * @since 5.4
		 */
		this.isStripePaymentHandlerInitiated = function ( formId ) {
			return (
				formId in this.stripePaymentHandlers &&
				this.stripePaymentHandlers[ formId ] !== null &&
				this.stripePaymentHandlers[ formId ] !== undefined
			);
		}

		/**
		 * End duplicated honeypot logic.
		 */

		this.init();

	}

})(jQuery);
