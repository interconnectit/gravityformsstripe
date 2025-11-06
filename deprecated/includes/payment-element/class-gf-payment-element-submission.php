<?php
/**
 * Gravity Forms Stripe Payment Element Submission manager.
 *
 * This class acts as a wrapper for all the logic required to create an entry from a payment element submission.
 *
 * @since     5.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2021, Rocketgenius
 */
class GF_Payment_Element_Submission {

	/**
	 * Instance of a GFStripe object.
	 *
	 * @since 5.0
	 *
	 * @var GFStripe
	 */
	protected $addon;


	/**
	 * The form ID of the form being processed.
	 *
	 * @since 5.0
	 *
	 * @var integer
	 */
	public $form_id;

	/**
	 * The feed ID of the feed being processed.
	 *
	 * @since 5.0
	 *
	 * @var integer
	 */
	public $feed_id;

	/**
	 * The draft ID created after form validation was successful.
	 *
	 * @since 5.0
	 *
	 * @var string
	 */
	public $draft_id;

	/**
	 * The entry ID of the entry being processed.
	 *
	 * @since 5.0
	 *
	 * @var int The entry ID of the entry being processed.
	 */
	public $entry_id;

	/**
	 * The payment object ID used to create the entry being processed (Payment intent, Setup intent or an invoice)
	 *
	 * @since 5.0
	 *
	 * @var int The intent ID used to create the entry being processed.
	 */
	public $payment_object_id;

	/**
	 * The Subscription ID if this submission is for a subscription payment.
	 *
	 * @since 5.0
	 *
	 * @var int The Subscription ID if this submission is for a subscription payment.
	 */
	public $subscription_id;

	/**
	 * GF_Stripe_Payment_Element_Submission constructor.
	 *
	 * @since 5.0
	 *
	 * @param GFStripe $addon Instance of a GFStripe object.
	 */
	public function __construct( $addon ) {
		$this->addon = $addon;
	}

	/**
	 * Validates a form submission.
	 *
	 * @since 5.0
	 *
	 * @param int $form_id The ID of the form being validated.
	 *
	 * @return array The validation result.
	 */
	public function validate( $form_id ) {
		// Modify the entry values as necessary before creating the draft.
		add_filter( 'gform_save_field_value', array( $this, 'stripe_payment_element_save_draft_values' ), 10, 5 );
		GFCommon::log_debug( __METHOD__ . '(): Validating form: ' . $form_id );
		return GFAPI::validate_form( $form_id );
	}

