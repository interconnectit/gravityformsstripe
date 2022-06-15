<?php


/**
 * Gravity Forms Stripe billing portal handler.
 *
 * This class acts as a wrapper for all things for creating/managing the self-serve billing portal links.
 *
 * @see https://stripe.com/docs/billing/subscriptions/customer-portal
 *
 * @since     4.2
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2021, Rocketgenius
 */
class GF_Stripe_Billing_Portal {

	/**
	 * Instance of a GFStripe object.
	 *
	 * @since 4.2
	 *
	 * @var GFStripe
	 */
	protected $addon;

	/**
	 * The logged in user that the links are being retrieved for.
	 *
	 * @since 4.2
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * If we are getting the subscriptions made by a specific form only, this holds the form id.
	 *
	 * @since 4.2
	 *
	 * @var integer
	 */
	protected $form_id = 0;

	/**
	 * If we should show the inactive subscriptions as well.
	 *
	 * @since 4.2
	 *
	 * @var integer
	 */
	protected $show_inactive = true;

	/**
	 * Stores any errors that might happen while executing the shortcode.
	 *
	 * @since 4.2
	 *
	 * @var array
	 */
	protected $shortcode_errors = array();

	/**
	 * GF_Stripe_Billing_Portal constructor.
	 *
	 * @since 4.2
	 *
	 * @param GFStripe $addon Instance of a GFStripe object.
	 */
	public function __construct( $addon ) {

		$this->addon = $addon;

	}

	/**
	 * Create a billing portal link for the Stripe customer created by the given entry.
	 *
	 * @since 4.2
	 *
	 * @param array $entry The entry that created the Stripe customer.
	 *
	 * @return string|bool
	 */
	public function get_entry_link( $entry ) {
		$customer_id = gform_get_meta( rgar( $entry, 'id' ), 'stripe_customer_id' );
		if ( empty( $customer_id ) ) {
			$this->addon->log_debug( __METHOD__ . '(): No stripe_customer_id for entry #' . rgar( $entry, 'id' ) );

			return '';
		}

		$api = $this->get_api_for_entry( $entry );

		$link = $api->get_billing_portal_link( $customer_id );

		if ( is_wp_error( $link ) ) {
			$this->addon->log_error( __METHOD__ . '(): Unable to get portal link' . $link->get_error_message() );
			return false;
		}

		return $link;
	}

	/**
	* Displays the stripe self serve billing portal links for the logged in user.
	*
	* @since 4.2
	*
	* @param string $shortcode_string The full shortcode string.
	* @param array  $attributes       The attributes within the shortcode.
	* @param string $content          The content of the shortcode, if available.
	*
	* @return string
	*/
	public function stripe_customer_portal_link_shortcode( $string, $attributes, $content ) {

		extract(
			shortcode_atts(
				array(
					'show_inactive' => true,
					'id'            => 0,
					'redirect_url'  => '',
				),
				$attributes
			)
		);

		$form_id = $id && (int) $id > 0 ? $id : 0;

		return $this->build_shortcode_ui( $form_id, $show_inactive, $redirect_url );

	}

	/**
	 * Build the markup for the shortcode given the provided attributes.
	 *
	 * @since 4.2
	 *
	 * @param integer $form_id        If provided only the subscriptions for this form will be outputted.
	 * @param bool    $show_inactive  Whether to show the inactive subscriptions or not.
	 * @param string  $redirect_url   Where to redirect the user to login.
	 *
	 * @return string
	 */
	protected function build_shortcode_ui( $form_id, $show_inactive, $redirect_url ) {
		$this->form_id       = $form_id;
		$this->show_inactive = $show_inactive;

		if ( ! $this->get_user() ) {

			return $this->get_redirect_script( $redirect_url );

		}

		$subscriptions = $this->get_user_subscriptions();

		return $this->get_subscriptions_management_markup( $subscriptions );

	}

	/**
	 * Prints the required assets for styling and handling errors.
	 *
	 * To display and handle the errors correctly we need the filtered styles for the form and only two scripts.
	 *
	 * @since 4.2
	 *
	 * @param array $entry The entry used to get the form to print the assets for.
	 */
	protected function print_assets( $entry ) {

		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
		}

		$form   = GFAPI::get_form( rgar( $entry, 'form_id' ) );
		$assets = \GFFormDisplay::get_form_enqueue_assets( $form );

