<?php

/**
 * Handles upgrade routines for the Gravity Forms Stripe Add-On.
 *
 * This class manages the upgrade process for the Stripe Add-On, ensuring that
 * necessary data migrations and settings updates are performed when upgrading
 * between different versions. It handles various upgrade scenarios including:
 * - SCA (Strong Customer Authentication) implementation
 * - Password field cleanup
 * - Feed delay settings for payment processing
 *
 * @since 6.0
 */
class GF_Stripe_Upgrade_Handler {

	/**
	 * Instance of a GFStripe object.
	 *
	 * @since 6.0
	 *
	 * @var GFStripe
	 */
	protected $addon;

	/**
	 * Constructor for the upgrade handler.
	 *
	 * @since 6.0
	 *
	 * @param GFStripe $addon The Stripe add-on instance.
	 */
	public function __construct( $addon ) {
		$this->addon = $addon;
	}

	/**
	 * Executes all necessary upgrade routines based on the previous version.
	 *
	 * @since 6.0
	 *
	 * @param string $previous_version The previously installed version number.
	 */
	public function upgrade( $previous_version ) {
		$this->handle_upgrade_3( $previous_version );
		$this->handle_upgrade_3_2_3( $previous_version );
		$this->handle_upgrade_3_3_3( $previous_version );
		$this->handle_upgrade_6( $previous_version );
	}

	/**
	 * Handle upgrading to 3.0; introduction of SCA.
	 *
	 * @since 3.2.3
	 *
	 * @param string $previous_version Previous version number.
	 */
	public function handle_upgrade_3( $previous_version ) {

		// Determine if previous version is before SCA upgrade.
		$previous_is_pre_sca = ! empty( $previous_version ) && version_compare( $previous_version, '3.0', '<' );

		// If previous version is not before the SCA upgrade, exit.
		if ( ! $previous_is_pre_sca ) {
			return;
		}

		// Get checkout_method.
		$checkout_method = $this->addon->get_plugin_setting( 'checkout_method' );
		if ( $checkout_method === 'stripe_checkout' ) {
			// let users know they are SCA compliant because they use Checkout.
			$message = sprintf(
			// Translators: All Positions are for HTML tags opening and closing.
				esc_html__( '%1$sYour Gravity Forms Stripe Add-On has been updated to 3.0, and now supports Apple Pay and Strong Customer Authentication (SCA/PSD2).%2$s%3$sNOTE:%4$s Stripe has changed Stripe Checkout from a modal display to a full page, and we have altered some existing Stripe hooks. Carefully review %5$sthis guide%6$s to see if your setup may be affected.%7$s', 'gravityformsstripe' ),
				'<p>',
				'</p>',
				'<p><b>',
				'</b>',
				'<a href="https://docs.gravityforms.com/changes-to-checkout-with-stripe-v3/" target="_blank">',
				'</a>',
				'</p>'
			);

		} else {
			// Remind people to switch to Checkout for SCA.
			$message = sprintf(
				// Translators: All Positions are for HTML tags opening and closing.
				esc_html__( '%1$sYour Gravity Forms Stripe Add-On has been updated to 3.0, and now supports Apple Pay and Strong Customer Authentication (SCA/PSD2).%2$s%3$sNOTE:%4$s Apple Pay and SCA are only supported by the Stripe Checkout payment collection method. Refer to %5$sthis guide%6$s for more information on payment methods and SCA.%7$s', 'gravityformsstripe' ),
				'<p>',
				'</p>',
				'<p><b>',
				'</b>',
				'<a href="https://docs.gravityforms.com/stripe-support-of-strong-customer-authentication/" target="_blank">',
				'</a>',
				'</p>'
			);
		}

		// Add message.
		GFCommon::add_dismissible_message( $message, 'gravityformsstripe_upgrade_30', 'warning', $this->addon->get_capabilities( 'form_settings' ), true, 'site-wide' );
	}