	/**
	 * Creates a draft submission.
	 *
	 * @since 5.0
	 *
	 * @param int $form_id The ID of the form being submitted.
	 *
	 * @return string|WP_Error
	 */
	public function create_draft_submission( $form_id, $stripe_encrypted_params ) {

		$files                           = array();
		$form                            = GFAPI::get_form( $form_id );
		$feed                            = $this->addon->get_feed( rgpost( 'feed_id' ) );
		$tracking_id                     = rgget( 'tracking_id' );
		$field_values                    = RGForms::post( 'gform_field_values' );
		$lead                            = GFFormsModel::get_current_lead();
		$lead['stripe_encrypted_params'] = $stripe_encrypted_params;
		$lead['version_hash']            = rgpost( 'version_hash' );
		require_once GFCommon::get_base_path() . '/form_display.php';
		$form  = GFFormDisplay::update_confirmation( $form, $lead, 'form_saved' );
		$files = $this->handle_file_uploads( $form );

		// Add the password to the draft entry if user registration is active and there's a password field.
		$password_id = $this->get_ur_password_id( $lead, $form );
		if ( $password_id ) {
			$password = rgpost( 'input_' . $password_id, false );
			if ( $password ) {
				$lead['ur_pass'] = wp_hash_password( $password );
			}
		}


		// Adding $_POST and removing User Registration passwords from submitted values.
		add_filter( 'gform_submission_values_pre_save', array( $this, 'filter_draft_submission_values' ), 10, 2 );
		GFCommon::log_debug( __METHOD__ . '(): Creating draft submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
		$resume_token = GFFormsModel::save_draft_submission(
			$form,
			$lead,
			$field_values,
			GFFormDisplay::get_source_page( $form_id ),
			$files,
			GFFormsModel::get_form_unique_id( $form_id ),
			rgars( $form, 'personalData/preventIP' ) ? '' : GFFormsModel::get_ip(),
			esc_url_raw( GFFormsModel::get_current_page_url() ),
			sanitize_key( rgpost( 'gform_resume_token' ) )
		);

		return $resume_token;
	}

	/**
	 * Adds the _POST array and removes the User Registration password from the draft submission values.
	 *
	 * @since 5.5
	 *
	 * @param $submitted_values
	 * @param $form
	 * @return array The filtered submission values with the _POST array added and the password field data removed.
	 */
	public function filter_draft_submission_values( $submitted_values, $form ) {
		$submitted_values['_POST'] = $_POST;

		// Remove the validation action from the submitted values.
		if ( isset( $submitted_values['_POST']['action'] ) ) {
			unset( $submitted_values['_POST']['action'] );
		}

		// Remove the User Registration password from the submitted values array because it has already been hashed and saved into a different key.
		$password_id = $this->get_ur_password_id( $submitted_values, $form );
		if ( $password_id ) {
			unset( $submitted_values[ $password_id ] );
			unset( $submitted_values['_POST'][ 'input_' . $password_id ] );
			if ( isset( $submitted_values['_POST'][ 'input_' . $password_id . '_2' ] ) ) {
				unset( $submitted_values['_POST'][ 'input_' . $password_id . '_2' ] );
			}
		}

		return $submitted_values;
	}

	/**
	 * Gets the id of the password field associated with a User Registration feed (if any).
	 *
	 * @since 5.5
	 *
	 * @param $entry The entry being processed.
	 * @param $form  The form being processed.
	 *
	 * @return false|int Returns the id of the password field if found, false otherwise.
	 */
	private function get_ur_password_id( $entry, $form ) {
		if ( ! function_exists( 'gf_user_registration' ) ) {
			return false;
		}

		$password_id = absint( rgars( gf_user_registration()->get_single_submission_feed( $entry, $form ), 'meta/password' ) );

		return $password_id ? $password_id : false;
	}
	/**
	 * Handles uploading the files in the submission.
	 *
	 * @since 5.0
	 *
	 * @param array $form The form object.
	 *
	 * @return array
	 */
	public function handle_file_uploads( $form ) {
		$file_upload_types = array( 'fileupload', 'post_image' );
		$files = array();
		// Handle single file uploads.
		foreach ( $form['fields'] as $field ) {
			if ( is_a( $field, 'GF_Field_Fileupload') && ! $field->multipleFiles && isset( $_FILES[ 'input_' . $field->id ] ) && ! empty( $_FILES[ 'input_' . $field->id ]['name'] ) ) {
				// File exists in $_FILES and is a single file, just upload it.
				$files[ 'input_' . $field->id ] = $field->upload_file( $form['id'], wp_unslash( $_FILES[ 'input_' . $field->id ] ) );
			} elseif (
				is_a( $field, 'GF_Field_Fileupload')
				&& ! $field->multipleFiles
				&& empty( $_POST[ 'input_' . $field->id ] )
				&& ! empty( $_POST['gform_uploaded_files'] )
			) {
				// File was uploaded in a previous submission, we need to extract the temp file name and then move it
				$file_data = json_decode( stripslashes( $_POST['gform_uploaded_files'] ), true );
				foreach ( $file_data as $input_id => $file_name ) {
					if ( $input_id == 'input_' . $field->id ) {
						$file_extension                 = pathinfo( $file_name, PATHINFO_EXTENSION );
						$files[ 'input_' . $field->id ] = array(
							'uploaded_filename' => $file_name,
							'temp_filename'     => $_POST['gform_unique_id'] . '_input_' . $field->id . '.' . $file_extension,
						);

						$files[ 'input_' . $field->id ] = $field->move_temp_file( $form['id'], $files[ 'input_' . $field->id ] );
					}
				}
			}
		}

		// Handle multi file uploads.
		$multi_file_uploads_json = str_replace( '\\', '', rgar( $_POST, 'gform_uploaded_files' ) );
		$multi_file_uploads      = json_decode( $multi_file_uploads_json, true );
		if ( ! empty( $multi_file_uploads ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( is_a( $field, 'GF_Field_Fileupload') && $field->multipleFiles && isset( $multi_file_uploads[ 'input_' . $field->id ] ) ) {
					$files[ 'input_' . $field->id ] = array();
					foreach ( $multi_file_uploads[ 'input_' . $field->id ] as $file_upload_info ) {
						$files[ 'input_' . $field->id ][] = $field->move_temp_file( $form['id'], $file_upload_info );
					}
					$files[ 'input_' . $field->id ] = json_encode( $files[ 'input_' . $field->id ] );
				}
			}
		}

		return $files;
	}


	/**
	 * Processes a form submission.
	 *
	 * @since 5.0
	 *
	 * @param int                                       $form_id           The id of the form being submitted.
	 * @param int                                       $feed_id           The id of the feed being processed.
	 * @param int                                       $draft_id          The id of the draft created when checkout started.
	 * @param \Stripe\PaymentIntent|\Stripe\SetupIntent $intent            The intent created when checkout started.
	 * @param int|null                                  $subscription_id   The subscription ID if this is a subscription payment.
	 *
	 * @return false|void
	 */
	public function process_submission( $form_id, $feed_id, $draft_id, $intent, $subscription_id = null ) {

		// Store the IDs, because once the form processing starts, other methods will be called that need to use the same values.
		$this->draft_id          = $draft_id;
		$this->form_id           = $form_id;
		$this->feed_id           = $feed_id;
		$this->payment_object_id = $intent ? $intent->id : null;
		$this->subscription_id   = $subscription_id;

		$form        = GFAPI::get_form( $this->form_id );
		$feed        = $this->addon->get_feed( $feed_id );
		$tracking_id = rgget( 'tracking_id' );

		// Prepare the submission values from the draft created while validating the form.
		$draft      = GFFormsModel::get_draft_submission_values( $this->draft_id );
		$submission = json_decode( rgar( $draft, 'submission' ), true );
		if ( ! $submission ) {
			$this->addon->log_debug( __METHOD__ . '(): Aborting Submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), Unable to retrieve the submission values from the draft' . ',  tracking id: ' . $tracking_id );
			return false;
		}

		// Gravity Flow stripe extension should not run until the actual form submission is being triggered.
		if ( function_exists( 'gravity_flow' ) ) {
			remove_action( 'gform_post_add_entry', array( gravity_flow(), 'action_gform_post_add_entry' ) );
		}

		// This adds the entry, but doesn't run the complete form processing flow.
		GFCommon::log_debug( __METHOD__ . '(): Creating entry for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
		$this->entry_id = GFAPI::add_entry( $submission['partial_entry'] );
		$this->addon->log_debug( __METHOD__ . '(): Entry with Id ' . $this->entry_id . ' created for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id:' . $tracking_id );
		// If there is a user registration password in the partial entry, add it to the entry meta.
		if ( ! empty( $submission['partial_entry']['ur_pass'] ) ) {
			gform_update_meta( $this->entry_id, 'userregistration_password', $submission['partial_entry']['ur_pass'] );
		}

		if ( function_exists( 'gravity_flow' ) ) {
			add_action( 'gform_post_add_entry', array( gravity_flow(), 'action_gform_post_add_entry' ), 10, 2 );
		}
		global $_gf_uploaded_files;
		$_gf_uploaded_files = $submission['files'];
		// Populate $_POST from the submission values and the intent values.
		$this->prepare_post_values( $submission, $intent, $form );

		// `GFFormsModel::$uploaded_files` is wiped out during `GFFormDisplay::process_form()`
		// So setting it before processing the submission will not work
		// This filter allows us to add the file values before the field values are being saved to the entry meta directly.
		add_filter(
			'gform_save_field_value',
			function( $value, $entry, $field, $form, $input_id ) {

				if ( empty( $_POST[ 'input_' . $input_id ] ) ) {
					return $value;
				}

				$input_post_value = filter_var( $_POST[ 'input_' . $input_id ], FILTER_SANITIZE_URL );

				if ( $entry['id'] !== $this->entry_id || ! $input_post_value ) {
					return $value;
				}

				if ( in_array( $field->type, array( 'fileupload', 'post_image' ) ) ) {

					GFFormsModel::$uploaded_files[ $form['id'] ] = array( 'input_' . $input_id => $input_post_value );
					if ( $field->type ===  'post_image' ) {
						return $field->get_value_save_entry( $value, $form, 'input_' . $input_id, $entry['id'], $entry );
					}

					return $input_post_value;
				}

				return $value;
			},
			1,
			5
		);

		// since we are simulating a form submission, we need to set the target page to be always 0
		// in case of a multi-page form, instead of just validating the page, the form will be actually submitted.
		add_filter(
			'gform_target_page',
			function( $page_number, $form, $current_page, $field_values ) {
				return 0;
			},
			1,
			4
		);

		// At this point validation has already been run and passed, so we will bypass the second validation that would occur during GFFormDisplay::process_form() to avoid inconsistent behavior.
		add_filter(
			'gform_validation',
			function( $validation_result, $form ) {
				$validation_result['is_valid'] = true;
				return $validation_result;
			},
			1000,
			2
		);

		// The require login nonce was validated earlier by GF_Stripe_Payment_Element::start_checkout().
		add_filter( "gform_require_login_{$this->form_id}", '__return_false', 99 );

		// Run the complete form processing flow.
		require_once GFCommon::get_base_path() . '/form_display.php';

		// We need to use the POST to retrieve values since all relevant values won't be available in the draft entry.
		if ( $form && GFFormDisplay::has_conditional_logic( $form ) ) {
			add_filter( 'gform_use_post_value_for_conditional_logic_save_entry', '__return_true' );
		}

		// Delete the draft.
		GFFormsModel::delete_draft_submission( $this->draft_id );

		// Process the form
		GFCommon::log_debug( __METHOD__ . '(): Processing submission for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
		GFFormDisplay::process_form( $this->form_id, GFFormDisplay::SUBMISSION_INITIATED_BY_WEBFORM );

		// Get the entry and form to handle confirmation.
		$entry = GFAPI::get_entry( $this->entry_id );
		$form  = GFAPI::get_form( $this->form_id );
		GFCommon::log_debug( __METHOD__ . '(): handling confirmation for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id );
		$confirmation = GFFormDisplay::handle_confirmation( $form, $entry, isset( $_POST['gform_ajax'] ) );

		$this->addon->log_debug( __METHOD__ . '(): Submission Confirmation for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id . ' :- ' . print_r( $confirmation, true ) );
		if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
			header( "Location: {$confirmation['redirect']}" );
			exit;
		}

		GFFormDisplay::$submission[ $this->form_id ] = array(
			'is_confirmation'      => true,
			'confirmation_message' => $confirmation,
			'form'                 => $form,
			'lead'                 => $entry,
		);
	}


	/**
	 * Extracts the order data from the submission.
	 *
	 * This function will also create a temporary entry array that is not saved to DB to be used later with other functions.
	 *
	 * @since 5.0
	 *
	 * @param int   $feed_id The id of the current feed object being processed.
	 * @param int   $form_id The id of the current form object being processed.
	 * @param array $entry   The entry being processed.
	 *
	 * @return array The order data including the temporary entry array.
	 */
	public function extract_order_from_submission( $feed_id, $form_id, $entry = null ) {
		$feed            = $this->addon->get_feed( $feed_id );
		$form            = GFAPI::get_form( $form_id );
		$form_meta       = GFFormsModel::get_form_meta( $form['id'] );
		$temp_lead       = $entry === null ? GFFormsModel::create_lead( $form_meta ) : $entry;
		$submission_data = $this->addon->get_submission_data( $feed, $form, $temp_lead );
		$line_items      = array();
		$item_total      = 0;
		$shipping        = 0;
		$discount_total  = 0;

		$items = rgar( $submission_data, 'line_items', rgar( $submission_data, 'items', array() ) );
		foreach ( $items as $item ) {
			if ( rgar( $item, 'is_shipping' ) && $item['is_shipping'] === 1 ) {
				$shipping = $item['unit_price'] * $item['quantity'];
			} else {
				$line_items[] = array(
					'name'        => GFCommon::safe_substr( $item['name'], 0, 127 ),
					'description' => GFCommon::safe_substr( $item['description'], 0, 127 ),
					'unit_amount' => array(
						'value'         => strval( $item['unit_price'] ),
						'currency_code' => rgar( $temp_lead, 'currency' ),
					),
					'quantity'    => $item['quantity'],
				);

				$item_total += GFCommon::to_number( $item['unit_price'] * $item['quantity'] );
			}
		}

		foreach ( $submission_data['discounts'] as $discount ) {
			$discount_total += GFCommon::to_number( $discount['unit_price'] * $discount['quantity'] );
		}

		return array(
			'line_items'    => $line_items,
			'subTotal'      => $item_total,
			'total'         => $submission_data['payment_amount'],
			'shipping'      => $shipping,
			'discountTotal' => $discount_total,
			'setup_fee'     => rgar( $submission_data, 'setup_fee', 0 ),
			'trial_days'    => rgars( $feed, 'meta/trialPeriod' ) ? $submission_data['trial'] : 0,
			'temp_lead'     => $temp_lead,
			'submission'    => $submission_data,
		);
	}

	/**
	 * Populate POST values from the intent and submission data before simulating form processing.
	 *
	 * @since 5.0
	 * @since 5.2 Added the form parameter.
	 *
	 * @param array                 $submission             The submission data.
	 * @param \Stripe\PaymentIntent $intent                 The payment intent.
	 * @param array                 $form                   The form being processed.
	 *
	 * @return void
	 */
	private function prepare_post_values( $submission, $intent, $form ) {

		$_POST = $submission['submitted_values']['_POST'];

		// Resetting gform_ajax if this is an AJAX form.
		if ( ! rgempty( 'gform_ajax--stripe-temp', $_POST ) ) {
			$_POST['gform_ajax'] = $_POST['gform_ajax--stripe-temp'];

			// Setting is_submit_{form_id} because that is needed to process an AJAX form.
			parse_str( rgpost( 'gform_ajax' ), $args );
			$form_id                          = isset( $args['form_id'] ) ? absint( $args['form_id'] ) : 0;
			$_POST[ 'is_submit_' . $form_id ] = '1';

			// Sets gform_field_values to an empty string when it is set to an empty array.
			// Gets around an issue with GF core that sets gform_field_values to an empty array instead of an empty string, causing a PHP notice.
			$field_values = rgpost( 'gform_field_values' );
			if ( is_array( $field_values ) && empty( $field_values ) ) {
				$_POST['gform_field_values'] = '';
			}
		}

		$files = rgar( $submission, 'files' );
		if ( ! empty( $files ) ) {
			foreach ( $files as $input_id => $file_data ) {
				$_POST[ $input_id ] = $file_data;
			}
		}

		$post_images = \GFAPI::get_fields_by_type( $form, 'post_image', true );
		foreach ( $post_images as $field ) {
			if ( ! isset( $submission['submitted_values'][ $field->id ] ) ) {
				continue;
			}

			foreach ( $submission['submitted_values'][ $field->id ] as $key => $value ) {
				$_POST[ 'input_' . str_replace( '.', '_', $key ) ] = $value;
			}
		}

		if ( rgars( $intent, 'charges/data/0/payment_method_details/card/brand' ) ) {
			$_POST['stripe_credit_card_type']      = rgars( $intent, 'charges/data/0/payment_method_details/card/brand' );
			$_POST['stripe_credit_card_last_four'] = 'XXXXXXXXXXXX' . rgars( $intent, 'charges/data/0/payment_method_details/card/last4' );
		} elseif ( rgars( $intent, 'payment_method/card/brand' ) ) {
			$_POST['stripe_credit_card_type']      = rgars( $intent, 'payment_method/card/brand' );
			$_POST['stripe_credit_card_last_four'] = 'XXXXXXXXXXXX' . rgars( $intent, 'payment_method/card/last4' );
		} else {
			$payment_method_type = rgars( $intent, 'payment_method/type' );
			$payment_method_data = rgars( $intent, 'payment_method/' . $payment_method_type );

			if ( empty( $payment_method_data ) ) {
				$billing_details = rgars( $intent, 'payment_method/billing_details' );
				if ( is_object( $billing_details ) ) {
					$payment_method_data = $billing_details->toArray();
				}
			}

			switch ( $payment_method_type ) {
				case 'link':
					$payment_method_data_string = 'Email: ' . $payment_method_data['email'];

					break;

				default:
					if ( is_object( $payment_method_data ) || is_array( $payment_method_data ) ) {
						$payment_method_data        = (array) $payment_method_data;
						$payment_method_data_string = '';
						foreach ( $payment_method_data as $key => $value ) {
							if ( is_array( $key ) || is_object( $key ) || is_array( $value ) || is_object( $value ) || empty( $value ) ) {
								continue;
							}
							$payment_method_data_string .= ucwords( str_replace( '_', ' ', $key ) ) . ': ' . ucwords( str_replace( '_', ' ', $value ) ) . "\n";
						}
					} else {
						$payment_method_data_string = $payment_method_data;
					}
			}

			$_POST['stripe_credit_card_type']      = ucwords( str_replace( '_', ' ', $payment_method_type ) );
			$_POST['stripe_credit_card_last_four'] = $payment_method_data_string;
		}
	}

	/**
	 * Hooks to the pre save lead filter and returns the current entry ID.
	 *
	 * This prevents creating a new entry when calling process_form().
	 *
	 * @since 5.0
	 *
	 * @param int   $id   The ID of the entry.
	 * @param array $form The current form being processed.
	 *
	 * @return int
	 */
	public function get_pending_entry_id( $id, $form ) {
		if ( rgar( $form, 'id' ) == $this->form_id ) {
			return $this->entry_id;
		}

		return $id;
	}

	/**
	 * Hooks to the skip field validation to skip the fields validating while simulating form processing as the fields were already validated but would fail state validation.
	 *
	 * @since 5.0
	 *
	 * @param array    $result  An array containing the validation result properties.
	 * @param mixed    $value   The field value currently being validated.
	 * @param array    $form    The form currently being validated.
	 * @param GF_Field $field   The field currently being validated.
	 *
	 * @return array|mixed
	 */
	public function maybe_skip_field_validation( $result, $value, $form, $field ) {
		if ( rgar( $form, 'id' ) == $this->form_id ) {
			return array(
				'is_valid' => true,
				'message'  => '',
			);
		}

		return $result;
	}

	/**
	 * Filter the values that get saved to the draft entry when using the Payment Element.
	 *
	 * @since 5.2
	 *
	 * @param string|array $value    The fields input value.
	 * @param array        $lead     The current entry object.
	 * @param GF_Field     $field    The current field object.
	 * @param array        $form     The current form object.
	 * @param string       $input_id The ID of the input being saved or the field ID for single input field types.
	 *
	 * @return string|array
	 */
	public function stripe_payment_element_save_draft_values( $value, $lead, $field, $form, $input_id ) {

		// Only modify the values if this is the Stripe validation submission.
		if ( rgpost( 'action' ) === 'gfstripe_validate_form' ) {

			// Modify list field values to have the expected format.
			if ( $field->type === 'list' ) {
				$value = maybe_unserialize( $value );

				if ( ! is_array( $value ) || ! $field->enableColumns ) {
					return $value;
				}

				if ( ! is_array( $value[0] ) ) {
					return $value;
				}

				$modified_value = array();
				array_walk_recursive(
					$value,
					function( $val, $key ) use ( &$modified_value ) {
						$modified_value = array_merge( $modified_value, is_array( $val ) ? array_values( $val ) : array( $val ) );
					}
				);

				return $modified_value;
			}
		}

		return $value;
	}
}