		foreach ( $assets as $asset ) {

			// To get the handle name we need to make it accessible as it is a protected property.
			$reflection      = new ReflectionClass( $asset );
			$handle_property = $reflection->getProperty( 'handle' );
			$handle_property->setAccessible( true );
			$asset_handle = $handle_property->getValue( $asset );

			// we only need styles, wp-a11y for accessibility, jQuery for some dom handling.
			if ( $asset instanceof GF_Script_Asset === true && ! in_array( $asset_handle, array( 'wp-a11y', 'jquery' ) ) ) {
				continue;
			}

			$asset->print_asset();

		}

	}

	/**
	 * Returns the no subscriptions found message.
	 *
	 * @since 4.2
	 *
	 * @return string
	 */
	private function get_no_subscriptions_found_message() {
		$message = __( 'You don\'t have any subscriptions.', 'gravityformsstripe' );

		/**
		 * Filters the no subscriptions found message.
		 *
		 * @since 4.2
		 *
		 * @param string $message The message to filter.
		 */
		return gf_apply_filters( array( 'gform_stripe_no_subscriptions_found_message', $this->form_id ), $message );
	}

	/**
	 * Outputs JS code to redirect the user if not logged in.
	 *
	 * @since 4.2
	 *
	 * @param string $redirect_url where to redirect the user to login.
	 *
	 * @return string
	 */
	private function get_redirect_script( $redirect_url = '' ) {

		$current_page_url = \RGFormsModel::get_current_page_url();

		if ( ! $redirect_url ) {
			$redirect_url = wp_login_url( $current_page_url );
		} else {
			$redirect_url = add_query_arg(
				array(
					'redirect_to' => urlencode( $current_page_url ),
				),
				$redirect_url
			);
		}

		return '
		
		<script type="text/javascript">
			window.location.href = "' . $redirect_url . '";
		</script>
		
		';
	}

	/**
	 * Checks if a request to redirect the logged in user to the stripe billing patrol is present, creates the link and redirects the user to it.
	 *
	 * @since 4.2
	 */
	public function maybe_redirect_logged_in_user_to_self_serve_link() {

		if (
			is_user_logged_in()
			&& rgpost( 'gforms_stripe_entry_id' )
			&& rgpost( 'gforms_stripe_customer_id' )
			&& wp_verify_nonce( rgpost( 'gforms_stripe_self_serve_link_nonce' ), 'gforms_stripe_self_serve_link_' . rgpost( 'gforms_stripe_customer_id' ) )
			) {

			$entry = GFAPI::get_entry( rgpost( 'gforms_stripe_entry_id' ) );
			$link  = $this->get_entry_link( $entry );

			if ( $link ) {
				wp_redirect( $link );
			} else {
				$this->print_assets( $entry );
				$this->shortcode_errors[] = array(
					'message'      => __( 'There was an error generating your Customer Portal link.', 'gravityformsstripe' ),
					'subscription' => array(
						'entry_id' => $entry['id'],
						'title'    => rgpost( 'gforms_stripe_subscription_title' ),
					),
				);
			}
		}

	}


	/**
	 * Passes the subscriptions information to the view and returns the generated markup
	 *
	 * @since 4.2
	 *
	 * @param array $subscriptions Subscription information for a user.
	 *
	 * @return string
	 */
	private function get_subscriptions_management_markup( $subscriptions ) {

		$no_subscriptions_found_message = $this->get_no_subscriptions_found_message();
		$errors                         = $this->shortcode_errors;

		$markup = '';
		ob_start();
			include 'views/subscription-information.php';
		$markup .= ob_get_clean();

		/**
		 * Filters the subscriptions details markup.
		 *
		 * @since 4.2
		 *
		 * @param string  $markup  The list markup.
		 * @param array   $subscriptions {
		 *    Array of subscription information.
		 *
		 *    @type string  $plan_title The subscription plan title.
		 *    @type string  $currency   The subscription currency.
		 *    @type integer $start_date The subscription start date.
		 *    @type double  $price      The subscription price.
		 *    @type string  $frequency  The subscription frequency.
		 *    @type string  $status     The subscription status.
		 * }
		 * @param integer $form_id The form ID to get the subscriptions for.
		 */
		return gf_apply_filters( array( 'gform_stripe_subscriptions_self_serve_markup', $this->form_id ), $markup, $subscriptions, $this->form_id );
	}


	/**
	 * Returns the current logged in user.
	 *
	 * @since 4.2
	 *
	 * @return false|WP_User
	 */
	private function get_user() {

		if ( $this->user ) {
			return $this->user;
		}

		if ( is_user_logged_in() ) {

			$this->user = wp_get_current_user();

			return $this->user;
		}

		return false;

	}

	/**
	 * Returns the Stripe subscription object that is associated for a specific entry.
	 *
	 * @since 4.2
	 *
	 * @param array $entry The entry to get the subscription object for.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	private function get_stripe_subscription_for_entry( $entry ) {

		$api             = $this->get_api_for_entry( $entry );
		$subscription_id = rgar( $entry, 'transaction_id' );
		$subscription    = $api->get_subscription( $subscription_id );

		if ( is_wp_error( $subscription ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Failed to retrieve Stripe subscription object for entry ' . rgar( $entry, 'id' ) . ', error : ' . $subscription->get_error_message() );
		}

		return $subscription;

	}

	/**
	 * Retrieves the stripe product object for a subscription that is linked to a certain entry.
	 *
	 * @since 4.2
	 *
	 * @param integer $entry      The entry we are getting the product for.
	 * @param string  $product_id The product ID.
	 *
	 * @return Stripe\Product|WP_Error
	 */
	private function get_stripe_subscription_product_for_entry( $entry, $product_id ) {

		$api     = $this->get_api_for_entry( $entry );
		$product = $api->get_product( $product_id );

		if ( is_wp_error( $product ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Failed to retrieve Stripe product  ' . $product_id . ', error : ' . $subscription->get_error_message() );
		}

		return $product;
	}

	/**
	 * Looks for and returns the IDs of all the stripe entries that have a subscription made with the provided email address.
	 *
	 * Any subscription requires a customer_id, which is created before the subscription itself is created and is saved to the entry's meta with the stripe_customer_id key.
	 * We are using this key and the email to find the subscription entry(s), where the customer_id(s) is saved, which is needed to generate the link.
	 *
	 * @since 4.2
	 *
	 * @param string $email The email address to use for the search.
	 *
	 * @return array A list of entry IDs that have subscriptions made by the provided email address.
	 */
	private function get_email_entry_ids( $email ) {

		$entry_ids = GFAPI::get_entry_ids(
			$this->form_id,
			array(
				'transaction_type' => 2,
				'is_fulfilled'     => 1,
				'field_filters'    => array(
					array(
						'value' => $email,
					),
					array(
						'key'   => 'payment_gateway',
						'value' => 'gravityformsstripe',
					),
					array(
						'key'      => 'stripe_customer_id',
						'operator' => '<>',
						'value'    => '',
					),
				),
			)
		);

		return $entry_ids;

	}

	/**
	 * Gets all the subscriptions and their details for the provided email.
	 *
	 * @since 4.2
	 *
	 * @param string $email The email address to use for the search.
	 *
	 * @return array The subscriptions found.
	 */
	private function get_email_subscriptions( $email ) {

		$subscriptions = array();
		$entry_ids     = $this->get_email_entry_ids( $email );

		foreach ( $entry_ids as $entry_id ) {

			$entry = GFAPI::get_entry( $entry_id );

			$stripe_subscription = $this->get_stripe_subscription_for_entry( $entry );

			if (
				is_wp_error( $stripe_subscription )
				|| ( $this->show_inactive !== true && $stripe_subscription->status === 'canceled' )
			) {
				continue;
			}

			$stripe_product = $this->get_stripe_subscription_product_for_entry( $entry, $stripe_subscription->plan->product );
			$product_name   = __( 'Subscription', 'gravityformsstripe' );
			if ( ! is_wp_error( $stripe_product ) ) {
				$product_name = $stripe_product->name;
			}

			$subscription['plan_title'] = $product_name;
			$subscription['currency']   = strtoupper( $stripe_subscription->plan->currency );
			$subscription['start_date'] = $stripe_subscription->start_date;
			$subscription['price']      = $this->addon->get_amount_import( $stripe_subscription->plan->amount, $subscription['currency'] );
			$subscription['frequency']  = $stripe_subscription->plan->interval;
			$subscription['status']     = $stripe_subscription->status;

			if ( $stripe_subscription->cancel_at_period_end !== false && $stripe_subscription->current_period_end > time() ) {
				/* Translators: 1: Subscription end date */
				$subscription['status'] .= ' ' . sprintf( __( 'until %s', 'gravityformsstripe' ), date( 'M, jS Y', $stripe_subscription->current_period_end ) );
			}

			if ( $stripe_subscription->status === 'trialing' ) {
				/* Translators: 1: subscription first billing period date after trial period is over */
				$subscription['frequency'] .= ' ' . sprintf( __( 'starting from %s', 'gravityformsstripe' ), date( 'M, jS Y', $stripe_subscription->trial_end ) );
			}

			$subscription['customer_id'] = gform_get_meta( rgar( $entry, 'id' ), 'stripe_customer_id' );

			$subscriptions[ $entry_id ] = $subscription;

		}

		return $subscriptions;
	}

	/**
	 * Gets all the subscriptions and their details for the user.
	 *
	 * @since 4.2
	 *
	 * @return array The subscriptions found or an empty array if no subscriptions were found.
	 */
	private function get_user_subscriptions() {
		$user = $this->get_user();
		if ( ! is_a( $user, 'WP_User' ) || empty( $user->user_email ) ) {
			return array();
		}

		return $this->get_email_subscriptions( $user->user_email );
	}

	/**
	 * Initializes an API object using the settings found in the feed or the general settings if no feeds found or if no settings were saved in the feed.
	 *
	 * Stripe has a feature that allows admins to connect to a different stripe account on feed level.
	 *
	 * @since 4.2
	 *
	 * @param array $entry The entry to get the API object for.
	 *
	 * @return GF_Stripe_API
	 */
	private function get_api_for_entry( $entry ) {

		$feed = $this->addon->get_payment_feed( $entry );

		if ( ! empty( $feed ) && $this->addon->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$api = $this->addon->include_stripe_api( $this->addon->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$api = $this->addon->include_stripe_api();
		}

		return $api;
	}

}