	/**
	 * Handle upgrade to 3.2.3; deletes passwords that GF Stripe 3.2 prevented from being deleted.
	 *
	 * @since 3.2.3
	 *
	 * @param string $previous_version Previous version number.
	 */
	public function handle_upgrade_3_2_3( $previous_version ) {
		global $wpdb;

		if ( version_compare( $previous_version, '3.2.3', '>=' ) || version_compare( $previous_version, '3.2', '<' ) ) {
			return;
		}

		$feeds           = $this->addon->get_feeds();
		$processed_forms = array();

		foreach ( $feeds as $feed ) {

			if ( in_array( $feed['form_id'], $processed_forms ) ) {
				continue;
			} else {
				$processed_forms[] = $feed['form_id'];
			}

			$form            = GFAPI::get_form( $feed['form_id'] );
			$password_fields = GFAPI::get_fields_by_type( $form, 'password' );
			if ( empty( $password_fields ) ) {
				continue;
			}

			$password_field_ids = array_map( 'intval', wp_list_pluck( $password_fields, 'id' ) );
			$sql_field_ids      = implode( ',', $password_field_ids );
			$form_id            = (int) $form['id'];

			$sql = $wpdb->prepare(
				"
				DELETE FROM {$wpdb->prefix}gf_entry_meta
				WHERE form_id = %d
				AND meta_key IN( {$sql_field_ids} )",
				$form_id
			);

			$result = $wpdb->query( $sql );

			$this->addon->log_debug( sprintf( '%s: Deleted %d passwords.', __FUNCTION__, (int) $result ) );

		}
	}

	/**
	 * Handle upgrading to 3.4; introduction of SCA in the Stripe Checkout and remove the CC field support for new installs.
	 *
	 * @since 3.4
	 *
	 * @param string $previous_version Previous version number.
	 */
	public function handle_upgrade_3_3_3( $previous_version ) {

		// Determine if previous version is before v3.3.3.
		$previous_is_pre_333 = ! empty( $previous_version ) && version_compare( $previous_version, '3.3.3', '<' );

		// If previous version is not before the v3.3.3, exit.
		if ( ! $previous_is_pre_333 ) {
			return;
		}

		// Get checkout_method.
		$checkout_method = $this->addon->get_plugin_setting( 'checkout_method' );
		if ( $checkout_method === 'stripe_elements' ) {
			// let users know they are SCA compliant because they use Elements.
			$message = sprintf(
				esc_html__( '%1$sYour Gravity Forms Stripe Add-On has been updated to 3.4, and now supports Strong Customer Authentication (SCA/PSD2).%2$s%3$sRefer to %4$sthis guide%5$s for more information on payment methods and SCA.%6$s', 'gravityformsstripe' ),
				'<p>',
				'</p>',
				'<p>',
				'<a href="https://docs.gravityforms.com/stripe-support-of-strong-customer-authentication/" target="_blank">',
				'</a>',
				'</p>'
			);
		} elseif ( $checkout_method === 'credit_card' ) {
			// let users know the Credit Card payment method has been deprecated.
			$message = sprintf(
				esc_html__( '%1$sYour Gravity Forms Stripe Add-On has been updated to 3.4, and it no longer supports the Gravity Forms Credit Card Field in new forms (current integrations can still work as usual).%2$s%3$sRefer to %4$sthis guide%5$s for more information about this change.%6$s', 'gravityformsstripe' ),
				'<p>',
				'</p>',
				'<p>',
				'<a href="https://docs.gravityforms.com/deprecation-of-the-gravity-forms-credit-card-field/" target="_blank">',
				'</a>',
				'</p>'
			);
		}

		if ( isset( $message ) ) {
			GFCommon::add_dismissible_message( $message, 'gravityformsstripe_upgrade_333', 'warning', $this->addon->get_capabilities( 'form_settings' ), true, 'site-wide' );
		}
	}

	/**
	 * Handle upgrading to 6.0. Adds the "Only process feed when payment in completed" setting to Stripe feeds.
	 *
	 * @since 6.0
	 *
	 * @param string $previous_version The previously installed version number.
	 */
	public function handle_upgrade_6( $previous_version ) {

		if ( ! empty( $previous_version ) && version_compare( $previous_version, '6.0-beta.1', '>=' ) ) {
			return;
		}

		$stripe_feeds = $this->addon->get_feeds();
		if ( empty( $stripe_feeds ) ) {
			return;
		}

		foreach ( $stripe_feeds as $feed ) {

			$form = GFAPI::get_form( rgar( $feed, 'form_id' ) );
			if ( empty( $form ) || ! $this->has_stripe_card_element( $form ) ) {
				continue;
			}

			$this->upgrade_feed_delay( $feed );
		}
	}

