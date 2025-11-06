/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 2);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./deprecated/js/src/error-handler.js":
/*!********************************************!*\
  !*** ./deprecated/js/src/error-handler.js ***!
  \********************************************/
/*! exports provided: initErrorHandler, displayError, clearErrors */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "initErrorHandler", function() { return initErrorHandler; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "displayError", function() { return displayError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clearErrors", function() { return clearErrors; });

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
function initErrorHandler(formIdParam, ccFieldIdParam) {
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
function displayError(message) {
	let cardContainer = gform.utils.getNode(`#gform_${formId} .ginput_stripe_creditcard`, document, true);
	const legacyCardContainer = gform.utils.getNode(`#gform_${formId} .ginput_container_creditcard`, document, true);
	if (legacyCardContainer) {
		cardContainer = legacyCardContainer;
	}

	// Handle edge case when field description exists and is below, then validation should be displayed beneath description instead of the field.
	const descriptionElement = gform.utils.getNode(`#gfield_description_${formId}_${ccFieldId}`, document, true);
	if (getValidationPlacement() === 'below' && descriptionElement) {
		cardContainer = descriptionElement;
	}

	if (!cardContainer) {
		return;
	}

	// Add error class to the whole field.
	const fieldContainer = getFieldContainer();
	fieldContainer.classList.add('gfield_error');

	// Make sure validation container exists after field container, if not create it.
	if (!validationContainerExists(cardContainer)) {
		insertValidationContainer(cardContainer);
	}

	const validationContainer = getValidationContainer(cardContainer);
	if (!validationContainer) {
		return;
	}

	validationContainer.innerText = message;

	setTimeout(() => {
		wp.a11y.speak(message);
	}, 500);
}

/**
 * @function clearErrors
 * @description Clears all error messages and classes from the field.
 */
function clearErrors() {
	const fieldContainer = getFieldContainer();
	fieldContainer.classList.remove('gfield_error');
	const validationMessages = gform.utils.getNodes('.stripe_validation_error.validation_message', true, fieldContainer, true);
	validationMessages.forEach(validationMessage => {
		validationMessage.remove();
	});
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
	return gform.utils.getNode(`#field_${formId}_${ccFieldId}`, document, true);
}

/**
 * @function getValidationPlacement
 * @description Determines the position of the validation message (above or below the element).
 *
 * @return {string} The placement setting, 'below' or 'above'.
 */
function getValidationPlacement() {
	if (validationPlacement) {
		return validationPlacement;
	}

	const field = gform.utils.getNode(`#field_${formId}_${ccFieldId}`, document, true);
	if (field && gform.utils.hasClassFromArray(field, ['field_validation_above'])) {
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
function insertValidationContainer(element) {
	const position = getValidationPlacement();
	const validationContainer = document.createElement('div');
	validationContainer.className = 'stripe_validation_error gfield_description validation_message gfield_validation_message';
	if (position === 'below') {
		element.insertAdjacentElement('afterend', validationContainer);
	} else {
		element.insertAdjacentElement('beforebegin', validationContainer);
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
function validationContainerExists(element) {
	if (!element) {
		return false;
	}
	return getValidationContainer(element) !== null;
}

/**
 * @function getValidationContainer
 * @description Retrieves the validation message container element, if it exists, next to or before the provided element.
 *
 * @param {HTMLElement} element The element to check.
 *
 * @return {HTMLElement|null} The validation message container element, or null if it does not exist.
 */
function getValidationContainer(element) {
	if (!element) {
		return null;
	}

	if (getValidationPlacement() === 'below' && element.nextElementSibling) {
		return element.nextElementSibling.matches('.validation_message') ? element.nextElementSibling : null;
	}

	if (element.previousElementSibling) {
		return element.previousElementSibling.matches('.validation_message') ? element.previousElementSibling : null;
	}

	return null;
}



/***/ }),

/***/ "./deprecated/js/src/frontend.js":
/*!***************************************!*\
  !*** ./deprecated/js/src/frontend.js ***!
  \***************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _payment_element_stripe_payments_handler__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./payment-element/stripe-payments-handler */ "./deprecated/js/src/payment-element/stripe-payments-handler.js");
/* harmony import */ var _error_handler__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./error-handler */ "./deprecated/js/src/error-handler.js");
/**
 * Front-end Script
 */



window.GFStripe = null;

gform.extensions = gform.extensions || {};
gform.extensions.styles = gform.extensions.styles || {};
gform.extensions.styles.gravityformsstripe = gform.extensions.styles.gravityformsstripe || {};

(function ($) {

	GFStripe = function (args) {

		for (var prop in args) {
			if (args.hasOwnProperty(prop)) this[prop] = args[prop];
		}

		this.form = null;

		this.activeFeed = null;

		this.GFCCField = null;

		this.stripeResponse = null;

		this.hasPaymentIntent = false;

		this.stripePaymentHandlers = {};

		Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["initErrorHandler"])(this.formId, this.ccFieldId);
		this.cardStyle = this.cardStyle || {};

		gform.extensions.styles.gravityformsstripe[this.formId] = gform.extensions.styles.gravityformsstripe[this.formId] || {};

		const componentStyles = Object.keys(this.cardStyle).length > 0 ? JSON.parse(JSON.stringify(this.cardStyle)) : gform.extensions.styles.gravityformsstripe[this.formId][this.pageInstance] || {};

		this.setComponentStyleValue = function (key, value, themeFrameworkStyles, manualElement) {
			// Helper to resolve CSS property key
			const resolveKey = key => key === 'fontSmoothing' ? '-webkit-font-smoothing' : key.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();

			// If the value provided is a custom property let's begin
			if (value.indexOf('--') === 0) {
				const computedValue = themeFrameworkStyles.getPropertyValue(value);

				// If we have a computed end value from the custom property, let's use that
				if (computedValue) {
					return computedValue.trim();
				}

				// Otherwise, let's use a provided element or the form wrapper
				// along with the key to nab the computed end value for the CSS property
				const selector = manualElement ? getComputedStyle(manualElement) : themeFrameworkStyles;
				return selector.getPropertyValue(resolveKey(key)).trim();
			}

			// Otherwise let's treat the provided value as the actual CSS value wanted
			return value.trim();
		};

		this.setComponentStyles = function (obj, objKey, parentKey) {
			// If our object doesn't have any styles specified, let's bail here
			if (Object.keys(obj).length === 0) {
				return;
			}

			// Grab the computed styles for the form, which the global CSS API and theme framework are scoped to
			const form = document.getElementById('gform_' + this.formId);
			const themeFrameworkStyles = getComputedStyle(form);

			// Grab the first form control in the form for fallback CSS property value computation
			const firstFormControl = form.querySelector('.gfield input');

			// Helper method to handle outline style properties and turn it into Stripe's
			// required shorthand outline property
			const processOutlineProperty = (target, key, value) => {
				if (!this.outlineProperties) {
					this.outlineProperties = { value: '', count: 0 };
				}

				this.outlineProperties.value += this.outlineProperties.count > 0 ? ` ${value}` : value;
				this.outlineProperties.count++;

				if (this.outlineProperties.count === 3) {
					target['outline'] = this.outlineProperties.value;

					// Reset for future calls
					this.outlineProperties = { value: '', count: 0 };
				}
			};

			// Note, this currently only supports three levels deep of object nesting.
			Object.keys(obj).forEach(key => {
				// Handling of keys that are objects with additional key/value pairs
				if (typeof obj[key] === 'object') {

					// Create object for top level key
					if (!parentKey) {
						this.cardStyle[key] = {};
					}

					// Create object for second level key
					if (parentKey) {
						this.cardStyle[parentKey][key] = {};
					}

					const objPath = parentKey ? parentKey : key;

					// Recursively pass each key's object through our method for continued processing
					this.setComponentStyles(obj[key], key, objPath);

					return;
				}

				// Handling of keys that are not objects and need their value to be set
				let value = '';
				const updateStyle = (target, keyPath) => {
					value = this.setComponentStyleValue(key, keyPath, themeFrameworkStyles, firstFormControl);
					if (value) {
						if (['outlineWidth', 'outlineStyle', 'outlineColor'].includes(key)) {
							delete target[key];
							processOutlineProperty(target, key, value);
						} else {
							target[key] = value;
						}
					}
				};

				if (parentKey && objKey && objKey !== parentKey) {
					updateStyle(this.cardStyle[parentKey][objKey], componentStyles[parentKey][objKey][key]);
				} else if (parentKey) {
					updateStyle(this.cardStyle[parentKey], componentStyles[parentKey][key]);
				} else {
					updateStyle(this.cardStyle, componentStyles[key]);
				}
			});
		};

		this.init = async function () {

			this.setComponentStyles(componentStyles);

			if (!this.isCreditCardOnPage()) {
				if (this.stripe_payment === 'stripe.js' || this.stripe_payment === 'elements' && !$('#gf_stripe_response').length) {
					return;
				}
			}

			var GFStripeObj = this,
			    activeFeed = null,
			    feedActivated = false,
			    hidePostalCode = false,
			    apiKey = this.apiKey;

			this.form = $('#gform_' + this.formId);
			this.GFCCField = $('#input_' + this.formId + '_' + this.ccFieldId + '_1');

			gform.addAction('gform_frontend_feeds_evaluated', async function (feeds, formId) {
				if (formId !== GFStripeObj.formId) {
					return;
				}

				activeFeed = null;
				feedActivated = false;
				hidePostalCode = false;

				for (var i = 0; i < Object.keys(feeds).length; i++) {
					if (feeds[i].addonSlug === 'gravityformsstripe' && feeds[i].isActivated) {
						feedActivated = true;

						for (var j = 0; j < Object.keys(GFStripeObj.feeds).length; j++) {
							if (GFStripeObj.feeds[j].feedId === feeds[i].feedId) {
								activeFeed = GFStripeObj.feeds[j];

								break;
							}
						}
						apiKey = activeFeed.hasOwnProperty('apiKey') ? activeFeed.apiKey : GFStripeObj.apiKey;
						GFStripeObj.activeFeed = activeFeed;

						gformCalculateTotalPrice(formId);

						if (GFStripeObj.stripe_payment == 'payment_element') {
							GFStripeObj.stripePaymentHandlers[formId] = new _payment_element_stripe_payments_handler__WEBPACK_IMPORTED_MODULE_0__["default"](apiKey, GFStripeObj);
						} else if (GFStripeObj.stripe_payment === 'elements') {
							stripe = Stripe(apiKey);
							elements = stripe.elements();

							hidePostalCode = activeFeed.address_zip !== '';

							// If Stripe Card is already on the page (AJAX failed validation, or switch frontend feeds),
							// Destroy the card field so we can re-initiate it.
							if (card != null && card.hasOwnProperty('_destroyed') && card._destroyed === false) {
								card.destroy();
							}

							// Clear card field errors before initiate it.
							Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["clearErrors"])();

							card = elements.create('card', {
								classes: GFStripeObj.cardClasses,
								style: GFStripeObj.cardStyle,
								hidePostalCode: hidePostalCode
							});

							if ($('.gform_stripe_requires_action').length) {
								if ($('.ginput_container_creditcard > div').length === 2) {
									// Cardholder name enabled.
									$('.ginput_container_creditcard > div:last').hide();
									$('.ginput_container_creditcard > div:first').html('<p><strong>' + gforms_stripe_frontend_strings.requires_action + '</strong></p>');
								} else {
									$('.ginput_container_creditcard').html('<p><strong>' + gforms_stripe_frontend_strings.requires_action + '</strong></p>');
								}

								// Add a spinner next to the validation message and disable the submit button until we are over with 3D Secure.
								if (jQuery('#gform_' + formId + '_validation_container h2 .gform_ajax_spinner').length <= 0) {
									jQuery('#gform_' + formId + '_validation_container h2').append('<img id="gform_ajax_spinner_' + formId + '"  class="gform_ajax_spinner" src="' + gf_global.spinnerUrl + '" alt="" />');
									jQuery('#gform_submit_button_' + formId).prop('disabled', true);
								}

								// Update legacy close icon to an info icon.
								const $iconSpan = jQuery('#gform_' + formId + '_validation_container h2 .gform-icon.gform-icon--close');
								const isThemeFrameWork = jQuery('.gform-theme--framework').length;
								console.log(isThemeFrameWork);
								console.log($iconSpan);
								if ($iconSpan.length && !isThemeFrameWork) {
									$iconSpan.removeClass('gform-icon--close').addClass('gform-icon--info');
								}

								GFStripeObj.scaActionHandler(stripe, formId);
							} else {
								card.mount('#' + GFStripeObj.GFCCField.attr('id'));

								card.on('change', function (event) {
									GFStripeObj.displayStripeCardError(event);
								});
							}
						} else if (GFStripeObj.stripe_payment == 'stripe.js') {
							Stripe.setPublishableKey(apiKey);
							break;
						}

						break; // allow only one active feed.
					}
				}

				if (!feedActivated) {
					if (GFStripeObj.stripe_payment === 'elements' || GFStripeObj.stripe_payment === 'payment_element') {
						if (elements != null && card === elements.getElement('card')) {
							card.destroy();
						}

						if (GFStripeObj.isStripePaymentHandlerInitiated(formId)) {
							GFStripeObj.stripePaymentHandlers[formId].destroy();
						}
						Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["displayError"])(gforms_stripe_frontend_strings.no_active_frontend_feed);
					}

					// remove Stripe fields and form status when Stripe feed deactivated
					GFStripeObj.resetStripeStatus(GFStripeObj.form, formId, GFStripeObj.isLastPage());
					apiKey = GFStripeObj.apiKey;
					GFStripeObj.activeFeed = null;
				}
			});

			// Set priority to 51 so it will be triggered after the coupons add-on
			gform.addFilter('gform_product_total', function (total, formId) {

				if (GFStripeObj.stripe_payment == 'payment_element' && GFStripeObj.isStripePaymentHandlerInitiated(formId)) {
					GFStripeObj.stripePaymentHandlers[formId].getOrderData(total, formId);
				}

				if (!GFStripeObj.activeFeed) {
					window['gform_stripe_amount_' + formId] = 0;
					return total;
				}

				if (GFStripeObj.activeFeed.paymentAmount !== 'form_total') {

					const paymentAmountInfo = GFStripeObj.getProductFieldPrice(formId, GFStripeObj.activeFeed.paymentAmount);
					window['gform_stripe_amount_' + formId] = paymentAmountInfo.price * paymentAmountInfo.qty;

					if (GFStripeObj.activeFeed.hasOwnProperty('setupFee')) {
						const setupFeeInfo = GFStripeObj.getProductFieldPrice(formId, GFStripeObj.activeFeed.setupFee);
						window['gform_stripe_amount_' + formId] += setupFeeInfo.price * setupFeeInfo.qty;
					}
				} else {
					window['gform_stripe_amount_' + formId] = total;
				}

				// Update elements payment amount if payment element is enabled.
				if (GFStripeObj.stripe_payment == 'payment_element' && GFStripeObj.isStripePaymentHandlerInitiated(formId) && GFStripeObj.stripePaymentHandlers[formId].elements !== null && gforms_stripe_frontend_strings.stripe_connect_enabled === "1") {
					GFStripeObj.stripePaymentHandlers[formId].updatePaymentAmount(GFStripeObj.stripePaymentHandlers[formId].order.paymentAmount);
				}

				return total;
			}, 51);

			switch (this.stripe_payment) {
				case 'elements':
					var stripe = null,
					    elements = null,
					    card = null,
					    skipElementsHandler = false;

					if ($('#gf_stripe_response').length) {
						this.stripeResponse = JSON.parse($('#gf_stripe_response').val());

						if (this.stripeResponse.hasOwnProperty('client_secret')) {
							this.hasPaymentIntent = true;
						}
					}
					break;
			}

			// bind Stripe functionality to submit event
			$('#gform_' + this.formId).on('submit', function (event) {

				// If the input isn't present on the page just return, this could happen in edit entry view.
				if (!GFStripeObj.GFCCField || GFStripeObj.GFCCField.length === 0) {
					return;
				}

				// Don't proceed with payment logic if clicking on the Previous button.
				let skipElementsHandler = false;
				const sourcePage = parseInt($('#gform_source_page_number_' + GFStripeObj.formId).val(), 10);
				const targetPage = parseInt($('#gform_target_page_number_' + GFStripeObj.formId).val(), 10);
				if (sourcePage > targetPage && targetPage !== 0) {
					skipElementsHandler = true;
				}

				if (skipElementsHandler || !feedActivated || $(this).data('gfstripesubmitting') || $('#gform_save_' + GFStripeObj.formId).val() == 1 || !GFStripeObj.isLastPage() && 'elements' !== GFStripeObj.stripe_payment || gformIsHidden(GFStripeObj.GFCCField) || GFStripeObj.maybeHitRateLimits() || GFStripeObj.invisibleCaptchaPending() || GFStripeObj.recaptchav3Pending() || 'payment_element' === GFStripeObj.stripe_payment && window['gform_stripe_amount_' + GFStripeObj.formId] === 0) {
					return;
				} else {
					event.preventDefault();
					$(this).data('gfstripesubmitting', true);
					GFStripeObj.maybeAddSpinner();
				}

				switch (GFStripeObj.stripe_payment) {
					case 'payment_element':
						GFStripeObj.injectHoneypot(event);
						GFStripeObj.stripePaymentHandlers[GFStripeObj.formId].validate(event);
						break;
					case 'elements':
						GFStripeObj.form = $(this);

						if (GFStripeObj.isLastPage() && !GFStripeObj.isCreditCardOnPage() || gformIsHidden(GFStripeObj.GFCCField) || skipElementsHandler) {
							$(this).submit();
							return;
						}

						if (activeFeed.type === 'product') {
							// Create a new payment method when every time the Stripe Elements is resubmitted.
							GFStripeObj.createPaymentMethod(stripe, card);
						} else {
							GFStripeObj.createToken(stripe, card);
						}
						break;
					case 'stripe.js':
						var form = $(this),
						    ccInputPrefix = 'input_' + GFStripeObj.formId + '_' + GFStripeObj.ccFieldId + '_',
						    cc = {
							number: form.find('#' + ccInputPrefix + '1').val(),
							exp_month: form.find('#' + ccInputPrefix + '2_month').val(),
							exp_year: form.find('#' + ccInputPrefix + '2_year').val(),
							cvc: form.find('#' + ccInputPrefix + '3').val(),
							name: form.find('#' + ccInputPrefix + '5').val()
						};

						GFStripeObj.form = form;

						Stripe.card.createToken(cc, function (status, response) {
							GFStripeObj.responseHandler(status, response);
						});
						break;
				}
			});

			// Show validation message if a payment element payment intent failed and we coulnd't tell until the page has been reloaded
			if ('payment_element_intent_failure' in GFStripeObj && GFStripeObj.payment_element_intent_failure) {
				const validationMessage = jQuery('<div class="gform_validation_errors" id="gform_' + GFStripeObj.formId + '_validation_container" data-js="gform-focus-validation-error" tabindex="-1"><h2 class="gform_submission_error hide_summary"><span class="gform-icon gform-icon--close"></span>' + gforms_stripe_frontend_strings.payment_element_intent_failure + '</h2></div>');
				jQuery('#gform_wrapper_' + GFStripeObj.formId).prepend(validationMessage);
			}
		};

		this.getProductFieldPrice = function (formId, fieldId) {

			var price = GFMergeTag.getMergeTagValue(formId, fieldId, ':price'),
			    qty = GFMergeTag.getMergeTagValue(formId, fieldId, ':qty');

			if (typeof price === 'string') {
				price = GFMergeTag.getMergeTagValue(formId, fieldId + '.2', ':price');
				qty = GFMergeTag.getMergeTagValue(formId, fieldId + '.3', ':qty');
			}

			return {
				price: price,
				qty: qty
			};
		};

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

					if (typeof this.cardLabels[cardType] != 'undefined') cardType = this.cardLabels[cardType];

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
			    currency = gform.applyFilters('gform_stripe_currency', this.currency, this.formId),
			    amount = 0 === gf_global.gf_currency_config.decimals ? window['gform_stripe_amount_' + this.formId] : gformRoundPrice(window['gform_stripe_amount_' + this.formId] * 100);

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
			if (!$('#gform_' + formId).data('gfstripescaauth')) {
				$('#gform_' + formId).data('gfstripescaauth', true);

				var GFStripeObj = this,
				    response = JSON.parse($('#gf_stripe_response').val());
				if (this.activeFeed.type === 'product') {
					// Prevent the 3D secure auth from appearing twice, so we need to check if the intent status first.
					stripe.retrievePaymentIntent(response.client_secret).then(function (result) {
						if (result.paymentIntent.status === 'requires_action') {
							stripe.handleCardAction(response.client_secret).then(function (result) {
								var currentResponse = JSON.parse($('#gf_stripe_response').val());
								currentResponse.scaSuccess = true;

								$('#gf_stripe_response').val($.toJSON(currentResponse));

								GFStripeObj.maybeAddSpinner();
								// Enable the submit button, which was disabled before displaying the SCA warning message, so we can submit the form.
								jQuery('#gform_submit_button_' + formId).prop('disabled', false);
								$('#gform_' + formId).data('gfstripescaauth', false);
								$('#gform_' + formId).data('gfstripesubmitting', true);
								if (GFStripeObj.isConversationalForm()) {
									$('.gform-conversational__field-form-footer-submit').attr('style', 'display: block');
								}
								$('#gform_' + formId).trigger('submit');
								// There are a couple of seconds delay where the button is available for clicking before the thank you page is displayed,
								// Disable the button so the user will not think it needs to be clicked again.
								jQuery('#gform_submit_button_' + formId).prop('disabled', true);
							});
						}
					});
				} else {
					stripe.retrievePaymentIntent(response.client_secret).then(function (result) {
						if (result.paymentIntent.status === 'requires_action') {
							stripe.handleCardPayment(response.client_secret).then(function (result) {
								GFStripeObj.maybeAddSpinner();
								// Enable the submit button, which was disabled before displaying the SCA warning message, so we can submit the form.
								jQuery('#gform_submit_button_' + formId).prop('disabled', false);
								$('#gform_' + formId).data('gfstripescaauth', false);
								$('#gform_' + formId).data('gfstripesubmitting', true);
								if (GFStripeObj.isConversationalForm()) {
									$('.gform-conversational__field-form-footer-submit').attr('style', 'display: block');
								}
								$('#gform_' + formId).trigger('submit');
							});
						}
					});
				}
			}
		};

		this.isLastPage = function () {

			var targetPageInput = $('#gform_target_page_number_' + this.formId);
			if (targetPageInput.length > 0) return targetPageInput.val() == 0;

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
		};

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
			if (!this.ccPage || !currentPage || this.isConversationalForm()) {
				return true;
			}

			return this.ccPage == currentPage;
		};

		this.getCurrentPageNumber = function () {
			var currentPageInput = $('#gform_source_page_number_' + this.formId);
			return currentPageInput.length > 0 ? currentPageInput.val() : false;
		};

		this.maybeAddSpinner = function () {
			if (this.isAjax) return;

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

		this.resetStripeStatus = function (form, formId, isLastPage) {
			$('#gf_stripe_response, #gf_stripe_credit_card_last_four, #stripe_credit_card_type').remove();
			form.data('gfstripesubmitting', false);
			const spinnerNodes = document.querySelectorAll('#gform_ajax_spinner_' + formId);
			spinnerNodes.forEach(function (node) {
				node.remove();
			});
			// must do this or the form cannot be submitted again
			if (isLastPage) {
				window["gf_submitting_" + formId] = false;
			}
		};

		this.displayStripeCardError = function (event) {
			if (event.error) {
				if ($('#gform_ajax_spinner_' + this.formId).length > 0) {
					$('#gform_ajax_spinner_' + this.formId).remove();
				}
				Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["displayError"])(event.error.message);
			} else {
				Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["clearErrors"])();
			}
		};

		this.createToken = function (stripe, card) {
			const GFStripeObj = this;
			const activeFeed = this.activeFeed;
			const cardholderName = $('#input_' + GFStripeObj.formId + '_' + GFStripeObj.ccFieldId + '_5').val();
			const tokenData = {
				name: cardholderName,
				address_line1: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line1)),
				address_line2: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_line2)),
				address_city: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_city)),
				address_state: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_state)),
				address_zip: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_zip)),
				address_country: GFMergeTag.replaceMergeTags(this.formId, this.getBillingAddressMergeTag(activeFeed.address_country)),
				currency: gform.applyFilters('gform_stripe_currency', this.currency, this.formId)
			};

			stripe.createToken(card, tokenData).then(function (response) {
				GFStripeObj.elementsResponseHandler(response);
			});
		};

		this.createPaymentMethod = function (stripe, card, country) {
			var GFStripeObj = this,
			    activeFeed = this.activeFeed,
			    countryFieldValue = '';

			if (activeFeed.address_country !== '') {
				countryFieldValue = GFMergeTag.replaceMergeTags(GFStripeObj.formId, GFStripeObj.getBillingAddressMergeTag(activeFeed.address_country));
			}

			if (countryFieldValue !== '' && (typeof country === 'undefined' || country === '')) {
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

		this.maybeHitRateLimits = function () {
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
		};

		/**
   * @function recaptchav3Pending
   * @description Check if recaptcha v3 is enabled and pending a response.
   *
   * @since 5.5.0
   */
		this.recaptchav3Pending = function () {
			const form = this.form;
			const recaptchaField = form.find('.ginput_recaptchav3');
			if (!recaptchaField.length) {
				return false;
			}

			const recaptchaResponse = recaptchaField.find('.gfield_recaptcha_response');

			return !(recaptchaResponse && recaptchaResponse.val());
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
		this.injectHoneypot = event => {
			const form = event.target;
			const shouldInjectHoneypot = (this.isFormSubmission(form) || this.isSaveContinue(form)) && !this.isHeadlessBrowser();

			if (shouldInjectHoneypot) {
				const hashInput = `<input type="hidden" name="version_hash" value="${gf_global.version_hash}" />`;
				form.insertAdjacentHTML('beforeend', hashInput);
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
		this.isSaveContinue = form => {
			const formId = form.dataset.formid;
			const nodes = this.getNodes(`#gform_save_${formId}`, true, form, true);
			return nodes.length > 0 && nodes[0].value === '1';
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
		this.isFormSubmission = form => {
			const formId = form.dataset.formid;
			const targetEl = this.getNodes(`input[name = "gform_target_page_number_${formId}"]`, true, form, true)[0];
			if ('undefined' === typeof targetEl) {
				return false;
			}
			const targetPage = parseInt(targetEl.value);
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
			window.__nightmare || window.domAutomation || window.domAutomationController || // chromium based automation driver.
			window.document.__webdriver_evaluate || window.document.__selenium_evaluate || window.document.__webdriver_script_function || window.document.__webdriver_script_func || window.document.__webdriver_script_fn || window.document.__fxdriver_evaluate || window.document.__driver_unwrapped || window.document.__webdriver_unwrapped || window.document.__driver_evaluate || window.document.__selenium_unwrapped || window.document.__fxdriver_unwrapped || window.document.documentElement.getAttribute('selenium') || window.document.documentElement.getAttribute('webdriver') || window.document.documentElement.getAttribute('driver');
		};

		/**
   * @function getNodes.
   * @description Duplicated from core until the build system can use Gravity Forms utilities.
   *
   * @since 5.
   */
		this.getNodes = (selector = '', convert = false, node = document, custom = false) => {
			const selectorString = custom ? selector : `[data-js="${selector}"]`;
			let nodes = node.querySelectorAll(selectorString);
			if (convert) {
				nodes = this.convertElements(nodes);
			}
			return nodes;
		};

		/**
   * @function convertElements.
   * @description Duplicated from core until the build system can use Gravity Forms utilities.
   *
   * @since 5.0
   */
		this.convertElements = (elements = []) => {
			const converted = [];
			let i = elements.length;
			for (i; i--; converted.unshift(elements[i])); // eslint-disable-line

			return converted;
		};

		/**
   * @function isStripePaymentHandlerInitiated.
   * @description Checks if a Stripe payment handler has been initiated for a form.
   *
   * @since 5.4
   */
		this.isStripePaymentHandlerInitiated = function (formId) {
			return formId in this.stripePaymentHandlers && this.stripePaymentHandlers[formId] !== null && this.stripePaymentHandlers[formId] !== undefined;
		};

		/**
   * End duplicated honeypot logic.
   */

		this.init();
	};
})(jQuery);

/***/ }),

/***/ "./deprecated/js/src/payment-element/request.js":
/*!******************************************************!*\
  !*** ./deprecated/js/src/payment-element/request.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
const request = async (data, isJson = false, action = false, nonce = false) => {

	// Delete gform_ajax if it exists in the FormData object
	if (typeof data.has === 'function' && data.has('gform_ajax')) {

		// Saves a temp gform_ajax so that it can be reset later during form processing.
		data.set('gform_ajax--stripe-temp', data.get('gform_ajax'));

		// Remove the ajax input to prevent Gravity Forms ajax submission handler from handling the submission in the backend during Stripe's validation.
		data.delete('gform_ajax');
	}

	const options = {
		method: 'POST',
		credentials: 'same-origin',
		body: data
	};

	if (isJson) {
		options.headers = { 'Accept': 'application/json', 'content-type': 'application/json' };
	}

	const url = new URL(gforms_stripe_frontend_strings.ajaxurl);

	if (action) {
		url.searchParams.set('action', action);
	}

	if (nonce) {
		url.searchParams.set('nonce', nonce);
	}

	if (gforms_stripe_frontend_strings.is_preview) {
		url.searchParams.set('preview', '1');
	}

	return await fetch(url.toString(), options).then(response => response.json());
};

/* harmony default export */ __webpack_exports__["default"] = (request);

/***/ }),