	/**
	 * Upgrades a feed to include delay settings for payment processing.
	 *
	 * This method ensures that feeds from other add-ons are configured to only
	 * process after a successful payment when using Stripe. It checks if the
	 * add-on supports feed delay and sets the appropriate settings.
	 *
	 * @since 6.0
	 *
	 * @param array $feed The feed to be upgraded.
	 */
	public function upgrade_feed_delay( $feed ) {
		static $has_added_message = false;

		// Get the slug of addons that have a feed for this form.
		$addon_slugs = $this->get_feed_addons_by_form( rgar( $feed, 'form_id' ) );

		// If there are no add-ons to delay, return.
		if ( empty( $addon_slugs ) ) {
			return;
		}

		// Set the delay setting for each add-on.
		foreach ( $addon_slugs as $addon_slug ) {

			// Check if the add-on supports feed delay. If not, skip it.
			if ( ! $this->supports_feed_delay( $addon_slug ) ) {
				continue;
			}

			// If setting is specifically set to 0, leave it as is.
			if ( rgars( $feed, "meta/delay_{$addon_slug}" ) === '0' ) {
				continue;
			}

			$feed['meta'][ "delay_{$addon_slug}" ] = '1';

			// Add upgrade message only once.
			if ( ! $has_added_message ) {

				$message = sprintf(
					// Translators: All Positions are for HTML tags opening and closing.
					esc_html__( '%1$sYour Gravity Forms Stripe Add-On has been updated to version 6.0, and now improves visibility of failed payments by creating an entry when payment fails. Your Stripe related feeds have been updated to only be processed upon a successful payment. %2$s%3$sRefer to %4$sthis guide%5$s for more information on this feature.%6$s', 'gravityformsstripe' ),
					'<p>',
					'</p>',
					'<p>',
					'<a href="https://docs.gravityforms.com/stripe-60-delayed-feed-upgrade/" target="_blank">',
					'</a>',
					'</p>'
				);
				GFCommon::add_dismissible_message( $message, 'gravityformsstripe_upgrade_6', 'warning', $this->addon->get_capabilities( 'form_settings' ), true, 'site-wide' );
				$has_added_message = true;
			}
		}

		// Update the feed with the new delay settings.
		$this->addon->update_feed_meta( $feed['id'], $feed['meta'] );
	}

	/**
	 * Retrieves all add-ons that have feeds for a specific form.
	 *
	 * @since 6.0
	 *
	 * @param int $form_id The ID of the form to check.
	 *
	 * @return array Array of add-on slugs that have feeds for the specified form.
	 */
	public function get_feed_addons_by_form( $form_id ) {
		global $wpdb;

		if ( ! $this->addon->table_exists( $wpdb->prefix . 'gf_addon_feed' ) ) {
			$this->addon->log_error( __METHOD__ . '(): The addon feed table does not exist. Could not add setting to process feed only when payment is received.' );
			return array();
		}

		$sql = $wpdb->prepare( "SELECT addon_slug FROM {$wpdb->prefix}gf_addon_feed WHERE form_id=%d and addon_slug<>'gravityformsstripe'", absint( $form_id ) );

		return $wpdb->get_col( $sql );
	}

	/**
	 * Checks if an add-on supports feed delay functionality.
	 *
	 * @since 6.0
	 *
	 * @param string $addon_slug The slug of the add-on to check.
	 *
	 * @return bool True if the add-on supports feed delay, false otherwise.
	 */
	public function supports_feed_delay( $addon_slug ) {
		$addon = $this->get_addon_by_slug( $addon_slug );
		if ( empty( $addon ) ) {
			return false;
		}

		$addon->init();
		return ! empty( $addon->delayed_payment_integration );
	}

	/**
	 * Determines if the card element is enabled for the provided form.
	 *
	 * @since 6.0
	 *
	 * @param array $form The current form.
	 *
	 * @return bool Returns true if the current form is configured to use the Stripe Card Element. Returns false otherwise.
	 */
	public function has_stripe_card_element( $form ) {

		$cc_field = $this->addon->get_stripe_card_field( $form );
		return $cc_field && ! rgobj( $cc_field, 'enableMultiplePaymentMethods' ) && $this->addon->is_stripe_connect_enabled();
	}

	/**
	 * Retrieves an add-on instance by its slug.
	 *
	 * This method caches the results to avoid repeated lookups.
	 *
	 * @since 6.0
	 *
	 * @param string $slug The slug of the add-on to retrieve.
	 *
	 * @return GFAddOn|null Returns the add-on instance if found, or null if not found.
	 */
	public function get_addon_by_slug( $slug ) {

		static $map = array();

		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}

		$addons = GFAddOn::get_registered_addons( true );

		foreach ( $addons as $addon ) {
			$map[ $addon->get_slug() ] = $addon;
		}

		return rgar( $map, $slug );
	}
}