/***/ "./deprecated/js/src/payment-element/stripe-payments-handler.js":
/*!**********************************************************************!*\
  !*** ./deprecated/js/src/payment-element/stripe-payments-handler.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return StripePaymentsHandler; });
/* harmony import */ var _request__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./request */ "./deprecated/js/src/payment-element/request.js");
/* harmony import */ var _error_handler__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./../error-handler */ "./deprecated/js/src/error-handler.js");
var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };



class StripePaymentsHandler {

	/**
  * StripePaymentsHandler constructor
  *
  * @since 5.0
  *
  * @param {String} apiKey The stripe API key.
  * @param {Object} GFStripeObj The stripe addon JS object.
  */
	constructor(apiKey, GFStripeObj) {
		this.GFStripeObj = GFStripeObj;
		this.apiKey = apiKey;
		this.stripe = null;
		this.elements = null;
		this.card = null;
		this.paymentMethod = null;
		this.draftId = null;
		// A workaround so we can call validate method from outside this class while still accessing the correct scope.
		this.validateForm = this.validate.bind(this);
		this.handlelinkEmailFieldChange = this.reInitiateLinkWithEmailAddress.bind(this);
		this.order = {
			'recurringAmount': 0,
			'paymentAmount': 0
		};
		// The object gets initialized everytime frontend feeds are evaluated so we need to clear any previous errors.
		Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["clearErrors"])();

		if (!this.initStripe() || gforms_stripe_frontend_strings.stripe_connect_enabled !== "1") {
			return;
		}

		// Create the payment element and mount it.
		this.card = this.elements.create('payment');

		// If an email field is mapped to link, bind it to initiate link on change.
		if (GFStripeObj.activeFeed.link_email_field_id) {
			const emailField = document.querySelector('#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id);
			const email = emailField ? emailField.value : '';
			this.handlelinkEmailFieldChange({ target: { value: email } });
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
	getStripeCoupon() {
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
		const couponField = document.querySelector('#field_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.coupon);
		return couponField ? couponField.querySelector('input') : null;
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
		if (!couponInput) {
			return '';
		}
		if (couponInput.className === 'gf_coupon_code') {
			const couponCode = couponInput ? document.querySelector('#gf_coupon_codes_' + this.GFStripeObj.formId).value : null;
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
		if (couponInput && !couponInput.getAttribute('data-listener-added')) {
			couponInput.addEventListener('blur', this.handleCouponChange.bind(this));
			couponInput.setAttribute('data-listener-added', true);
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
	async handleCouponChange(event) {

		if (this.getStripeCouponInput() !== event.target) {
			return;
		}

		if (event.target.classList.contains('gf_coupon_code')) {
			event.target.value = event.target.value.toUpperCase();
		}

		await this.updateStripeCoupon(event.target.value);

		gformCalculateTotalPrice(this.GFStripeObj.formId);
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
	async updateStripeCoupon(coupon_code) {

		// If the coupon code is empty, we don't need to do anything.
		if (!coupon_code) {
			return;
		}

		// Initializing stripeCoupons object if it doesn't exist.
		if (!window.stripeCoupons) {
			window.stripeCoupons = {};
		}

		// If coupon has already been retrieved from Stripe, abort.
		if (window.stripeCoupons[coupon_code]) {
			return;
		}

		// Retreive coupon from Stripe and store it in the window object.
		const response = await Object(_request__WEBPACK_IMPORTED_MODULE_0__["default"])(JSON.stringify({
			'coupon': coupon_code,
			'feed_id': this.GFStripeObj.activeFeed.feedId
		}), true, 'gfstripe_get_stripe_coupon', gforms_stripe_frontend_strings.get_stripe_coupon_nonce);

		window.stripeCoupons[coupon_code] = response.data;
	}

	/**
  * Creates the Stripe object with the given API key.
  *
  * @since 5.0
  *
  * @return {boolean}
  */
	async initStripe() {
		this.stripe = Stripe(this.apiKey);

		const initialPaymentInformation = this.GFStripeObj.activeFeed.initial_payment_information;
		// Round the minimum amount to prevent an error on the stripe side.
		initialPaymentInformation.amount = Math.round(initialPaymentInformation.amount);

		const appearance = this.GFStripeObj.cardStyle;

		if ('payment_method_types' in initialPaymentInformation) {
			initialPaymentInformation.payment_method_types = Object.values(initialPaymentInformation.payment_method_types);
		}

		this.elements = this.stripe.elements(_extends({}, initialPaymentInformation, { appearance }));

		return true;
	}

	/**
  * Mounts the card element to the field node.
  *
  * @since 5.0
  */
	mountCard() {
		this.card.mount('#' + this.GFStripeObj.GFCCField.attr('id'));
	}

	/**
  * Creates a container node for the link element and mounts it.
  *
  * @since 5.0
  */
	mountLink() {
		if (this.link === null) {
			return;
		}
		if (document.querySelectorAll('.stripe-payment-link').length <= 0) {
			const linkDiv = document.createElement('div');
			linkDiv.setAttribute('id', 'stripe-payment-link');
			linkDiv.classList.add('StripeElement--link');
			this.GFStripeObj.GFCCField.before(jQuery(linkDiv));
		}

		this.link.mount('#stripe-payment-link');
	}

	/**
  * Binds event listeners.
  *
  * @since 5.0
  */
	async bindEvents() {
		if (this.card) {
			this.card.on('change', event => {
				if (this.paymentMethod !== null) {
					Object(_error_handler__WEBPACK_IMPORTED_MODULE_1__["clearErrors"])();
				}
				this.paymentMethod = event;
			});
		}

		// Binding events for Stripe Coupon.
		this.bindStripeCoupon();

		const emailField = document.querySelector('#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id);

		if (emailField === null) {
			return;
		}

		emailField.addEventListener('blur', this.handlelinkEmailFieldChange);

		window.addEventListener('load', async function () {
			const emailField = document.querySelector('#input_' + this.GFStripeObj.formId + '_' + this.GFStripeObj.activeFeed.link_email_field_id);
			if (String(emailField.value).toLowerCase().match(/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/) && this.GFStripeObj.isLastPage()) {
				this.handlelinkEmailFieldChange({ target: { value: emailField.value } });
			}
		}.bind(this));
	}

	/**
  * Destroys the current instance of link and creates a new one with value extracted from the passed event.
  *
  * @since 5
  *
  * @param {Object} event an object that contains information about the email input.
  * @return {Promise<void>}
  */
	async reInitiateLinkWithEmailAddress(event) {

		if (this.GFStripeObj.isCreditCardOnPage() === false) {
			return;
		}

		// If there is a Link instance, destroy it.
		this.destroyLink();

		const emailAddress = event.target.value;
		if (emailAddress) {
			this.link = await this.elements.create("linkAuthentication", { defaultValues: { email: emailAddress } });
			this.mountLink();
			this.GFStripeObj.GFCCField.siblings('.gfield #stripe-payment-link').addClass('visible');
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
	async validate(event) {
		// If this is an ajax form submission, we just need to submit the form as everything has already been handled.
		const form = jQuery('#gform_' + this.GFStripeObj.formId);
		if (form.data('isAjaxSubmitting')) {
			form.submit();
			return;
		}

		// Make sure the required information are entered.
		// Link stays incomplete even when email is entered, and it will fail with a friendly message when the confirmation request fails, so skip its frontend validation.
		if (!this.paymentMethod.complete && this.paymentMethod.value.type !== 'link') {
			this.failWithMessage(gforms_stripe_frontend_strings.payment_incomplete, this.GFStripeObj.formId);
			return false;
		}

		gformAddSpinner(this.GFStripeObj.formId);
		const response = await Object(_request__WEBPACK_IMPORTED_MODULE_0__["default"])(this.getFormData(event.target));

		if (response === -1) {
			this.failWithMessage(gforms_stripe_frontend_strings.invalid_nonce, this.GFStripeObj.formId);

			return false;
		}

		if ('success' in response && response.success === false) {
			this.failWithMessage(gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId);

			return false;
		}

		// Invoice for trials are automatically paid.
		if ('invoice_id' in response.data && response.data.invoice_id !== null && 'resume_token' in response.data) {
			const redirect_url = new URL(window.location.href);
			redirect_url.searchParams.append('resume_token', response.data.resume_token);
			redirect_url.searchParams.append('tracking_id', response.data.tracking_id);
			window.location.href = redirect_url.href;
		}

		const is_valid_intent = 'intent' in response.data && response.data.intent !== false && response.data.intent != null && 'client_secret' in response.data.intent;

		const is_valid_submission = 'data' in response && 'is_valid' in response.data && response.data.is_valid && 'resume_token' in response.data;

		const is_spam = 'is_spam' in response.data && response.data.is_spam;

		if (!is_valid_intent && !is_spam && is_valid_submission) {
			this.failWithMessage(gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId);
			return false;
		}

		if (is_valid_submission) {

			// Reset any errors.
			this.resetFormValidationErrors();
			this.draftId = response.data.resume_token;
			// Validate Stripe coupon, if there is a setup fee or trial, the coupon won't be applied to the current payment, so pass validation as it is all handled in the backend.
			if (this.GFStripeObj.activeFeed.hasTrial !== '1' && !this.GFStripeObj.activeFeed.setupFee && !this.isValidCoupon(response.data.total)) {
				this.failWithMessage(gforms_stripe_frontend_strings.coupon_invalid, this.GFStripeObj.formId);

				return false;
			}

			// Do not confirm payment if this is a spam submission.
			if (is_spam) {
				// For spam submissions, redirect to the confirmation page without confirming the payment. This will process the submission as a spam entry without capturing the payment.
				this.handleRedirect(this.getRedirectUrl(response.data.resume_token));
			} else {
				// For non-spam submissions, confirm the payment and redirect to the confirmation page.
				this.confirm(response.data);
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
	isValidCoupon(payment_amount) {
		const coupon = this.getStripeCoupon();
		if (!coupon) {
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
	getFormData(form) {
		const formData = new FormData(form);
		// if gform_submit exist in the request, GFFormDisplay::process_form() will be called even before the AJAX handler.
		formData.delete('gform_submit');
		// Append the payment data to the form.
		const appendParams = {
			'action': 'gfstripe_validate_form',
			'feed_id': this.GFStripeObj.activeFeed.feedId,
			'form_id': this.GFStripeObj.formId,
			'tracking_id': Math.random().toString(36).slice(2, 10),
			'payment_method': this.paymentMethod.value.type,
			'nonce': gforms_stripe_frontend_strings.validate_form_nonce
		};

		Object.keys(appendParams).forEach(key => {
			formData.append(key, appendParams[key]);
		});

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
	updatePaymentAmount(newAmount) {
		if (newAmount <= 0 || this.GFStripeObj.activeFeed.initial_payment_information.mode === 'setup') {
			return;
		}
		// Get amount in cents (or the equivalent subunit for other currencies)
		let total = newAmount * 100;
		// Round total to two decimal places.
		total = Math.round(total * 100) / 100;

		let updatedPaymentInformation = {
			amount: total
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
		updatedPaymentInformation = window.gform.applyFilters('gform_stripe_payment_element_updated_payment_information', updatedPaymentInformation, this.GFStripeObj.activeFeed.initial_payment_information, this.GFStripeObj.activeFeed.feedId, this.GFStripeObj.formId);

		this.elements.update(updatedPaymentInformation);
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
	applyStripeCoupon(total) {

		const coupon = this.getStripeCoupon();
		if (!coupon || !coupon.is_valid) {
			return total;
		}

		if (coupon.percentage_off) {
			total = total - total * (coupon.percentage_off / 100);
		} else if (coupon.amount_off) {
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
	async confirm(confirmData) {

		// Prepare the return URL.
		const redirect_url = this.getRedirectUrl(confirmData.resume_token);
		redirect_url.searchParams.append('tracking_id', confirmData.tracking_id);

		const { error: submitError } = await this.elements.submit();
		if (submitError) {
			this.failWithMessage(submitError.message, this.GFStripeObj.formId);
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
							line1: GFMergeTag.replaceMergeTags(this.GFStripeObj.formId, this.getBillingAddressMergeTag(this.GFStripeObj.activeFeed.address_line1)),
							line2: GFMergeTag.replaceMergeTags(this.GFStripeObj.formId, this.getBillingAddressMergeTag(this.GFStripeObj.activeFeed.address_line2)),
							city: GFMergeTag.replaceMergeTags(this.GFStripeObj.formId, this.getBillingAddressMergeTag(this.GFStripeObj.activeFeed.address_city)),
							state: GFMergeTag.replaceMergeTags(this.GFStripeObj.formId, this.getBillingAddressMergeTag(this.GFStripeObj.activeFeed.address_state)),
							postal_code: GFMergeTag.replaceMergeTags(this.GFStripeObj.formId, this.getBillingAddressMergeTag(this.GFStripeObj.activeFeed.address_zip))
						}
					}
				}
			},
			// let Stripe handle redirection only if the payment method  requires redirection to a third party page,
			// Otherwise, the add-on will handle the redirection.
			redirect: 'if_required'
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
		let isSetupIntent = confirmData.intent.id.indexOf('seti_') === 0;
		try {
			paymentResult = isSetupIntent ? await this.stripe.confirmSetup(paymentData) : await this.stripe.confirmPayment(paymentData);
		} catch (e) {
			console.log(e);
			this.failWithMessage(gforms_stripe_frontend_strings.failed_to_confirm_intent, this.GFStripeObj.formId);
		}

		// If we have a paymentIntent or a setupIntent in the result, the process was successful.
		// Note that confirming could be successful but the intent status is still 'processing' or 'pending'.
		if ('paymentIntent' in paymentResult || 'setupIntent' in paymentResult) {
			this.handlePaymentRedirect(paymentResult, redirect_url);
		} else {
			await this.handleFailedPayment(paymentResult);
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
	handlePaymentRedirect(paymentResult, redirect_url) {
		const intent = paymentResult.paymentIntent ? paymentResult.paymentIntent : paymentResult.setupIntent;
		// Add parameters required for entry processing in the backend.
		const intentTypeString = intent.id.indexOf('seti_') === 0 ? 'setup' : 'payment';
		redirect_url.searchParams.append(intentTypeString + '_intent', intent.id);
		redirect_url.searchParams.append(intentTypeString + '_intent_client_secret', intent.client_secret);
		redirect_url.searchParams.append('redirect_status', intent.status ? 'succeeded' : 'pending');

		this.handleRedirect(redirect_url);
	}

	/**
  * Redirects the user to the confirmation page.
  *
  * @since 5.4.1
  *
  * @param {URL} redirect_url  The redirect URL the user will be taken to after confirmation.
  */
	handleRedirect(redirect_url) {

		// If this is not an AJAX embedded form, redirect the user to the confirmation page.
		if (!this.isAjaxEmbed(this.GFStripeObj.formId)) {
			window.location.href = redirect_url.toString();
		} else {
			// AJAX embeds are handled differently, we need to update the form's action with the redirect URL, and submit it inside the AJAX IFrame.
			jQuery('#gform_' + this.GFStripeObj.formId).attr('action', redirect_url.toString());
			// Prevent running same logic again after submitting the form.
			jQuery('#gform_' + this.GFStripeObj.formId).data('isAjaxSubmitting', true);
			// Keeping this input makes the backend thinks it is not an ajax form, so we need to remove it.
			jQuery('#gform_' + this.GFStripeObj.formId).find('[name="gform_submit"]').remove();
			// Form will be submitted inside the IFrame, once IFrame content is updated, the form element will be replaced with the content of the IFrame.
			jQuery('#gform_' + this.GFStripeObj.formId).submit();
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
	getRedirectUrl(resume_token) {
		const redirect_url = new URL(window.location.href);
		redirect_url.searchParams.append('resume_token', resume_token);
		redirect_url.searchParams.append('feed_id', this.GFStripeObj.activeFeed.feedId);
		redirect_url.searchParams.append('form_id', this.GFStripeObj.formId);
		return redirect_url;
	}

	/**
  * Handles a failed payment attempt.
  *
  * @since 5.0
  *
  * @param {Object} paymentResult The result of confirming a payment intent or a setup intent.
  */
	async handleFailedPayment(paymentResult) {
		let errorMessage = '';
		if ('error' in paymentResult && 'message' in paymentResult.error) {
			errorMessage = paymentResult.error.message;
		}
		this.failWithMessage(errorMessage, this.GFStripeObj.formId);
		// Delete the draft entry created.
		let response = Object(_request__WEBPACK_IMPORTED_MODULE_0__["default"])(JSON.stringify({ 'draft_id': this.draftId }), true, 'gfstripe_delete_draft_entry', gforms_stripe_frontend_strings.delete_draft_nonce);
		// If rate limiting is enabled, increase the errors number at the backend side, and set the new count here.
		if (this.GFStripeObj.hasOwnProperty('cardErrorCount')) {
			response = await Object(_request__WEBPACK_IMPORTED_MODULE_0__["default"])(JSON.stringify({ 'increase_count': true }), true, 'gfstripe_payment_element_check_rate_limiting', gforms_stripe_frontend_strings.rate_limiting_nonce);
			this.GFStripeObj.cardErrorCount = response.data.error_count;
		}
	}

	/**
  * Destroys the stripe objects and removes any DOM nodes created while initializing them.
  *
  * @since 5.0
  */
	destroy() {
		if (this.card) {
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
		if (this.link) {
			this.link.destroy();
			this.link = null;

			const linkContainer = this.GFStripeObj.GFCCField.siblings('.gfield #stripe-payment-link');
			if (linkContainer) {
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
		document.querySelectorAll('.gform_validation_errors, .validation_message').forEach(el => {
			el.remove();
		});
		document.querySelectorAll('.gfield_error').forEach(el => {
			el.classList.remove('gfield_error');
		});
	}

	/**
  * Displays an error message if the flow failed at any point, also clears the loading indicator and resets the form data attributes.
  *
  * @since 5.0
  *
  * @param {String} message The error message to display.
  * @param {int}    formId The form ID.
  */
	failWithMessage(message, formId) {
		message = message ? message : gforms_stripe_frontend_strings.failed_to_process_payment;
		this.GFStripeObj.displayStripeCardError({ error: { message: message } });
		this.GFStripeObj.resetStripeStatus(jQuery('#gform_' + formId), formId, true);
		jQuery('#gform_ajax_spinner_' + formId).remove();
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
	getBillingAddressMergeTag(field) {

		if (field === '') {
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
	getOrderData(total, formId) {

		if (!_gformPriceFields[formId] || this.GFStripeObj.activeFeed === null) {
			return this.order;
		}

		const setUpFieldId = this.GFStripeObj.activeFeed.setupFee;
		let setupFee = 0;
		let productTotal = 0;
		const isTrial = this.GFStripeObj.activeFeed.hasTrial;

		// If this is the setup fee field, or the shipping field, don't add to total.
		if (setUpFieldId) {
			const setupFeeInfo = this.GFStripeObj.getProductFieldPrice(formId, this.GFStripeObj.activeFeed.setupFee);
			setupFee = setupFeeInfo.price * setupFeeInfo.qty;
			// If this field is a setup fee, subtract it from total, so it is not added to the recurring amount.
			total -= setupFee;
		}

		if (this.GFStripeObj.activeFeed.paymentAmount === 'form_total') {
			this.order.recurringAmount = total;
			if (this.isTextCoupon()) {
				this.order.recurringAmount = this.applyStripeCoupon(this.order.recurringAmount);
			}
		} else {
			this.order.recurringAmount = gformCalculateProductPrice(formId, this.GFStripeObj.activeFeed.paymentAmount);
			this.order.recurringAmount = this.applyStripeCoupon(this.order.recurringAmount);
		}

		if (isTrial === '1') {
			this.order.paymentAmount = setupFee;
		} else {
			this.order.paymentAmount = this.order.recurringAmount + setupFee;
		}

		return this.order;
	}

	isTextCoupon() {
		const coupon = this.getStripeCouponInput();
		if (!coupon) {
			return false;
		}

		return !coupon.classList.contains('gf_coupon_code');
	}

	/**
  * Decides whether the form is embedded with the AJAX option on or not.
  *
  * Since 5.2
  *
  * @param {integer} formId The form ID.
  * @returns {boolean}
  */
	isAjaxEmbed(formId) {
		return jQuery('#gform_ajax_frame_' + formId).length >= 1;
	}
}

/***/ }),

/***/ "./node_modules/webpack/buildin/global.js":
/*!***********************************!*\
  !*** (webpack)/buildin/global.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || new Function("return this")();
} catch (e) {
	// This works if the window reference is available
	if (typeof window === "object") g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),

/***/ "./node_modules/whatwg-fetch/fetch.js":
/*!********************************************!*\
  !*** ./node_modules/whatwg-fetch/fetch.js ***!
  \********************************************/
/*! exports provided: Headers, Request, Response, DOMException, fetch */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(global) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Headers", function() { return Headers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Request", function() { return Request; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Response", function() { return Response; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DOMException", function() { return DOMException; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fetch", function() { return fetch; });
/* eslint-disable no-prototype-builtins */
var g =
  (typeof globalThis !== 'undefined' && globalThis) ||
  (typeof self !== 'undefined' && self) ||
  // eslint-disable-next-line no-undef
  (typeof global !== 'undefined' && global) ||
  {}

var support = {
  searchParams: 'URLSearchParams' in g,
  iterable: 'Symbol' in g && 'iterator' in Symbol,
  blob:
    'FileReader' in g &&
    'Blob' in g &&
    (function() {
      try {
        new Blob()
        return true
      } catch (e) {
        return false
      }
    })(),
  formData: 'FormData' in g,
  arrayBuffer: 'ArrayBuffer' in g
}

function isDataView(obj) {
  return obj && DataView.prototype.isPrototypeOf(obj)
}

if (support.arrayBuffer) {
  var viewClasses = [
    '[object Int8Array]',
    '[object Uint8Array]',
    '[object Uint8ClampedArray]',
    '[object Int16Array]',
    '[object Uint16Array]',
    '[object Int32Array]',
    '[object Uint32Array]',
    '[object Float32Array]',
    '[object Float64Array]'
  ]

  var isArrayBufferView =
    ArrayBuffer.isView ||
    function(obj) {
      return obj && viewClasses.indexOf(Object.prototype.toString.call(obj)) > -1
    }
}

function normalizeName(name) {
  if (typeof name !== 'string') {
    name = String(name)
  }
  if (/[^a-z0-9\-#$%&'*+.^_`|~!]/i.test(name) || name === '') {
    throw new TypeError('Invalid character in header field name: "' + name + '"')
  }
  return name.toLowerCase()
}

function normalizeValue(value) {
  if (typeof value !== 'string') {
    value = String(value)
  }
  return value
}

// Build a destructive iterator for the value list
function iteratorFor(items) {
  var iterator = {
    next: function() {
      var value = items.shift()
      return {done: value === undefined, value: value}
    }
  }

  if (support.iterable) {
    iterator[Symbol.iterator] = function() {
      return iterator
    }
  }

  return iterator
}

function Headers(headers) {
  this.map = {}

  if (headers instanceof Headers) {
    headers.forEach(function(value, name) {
      this.append(name, value)
    }, this)
  } else if (Array.isArray(headers)) {
    headers.forEach(function(header) {
      if (header.length != 2) {
        throw new TypeError('Headers constructor: expected name/value pair to be length 2, found' + header.length)
      }
      this.append(header[0], header[1])
    }, this)
  } else if (headers) {
    Object.getOwnPropertyNames(headers).forEach(function(name) {
      this.append(name, headers[name])
    }, this)
  }
}

Headers.prototype.append = function(name, value) {
  name = normalizeName(name)
  value = normalizeValue(value)
  var oldValue = this.map[name]
  this.map[name] = oldValue ? oldValue + ', ' + value : value
}

Headers.prototype['delete'] = function(name) {
  delete this.map[normalizeName(name)]
}

Headers.prototype.get = function(name) {
  name = normalizeName(name)
  return this.has(name) ? this.map[name] : null
}

Headers.prototype.has = function(name) {
  return this.map.hasOwnProperty(normalizeName(name))
}

Headers.prototype.set = function(name, value) {
  this.map[normalizeName(name)] = normalizeValue(value)
}

Headers.prototype.forEach = function(callback, thisArg) {
  for (var name in this.map) {
    if (this.map.hasOwnProperty(name)) {
      callback.call(thisArg, this.map[name], name, this)
    }
  }
}

Headers.prototype.keys = function() {
  var items = []
  this.forEach(function(value, name) {
    items.push(name)
  })
  return iteratorFor(items)
}

Headers.prototype.values = function() {
  var items = []
  this.forEach(function(value) {
    items.push(value)
  })
  return iteratorFor(items)
}

Headers.prototype.entries = function() {
  var items = []
  this.forEach(function(value, name) {
    items.push([name, value])
  })
  return iteratorFor(items)
}

if (support.iterable) {
  Headers.prototype[Symbol.iterator] = Headers.prototype.entries
}

function consumed(body) {
  if (body._noBody) return
  if (body.bodyUsed) {
    return Promise.reject(new TypeError('Already read'))
  }
  body.bodyUsed = true
}

function fileReaderReady(reader) {
  return new Promise(function(resolve, reject) {
    reader.onload = function() {
      resolve(reader.result)
    }
    reader.onerror = function() {
      reject(reader.error)
    }
  })
}

function readBlobAsArrayBuffer(blob) {
  var reader = new FileReader()
  var promise = fileReaderReady(reader)
  reader.readAsArrayBuffer(blob)
  return promise
}

function readBlobAsText(blob) {
  var reader = new FileReader()
  var promise = fileReaderReady(reader)
  var match = /charset=([A-Za-z0-9_-]+)/.exec(blob.type)
  var encoding = match ? match[1] : 'utf-8'
  reader.readAsText(blob, encoding)
  return promise
}

function readArrayBufferAsText(buf) {
  var view = new Uint8Array(buf)
  var chars = new Array(view.length)

  for (var i = 0; i < view.length; i++) {
    chars[i] = String.fromCharCode(view[i])
  }
  return chars.join('')
}

function bufferClone(buf) {
  if (buf.slice) {
    return buf.slice(0)
  } else {
    var view = new Uint8Array(buf.byteLength)
    view.set(new Uint8Array(buf))
    return view.buffer
  }
}

function Body() {
  this.bodyUsed = false

  this._initBody = function(body) {
    /*
      fetch-mock wraps the Response object in an ES6 Proxy to
      provide useful test harness features such as flush. However, on
      ES5 browsers without fetch or Proxy support pollyfills must be used;
      the proxy-pollyfill is unable to proxy an attribute unless it exists
      on the object before the Proxy is created. This change ensures
      Response.bodyUsed exists on the instance, while maintaining the
      semantic of setting Request.bodyUsed in the constructor before
      _initBody is called.
    */
    // eslint-disable-next-line no-self-assign
    this.bodyUsed = this.bodyUsed
    this._bodyInit = body
    if (!body) {
      this._noBody = true;
      this._bodyText = ''
    } else if (typeof body === 'string') {
      this._bodyText = body
    } else if (support.blob && Blob.prototype.isPrototypeOf(body)) {
      this._bodyBlob = body
    } else if (support.formData && FormData.prototype.isPrototypeOf(body)) {
      this._bodyFormData = body
    } else if (support.searchParams && URLSearchParams.prototype.isPrototypeOf(body)) {
      this._bodyText = body.toString()
    } else if (support.arrayBuffer && support.blob && isDataView(body)) {
      this._bodyArrayBuffer = bufferClone(body.buffer)
      // IE 10-11 can't handle a DataView body.
      this._bodyInit = new Blob([this._bodyArrayBuffer])
    } else if (support.arrayBuffer && (ArrayBuffer.prototype.isPrototypeOf(body) || isArrayBufferView(body))) {
      this._bodyArrayBuffer = bufferClone(body)
    } else {
      this._bodyText = body = Object.prototype.toString.call(body)
    }

    if (!this.headers.get('content-type')) {
      if (typeof body === 'string') {
        this.headers.set('content-type', 'text/plain;charset=UTF-8')
      } else if (this._bodyBlob && this._bodyBlob.type) {
        this.headers.set('content-type', this._bodyBlob.type)
      } else if (support.searchParams && URLSearchParams.prototype.isPrototypeOf(body)) {
        this.headers.set('content-type', 'application/x-www-form-urlencoded;charset=UTF-8')
      }
    }
  }

  if (support.blob) {
    this.blob = function() {
      var rejected = consumed(this)
      if (rejected) {
        return rejected
      }

      if (this._bodyBlob) {
        return Promise.resolve(this._bodyBlob)
      } else if (this._bodyArrayBuffer) {
        return Promise.resolve(new Blob([this._bodyArrayBuffer]))
      } else if (this._bodyFormData) {
        throw new Error('could not read FormData body as blob')
      } else {
        return Promise.resolve(new Blob([this._bodyText]))
      }
    }
  }

  this.arrayBuffer = function() {
    if (this._bodyArrayBuffer) {
      var isConsumed = consumed(this)
      if (isConsumed) {
        return isConsumed
      } else if (ArrayBuffer.isView(this._bodyArrayBuffer)) {
        return Promise.resolve(
          this._bodyArrayBuffer.buffer.slice(
            this._bodyArrayBuffer.byteOffset,
            this._bodyArrayBuffer.byteOffset + this._bodyArrayBuffer.byteLength
          )
        )
      } else {
        return Promise.resolve(this._bodyArrayBuffer)
      }
    } else if (support.blob) {
      return this.blob().then(readBlobAsArrayBuffer)
    } else {
      throw new Error('could not read as ArrayBuffer')
    }
  }

  this.text = function() {
    var rejected = consumed(this)
    if (rejected) {
      return rejected
    }

    if (this._bodyBlob) {
      return readBlobAsText(this._bodyBlob)
    } else if (this._bodyArrayBuffer) {
      return Promise.resolve(readArrayBufferAsText(this._bodyArrayBuffer))
    } else if (this._bodyFormData) {
      throw new Error('could not read FormData body as text')
    } else {
      return Promise.resolve(this._bodyText)
    }
  }

  if (support.formData) {
    this.formData = function() {
      return this.text().then(decode)
    }
  }

  this.json = function() {
    return this.text().then(JSON.parse)
  }

  return this
}

// HTTP methods whose capitalization should be normalized
var methods = ['CONNECT', 'DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT', 'TRACE']

function normalizeMethod(method) {
  var upcased = method.toUpperCase()
  return methods.indexOf(upcased) > -1 ? upcased : method
}

function Request(input, options) {
  if (!(this instanceof Request)) {
    throw new TypeError('Please use the "new" operator, this DOM object constructor cannot be called as a function.')
  }

  options = options || {}
  var body = options.body

  if (input instanceof Request) {
    if (input.bodyUsed) {
      throw new TypeError('Already read')
    }
    this.url = input.url
    this.credentials = input.credentials
    if (!options.headers) {
      this.headers = new Headers(input.headers)
    }
    this.method = input.method
    this.mode = input.mode
    this.signal = input.signal
    if (!body && input._bodyInit != null) {
      body = input._bodyInit
      input.bodyUsed = true
    }
  } else {
    this.url = String(input)
  }

  this.credentials = options.credentials || this.credentials || 'same-origin'
  if (options.headers || !this.headers) {
    this.headers = new Headers(options.headers)
  }
  this.method = normalizeMethod(options.method || this.method || 'GET')
  this.mode = options.mode || this.mode || null
  this.signal = options.signal || this.signal || (function () {
    if ('AbortController' in g) {
      var ctrl = new AbortController();
      return ctrl.signal;
    }
  }());
  this.referrer = null

  if ((this.method === 'GET' || this.method === 'HEAD') && body) {
    throw new TypeError('Body not allowed for GET or HEAD requests')
  }
  this._initBody(body)

  if (this.method === 'GET' || this.method === 'HEAD') {
    if (options.cache === 'no-store' || options.cache === 'no-cache') {
      // Search for a '_' parameter in the query string
      var reParamSearch = /([?&])_=[^&]*/
      if (reParamSearch.test(this.url)) {
        // If it already exists then set the value with the current time
        this.url = this.url.replace(reParamSearch, '$1_=' + new Date().getTime())
      } else {
        // Otherwise add a new '_' parameter to the end with the current time
        var reQueryString = /\?/
        this.url += (reQueryString.test(this.url) ? '&' : '?') + '_=' + new Date().getTime()
      }
    }
  }
}

Request.prototype.clone = function() {
  return new Request(this, {body: this._bodyInit})
}

function decode(body) {
  var form = new FormData()
  body
    .trim()
    .split('&')
    .forEach(function(bytes) {
      if (bytes) {
        var split = bytes.split('=')
        var name = split.shift().replace(/\+/g, ' ')
        var value = split.join('=').replace(/\+/g, ' ')
        form.append(decodeURIComponent(name), decodeURIComponent(value))
      }
    })
  return form
}

function parseHeaders(rawHeaders) {
  var headers = new Headers()
  // Replace instances of \r\n and \n followed by at least one space or horizontal tab with a space
  // https://tools.ietf.org/html/rfc7230#section-3.2
  var preProcessedHeaders = rawHeaders.replace(/\r?\n[\t ]+/g, ' ')
  // Avoiding split via regex to work around a common IE11 bug with the core-js 3.6.0 regex polyfill
  // https://github.com/github/fetch/issues/748
  // https://github.com/zloirock/core-js/issues/751
  preProcessedHeaders
    .split('\r')
    .map(function(header) {
      return header.indexOf('\n') === 0 ? header.substr(1, header.length) : header
    })
    .forEach(function(line) {
      var parts = line.split(':')
      var key = parts.shift().trim()
      if (key) {
        var value = parts.join(':').trim()
        try {
          headers.append(key, value)
        } catch (error) {
          console.warn('Response ' + error.message)
        }
      }
    })
  return headers
}

Body.call(Request.prototype)

function Response(bodyInit, options) {
  if (!(this instanceof Response)) {
    throw new TypeError('Please use the "new" operator, this DOM object constructor cannot be called as a function.')
  }
  if (!options) {
    options = {}
  }

  this.type = 'default'
  this.status = options.status === undefined ? 200 : options.status
  if (this.status < 200 || this.status > 599) {
    throw new RangeError("Failed to construct 'Response': The status provided (0) is outside the range [200, 599].")
  }
  this.ok = this.status >= 200 && this.status < 300
  this.statusText = options.statusText === undefined ? '' : '' + options.statusText
  this.headers = new Headers(options.headers)
  this.url = options.url || ''
  this._initBody(bodyInit)
}

Body.call(Response.prototype)

Response.prototype.clone = function() {
  return new Response(this._bodyInit, {
    status: this.status,
    statusText: this.statusText,
    headers: new Headers(this.headers),
    url: this.url
  })
}

Response.error = function() {
  var response = new Response(null, {status: 200, statusText: ''})
  response.ok = false
  response.status = 0
  response.type = 'error'
  return response
}

var redirectStatuses = [301, 302, 303, 307, 308]

Response.redirect = function(url, status) {
  if (redirectStatuses.indexOf(status) === -1) {
    throw new RangeError('Invalid status code')
  }

  return new Response(null, {status: status, headers: {location: url}})
}

var DOMException = g.DOMException
try {
  new DOMException()
} catch (err) {
  DOMException = function(message, name) {
    this.message = message
    this.name = name
    var error = Error(message)
    this.stack = error.stack
  }
  DOMException.prototype = Object.create(Error.prototype)
  DOMException.prototype.constructor = DOMException
}

function fetch(input, init) {
  return new Promise(function(resolve, reject) {
    var request = new Request(input, init)

    if (request.signal && request.signal.aborted) {
      return reject(new DOMException('Aborted', 'AbortError'))
    }

    var xhr = new XMLHttpRequest()

    function abortXhr() {
      xhr.abort()
    }

    xhr.onload = function() {
      var options = {
        statusText: xhr.statusText,
        headers: parseHeaders(xhr.getAllResponseHeaders() || '')
      }
      // This check if specifically for when a user fetches a file locally from the file system
      // Only if the status is out of a normal range
      if (request.url.indexOf('file://') === 0 && (xhr.status < 200 || xhr.status > 599)) {
        options.status = 200;
      } else {
        options.status = xhr.status;
      }
      options.url = 'responseURL' in xhr ? xhr.responseURL : options.headers.get('X-Request-URL')
      var body = 'response' in xhr ? xhr.response : xhr.responseText
      setTimeout(function() {
        resolve(new Response(body, options))
      }, 0)
    }

    xhr.onerror = function() {
      setTimeout(function() {
        reject(new TypeError('Network request failed'))
      }, 0)
    }

    xhr.ontimeout = function() {
      setTimeout(function() {
        reject(new TypeError('Network request timed out'))
      }, 0)
    }

    xhr.onabort = function() {
      setTimeout(function() {
        reject(new DOMException('Aborted', 'AbortError'))
      }, 0)
    }

    function fixUrl(url) {
      try {
        return url === '' && g.location.href ? g.location.href : url
      } catch (e) {
        return url
      }
    }

    xhr.open(request.method, fixUrl(request.url), true)

    if (request.credentials === 'include') {
      xhr.withCredentials = true
    } else if (request.credentials === 'omit') {
      xhr.withCredentials = false
    }

    if ('responseType' in xhr) {
      if (support.blob) {
        xhr.responseType = 'blob'
      } else if (
        support.arrayBuffer
      ) {
        xhr.responseType = 'arraybuffer'
      }
    }

    if (init && typeof init.headers === 'object' && !(init.headers instanceof Headers || (g.Headers && init.headers instanceof g.Headers))) {
      var names = [];
      Object.getOwnPropertyNames(init.headers).forEach(function(name) {
        names.push(normalizeName(name))
        xhr.setRequestHeader(name, normalizeValue(init.headers[name]))
      })
      request.headers.forEach(function(value, name) {
        if (names.indexOf(name) === -1) {
          xhr.setRequestHeader(name, value)
        }
      })
    } else {
      request.headers.forEach(function(value, name) {
        xhr.setRequestHeader(name, value)
      })
    }

    if (request.signal) {
      request.signal.addEventListener('abort', abortXhr)

      xhr.onreadystatechange = function() {
        // DONE (success or failure)
        if (xhr.readyState === 4) {
          request.signal.removeEventListener('abort', abortXhr)
        }
      }
    }

    xhr.send(typeof request._bodyInit === 'undefined' ? null : request._bodyInit)
  })
}

fetch.polyfill = true

if (!g.fetch) {
  g.fetch = fetch
  g.Headers = Headers
  g.Request = Request
  g.Response = Response
}

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ 2:
/*!**********************************************************!*\
  !*** multi whatwg-fetch ./deprecated/js/src/frontend.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! whatwg-fetch */"./node_modules/whatwg-fetch/fetch.js");
module.exports = __webpack_require__(/*! ./deprecated/js/src/frontend.js */"./deprecated/js/src/frontend.js");


/***/ })

/******/ });
//# sourceMappingURL=frontend.js.map