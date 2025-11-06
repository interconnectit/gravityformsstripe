<?php
/**
 * Gravity Forms Stripe Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2009 - 2018, Rocketgenius
 */

defined( 'ABSPATH' ) || die();

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class GFStripe
 *
 * Primary class to manage the Stripe add-on.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
 */
class GFStripe extends GFPaymentAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @used-by GFStripe::get_instance()
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Stripe Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GFStripe::scripts()
	 *
	 * @var string $_version Contains the version, defined from stripe.php
	 */
	protected $_version = GF_STRIPE_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.17';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsstripe';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsstripe/stripe.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_url The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Stripe Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'Stripe';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_enable_rg_autoupgrade true
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines if user will not be able to create feeds for a form until a credit card field has been added.
	 *
	 * @since  1.0
	 * @since  3.4 Change the default value to false.
	 * @access protected
	 *
	 * @var bool $_requires_credit_card false.
	 */
	protected $_requires_credit_card = false;

	/**
	 * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_supports_callbacks true
	 */
	protected $_supports_callbacks = true;

	/**
	 * Stripe requires monetary amounts to be formatted as the smallest unit for the currency being used e.g. cents.
	 *
	 * @since  1.10.1
	 * @access protected
	 *
	 * @var bool $_requires_smallest_unit true
	 */
	protected $_requires_smallest_unit = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_stripe';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_stripe';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_stripe_uninstall';

	/**
	 * Defines the capabilities needed for the Stripe Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_stripe', 'gravityforms_stripe_uninstall' );

	/**
	 * Holds the custom meta key currently being processed. Enables the key to be passed to the gform_stripe_field_value filter.
	 *
	 * @since  2.1.1
	 * @access protected
	 *
	 * @used-by GFStripe::maybe_override_field_value()
	 *
	 * @var string $_current_meta_key The meta key currently being processed.
	 */
	protected $_current_meta_key = '';

	/**
	 * Contains an instance of the Stripe API library, if available.
	 *
	 * @since  3.4
	 * @access protected
	 * @var    GF_Stripe_API $api If available, contains an instance of the Stripe API library.
	 */
	protected $api = null;

	/**
	 * Enable rate limits to log card errors etc.
	 *
	 * @since 3.4
	 *
	 * @var bool
	 */
	protected $_enable_rate_limits = true;

	/**
	 * Whether Add-on framework has settings renderer support or not, settings renderer was introduced in Gravity Forms 2.5
	 *
	 * @since 3.8
	 *
	 * @var bool
	 */
	protected $_has_settings_renderer;

	/**
	 * Settings inputs prefix string.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	protected $_input_prefix = '';

	/**
	 * Settings inputs container prefix string.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	protected $_input_container_prefix = '';

	/**
	 * Instance of the billing portal handler.
	 *
	 * @since 4.2
	 *
	 * @var GF_Stripe_Billing_Portal
	 */
	protected $billing_portal_handler;

	protected $_enable_theme_layer = true;
	protected $_asset_min;

	/**
	 * Instance of the payment element handler.
	 *
	 * @since 5.0
	 *
	 * @var GF_Stripe_Payment_Elements
	 */
	protected $payment_element_handler;

	/**
	 * @var \Gravity_Forms\Gravity_Forms\GF_Service_Container $container
	 */
	private static $container;


	const GF_STRIPE_CONFIG = 'gf_stripe_config';
	const DATA_PARSER = 'data_parser';
	const CONFIG_COLLECTION = 'config_collection';

	/**
	 * Get an instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFStripe
	 * @uses GFStripe::$_instance
	 *
	 * @return object GFStripe
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GFStripe();

			if ( ! isset( self::$_instance->_asset_min ) ) {
				self::$_instance->_asset_min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
			}
		}

		return self::$_instance;

	}

	/**
	 * Load the Stripe credit card field.
	 *
	 * @since 2.6
	 */
	public function pre_init() {
		// For form confirmation redirection, this must be called in `wp`,
		// or confirmation redirect to a page would throw PHP fatal error.
		// Run before calling parent method. We don't want to run anything else before displaying thank you page.
		add_action( 'wp', array( $this, 'maybe_thankyou_page' ), 5 );

		// When the manage subscription link is clicked, it should be handled here.
		add_action( 'wp', array( $this->get_billing_portal_handler(), 'maybe_redirect_logged_in_user_to_self_serve_link' ), 10 );

        parent::pre_init();

		require_once 'includes/class-gf-field-stripe-creditcard.php';
	}

	/**
	 * Register service providers for the plugin.
	 *
	 * @since 6.0
	 *
	 * @return void
	 */
	public function register_services() {

		$container = self::get_service_container();

		$container->add_provider( new \Gravity_Forms\Gravity_Forms_Stripe\Elements\Experimental_GF_Elements_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms_Stripe\Common\Webhook_Service_Provider() );
	}

	/**
	 * Get the Service Container for the plugin.
	 *
	 * @since 6.0
	 *
	 * @return \Gravity_Forms\Gravity_Forms\GF_Service_Container
	 */
	public static function get_service_container() {

		require_once 'includes/elements/class-gf-elements-service-provider.php';
		require_once 'includes/common/class-webhook-service-provider.php';

		if ( ! empty( self::$container ) ) {
			return self::$container;
		}
		self::$container = \GFForms::get_service_container();


		return self::$container;
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFPaymentAddOn::scripts()
	 * @uses GFAddOn::get_base_url()
	 * @uses GFAddOn::get_short_title()
	 * @uses GFStripe::$_version
	 * @uses GFCommon::get_base_url()
	 * @uses GFStripe::frontend_script_callback()
	 *
	 * @return array The scripts to be enqueued.
	 */
	public function scripts() {
		$base_url = $this->get_base_url();
        $version  = $this->_version;
		$currency = GFCommon::get_currency();
		$scripts = array(
			array(
				'handle'    => 'stripe.js',
				'src'       => 'https://js.stripe.com/v2/',
				'version'   => $version,
				'deps'      => array(),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'stripe_js_callback' ),
				),
			),
			array(
				'handle'    => 'stripe_v3',
				'src'       => 'https://js.stripe.com/v3/',
				'version'   => $version,
				'deps'      => array(),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'stripe_js_v3_callback' ),
				),
			),
			array(
				'handle'    => 'gforms_stripe_frontend',
				'src'       => $base_url . "/js/frontend{$this->_asset_min}.js",
				'version'   => $version,
				'deps'      => array( 'jquery', 'gform_json', 'gform_gravityforms', 'wp-a11y' ),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'frontend_script_callback' ),
				),
				'strings'   => array(
					'no_active_frontend_feed'         => wp_strip_all_tags( __( 'The credit card field will initiate once the payment condition is met.', 'gravityformsstripe' ) ),
					'requires_action'                 => wp_strip_all_tags( __( 'Please follow the instructions on the screen to validate your card.', 'gravityformsstripe' ) ),
					'create_payment_intent_nonce'     => wp_create_nonce( 'gf_stripe_create_payment_intent' ),
					'ajaxurl'                         => admin_url( 'admin-ajax.php' ),
					'is_preview'                      => $this->is_preview(),
					'payment_incomplete'              => wp_strip_all_tags( __( 'Please enter all required payment information.', 'gravityformsstripe' ) ),
					'failed_to_create_draft'          => wp_strip_all_tags( __( 'We could not process your request at the moment.', 'gravityformsstripe' ) ),
					'failed_to_create_initial_intent' => wp_strip_all_tags( __( 'Payment information field failed to be displayed, please contact support.', 'gravityformsstripe' ) ),
					'failed_to_confirm_intent'        => wp_strip_all_tags( __( 'The payment gateway failed to process the request. Please use a different payment method.', 'gravityformsstripe' ) ),
					'rate_limit_exceeded'             => esc_html__( 'We are not able to process your payment request at the moment. Please try again later.', 'gravityformsstripe' ),
					'payment_element_intent_failure'  => esc_html__( 'The payment has failed', 'gravityformsstripe' ),
					'stripe_connect_enabled'          => $this->is_stripe_connect_enabled() === true,
					'validate_form_nonce'             => wp_create_nonce( 'gfstripe_validate_form' ),
					'delete_draft_nonce'              => wp_create_nonce( 'gfstripe_delete_draft_entry' ),
					'rate_limiting_nonce'             => wp_create_nonce( 'gfstripe_payment_element_check_rate_limiting' ),
					'get_stripe_coupon_nonce'         => wp_create_nonce( 'gfstripe_get_stripe_coupon' ),
					'coupon_invalid'                  => esc_html__( 'You have entered an invalid coupon code.', 'gravityformsstripe' ),
					'invalid_nonce'                   => wp_strip_all_tags( __( "Oops! It looks like we're having trouble processing your request. To resolve this issue, please try clearing your browser cache and then refreshing the page.", 'gravityformsstripe' ) ),
				),
			),
			array(
				'handle'    => 'gforms_stripe_admin',
				'src'       => $base_url . "/js/admin{$this->_asset_min}.js",
				'version'   => $version,
				'deps'      => array( 'jquery', 'thickbox', 'stripe.js', 'wp-a11y' ),
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'plugin_settings', 'form_settings', 'entry_view' ),
						'tab'        => array( $this->_slug, $this->get_short_title() ),
					),
				),
				'strings'   => array(
					'spinner'                         => GFCommon::get_base_url() . '/images/spinner.gif',
					'validation_error'                => wp_strip_all_tags( __( 'Error validating this key. Please try again later.', 'gravityformsstripe' ) ),
					'switch_account_disabled_message' => wp_strip_all_tags( __( 'In order to switch accounts, you must first save this feed by clicking the "Update Settings" button below', 'gravityformsstripe' ) ),
					'disconnect'                      => array(
						'site'    => wp_strip_all_tags( __( 'Are you sure you want to disconnect from Stripe for this website?', 'gravityformsstripe' ) ),
						'feed'    => wp_strip_all_tags( __( 'Are you sure you want to disconnect from Stripe for this feed?', 'gravityformsstripe' ) ),
						'account' => wp_strip_all_tags( __( 'Are you sure you want to disconnect all Gravity Forms sites connected to this Stripe account?', 'gravityformsstripe' ) ),
					),
					'settings_url'                    => admin_url( 'admin.php?page=gf_settings&subview=' . $this->get_slug() ),
					'ajax_nonce'                      => wp_create_nonce( 'gf_stripe_ajax' ),
					'liveDependencySupported'         => $this->_has_settings_renderer,
					'apiMode'                         => $this->get_api_mode( $this->get_settings() ),
					'input_container_prefix'          => $this->_input_container_prefix,
					'input_prefix'                    => $this->_input_prefix,
					'refund'                          => wp_strip_all_tags( __( 'Are you sure you want to refund this payment?', 'gravityformsstripe' ) ),
					'refund_nonce'                    => wp_create_nonce( 'gf_stripe_refund' ),
					'refund_processing'               => wp_strip_all_tags( __( 'Processing refund', 'gravityformsstripe' ) ),
					'refund_complete'                 => wp_strip_all_tags( __( 'Transaction successfully refunded', 'gravityformsstripe' ) ),
					'capture_confirm'                 => wp_strip_all_tags( __( 'Are you sure you want to capture this payment?', 'gravityformstripe' ) ),
					'capture_processing'              => wp_strip_all_tags( __( 'Processing capture', 'gravityformsstripe' ) ),
					'capture_complete'                => wp_strip_all_tags( __( 'Transaction successfully captured', 'gravityformsstripe' ) ),
					'copied'					      => wp_strip_all_tags( __( 'Copied', 'gravityformsstripe' ) ),
					'copied_to_clipboard'             => wp_strip_all_tags( __( 'Webhook URL copied to clipboard', 'gravityformsstripe' ) ),
				),
			),
			array(
				'handle'    => 'gform_stripe_payment_element_form_editor',
				'src'       => $base_url . "/js/payment_element_form_editor{$this->_asset_min}.js",
				'version'   => $version,
				'deps'      => array( 'jquery', 'stripe_v3' ),
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'form_editor' ),
					),
				),
				'strings'   => array(
					'payment_element_error'            => wp_strip_all_tags( __( 'Please check your Stripe settings', 'gravityformsstripe' ) ),
					'payment_element_currency'         => strtolower( $currency ),
					'payment_element_amount'           => $this->get_amount_export( $this->get_payment_element_handler()->get_minimum_amount( strtolower( $currency ) ) ),
					'api_key'                          => $this->get_publishable_api_key(),
					'field_position_validation_error'  => wp_strip_all_tags( __( 'When the additional payment methods option is enabled, the Stripe field must be on the last page of the form.', 'gravityformsstripe' ) ),
					'stripe_connect_enabled'           => $this->is_stripe_connect_enabled() === true,
					'payment_element_supported'        => $this->is_payment_element_supported(),
					'payment_element_disabled_message' => wp_strip_all_tags( __( 'To enable additional payment methods, you must update Gravity Forms to the latest version.', 'gravityformsstripe' ) ),
					'email_field_id_text'              => wp_strip_all_tags( __( 'Field ID', 'gravityformsstripe' ) ),
				),
			),
			array(
				'handle'    => 'gform_stripe_vendor_theme_js',
				'src'       => trailingslashit( $this->get_base_url() ) . "assets/js/dist/vendor-theme{$this->_asset_min}.js",
				'version'   => $this->_version,
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'is_refactored_payment_method' ),
				),
			),
			array(
				'handle'    => 'gform_stripe_theme_js',
				'src'       => trailingslashit( $this->get_base_url() ) . "assets/js/dist/scripts-theme{$this->_asset_min}.js",
				'version'   => $this->_version,
				'deps'      => array( 'gform_stripe_vendor_theme_js', 'stripe_v3' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'is_refactored_payment_method' ),
					array( 'admin_page' => ( 'block_editor' ) ),
				),
				'strings'   => array(
					'publishable_key'                     => $this->get_publishable_api_key(),
					'create_payment_intent_nonce'         => wp_create_nonce( 'gfstripe_create_payment_intent' ),
					'create_subscription_nonce'           => wp_create_nonce( 'gfstripe_create_subscription' ),
					'increase_error_count_nonce'          => wp_create_nonce( 'gfstripe_increase_error_count' ),
					'get_entry_nonce'                     => wp_create_nonce( 'gfstripe_get_entry' ),
					'handle_successful_entry_nonce'       => wp_create_nonce( 'gfstripe_handle_successful_entry' ),
					'payment_timeout_message'             => esc_html__( 'This process is taking longer than expected. You will receive a confirmation via email when your payment is completed.', 'gravityformsstripe' ),
					'processing_payment'                  => esc_html__( 'Processing payment...', 'gravityformsstripe' ),
					'payment_failed_message'              => esc_html__( 'Your payment has failed. Please update your payment method and try again.', 'gravityformsstripe' ),
					'try_again'                           => esc_html__( 'Try again', 'gravityformsstripe' ),
					'card_error_message'                  => esc_html__( 'Please enter your card details.', 'gravityformsstripe' ),
					'subscription_creation_error_message' => esc_html__( 'Could not create subscription.', 'gravityformsstripe' ),
					'unknown_error_message'               => esc_html__( 'There was an error processing your request. Please try again later.', 'gravityformsstripe' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/***
	 *  Return the styles that need to be enqueued.
	 *
	 * @since  2.6
	 * @since  2.8 Add plugin settings CSS.
	 *
	 * @access public
	 *
	 * @return array Returns an array of styles and when to enqueue them
	 */
	public function styles() {
		$base_url = $this->get_base_url();
        $version  = $this->_version;
		$styles   = array(
			array(
				'handle'  => 'gform_stripe_pluginsettings',
				'src'     => $base_url . "/assets/css/dist/admin{$this->_asset_min}.css",
				'version' => $version,
				'deps'    => array( 'thickbox' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings', 'form_settings', 'form_editor', 'entry_edit' ),
						'tab'        => $this->_slug,
					),
				),
			),
		);

		if ( ! $this->supports_theme_enqueuing() ) {
			$styles[] = array(
				'handle'  => 'gform_stripe_frontend',
				'src'     => $base_url . "/assets/css/dist/theme{$this->_asset_min}.css",
				'version' => $version,
				'enqueue' => array(
					array( 'admin_page'  => array( 'form_editor', 'block_editor' ) ),
					array( $this, 'frontend_style_callback' ),
				),
			);
			$styles[] = array(
				'handle'  => 'gform-stripe-theme-foundation',
				'src'     => $base_url . "/assets/css/dist/theme-foundation{$this->_asset_min}.css",
				'version' => $version,
				'enqueue' => array(
					array( 'admin_page'  => array( 'form_editor', 'block_editor' ) ),
					array( $this, 'frontend_style_callback' ),
				),
			);
			$styles[] = array(
				'handle'  => 'gform-stripe-theme-foundation-admin',
				'src'     => $base_url . "/assets/css/dist/theme-foundation-admin{$this->_asset_min}.css",
				'version' => $version,
				'enqueue' => array(
					array( 'admin_page'  => array( 'form_editor' ) ),
				),
			);
			$styles[] = array(
				'handle'  => 'gform-stripe-theme-framework',
				'src'     => $base_url . "/assets/css/dist/theme-framework{$this->_asset_min}.css",
				'version' => $version,
				'enqueue' => array(
					array( 'admin_page'  => array( 'form_editor', 'block_editor' ) ),
					array( $this, 'frontend_style_callback' ),
				),
			);
		}

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Helper method that returns the theme styles that should be enqueued for the add-on.
	 * Returns an array in the format accepted by the Gravity Forms theme layer set_styles() method
	 *
	 * @since 5.7.2
	 *
	 * @param array  $form               The current form object to enqueue styles for.
	 * @param string $field_type         The field type associated with the add-on. Styles will only be enqueued on the frontend if the form has a field with the specified field type.
	 * @param string $gravity_theme_path The path to the gravity theme style. Optional. Only needed for add-ons that implement the gravity theme outside of the default /assets/css/dist/theme.css path.
	 *
	 * @return array Returns and array of styles to enqueue in the format accepted by the Gravity Forms theme layer set_styles() method.
	 */
	public function get_theme_layer_styles( $form, $field_type = '', $gravity_theme_path = '' ) {
		$styles = parent::get_theme_layer_styles( $form, $field_type, $gravity_theme_path );

		if ( GFCommon::is_form_editor() ) {
			$styles['foundation'][] = array( "{$this->_slug}_theme_foundation_admin", $this->get_base_url() . "/assets/css/dist/theme-foundation-admin{$this->_asset_min}.css" );
		}

		return $styles;
	}

	public function supports_theme_enqueuing() {
		return method_exists( 'GFAddOn', 'get_theme_layer_styles' );
	}

	/**
	 * An array of styles to enqueue.
	 *
	 * @since 4.3
	 *
	 * @param $form
	 * @param $ajax
	 * @param $settings
	 * @param $block_settings
	 *
	 * @return array|\string[][]
	 */
	public function theme_layer_styles( $form, $ajax, $settings, $block_settings = array() ) {
		return $this->supports_theme_enqueuing() ? $this->get_theme_layer_styles( $form, 'stripe_creditcard' ) : array();
	}

	/**
	 * Styles to pass to the Stripe JS widget as part of its CSS properties object.
	 *
	 * @since 4.3
	 *
	 * @param $form_id
	 * @param $settings
	 * @param $block_settings
	 *
	 * @return array
	 */
	public function theme_layer_third_party_styles( $form_id, $settings, $block_settings ) {
		$default_settings = \GFForms::get_service_container()->get( \Gravity_Forms\Gravity_Forms\Form_Display\GF_Form_Display_Service_Provider::BLOCK_STYLES_DEFAULTS );
		$applied_settings = wp_parse_args( $block_settings, $default_settings );

		if ( $applied_settings['theme'] !== 'orbital' ) {
			return array();
		}

		$form = GFAPI::get_form( $form_id );

		// Only enqueue third party styles for the payment element. The refactored card element uses config to output styles since it supports loading on demand, integrating well with the block editor.
		return $this->is_payment_element_enabled( $form ) ? $this->get_stripe_payment_element_styles() : array();
	}

	/**
	 * Gets the styles for the payment element control/widget.
	 *
	 * @since 5.0
	 *
	 * @return array Returns an array containing styles for the card element.
	 */
	public function get_stripe_card_styles() {
        return array(
            'base'    => array(
                'backgroundColor' => 'transparent',
                'color'           => '--gf-ctrl-color',
                'fontFamily'      => '--gf-ctrl-font-family',
                'fontSize'        => '--gf-ctrl-font-size',
                'fontSmoothing'   => '--gf-ctrl-font-smoothing',
                'fontStyle'       => '--gf-ctrl-font-style',
                'fontWeight'      => '--gf-ctrl-font-weight',
                'iconColor'       => '--gf-ctrl-color',
                'letterSpacing'   => '--gf-ctrl-letter-spacing',
                ':hover'          => array(
                    'backgroundColor' => 'transparent',
                    'color'           => '--gf-ctrl-color-hover',
                    'iconColor'       => '--gf-ctrl-color-hover',
                ),
                ':focus'          => array(
                    'backgroundColor' => 'transparent',
                    'color'           => '--gf-ctrl-color-focus',
                    'iconColor'       => '--gf-ctrl-color-focus',
                ),
                ':disabled'       => array(
                    'backgroundColor' => 'transparent',
                    'color'           => '--gf-ctrl-color-disabled',
                    'iconColor'       => '--gf-ctrl-color-disabled',
                ),
                '::placeholder'   => array(
                    'color'         => '--gf-ctrl-placeholder-color',
                    'fontFamily'    => '--gf-ctrl-placeholder-font-family',
                    'fontSize'      => '--gf-ctrl-placeholder-font-size',
                    'fontStyle'     => '--gf-ctrl-placeholder-font-style',
                    'fontWeight'    => '--gf-ctrl-placeholder-font-weight',
                    'letterSpacing' => '--gf-ctrl-placeholder-letter-spacing',
                ),
            ),
            'invalid' => array(
                'color'     => '--gf-ctrl-color',
                'iconColor' => '--gf-color-danger',
            ),
        );
	}

	/**
	 * Gets the styles for Stripe's payment element.
	 *
	 * @since 5.0
	 *
	 * @return array Returns an array containg styles for Stripe's payment element.
	 */
	private function get_stripe_payment_element_styles() {
		$style_rules = array(
			'.Input'                        => array(
				'borderColor'   => '--gf-ctrl-border-color',
				'borderWidth'   => '--gf-ctrl-border-width',
				'borderStyle'   => '--gf-ctrl-border-style',
				'color'         => '--gf-ctrl-color',
				'fontWeight'    => '--gf-ctrl-font-weight',
				'fontSize'      => '--gf-ctrl-font-size',
				'letterSpacing' => '--gf-ctrl-letter-spacing',
			),
			'.Input:hover'                  => array(
				'backgroundColor' => '--gf-ctrl-bg-color-hover',
				'borderColor'     => '--gf-ctrl-border-color-hover',
				'color'           => '--gf-ctrl-color-hover',
			),
			'.Input:hover:focus'            => array(
				'backgroundColor' => '--gf-ctrl-bg-color-focus',
				'borderColor'     => '--gf-ctrl-border-color-focus',
				'color'           => '--gf-ctrl-color-focus',
			),
			'.Input:focus'                  => array(
				'backgroundColor' => '--gf-ctrl-bg-color-focus',
				'borderColor'     => '--gf-ctrl-border-color-focus',
				'color'           => '--gf-ctrl-color-focus',
			),
			'.Input:disabled'               => array(
				'backgroundColor' => '--gf-ctrl-bg-color-disabled',
				'borderColor'     => '--gf-ctrl-border-color-disabled',
				'color'           => '--gf-ctrl-color-disabled',
			),
			'.Input:disabled:hover'         => array(
				'backgroundColor' => '--gf-ctrl-bg-color-disabled',
				'borderColor'     => '--gf-ctrl-border-color-disabled',
				'color'           => '--gf-ctrl-color-disabled',
			),
			'.Input--invalid'               => array(
				'backgroundColor' => '--gf-ctrl-bg-color-error',
				'borderColor'     => '--gf-ctrl-border-color-error',
				'boxShadow'       => 'none',
				'color'           => '--gf-ctrl-color-error',
			),
			'.Input--invalid:hover'         => array(
				'borderColor' => '--gf-ctrl-border-color-error',
			),
			'.Input::placeholder'           => array(
				'fontFamily'    => '--gf-ctrl-placeholder-font-family',
				'fontSize'      => '--gf-ctrl-placeholder-font-size',
				'fontWeight'    => '--gf-ctrl-placeholder-font-weight',
				'letterSpacing' => '--gf-ctrl-placeholder-letter-spacing',
			),
			'.CodeInput'                    => array(
				'borderColor'   => '--gf-ctrl-border-color',
				'borderWidth'   => '--gf-ctrl-border-width',
				'borderStyle'   => '--gf-ctrl-border-style',
				'color'         => '--gf-ctrl-color',
				'fontWeight'    => '--gf-ctrl-font-weight',
				'fontSize'      => '--gf-ctrl-font-size',
				'letterSpacing' => '--gf-ctrl-letter-spacing',
			),
			'.CodeInput:focus'              => array(
				'backgroundColor' => '--gf-ctrl-bg-color-focus',
				'borderColor'     => '--gf-ctrl-border-color-focus',
				'color'           => '--gf-ctrl-color-focus',
			),
			'.CheckboxInput'                => array(
				'backgroundColor' => '--gf-ctrl-bg-color',
				'borderColor'     => '--gf-ctrl-border-color',
				'borderWidth'     => '--gf-ctrl-border-width',
				'borderStyle'     => '--gf-ctrl-border-style',
				'borderRadius'    => '--gf-ctrl-checkbox-check-radius',
			),
			'.CheckboxInput:hover'          => array(
				'backgroundColor' => '--gf-ctrl-bg-color-hover',
				'borderColor'     => '--gf-ctrl-border-color-hover',
			),
			'.CheckboxInput:focus'          => array(
				'backgroundColor' => '--gf-ctrl-bg-color-focus',
				'borderColor'     => '--gf-ctrl-border-color-focus',
			),
			'.CheckboxInput--checked'       => array(
				'backgroundColor' => '--gf-ctrl-choice-check-color',
				'borderColor'     => '--gf-ctrl-choice-check-color',
			),
			'.CheckboxInput--checked:hover' => array(
				'backgroundColor' => '--gf-ctrl-choice-check-color',
				'borderColor'     => '--gf-ctrl-choice-check-color',
			),
			'.CheckboxInput--checked:focus' => array(
				'backgroundColor' => '--gf-ctrl-choice-check-color',
				'borderColor'     => '--gf-ctrl-border-color-focus',
			),
			'.Error'                        => array(
				'color'         => '--gf-ctrl-desc-color-error',
				'fontFamily'    => '--gf-ctrl-desc-font-family-error',
				'fontSize'      => '--gf-ctrl-desc-font-size-error',
				'fontWeight'    => '--gf-ctrl-desc-font-weight-error',
				'letterSpacing' => '--gf-ctrl-desc-letter-spacing-error',
				'lineHeight'    => '--gf-ctrl-desc-line-height-error',
				'marginTop'     => '--gf-desc-space',
			),
			'.Label--resting'               => array(
				'color'         => '--gf-ctrl-placeholder-color',
				'fontFamily'    => '--gf-ctrl-placeholder-font-family',
				'fontSize'      => '--gf-ctrl-placeholder-font-size',
				'fontWeight'    => '--gf-ctrl-placeholder-font-weight',
				'letterSpacing' => '--gf-ctrl-placeholder-letter-spacing',
				'opacity'       => '--gf-ctrl-placeholder-opacity',
			),
			'.Label--floating'              => array(
				'color'         => '--gf-ctrl-color',
				'fontFamily'    => '--gf-ctrl-placeholder-font-family',
				'fontWeight'    => '--gf-ctrl-label-font-weight-primary',
				'letterSpacing' => '--gf-ctrl-placeholder-letter-spacing',
			),
			'.Tab'                          => array(
				'backgroundColor' => '--gf-ctrl-button-bg-color-secondary',
				'borderColor'     => '--gf-ctrl-button-border-color-secondary',
				'borderWidth'     => '--gf-ctrl-button-border-width-secondary',
				'borderStyle'     => '--gf-ctrl-button-border-style-secondary',
				'color'           => '--gf-ctrl-button-color-secondary',
			),
			'.Tab:hover'                    => array(
				'backgroundColor' => '--gf-ctrl-btn-bg-color-hover-secondary',
				'borderColor'     => '--gf-ctrl-btn-border-color-hover-secondary',
				'color'           => '--gf-ctrl-btn-color-hover-secondary',
			),
			'.Tab:focus'                    => array(
				'backgroundColor' => '--gf-ctrl-btn-bg-color-focus-secondary',
				'borderColor'     => '--gf-ctrl-btn-border-color-focus-secondary',
				'color'           => '--gf-ctrl-btn-color-focus-secondary',
			),
			'.Tab:disabled'                 => array(
				'backgroundColor' => '--gf-ctrl-btn-bg-color-disabled-secondary',
				'borderColor'     => '--gf-ctrl-btn-border-color-disabled-secondary',
				'color'           => '--gf-ctrl-btn-color-disabled-secondary',
			),
			'.Tab--selected'                => array(
				'borderColor' => '--gf-ctrl-btn-border-color-focus-secondary',
				'color'       => '--gf-ctrl-btn-color-secondary',
			),
			'.Tab--selected:hover'          => array(
				'borderColor' => '--gf-ctrl-btn-border-color-focus-secondary',
				'color'       => '--gf-ctrl-btn-color-secondary',
			),
			'.Tab--selected:focus'          => array(
				'borderColor' => '--gf-ctrl-btn-border-color-focus-secondary',
				'color'       => '--gf-ctrl-btn-color-secondary',
			),
			'.RedirectText'                 => array(
				'color'         => '--gf-color-primary',
				'fontFamily'    => '--gf-ctrl-desc-font-family',
				'fontSize'      => '--gf-ctrl-desc-font-size',
				'fontWeight'    => '--gf-ctrl-desc-font-weight',
				'letterSpacing' => '--gf-ctrl-desc-letter-spacing',
				'lineHeight'    => '--gf-ctrl-desc-line-height',
			),
			'.PickerItem--new'              => array(
				'color' => '--gf-ctrl-color',
			),
			'.TermsText'                    => array(
				'color' => '--gf-ctrl-desc-color',
			),
			'.TermsLink'                    => array(
				'color' => '--gf-ctrl-desc-color',
			),
			'.Block'                        => array(
				'borderRadius' => '--gf-ctrl-radius-max-lg',
			),
		);

		/*
		NOTE:
		The Theme Framework CSS approach for focus styles has been updated as of Gravity Forms 2.9.0.1
		for better A11Y from being box-shadow based to outline based. The box-shadow approach is now
		deprecated, however we want to support older version of core for a bit as a precaution given
		this is important for A11Y.

		It's also important to note that for the outline property to be properly computed into the outline
		shorthand property required/supported by Stripe, the outline based properties/keys below should
		always remain grouped together in this order: width, style, color

		Deprecated version (core): 2.9.0.1
		End of support version (core): 3.0
		Deprecated version (stripe): 5.9.1
		*/
		if ( version_compare( GFForms::$version, '2.9.0.1', '>=' ) ) {
			$style_rules_additional = array(
				'.Input'                  => array(
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.Input:hover:focus'      => array(
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.Input:focus'            => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.CodeInput'              => array(
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.CodeInput:focus'        => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.CheckboxInput'          => array(
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.CheckboxInput:focus'    => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.Tab'                    => array(
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.Tab:focus'              => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.Tab--selected:focus'    => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.PickerItem'             => array(
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.PickerItem:hover:focus' => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.PickerItem:focus'       => array(
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.MenuIcon'               => array(
					'borderColor'   => 'transparent',
					'borderWidth'   => '--gf-ctrl-border-width',
					'borderStyle'   => '--gf-ctrl-border-style',
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.MenuIcon:focus'         => array(
					'borderColor'  => '--gf-ctrl-border-color-focus',
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.MenuAction'             => array(
					'borderColor'   => 'transparent',
					'borderWidth'   => '--gf-ctrl-border-width',
					'borderStyle'   => '--gf-ctrl-border-style',
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.MenuAction:focus'       => array(
					'borderColor'  => '--gf-ctrl-border-color-focus',
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
				'.Action'                 => array(
					'borderColor'   => 'transparent',
					'borderWidth'   => '--gf-ctrl-border-width',
					'borderStyle'   => '--gf-ctrl-border-style',
					'outlineWidth'  => '--gf-ctrl-outline-width',
					'outlineStyle'  => '--gf-ctrl-outline-style',
					'outlineColor'  => '--gf-ctrl-outline-color',
					'outlineOffset' => '--gf-ctrl-outline-offset',
					'transition'    => '--gf-ctrl-transition',
				),
				'.Action:focus'           => array(
					'borderColor'  => '--gf-ctrl-border-color-focus',
					'boxShadow'    => 'none',
					'outlineWidth' => '--gf-ctrl-outline-width-focus',
					'outlineStyle' => '--gf-ctrl-outline-style',
					'outlineColor' => '--gf-ctrl-outline-color-focus',
				),
			);
		} else {
			$style_rules_additional = array(
				'.Input:focus'         => array( 'boxShadow' => '--gf-ctrl-shadow-focus', ),
				'.CodeInput:focus'     => array( 'boxShadow' => '--gf-ctrl-shadow-focus', ),
				'.CheckboxInput:focus' => array( 'boxShadow' => '--gf-ctrl-shadow-focus', ),
				'.Tab:focus'           => array( 'boxShadow' => '--gf-ctrl-shadow-focus', ),
				'.Tab--selected:focus' => array( 'boxShadow' => '--gf-ctrl-shadow-focus', ),
			);
		}

		$style_rules = array_merge_recursive( $style_rules, $style_rules_additional );

		return array(
			'labels'    => 'floating',
			'theme'     => 'stripe',
			'variables' => array(
				'borderRadius'          => '--gf-ctrl-radius',
				'colorBackground'       => '--gf-ctrl-bg-color',
				'colorBackgroundText'   => '--gf-ctrl-color',
				'colorIcon'             => '--gf-ctrl-color',
				'colorIconCardCvc'      => '--gf-ctrl-color',
				'colorIconCardCvcError' => '--gf-color-danger',
				'colorIconCardError'    => '--gf-color-danger',
				//'colorIconSelectArrow' => '--gf-color-in-ctrl-dark-lighter', not currently supported
				'colorIconTab'          => '--gf-ctrl-btn-icon-color-secondary',
				'colorIconTabHover'     => '--gf-ctrl-btn-icon-color-hover-secondary',
				'colorIconTabSelected'  => '--gf-ctrl-btn-color-secondary',
				'colorIconTabMore'      => '--gf-ctrl-btn-icon-color-secondary',
				'colorIconTabMoreHover' => '--gf-ctrl-btn-icon-color-hover-secondary',
				'colorIconMenu'         => '--gf-ctrl-color',
				'colorPrimary'          => '--gf-color-primary',
				'colorPrimaryText'      => '--gf-color-primary-contrast',
				'colorDanger'           => '--gf-color-danger',
				'colorDangerText'       => '--gf-color-danger-contrast',
				'colorSuccess'          => '--gf-color-success',
				'colorSuccessText'      => '--gf-color-success-contrast',
				'colorText'             => '--gf-ctrl-color',
				'colorTextPlaceholder'  => '--gf-ctrl-placeholder-color',
				'colorTextSecondary'    => '--gf-ctrl-color',
				'fontFamily'            => '--gf-font-family-base',
				'fontSizeBase'          => '16px',
				'fontSmooth'            => '--gf-ctrl-font-smoothing',
				'spacingGridRow'        => '--gf-field-gap-y',
				'spacingGridColumn'     => '--gf-field-gap-x',
			),
			'rules'     => $style_rules,
		);
	}

	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return $this->is_gravityforms_supported( '2.5-beta-4' ) ? 'gform-icon--stripe' : 'dashicons-admin-generic';

	}

	/**
	 * Initialize the AJAX hooks.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::init_ajax()
	 *
	 * @return void
	 */
	public function init_ajax() {

		parent::init_ajax();

		// Add AJAX callback for de-authorizing from Stripe.
		add_action( 'wp_ajax_gfstripe_deauthorize', array( $this, 'ajax_deauthorize' ) );

		add_action( 'wp_ajax_gf_validate_secret_key', array( $this, 'ajax_validate_secret_key' ) );

		// Add AJAX callback for creating a payment intent.
		add_action( 'wp_ajax_nopriv_gfstripe_create_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_gfstripe_create_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_gfstripe_update_payment_intent', array( $this, 'update_payment_intent' ) );
		add_action( 'wp_ajax_gfstripe_update_payment_intent', array( $this, 'update_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_gfstripe_get_country_code', array( $this, 'get_country_code' ) );
		add_action( 'wp_ajax_gfstripe_get_country_code', array( $this, 'get_country_code' ) );
		add_action( 'wp_ajax_gfstripe_capture_action', array( $this, 'ajax_capture_payment' ) );
		add_action( 'wp_ajax_gfstripe_refund', array( $this, 'ajax_refund' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_get_stripe_coupon', array( $this->get_payment_element_handler(), 'get_stripe_coupon' ) );
		add_action( 'wp_ajax_gfstripe_get_stripe_coupon', array( $this->get_payment_element_handler(), 'get_stripe_coupon' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_validate_form', array( $this->get_payment_element_handler(), 'start_checkout' ) );
		add_action( 'wp_ajax_gfstripe_validate_form', array( $this->get_payment_element_handler(), 'start_checkout' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_delete_draft_entry', array( $this->get_payment_element_handler(), 'delete_draft_entry' ) );
		add_action( 'wp_ajax_gfstripe_delete_draft_entry', array( $this->get_payment_element_handler(), 'delete_draft_entry' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_payment_element_check_rate_limiting', array( $this->get_payment_element_handler(), 'check_rate_limiting' ) );
		add_action( 'wp_ajax_gfstripe_payment_element_check_rate_limiting', array( $this->get_payment_element_handler(), 'check_rate_limiting' ) );

	}

	/**
	 * Admin initial actions.
	 *
	 * @since 2.8
	 */
	public function init_admin() {
		parent::init_admin();

		add_action( 'admin_notices', array( $this, 'maybe_display_update_authentication_message' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_deprecated_cc_field_warning' ) );
		add_action( 'admin_init', array( $this, 'maybe_update_auth_tokens' ) );
		add_action( 'gform_payment_details', array( $this, 'maybe_display_capture_button' ), 10, 2 );
		add_action( 'gform_payment_details', array( $this, 'maybe_display_refund_button' ), 10, 2 );
		add_action( 'gform_field_standard_settings', array( $this, 'add_payment_method_setting' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'remove_cardholder_name_merge_tag' ), 10, 0 );
	}

	/**
	 * Adds the enable additional payment methods (payment element) setting to the stripe field.
	 *
	 * @since 5.0
	 *
	 * @param int $placement The setting placement.
	 * @param int $form_id   The current form ID.
	 *
	 * @return void
	 */
	public function add_payment_method_setting( $placement, $form_id ) {
		if ( $placement !== 1415 ) {
			return;
		}
		?>
		<li class="enable_multiple_payment_methods_setting field_setting">
			<label for="rules" class="section_label">
				<?php esc_html_e( 'Stripe API', 'gravityformsstripe' ); ?>
				<?php gform_tooltip( 'form_field_enable_multiple_payment_methods' ); ?>
			</label>
			<?php
			if ( $this->is_stripe_connect_enabled() === true ) {
				$disabled = $this->is_payment_element_supported() ? '' : 'disabled="disabled"';
				?>
			<div>
				<input type="checkbox" <?php echo $disabled; ?> id="field_enable_multiple_payment_methods" data-js="enable_multiple_payment_methods" onclick="SetFieldProperty('enableMultiplePaymentMethods', this.checked);" onkeypress="SetFieldProperty('enableMultiplePaymentMethods', this.checked);" />
				<label for="field_enable_multiple_payment_methods" class="inline">
					<?php esc_html_e( "Use Stripe's Payment Element.", 'gravityformsstripe' ); ?>
				</label>
				<div id="field_multiple_payment_methods_description">
					<?php
					// translators: variables are the markup to generate a link.
					printf( esc_html__( 'The Payment Element is Stripe\'s latest payment widget. It includes support for %1$sadditional payment methods%2$s and other improvements.', 'gravityformsstripe' ), '<a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank">', ' <span aria-hidden="true" class="gform-icon gform-icon--external-link"></span></a>' );
					?>
				</div>
			</div>
			<br>
			<div id="link_email_field_container">
				<label for="link_email_field" class="section_label">
					<?php esc_html_e( 'Link Email Field', 'gravityformsstripe' ); ?>
				</label>
				<select id="link_email_field" name="link_email_field" class="inline">
					<option value="">
					<?php esc_html_e( 'Select an Email Field', 'gravityformsstripe' ); ?>
					</option>
				</select>
				<div>
					<?php
					// translators: variables are the markup to generate a link.
					printf( esc_html__( 'Link is a payment method that enables your customers to save their payment information so they can use it again on any site that uses Stripe Link. %1$sLearn more about Link%2$s.', 'gravityformsstripe' ), '<a href="https://stripe.com/docs/payments/link" target="_blank">', '</a>' );
					?>
				</div>
			</div>
			<?php } else { ?>
				<div>
					<?php
					// translators: variables are the markup to generate a link.
					printf( esc_html__( 'This option is disabled because Stripe is authenticated using API keys instead of Stripe Connect. To take advantage of additional payment methods, re-authenticate using Stripe Connect. %1$sLearn more about Stripe payment element%2$s.', 'gravityformsstripe' ), '<a href="https://stripe.com/docs/payments/payment-element" target="_blank">', '</a>' );
					?>
				</div>
			<?php } ?>
		</li>
		<?php
	}

	/**
	 * Gets billing portal handler instance if already initialized, otherwise, initialize it.
	 *
	 * @since 4.2
	 *
	 * @return GF_Stripe_Billing_Portal
	 */
	public function get_billing_portal_handler() {

		if ( $this->billing_portal_handler instanceof GF_Stripe_Billing_Portal === false ) {
			require_once plugin_dir_path( __FILE__ ) . '/includes/class-gf-stripe-billing-portal.php';
			$this->billing_portal_handler = new GF_Stripe_Billing_Portal( $this );
		}

		return $this->billing_portal_handler;

	}

	/**
	 * Gets Payment Element handler instance if already initialized, otherwise, initialize it.
	 *
	 * @since 5.0
	 *
	 * @return GF_Stripe_Payment_Elements
	 */
	public function get_payment_element_handler() {

		if ( $this->payment_element_handler instanceof GF_Stripe_Payment_Element === false ) {
			require_once plugin_dir_path( __FILE__ ) . '/includes/payment-element/class-gf-payment-element-payment.php';
			require_once plugin_dir_path( __FILE__ ) . '/includes/payment-element/class-gf-payment-element-submission.php';
			require_once plugin_dir_path( __FILE__ ) . '/includes/payment-element/class-gf-payment-element.php';
			$this->payment_element_handler = new GF_Stripe_Payment_Element( $this );
		}

		return $this->payment_element_handler;

	}


	/**
	 * Handler for the gf_validate_secret_key AJAX request.
	 *
	 * @since Unknown
	 * @since 3.3 Fix PHP fatal error thrown when deleting test data.
	 * @since 3.4 Use GF_Stripe_API class.
	 *
	 * @access public
	 *
	 * @used-by GFStripe::init_ajax()
	 * @uses    GFStripe::include_stripe_api()
	 * @uses    GFAddOn::log_error()
	 *
	 * @return void
	 */
	public function ajax_validate_secret_key() {
		check_ajax_referer( 'gf_stripe_ajax', 'nonce' );

		if ( ! GFCommon::current_user_can_any( $this->_capabilities_settings_page ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Access denied.', 'gravityformsstripe' ) ) );
		}

		// Load the Stripe API library.
		if ( ! class_exists( 'GF_Stripe_API' ) ) {
			require_once 'includes/api/class-gf-stripe-api.php';
		}

		$stripe = new GF_Stripe_API( rgpost( 'key' ) );

		// Initialize validity state.
		$is_valid = true;

		$account = $stripe->get_account();
		if ( is_wp_error( $account ) ) {
			$this->log_error( __METHOD__ . '(): ' . $account->get_error_message() );

			$is_valid = false;
		}

		// Prepare response.
		$response = $is_valid ? 'valid' : 'invalid';

		// Send API key validation response.
		die( $response );

	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  Unknown
	 * @since  2.6     Add Payment Collection section.
	 * @since  2.8     Add Stripe Connect. Remove Stripe Webhooks.
	 *
	 * @access public
	 *
	 * @used-by GFAddOn::maybe_save_plugin_settings()
	 * @used-by GFAddOn::plugin_settings_page()
	 * @uses    GFStripe::api_settings_fields()
	 * @uses    GFStripe::get_webhooks_section_description()
	 *
	 * @return array Plugin settings fields to add.
	 */
	public function plugin_settings_fields() {
		$fields = array(
			array(
				'title'  => esc_html__( 'Stripe Account', 'gravityformsstripe' ),
				'fields' => $this->api_settings_fields(),
			),
		);

		if ( version_compare( GFFormsModel::get_database_version(), '2.4-beta-1', '>=' ) ) {
			$fields[] = array(
				'title'       => esc_html__( 'Payment Collection', 'gravityformsstripe' ),
				'description' => $this->get_checkout_method_section_description(),
				'fields'      => array(

					array(
						'name'          => 'checkout_method',
						'label'         => esc_html__( 'Payment Collection Method', 'gravityformsstripe' ),
						'type'          => 'radio',
						'default_value' => 'stripe_elements',
						'choices'       => array(
							array(
								'label'   => esc_html__( 'Stripe Field (Elements, SCA-ready)', 'gravityformsstripe' ),
								'value'   => 'stripe_elements',
								'tooltip' => '<h6>' . esc_html__( 'Stripe Field', 'gravityformsstripe' ) . '</h6>' .
								             '<p>' . esc_html__( 'Select this option to use a Stripe field hosted by Stripe. The Stripe field can be embedded in a form and supports credit card payments as well as other payment methods such as Google Pay, Apple Pay and bank transfers.', 'gravityformsstripe' ) .
								             '</p><p>' .
								             /* translators: 1. Open link tag 2. Close link tag */
								             sprintf( esc_html__( 'Stripe Field is ready for %1$sStrong Customer Authentication%2$s for European customers.', 'gravityformsstripe' ), '<a href="https://stripe.com/docs/strong-customer-authentication" target="_blank">', '</a>' ) .
								             '</p>',
							),
							array(
								'label'   => esc_html__( 'Stripe Payment Form (Stripe Checkout, SCA-ready)', 'gravityformsstripe' ),
								'value'   => 'stripe_checkout',
								'tooltip' => '<h6>' . esc_html__( 'Stripe Payment Form', 'gravityformsstripe' ) . '</h6>' .
								             '<p>' . esc_html__( 'Select this option to collect all payment information in a separate page hosted by Stripe. This option is the simplest to implement since it doesn\'t require a Stripe field in your form. Stripe Checkout supports credit card payments as well as other payment methods such as Google Pay, Apple Pay and bank transfers.', 'gravityformsstripe' ) .
								             '</p><p>' .
								             /* translators: 1. Open link tag 2. Close link tag */
								             sprintf( esc_html__( 'Stripe Checkout is ready for %1$sStrong Customer Authentication%2$s for European customers.', 'gravityformsstripe' ), '<a href="https://stripe.com/docs/strong-customer-authentication" target="_blank">', '</a>' ) .
								             '</p>',
							),
						),
					),
				),
			);
		}

		return $fields;

	}

	/**
	 * Define the settings which appear in the Stripe API section.
	 *
	 * @since  2.8     Add Stripe Connect fields.
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::plugin_settings_fields()
	 *
	 * @return array The API settings fields.
	 */
	public function api_settings_fields() {

		// If no ssl certificate found, just return the ssl error message.
		if ( ! is_ssl() ) {
			return array(
				array(
					'name' => 'ssl_error',
					'type' => 'ssl_error',
				),
			);
		}

		$fields = array(
			array(
				'name'       => 'connected_to',
				'label'      => esc_html__( 'Connected to Stripe as', 'gravityformsstripe' ),
				'type'       => 'connected_to',
				'dependency' => array( $this, 'is_detail_page' ),
				'tooltip'    => '<h6>' . esc_html__( 'Connected to Stripe as', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'The Stripe account this feed is currently connected to.', 'gravityformsstripe' ),
			),
			array(
				'name'          => 'api_mode',
				'label'         => esc_html__( 'Mode', 'gravityformsstripe' ),
				'type'          => 'radio',
				'default_value' => $this->get_api_mode( $this->get_plugin_settings() ),
				'choices'       => array(
					array(
						'label' => esc_html__( 'Live', 'gravityformsstripe' ),
						'value' => 'live',
					),
					array(
						'label' => esc_html__( 'Test', 'gravityformsstripe' ),
						'value' => 'test',
					),
				),
				'horizontal'    => true,
			),
			array(
				'name'       => 'live_auth_token',
				'type'       => 'auth_token_button',
				'dependency' => $this->get_auth_button_dependency( 'live' ),
			),
			array(
				'name'       => 'test_auth_token',
				'type'       => 'auth_token_button',
				'dependency' => $this->get_auth_button_dependency( 'test' ),
			),
			array(
				'label' => 'hidden',
				'name'  => 'live_publishable_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'live_secret_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'test_publishable_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'test_secret_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'stripe_admin_init_intent',
				'type'  => 'hidden',
			),
		);

		if ( ! $this->is_detail_page() && ! $this->is_stripe_connect_enabled() ) {
			$legacy_connect_fields = array(
				array(
					'name'     => 'test_publishable_key',
					'label'    => esc_html__( 'Test Publishable Key', 'gravityformsstripe' ),
					'type'     => 'text',
					'class'    => 'medium',
					'onchange' => "GFStripeAdmin.validateKey('test_publishable_key', this.value);",
				),
				array(
					'name'       => 'test_secret_key',
					'label'      => esc_html__( 'Test Secret Key', 'gravityformsstripe' ),
					'type'       => 'text',
					'input_type' => 'password',
					'class'      => 'medium',
					'onchange'   => "GFStripeAdmin.validateKey('test_secret_key', this.value);",
				),
				array(
					'name'     => 'live_publishable_key',
					'label'    => esc_html__( 'Live Publishable Key', 'gravityformsstripe' ),
					'type'     => 'text',
					'class'    => 'medium',
					'onchange' => "GFStripeAdmin.validateKey('live_publishable_key', this.value);",
				),
				array(
					'name'       => 'live_secret_key',
					'label'      => esc_html__( 'Live Secret Key', 'gravityformsstripe' ),
					'type'       => 'text',
					'input_type' => 'password',
					'class'      => 'medium',
					'onchange'   => "GFStripeAdmin.validateKey('live_secret_key', this.value);",
				),
			);

			$fields = array_merge( $fields, $legacy_connect_fields );
		} else {
			$stripe_connect_fields = array(
				array(
					'name'  => 'test_publishable_key',
					'label' => esc_html__( 'Test Publishable Key', 'gravityformsstripe' ),
					'type'  => 'hidden',
				),
				array(
					'name'  => 'test_secret_key',
					'label' => esc_html__( 'Test Secret Key', 'gravityformsstripe' ),
					'type'  => 'hidden',
				),
				array(
					'name'  => 'live_publishable_key',
					'label' => esc_html__( 'Live Publishable Key', 'gravityformsstripe' ),
					'type'  => 'hidden',
				),
				array(
					'name'  => 'live_secret_key',
					'label' => esc_html__( 'Live Secret Key', 'gravityformsstripe' ),
					'type'  => 'hidden',
				),
			);

			$fields = array_merge( $fields, $stripe_connect_fields );
		}


		$webhook_fields = array(
			array(
				'name'        => 'webhooks_enabled',
				'label'       => esc_html__( 'Webhooks Enabled?', 'gravityformsstripe' ),
				'type'        => 'checkbox',
				'horizontal'  => true,
				'required'    => ( $this->get_current_feed_id() && $this->is_feed_stripe_connect_enabled() ) || ! isset( $_GET['fid'] ),
				'description' => $this->get_webhooks_section_description(),
				'dependency'  => $this->get_webhooks_dependency(),
				'choices'     => array(
					array(
						'label' => esc_html__( 'I have enabled the Gravity Forms webhook URL in my Stripe account.', 'gravityformsstripe' ),
						'value' => 1,
						'name'  => 'webhooks_enabled',
					),
				),
			),
			array(
				'name'                => 'test_signing_secret',
				'label'               => esc_html__( 'Test Signing Secret', 'gravityformsstripe' ),
				'type'                => 'text',
				'input_type'          => 'password',
				'class'               => 'medium',
				'dependency'          => $this->get_webhooks_dependency(),
				'validation_callback' => array( $this, 'validate_webhook_signing_secret' ),
			),
			array(
				'name'                => 'live_signing_secret',
				'label'               => esc_html__( 'Live Signing Secret', 'gravityformsstripe' ),
				'type'                => 'text',
				'input_type'          => 'password',
				'class'               => 'medium',
				'dependency'          => $this->get_webhooks_dependency(),
				'validation_callback' => array( $this, 'validate_webhook_signing_secret' ),
			),
		);

		$fields = array_merge( $fields, $webhook_fields );

		return $fields;
	}

	/**
	 * Generates dependency array for auth token button if live dependencies are supported.
	 *
	 * @param string $api_mode The API mode that the button value represents, could be live or test.
	 *
	 * @since 3.8
	 *
	 * @return array|false
	 */
	private function get_auth_button_dependency( $api_mode ) {
		if ( ! $this->_has_settings_renderer ) {
			return false;
		}

		return array(
			'live'   => true,
			'fields' => array(
				array(
					'field'  => 'api_mode',
					'values' => array( $api_mode ),
				),
			),
		);
	}

	/**
	 * Generates webhooks fields dependency if required.
	 *
	 * @since 3.8
	 *
	 * @return array|false
	 */
	private function get_webhooks_dependency() {
		if ( ! $this->is_detail_page() ) {
			return false;
		}

		return array( $this, 'is_feed_stripe_connect_enabled' );
	}

	/**
	 * Generates the markup for the SSL error message field.
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function settings_ssl_error( $field, $echo = true ) {

		$html = $this->_has_settings_renderer ? '<div class="alert gforms_note_error">' : '<div class="alert_red" style="padding:20px; padding-top:5px;">';
		$html .= '<h4>' . esc_html__( 'SSL Certificate Required', 'gravityformsstripe' ) . '</h4>';
		/* Translators: 1: Open link tag 2: Close link tag */
		$html .= sprintf( esc_html__( 'Make sure you have an SSL certificate installed and enabled, then %1$sclick here to reload the settings page%2$s.', 'gravityformsstripe' ), '<a href="' . $this->get_settings_page_url() . '">', '</a>' );
		$html .= '</div>';

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Define the markup to be displayed for the webhooks section description.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::plugin_settings_fields()
	 * @uses    GFStripe::get_webhook_url()
	 *
	 * @return string HTML formatted webhooks description.
	 */
	public function get_webhooks_section_description() {
		ob_start();
		?>
		<a href="https://docs.gravityforms.com/downloading-installing-the-stripe-add-on/#h-webhooks" target="_blank"><?php esc_html_e( 'View Webhooks Instructions', 'gravityformsstripe' ); ?></a></p>
		<p><?php esc_html_e( 'Here is your Endpoint URL:', 'gravityformsstripe' ); ?>
			<code><span data-copy="webhookUrl"><?php echo $this->get_webhook_url( $this->get_current_feed_id() ); ?></span></code>
			<button
				class="gform-button gform-button--size-xs gform-button--white gform-button--width-auto gform-button--active-type-loader gform-button--loader-after gform-button--icon-trailing gform-copy-text"
				aria-label="<?php esc_attr_e( 'Copy Webhook URL', 'gravityformsstripe' ); ?>"
			>
				<span class="gform-button__text gform-button__text--inactive">
					<?php esc_html_e( 'Copy URL', 'gravityformsstripe' ); ?>
				</span>
				<span class="gform-icon gform-icon--copy gform-button__icon"></span>
			</button>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Define the markup to be displayed for the Stripe Checkout section description.
	 *
	 * @since 2.6
	 * @since 3.4 Update description about removing CC field support.
	 */
	public function get_checkout_method_section_description() {
		ob_start();
		?>
		<p><?php esc_html_e( 'Select how payment information will be collected. You can select one of the Stripe hosted solutions (Stripe Credit Card or Stripe Checkout) which simplifies the PCI compliance process with Stripe.', 'gravityformsstripe' ); ?></p>

		<?php
		if ( $this->get_form_with_deprecated_cc_field() ) {
			/* translators: Placeholders represent opening and closing link tags. */
			echo '<p>' . sprintf( esc_html__( 'The Gravity Forms Credit Card Field was deprecated in the Stripe Add-On in version 3.4. Forms that are currently using this field will stop working in a future version. Refer to %1$sthis guide%2$s for more information about this change.', 'gravityformsstripe' ), '<a href="https://docs.gravityforms.com/deprecation-of-the-gravity-forms-credit-card-field/" target="_blank">', '</a>' ) . '</p>';
		}

		return ob_get_clean();
	}

	/**
	 * Create Connect with Stripe settings field.
	 *
	 * @since  2.8
	 * @access public
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_auth_token_button( $field, $echo = true ) {

		$settings = $this->get_settings();

		if ( ! $this->can_display_connect_button() ) {
			return '';
		}

		$api_mode = ( $field['name'] === 'live_auth_token' ) ? 'live' : 'test';

		// Get authentication URL.
		$license_key = GFCommon::get_key();
		$nonce       = wp_create_nonce( $this->get_authentication_state_action() );
		$auth_url    = add_query_arg(
			array(
				'mode'        => $api_mode,
				'redirect_to' => rawurlencode( $this->get_settings_page_url() ),
				'license'     => $license_key,
				'state'       => $nonce,
			),
			$this->get_gravity_api_url( '/auth/stripe' )
		);

		if ( get_transient( 'gravityapi_request_gravityformsstripe' ) ) {
			delete_transient( 'gravityapi_request_gravityformsstripe' );
		}

		set_transient( 'gravityapi_request_gravityformsstripe', $nonce, 10 * MINUTE_IN_SECONDS );

		// Create connect button markup.
		$connect_button = sprintf(
			'<a href="%5$s" class="gform_stripe_auth_button"><img alt="%1$s" src="%2$s" srcset="%2$s 1x, %3$s 2x, %4$s 3x"></a>',
			esc_html__( 'Click here to authenticate with Stripe', 'gravityformsstripe' ),
			$this->get_base_url() . '/images/light-on-dark.png',
			$this->get_base_url() . '/images/light-on-dark@2x.png',
			$this->get_base_url() . '/images/light-on-dark@3x.png',
			$auth_url
		);

		$deprecated_auth_message = '';
		if ( $this->requires_reauthentication() ) {
			$deprecated_auth_message  = $this->_has_settings_renderer ? '<div class="alert gforms_note_error">' : '<div class="alert_red" style="padding:20px; padding-top:5px; margin-bottom: 20px;">';
			$deprecated_auth_message .= '<p>' . esc_html__( 'You are currently logged in to Stripe using a deprecated authentication method.', 'gravityformsstripe' ) . '</p>';
			/* Translators: 1: Open strong tag 2: Close strong tag */
			$deprecated_auth_message .= '<p>' . sprintf( esc_html__( '%1$sPlease login to your Stripe account via Stripe Connect using the button below.%2$s It is a more secure authentication method and will be required for upcoming features of the Stripe Add-on.', 'gravityformsstripe' ), '<strong>', '</strong>' ) . '</p>';
			$deprecated_auth_message .= '</div>';
		}

		// translators: Placeholders represent wrapping link tag.
		$learn_more_message = '<p>' . sprintf( esc_html__( '%1$sLearn more%2$s about connecting with Stripe.', 'gravityformsstripe' ), '<a target="_blank" href=" https://docs.gravityforms.com/faq-authenticating-with-stripe/">', '</a>' ) . '</p>';

		$connect_button = $deprecated_auth_message . $connect_button . $learn_more_message;

		$is_valid = $this->is_stripe_auth_valid( $settings, $api_mode );

		if ( $is_valid ) {
			if ( ! rgblank( $this->get_stripe_user_id( $settings, $api_mode ) ) && ! $this->is_detail_page() ) {
				$display_name  = $this->get_stripe_display_name( $is_valid );

				$html = '';
				if ( ! $this->is_detail_page() && ! empty( $display_name ) ) {
					$html .= sprintf('<p class="connected_to_stripe_text"><span class="gform-status-indicator gform-status-indicator--size-sm gform-status-indicator--theme-cosmos gform-status--active gform-status--no-icon gform-status--no-hover">' .
						'<span class="gform-status-indicator-status gform-typography--weight-medium gform-typography--size-text-xs">%1$s: %2$s</span></span></p>',
						esc_html__( 'Connected to Stripe as', 'gravityformsstripe' ),
						esc_html( $display_name )
					);
				}
				$html .= sprintf(
					' <a href="#" class="button gform_stripe_deauth_button" data-fid="%2$s" data-id="%3$s" data-mode="%4$s">%1$s</a>',
					esc_html__( 'Disconnect from Stripe', 'gravityformsstripe' ),
					$this->get_current_feed_id(),
					rgget( 'id' ),
					$api_mode
				);

				// Display the deauth options.
				$html .= '<div id="deauth_scope" class="deauth_scope">';
				$html .= '<p><label for="' . $api_mode . '_deauth_scope0"><input type="radio" name="deauth_scope" value="site" id="' . $api_mode . '_deauth_scope0" checked="checked">';

				$target = $this->is_detail_page() ? 'feed' : 'site';

				// translators: placeholder represents contextual target, either "feed" or "site".
				$html .= sprintf( esc_html__( 'Disconnect this %s only', 'gravityformsstripe' ), $target );
				$html .= '</label></p>';
				$html .= '<p><label for="' . $api_mode . '_deauth_scope1"><input type="radio" name="deauth_scope" value="account" id="' . $api_mode . '_deauth_scope1">' . esc_html__( 'Disconnect all Gravity Forms sites connected to this Stripe account', 'gravityformsstripe' ) . '</label></p>';
				$html .= sprintf(
					'<p><a href="#" id="gform_stripe_deauth_button" class="button gform_stripe_deauth_button" data-fid="%2$s" data-id="%3$s" data-mode="%4$s">%1$s</a></p>',
					esc_html__( 'Disconnect from Stripe', 'gravityformsstripe' ),
					$this->get_current_feed_id(),
					rgget( 'id' ),
					$api_mode
				);
				$html .= '</div>';
			} else {
				$html = $connect_button;
			}
		} else {
			$html = $connect_button;
		}

		// Lock account settings when it's a post back from changing transaction type.
		if ( ( rgpost( $this->_input_prefix . '_transactionType' ) && ! $this->is_save_postback() ) ) {
			$html .= '<script>';
			$html .= 'jQuery(document).ready(function(){
						GFStripeAdmin.accountSettingsLocked = true;});';
			$html .= '</script>';
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Decides if authentication button can be displayed or not.
	 *
	 * @since 3.8
	 * @since 3.9 - $settings parameter no longer needed.
	 *
	 * @return bool
	 */
	private function can_display_connect_button() {
		return ( $this->is_detail_page() || $this->is_stripe_connect_enabled() );
	}

	/**
	 * Retrieves the plugin settings or feed settings depending on current screen.
	 *
	 * @since 3.8
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( $this->is_detail_page() ) {
			$feed = $this->get_current_feed();
		}

		return ! empty( $feed['meta'] ) ? $feed['meta'] : $this->get_plugin_settings();
	}

	/**
	 * Generates plugin settings page URL or feed details page URL depending on current screen.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_settings_page_url() {
		if ( ! $this->is_detail_page() ) {
			return admin_url( 'admin.php?page=gf_settings&subview=' . $this->get_slug(), 'https' );
		}

		return add_query_arg(
			array(
				'page'    => 'gf_edit_forms',
				'view'    => 'settings',
				'subview' => $this->get_slug(),
				'id'      => rgget( 'id' ),
				'fid'     => $this->get_current_feed_id(),
			),
			admin_url( 'admin.php', 'https' )
		);
	}

	/**
	 * Display the Connected To feed setting.
	 *
	 * @since 2.8
	 */
	public function settings_connected_to() {
		$html = '';

		if ( ! $this->is_feed_stripe_connect_enabled() ) {
			$settings = $this->get_plugin_settings();
			$api_mode = $this->get_api_mode( $settings );
		} else {
			$feed     = $this->get_current_feed();
			$settings = $feed['meta'];
			$api_mode = $this->get_api_mode( $settings, $this->get_current_feed_id() );
		}

		$is_valid = $this->is_stripe_auth_valid( $settings, $api_mode );

		$html .= '<p style="margin-top: -3px;">';
		if ( $is_valid ) {
			$display_name = $this->get_stripe_display_name( $is_valid );

			/* Translators: 1: Open strong tag 2: Account name 3: Close account tag */
			if ( ! empty( $display_name ) ) {
				$html .= sprintf(
					esc_html__( '%1$s%2$s%3$s', 'gravityformsstripe' ),
					'<strong>',
					$display_name,
					'</strong>'
				);

				$html .= ' &nbsp;&nbsp;';
			}
		}

		$classes = $this->_has_settings_renderer ? 'alert gforms_note_error' : 'alert_red';
		$html   .= $this->maybe_display_update_authentication_message( true, $classes );
		if ( ! $this->requires_reauthentication() ) {
			$disabled  = $this->get_current_feed_id() == 0 ? 'data-disabled="1"' : '';
			$html     .= '<a class="button" id="gform_stripe_change_account" ' . $disabled . ' href="javascript:void(0);">';
			$html     .= esc_html__( 'Switch Accounts', 'gravityformsstripe' );
			$html     .= '</a>';
		}

		$html .= '</p>';

		echo $html;
	}

	/**
	 * Stripe Checkout Logo Settings
	 *
	 * @since 3.0
	 */
	public function settings_stripe_checkout_logo() {
		/* Translators: 1. Open link tag. 2. Close link tag. */
		$html = sprintf( esc_html__( 'Logo can be configured on %1$sStripe\'s branding page%2$s.', 'gravityformsstripe' ), '<a href="https://dashboard.stripe.com/account/branding" target="_blank">', '</a>' );

		echo $html;
	}

	/**
	 * Get the authorization payload data.
	 *
	 * Returns the auth POST request if it's present, otherwise attempts to return a recent transient cache.
	 *
	 * @since 3.10
	 *
	 * @return array
	 */
	private function get_oauth_payload() {
		$payload = array_filter(
			array(
				'auth_payload' => rgpost( 'auth_payload' ),
				'auth_error'   => rgpost( 'auth_error' ),
				'state'        => rgpost( 'state' ),
			)
		);

		if ( count( $payload ) === 2 || isset( $payload['auth_error'] ) ) {
			return $payload;
		}

		$payload = get_transient( "gravityapi_response_{$this->_slug}" );

		if ( rgar( $payload, 'state' ) !== get_transient( "gravityapi_request_{$this->_slug}" ) ) {
			return array();
		}

		delete_transient( "gravityapi_response_{$this->_slug}" );

		return is_array( $payload ) ? $payload : array();
	}

	/**
	 * Store auth tokens when we get auth payload from Stripe Connect.
	 *
	 * @since 2.8
	 * @since 3.5.1 Added support for the state param.
	 */
	public function maybe_update_auth_tokens() {
		if ( rgget( 'subview' ) !== $this->get_slug() ) {
			return;
		}

		$payload = $this->get_oauth_payload();

		if ( ! $payload ) {
			return;
		}

		// If access token is provided, save it.
		$auth_payload = json_decode( base64_decode( rgar( $payload, 'auth_payload' ) ), true );

		// If state does not match, do not save.
		if ( rgpost( 'state' ) && ! wp_verify_nonce( rgar( $payload, 'state' ), $this->get_authentication_state_action() ) ) {
			// Add error message.
			GFCommon::add_error_message( esc_html__( 'Unable to connect to Stripe due to mismatched state.', 'gravityformsstripe' ) );

			return;
		}

		// Get access token.
		$access_token = rgar( $auth_payload, 'access_token' );

		$is_feed_settings = $this->is_detail_page() ? true : false;

		if ( $is_feed_settings ) {
			$settings = $this->get_current_feed();
			$settings = $settings['meta'];
		} else {
			$settings = (array) $this->get_plugin_settings();
		}

		$settings['api_mode'] = ( rgar( $auth_payload, 'livemode' ) === true ) ? 'live' : 'test';
		$auth_token           = $this->get_auth_token( $settings, $settings['api_mode'] );

		if ( empty( $auth_token ) || rgar( $settings, $settings['api_mode'] . '_secret_key' ) !== $access_token ) {
			// Add auth info to plugin settings.
			$settings[ $settings['api_mode'] . '_auth_token' ]               = array(
				'stripe_user_id' => rgar( $auth_payload, 'stripe_user_id' ),
				'refresh_token'  => rgar( $auth_payload, 'refresh_token' ),
				'date_created'   => time(),
			);
			$settings[ $settings['api_mode'] . '_secret_key' ]               = $access_token;
			$settings[ $settings['api_mode'] . '_secret_key_is_valid' ]      = '1';
			$settings[ $settings['api_mode'] . '_publishable_key' ]          = rgar( $auth_payload, 'stripe_publishable_key' );
			$settings[ $settings['api_mode'] . '_publishable_key_is_valid' ] = '1';

			// Save settings.
			if ( $is_feed_settings ) {
				$this->save_feed_settings( $this->get_current_feed_id(), rgget( 'id' ), $settings );
			} else {
				$this->update_plugin_settings( $settings );
			}

			// Reload page to load saved settings.
			wp_redirect( $this->get_settings_page_url() );
			exit();
		}

		// If error is provided, display message.
		if ( rgpost( 'auth_error' ) || isset( $payload['auth_error'] ) ) {
			// Add error message.
			GFCommon::add_error_message( esc_html__( 'Unable to authenticate with Stripe.', 'gravityformsstripe' ) );
		}
	}


	/**
	 * Target of the gform_payment_details hook. If appropriate, will add the Capture button in the payment details box
	 *
	 * @since 4.2
	 *
	 * @param int   $form_id Current form ID.
	 * @param array $entry Current Entry Object.
	 */
	public function maybe_display_capture_button( $form_id, $entry ) {
		if ( ! $this->should_display_capture_button( $entry ) ) {
			return;
		}

		$ajax_data = array(
			'nonce'          => wp_create_nonce( 'gfstripe_capture_payment' ),
			'entry_id'       => absint( $entry['id'] ),
			'transaction_id' => $entry['transaction_id'],
		);

		printf(
			'<p>
			<button href="#" data-ajax="%1$s" class="button stripe-capture">%2$s</button>
			<span id="capture_wait_container" style="display: none; margin-left: 5px;">
				<i class="gficon-gravityforms-spinner-icon gficon-spin"></i>%3$s
			</span>
			</p>
			<div class="alert_red gform_stripe_capture_alert" style="padding:10px; display:none;"></div>',

			esc_attr( wp_json_encode( $ajax_data ) ),
			esc_html__( 'Capture Payment', 'gravityformsstripe' ),
			esc_html__( 'Capturing payment', 'gravityformsstripe' )
		);
	}

	/**
	 * Checks if the capture payment button should be displayed or not.
	 *
	 * @since 4.2
	 *
	 * @param array $entry            Current entry being processed.
	 *
	 * @return bool Returns true if capture button should be displayed. Returns false otherwise.
	 */
	private function should_display_capture_button( $entry ) {
		return (
			$entry['transaction_type'] === '1'
			&& $this->is_payment_gateway( $entry['id'] )
			&& $entry['payment_status'] == 'Authorized'
			&& gform_get_meta( rgar( $entry, 'id' ), 'payment_method' ) !== 'stripe_payment_element'
		);
	}

	/**
	 * Add auth_token data when updating plugin settings.
	 *
	 * @since 2.8
	 *
	 * @param array $settings Plugin settings to be saved.
	 */
	public function update_plugin_settings( $settings ) {
		$modes = array( 'live', 'test' );
		foreach ( $modes as $mode ) {
			if ( rgempty( "{$mode}_auth_token", $settings ) ) {
				$auth_token = $this->get_plugin_setting( "{$mode}_auth_token" );

				if ( ! empty( $auth_token ) ) {
					$settings[ "{$mode}_auth_token" ] = $auth_token;
				}
			}
		}

		parent::update_plugin_settings( $settings );
	}

	/**
	 * Add auth_token data when updating feed settings.
	 *
	 * @since 2.8
	 *
	 * @param int   $feed_id Feed ID.
	 * @param int   $form_id Form ID.
	 * @param array $settings Feed settings.
	 *
	 * @return int
	 */
	public function save_feed_settings( $feed_id, $form_id, $settings ) {
		$feed = $this->get_feed( $feed_id );
		if ( rgar( $feed, 'meta' ) ) {
			$modes = array( 'live', 'test' );
			foreach ( $modes as $mode ) {
				if ( rgempty( "{$mode}_auth_token", $settings ) ) {
					$auth_token = $this->get_auth_token( $feed['meta'], $mode );

					if ( ! empty( $auth_token ) ) {
						$settings[ "{$mode}_auth_token" ] = $auth_token;
					}
				}
			}
		}

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}

	/**
	 * Get the payment feed for the current entry.
	 *
	 * @since 5.6.0
	 *
	 * @param array      $entry The current entry object.
	 * @param bool|array $form  The current form object or false.
	 *
	 * @return bool|array The payment feed for the current entry or false.
	 */
	public function get_payment_feed( $entry, $form = false ) {
		if ( empty( $entry['id'] ) && $form ) {
			$field = $this->get_stripe_card_field( $form );
			if ( $field && GFFormsModel::is_field_hidden( $form, $field, rgpost( 'gform_field_values' ) ) ) {
				$this->log_debug( __METHOD__ . '(): Stripe card field is hidden. No payment will be attempted.' );

				return false;
			}
		}

		$feed = parent::get_payment_feed( $entry, $form );

		return rgar( $feed, 'addon_slug' ) === $this->_slug ? $feed : false;
	}

	/**
	 * Add update authentication message.
	 *
	 * @since 2.8
	 */
	public function maybe_display_update_authentication_message( $return = false, $classes = 'notice notice-error' ) {

		if ( $this->requires_reauthentication() ) {
			$message = sprintf(
			/* Translators: 1: Open link tag 2: Close link tag */
				esc_html__( 'You are currently logged in to Stripe using a deprecated authentication method. %1$sRe-authenticate your Stripe account%2$s.', 'gravityformsstripe' ),
				'<a href="' . admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) . '">',
				'</a>'
			);

			$message = sprintf( '<div class="%s"><p>%s</p></div>', $classes, $message );

			if ( $return ) {
				return $message;
			} else {
				echo $message;
			}
		}
	}

	/**
	 * Adds a warning message if any form has an active stripe feed and is still using the deprecated credit card field.
	 *
	 * @since 3.9
	 */
	public function maybe_display_deprecated_cc_field_warning() {

		// If form is being saved, postpone check after it is saved.
		if ( $this->is_form_editor() && isset( $_POST['gform_meta'] ) ) {
			add_action( 'gform_after_save_form', array( $this, 'maybe_display_saved_form_deprecated_cc_field_warning' ), 10, 2 );
			return;
		}

		$form_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		// Check if a warning should be displayed for current form.
		if ( ! empty( $form_id ) && GFForms::is_gravity_page() ) {
			$form = GFAPI::get_form( $form_id );
			if ( $form && $this->has_feed( $form['id'] ) && $this->form_has_deprecated_cc_field( $form ) ) {
				$this->display_deprecated_cc_field_warning( $form );

				return;
			}
		}

		// Check if a warning should be displayed for any other forms.
		$form = $this->get_form_with_deprecated_cc_field();
		if ( false === $form ) {
			return;
		}

		$this->display_deprecated_cc_field_warning( $form );

	}

	/**
	 * Checks if the deprecated cc field warning should still be displayed after form has been saved.
	 *
	 * @since 3.9
	 *
	 * @param array $saved_form The current form being saved.
	 */
	public function maybe_display_saved_form_deprecated_cc_field_warning( $saved_form, $is_new ) {
		if ( ! $is_new && $this->has_feed( $saved_form['id'] ) && $this->form_has_deprecated_cc_field( $saved_form ) ) {
			$this->display_deprecated_cc_field_warning( $saved_form );
		}
	}

	/**
	 * Displays deprecated cc field warning message for a form.
	 *
	 * @since 3.9
	 *
	 * @param array $form Current form array being processed.
	 */
	private function display_deprecated_cc_field_warning( $form ) {
		$message = sprintf(
		/* translators: 1: Open strong tag, 2: Close strong tag, 3: Form title, 4: Open link tag, 5: Close link tag. */
			esc_html__( '%1$sImportant%2$s: The form %3$s is using a deprecated payment collection method for Stripe that will stop working in a future version. Take action now to continue collecting payments. %4$sLearn more.%5$s', 'gravityformsstripe' ),
			'<strong>',
			'</strong>',
			'<a href="admin.php?page=gf_edit_forms&id=' . intval( $form['id'] ) . '">' . esc_html( $form['title'] ) . '</a>',
			'<a target="_blank" href="https://docs.gravityforms.com/deprecation-of-the-gravity-forms-credit-card-field/">',
			'</a>'
		);

		echo sprintf( '<div class="notice notice-error gf-notice"><p>%s</p></div>', $message );
	}

	/**
	 * Gets the first form that has active feeds and is still using the deprecated credit card field.
	 *
	 * @since 3.9
	 *
	 * @return array|bool Form array or false if no forms founds.
	 */
	private function get_form_with_deprecated_cc_field() {

		$feeds = $this->get_active_feeds();

		foreach ( $feeds as $feed ) {
			$form = GFAPI::get_form( $feed['form_id'] );
			if ( $this->form_has_deprecated_cc_field( $form ) ) {
				return $form;
			}
		}

		return false;
	}

	/**
	 * Checks if provided form is still depending only on the deprecated cc field.
	 *
	 * @since 3.9
	 *
	 * @param array $form Current form being processed.
	 *
	 * @return bool
	 */
	private function form_has_deprecated_cc_field( $form ) {
		return $this->has_credit_card_field( $form ) && ! $this->has_stripe_card_field( $form );
	}

	/**
	 * Decides whether or not the notice for deprecated authentication message should be displayed
	 *
	 * @since 2.8
	 * @since 3.3 Fix how we define $is_valid.
	 *
	 * @return bool Returns true if the re-authentication message/notice should be displayed. Returns false otherwise
	 */
	public function requires_reauthentication() {

		$settings   = $this->get_plugin_settings();
		$api_mode   = $this->get_api_mode( $settings );
		$auth_token = $this->get_auth_token( $settings, $api_mode );
		$is_valid   = rgar( $settings, "{$api_mode}_publishable_key_is_valid" ) && rgar( $settings, "{$api_mode}_secret_key_is_valid" );

		return $is_valid && empty( $auth_token ) && $this->is_stripe_connect_enabled();
	}


	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Remove the add new button from the title.
	 *
	 * @since 2.6
	 * @since 3.4 Allow add new feed only for: 1) has CC field + current feeds; 2) has Stripe Card; 3) use Stripe Checkout.
	 *
	 * @return string
	 */
	public function feed_list_title() {
		if ( ! $this->can_create_feed() ) {
			return $this->form_settings_title();
		}

		return GFFeedAddOn::feed_list_title();
	}

	/**
	 * Get the require credit card message.
	 *
	 * @since 2.6
	 * @since 3.4 Allow add new feed only for: 1) has CC field + current feeds; 2) has Stripe Card; 3) use Stripe Checkout.
	 *
	 * @return false|string
	 */
	public function feed_list_message() {
		$checkout_method = $this->get_plugin_setting( 'checkout_method' );
		if ( ! $this->can_create_feed() && $checkout_method === 'stripe_elements' && ! $this->has_stripe_card_field() ) {
			return $this->requires_stripe_card_message();
		}

		return GFFeedAddOn::feed_list_message();
	}

	/**
	 * Display the requiring Stripe Card field message.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function requires_stripe_card_message() {
		$url = add_query_arg( array( 'view' => null, 'subview' => null ) );

		return sprintf( esc_html__( "You must add a Stripe Card field to your form before creating a feed. Let's go %sadd one%s!", 'gravityformsstripe' ), "<a href='" . esc_url( $url ) . "'>", '</a>' );
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::feed_settings_fields()
	 * @uses GFAddOn::replace_field()
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::add_field_after()
	 * @uses GFAddOn::remove_field()
	 * @uses GFAddOn::add_field_before()
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields() {

		// Get default payment feed settings fields.
		$default_settings = parent::feed_settings_fields();
		// Prepare customer information fields.
		$customer_info_field = array(
			'name'       => 'customerInformation',
			'label'      => esc_html__( 'Customer Information', 'gravityformsstripe' ),
			'type'       => 'field_map',
			'dependency' => array(
				'field' => 'transactionType',
				'values' => array( 'subscription' ),
			),
			'field_map'  => array(
				array(
					'name'       => 'email',
					'label'      => esc_html__( 'Email', 'gravityformsstripe' ),
					'required'   => true,
					'field_type' => array( 'email', 'hidden' ),
					'tooltip'    => '<h6>' . esc_html__( 'Email', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'You can specify an email field and it will be sent to Stripe as the customer\'s email.', 'gravityformsstripe' ),
				),
				array(
					'name'     => 'description',
					'label'    => esc_html__( 'Description', 'gravityformsstripe' ),
					'required' => false,
				),
				array(
					'name'       => 'coupon',
					'label'      => esc_html__( 'Coupon', 'gravityformsstripe' ),
					'required'   => false,
					'field_type' => array( 'text', 'coupon' ),
					'tooltip'    => '<h6>' . esc_html__( 'Coupon', 'gravityformsstripe' ) . '</h6><p>' . esc_html__( 'Select which field contains the coupon code to be applied to the recurring charge(s). The coupon must also exist in your Stripe Dashboard.', 'gravityformsstripe' ) . '</p><p>' . esc_html__( 'If you use Stripe Checkout, the coupon won\'t be applied to your first invoice.', 'gravityformsstripe' ) . '</p>',
				),
			),
		);

		if ( $this->is_payment_element_enabled( $this->get_current_form() ) ) {
			$customer_info_field = $this->get_payment_element_handler()->update_customer_info_field( $customer_info_field );
		}

		// Replace default billing information fields with customer information fields.
		$default_settings = $this->replace_field( 'billingInformation', $customer_info_field, $default_settings );

		// Define end of Metadata tooltip based on transaction type.
		if ( 'subscription' === $this->get_setting( 'transactionType' ) ) {
			$info = esc_html__( 'You will see this data when viewing a customer page.', 'gravityformsstripe' );
		} else {
			$info = esc_html__( 'You will see this data when viewing a payment page.', 'gravityformsstripe' );
		}

		// Prepare meta data field.
		$custom_meta = array(
			array(
				'name'                => 'metaData',
				'label'               => esc_html__( 'Metadata', 'gravityformsstripe' ),
				'type'                => 'dynamic_field_map',
				'limit'               => 50,
				'exclude_field_types' => array( 'creditcard', 'stripe_creditcard' ),
				'tooltip'             => '<h6>' . esc_html__( 'Metadata', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'You may send custom meta information to Stripe. A maximum of 50 custom keys may be sent. The key name must be 40 characters or less, and the mapped data will be truncated to 500 characters per requirements by Stripe. ' . $info, 'gravityformsstripe' ),
				'validation_callback' => array( $this, 'validate_custom_meta' ),
			),
		);

		// Add meta data field.
		$default_settings = $this->add_field_after( 'customerInformation', $custom_meta, $default_settings );

		// Remove subscription recurring times setting due to lack of Stripe support.
		$default_settings = $this->remove_field( 'recurringTimes', $default_settings );

		// Set trial field to be visibile by default, setup fee and trial period can coexist in stripe.
		$trial_field = array(
			'name'	 => 'trial',
			'label'  => esc_html__( 'Trial', 'gravityformsstripe' ),
			'type'   => 'trial',
			'hidden' => false,
			'tooltip' => '<h6>' . esc_html__( 'Trial Period', 'gravityforms' ) . '</h6>' . esc_html__( 'Enable a trial period.  The user\'s recurring payment will not begin until after this trial period.', 'gravityformsstripe' )
		);
		$default_settings = $this->replace_field( 'trial', $trial_field, $default_settings );

		// Prepare trial period field.
		$trial_period_field = array(
			'name'                => 'trialPeriod',
			'label'               => esc_html__( 'Trial Period', 'gravityformsstripe' ),
			'type'                => 'trial_period',
			'validation_callback' => array( $this, 'validate_trial_period' ),
		);

		if ( $this->_has_settings_renderer ) {
			$trial_period_field[ 'append' ] = esc_html__( 'days', 'gravityformsstripe' );
		} else {
			$trial_period_field[ 'style' ]       = 'width:40px;text-align:center;';
			$trial_period_field[ 'after_input' ] = '&nbsp;' . esc_html__( 'days', 'gravityformsstripe' );
		}
		// Add trial period field.
		$default_settings = $this->add_field_after( 'trial', $trial_period_field, $default_settings );

		// Add subscription name field.
		$subscription_name_field = array(
			'name'    => 'subscription_name',
			'label'   => esc_html__( 'Subscription Name', 'gravityformsstripe' ),
			'type'    => 'text',
			'class'   => 'medium merge-tag-support mt-hide_all_fields mt-position-right mt-exclude-creditcard-stripe_creditcard',
			'tooltip' => '<h6>' . esc_html__( 'Subscription Name', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Enter a name for the subscription. It will be displayed on the payment form as well as the Stripe dashboard.', 'gravityformsstripe' ),
		);
		$default_settings        = $this->add_field_before( 'recurringAmount', $subscription_name_field, $default_settings );

		// Get the other settings section index.
		$section_index  = count( $default_settings ) - 1 ;
		$other_settings = $default_settings[ $section_index ];

		if ( $this->has_stripe_card_field() ) {
			$default_settings[ $section_index ] = array(
				'title'      => esc_html__( 'Stripe Credit Card Field Settings', 'gravityformsstripe' ),
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'donation' ),
				),
				'fields'     => array(
					array(
						'name'      => 'billingInformation',
						'label'     => esc_html__( 'Billing Information', 'gravityformsstripe' ),
						'tooltip'   => '<h6>' . esc_html__( 'Billing Information', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Map your Form Fields to the available listed fields. The address information will be sent to Stripe.', 'gravityformsstripe' ),
						'type'      => 'field_map',
						'field_map' => $this->billing_info_fields(),
					),
				),
			);

			// Put the other settings section back.
			$default_settings[] = $other_settings;

		} elseif ( $this->is_stripe_checkout_enabled() ) {
			$default_settings[ $section_index ] = array(
				'title'       => esc_html__( 'Stripe Payment Form Settings', 'gravityformsstripe' ),
				'description' => esc_html__( 'The following settings control information displayed on the Stripe hosted payment page that is displayed after the form is submitted.', 'gravityformsstripe' ),
				'dependency'  => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'donation' ),
				),
				'fields'      => $this->get_stripe_payment_form_settings(),
			);

			// Put the other settings section back.
			$default_settings[] = $other_settings;
		}

		// Add receipt field if the feed transaction type is a product.
		if ( 'product' === $this->get_setting( 'transactionType' ) ) {

			$receipt_settings = array(
				'name'    => 'receipt',
				'label'   => esc_html__( 'Stripe Receipt', 'gravityformsstripe' ),
				'type'    => 'receipt',
				'tooltip' => '<h6>' . esc_html__( 'Stripe Receipt', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Stripe can send a receipt via email upon payment. Select an email field to enable this feature.', 'gravityformsstripe' ),
			);

			$default_settings = $this->add_field_before( 'conditionalLogic', $receipt_settings, $default_settings );

		}

		if ( $this->is_stripe_connect_enabled() ) {
			// Add Stripe Account section if stripe connect is enabled.
			$default_settings[] = array(
				'title'      => esc_html__( 'Stripe Account', 'gravityformsstripe' ),
				'fields'     => $this->api_settings_fields(),
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'donation' ),
				),
			);
		}

		return $default_settings;

	}

	/**
	 * Checkout setting fields in the feed settings.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get_stripe_payment_form_settings() {
		$settings = array(
			array(
				'name'  => 'logo',
				'label' => esc_html__( 'Logo', 'gravityformsstripe' ),
				'type'  => 'stripe_checkout_logo',
			),
			array(
				'name'       => 'customer_email',
				'label'      => esc_html__( 'Customer Email', 'gravityformsstripe' ),
				'type'       => 'receipt',
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'product' ),
				),
				'tooltip'    => '<h6>' . esc_html__( 'Customer Email', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'You can specify an email field and it will be sent to Stripe as the customer\'s email.', 'gravityformsstripe' ),
			),
			array(
				'name'          => 'billingAddress',
				'label'         => esc_html__( 'Billing Address', 'gravityformsstripe' ),
				'type'          => 'radio',
				'tooltip'       => '<h6>' . esc_html__( 'Billing Address', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'When enabled, Stripe Checkout will collect the customer\'s billing address for you.', 'gravityformsstripe' ),
				'horizontal'    => true,
				'choices'       => array(
					array(
						'label' => esc_html__( 'Enabled', 'gravityformsstripe' ),
						'value' => 1,
					),
					array(
						'label' => esc_html__( 'Disabled', 'gravityformsstripe' ),
						'value' => 0,
					),
				),
				'default_value' => 0,
			),
		);

		return $settings;
	}

	/**
	 * Prevent feeds being listed or created if the API keys aren't valid.
	 *
	 * @since  Unknown
	 * @since  3.4     Allow add new feed only for: 1) has CC field + current feeds; 2) has Stripe Card; 3) use Stripe Checkout.
	 * @access public
	 *
	 * @used-by GFFeedAddOn::feed_edit_page()
	 * @used-by GFFeedAddOn::feed_list_message()
	 * @used-by GFFeedAddOn::feed_list_title()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFStripe::get_api_mode()
	 *
	 * @return bool True if feed creation is allowed. False otherwise.
	 */
	public function can_create_feed() {
		return $this->is_stripe_account_set() && $this->feed_allowed_for_current_form();
	}

	/**
	 * Checks if current form being processed ready to have a stripe feed.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	private function feed_allowed_for_current_form(){
		$checkout_method = $this->get_plugin_setting( 'checkout_method' );
		$form            = $this->get_current_form();
		$feeds           = $this->get_feeds_by_slug( $this->_slug, $form['id'] );
		// If stripe checkout is not used, form must have a stripe elements field, or a credit card field and at least one feed that has already been created before.
		return (
			$checkout_method === 'stripe_checkout'
			|| $this->has_stripe_card_field( $form )
			|| ( $this->has_credit_card_field( $form ) && $feeds )
		);
	}

	/**
	 * Checks if stripe account is authenticated and valid.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	private function is_stripe_account_set(){
		$settings    = $this->get_plugin_settings();
		$api_mode    = $this->get_api_mode( $settings );

		return (
			rgar( $settings, "{$api_mode}_publishable_key_is_valid" )
			&& rgar( $settings, "{$api_mode}_secret_key_is_valid" )
			&& $this->is_webhook_enabled()
		);
	}

	/**
	 * Enable feed duplication on feed list page and during form duplication.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return false
	 */
	public function can_duplicate_feed( $id ) {

		return false;

	}

	/**
	 * Define the markup for the field_map setting table header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The header HTML markup.
	 */
	public function field_map_table_header() {
		return '<thead>
					<tr>
						<th></th>
						<th></th>
					</tr>
				</thead>';
	}

	/**
	 * Define the markup for the receipt type field.
	 *
	 * @since  Unknown
	 * @since  3.0     Changed to support customer email field in Stripe Payment form settings.
	 * @access public
	 *
	 * @uses GFAddOn::get_form_fields_as_choices()
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::settings_select()
	 *
	 * @param array     $field The field properties. Not used.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_receipt( $field, $echo = true ) {
		$input_types = array( 'email', 'hidden' );

		if ( $field['name'] === 'receipt' ) {
			// Prepare first field choice and get form fields as choices.
			$first_choice = array(
				'label' => esc_html__( 'Do not send receipt', 'gravityformsstripe' ),
				'value' => '',
			);
		} elseif ( $field['name'] === 'customer_email' ) {
			// Prepare first field choice and get form fields as choices.
			$first_choice = array(
				'label' => esc_html__( 'Do not set customer email', 'gravityformsstripe' ),
				'value' => '',
			);

			$input_types = array( 'email' );
		}

		$fields = $this->get_form_fields_as_choices(
			$this->get_current_form(),
			array(
				'input_types' => $input_types,
			)
		);

		// Add first choice to the beginning of the fields array.
		array_unshift( $fields, $first_choice );

		// Prepare select field settings.
		$select = array(
			'name'    => $field['name'] . '_field',
			'choices' => $fields,
		);

		// Get select markup.
		$html = $this->settings_select( $select, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Define the markup for the setup_fee type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::get_payment_choices()
	 * @uses GFAddOn::settings_checkbox()
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::settings_select()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_setup_fee( $field, $echo = true ) {

		// Prepare checkbox field settings.
		$enabled_field = array(
			'name'       => $field['name'] . '_checkbox',
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'    => esc_html__( 'Enabled', 'gravityformsstripe' ),
					'name'     => $field['name'] . '_enabled',
					'value'    => '1',
					'onchange' => "if(jQuery(this).prop('checked')){
						jQuery('#{$field['name']}_product').prop('disabled', false);
					} else {
						jQuery('#{$field['name']}_product').prop('disabled', true);
					}",
				),
			),
		);

		// Get checkbox field markup.
		$html = $this->settings_checkbox( $enabled_field, false );

		// Get current form.
		$form = $this->get_current_form();

		// Get enabled state.
		$is_enabled = $this->get_setting( "{$field['name']}_enabled" );

		// Prepare setup fee select field settings.
		$product_field = array(
			'name'     => $field['name'] . '_product',
			'type'     => 'select',
			'choices'  => $this->get_payment_choices( $form ),
		);

		if ( ! $is_enabled ) {
			$product_field['disabled'] = 'disabled';
		}

		// Add select field markup to checkbox field markup.
		$html .= '&nbsp' . $this->settings_select( $product_field, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Define the markup for the trial type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_checkbox()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial( $field, $echo = true ) {

		// Prepare enabled field settings.
		$enabled_field = array(
			'name'       => $field['name'] . '_checkbox',
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'    => esc_html__( 'Enabled', 'gravityformsstripe' ),
					'name'     => $field['name'] . '_enabled',
					'value'    => '1',
					'onchange' => "if(jQuery(this).prop('checked')){
						jQuery('#{$this->_input_container_prefix}trialPeriod').show();
					} else {
						jQuery('#{$this->_input_container_prefix}trialPeriod').hide();
						jQuery('#trialPeriod').val( '' );
					}",
				),
			),
		);

		// Get checkbox markup.
		$html = $this->settings_checkbox( $enabled_field, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Define the markup for the trial_period type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_text()
	 * @uses GFAddOn::field_failed_validation()
	 * @uses GFAddOn::get_error_icon()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial_period( $field, $echo = true ) {

		// Get text input markup.
		$html = $this->settings_text( $field, false );

		// Prepare validation placeholder name.
		$validation_placeholder = array( 'name' => 'trialValidationPlaceholder' );

		// Add validation indicator.
		if ( $this->field_failed_validation( $validation_placeholder ) ) {
			$html .= '&nbsp;' . $this->get_error_icon( $validation_placeholder );
		}

		// If trial is not enabled and setup fee is enabled, hide field.
		$html .= '
			<script type="text/javascript">
			if( ! jQuery( "#trial_enabled" ).is( ":checked" ) || jQuery( "#setupFee_enabled" ).is( ":checked" ) ) {
				jQuery( "#trial_enabled" ).prop( "checked", false );
				jQuery( "#' . $this->_input_container_prefix . 'trialPeriod" ).hide();
			}
			</script>';

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Validate the trial_period type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 *
	 * @param array $field The field properties. Not used.
	 *
	 * @return void
	 */
	public function validate_trial_period( $field ) {

		// Get posted settings.
		$settings = $this->get_posted_settings();

		// If trial period is not numeric, set field error.
		if ( $settings['trial_enabled'] && ( empty( $settings['trialPeriod'] ) || ! ctype_digit( $settings['trialPeriod'] ) ) ) {
			$this->set_field_error( $field, esc_html__( 'Please enter a valid number of days.', 'gravityformsstripe' ) );
		}

	}

	/**
	 * Validate the custom_meta type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 *
	 * @param array $field The field properties. Not used.
	 *
	 * @return void
	 */
	public function validate_custom_meta( $field ) {

		/*
		 * Number of keys is limited to 50.
		 * Interface should control this, validating just in case.
		 * Key names have maximum length of 40 characters.
		 */

		// Get metadata from posted settings.
		if ( $this->_has_settings_renderer ) {
			$settings  = $this->get_settings_renderer()->get_posted_values();
		} else {
			$settings  = $this->get_posted_settings();
		}

		$meta_data = ! empty( $settings['metaData'] ) ? $settings['metaData'] : '' ;

		// If metadata is not defined, return.
		if ( empty( $meta_data ) ) {
			return;
		}

		// Get number of metadata items.
		$meta_count = count( $meta_data );

		// If there are more than 50 metadata keys, set field error.
		if ( $meta_count > 50 ) {
			$meta_count_error = array( esc_html__( 'You may only have 50 custom keys.' ), 'gravityformsstripe' );
			$this->set_field_error( $meta_count_error );
			return;
		}

		$custom_meta_field = array( 'name' => 'metaData' );
		// Loop through metadata and check the key name length (custom_key).
		foreach ( $meta_data as $meta ) {
			if ( empty( $meta['custom_key'] ) && ! empty( $meta['value'] ) ) {
				$this->set_field_error( $custom_meta_field, esc_html__( "A field has been mapped to a custom key without a name. Please enter a name for the custom key, remove the metadata item, or return the corresponding drop down to 'Select a Field'.", 'gravityformsstripe' ) );
				break;
			} else if ( strlen( $meta['custom_key'] ) > 40 ) {
				$this->set_field_error( $custom_meta_field, sprintf( esc_html__( 'The name of custom key %s is too long. Please shorten this to 40 characters or less.', 'gravityformsstripe' ), $meta['custom_key'] ) );
				break;
			}
		}

	}

	/**
	 * Validate webhook signing secret.
	 *
	 * @since 3.0
	 *
	 * @param array  $field         The field object.
	 * @param string $field_setting The field value.
	 */
	public function validate_webhook_signing_secret( $field, $field_setting ) {

		if ( ! empty( $field_setting ) && strpos( $field_setting, 'whsec_' ) === false ) {
			$this->set_field_error( $field, esc_html__( 'Please use the correct webhook signing secret, which should start with "whsec_".', 'gravityformsstripe' ) );
		}
	}

	/**
	 * Define the choices available in the billing cycle dropdowns.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::settings_billing_cycle()
	 *
	 * @return array Billing intervals that are supported.
	 */
	public function supported_billing_intervals() {

		return array(
			'day'   => array( 'label' => esc_html__( 'day(s)', 'gravityformsstripe' ), 'min' => 1, 'max' => 365 ),
			'week'  => array( 'label' => esc_html__( 'week(s)', 'gravityformsstripe' ), 'min' => 1, 'max' => 12 ),
			'month' => array( 'label' => esc_html__( 'month(s)', 'gravityformsstripe' ), 'min' => 1, 'max' => 12 ),
			'year'  => array( 'label' => esc_html__( 'year(s)', 'gravityformsstripe' ), 'min' => 1, 'max' => 1 ),
		);

	}

	/**
	 * Prevent the 'options' checkboxes setting being included on the feed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::other_settings_fields()
	 *
	 * @return false
	 */
	public function option_choices() {
		return false;
	}



	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add supported notification events.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFeedAddOn::notification_events()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 */
	public function supported_notification_events( $form ) {

		// If this form does not have a Stripe feed, return false.
		if ( ! $this->has_feed( $form['id'] ) ) {
			return false;
		}

		// Return Stripe notification events.
		return array(
			'add_pending_payment'       => esc_html__( 'Payment Pending', 'gravityformsstripe' ),
			'authorized_payment'        => esc_html__( 'Payment Authorized', 'gravityformsstripe' ),
			'complete_payment'          => esc_html__( 'Payment Completed', 'gravityformsstripe' ),
			'refund_payment'            => esc_html__( 'Payment Refunded', 'gravityformsstripe' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'gravityformsstripe' ),
			'create_subscription'       => esc_html__( 'Subscription Created', 'gravityformsstripe' ),
			'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'gravityformsstripe' ),
			'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'gravityformsstripe' ),
			'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'gravityformsstripe' ),
		);

	}





	// # FRONTEND ------------------------------------------------------------------------------------------------------

	/**
	 * Initialize the frontend hooks.
	 *
	 * @since  2.6 Added more filters per Stripe Elements and Stripe Checkout.
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::register_init_scripts()
	 * @uses GFStripe::add_stripe_inputs()
	 * @uses GFStripe::pre_validation()
	 * @uses GFStripe::populate_credit_card_last_four()
	 * @uses GFPaymentAddOn::init()
	 *
	 * @return void
	 */
	public function init() {

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
		add_filter( 'gform_field_content', array( $this, 'add_stripe_inputs' ), 10, 5 );
		add_filter( 'gform_field_validation', array( $this, 'pre_validation' ), 10, 4 );
		add_filter( 'gform_pre_submission', array( $this, 'populate_credit_card_last_four' ) );
		add_filter( 'gform_field_css_class', array( $this, 'stripe_card_field_css_class' ), 10, 3 );
		add_filter( 'gform_submission_values_pre_save', array( $this, 'stripe_card_submission_value_pre_save' ), 10, 3 );
		add_filter( 'gform_shortcode_stripe_customer_portal_link', array( $this->get_billing_portal_handler(), 'stripe_customer_portal_link_shortcode' ), 10, 3 );

		// Supports frontend feeds.
		$this->_supports_frontend_feeds = true;

		switch ( $this->get_plugin_setting( 'checkout_method' ) ) {
			case 'credit_card':
				$this->_requires_credit_card = true;
				break;
		}

		if ( $this->is_stripe_checkout_enabled() ) {
			// Use priority 50 because users may hook to `gform_after_submission` for other purposes so we run it later.
			add_action( 'gform_after_submission', array( $this, 'stripe_checkout_redirect_scripts' ), 110, 2 );

            // Stripe checkout doesn't yet support new AJAX submissions. For forms with a Stripe feed, we need to force POSTBACK if new AJAX is set.
			add_filter( 'gform_form_args', function( $args ) {
				$stripe_feed = $this->get_active_feeds( $args['form_id'] );
				if( $stripe_feed && rgar( $args, 'submission_method' ) === GFFormDisplay::SUBMISSION_METHOD_AJAX ) {
					$args['submission_method'] = GFFormDisplay::SUBMISSION_METHOD_POSTBACK;
                }
				return $args;
			}, 1000 );
		}

		// Set UI prefixes depending on settings renderer availability.
		$this->_has_settings_renderer  = $this->is_gravityforms_supported( '2.5-beta' );
		$this->_input_prefix           = $this->_has_settings_renderer ? '_gform_setting' : '_gaddon_setting';
		$this->_input_container_prefix = $this->_has_settings_renderer ? 'gform_setting_' : 'gaddon-setting-row-';

		$this->register_services();

		parent::init();

	}

	/**
	 * Register Stripe script when displaying form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 * @uses    GFStripe::get_publishable_api_key()
	 * @uses    GFStripe::get_card_labels()
	 * @uses    GFFormDisplay::add_init_script()
	 * @uses    GFFormDisplay::ON_PAGE_RENDER
	 *
	 * @param array $form         Form object.
	 * @param array $field_values Current field values. Not used.
	 * @param bool  $is_ajax      If form is being submitted via AJAX.
	 *
	 * @return void
	 */
	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		if ( ! $this->frontend_script_callback( $form ) ) {
			return;
		}

		// Prepare Stripe Javascript arguments.
		$args = array(
			'apiKey'         => $this->get_publishable_api_key(),
			'formId'         => $form['id'],
			'pageInstance'   => isset( $form['page_instance'] ) ? $form['page_instance'] : 0,
			'isAjax'         => $is_ajax,
			'stripe_payment' => ( $this->has_stripe_card_field( $form ) ) ? ( $this->is_payment_element_enabled( $form ) ? 'payment_element' : 'elements' ) : 'stripe.js',
		);

		// When using the payment element, for some methods we can't decide if the payment has failed until the form is being reloaded
		// if that happens a value is stored in cache, so we can pass it to front end here.
		// Check  GF_Stripe_Payment_Element::validate_redirect_intent()
		$args['payment_element_intent_failure'] = GFCache::get( 'payment_element_intent_failure' );
		GFCache::delete( 'payment_element_intent_failure' );

		if ( $this->is_rate_limits_enabled( $form['id'] ) ) {
			$args['cardErrorCount'] = $this->get_card_error_count();
		}

		if ( $this->has_stripe_card_field( $form ) ) {
			$cc_field = $this->get_stripe_card_field( $form );
		} elseif ( $this->has_credit_card_field( $form ) ) {
			$cc_field = $this->get_credit_card_field( $form );
		}

		// Starts from 2.6, CC field isn't required when Stripe Checkout enabled.
		if ( isset( $cc_field ) ) {
			$args['ccFieldId']  = $cc_field->id;
			$args['ccPage']     = $cc_field->pageNumber;
			$args['cardLabels'] = $this->get_card_labels();
		}

		// getting all Stripe feeds.
		$args['currency'] = gf_apply_filters( array( 'gform_currency_pre_save_entry', $form['id'] ), GFCommon::get_currency(), $form );
		$feeds            = $this->get_feeds_by_slug( $this->_slug, $form['id'] );
		if ( $this->has_stripe_card_field( $form ) ) {
			$field = $this->get_stripe_card_field( $form );
			// Add options when creating Stripe Elements.
			$args['field'] = $field;
			/**
			 * This filter allows classes to be used with Stripe Elements
             * to control the look of the Credit Card field.
			 *
			 * @since 2.6
			 *
			 * @link https://stripe.com/docs/js/elements_object/create#elements_create-options-classes
			 *
			 * @param array The list of classes to be passed along to the Stripe element.
			 */
			$args['cardClasses'] = apply_filters( 'gform_stripe_elements_classes', array(), $form['id'] );

			/**
			 * This filter allows styles to be used with Stripe Elements
             * to control the look of the Credit Card field and/or Payment Element.
			 * NOTE: this filter does not apply to forms using the Orbital form theme (theme framework)
			 *
			 * @since 2.6
             * @since 5.0 Added an argument to know whether or not the payment element is enabled.
			 *
			 * @link https://stripe.com/docs/js/elements_object/create_element?type=card#elements_create-options-style
             * @link https://stripe.com/docs/elements/appearance-api
			 *
			 * @param array The list of styles to be passed along to the Stripe element.
			 */
			$args['cardStyle'] = apply_filters( 'gform_stripe_elements_style', array(), $form['id'], $this->is_payment_element_enabled( $form ) );

			foreach ( $feeds as $feed ) {
				if ( rgar( $feed, 'is_active' ) === '0' ) {
					continue;
				}
				$feed_settings = array(
					'feedId'          => $feed['id'],
					'type'            => rgars( $feed, 'meta/transactionType' ),
					'address_line1'   => rgars( $feed, 'meta/billingInformation_address_line1' ),
					'address_line2'   => rgars( $feed, 'meta/billingInformation_address_line2' ),
					'address_city'    => rgars( $feed, 'meta/billingInformation_address_city' ),
					'address_state'   => rgars( $feed, 'meta/billingInformation_address_state' ),
					'address_zip'     => rgars( $feed, 'meta/billingInformation_address_zip' ),
					'address_country' => rgars( $feed, 'meta/billingInformation_address_country' ),
					'coupon'          => rgars( $feed, 'meta/customerInformation_coupon' ),
					'hasTrial'        => rgars( $feed, 'meta/trial_enabled' ),
				);

				if ( rgars( $feed, 'meta/transactionType' ) === 'product' ) {
					$feed_settings['paymentAmount'] = rgars( $feed, 'meta/paymentAmount' );
				} else {
					$feed_settings['paymentAmount'] = rgars( $feed, 'meta/recurringAmount' );
					if ( rgars( $feed, 'meta/setupFee_enabled' ) ) {
						$feed_settings['setupFee'] = rgars( $feed, 'meta/setupFee_product' );
					}
				}

				if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
					$feed_settings['apiKey'] = $this->get_publishable_api_key( $feed['meta'] );
				}

				if ( $this->is_payment_element_enabled( $form ) ) {
					$feed_settings['initial_payment_information'] = $this->get_payment_element_handler()->get_initial_payment_information( $feed, $form );
					$stripe_field                                 = $this->get_stripe_card_field( $form );
					$feed_settings['link_email_field_id']         = rgobj( $field, 'linkEmailFieldId' );
				}

				$args['feeds'][] = $feed_settings;
			}
		} elseif ( $this->has_credit_card_field( $form ) ) {
			foreach ( $feeds as $feed ) {
				if ( rgar( $feed, 'is_active' ) === '0' ) {
					continue;
				}

				$feed_settings = array(
					'feedId' => $feed['id'],
				);

				if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
					$feed_settings['apiKey'] = $this->get_publishable_api_key( $feed['meta'] );
				}

				$args['feeds'][] = $feed_settings;
			}
		}



		// Initialize Stripe script.
		$args   = apply_filters( 'gform_stripe_object', $args, $form['id'] );
		$script = 'new GFStripe( ' . json_encode( $args, JSON_FORCE_OBJECT ) . ' );';

		// Add Stripe script to form scripts.
		GFFormDisplay::add_init_script( $form['id'], 'stripe', GFFormDisplay::ON_PAGE_RENDER, $script );
	}

	/**
	 * Checks if the payment element is enabled for the given form.
	 *
	 * @since 5.0
	 *
	 * @param array $form The current form object being processed.
	 *
	 * @return boolean Whether the payment element is enabled for the given form or not.
	 */
	public function is_payment_element_enabled( $form ) {
		if ( ! $this->is_payment_element_supported() ) {
			return false;
		}

		$cc_field = $this->get_stripe_card_field( $form );
		return $cc_field && rgobj( $cc_field, 'enableMultiplePaymentMethods' ) && $this->is_stripe_connect_enabled() === true;
	}

	/**
	 * Determines if the Payment Element is supported by the installed version of Gravity Forms.
	 *
	 * @since 5.0
	 *
	 * @return bool Returns true if Payment Element is supported by the installed version of Gravity Forms. Returns false otherwise.
	 */
	public function is_payment_element_supported() {
		return is_callable( array( 'GFAPI', 'validate_form' ) );
	}

	/**
	 * Check if the form has an active Stripe feed and a credit card field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::scripts()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function frontend_script_callback( $form ) {

        // Refactored payment methods don't use the old Stripe JS.
        if ( $this->is_refactored_payment_method( $form ) ) {
            return false;
        }

        // If Stripe Checkout is enabled, we don't need to load the Stripe JS.
        if ( $this->is_stripe_checkout_enabled() ) {
            return false;
        }

        // Load JS if form has a Stripe feed and a Stripe card field.
        return $form && $this->has_feed( $form['id'] ) && $this->has_stripe_card_field( $form );
	}

	/**
	 * Check if we should display the Stripe JS.
	 *
	 * @since  2.6
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function stripe_js_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && $this->has_credit_card_field( $form ) && ! $this->has_stripe_card_field( $form );
	}

	/**
	 * Check if we should enqueue Stripe JS v3.
	 *
	 * @since  3.8
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function stripe_js_v3_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && ! wp_script_is( 'stripe.js' );
	}

	/**
	 * Check if the form has an active Stripe feed and Stripe Elements is enabled
	 *
	 * @since 2.6
	 * @since 3.4 Only load frontend styles if the form has a Stripe Card.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool True if the style should be enqueued, false otherwise.
	 */
	public function frontend_style_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && $this->has_stripe_card_field( $form );
	}

	/**
	 * Determines if the card element is enabled for the provided form.
	 *
	 * @since 6.0.0
	 *
	 * @param array $form The current form.
	 *
	 * @return bool Returns true if the current form is configured to use the Stripe Card Element. Returns false otherwise.
	 */
	public function has_stripe_card_element($form ) {

		$cc_field = $this->get_stripe_card_field( $form );
		return $cc_field && ! rgobj( $cc_field, 'enableMultiplePaymentMethods' ) && $this->is_stripe_connect_enabled();
	}

	/**
	 * Determines if the payment method configured for the provided form has been refactored and uses the new submission method and cleaner Stripe API integration.
     * This method is temporary and will be removed after all payment methods have been refactored.
	 *
     * @since 6.0
     *
     * @param array $form The current form.
	 *
	 * @return bool Returns true if the payment method configured in the form has been refactored. Returns false otherwise.
	 */
	public function is_refactored_payment_method( $form ) {
		if( $this->is_stripe_checkout_enabled() ) {
			// Stripe Checkout doesn't need the new scripts.
			return false;
		};

		return $this->has_stripe_card_element( $form );
	}

	/**
	 * Add required Stripe inputs to form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFStripe::get_stripe_js_response()
	 *
	 * @param string  $content The field content to be filtered.
	 * @param object  $field   The field that this input tag applies to.
	 * @param string  $value   The default/initial value that the field should be pre-populated with.
	 * @param integer $lead_id When executed from the entry detail screen, $lead_id will be populated with the Entry ID.
	 * @param integer $form_id The current Form ID.
	 *
	 * @return string $content HTML formatted content.
	 */
	public function add_stripe_inputs( $content, $field, $value, $lead_id, $form_id ) {

		// If this form does not have a Stripe feed or if this is not a credit card field, return field content.
		if ( ! $this->has_feed( $form_id ) || ( $field->get_input_type() !== 'creditcard' && $field->get_input_type() !== 'stripe_creditcard' ) ) {
			return $content;
		}

		// If a Stripe response exists, populate it to a hidden field.
		if ( $this->get_stripe_js_response() ) {
			$content .= '<input type=\'hidden\' name=\'stripe_response\' id=\'gf_stripe_response\' value=\'' . rgpost( 'stripe_response' ) . '\' />';
		}

		// If the last four credit card digits are provided by Stripe, populate it to a hidden field.
		if ( rgpost( 'stripe_credit_card_last_four' ) ) {
			$content .= '<input type="hidden" name="stripe_credit_card_last_four" id="gf_stripe_credit_card_last_four" value="' . esc_attr( rgpost( 'stripe_credit_card_last_four' ) ) . '" />';
		}

		// If the  credit card type is provided by Stripe, populate it to a hidden field.
		if ( rgpost( 'stripe_credit_card_type' ) ) {
			$content .= '<input type="hidden" name="stripe_credit_card_type" id="stripe_credit_card_type" value="' . esc_attr( rgpost( 'stripe_credit_card_type' ) ) . '" />';
		}

		if ( $field->get_input_type() === 'creditcard' && ! $this->has_stripe_card_field( GFAPI::get_form( $form_id ) ) ) {
			// Remove name attribute from credit card field inputs for security.
			// Removes: name='input_2.1', name='input_2.2[]', name='input_2.3', name='input_2.5', where 2 is the credit card field id.
			$content = preg_replace( "/name=\'input_{$field->id}\.([135]|2\[\])\'/", '', $content );
		}

		return $content;

	}

	/**
	 * Validate the card type and prevent the field from failing required validation, Stripe.js will handle the required validation.
	 *
	 * The card field inputs are erased on submit, this will cause two issues:
	 * 1. The field will fail standard validation if marked as required.
	 * 2. The card type validation will not be performed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GF_Field_CreditCard::is_card_supported()
	 * @uses    GFStripe::get_card_slug()
	 *
	 * @param array    $result The field validation result and message.
	 * @param mixed    $value  The field input values; empty for the credit card field as they are cleared by frontend.js.
	 * @param array    $form   The Form currently being processed.
	 * @param GF_Field $field  The field currently being processed.
	 *
	 * @return array $result The results of the validation.
	 */
	public function pre_validation( $result, $value, $form, $field ) {
		$stripe_response = $this->get_stripe_js_response();
		// If this is a credit card field and the last four credit card digits are defined, validate.
		if ( $field->type == 'creditcard' && rgpost( 'stripe_credit_card_last_four' ) ) {

			// Get card type.
			$card_type = rgpost( 'stripe_credit_card_type' );
			if ( ! $card_type || $card_type === 'false' ) {
				$card_type = __( 'Unknown', 'gravityformsstripe' );
			}

			// Get card slug.
			$card_slug = $this->get_card_slug( $card_type );

			// If credit card type is not supported, mark field as invalid.
			if ( $field->type == 'creditcard' && ! $field->is_card_supported( $card_slug ) ) {
				$result['is_valid'] = false;
				$result['message']  = sprintf( esc_html__( 'Card type (%s) is not supported. Please enter one of the supported credit cards.', 'gravityformsstripe' ), $card_type );
			} else {
				$result['is_valid'] = true;
				$result['message']  = '';
			}
		}

		$is_sca_success_response = ! empty( $stripe_response ) && $this->is_payment_intent( $stripe_response->id ) && isset( $stripe_response->scaSuccess ) && $stripe_response->scaSuccess;

		// Modify the validation result if the form was submitted by the SCA handler.
		if ( $is_sca_success_response ) {
			add_filter( 'gform_stripe_sca_success_pre_validation', array( $this, 'modify_sca_success_validation' ), 10, 4 );

			/**
			 * Filter the validation result if the form was submitted by the SCA handler. Can be used to bypass validation for fields that would fail the second validation.
			 *
			 * @since 5.4.0
			 *
			 * @param array $result The validation result.
			 * @param array $value  The field value being validated.
			 * @param array $form   The current form object.
			 * @param array $field  The current field being validated.
			 */
			$result = apply_filters( 'gform_stripe_sca_success_pre_validation', $result, $value, $form, $field );
		}

		return $result;

	}

	/**
	 * Modify the validation result if the form was submitted by the SCA handler.
	 *
	 * @since 5.4.0
	 *
	 * @param array $result The validation result.
	 * @param array $value  The field value being validated.
	 * @param array $form   The current form object.
	 * @param array $field  The current field being validated.
	 *
	 * @return array $result The results of the validation.
	 */
	public function modify_sca_success_validation( $result, $value, $form, $field ) {
		// Spam fields will fail a second validation, but have already been validated once, so we skip them here.
		if ( $field->type === 'captcha' || $field->type === 'turnstile' ) {
			$result['is_valid'] = true;
			$result['message']  = '';
		}

		if ( $field->type === 'stripe_creditcard' && ! $result['is_valid'] ) {
			// When a Stripe card is not on the last page, and the form is submitted by the SCA handler,
			// the field failed because the Card Holder name is wiped out. In this case we assume it's valid.
			$result['is_valid'] = true;
			$result['message']  = '';
		}

		return $result;
	}

	/**
	 * Returns validation error message markup.
	 *
	 * @since 4.1
	 *
	 * @param string $validation_message  The validation message to add to the markup.
	 * @param array  $form                The submitted form data.
	 *
	 * @return false|string
	 */
	private function get_validation_error_markup( $validation_message, $form ) {
		$error_classes = $this->get_validation_error_css_classes( $form );
		ob_start();

		if ( ! $this->is_gravityforms_supported( '2.5' ) ) {
			?>
			<div class="<?php echo esc_attr( $error_classes ); ?>"><?php echo esc_html( $validation_message ); ?></div>
			<?php
			return ob_get_clean();
		}
		?>
		<h2 class="<?php echo esc_attr( $error_classes ); ?>">
			<span class="gform-icon gform-icon--close"></span>
			<?php echo esc_html( $validation_message ); ?>
		</h2>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the CSS classes for the validation markup.
	 *
	 * @since 4.1
	 *
	 * @param array $form The submitted form data.
	 */
	private function get_validation_error_css_classes( $form ) {
		$container_css   = $this->is_gravityforms_supported( '2.5' ) ? 'gform_submission_error' : 'validation_error';
		$field           = $this->get_stripe_card_field( $form );
		$field_error_css = $field ? "field_{$form['id']}_{$field->id}-error" : '';

		if ( rgar( $this->authorization, 'requires_action' ) || rgars( $this->authorization, 'subscription/requires_action' ) ) {
			$container_css .= ' gform_stripe_requires_action';
		}

		return "{$container_css} hide_summary {$field_error_css}";
	}

	/**
	 * Handler for validation messages.
	 *
	 * @since 4.1
	 *
	 * @param string $error_type The type of error.
	 */
	protected function filter_validation_message( $error_type = '' ) {
		switch ( $error_type ) {
			case 'stripe_checkout':
				$callback = array( $this, 'stripe_checkout_error_message' );
				break;
			case 'stripe_elements':
				$callback = array( $this, 'stripe_elements_requires_action_message' );
				break;
			case 'stripe_missing_field':
				$callback = array( $this, 'stripe_missing_field_error_message' );
				break;
			default:
				$callback = array( $this, 'stripe_general_validation_error_message' );
		}

		add_filter( 'gform_validation_message', $callback, 10, 2 );
	}

	/**
	 * Validate if the card type is supported.
	 *
	 * @deprecated 3.0
	 *
	 * @since 2.6.0
	 *
	 * @param array $validation_result The results of the validation.
	 *
	 * @return array $validation_result The results of the validation.
	 */
	public function card_type_validation( $validation_result ) {

		if ( rgpost( 'stripe_credit_card_last_four' ) ) {

			// Get card type.
			$card_type = rgpost( 'stripe_credit_card_type' );
			if ( ! $card_type || 'false' === $card_type ) {
				$card_type = __( 'Unknown', 'gravityformsstripe' );
			}

			// Get card slug.
			$card_slug = $this->get_card_slug( $card_type );

			// Use a filter `gform_stripe_checkout_supported_cards` to set the supported cards.
			// By default (when it's empty), allows all card types Stripe supports.
			// Possible value could be: array( 'amex', 'discover', 'mastercard', 'visa' );.
			$supported_cards = apply_filters( 'gform_stripe_checkout_supported_cards', array() );
			if ( ! empty( $supported_cards ) && ! in_array( $card_slug, $supported_cards, true ) ) {
				$validation_result['is_valid']               = false;
				$validation_result['failed_validation_page'] = GFFormDisplay::get_max_page_number( $validation_result['form'] );

				add_filter( 'gform_validation_message', array( $this, 'card_type_validation_message' ), 10, 2 );

				$this->log_debug( __METHOD__ . '(): The gform_stripe_checkout_supported_cards filter was used; the card type wasn\'t supported.' );

				// empty Stripe response so we can trigger Stripe Checkout modal again.
				$_POST['stripe_response'] = '';
			}
		}

		return $validation_result;
	}

	/**
	 * Display card type validation error message.
	 *
	 * @deprecated 3.0
	 *
	 * @since      2.6.0
	 *
	 * @param string $validation_message The original validation message from the gform_validation_message filter.
	 * @param array  $form               Form object.
	 *
	 * @return string
	 */
	public function card_type_validation_message( $validation_message, $form ) {
		$card_type = rgpost( 'stripe_credit_card_type' );
		if ( ! $card_type || 'false' === $card_type ) {
			$card_type = __( 'Unknown', 'gravityformsstripe' );
		}

		// Translators: 1. Type of credit card.
		$message = sprintf( esc_html__( 'Card type (%1$s) is not supported. Please enter one of the supported credit cards.', 'gravityformsstripe' ), $card_type );

		return $validation_message .= $this->get_validation_error_markup( $message, $form );
	}

	/**
	 * Display card type validation error message.
	 *
	 * @since 2.6.1
	 * @since 3.0   Changed for display a single validation message containing the Checkout error.
	 *
	 * @param string $validation_message The original validation message from the gform_validation_message filter.
	 * @param array  $form               The submitted form data.
	 *
	 * @return string
	 */
	public function stripe_checkout_error_message( $validation_message = '', $form = array() ) {
		return $this->get_validation_error_markup(
			esc_html__( 'There was a problem with your submission.', 'gravityformsstripe' ) . ' ' . $this->get_authorization_error_message(),
			$form
		);
	}

	/**
	 * Display extra authentication (like 3D secure) for Stripe Elements.
	 *
	 * @since 3.3
	 *
	 * @param string $validation_message The original validation message from the gform_validation_message filter.
	 * @param array  $form               The submitted form data.
	 *
	 * @return string
	 */
	public function stripe_elements_requires_action_message( $validation_message = '', $form = array() ) {
		return $this->get_validation_error_markup( $this->get_authorization_error_message(), $form );
	}

	/**
	 * Display missing Stripe field error message.
	 *
	 * @since 5.4
	 *
	 * @param string $validation_message The original validation message from the gform_validation_message filter.
	 * @param array  $form               The submitted form data.
	 *
	 * @return string
	 */
	public function stripe_missing_field_error_message( $validation_message = '', $form = array() ) {
		return $this->get_validation_error_markup(
			esc_html__( 'This form is not configured to collect payment.', 'gravityformsstripe' ),
			$form
		);
	}

	/**
	 * Return the markup for general errors from Stripe.
	 *
	 * Callback to `gform_validation_message`.
	 *
	 * @since 4.1
	 *
	 * @param string $validation_message The validation message.
	 * @param array  $form               The form data.
	 *
	 * @return string
	 */
	public function stripe_general_validation_error_message( $validation_message = '', $form = array() ) {
		$message = $this->get_authorization_error_message();

		if ( ! $message ) {
			return esc_html__( 'There was a problem with your submission. Please try again later.', 'gravityformsstripe' );
		}

		return $this->get_validation_error_markup(
			esc_html__( 'There was a problem with your submission:', 'gravityformsstripe' ) . ' ' . $message,
			$form
		);
	}

	// # STRIPE PAYMENT FLOW -------------------------------------------------------------------------------------------

	/*
	 * For refactored payment methods, bypass the standard Payment Add-On flow.
	 * The authorize(), capture() and subscribe() methods won't get called.
	 *
	 * @since 6.0
	 *
	 * @param array $validation_result The results of the validation.
	 * @param string $context The context in which the validation is being performed.
	 */
	public function validation( $validation_result )
	{
		// Refactored payment methods should bypass the standard Payment Add-On flow. (i.e. authorize(), capture() and subscribe() methods should be bypassed or not called)
		if ( $this->is_refactored_payment_method( $validation_result['form'] ) ) {
            // Set the payment gateway to this add-on. We are bypassing the payment add-on flow, so we need to set this manually.
            $this->try_set_payment_gateway( $validation_result );
            return $validation_result;
		}

        // Standard Payment Add-On flow.
        return parent::validation( $validation_result );
	}

	/**
	 * For refactored payment methods, bypass the standard Payment Add-On flow.
	 * The authorize(), capture() and subscribe() methods won't get called.
	 * Save the payment gateway in the entry metadata.
	 *
	 * @since  6.0
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array $entry The entry object currently being processed.
	 */
	public function entry_post_save( $entry, $form ) {

		// Refactored payment methods should bypass the standard Payment Add-On flow. (i.e. authorize(), capture() and subscribe() methods should be bypassed or not called)
		if ( $this->is_refactored_payment_method( $form ) ) {

			// Saving which gateway was used to process this entry. Since we are bypassing the payment add-on flow, we need to set this manually.
			if ( $this->is_payment_gateway ) {
				gform_update_meta( $entry['id'], 'payment_gateway', $this->get_slug() );
			}
			return $entry;
		}

		// Standard Payment Add-On flow.
		return parent::entry_post_save( $entry, $form );
	}

	/**
     * This method sets the $this->is_payment_gateway property to true if the current submission should be processed by this add-on.
     * This is a copy of the first part of the validation() method of the Payment Add-On. We will look for ways to refactor this in the future.
     *
     * @since  6.0
     *
	 * @param array $validation_result The validation result parameter passed to the validation() method.
	 * @return void
	 */
    private function try_set_payment_gateway( $validation_result ) {

        // If the validation result is not valid, abort.
	    if ( ! $validation_result['is_valid'] ) {
		    return;
	    }

        // If the payment gateway is already set by another addon, abort.
	    global $gf_payment_gateway;
	    if ( $gf_payment_gateway && $gf_payment_gateway !== $this->get_slug() ) {
		    $this->log_debug(__METHOD__ . '() Aborting. Submission already processed by ' . $gf_payment_gateway);
		    return;
	    }

        // If there aren't any feeds to be processed by this add-on, abort.
	    $form  = $validation_result['form'];
	    $entry = GFFormsModel::get_current_lead( $form );
	    $feed  = $this->get_payment_feed( $entry, $form );
	    if ( ! $feed ) {
		    return;
	    }

        // If the payment amount is not valid, abort.
	    $submission_data = $this->get_submission_data( $feed, $form, $entry );
	    if ( ! $this->is_valid_payment_amount( $submission_data, $feed, $form, $entry ) ) {
		    $this->log_debug( __METHOD__ . '(): Aborting. Payment amount not valid for processing.' );
		    return;
	    }

        // If all the checks passed, set the payment gateway to this add-on.
	    $gf_payment_gateway       = $this->get_slug();
	    $this->is_payment_gateway = true;
    }
	//------------------------------------------------------------------------------------------------------------------

	// # STRIPE TRANSACTIONS -------------------------------------------------------------------------------------------
	/**
	 * Initialize authorizing the transaction for the product & services type feed or return the Stripe.js error.
	 *
	 * @since  Unknown
	 * @since  2.8     Added $feed param when include Stripe API.
	 * @since  3.4     Added card error rate limits.
	 * @access public
	 *
	 * @uses GFStripe::include_stripe_api()
	 * @uses GFStripe::get_stripe_js_error()
	 * @uses GFStripe::authorization_error()
	 * @uses GFStripe::authorize_product()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array Authorization and transaction ID if authorized. Otherwise, exception.
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {
		// Check if the current IP has hit the error rate limit.
		if ( $hit_rate_limits = $this->maybe_hit_rate_limits( $form['id'] ) ) {
			return $hit_rate_limits;
		}

		// Include Stripe API library.
		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		// If Payment element is enabled, then this is called by submitting a form with 0 total or a hidden stripe field.
		if ( $this->is_payment_element_enabled( $form ) ) {
			$this->log_debug( __METHOD__ . '(): Authorizing product for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id:' . rgget( 'tracking_id' ) );
			return $this->authorize_product( $feed, $submission_data, $form, $entry );
		}

		// If there was an error when retrieving the Stripe.js token, return an authorization error.
		if ( $this->get_stripe_js_error() ) {
			return $this->authorization_error( $this->get_stripe_js_error() );
		}

		if ( $this->is_stripe_checkout_enabled() ) {
			// Create checkout session.
			return $this->create_checkout_session( $feed, $submission_data, $form, $entry );
		} else {
			// Authorize product.
			return $this->authorize_product( $feed, $submission_data, $form, $entry );
		}
	}

	/**
	 * Create the Stripe charge authorization and return any authorization errors which occur.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize()
	 * @uses    GFStripe::get_stripe_js_response()
	 * @uses    GFPaymentAddOn::get_amount_export()
	 * @uses    GFStripe::get_payment_description()
	 * @uses    GFStripe::get_customer()
	 * @uses    GFAddOn::get_field_value()
	 * @uses    GFStripe::get_stripe_meta_data()
	 * @uses    GFAddOn::log_debug()
	 * @uses    GFPaymentAddOn::authorization_error()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array Authorization and transaction ID if authorized. Otherwise, exception.
	 */
	public function authorize_product( $feed, $submission_data, $form, $entry ) {
		$data = array();
		// Get customer.
		$customer = $this->get_customer( '', $feed, $entry, $form );
		if ( $customer ) {
			$data['customer'] = $customer->id;
		}
		// If receipt field is defined, add receipt email address to charge meta.
		$receipt_field = rgars( $feed, 'meta/receipt_field' );
		if ( ! empty( $receipt_field ) && strtolower( $receipt_field ) !== 'do not send receipt' ) {
			$data['receipt_email'] = $this->get_field_value( $form, $entry, $receipt_field );
		}
		$payment_element_enabled = $this->is_payment_element_enabled( $form );
		// check if stripe_response has a payment id.
		// if yes, confirm the payment here.
		if ( $payment_element_enabled ) {
			$intent_id       = $this->get_payment_element_handler()->get_stripe_payment_object_id();
			$stripe_response = $this->api->get_payment_intent( $intent_id );
		} else {
			$stripe_response = $this->get_stripe_js_response();
			if ( ! $stripe_response ) {
				$stripe_response = new WP_Error( 'get_stripe_js_response', 'get_stripe_js_response() = null' );
			}
		}

		if ( is_wp_error( $stripe_response ) ) {
			$this->log_error( __METHOD__ . '(): Could not retrieve payment intent.' );

			return $this->authorization_error( esc_html__( 'Your payment attempt has failed. Please enter your card details and try again.', 'gravityformsstripe' ) );
		}

		if ( $payment_element_enabled || $this->is_payment_intent( $stripe_response->id ) ) {
			$result = $this->api->get_payment_intent( $stripe_response->id );
			if ( ! is_wp_error( $result ) ) {
				$currency        = rgar( $entry, 'currency' );
				$expected_amount = $this->get_amount_export( $submission_data['payment_amount'], $currency );

				if ( $result->amount !== $expected_amount ) {
					$this->log_debug( __METHOD__ . sprintf( '(): Amount mismatch. PaymentIntent: %s. Expected: %s. Cancelling PaymentIntent, tracking id: %s', GFCommon::to_money( $this->get_amount_import( $result->amount, $currency ), $currency ), GFCommon::to_money( $submission_data['payment_amount'], $currency ), rgget( 'tracking_id' ) ) );

					$result = $this->api->cancel_payment_intent( $result->id, 'fraudulent' );
					if ( is_wp_error( $result ) ) {
						$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );
					}

					// Clear the response so a new PI will be created on the next submission attempt.
					$_POST['stripe_response'] = '';

					return $this->authorization_error( esc_html__( 'Your payment attempt has failed. Please enter your card details and try again.', 'gravityformsstripe' ) );
				}

				// Add customer, receipt_email and metadata.
				// Change data before sending to Stripe.
				$data = $this->get_product_payment_data( $data, $feed, $submission_data, $form, $entry );
				// Compare the properties of the intent and the properties retrieved from the filter.
				// If they are the same, then no need to update.
				$intentArray = $result->toArray();
				foreach ( $data as $key => $value ) {
					if ( rgar( $intentArray, $key ) === $value ) {
						// Remove equal properties.
						unset( $data[ $key ] );
					}
				}
				// Update only if we have properties that are different from the intent's properties.
				if ( ! empty( $data ) ) {
					$this->log_debug( __METHOD__ . '(): payment intent data to be updated => ' . print_r( $data, true ) );
					$result = $this->api->update_payment_intent( $result->id, $data );
				}

				if ( ! is_wp_error( $result ) ) {
					if ( $result->status === 'requires_confirmation' ) {
						// Confirm first, and the status will become `requires_action` if the card requires authentication.
						$result = $this->api->confirm_payment_intent( $result );
					}
				}
			}

			if ( is_wp_error( $result ) ) {
				$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );

				return $this->authorization_error( $result->get_error_message() );
			}

			// if status = requires_action, return validation error so we can do dynamic authentication on the front end.
			if ( ! $payment_element_enabled && $result->status === 'requires_action' ) {
				$error = $this->authorization_error( esc_html__( 'This payment might require 3D Secure authentication, please wait while we communicate with Stripe.', 'gravityformsstripe' ) );

				return array_merge( $error, array( 'requires_action' => true ) );

			} elseif ( $result->status === 'requires_payment_method' ) {
				return $this->authorization_error( esc_html__( 'Your payment attempt has failed. Please enter your card details and try again.', 'gravityformsstripe' ) );
			} elseif ( $result->status === 'canceled' ) {
				return $this->authorization_error( esc_html__( 'The payment has been canceled', 'gravityformsstripe' ) );
			} else {
				$this->log_debug( __METHOD__ . '(): PaymentIntent status: ' . $result->status . ', tracking id:' . rgget( 'tracking_id' ) );
				return array(
					'is_authorized'  => true,
					'transaction_id' => $stripe_response->id,
				);
			}
		}

		// Prepare Stripe charge meta.
		$charge_meta = array(
			'amount'      => $this->get_amount_export( $submission_data['payment_amount'], rgar( $entry, 'currency' ) ),
			'currency'    => rgar( $entry, 'currency' ),
			'description' => $this->get_payment_description( $entry, $submission_data, $feed ),
			'capture'     => false,
		);

		if ( $customer ) {
			// Update the customer source with the Stripe token.
			$customer->source = $stripe_response->id;
			$this->api->save_customer( $customer );
		} else {
			// Add the Stripe token to the charge meta.
			$charge_meta['source'] = $stripe_response->id;
		}

		// merge $data with $charge_meta.
		$charge_meta = array_merge( $charge_meta, $data );
		$charge_meta = $this->get_product_payment_data( $charge_meta, $feed, $submission_data, $form, $entry );
		// Log the charge we're about to process.
		$this->log_debug( __METHOD__ . '(): Charge meta to be created => ' . print_r( $charge_meta, true ) );

		// Charge customer.
		$charge = $this->api->create_charge( $charge_meta );
		if ( is_wp_error( $charge ) ) {
			$this->log_error( __METHOD__ . '(): ' . $charge->get_error_message() );

			$auth = $this->authorization_error( $charge->get_error_message() );
		} else {
			// Get authorization data from charge.
			$auth = array(
				'is_authorized'  => true,
				'transaction_id' => $charge['id'],
			);
		}

		return $auth;

	}

	/**
	 * Update the rate limits when errors thrown.
	 *
	 * We will add rate limits to the payment add-on framework as what we did here. Once we've done that, this method will be removed.
	 *
	 * @since 3.4
	 *
	 * @param string $error_message The error message.
	 *
	 * @return array
	 */
	public function authorization_error( $error_message ) {
		if ( $this->_enable_rate_limits ) {
			$this->get_card_error_count( true );
		}

		return array( 'error_message' => $error_message, 'is_success' => false, 'is_authorized' => false );
	}

	/**
	 * Get the authorization error message from the authorization array.
	 *
	 * @since 4.1
	 *
	 * @return string
	 */
	protected function get_authorization_error_message() {
		return rgar( $this->authorization, 'error_message', '' );
	}

	/**
	 * Create Stripe Checkout Session.
	 *
	 * @since 3.0
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array|boolean
	 */
	public function create_checkout_session( $feed, $submission_data, $form, $entry ) {

		/**
		 * Allow manually setting the payment methods used by Stripe Checkout.
		 *
		 * @since  5.0
		 *
		 * @param array $payment_methods An array of payment methods to be used.
		 * @param array $feed            The feed currently being processed.
		 * @param array $form            The form which created the current entry.
		 */
		$payment_methods = apply_filters( 'gform_stripe_checkout_payment_methods', array(), $feed, $form );

		$is_subscription = $feed['meta']['transactionType'] === 'subscription';
		$session_data    = array(
			'success_url'                => $this->get_success_url( $form['id'], $feed['id'] ),
			'cancel_url'                 => $this->get_cancel_url( $form['id'] ),
			'billing_address_collection' => ( boolval( rgars( $feed, 'meta/billingAddress' ) ) === true ) ? 'required' : 'auto',
		);

		if ( ! empty( $payment_methods ) ) {
			$session_data['payment_method_types'] = $payment_methods;
		}

		// Get customer ID.
		// Note here we cannot run create_customer() yet because the payment is not authorized yet.
		// Stripe Checkout will create the customer for us and we run `gform_stripe_customer_after_create` filter then.
		$customer = $this->get_customer( '', $feed, $entry, $form );
		if ( $customer ) {
			$session_data['customer'] = rgar( $customer, 'id' );
		}

		// Use the payment amount from submission data.
		$payment_amount = rgar( $submission_data, 'payment_amount' );

		if ( ! $is_subscription ) {
			$session_data['mode']              = 'payment';
			$session_data['customer_creation'] = 'always';

			// add discounts from the GF Coupon add-on.
			// Stripe Checkout cannot display our coupon as a negative value,
			// so we couldn't display the line items when an order contains coupons.
			$discounts    = rgar( $submission_data, 'discounts' );
			$discount_amt = 0;
			if ( is_array( $discounts ) ) {
				foreach ( $discounts as $discount ) {
					$discount_full = abs( $discount['unit_price'] ) * $discount['quantity'];
					$discount_amt += $discount_full;
				}
			}
			if ( $discount_amt > 0 ) {
				/**
				 * Filter line item name when there's coupon discounts apply to the payment.
				 *
				 * @since 3.0.3
				 *
				 * @param string $name            The line item name of the discounted order.
				 * @param array  $feed            The feed object currently being processed.
				 * @param array  $submission_data The customer and transaction data.
				 * @param array  $form            The form object currently being processed.
				 * @param array  $entry           The entry object currently being processed.
				 */
				$line_items_name = apply_filters( 'gform_stripe_discounted_line_items_name', esc_html__( 'Payment with Discounts', 'gravityformsstripe' ), $feed, $submission_data, $form, $entry );

				$session_data['line_items'][] = array(
					'quantity'   => 1,
					'price_data' => array(
						'unit_amount'  => $this->get_amount_export( $payment_amount, rgar( $entry, 'currency' ) ),
						'currency'     => $entry['currency'],
						'product_data' => array(
							'name'        => $line_items_name,
							'description' => $this->get_payment_description( $entry, $submission_data, $feed ),
						),
					),
				);
			} else {
				foreach ( $submission_data['line_items'] as $line_item ) {
					$unit_price = $line_item['unit_price'];

					// Stripe Checkout doesn't allow 0 or negative price.
					if ( $unit_price > 0 ) {
						$data = array(
							'quantity'   => $line_item['quantity'],
							'price_data' => array(
								'unit_amount'  => $this->get_amount_export( $unit_price, rgar( $entry, 'currency' ) ),
								'currency'     => $entry['currency'],
								'product_data' => array(
									'name' => $line_item['name'],
								),
							),
						);

						if ( ! empty( $line_item['description'] ) ) {
							$data['price_data']['product_data']['description'] = $line_item['description'];
						}

						$session_data['line_items'][] = $data;
					}
				}
			}

			// Set capture method.
			$session_data['payment_intent_data']['capture_method'] = $this->get_capture_method( $feed, $submission_data, $form, $entry );

			// Add description.
			$session_data['payment_intent_data']['description'] = $this->get_payment_description( $entry, $submission_data, $feed );

			// If receipt field is defined, add receipt email address to charge meta.
			$receipt_field = rgars( $feed, 'meta/receipt_field' );
			if ( ! empty( $receipt_field ) && strtolower( $receipt_field ) !== 'do not send receipt' ) {
				$session_data['payment_intent_data']['receipt_email'] = $this->get_field_value( $form, $entry, $receipt_field );
			}

			// Set customer email.
			$customer_email_field = rgars( $feed, 'meta/customer_email_field' );
			if ( ! empty( $customer_email_field ) && strtolower( $customer_email_field ) !== 'do not set customer email' ) {
				$session_data['customer_email'] = $this->get_field_value( $form, $entry, $customer_email_field );
			}
		} else {
			$session_data['mode'] = 'subscription';
			// Prepare payment amount and trial period data.
			$single_payment_amount = $submission_data['setup_fee'];
			$trial_period_days     = rgars( $feed, 'meta/trialPeriod' ) ? $submission_data['trial'] : 0;
			$currency              = rgar( $entry, 'currency' );

			if ( $single_payment_amount ) {
				// Create invoice line items for setup fee.
				$line_items                 = array(
					array(
						'quantity'   => 1,
						'price_data' => array(
							'unit_amount'  => $this->get_amount_export( $single_payment_amount, $currency ),
							'currency'     => $currency,
							'product_data' => array(
								'name' => esc_html__( 'Setup Fee', 'gravityformsstripe' ),
							),
						),
					),
				);
				$session_data['line_items'] = $line_items;
			}

			// Checkout Session does not support setting trial period days at the plan level. So we create plans in
			// 0 trial days, and then set it in the subscription data.

			// Process merge tags in the subscription name.
			$feed['meta']['processed_subscription_name'] = GFCommon::replace_variables( rgars( $feed, 'meta/subscription_name' ), $form, $entry, false, true, true, 'text' );
			// Get Stripe plan that matches current feed.
			$plan = $this->get_plan_for_feed( $feed, $payment_amount, null, $currency );

			// If error was returned when retrieving plan, return authorization error array.
			if ( rgar( $plan, 'error_message' ) ) {
				return $plan;
			}

			$items = array(
				'quantity' => 1,
				'price'    => $plan->id,
			);

			$session_data['line_items'][] = $items;
			if ( $trial_period_days ) {
				$session_data['subscription_data']['trial_period_days'] = $trial_period_days;
			}

			$customer_email = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_email' ) );
			if ( is_email( $customer_email ) ) {
				$session_data['customer_email'] = $customer_email;
			}
		}

		/**
		 * Filter session data before creating the session. Can be used to add product images.
		 *
		 * @since 3.0
		 *
		 * @param array $session_data    Session data for creating the session.
		 * @param array $feed            The feed object currently being processed.
		 * @param array $submission_data The customer and transaction data.
		 * @param array $form            The form object currently being processed.
		 * @param array $entry           The entry object currently being processed.
		 */
		$session_data = apply_filters( 'gform_stripe_session_data', $session_data, $feed, $submission_data, $form, $entry );

		// Remove 'customer_email' if 'customer' is defined to prevent a session creation failure.
		if ( isset( $session_data['customer'] ) && isset( $session_data['customer_email'] ) ) {
			$this->log_debug( __METHOD__ . '(): customer is defined; removing incompatible customer_email property.' );
			unset( $session_data['customer_email'] );
		}

		// Remove 'customer_creation' if 'customer' is defined to prevent a session creation failure.
		if ( isset( $session_data['customer'] ) && isset( $session_data['customer_creation'] ) ) {
			$this->log_debug( __METHOD__ . '(): customer is defined; removing incompatible customer_creation property.' );
			unset( $session_data['customer_creation'] );
		}

		$this->log_debug( __METHOD__ . '(): Checkout Session to be created for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ') => ' . print_r( $session_data, true ) );

		$session = $this->api->create_checkout_session( $session_data );
		if ( ! is_wp_error( $session ) ) {
			$session_id = rgar( $session, 'id' );

			if ( ! $is_subscription ) {
				return array(
					'is_authorized'  => true,
					'transaction_id' => rgar( $session, 'payment_intent' ), // Store payment_intent as transaction_id.
					'session_id'     => $session_id,
				);
			} else {
				return array(
					'is_success'      => true,
					'subscription_id' => '',
					'customer_id'     => '',
					'amount'          => $payment_amount,
					'session_id'      => $session_id,
				);
			}
		}

		$this->log_error( __METHOD__ . '(): Unable to create Stripe Checkout session; ' . $session->get_error_message() );

		return $this->authorization_error( esc_html__( 'Unable to create Stripe Checkout session.', 'gravityformsstripe' ) );
	}

	/**
	 * Complete authorization (mark entry as authorized and create note).
	 *
	 * @since 3.0
	 *
	 * @param array $entry  Entry data.
	 * @param array $action Authorization data.
	 *
	 * @return bool
	 */
	public function complete_authorization( &$entry, $action ) {
		if ( rgar( $action, 'session_id' ) ) {
			// Do not complete authorization at this stage since users haven't paid.
			$this->log_debug( __METHOD__ . '(): Stripe session will be created, but the payment hasn\'t been authorized yet. Mark it as processing.' );
			GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Processing' );

			return true;
		}

		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_feed( $this->get_feeds_by_entry( $entry['id'] )[0] );

		if ( $this->is_payment_element_enabled( $form ) && ( $this->get_payment_element_capture_method( $form, $feed ) !== 'manual' ) || $this->get_payment_element_handler()->get_subscription_id() ) {
			GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Processing' );
			GFAPI::update_entry_property( $entry['id'], 'transaction_id', $this->get_payment_element_handler()->get_subscription_id() );

			gform_update_meta( $entry['id'], 'payment_element_intent_id', $this->get_payment_element_handler()->get_stripe_payment_object_id() );
			gform_update_meta( $entry['id'], 'payment_element_feed_id', $this->get_payment_element_handler()->get_feed_id() );
			gform_update_meta( $entry['id'], 'payment_element_draft_id', $this->get_payment_element_handler()->get_draft_id() );
			gform_update_meta( $entry['id'], 'payment_element_subscription_id', $this->get_payment_element_handler()->get_subscription_id() );

			$action['payment_status'] = 'Processing';
			$action['type'] = 'add_pending_payment';
			$this->post_payment_action( $entry, $action );
			return true;
		}

		if ( empty( rgar( $action, 'captured_payment' ) ) && rgar( $action, 'is_authorized' ) ) {
			$action['type'] = 'authorized_payment';
		}

		return parent::complete_authorization( $entry, $action );
	}

	/**
	 * Handle cancelling the subscription from the entry detail page.
	 *
	 * @since Unknown
	 * @since 2.8     Updated to use the subscription object instead of the customer object. Added $feed param when including Stripe API.
	 *
	 * @param array $entry The entry object currently being processed.
	 * @param array $feed  The feed object currently being processed.
	 *
	 * @return bool True if successful. False if failed.
	 */
	public function cancel( $entry, $feed ) {

		// Include Stripe API library.
		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		if ( empty( $entry['transaction_id'] ) ) {
			return false;
		}

		// Get Stripe subscription object.
		$subscription = $this->api->get_subscription( $entry['transaction_id'] );

		if ( is_wp_error( $subscription ) ) {
			return false;
		}

		if ( $subscription->status === 'canceled' ) {
			$this->log_debug( __METHOD__ . '(): Subscription already cancelled.' );

			return true;
		}

		/**
		 * Allow the cancellation of the subscription to be delayed until the end of the current period.
		 *
		 * @since 2.1.0
		 *
		 * @param bool  $at_period_end Defaults to false, subscription will be cancelled immediately.
		 * @param array $entry         The entry from which the subscription was created.
		 * @param array $feed          The feed object which processed the current entry.
		 */
		$at_period_end = apply_filters( 'gform_stripe_subscription_cancel_at_period_end', false, $entry, $feed );
		$action        = $at_period_end ? 'update' : 'cancel';

		if ( $at_period_end ) {
			$this->log_debug( __METHOD__ . '(): The gform_stripe_subscription_cancel_at_period_end filter was used; cancelling subscription at period end.' );
			$subscription->cancel_at_period_end = true;

			$result = $this->api->save_subscription( $subscription );
			$this->log_debug( __METHOD__ . '(): Subscription updated.' );
		} else {
			$result = $this->api->cancel_subscription( $subscription );
			$this->log_debug( __METHOD__ . '(): Subscription cancelled.' );
		}

		if ( is_wp_error( $result ) ) {
			$this->log_error( sprintf( '%s(): Unable to %s subscription; %s', __METHOD__, $action, $result->get_error_message() ) );

			return false;
		} else {
			$this->log_debug( sprintf( '%s(): Successfully %s the subscription.', __METHOD__, $action ) );
		}

		return true;
	}

	/**
	 * Gets the payment validation result.
	 *
	 * @since  3.0 Remove validation message for Stripe Checkout.
	 * @since  2.6
	 *
	 * @param array $validation_result    Contains the form validation results.
	 * @param array $authorization_result Contains the form authorization results.
	 *
	 * @return array The validation result for the credit card field.
	 */
	public function get_validation_result( $validation_result, $authorization_result ) {
		if ( empty( $authorization_result['error_message'] ) ) {
			return parent::get_validation_result( $validation_result, $authorization_result );
		}

		$field_lookup_result = array_filter(
			rgars( $validation_result, 'form/fields' ),
			function( $field ) {
				return in_array( $field->type, array( 'creditcard', 'stripe_creditcard' ), true );
			}
		);

		if ( $this->is_stripe_checkout_enabled() ) {
			return $this->get_stripe_checkout_validation_result( $validation_result );
		} elseif ( empty( $field_lookup_result ) ) {
			return $this->get_stripe_missing_credit_card_field_validation_result( $validation_result );
		} else {
			$field_index       = $this->get_first_array_key( $field_lookup_result );
			$credit_card_field = $field_lookup_result[ $field_index ];

			// If payment element is enabled, then the payment was validated in the front end.
			if ( rgobj( $credit_card_field, 'enableMultiplePaymentMethods' ) ) {
				$validation_result['is_valid'] = true;
				return $validation_result;
			}

			return $this->get_credit_card_field_validation_result(
				$credit_card_field,
				$field_index,
				$validation_result
			);
		}
	}


	/**
	 * Gets the first key from an array.
	 *
	 * The reset method updates the pointer to the first element, and key fetches the index element in the first position.
	 *
	 * Once PHP 7+ is the minimum version, we can replace any calls of this method with array_key_first.
	 *
	 * @see https://www.php.net/manual/en/function.reset.php
	 *
	 * @param array $array The array we'd like the first key from.
	 *
	 * @return int|string|null
	 */
	private function get_first_array_key( array $array ) {
		reset( $array );

		return key( $array );
	}

	/**
	 * Updates the validation result with the changes made to the field following validation.
	 *
	 * @param GF_Field   $field             The modified field.
	 * @param string|int $field_index       The array index of the field within the form array.
	 * @param array      $validation_result The result of the validation.
	 *
	 * @return array
	 */
	private function apply_field_validation_changes_to_result( $field, $field_index, $validation_result ) {
		$validation_result['form']['fields'][ $field_index ] = $field;

		return $validation_result;
	}

	/**
	 * Determines whether the authorization requires action.
	 *
	 * @since 4.1
	 *
	 * @param GF_Field_Stripe_CreditCard $field                The Stripe credit card field.
	 * @param array                      $authorization_result The authorization result.
	 *
	 * @return bool
	 */
	private function stripe_authorization_requires_action( $field, $authorization_result ) {
		return (
			$field->type === 'stripe_creditcard'
			&& ( rgar( $authorization_result, 'requires_action' ) || rgars( $authorization_result, 'subscription/requires_action' ) )
		);
	}

	/**
	 * Get the validation result for Stripe Checkout.
	 *
	 * @since 4.1
	 *
	 * @see   GFStripe::get_validation_result()
	 *
	 * @param array $validation_result The validation result data.
	 *
	 * @return array
	 */
	private function get_stripe_checkout_validation_result( $validation_result ) {
		$this->filter_validation_message( 'stripe_checkout' );

		return array_merge(
			$validation_result,
			array(
				'is_valid'         => false,
				'credit_card_page' => GFFormDisplay::get_max_page_number( $validation_result['form'] ),
			)
		);
	}

	/**
	 * Get the validation result when the stripe field is missing from the form.
	 *
	 * @since 5.4
	 *
	 * @see   GFStripe::get_validation_result()
	 *
	 * @param array $validation_result The validation result data.
	 *
	 * @return array
	 */
	private function get_stripe_missing_credit_card_field_validation_result( $validation_result ) {
		$this->filter_validation_message( 'stripe_missing_field' );

		return array_merge(
			$validation_result,
			array(
				'is_valid'         => false,
				'credit_card_page' => GFFormDisplay::get_max_page_number( $validation_result['form'] ),
			)
		);
	}

	/**
	 * Get the validation result when the validation is for a credit card field for Stripe.
	 *
	 * @since 4.1
	 *
	 * @see   GFStripe::get_validation_result()
	 *
	 * @param GF_Field   $credit_card_field The credit card field.
	 * @param string|int $field_index       The index of the field in the form array.
	 * @param array      $validation_result The validation result.
	 *
	 * @return array
	 */
	private function get_credit_card_field_validation_result( $credit_card_field, $field_index, $validation_result ) {
		if ( $this->stripe_authorization_requires_action( $credit_card_field, $this->authorization ) ) {
			return $this->get_credit_card_requires_action_result( $credit_card_field, $field_index, $validation_result );
		}

		$credit_card_field->failed_validation  = true;
		$credit_card_field->validation_message = $this->get_authorization_error_message();
		$credit_card_page                      = $credit_card_field->pageNumber;

		$this->filter_validation_message();

		return array_merge(
			$this->apply_field_validation_changes_to_result( $credit_card_field, $field_index, $validation_result ),
			array(
				'is_valid'         => false,
				'credit_card_page' => $credit_card_page,
			)
		);
	}

	/**
	 * Gets the validation result when Stripe authorization requires action.
	 *
	 * @since 4.1
	 *
	 * @param GF_Field_Stripe_CreditCard $credit_card_field The Stripe credit card field.
	 * @param string|int                 $field_index       The index of the field in the form array.
	 * @param array                      $validation_result The validation result.
	 *
	 * @return array
	 */
	private function get_credit_card_requires_action_result( $credit_card_field, $field_index, $validation_result ) {
		$credit_card_page = ( GFCommon::has_pages( rgar( $validation_result, 'form' ) ) )
			? GFFormDisplay::get_max_page_number( $validation_result['form'] )
			: $credit_card_field->pageNumber;

		// Add SCA requires extra action message.
		$this->filter_validation_message( 'stripe_elements' );

		return array_merge(
			$this->apply_field_validation_changes_to_result( $credit_card_field, $field_index, $validation_result ),
			array(
				'is_valid'         => false,
				'credit_card_page' => $credit_card_page,
			)
		);
	}

	/**
	 * Capture the Stripe charge which was authorized during validation.
	 *
	 * @since  Unknown
	 * @since  3.4     Added card error rate limits.
	 * @access public
	 *
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFStripe::get_payment_description()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_amount_import()
	 * @uses Exception::getMessage()
	 *
	 * @param array $auth            Contains the result of the authorize() function.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array $payment Contains payment details. If failed, shows failure message.
	 */
	public function capture( $auth, $feed, $submission_data, $form, $entry ) {
		// Check if the current IP has hit the error rate limit.
		if ( $hit_rate_limits = $this->maybe_hit_rate_limits( $form['id'] ) ) {
			return $hit_rate_limits;
		}

		if ( $this->is_stripe_checkout_enabled() ) {
			gform_update_meta( $entry['id'], 'stripe_session_id', $auth['session_id'] );
			// Cache the submission data so we can use it in complete_checkout_session().
			gform_update_meta( $entry['id'], 'submission_data', $submission_data );

			// return empty details for the new Stripe Checkout.
			return array();
		}

		$payment_element_enabled = $this->is_payment_element_enabled( $form );
		// check if stripe_response has a payment id.
		// if yes, confirm the payment here.
		if ( $payment_element_enabled ) {
			$intent_id = $this->get_payment_element_handler()->get_stripe_payment_object_id();
			$response  = $this->api->get_payment_intent( $intent_id );
		} else {
			$response = $this->get_stripe_js_response();
			if ( ! $response ) {
				$response = new WP_Error( 'get_stripe_js_response', 'get_stripe_js_response() = null' );
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): There is no Stripe response from which to get a payment intent.' );

			return array(
				'is_success'    => false,
				'error_message' => esc_html__( 'Cannot get payment intent.', 'gravityformsstripe' ),
			);
		}

		if ( $payment_element_enabled || $this->is_payment_intent( $response->id ) ) {
			$intent = $this->api->get_payment_intent( $response->id );

			if ( is_wp_error( $intent ) ) {
				$this->log_error( __METHOD__ . '(): Cannot get payment intent data; ' . $intent->get_error_message() . ', tracking id:' . rgget( 'tracking_id' ) );

				return array(
					'is_success'    => false,
					'error_message' => esc_html__( 'Cannot get payment intent data.', 'gravityformsstripe' ),
				);
			}

			// Add entry ID to payment description.
			$intent->description = $this->get_payment_description( $entry, $submission_data, $feed );
			$metadata            = $this->get_stripe_meta_data( $feed, $entry, $form );
			if ( ! empty( $metadata ) ) {
				$intent->metadata = $metadata;
			}

			// Save payment intent.
			$intent = $this->api->save_payment_intent( $intent );
			if ( is_wp_error( $intent ) ) {
				$this->log_error( __METHOD__ . '(): Cannot update payment intent data; ' . $intent->get_error_message() );

				return array(
					'is_success'    => false,
					'error_message' => esc_html__( 'Cannot update payment intent data.', 'gravityformsstripe' ),
				);
			}

			if ( $payment_element_enabled && $intent->status === 'processing' ) {

				$this->log_debug( __METHOD__ . '(): Can not capture payment while it is still in pending status, payment will be captured via webhook, aborting tracking id: ' . rgget( 'tracking_id' ) );

				// Mark the payment status as Pending.
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Pending' );
				GFAPI::update_entry_property( $entry['id'], 'payment_method', 'stripe_payment_element' );

				return array();
			}

			// Get the capture method.
			$capture_method = $payment_element_enabled ? $this->get_payment_element_capture_method( $form, $feed ) : $this->get_capture_method( $feed, $submission_data, $form, $entry );

			// Return empty array if capture method is manual.
			// An empty array will leave the payment status as authorized only.
			if ( $capture_method === 'manual' ) {
				return array();
			}

			// At this point, if the payment element is active, the intent has already been captured.
			// Attempt capturing the payment only if the payment element is not enabled.
			if ( $payment_element_enabled === false ) {
				// Capturing payment intent.
				$intent = $this->api->capture_payment_intent( $intent );

				if ( is_wp_error( $intent ) ) {
					$this->log_error( __METHOD__ . '(): Cannot capture payment intent data; ' . $intent->get_error_message() );

					return array(
						'is_success'    => false,
						'error_message' => esc_html__( 'Cannot capture payment intent data.', 'gravityformsstripe' ),
					);
				}
			}

			if ( $intent->status !== 'succeeded' ) {
				$payment = $this->authorization_error( esc_html__( 'Cannot capture the payment; the payment intent status is ', 'gravityformsstripe' ) . $intent->status );
			} else {
				$payment = array(
					'is_success'     => true,
					'transaction_id' => $response->id,
					'amount'         => $this->get_amount_import( $intent->amount, $entry['currency'] ),
					'payment_method' => $payment_element_enabled ? $intent->payment_method : rgpost( 'stripe_credit_card_type' ),
				);
			}
		} else {
			// Get Stripe charge from authorization.
			$charge = $this->api->get_charge( $auth['transaction_id'] );
			if ( is_wp_error( $charge ) ) {
				$this->log_error( __METHOD__ . '(): Unable to capture the charge; ' . $charge->get_error_message() );

				return array(
					'is_success'    => false,
					'error_message' => esc_html__( 'Unable to capture the charge.', 'gravityformsstripe' ),
				);
			}

			// Set charge description and metadata.
			$charge->description = $this->get_payment_description( $entry, $submission_data, $feed );

			$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );
			if ( ! empty( $metadata ) ) {
				$charge->metadata = $metadata;
			}

			// Save charge.
			$charge = $this->api->save_charge( $charge );
			if ( is_wp_error( $charge ) ) {
				$this->log_error( __METHOD__ . '(): Unable to save the charge; ' . $charge->get_error_message() );

				return array(
					'is_success'    => false,
					'error_message' => esc_html__( 'Unable to save the charge.', 'gravityformsstripe' ),
				);
			}

			// Capture the payment if the filter not used.
			if ( $this->get_capture_method( $feed, $submission_data, $form, $entry ) === 'manual' ) {
				return array();
			}

			// Capture the charge.
			$charge = $this->api->capture_charge( $charge );
			if ( is_wp_error( $charge ) ) {
				$this->log_error( __METHOD__ . '(): Unable to capture the charge; ' . $charge->get_error_message() );

				return array(
					'is_success'    => false,
					'error_message' => esc_html__( 'Unable to capture the charge.', 'gravityformsstripe' ),
				);
			}

			// Prepare payment details.
			$payment = array(
				'is_success'     => true,
				'transaction_id' => $charge->id,
				'amount'         => $this->get_amount_import( $charge->amount, $entry['currency'] ),
				'payment_method' => rgpost( 'stripe_credit_card_type' ),
			);
		}

		return $payment;
	}

	/**
	 * Update the entry meta with the Stripe Customer ID.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFStripe::get_customer()
	 * @uses GFPaymentAddOn::process_subscription()
	 * @uses \Exception::getMessage()
	 * @uses GFAddOn::log_error()
	 *
	 * @param array $authorization   Contains the result of the subscribe() function.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array The entry object.
	 */
	public function process_subscription( $authorization, $feed, $submission_data, $form, $entry ) {
		if ( empty( $authorization['subscription']['subscription_id'] ) && $this->is_stripe_checkout_enabled() ) {

			gform_update_meta( $entry['id'], 'stripe_session_id', $authorization['subscription']['session_id'] );

			$this->log_debug( __METHOD__ . '(): Marking entry as processing for a subscription created by Stripe Checkout.' );
			GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Processing' );

			// return $entry directly for the new Stripe Checkout.
			return $entry;
		}

		// Update customer ID for entry.
		$customer_id = rgars( $authorization, 'subscription/customer_id' );
		if ( $customer_id ) {
			gform_update_meta( $entry['id'], 'stripe_customer_id', $customer_id );
		} else {
			// No customer ID, setup the subscription error message.
			$authorization['subscription']['error_message'] = esc_html__( 'Failed to get the customer ID from Stripe.', 'gravityformsstripe' );
		}

		if ( $this->is_stripe_checkout_enabled() ) {
			// When the session is completed, we cannot run create_customer() because the customer is already created
			// by Stripe Checkout. So we support the `gform_stripe_customer_after_create` filter here to perform custom
			// actions.
			$customer = $this->get_customer( '', $feed, $entry, $form );
			if ( ! $customer ) {
				$customer = $this->get_customer( $authorization['subscription']['customer_id'] );

				$this->after_create_customer( $customer, $feed, $entry, $form );

				// update customer data with feed settings.
				$customer_meta = array(
					'description' => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_description' ) ),
					'email'       => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_email' ) ),
					'metadata'    => $this->get_stripe_meta_data( $feed, $entry, $form ),
				);

				// Get coupon for feed.
				$coupon_field_id = rgar( $feed['meta'], 'customerInformation_coupon' );
				$coupon          = $this->maybe_override_field_value( rgar( $entry, $coupon_field_id ), $form, $entry, $coupon_field_id );

				// If coupon is set, add it to customer metadata.
				if ( $coupon ) {
					$stripe_coupon = $this->api->get_coupon( $coupon );
					if ( ! is_wp_error( $stripe_coupon ) ) {
						$customer_meta['coupon'] = $coupon;
					} else {
						$this->log_error( __METHOD__ . '(): Unable to add the coupon to the customer; ' . $stripe_coupon->get_error_message() );
					}
				}

				$customer = $this->api->update_customer( $authorization['subscription']['customer_id'], $customer_meta );
				if ( is_wp_error( $customer ) ) {
					$this->log_error( __METHOD__ . '(): Unable to update the customer; ' . $customer->get_error_message() . '. Customer meta passed: ' . print_r( $customer_meta, true ) );
				}
			}
		} else {
			$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );
			if ( ! empty( $metadata ) ) {
				// Update to user meta post entry creation so entry ID is available.
				// Get customer.
				$customer = $this->get_customer( $customer_id );

				if ( $customer ) {
					// Update customer metadata.
					$customer->metadata = $metadata;

					// Save customer.
					$result = $this->api->save_customer( $customer );

					if ( is_wp_error( $result ) ) {
						$this->log_error( __METHOD__ . '(): Unable to save customer; ' . $result->get_error_message() );
					}
				}
			}
		}

		if ( $this->is_payment_element_enabled( $form ) && rgars( $authorization, 'subscription/status' ) === 'pending' ) {
			gform_update_meta( $entry['id'], 'payment_element_order', $authorization['subscription']['order'] );
			$this->complete_authorization( $entry, array() );
			return $entry;
		}

		return parent::process_subscription( $authorization, $feed, $submission_data, $form, $entry );

	}

	/**
	 * Subscribe the user to a Stripe plan. This process works like so:
	 *
	 * 1 - Get existing plan or create new plan (plan ID generated by feed name, id and recurring amount).
	 * 2 - Create new customer.
	 * 3 - Create new subscription by subscribing customer to plan.
	 *
	 * @since  Unknown
	 * @since  2.8     Added $feed param when including Stripe API.
	 * @since  3.0     Support the new Stripe Checkout (the workflow is different).
	 * @since  3.4     Added card error rate limits.
	 * @since  3.5     Added support for GF_Stripe_API class.
	 * @access public
	 *
	 * @uses GFStripe::include_stripe_api()
	 * @uses GFStripe::get_stripe_js_error()
	 * @uses GFPaymentAddOn::authorization_error()
	 * @uses GFStripe::get_subscription_plan_id()
	 * @uses GFStripe::get_plan()
	 * @uses GFStripe::get_stripe_js_response()
	 * @uses GFStripe::get_customer()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_amount_export()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFAddOn::maybe_override_field_value()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array|void Subscription details if successful. Contains error message if failed.
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {
		// Check if the current IP has hit the error rate limit.
		if ( $hit_rate_limits = $this->maybe_hit_rate_limits( $form['id'] ) ) {
			return $hit_rate_limits;
		}
		$payment_element_enabled = $this->is_payment_element_enabled( $form );
		// Include Stripe API library.
		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		// If there was an error when retrieving the Stripe.js token, return an authorization error.
		if ( ! $payment_element_enabled && $this->get_stripe_js_error() ) {
			return $this->authorization_error( $this->get_stripe_js_error() );
		}

		if ( $this->is_stripe_checkout_enabled() ) {

			// Create checkout session.
			return $this->create_checkout_session( $feed, $submission_data, $form, $entry );
		}

		// Prepare payment amount and trial period data.
		$payment_amount        = $submission_data['payment_amount'];
		$single_payment_amount = $submission_data['setup_fee'];
		$trial_period_days     = rgars( $feed, 'meta/trialPeriod' ) ? $submission_data['trial'] : 0;
		$currency              = rgar( $entry, 'currency' );
		// Process merge tags in subscription name.
		$feed['meta']['processed_subscription_name'] = rgars( $feed, 'meta/subscription_name' ) ? GFCommon::replace_variables( rgars( $feed, 'meta/subscription_name' ), $form, $entry, false, true, true, 'text' ) : '';

		// Get Stripe plan that matches current feed.
		$plan = $this->get_plan_for_feed( $feed, $payment_amount, $trial_period_days, $currency );

		// If error was returned when retrieving plan, return authorization error array.
		if ( rgar( $plan, 'error_message' ) ) {
			return $plan;
		}

		// Get Stripe.js token.
		// check if stripe_response has a payment id.
		// if yes, confirm the payment here.
		if ( $payment_element_enabled ) {
				return $this->get_payment_element_handler()->subscribe( $feed, $submission_data, $form, $entry );
		} else {
			$stripe_response = $this->get_stripe_js_response();
		}
		// Subscription has been created but the invoice hasn't been paid.
		if ( ! empty( $stripe_response->subscription ) ) {
			$subscription = $this->api->get_subscription( $stripe_response->subscription );
			if ( is_wp_error( $subscription ) ) {
				$this->log_error( __METHOD__ . '(): ' . $subscription->get_error_message() );

				return $this->authorization_error( $subscription->get_error_message() );
			}

			if ( rgar( $subscription, 'status' ) === 'active' || rgar( $subscription, 'status' ) === 'trialing' ) {
				$_POST['stripe_response'] = '';

				return array(
					'is_success'      => true,
					'subscription_id' => $stripe_response->subscription,
					'customer_id'     => $subscription->customer,
					'amount'          => $payment_amount,
				);
			} else {
				$invoice = $this->api->get_invoice( rgar( $subscription, 'latest_invoice' ) );
				if ( is_wp_error( $invoice ) ) {
					$this->log_error( __METHOD__ . '(): ' . $invoice->get_error_message() );

					return $this->authorization_error( $invoice->get_error_message() );
				}

				$payment_intent = $this->api->get_payment_intent( rgar( $invoice, 'payment_intent' ) );
				if ( is_wp_error( $payment_intent ) ) {
					$this->log_error( __METHOD__ . '(): ' . $payment_intent->get_error_message() );

					return $this->authorization_error( $payment_intent->get_error_message() );
				}

				// Update customer source only when the plan id is the same.
				if ( rgar( $payment_intent, 'status' ) === 'requires_payment_method' && ( $plan->id === rgars( $subscription, 'plan/id' ) ) ) {
					if ( ! empty( $stripe_response->updatedToken ) ) {
						$result = $this->api->update_customer(
							$subscription->customer,
							array(
								'source' => $stripe_response->updatedToken,
							)
						);
						if ( ! is_wp_error( $result ) ) {
							// Pay the invoice.
							$result = $this->api->pay_invoice( $invoice, array( 'expand' => array( 'payment_intent' ) ) );
							if ( ! is_wp_error( $result ) ) {
								// Retrieve it again because the status might have changed after we pay the invoice.
								$result = $this->api->get_subscription( $stripe_response->subscription );
								if ( ! is_wp_error( $result ) ) {
									$subscription_id = $this->handle_subscription_payment( $result, $invoice );

									if ( is_array( $subscription_id ) ) {
										return $subscription_id;
									} else {
										return array(
											'is_success'      => true,
											'subscription_id' => $subscription_id,
											'customer_id'     => $result->customer,
											'amount'          => $payment_amount,
										);
									}
								}
							}
						}

						$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );

						return $this->authorization_error( $result->get_error_message() );
					} else {
						// Clear out the Stripe response so we can trigger the SCA modal again.
						$_POST['stripe_response'] = '';

						return $this->authorization_error( esc_html__( 'Your payment attempt has failed. Please enter your card details and try again.', 'gravityformsstripe' ) );
					}
				}
			}
		}

		$customer = $this->get_customer( '', $feed, $entry, $form );

		if ( $customer ) {
			$this->log_debug( __METHOD__ . '(): Updating existing customer, tracking id:' . rgget( 'tracking_id' ) );

			// Update the customer source with the Stripe token.
			$customer->source = ( ! empty( $stripe_response->updatedToken ) ) ? $stripe_response->updatedToken : $stripe_response->id;
			$result           = $this->api->save_customer( $customer );
			if ( ! is_wp_error( $result ) ) {
				// If a setup fee is required, add an invoice item.
				if ( $single_payment_amount ) {
					$setup_fee = array(
						'amount'   => $this->get_amount_export( $single_payment_amount, $currency ),
						'currency' => $currency,
						'customer' => $customer->id,
					);

					$result = $this->api->add_invoice_item( $setup_fee );
				}
			}

			if ( is_wp_error( $result ) ) {
				$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );

				return $this->authorization_error( $result->get_error_message() );
			}
		} else {
			// Prepare customer metadata.
			// Starts from 3.0, customers created by Stripe Checkout won't have the `balance` set.
			// Setup fee would be a line item in the invoice.
			$customer_meta = array(
				'description' => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_description' ) ),
				'email'       => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_email' ) ),
				'source'      => ( ! empty( $stripe_response->updatedToken ) ) ? $stripe_response->updatedToken : $stripe_response->id,
				'balance'     => $this->get_amount_export( $single_payment_amount, $currency ),
			);

			// Get coupon for feed.
			$coupon_field_id = rgar( $feed['meta'], 'customerInformation_coupon' );
			$coupon          = $this->maybe_override_field_value( rgar( $entry, $coupon_field_id ), $form, $entry, $coupon_field_id );

			// If coupon is set, add it to customer metadata.
			if ( $coupon ) {
				$customer_meta['coupon'] = $coupon;
			}

			$customer = $this->create_customer( $customer_meta, $feed, $entry, $form );

		}

		// An authorization_error is returned.
		if ( is_wp_error( $customer ) ) {
			// Return authorization error.
			return $this->authorization_error( $customer->get_error_message() );
		}

		// Add subscription to customer and retrieve the subscription ID.
		$subscription_id = $this->update_subscription( $customer, $plan, $feed, $entry, $form, $trial_period_days );

		if ( is_array( $subscription_id ) ) {
			return $subscription_id;
		}

		// Return subscription data.
		return array(
			'is_success'      => true,
			'subscription_id' => $subscription_id,
			'customer_id'     => $customer->id,
			'amount'          => $payment_amount,
		);

	}

	/**
	 * Display the thank you page when there's a gf_stripe_success URL param.
	 *
	 * @since 3.0
	 */
	public function maybe_thankyou_page() {
		if ( ! $this->is_gravityforms_supported() ) {
			return;
		}

		if ( rgget( 'resume_token' ) ) {
			$this->get_payment_element_handler()->handle_redirect();
		}

		if ( $str = rgget( 'gf_stripe_success' ) ) {
			$str = base64_decode( $str );

			parse_str( $str, $query );
			if ( wp_hash( 'ids=' . $query['ids'] ) == $query['hash'] ) {
				list( $form_id, $feed_id ) = explode( '|', $query['ids'] );
				$feed = $this->get_feed( $feed_id );
				$mode = $settings = null;
				if ( $this->is_feed_stripe_connect_enabled( $feed_id ) ) {
					$settings = $feed['meta'];
				}

				$this->include_stripe_api( $mode, $settings );

				$form       = GFAPI::get_form( $form_id );
				$session_id = sanitize_text_field( rgget( 'gf_stripe_session_id' ) );
				$session    = $this->api->get_checkout_session( $session_id );
				$entries    = GFAPI::get_entries(
					$form_id,
					array(
						'field_filters' => array(
							array(
								'key'   => 'stripe_session_id',
								'value' => $session_id,
							),
						),
					)
				);
				if ( is_wp_error( $entries ) || ! $entries ) {
					return;
				}
				$entry = $entries[0];

				if ( $this->should_complete_checkout_session( $session, $entry ) ) {
					$this->log_debug( __METHOD__ . '(): Stripe Checkout session ' . $session['id'] . ' will be completed by the thank you page.' );
					$this->complete_checkout_session( $session, $entry, $feed, $form );
				} else {
					$this->log_debug( __METHOD__ . '(): Stripe Checkout session ' . $session['id'] . ' will be completed by webhooks.' );
				}

				if ( ! class_exists( 'GFFormDisplay' ) ) {
					require_once( GFCommon::get_base_path() . '/form_display.php' );
				}

				$confirmation = GFFormDisplay::handle_confirmation( $form, $entry, false );

				if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
					header( "Location: {$confirmation['redirect']}" );
					exit;
				}

				GFFormDisplay::$submission[ $form_id ] = array(
					'is_confirmation'      => true,
					'confirmation_message' => $confirmation,
					'form'                 => $form,
					'lead'                 => $entry,
				);
			}
		}
	}

	/**
	 * Determines if Checkout Session should be completed, updating entry payment information.
	 *
	 * @since 5.0
	 *
	 * @param Session $session Stripe Session object.
	 * @param array   $entry   Entry Object.
	 *
	 * @return bool Returns True if checkout session should be completed. False if not.
	 */
	private function should_complete_checkout_session( $session, $entry ) {

		$is_payment_complete         = rgar( $session, 'payment_status' ) === 'paid';
		$is_entry_marked_as_complete = in_array( $entry['payment_status'], array( 'Active', 'Paid' ) );

		// Payment is complete in Stripe, but entry is not marked as complete.
		if ( $is_payment_complete && ! $is_entry_marked_as_complete ) {
			return true;
		}

		if ( ! rgar( $session, 'subscription' ) ) {
			$intent = $this->api->get_payment_intent( $session['payment_intent'] );

			if ( is_wp_error( $intent ) ) {
				$this->log_error( __METHOD__ . '(): Could not retrieve intent from Stripe checkout session ' . $session['id'] . ', Session should not be completed' );
				return false;
			}

			$is_authorize_only = $intent->capture_method == 'manual' && $intent->status == 'requires_capture';
			if ( $is_authorize_only && $entry['payment_status'] !== 'Authorized' ) {
				$this->log_debug( __METHOD__ . '(): Stripe checkout session ' . $session['id'] . ' should be completed. Authorization only' );
				return true;
			}
		}

		return false;
	}

	/**
	 * Complete payments or subscriptions when redirect back from Stripe Checkout or checkout.session.completed event
	 * triggered.
	 *
	 * @since 3.0
	 *
	 * @param array $session The session object.
	 * @param array $entry   The entry object.
	 * @param array $feed    The feed object.
	 * @param array $form    The form object.
	 *
	 * @return array $action
	 */
	public function complete_checkout_session( $session, $entry, $feed, $form ) {
		// Set the tracking ID to the session ID so when logging tries to get it from POST, it will be available.
		$_POST['tracking_id'] = $session['id'];
		// If checkout session is locked by another process, abort.
		if ( ! $this->get_checkout_session_lock( $session['id'] ) ) {
			$this->log_debug( __METHOD__ . '(): Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ') locked. Aborting to avoid duplication' );
			$action['abort_callback'] = true;

			return $action;
		}

		$action         = array();
		$payment_status = rgar( $entry, 'payment_status' );

		if ( $subscription_id = rgar( $session, 'subscription' ) ) {
			$this->log_debug( __METHOD__ . '(): Processing subscription for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . ')' );
			$subscription = $this->api->get_subscription( $subscription_id );
			if ( is_wp_error( $subscription ) ) {
				$this->log_error( __METHOD__ . '(): A Stripe API error occurs for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $subscription->get_error_message() );
				$this->release_checkout_session_lock( $session['id'] );

				return $action;
			}

			if ( in_array( rgar( $subscription, 'status' ), array( 'active', 'trialing' ), true ) && $payment_status !== 'Active' ) {
				// Create the $authorization array and run process_subscription().
				$recurring_fee = (int) rgars( $subscription, 'items/data/0/plan/amount' );
				$authorization = array(
					'subscription' => array(
						'subscription_id' => $subscription_id,
						'customer_id'     => rgar( $session, 'customer' ),
						'is_success'      => true,
						'amount'          => $this->get_amount_import( $recurring_fee, $entry['currency'] ),
					),
				);
				$this->process_subscription( $authorization, $feed, array(), $form, $entry );

				// Update the subscription data if `gform_stripe_subscription_params_pre_update_customer` filter is used.
				$customer = $this->api->get_customer( rgar( $session, 'customer' ) );
				if ( is_wp_error( $customer ) ) {
					$this->log_error( __METHOD__ . '(): A Stripe API error occurs for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $customer->get_error_message() );
					$this->release_checkout_session_lock( $session['id'] );

					return $action;
				}

				$plan = $this->get_plan( rgars( $subscription, 'items/data/0/plan/id' ) );
				if ( is_wp_error( $subscription ) ) {
					$this->log_error( __METHOD__ . '(): A Stripe API error occurs for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $subscription->get_error_message() );
					$this->release_checkout_session_lock( $session['id'] );

					return $action;
				}

				$trial_period_days            = rgars( $subscription, 'items/data/0/plan/trial_period_days', 0 );
				$subscription_params          = array();
				$filtered_subscription_params = $this->get_subscription_params( $subscription_params, $customer, $plan, $feed, $entry, $form, $trial_period_days );
				if ( $subscription_params !== $filtered_subscription_params ) {
					$subscription = $this->api->update_subscription( $subscription_id, $filtered_subscription_params );

					if ( is_wp_error( $subscription ) ) {
						$this->log_error( __METHOD__ . '(): A Stripe API error occurs Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $subscription->get_error_message() );
						$this->release_checkout_session_lock( $session['id'] );

						return $action;
					}
				}

				// Add capture payment note.
				$action['subscription_id'] = $subscription_id;
				$invoice                   = $this->api->get_invoice( rgar( $subscription, 'latest_invoice' ) );
				if ( is_wp_error( $invoice ) ) {
					$this->log_error( __METHOD__ . '(): A Stripe API error occurs for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $invoice->get_error_message() );
					$this->release_checkout_session_lock( $session['id'] );

					return $action;
				}

				$action['transaction_id'] = rgar( $invoice, 'payment_intent' );
				$action['entry_id']       = $entry['id'];
				$action['type']           = 'add_subscription_payment';
				$action['amount']         = $this->get_amount_import( rgar( $invoice, 'amount_due' ), $entry['currency'] );
				$action['note']           = '';

				// Get starting balance, assume this balance represents a setup fee or trial.
				$starting_balance = $this->get_amount_import( rgar( $invoice, 'starting_balance' ), $entry['currency'] );
				if ( $starting_balance > 0 ) {
					$action['note'] = $this->get_captured_payment_note( $action['entry_id'] ) . ' ';
				}

				$amount_formatted = GFCommon::to_money( $action['amount'], $entry['currency'] );
				$action['note']   .= sprintf( __( 'Subscription payment has been paid. Amount: %s. Subscription Id: %s', 'gravityformsstripe' ), $amount_formatted, $action['subscription_id'] );

				if ( rgar( $subscription, 'status' ) === 'active' ) {
					// Run gform_stripe_fulfillment hook for supporting delayed payments.
					$this->checkout_fulfillment( $session, $entry, $feed, $form );
				}
			}
		} else {
			$action['abort_callback'] = true;

			if ( $payment_status !== 'Paid' ) {
				$submission_data       = gform_get_meta( $entry['id'], 'submission_data' );
				$payment_intent        = rgar( $session, 'payment_intent' );
				$payment_intent_object = false;
				$amount_total          = rgar( $session, 'amount_total' );

				if ( $amount_total === 0 && ! $payment_intent ) {
					$this->log_debug( __METHOD__ . '(): Order total is 0, skip payment intent retrieval as there is none' );
				} else {
					$payment_intent_object = $this->api->get_payment_intent( $payment_intent, array( 'expand' => array( 'latest_charge' ) ) );
					if ( is_wp_error( $payment_intent_object ) ) {
						$this->log_error( __METHOD__ . '(): A Stripe API error occurs for Checkout session ' . $session['id'] . ' for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '); ' . $payment_intent_object->get_error_message() );
						$this->release_checkout_session_lock( $session['id'] );

						return $action;
					}
				}

				$authorization = array(
					'is_authorized'  => true,
					'transaction_id' => $payment_intent,
					'amount'         => $payment_intent_object ? $this->get_amount_import( rgars( $payment_intent_object, 'amount_received' ), $entry['currency'] ) : 0,
				);

				// complete authorization.
				if ( $payment_status !== 'Authorized' ) {
					$this->process_capture( $authorization, $feed, $submission_data, $form, $entry );
				}

				// if authorization_only = true, status will be 'requires_capture',
				// so if the payment intent status is succeeded, we can mark the entry as Paid.
				if ( rgars( $payment_intent_object, 'status' ) === 'succeeded' || $amount_total === 0 ) {
					$payment_method = $payment_intent_object ? rgars( $payment_intent_object, 'latest_charge/payment_method_details/card/brand' ) : esc_html__( '100% Off Discount Coupon', 'graivtyformsstripe' );

					$authorization['captured_payment'] = array(
						'is_success'     => true,
						'transaction_id' => $payment_intent,
						'amount'         => $authorization['amount'],
						'payment_method' => $payment_method,
					);
					// Mark payment as completed (paid).
					$this->process_capture( $authorization, $feed, $submission_data, $form, $entry );

					// Run gform_stripe_fulfillment hook for supporting delayed payments.
					$this->checkout_fulfillment( $session, $entry, $feed, $form );
				}

				// Update payment intent for description to add Entry ID.
				// Because the entry ID wasn't available when checkout session was created.
				if ( ! empty( $submission_data ) && $payment_intent_object !== false ) {
					$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );
					if ( ! empty( $metadata ) ) {
						$payment_intent_object->metadata = $metadata;
					}

					$payment_intent_object->description = $this->get_payment_description( $entry, $submission_data, $feed );
					$this->api->save_payment_intent( $payment_intent_object );

					gform_delete_meta( $entry['id'], 'submission_data' );
				}
			}
		}

		$this->release_checkout_session_lock( $session['id'] );

		return $action;
	}

	/**
	 * Gets a lock for completing the checkout session process.
	 *
	 * @since 5.0
	 *
	 * @param string $session_id The session id to be locked.
	 *
	 * @return bool Returns true if session was able to be locked. Returns false if session is already locked by another process.
	 */
	private function get_checkout_session_lock( $session_id ) {
		$lock_key = "stripe_checkout_session_locked_{$session_id}";
		$is_locked = get_transient( $lock_key );
		if ( $is_locked ) {
			return false;
		}
		// Locking session for 10 seconds, or until transient is deleted. Setting an expiration ensures session won't be locked forever if an error prevents it from being unlocked.
		set_transient( $lock_key, 1, 10 );

		return true;
	}

	/**
	 * Releases the checkout session process lock.
	 *
	 * @since 5.0
	 *
	 * @param string $session_id The session to be released.
	 *
	 * @return void
	 */
	private function release_checkout_session_lock( $session_id ) {
		delete_transient( "stripe_checkout_session_locked_{$session_id}" );
	}

	/**
	 * Run functions that hook to gform_stripe_fulfillment.
	 *
	 * @since 3.1
	 *
	 * @param array|\Stripe\Checkout\Session $session The session object.
	 * @param array                          $entry   The entry object.
	 * @param array                          $feed    The feed object.
	 * @param array                          $form    The form object.
	 */
	public function checkout_fulfillment( $session, $entry, $feed, $form ) {

		if ( $subscription_id = rgar( $session, 'subscription' ) ) {
			$transaction_id = $subscription_id;
		} else {
			$transaction_id = rgar( $session, 'payment_intent' );
		}

		$result = GFAPI::get_entry( rgar( $entry, 'id' ) );
		if ( ! is_wp_error( $result ) ) {
			$entry = $result;
		}

		if ( method_exists( $this, 'trigger_payment_delayed_feeds' ) ) {
			$this->trigger_payment_delayed_feeds( $transaction_id, $feed, $entry, $form );
		}

		if ( has_filter( 'gform_stripe_fulfillment' ) ) {
			// Log that filter will be executed.
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_fulfillment.' );

			/**
			 * Allow custom actions to be performed after a checkout session is completed.
			 *
			 * @since 3.1
			 *
			 * @param array $session The session object.
			 * @param array $entry   The entry object.
			 * @param array $feed    The feed object.
			 * @param array $form    The form object.
			 */
			do_action( 'gform_stripe_fulfillment', $session, $entry, $feed, $form );
		}
	}

	/**
	 * If this entry was created by a Stripe Checkout session, executes checkout_fulfillment() function
	 *
	 * @param array $entry Current entry being processed.
	 *
	 * @return bool|WP_Error Returns true if checkout_fulfillment() was called and false if entry was not created via Stripe Checkout. Returns a WP_Error if session can't be retrieved...
	 */
	public function maybe_checkout_fulfillment( $entry ) {

		$session_id = gform_get_meta( $entry['id'], 'stripe_session_id' );
		if ( ! $session_id ) {
			return false;
		}

		$session = \Stripe\Checkout\Session::retrieve( $session_id );
		if ( is_wp_error( $session ) ) {
			$this->log_error( __METHOD__ . '(): A Stripe API error occurs; ' . $session->get_error_message() );

			return new WP_Error( 'stripe_session_failed', __METHOD__ . '(): A Stripe API error occurs; ' . $session->get_error_message() );
		}

		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_payment_feed( $entry, $form );

		// Run gform_stripe_fulfillment hook.
		$this->checkout_fulfillment( $session, $entry, $feed, $form );

		return true;

	}

	// # STRIPE HELPER FUNCTIONS ---------------------------------------------------------------------------------------

	/**
	 * Retrieve a specific customer from Stripe.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::cancel()
	 * @used-by GFStripe::process_subscription()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::log_debug()
	 *
	 * @param string $customer_id The identifier of the customer to be retrieved.
	 * @param array  $feed        The feed currently being processed.
	 * @param array  $entry       The entry currently being processed.
	 * @param array  $form        The which created the current entry.
	 *
	 * @return bool|\Stripe\Customer Contains customer data if available. Otherwise, false.
	 */
	public function get_customer( $customer_id, $feed = array(), $entry = array(), $form = array() ) {
		if ( empty( $customer_id ) && has_filter( 'gform_stripe_customer_id' ) ) {
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_customer_id.' );

			/**
			 * Allow an existing customer ID to be specified for use when processing the submission.
			 *
			 * @since  2.1.0
			 * @access public
			 *
			 * @param string $customer_id The identifier of the customer to be retrieved. Default is empty string.
			 * @param array  $feed        The feed currently being processed.
			 * @param array  $entry       The entry currently being processed.
			 * @param array  $form        The form which created the current entry.
			 */
			$customer_id = apply_filters( 'gform_stripe_customer_id', $customer_id, $feed, $entry, $form );
		}

		if ( $customer_id ) {
			$this->log_debug( __METHOD__ . '(): Retrieving customer id => ' . print_r( $customer_id, true ) );
			$customer = $this->api->get_customer( $customer_id );

			if ( ! is_wp_error( $customer ) ) {
				return $customer;
			}

			$this->log_error( __METHOD__ . '(): Unable to get customer; ' . $customer->get_error_message() );
		}

		return false;
	}

	/**
	 * Create and return a Stripe customer with the specified properties.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::log_debug()
	 *
	 * @param array $customer_meta The customer properties.
	 * @param array $feed          The feed currently being processed.
	 * @param array $entry         The entry currently being processed.
	 * @param array $form          The form which created the current entry.
	 *
	 * @return WP_Error|\Stripe\Customer The Stripe customer object.
	 */
	public function create_customer( $customer_meta, $feed, $entry, $form ) {
		// Log the customer to be created.
		$tracking_id = rgpost( 'tracking_id' );
		if ( $tracking_id ) {
			$tracking = 'for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id;
			$this->log_debug( __METHOD__ . '(): Creating Customer ' . $tracking );
		}

		$this->log_debug( __METHOD__ . '(): Customer meta to be created => ' . print_r( $customer_meta, true ) );
		$customer = $this->api->create_customer( $customer_meta );

		if ( is_wp_error( $customer ) ) {
			$this->log_error( __METHOD__ . '(): A Stripe API error occurs; ' . $customer->get_error_message() );
		} else {
			$this->after_create_customer( $customer, $feed, $entry, $form );
		}

		return $customer;
	}

	/**
	 * Run action hook after a customer is created.
	 *
	 * @since 3.0
	 *
	 * @param Stripe\Customer $customer The customer object.
	 * @param array           $feed     The feed object.
	 * @param array           $entry    The entry object.
	 * @param array           $form     The form object.
	 */
	public function after_create_customer( $customer, $feed, $entry, $form ) {
		if ( has_filter( 'gform_stripe_customer_after_create' ) ) {
			// Log that filter will be executed.
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_customer_after_create.' );

			/**
			 * Allow custom actions to be performed between the customer being created and subscribed to the plan.
			 *
			 * @since 2.0.1
			 *
			 * @param Stripe\Customer $customer The Stripe customer object.
			 * @param array           $feed     The feed currently being processed.
			 * @param array           $entry    The entry currently being processed.
			 * @param array           $form     The form currently being processed.
			 */
			do_action( 'gform_stripe_customer_after_create', $customer, $feed, $entry, $form );
		}
	}

	/**
	 * Retrieves a plan that matches the feed properties or creates a new one if no matching plans exist.
	 *
	 * @since 3.7.2
	 *
	 * @param array     $feed              The feed object currently being processed.
	 * @param float|int $payment_amount    The recurring amount.
	 * @param int       $trial_period_days The number of days the trial should last.
	 * @param string    $currency          The currency code for the entry being processed.
	 *
	 * @return \Stripe\Plan|array $plan The plan object, If invalid request, the authorization error array containing the error message.
	 */
	public function get_plan_for_feed( $feed, $payment_amount, $trial_period_days, $currency ) {

		$plan_id = $this->get_subscription_plan_id( $feed, $payment_amount, $trial_period_days, $currency );
		$plan    = $this->get_plan( $plan_id );

		if ( rgar( $plan, 'error_message' ) ) {

			// If $plan is an array that has an error message, it is an authorization error array.
			return $plan;

		} elseif ( false === $plan ) {

			// plan does not exist, create it.
			return $this->create_plan( $plan_id, $feed, $payment_amount, $trial_period_days, $currency );

		} elseif ( false === $this->is_plan_for_feed( $plan, $feed, $payment_amount, $currency ) ) {

			// Plan with same id exists but with mismatching properties.
			// Append time to feed subscription name to guarantee the generated plan id will be unique.
			$current_subscription_name                   = rgars( $feed, 'meta/subscription_name', $feed['meta']['feedName'] );
			$processed_subscription_name                 = rgars( $feed, 'meta/processed_subscription_name', $feed['meta']['feedName'] );
			$feed['meta']['subscription_name']           = $current_subscription_name . '_' . gmdate( 'Y_m_d_H_i_s' );
			$feed['meta']['processed_subscription_name'] = $processed_subscription_name . '_' . gmdate( 'Y_m_d_H_i_s' );
			$this->update_feed_meta( $feed['id'], $feed['meta'] );
			// Generate new unique plan id.
			$plan_id = $this->get_subscription_plan_id( $feed, $payment_amount, $trial_period_days, $currency );
			// Create a new plan with unique id and correct parameters.
			return $this->create_plan( $plan_id, $feed, $payment_amount, $trial_period_days, $currency );

		}

		return $plan;
	}

	/**
	 * Compares stripe plan object properties with feed properties to make sure this is the correct plan for this feed.
	 *
	 * @since 3.7.2
	 * @since 4.1   Updated to validate the plan and parent product are active.
	 *
	 * @param \Stripe\Plan  $plan           The retrieved stripe plan object.
	 * @param array         $feed           The feed object currently being processed.
	 * @param float|int     $payment_amount The recurring amount.
	 * @param string        $currency       The currency code for the entry being processed.
	 *
	 * @return bool
	 */
	public function is_plan_for_feed( $plan, $feed, $payment_amount, $currency ) {
		return (
			$plan->active
			&& $plan->product->active
			&& $plan->amount === $this->get_amount_export( $payment_amount, $currency )
			&& strtolower( $plan->currency ) === strtolower( $currency )
			&& $plan->interval === $feed['meta']['billingCycle_unit']
			&& $plan->interval_count === intval( $feed['meta']['billingCycle_length'] )
		);
	}

	/**
	 * Try and retrieve the plan if a plan with the matching id has previously been created.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    GFPaymentAddOn::authorization_error()
	 *
	 * @param string $plan_id The subscription plan id.
	 *
	 * @return \Stripe\Plan|bool|array $plan The plan object. False if not found. If invalid request, the authorization error array containing the error message.
	 */
	public function get_plan( $plan_id ) {
		// Get Stripe plan.
		$plan = $this->api->get_plan( $plan_id );
		if ( is_wp_error( $plan ) ) {
			$plan = $this->authorization_error( $plan->get_error_message() );
		}

		return $plan;
	}

	/**
	 * Create and return a Stripe plan with the specified properties.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    GFPaymentAddOn::get_amount_export()
	 * @uses    GFAddOn::log_debug()
	 *
	 * @param string    $plan_id           The plan ID.
	 * @param array     $feed              The feed currently being processed.
	 * @param float|int $payment_amount    The recurring amount.
	 * @param int       $trial_period_days The number of days the trial should last.
	 * @param string    $currency          The currency code for the entry being processed.
	 *
	 * @return array|\Stripe\Plan The plan object, or an authorization error.
	 */
	public function create_plan( $plan_id, $feed, $payment_amount, $trial_period_days, $currency ) {

		$plan_meta = array(
			'interval'          => $feed['meta']['billingCycle_unit'],
			'interval_count'    => $feed['meta']['billingCycle_length'],
			'product'           => array( 'name' => $this->get_product_name( $feed ) ),
			'currency'          => $currency,
			'id'                => $plan_id,
			'amount'            => $this->get_amount_export( $payment_amount, $currency ),
			'trial_period_days' => $trial_period_days ? $trial_period_days : 0,
		);

		// Log the plan to be created.
		$tracking_id = rgpost( 'tracking_id' );
		if ( $tracking_id ) {
			$form     = GFAPI::get_form( rgar( $feed, 'form_id' ) );
			$tracking = 'for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . $tracking_id;
			$this->log_debug( __METHOD__ . '(): Creating Plan ' . $tracking );
		}
		$this->log_debug( __METHOD__ . '(): Plan to be created => ' . print_r( $plan_meta, true ) );

		// Create Stripe plan.
		$plan = $this->api->create_plan( $plan_meta );
		if ( is_wp_error( $plan ) ) {
			$this->log_error( __METHOD__ . '(): A Stripe API error occurs; ' . $plan->get_error_message() );
			$plan = $this->authorization_error( $plan->get_error_message() );
		}

		return $plan;
	}

	/**
	 * Retrieves the name of the product from the feed.
	 *
	 * @since 4.1
	 *
	 * @param array $feed The feed being processed.
	 *
	 * @return string
	 */
	private function get_product_name( $feed ) {
		$processed_subscription_name = rgars( $feed, 'meta/processed_subscription_name', '' );
		return $processed_subscription_name ? $processed_subscription_name : $feed['meta']['feedName'];
	}

	/**
	 * Subscribes the customer to the plan.
	 *
	 * @since 2.3.4
	 * @since 2.5.2 Added the $trial_period_days param.
	 * @since 3.3   Updated to support payment intents API.
	 * @since 3.4   Updated for Stripe SDK 7.0.
	 *
	 * @param \Stripe\Customer $customer          The Stripe customer object.
	 * @param \Stripe\Plan     $plan              The Stripe plan object.
	 * @param array            $feed              The feed currently being processed.
	 * @param array            $entry             The entry currently being processed.
	 * @param array            $form              The form which created the current entry.
	 * @param int              $trial_period_days The number of days the trial should last.
	 *
	 * @return string|array Return Stripe subscription ID if payment succeed; otherwise returning an authorization error array.
	 */
	public function update_subscription( $customer, $plan, $feed, $entry, $form, $trial_period_days = 0 ) {
		if ( $this->is_stripe_checkout_enabled() ) {
			$subscription_params = array();
		} elseif ( $this->has_credit_card_field( $form ) ) {
			$subscription_params = array(
				'customer' => $customer->id,
				'items'    => array(
					array(
						'plan' => $plan->id,
					),
				),
			);
		} else {
			$subscription_params = array(
				'customer' => $customer->id,
				'items'    => array(
					array(
						'plan' => $plan->id,
					),
				),
				'expand'   => array(
					'latest_invoice.payment_intent',
				),
			);
		}

		if ( $trial_period_days > 0 ) {
			$subscription_params['trial_from_plan'] = true;
		}

		// Get filtered $subscription_params.
		$subscription_params = $this->get_subscription_params( $subscription_params, $customer, $plan, $feed, $entry, $form, $trial_period_days );
		if ( $this->has_credit_card_field( $form ) || $this->is_stripe_checkout_enabled() ) {
			$update_subscription = rgar( $subscription_params, 'id' ) ? true : false;

			if ( $update_subscription ) {
				$subscription = $this->api->update_subscription( rgar( $subscription_params, 'id' ), $subscription_params );
			} else {
				$subscription = $this->api->create_subscription( $subscription_params );
			}

			if ( ! is_wp_error( $subscription ) ) {
				return $subscription->id;
			} else {
				$this->log_error( __METHOD__ . '(): A Stripe API error occurs; ' . $subscription->get_error_message() );
			}
		} else {
			$subscription = $this->api->create_subscription( $subscription_params );

			if ( ! is_wp_error( $subscription ) ) {
				return $this->handle_subscription_payment( $subscription );
			} else {
				$this->log_error( __METHOD__ . '(): A Stripe API error occurs; ' . $subscription->get_error_message() );
			}
		}

		return $this->authorization_error( $subscription->get_error_message() );
	}

	/**
	 * Handle subscription payment intent.
	 *
	 * @since 3.3
	 *
	 * @param \Stripe\Subscription|array $subscription The subscription object.
	 * @param \Stripe\Invoice|null|array $invoice      The invoice object.
	 *
	 * @return int|array
	 */
	public function handle_subscription_payment( $subscription, $invoice = null ) {
		if ( empty( $invoice ) ) {
			$payment_intent = rgars( $subscription, 'latest_invoice/payment_intent' );
		} else {
			$payment_intent = rgar( $invoice, 'payment_intent' );
		}

		if ( rgar( $subscription, 'status' ) === 'active' || rgar( $subscription, 'status' ) === 'trialing' ) {
			return $subscription->id;
		} elseif ( rgar( $subscription, 'status' ) === 'incomplete' ) {
			$stripe_response = $this->get_stripe_js_response();

			if ( rgar( $payment_intent, 'status' ) === 'requires_payment_method' ) {
				return $this->authorization_error( esc_html__( 'Your payment attempt has failed. Please enter your card details and try again.', 'gravityformsstripe' ) );
			} elseif ( rgar( $payment_intent, 'status' ) === 'requires_action' ) {
				$_POST['stripe_response'] = json_encode(
					array(
						'id'            => rgobj( $stripe_response, 'id' ),
						'client_secret' => $payment_intent->client_secret,
						'amount'        => $payment_intent->amount,
						'subscription'  => rgar( $subscription, 'id' ),
					)
				);

				$error = $this->authorization_error( esc_html__( 'This payment might require 3D Secure authentication, please wait while we communicate with Stripe.', 'gravityformsstripe' ) );

				return array_merge( $error, array( 'requires_action' => true ) );
			}
		}
	}

	/**
	 * Gets the Stripe subscription object for the given ID.
	 *
	 * @deprecated 3.5 Use $this->api->get_subscription() instead.
	 *
	 * @since 2.8
	 *
	 * @param string $subscription_id The subscription ID.
	 *
	 * @return bool|\Stripe\Subscription
	 */
	public function get_subscription( $subscription_id ) {

		$this->log_debug( __METHOD__ . '(): Getting subscription ' . $subscription_id );

		try {
			$subscription = $this->api->get_subscription( $subscription_id );
		} catch ( \Exception $e ) {

			$this->log_error( __METHOD__ . '(): Unable to get subscription; ' . $e->getMessage() );
			$subscription = false;
		}

		return $subscription;
	}

	/**
	 * Retrieve the specified Stripe Event.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::callback()
	 * @uses    GFStripe::include_stripe_api()
	 * @uses    \Stripe\Event::retrieve()
	 *
	 * @param string      $event_id Stripe Event ID.
	 * @param null|string $mode     The API mode; live or test.
	 *
	 * @return WP_Error|\Stripe\Event The Stripe event object.
	 */
	public function get_stripe_event( $event_id, $mode = null ) {
		// Include Stripe API library.
		$this->include_stripe_api( $mode );

		$event = $this->api->get_event( $event_id );

		return $event;
	}

	public function get_simulated_event( $response ) {
		// Include sandbox Stripe API library.
		$api = $this->include_stripe_api( 'test' );

		// TODO: need to fix this
		require_once( 'includes/api/model/class-event.php' );
		return new \Gravity_Forms_Stripe\API\Model\Event( $response, $api );
	}

	/**
	 * Get Stripe account display name.
	 *
	 * @since 2.8
	 *
	 * @param array       $settings Settings.
	 * @param string|null $mode API mode.
	 *
	 * @return Stripe\Account|WP_Error object.
	 */
	public function get_stripe_account( $settings, $mode = null ) {
		$this->include_stripe_api( $mode, $settings );

		return $this->api->get_account();
	}

	/**
	 * If custom meta data has been configured on the feed retrieve the mapped field values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::capture()
	 * @used-by GFStripe::process_subscription()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::get_field_value()
	 *
	 * @param array $feed  The feed object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array The Stripe meta data.
	 */
	public function get_stripe_meta_data( $feed, $entry, $form ) {

		// Initialize metadata array.
		$metadata = array();

		// Find feed metadata.
		$custom_meta = rgars( $feed, 'meta/metaData' );

		if ( is_array( $custom_meta ) ) {

			// Loop through custom meta and add to metadata for stripe.
			foreach ( $custom_meta as $meta ) {

				// If custom key or value are empty, skip meta.
				if ( empty( $meta['custom_key'] ) || empty( $meta['value'] ) ) {
					continue;
				}

				// Make the key available to the gform_stripe_field_value filter.
				$this->_current_meta_key = $meta['custom_key'];

				// Get field value for meta key.
				$field_value = $this->get_field_value( $form, $entry, $meta['value'] );

				if ( ! empty( $field_value ) ) {

					// Trim to 500 characters, per Stripe requirement.
					$field_value = substr( $field_value, 0, 500 );

					// Add to metadata array.
					$metadata[ $meta['custom_key'] ] = $field_value;
				}
			}

			if ( ! empty( $metadata ) ) {
				$this->log_debug( __METHOD__ . '(): ' . json_encode( $metadata ) );
			}

			// Clear the key in case get_field_value() and gform_stripe_field_value are used elsewhere.
			$this->_current_meta_key = '';

		}

		return $metadata;

	}

	/**
	 * Check if a Stripe.js has an error or is missing the ID and then return the appropriate message.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFStripe::get_stripe_js_response()
	 *
	 * @return bool|string The error. False if the error does not exist.
	 */
	public function get_stripe_js_error() {

		// Get Stripe.js response.
		$response = $this->get_stripe_js_response();

		// If an error message is provided, return error message.
		if ( isset( $response->error ) ) {
			return $response->error->message;
		} elseif ( empty( $response->id ) && ! $this->is_stripe_checkout_enabled() ) {
			return esc_html__( 'Unable to authorize card. No response from Stripe.js.', 'gravityformsstripe' );
		}

		return false;

	}

	/**
	 * Response from Stripe.js is posted to the server as 'stripe_response'.
	 *
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GFStripe::add_stripe_inputs()
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::get_stripe_js_error()
	 * @used-by GFStripe::subscribe()
	 *
	 * @return \Stripe\Token|null A valid Stripe response object or null
	 */
	public function get_stripe_js_response() {
		$response = json_decode( rgpost( 'stripe_response' ) );

		if ( isset( $response->token ) ) {
			$response->id = $response->token->id;
		} elseif ( isset( $response->paymentMethod ) ) {
			$response->id = $response->paymentMethod->id;
		}

		return $response;

	}

	/**
	 * Include the Stripe API and set the current API key.
	 *
	 * @since   Unknown
	 * @since   2.8     Added $feed param.
	 * @since   3.3     Set to use API version 2019-10-17.
	 * @since   3.4     Use the new GF_Stripe_API class.
	 * @access  public
	 *
	 * @used-by GFStripe::ajax_validate_secret_key()
	 * @used-by GFStripe::authorize()
	 * @used-by GFStripe::cancel()
	 * @used-by GFStripe::get_stripe_event()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::get_base_path()
	 * @uses    \Stripe\Stripe::setApiKey()
	 * @uses    GFStripe::get_secret_api_key()
	 *
	 * @param null|string $mode The API mode; live or test.
	 * @param null|array  $settings The settings.
	 */
	public function include_stripe_api( $mode = null, $settings = null ) {
		if ( empty( $mode ) && empty( $settings ) ) {
			$settings = $this->get_plugin_settings();
			$mode     = $this->get_api_mode( $settings );
		}

		// Load the Stripe API library.
		if ( ! class_exists( 'GF_Stripe_API' ) ) {
			require_once 'includes/api/class-gf-stripe-api.php';
		}

		$this->log_debug( sprintf( '%s(): Initializing Stripe API for %s mode.', __METHOD__, $mode ) );

		$stripe = new GF_Stripe_API( $this->get_secret_api_key( $mode, $settings ) );

		/**
		 * Run post Stripe API initialization action.
		 *
		 * @since 2.0.10
		 */
		do_action( 'gform_stripe_post_include_api' );

		// Assign the Stripe API object to this instance.
		$this->api = $stripe;

		return $this->api;

	}

	/**
	 * Get success URL for Stripe Session.
	 *
	 * The URL structure and thank you page pattern was created in the PayPal add-on and we borrow it here.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id Form ID.
	 * @param int $feed_id Feed ID.
	 *
	 * @return string
	 */
	public function get_success_url( $form_id, $feed_id ) {
		$page_url = GFCommon::is_ssl() ? 'https://' : 'http://';

		/**
		 * Set the Stripe URL port if it's not 80.
		 *
		 * @since 3.0
		 *
		 * @param string Default server port.
		 */
		$server_port = apply_filters( 'gform_stripe_url_port', $_SERVER['SERVER_PORT'] );

		if ( $server_port != '80' && $server_port != '443' ) {
			$page_url .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
		} else {
			$page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		$ids_query = "ids={$form_id}|{$feed_id}";
		$ids_query .= '&hash=' . wp_hash( $ids_query );

		$url = add_query_arg( 'gf_stripe_success', base64_encode( $ids_query ), $page_url );
		// Add session id template to success url, Stripe will convert it to the real value.
		$url = add_query_arg( 'gf_stripe_session_id', '{CHECKOUT_SESSION_ID}', $url );

		// We will detect gf_stripe_success in the URL param and display a thank you page (confirmation) later.
		$query = 'gf_stripe_success=' . base64_encode( $ids_query ) . '&gf_stripe_session_id={CHECKOUT_SESSION_ID}';

		/**
		 * Filters Stripe Session's success URL, which is the URL that users will be sent to after completing the payment on Stripe.
		 *
		 * @since 3.0
		 *
		 * @param string $url     The URL to be filtered.
		 * @param int    $form_id The ID of the form being submitted.
		 * @param string $query   The query string portion of the URL.
		 */
		return apply_filters( 'gform_stripe_success_url', $url, $form_id, $query );
	}

	/**
	 * Get cancel URL for Stripe Session.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return string
	 */
	public function get_cancel_url( $form_id ) {
		$page_url = GFCommon::is_ssl() ? 'https://' : 'http://';

		$server_port = apply_filters( 'gform_stripe_url_port', $_SERVER['SERVER_PORT'] );

		if ( $server_port != '80' ) {
			$page_url .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
		} else {
			$page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		/**
		 * Filters Stripe Session's cancel URL, which is the URL that users will be sent to after canceling the payment on Stripe.
		 *
		 * @since 3.0
		 *
		 * @param string $url     The URL to be filtered.
		 * @param int    $form_id The ID of the form being submitted.
		 */
		return apply_filters( 'gform_stripe_cancel_url', $page_url, $form_id );
	}





	// # WEBHOOKS ------------------------------------------------------------------------------------------------------

	/**
	 * If the Stripe webhook belongs to a valid entry process the raw response into a standard Gravity Forms $action.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFStripe::get_api_mode()
	 * @uses GFStripe::get_stripe_event()
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_entry_by_transaction_id()
	 * @uses GFPaymentAddOn::get_amount_import()
	 * @uses GFStripe::get_subscription_line_item()
	 * @uses GFStripe::get_captured_payment_note()
	 * @uses GFAPI::get_entry()
	 * @uses GFCommon::to_money()
	 *
	 * @return array|bool|WP_Error Return a valid GF $action or if the webhook can't be processed a WP_Error object or false.
	 */
	public function callback() {

		$event = $this->get_webhook_event();

		if ( ! $event || is_wp_error( $event ) ) {
			return $event;
		}

		// Get event properties.
		$action = $log_details = array( 'id' => $event->id );
		$type   = $event->type;

		$log_details += array(
			'type'                => $type,
			'webhook api_version' => $event->api_version,
		);

		$supported_event_types = array(
			'charge.expired',
			'charge.refunded',
			'charge.captured',
			'customer.subscription.deleted',
			'invoice.payment_succeeded',
			'invoice.payment_failed',
			'payment_intent.succeeded',
			'payment_intent.payment_failed',
			'checkout.session.completed',
			'checkout.session.async_payment_succeeded',
			'checkout.session.async_payment_failed',
		);


		if ( in_array( $type, $supported_event_types ) ) {
			$this->log_debug( __METHOD__ . '() Webhook event details => ' . print_r( $log_details, true ) );
		}

		/**
		 * Enable support for a custom webhook handler that will take priority over the default handler below.
		 *
		 * @since 6.0
		 *
		 * @param array         $action An associative array containing the event details.
		 * @param \Stripe\Event $event  The Stripe event object for the webhook which was received.
		 */
		$action = apply_filters( 'gform_stripe_pre_webhook', $action, $event );

		// If the custom webhook handler has returned an error, return it.
		if( is_wp_error( $action ) ) {
			return $action;
		}

		// If webhooks have not been processed in the gform_stripe_pre_webhook filter, process them here.
		$is_webhook_processed = rgar( $action, 'entry_id' ) || rgar( $action, 'abort_callback' );
		if ( ! $is_webhook_processed ) {

			switch ( $type ) {

				case 'charge.expired':
				case 'charge.refunded':
					// try payment intent first.
					$action['transaction_id'] = rgars($event, 'data/object/payment_intent');
					$entry_id = $this->get_entry_by_transaction_id($action['transaction_id']);
					if (!$entry_id) {
						// try charge id.
						$action['transaction_id'] = rgars($event, 'data/object/id');
						$entry_id = $this->get_entry_by_transaction_id($action['transaction_id']);
						if (!$entry_id) {
							return $this->get_entry_not_found_wp_error('transaction', $action, $event);
						}
					}

					$entry = GFAPI::get_entry($entry_id);

					$payment_status = rgar($entry, 'payment_status');

					// Don't process the webhook if the payment has already been refunded in the dashboard.
					if ($payment_status !== 'Refunded') {
						if (!$this->is_valid_entry_for_callback($entry)) {
							return $this->get_wrong_feed_wp_error($entry_id);
						}

						$action['entry_id'] = $entry_id;

						if ($event->data->object->captured) {

							$action['type'] = 'refund_payment';
							$action['amount'] = $this->get_amount_import(
								rgars($event, 'data/object/amount_refunded'),
								$entry['currency']
							);
						} else {
							$action['type'] = 'void_authorization';
						}
					}

					break;

				case 'charge.captured':
					// Getting transaction ID. Use Payment Intent if one is set. Otherwhise use Charge ID.
					$action['transaction_id'] = rgars($event, 'data/object/payment_intent') ? rgars($event, 'data/object/payment_intent') : rgars($event, 'data/object/id');
					$entry_id = $this->get_entry_by_transaction_id($action['transaction_id']);

					// Abort if entry can't be found.
					if (!$entry_id) {
						return $this->get_entry_not_found_wp_error('transaction', $action, $event);
					}
					$entry = GFAPI::get_entry($entry_id);

					$is_processing = get_option("gform_stripe_capturing_{$action['transaction_id']}");

					// Abort if transaction is not 'Authorized' or capture process is currently taking place.
					if (rgar($entry, 'payment_status') !== 'Authorized' || $is_processing) {
						$action['abort_callback'] = true;
						break;
					}

					// Abort if entry doesn't match this callback.
					if (!$this->is_valid_entry_for_callback($entry)) {
						return $this->get_wrong_feed_wp_error($entry_id);
					}

					$checkout_fulfillment_result = $this->maybe_checkout_fulfillment($entry);
					if (is_wp_error($checkout_fulfillment_result)) {
						return $checkout_fulfillment_result;
					}

					// Completing payment. Marking entry as Paid.
					$action['entry_id'] = $entry_id;
					$action['type'] = 'complete_payment';
					$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/amount'), $entry['currency']);

					break;

				case 'customer.subscription.deleted':
					$action['subscription_id'] = rgars($event, 'data/object/id');
					$entry_id = $this->get_entry_by_transaction_id($action['subscription_id']);
					if (!$entry_id) {
						return $this->get_entry_not_found_wp_error('subscription', $action, $event);
					}

					$entry = GFAPI::get_entry($entry_id);

					if (!$this->is_valid_entry_for_callback($entry)) {
						return $this->get_wrong_feed_wp_error($entry_id);
					}

					$action['entry_id'] = $entry_id;
					$action['type'] = 'cancel_subscription';
					$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/plan/amount'), $entry['currency']);

					break;

			case 'invoice.payment_succeeded':
					$subscription = $this->get_subscription_line_item( $event );
					if ( ! $subscription ) {

						// Try to get the subscription from parent/subscription_details ( New versions of Stripe API no longer have subscription line item in the invoice object ).
						$subscription = rgars( $event, 'data/object/parent/subscription_details' );
						if ( ! $subscription ) {
							return new WP_Error('invalid_request', sprintf(__('Subscription line item not found in request', 'gravityformsstripe')));
						}
					}

					$action['subscription_id'] = rgar($subscription, 'subscription');
					$entry_id = $this->get_entry_by_transaction_id($action['subscription_id']);
					if (!$entry_id) {
						return $this->get_entry_not_found_wp_error('subscription', $action, $event);
					}

					$entry = GFAPI::get_entry($entry_id);
					$form = GFAPI::get_form($entry['form_id']);
					if ($entry['payment_status'] === 'Processing' && $this->is_payment_element_enabled($form)) {
						return $this->get_payment_element_handler()->complete_processing_entry($entry, $action, $event);
					}
					if (!$this->is_valid_entry_for_callback($entry)) {
						return $this->get_wrong_feed_wp_error($entry_id);
					}

					// If it's the first invoice and payment_status is active, it means the subscription has just started
					// when checkout session completed. So don't set action to prevent duplicate notes.
					$number = explode('-', rgars($event, 'data/object/number'));
					if ($this->is_stripe_checkout_enabled() && rgar($entry, 'payment_status') === 'Active' && $number[1] === '0001') {
						$action['abort_callback'] = true;
					} else {
						$payment_intent = rgars($event, 'data/object/payment_intent');
						$action['transaction_id'] = empty($payment_intent) ? rgars($event, 'data/object/charge') : $payment_intent;
						$action['entry_id'] = $entry_id;
						$action['type'] = 'add_subscription_payment';
						$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/amount_due'), $entry['currency']);
						$action['note'] = '';

						// Get starting balance, assume this balance represents a setup fee or trial.
						$starting_balance = $this->get_amount_import(rgars($event, 'data/object/starting_balance'), $entry['currency']);
						if ($starting_balance > 0) {
							$action['note'] = $this->get_captured_payment_note($action['entry_id']) . ' ';
						}

						$amount_formatted = GFCommon::to_money($action['amount'], $entry['currency']);
						$action['note'] .= sprintf(__('Subscription payment has been paid. Amount: %s. Subscription Id: %s', 'gravityformsstripe'), $amount_formatted, $action['subscription_id']);

						// Detect the 0002 invoice for subscriptions with trial just ended.
						if ($this->is_stripe_checkout_enabled() && rgar($entry, 'payment_status') === 'Active' && $number[1] === '0002') {

							$result = $this->api->get_subscription($action['subscription_id']);

							if (!is_wp_error($result)) {
								$trial_end = rgar($result, 'trial_end');
								// After the trial has ended, the invoice created right away will be #0002.
								if ($trial_end && $trial_end <= time()) {
									$form = GFAPI::get_form($entry['form_id']);
									$feed = $this->get_payment_feed($entry, $form);

									// Get session.
									$session_id = gform_get_meta($entry_id, 'stripe_session_id');
									$result = $this->api->get_checkout_session($session_id);

									if (!is_wp_error($result)) {
										// fulfill delayed feeds.
										$this->checkout_fulfillment($result, $entry, $feed, $form);
									}
								}
							}

							if (is_wp_error($result)) {
								$this->log_error(__METHOD__ . '(): A Stripe API error occurs; ' . $result->get_error_message());
							}
						}
					}

					break;

			case 'invoice.payment_failed':
				$subscription = $this->get_subscription_line_item( $event );
				if ( ! $subscription ) {

					// Try to get the subscription from parent/subscription_details ( New versions of Stripe API no longer have subscription line item in the invoice object ).
					$subscription = rgars( $event, 'data/object/parent/subscription_details' );
					if ( ! $subscription ) {
						return new WP_Error('invalid_request', sprintf(__('Subscription line item not found in request', 'gravityformsstripe')));
					}
					// Use amount_due since parent/subscription_details does not have amount.
					$subscription['amount'] = rgars( $event, 'data/object/amount_due' );
				}

					$action['subscription_id'] = rgar( $subscription, 'subscription');
					$entry_id = $this->get_entry_by_transaction_id($action['subscription_id']);
					if (!$entry_id) {
						return $this->get_entry_not_found_wp_error('subscription', $action, $event);
					}

					$entry = GFAPI::get_entry($entry_id);

					if (!$this->is_valid_entry_for_callback($entry)) {
						return $this->get_wrong_feed_wp_error($entry_id);
					}

					$action['type'] = 'fail_subscription_payment';
					$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/amount_due'), $entry['currency']);
					$action['entry_id'] = $this->get_entry_by_transaction_id($action['subscription_id']);

					break;

				case 'checkout.session.completed':
				case 'checkout.session.async_payment_succeeded':
					// Getting Stripe Session from event object.
					$session_id = rgars($event, 'data/object/id');
					$session = $this->api->get_checkout_session($session_id);
					if (is_wp_error($session)) {
						$this->log_error(__METHOD__ . '(): A Stripe API error occurs; ' . $session->get_error_message());

						return new WP_Error('invalid_stripe_session', esc_html__('Invalid Checkout Session', 'gravityformsstripe') . ': ' . $session->get_error_message());
					}

					// Getting entry associated with this Stripe Session.
					$entry = $this->get_entry_by_session_id($session_id, $action, $event);
					if (is_wp_error($entry)) {
						$this->log_error(__METHOD__ . '(): A Stripe API error occurs; ' . $entry->get_error_message());

						return $entry;
					}

					if ($this->should_complete_checkout_session($session, $entry)) {
						// Completing the checkout.
						$form = GFAPI::get_form($entry['form_id']);
						$feed = $this->get_payment_feed($entry, $form);
						$action = array_merge($action, $this->complete_checkout_session($session, $entry, $feed, $form));
						$this->log_debug(__METHOD__ . "(): Stripe Checkout session " . $session_id . " will be completed by the webhook event {$type}.");
					} else {
						$this->log_debug(__METHOD__ . "(): Not completing the Stripe Checkout Session " . $session_id . " during the webhook event {$type}.");
					}
					break;

				case 'checkout.session.async_payment_failed':
					$session_id = rgars($event, 'data/object/id');
					$session = $this->api->get_checkout_session($session_id);
					if (is_wp_error($session)) {
						$this->log_error(__METHOD__ . '(): A Stripe API error occurs for session ' . $session_id . ' ' . $session->get_error_message());

						return new WP_Error('invalid_stripe_session', esc_html__('Invalid Checkout Session', 'gravityformsstripe') . ': ' . $session->get_error_message());
					}

					$entry = $this->get_entry_by_session_id($session_id, $action, $event);
					if (is_wp_error($entry)) {
						$this->log_error(__METHOD__ . '(): A Stripe API error occurs for session ' . $session_id . ' ' . $entry->get_error_message());

						return $entry;
					}

					$action['type'] = 'fail_payment';
					$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/amount_total'), $entry['currency']);
					$action['entry_id'] = $entry['id'];

					$this->log_debug(__METHOD__ . "(): Stripe Checkout session ' . $session_id . ' async payment has failed. Webhook: {$type}.");

					break;

				case 'payment_intent.succeeded':
					$payment_intent_id = rgars($event, 'data/object/id');
					$entries = GFAPI::get_entries(
						null,
						array(
							'field_filters' => array(
								array(
									'key' => 'payment_element_intent_id',
									'value' => $payment_intent_id,
								),
							),
						)
					);

					if (empty($entries)) {
						$action['abort_callback'] = true;

					} elseif (rgar($entries[0], 'payment_status') !== 'Processing') {
						// If the entry is not an asynchronous payment, webhook events should be ignored.

						$this->log_debug(__METHOD__ . '(): This is not an async payment, aborting as it will be already handled by the direct flow.');
						$action['abort_callback'] = true;

					} else {

						$this->log_debug(__METHOD__ . '(): Current Entry Status:' . rgar($entries[0], 'payment_status'));
						$action = $this->get_payment_element_handler()->complete_processing_entry($entries[0], $action, $event);
					}

					break;

				case 'payment_intent.payment_failed':
					$payment_intent_id = rgars($event, 'data/object/id');
					$entries = GFAPI::get_entries(
						null,
						array(
							'field_filters' => array(
								array(
									'key' => 'payment_element_intent_id',
									'value' => $payment_intent_id,
								),
							),
						)
					);

					if (empty($entries)) {
						$action['abort_callback'] = true;
					} else {

						$entry = $entries[0];
						$action['type'] = 'fail_payment';
						$action['amount'] = $this->get_amount_import(rgars($event, 'data/object/amount'), $entry['currency']);
						$action['entry_id'] = $entry['id'];
					}

					break;
			}

        }

        if ( has_filter( 'gform_stripe_webhook' ) ) {
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_webhook.' );

			/**
			 * Enable support for custom webhook events.
			 *
			 * @since 1.0.0
			 *
			 * @param array         $action An associative array containing the event details.
			 * @param \Stripe\Event $event  The Stripe event object for the webhook which was received.
			 */
			$action = apply_filters( 'gform_stripe_webhook', $action, $event );
		}

		if ( rgempty( 'entry_id', $action ) ) {
			return false;
		}

		return $action;

	}

	/**
	 * Gets an entry associated with the specified Stripe Checkout session id.
	 *
	 * @since 5.0
	 *
	 * @param int   $session_id The Session Id.
	 * @param array $action     The webhook action array.
	 * @param array $event      The webhook event array.
	 *
	 * @return array|WP_Error Returns the entry associated with the Stripe Checkout session, or a WP_Error
	 */
	private function get_entry_by_session_id( $session_id, $action, $event ) {
		$entries = GFAPI::get_entries(
			null,
			array(
				'field_filters' => array(
					array(
						'key'   => 'stripe_session_id',
						'value' => $session_id,
					),
				),
			)
		);

		if ( empty( $entries ) ) {
			$action['session_id'] = $session_id;
			return $this->get_entry_not_found_wp_error( 'session', $action, $event );
		}
		$entry = $entries[0];

		if ( ! $this->is_valid_entry_for_callback( $entry ) ) {
			return $this->get_wrong_feed_wp_error( $entry['id'] );
		}

		return $entry;
	}

	/**
	 * Determines if the supplied entry should be processed by the current callback.
	 *
	 * @since 3.9
	 *
	 * @param array $entry The entry for the webhook event being processed.
	 *
	 * @return bool
	 */
	public function is_valid_entry_for_callback( $entry ) {
		$feed_id = $this->get_callback_feed_id();
		if ( empty( $feed_id ) ) {
			return true;
		}

		return rgar( $this->get_payment_feed( $entry ), 'id' ) === $feed_id;
	}

	/**
	 * Returns the WP_Error which will be used to terminate the callback when the entry was processed by a different feed.
	 *
	 * @since 3.9
	 *
	 * @param int $entry_id The ID of the entry for the current callback.
	 *
	 * @return WP_Error
	 */
	public function get_wrong_feed_wp_error( $entry_id ) {
		return new WP_Error(
			'wrong_feed_for_entry',
			sprintf( __( 'Entry %d was not processed by feed %d. Webhook cannot be processed.', 'gravityformsstripe' ), $entry_id, $_GET['fid'] ),
			array( 'status_header' => 200 )
		);
	}

	/**
	 * Get the WP_Error to be returned when the entry is not found.
	 *
	 * @since 2.5.1
	 *
	 * @param string        $type   The type to be included in the error message and when getting the id: transaction or subscription.
	 * @param array         $action An associative array containing the event details.
	 * @param \Stripe\Event $event  The Stripe event object for the webhook which was received.
	 *
	 * @return WP_Error
	 */
	public function get_entry_not_found_wp_error( $type, $action, $event ) {
		$message     = sprintf( __( 'Entry for %s id: %s was not found. Webhook cannot be processed.', 'gravityformsstripe' ), $type, rgar( $action, $type . '_id' ) );
		$status_code = 200;

		/**
		 * Enables the status code for the entry not found WP_Error to be overridden.
		 *
		 * @since 2.5.1
		 *
		 * @param int           $status_code The status code. Default is 200.
		 * @param array         $action      An associative array containing the event details.
		 * @param \Stripe\Event $event       The Stripe event object for the webhook which was received.
		 */
		$status_code = apply_filters( 'gform_stripe_entry_not_found_status_code', $status_code, $action, $event );

		return new WP_Error( 'entry_not_found', $message, array( 'status_header' => $status_code ) );
	}

	/**
	 * @var \Stripe\Event The Stripe event object for the webhook which was received. Used for caching in get_webhook_event().
	 *
	 * @since 5.2
	 */
	private static $webhook_event;

	/**
	 * Retrieve the Stripe Event for the received webhook.
	 *
	 * @since 2.8   Added support for webhooks per feed.
	 * @since 2.3.1
	 *
	 * @return bool|array|WP_Error|\Stripe\Event
	 */
	public function get_webhook_event() {

		// Abort early if webhook isn't valid
		if ( ! $this->is_callback_valid() ) {
			return false;
		}

		// If the webhook event has already been retrieved, return it.
		if ( ! empty( self::$webhook_event ) ) {
			$this->log_debug( __METHOD__ . '(): Returning cached webhook event.' );

			return self::$webhook_event;

		}

		$body     = @file_get_contents( 'php://input' );
		$response = json_decode( $body, true );

		if ( empty( $response ) ) {
			return false;
		}

		$mode            = rgempty( 'livemode', $response ) ? 'test' : 'live';
		$feed_id         = $this->get_callback_feed_id();
		$feed            = ! empty( $feed_id ) ? $this->get_feed( $feed_id ) : null;
		$settings        = ! empty( $feed ) ? $feed['meta'] : null;
		$endpoint_secret = $this->get_webhook_signing_secret( $mode, $settings );

		$this->log_debug( __METHOD__ . sprintf( '(): Processing %s mode event%s.', $mode, $settings ? " for feed (#{$feed_id} - {$settings['feedName']})" : '' ) );

		$event_id            = rgar( $response, 'id' );
		$is_test_event       = 'evt_00000000000000' === $event_id;
		$is_playwright_event = str_starts_with( $event_id, 'evt_playwright' ) && $mode === 'test';


		if ( $is_playwright_event ) {
			$event = $this->get_simulated_event( $response );

		} elseif ( empty( $endpoint_secret ) && ! $is_test_event ) {

			// Use the legacy method for getting the event.
			$event = $this->get_stripe_event( $event_id, $mode );

		} else {

			$this->include_stripe_api( $mode, $settings );
			$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
			$event      = $this->api->construct_event( $body, $sig_header, $endpoint_secret );
		}

		if ( is_wp_error( $event ) ) {
			$error_message = $event->get_error_message();

			$this->log_error( __METHOD__ . '(): Unable to retrieve Stripe Event object. ' . $error_message );
			$message = __( 'Invalid request. Webhook could not be processed.', 'gravityformsstripe' ) . ' ' . $error_message;

			return new WP_Error( 'invalid_request', $message, array( 'status_header' => 400 ) );
		}

		if ( $is_test_event ) {
			return new WP_Error( 'test_webhook_succeeded', __( 'Test webhook succeeded. Your Stripe Account and Stripe Add-On are configured correctly to process webhooks.', 'gravityformsstripe' ), array( 'status_header' => 200 ) );
		}

		self::$webhook_event = $event;

		return $event;
	}

	/**
	 * Get the feed ID from the callback URL. Supports two URL formats: ?callback=gravityformstripe&fid=1234 and ?stripe_callback=1234
	 *
	 * @since 6.0
	 *
	 * @return int The feed ID.
	 */
	public function get_callback_feed_id() {
		return rgget('fid') ? intval( rgget( 'fid' ) ) : intval( rgget( 'stripe_callback' ) );
	}

	/**
	 * Check if the callback is valid. Supports two URL formats: ?callback=gravityformstripe&fid=1234 and ?stripe_callback=1234
	 *
	 * @since 6.0
	 *
	 * @return bool True if the callback is valid, false otherwise.
	 */
	public function is_callback_valid() {
		if ( rgget( 'callback' ) == $this->get_slug() || isset( $_GET['stripe_callback'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Generate the url Stripe webhooks should be sent to.
	 *
	 * @since  2.8     Added webhooks endpoint for feeds.
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::get_webhooks_section_description()
	 *
	 * @param int $feed_id The feed id.
	 *
	 * @return string The webhook URL.
	 */
	public function get_webhook_url( $feed_id = null ) {

		$url = home_url( '/', 'https' ) . '?callback=' . $this->_slug;

		if ( ! rgblank( $feed_id ) ) {
			$url .= '&fid=' . $feed_id;
		}

		return $url;

	}

	/**
	 * Helper to check that webhooks are enabled.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::can_create_feed()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return bool True if webhook is enabled. False otherwise.
	 */
	public function is_webhook_enabled() {

		return $this->get_plugin_setting( 'webhooks_enabled' ) == true;

	}

	// # HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Retrieve the specified api key.
	 *
	 * @since   Unknown
	 * @since   2.8     Add $settings param.
	 *
	 * @access  public
	 *
	 * @used-by GFStripe::get_publishable_api_key()
	 * @used-by GFStripe::get_secret_api_key()
	 * @uses    GFStripe::get_query_string_api_key()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFStripe::get_api_mode()
	 * @uses    GFAddOn::get_setting()
	 *
	 * @param string      $type    The type of key to retrieve.
	 * @param null|string $mode    The API mode; live or test.
	 * @param null|int    $settings The current settings.
	 *
	 * @return string
	 */
	public function get_api_key( $type = 'secret', $mode = null, $settings = null ) {

		// Check for api key in query first; user must be an administrator to use this feature.
		$api_key = $this->get_query_string_api_key( $type );
		if ( $api_key && current_user_can( 'update_core' ) ) {
			return $api_key;
		}

		if ( ! isset( $settings ) ) {
			$settings = $this->get_plugin_settings();

			if ( ! $mode ) {
				// Get API mode.
				$mode = $this->get_api_mode( $settings );
			}
		}

		// Get API key based on current mode and defined type.
		$setting_key = "{$mode}_{$type}_key";
		$api_key     = $this->get_setting( $setting_key, '', $settings );

		return $api_key;

	}

	/**
	 * Helper to implement the gform_stripe_api_mode filter so the api mode can be overridden.
	 *
	 * @since  Unknown
	 * @since  2.8     Added $feed_id param.
	 *
	 * @access public
	 *
	 * @used-by GFStripe::get_api_key()
	 * @used-by GFStripe::callback()
	 * @used-by GFStripe::can_create_feed()
	 *
	 * @param array $settings The plugin settings.
	 * @param int   $feed_id  Feed ID.
	 *
	 * @return string $api_mode Either live or test.
	 */
	public function get_api_mode( $settings, $feed_id = null ) {
		// Set from POST value.
		if ( rgpost( 'access_token' ) ) {
			$api_mode = ( rgpost( 'livemode' ) === true ) ? 'live' : 'test';
		} else {
			// If the provided settings array has no api_mode or an empty value, for example in the case of an unsaved feed, use the plugin settings api mode.
			$api_mode = rgar( $settings, 'api_mode', $this->get_plugin_setting( 'api_mode' ) );

			// If no api_mode is set, default to test.
			if ( empty( $api_mode ) ) {
				$api_mode = 'test';
			}
		}

		/**
		 * Filters the API mode.
		 *
		 * @since 1.10.1
		 * @since 2.8   Added $feed_id param.
		 *
		 * @param string $api_mode The API mode.
		 * @param int    $feed_id  Feed ID.
		 */
		return apply_filters( 'gform_stripe_api_mode', $api_mode, $feed_id );

	}

	/**
	 * Retrieve the specified api key from the query string.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::get_api_key()
	 *
	 * @param string $type The type of key to retrieve. Defaults to 'secret'.
	 *
	 * @return string The result of the query string.
	 */
	public function get_query_string_api_key( $type = 'secret' ) {

		return rgget( $type );

	}

	/**
	 * Retrieve the publishable api key.
	 *
	 * @since   Unknown
	 * @since   2.8     Added $settings param.
	 *
	 * @access  public
	 *
	 * @used-by GFStripe::register_init_scripts()
	 * @uses    GFStripe::get_api_key()
	 *
	 * @param array|null $settings The current settings.
	 *
	 * @return string The publishable (public) API key.
	 */
	public function get_publishable_api_key( $settings = null ) {

		return $this->get_api_key( 'publishable', $this->get_api_mode( $settings ), $settings );

	}

	/**
	 * Retrieve the secret api key.
	 *
	 * @since   2.8     Added $settings param.
	 * @since   Unknown
	 * @since   2.8     Added $settings param.
	 *
	 * @access  public
	 *
	 * @used-by GFStripe::include_stripe_api()
	 * @uses    GFStripe::get_api_key()
	 *
	 * @param null|string $mode    The API mode; live or test.
	 * @param null|array  $settings The current settings.
	 *
	 * @return string The secret API key.
	 */
	public function get_secret_api_key( $mode = null, $settings = null ) {

		if ( empty( $settings ) ) {
			$settings = $this->get_plugin_settings();
		}

		if ( empty( $mode ) ) {
			$mode = $this->get_api_mode( $settings );
		}

		return $this->get_api_key( 'secret', $mode, $settings );

	}

	/**
	 * Retrieve the webhook signing secret for the specified API mode.
	 *
	 * @since 2.3.1
	 * @since 2.8   Added $settings param.
	 *
	 * @param string     $mode The API mode; live or test.
	 * @param array|null $settings The settings.
	 *
	 * @return string
	 */
	public function get_webhook_signing_secret( $mode, $settings = null ) {

		if ( empty( $settings ) ) {
			$signing_secret = $this->get_plugin_setting( $mode . '_signing_secret' );

			/**
			 * Override the webhook signing secret for the specified API mode.
			 *
			 * @param string     $signing_secret The signing secret to be used when validating received webhooks.
			 * @param string     $mode           The API mode; live or test.
			 * @param GFStripe   $gfstripe       GFStripe class object
			 * @param array|null $settings The settings.
			 *
			 * @since 2.3.1
			 */
			return apply_filters( 'gform_stripe_webhook_signing_secret', $signing_secret, $mode, $this );
		} else {
			return rgar( $settings, $mode . '_signing_secret' );
		}

	}

	/**
	 * Helper to check that Stripe Checkout is enabled.
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @used-by GFStripe::scripts()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return bool True if Stripe Checkout is enabled. False otherwise.
	 */
	public function is_stripe_checkout_enabled() {

		return $this->get_plugin_setting( 'checkout_method' ) === 'stripe_checkout' && version_compare( GFFormsModel::get_database_version(), '2.4-beta-1', '>=' );

	}

	/**
	 * Get auth token data from settings.
	 *
	 * @since 2.8
	 *
	 * @param array  $settings Settings.
	 * @param string $mode API mode.
	 *
	 * @return array
	 */
	public function get_auth_token( $settings, $mode ) {
		return rgar( $settings, $mode . '_auth_token' );
	}

	/**
	 * Check if Stripe authentication is valid.
	 *
	 * @since 2.8
	 * @since 3.3 Fix PHP fatal error thrown when deleting test data.
	 *
	 * @param array  $settings Settings.
	 * @param string $mode API mode.
	 *
	 * @return bool|\Stripe\Account
	 */
	public function is_stripe_auth_valid( $settings, $mode = null ) {
		if ( empty( $mode ) ) {
			$mode = $this->get_api_mode( $settings );
		}

		if ( rgar( $settings, "{$mode}_publishable_key_is_valid" ) && rgar( $settings, "{$mode}_secret_key_is_valid" ) ) {
			$result = $this->get_stripe_account( $settings, $mode );

			if ( ! is_wp_error( $result ) ) {
				return $result;
			}

			$this->log_error( sprintf( '%s(): Unable to get Stripe account info; %s', __METHOD__, $result->get_error_message() ) );
		}

		return false;
	}

	/**
	 * Get Stripe user id.
	 *
	 * @since 2.8
	 *
	 * @param array       $settings Settings.
	 * @param null|string $mode API mode.
	 *
	 * @return string
	 */
	public function get_stripe_user_id( $settings, $mode = null ) {
		if ( empty( $mode ) ) {
			$mode = $this->get_api_mode( $settings );
		}

		$auth_token = $this->get_auth_token( $settings, $mode );

		if ( ! rgempty( 'stripe_user_id', $auth_token ) ) {
			return rgar( $auth_token, 'stripe_user_id' );
		}

		return '';
	}

	/**
	 * Check if Stripe Connect is enabled. The current Stripe Connect must be disconnected then the filter can work.
	 *
	 * @since 2.8
	 *
	 * @return mixed|void
	 */
	public function is_stripe_connect_enabled() {
		$settings               = $this->get_plugin_settings();
		$stripe_user_id         = $this->get_stripe_user_id( $settings );
		$stripe_connect_enabled = true;

		if ( rgblank( $stripe_user_id ) ) {
			/**
			 * If enable Stripe connect.
			 *
			 * @param bool $stripe_connect_enabled Whether to enable legacy connect.
			 *
			 * @since 2.8
			 */
			$stripe_connect_enabled = apply_filters( 'gform_stripe_connect_enabled', $stripe_connect_enabled );
		}

		return $stripe_connect_enabled;
	}

	/**
	 * Check if the current feed is Stripe Connect enabled.
	 *
	 * @since 2.8
	 *
	 * @param int $feed_id Feed ID.
	 *
	 * @return bool
	 */
	public function is_feed_stripe_connect_enabled( $feed_id = null ) {
		$feed = ( empty( $feed_id ) || ! is_numeric( $feed_id ) ) ? $this->get_current_feed() : $this->get_feed( $feed_id );

		if ( ! empty( $feed['meta'] ) && ! rgblank( $this->get_stripe_user_id( $feed['meta'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get capture method (automatic or manual) for the payment intent API.
	 *
	 * @since 3.3
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return string
	 */
	public function get_capture_method( $feed, $submission_data, $form, $entry ) {
		/**
		 * Allow authorization only transactions by preventing the capture request from being made after the entry has been saved.
		 *
		 * @since 2.1.0
		 *
		 * @param bool  $result          Defaults to false, return true to prevent payment being captured.
		 * @param array $feed            The feed object currently being processed.
		 * @param array $submission_data The customer and transaction data.
		 * @param array $form            The form object currently being processed.
		 * @param array $entry           The entry object currently being processed.
		 */
		$authorization_only = apply_filters( 'gform_stripe_charge_authorization_only', false, $feed, $submission_data, $form, $entry );

		if ( $authorization_only ) {
			$this->log_debug( __METHOD__ . '(): The gform_stripe_charge_authorization_only filter was used to prevent capture.' );

			return 'manual';
		}

		return 'automatic';
	}

	/**
	 * Get capture method (automatic or manual) for the payment element.
	 *
	 * @since 5.0
	 *
	 * @param array $form The form object currently being processed.
	 * @param array $feed The feed object currently being processed.
	 *
	 * @return string Returns "automatic" to immediately capture the payment or "manual" to authorize the payment and capture it later.
	 */
	public function get_payment_element_capture_method( $form, $feed ) {

		/**
		 * Allow authorization only transactions by preventing the capture request from being made after the entry has been saved.
		 *
		 * @since 2.1.0
		 *
		 * @param bool  $result Defaults to false, return true to prevent payment being captured.
		 * @param array $form   The form object currently being processed.
		 * @param array $feed   The feed object currently being processed.
		 */
		$authorization_only = apply_filters( 'gform_stripe_payment_element_authorization_only', false, $form, $feed );

		if ( $authorization_only ) {
			$this->log_debug( __METHOD__ . '(): The gform_stripe_payment_element_authorization_only filter was used to prevent capture tracking id:' . md5( rgpost( 'state_' . rgar( $form, 'id' ) ) ) );

			return 'manual';
		}

		return 'automatic';
	}


	/**
	 * Revoke token and remove them from Settings.
	 *
	 * @since  2.8
	 */
	public function ajax_deauthorize() {
		check_ajax_referer( 'gf_stripe_ajax', 'nonce' );

		if ( ! GFCommon::current_user_can_any( $this->_capabilities_settings_page ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Access denied.', 'gravityformsstripe' ) ) );
		}

		$feed_id  = intval( rgpost( 'fid' ) );
		$api_mode = sanitize_text_field( rgpost( 'mode' ) );

		if ( $feed_id ) {
			$settings = $this->get_feed( $feed_id );
			$settings = $settings['meta'];
		} else {
			$settings = $this->get_plugin_settings();
		}

		if ( rgpost( 'scope' ) === 'account' ) {
			$stripe_user_id = $this->get_stripe_user_id( $settings, $api_mode );
			if ( ! rgblank( $stripe_user_id ) ) {
				// Call API to revoke access token.
				$response      = wp_remote_get( add_query_arg( array(
					'stripe_user_id' => $stripe_user_id,
					'mode'           => $api_mode,
				), $this->get_gravity_api_url( '/auth/stripe/deauthorize' ) ) );
				$response_code = wp_remote_retrieve_response_code( $response );

				if ( $response_code === 200 ) {
					$auth_payload = json_decode( wp_remote_retrieve_body( $response ), true );
					$auth_payload = json_decode( base64_decode( $auth_payload['auth_payload'] ), true );
					if ( rgar( $auth_payload, 'stripe_user_id' ) === $stripe_user_id ) {
						// Log that we revoked the access token.
						$this->log_debug( __METHOD__ . '(): Stripe access token revoked.' );
					}
				} else {
					// Log that token cannot be revoked.
					$this->log_error( __METHOD__ . '(): Unable to revoke token at Stripe.' );
				}
			}
		}

		// Remove access token from settings.
		if ( ! rgempty( "{$api_mode}_auth_token", $settings ) ) {
			unset( $settings[ "{$api_mode}_auth_token" ] );
		}
		unset( $settings[ "{$api_mode}_secret_key" ] );
		unset( $settings[ "{$api_mode}_secret_key_is_valid" ] );
		unset( $settings[ "{$api_mode}_publishable_key" ] );
		unset( $settings[ "{$api_mode}_publishable_key_is_valid" ] );
		unset( $settings[ "{$api_mode}_signing_secret" ] );
		unset( $settings['webhooks_enabled'] );

		// Call parent method because we don't want to falsely add auth_token back.
		if ( $feed_id ) {
			parent::save_feed_settings( $feed_id, rgpost( 'id' ), $settings );
		} else {
			parent::update_plugin_settings( $settings );
		}

		wp_send_json_success();
	}

	/**
	 * Retrieve the first part of the subscription's entry note.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::callback()
	 * @uses    GFAPI::get_entry()
	 * @uses    GFPaymentAddOn::get_payment_feed()
	 *
	 * @param int $entry_id The ID of the entry currently being processed.
	 *
	 * @return string The payment note. Escaped.
	 */
	public function get_captured_payment_note( $entry_id ) {

		// Get feed for entry.
		$entry = GFAPI::get_entry( $entry_id );
		$feed  = $this->get_payment_feed( $entry );

		// Define note based on if setup fee is enabled.
		if ( rgars( $feed, 'meta/setupFee_enabled' ) ) {
			$note = esc_html__( 'Setup fee has been paid.', 'gravityformsstripe' );
		} else {
			$note = esc_html__( 'Trial has been paid.', 'gravityformsstripe' );
		}

		return $note;
	}

	/**
	 * Retrieve the labels for the various card types.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::register_init_scripts()
	 * @uses    GFCommon::get_card_types()
	 *
	 * @return array The card labels available.
	 */
	public function get_card_labels() {

		// Get credit card types.
		$card_types  = GFCommon::get_card_types();

		// Initialize credit card labels array.
		$card_labels = array();

		// Loop through card types.
		foreach ( $card_types as $card_type ) {

			// Add card label for card type.
			$card_labels[ $card_type['slug'] ] = $card_type['name'];

		}

		return $card_labels;

	}

	/**
	 * Get the slug for the card type returned by Stripe.js
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::pre_validation()
	 * @uses    GFCommon::get_card_types()
	 *
	 * @param string $type The possible types are "Visa", "MasterCard", "American Express", "Discover", "Diners Club", and "JCB" or "Unknown".
	 *
	 * @return string
	 */
	public function get_card_slug( $type ) {

		// If type is defined, attempt to get card slug.
		if ( $type ) {

			// Get card types.
			$card_types = GFCommon::get_card_types();

			// Loop through card types.
			foreach ( $card_types as $card ) {

				// If the requested card type is equal to the current card's name, return the slug.
				if ( rgar( $card, 'name' ) === $type ) {
					return rgar( $card, 'slug' );
				}

			}

		}

		return $type;

	}

	/**
	 * Populate the $_POST with the last four digits of the card number and card type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFPaymentAddOn::$is_payment_gateway
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 *
	 * @param array $form Form object.
	 */
	public function populate_credit_card_last_four( $form ) {

		if (
			! $this->is_payment_gateway
			|| (
				$this->is_stripe_checkout_enabled()
				&& ! $this->has_credit_card_field( $form )
				&& ! $this->has_stripe_card_field( $form )
			)
		) {
			return;
		}

		if ( $this->has_stripe_card_field( $form ) ) {
			$cc_field = $this->get_stripe_card_field( $form );
		} elseif ( $this->has_credit_card_field( $form ) ) {
			$cc_field = $this->get_credit_card_field( $form );
		}

		$_POST[ 'input_' . $cc_field->id . '_1' ] = $this->is_payment_element_enabled( $form ) ? rgpost( 'stripe_credit_card_last_four' ) : 'XXXXXXXXXXXX' . rgpost( 'stripe_credit_card_last_four' );
		$_POST[ 'input_' . $cc_field->id . '_4' ] = rgpost( 'stripe_credit_card_type' );

	}

	/**
	 * Add credit card warning CSS class for the Stripe Card field.
	 *
	 * @since 2.6
	 *
	 * @param string   $css_class CSS classes.
	 * @param GF_Field $field Field object.
	 * @param array    $form Form array.
	 *
	 * @return string
	 */
	public function stripe_card_field_css_class( $css_class, $field, $form ) {
		if ( GFFormsModel::get_input_type( $field ) === 'stripe_creditcard' && ! GFCommon::is_ssl() ) {
			$css_class .= ' gfield_creditcard_warning';
		}

		return $css_class;
	}

	/**
	 * Allows the modification of submitted values of the Stripe Card field before the draft submission is saved.
	 *
	 * @since 2.6
	 *
	 * @param array $submitted_values The submitted values.
	 * @param array $form             The Form object.
	 *
	 * @return array
	 */
	public function stripe_card_submission_value_pre_save( $submitted_values, $form ) {
		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'stripe_creditcard' ) {
				unset( $submitted_values[ $field->id ] );
			}
		}

		return $submitted_values;
	}

	/**
	 * Populate Stripe Checkout response in a hidden field. Deprecated in 3.0 since we upgraded to use the new
	 * Stripe Checkout.
	 *
	 * @deprecated 3.0
	 *
	 * @since 2.6
	 *
	 * @param string $form The form tag.
	 *
	 * @return string
	 */
	public function populate_stripe_response( $form ) {

		// If a Stripe response exists, populate it to a hidden field.
		if ( $this->get_stripe_js_response() ) {
			$form .= '<input type="hidden" name="stripe_response" id="gf_stripe_response" value="' . esc_attr( rgpost( 'stripe_response' ) ) . '" />';
		}

		return $form;
	}

	/**
	 * Add JS call to finish Stripe Checkout process. Hook to `gform_after_submission` action.
	 *
	 * @since 3.0
	 *
	 * @param array $entry The current lead.
	 * @param array $form  The form object.
	 */
	public function stripe_checkout_redirect_scripts( $entry, $form ) {
		if ( ! $this->is_payment_gateway ) {
			return;
		}

		$session_id = gform_get_meta( $entry['id'], 'stripe_session_id' );
		$feed       = $this->current_feed;

		if ( ! empty( $session_id ) && ! empty( $feed ) ) {
			if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
				$settings = $feed['meta'];
			} else {
				$settings = $this->get_plugin_settings();
			}

			$this->log_debug( __METHOD__ . "(): Outputting scripts for entry #{$entry['id']} for session {$session_id}." );

			// Adding the GF_AJAX_POSTBACK wrapper so AJAX embedded forms can copy the script to the parent (top-level)
			// frame, Safari can only perform the redirection from a top-level frame.
			// For regular embedded forms the wrapper is harmless.
			?>
			<script src="https://js.stripe.com/v3"></script>
			<div class="GF_AJAX_POSTBACK">
				<p class="gform_stripe_checkout_message"><?php esc_html_e( 'You\'re being redirected to the hosted Checkout page on Stripe...', 'gravityformsstripe' ); ?></p>
				<script>
                    var stripe = Stripe("<?php echo $this->get_publishable_api_key( $settings ); ?>");

                    stripe.redirectToCheckout({
                        sessionId: "<?php echo $session_id; ?>"
                    }).then(function (result) {
                        console.log(result);
                    });
                </script>
			</div>
			<?php
			exit();
		}
	}

	/**
	 * Add the value of the trialPeriod property to the order data which is to be included in the $submission_data.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::get_submission_data()
	 * @uses    GFPaymentAddOn::get_order_data()
	 *
	 * @param array $feed  The feed currently being processed.
	 * @param array $form  The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 *
	 * @return array The order data found.
	 */
	public function get_order_data( $feed, $form, $entry ) {

		$order_data          = parent::get_order_data( $feed, $form, $entry );
		$order_data['trial'] = rgars( $feed, 'meta/trialPeriod' );

		return $order_data;

	}

	/**
	 * Return the description to be used with the Stripe charge.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::capture()
	 *
	 * @param array $entry           The entry object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $feed            The feed object currently being processed.
	 *
	 * @return string
	 */
	public function get_payment_description( $entry, $submission_data, $feed ) {

		// Charge description format:
		// Entry ID: 123, Products: Product A, Product B, Product C

		$strings = array();

		if ( $entry['id'] ) {
			$strings['entry_id'] = sprintf( esc_html__( 'Entry ID: %d', 'gravityformsstripe' ), $entry['id'] );
		}

		$strings['products'] = sprintf(
			_n( 'Product: %s', 'Products: %s', count( $submission_data['line_items'] ), 'gravityformsstripe' ),
			implode( ', ', wp_list_pluck( $submission_data['line_items'], 'name' ) )
		);

		$description = implode( ', ', $strings );

		/**
		 * Allow the charge description to be overridden.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description     The charge description.
		 * @param array  $strings         Contains the Entry ID and Products. The array which was imploded to create the description.
		 * @param array  $entry           The entry object currently being processed.
		 * @param array  $submission_data The customer and transaction data.
		 * @param array  $feed            The feed object currently being processed.
		 */
		return apply_filters( 'gform_stripe_charge_description', $description, $strings, $entry, $submission_data, $feed );
	}

	/**
	 * Retrieve the subscription line item from from the Stripe response.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::capture()
	 *
	 * @param \Stripe\Event $response The Stripe webhook response.
	 *
	 * @return bool|array The subscription line items. Returns false if nothing found.
	 */
	public function get_subscription_line_item( $response ) {

		$lines = rgars( $response, 'data/object/lines/data' );

		foreach ( $lines as $line ) {
			if ( 'subscription' === $line['type'] ) {
				return $line;
			}
		}

		return false;
	}

	/**
	 * Generate the subscription plan id.
	 *
	 * @since   2.3.4 Added the $currency param.
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::subscribe()
	 *
	 * @param array     $feed              The feed object currently being processed.
	 * @param float|int $payment_amount    The recurring amount.
	 * @param int       $trial_period_days The number of days the trial should last.
	 * @param string    $currency          The currency code for the entry being processed.
	 *
	 * @return string The subscription plan ID, if found.
	 */
	public function get_subscription_plan_id( $feed, $payment_amount, $trial_period_days, $currency = '' ) {

		$safe_subscription_name = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( rgars( $feed, 'meta/processed_subscription_name', $feed['meta']['feedName'] ) ) );
		$safe_billing_cycle     = $feed['meta']['billingCycle_length'] . $feed['meta']['billingCycle_unit'];
		$safe_trial_period      = $trial_period_days ? 'trial' . $trial_period_days . 'days' : '';
		$safe_payment_amount    = $this->get_amount_export( $payment_amount, $currency );

		/*
		 * Only include the currency code in the plan id when the entry currency does not match the plugin currency.
		 * Ensures the majority of plans created before this change will continue to be used.
		 * https://stripe.com/docs/subscriptions/plans#working-with-local-currencies
		*/
		if ( ! empty( $currency ) && $currency === GFCommon::get_currency() ) {
			$currency = '';
		}

		$plan_id = implode( '_', array_filter( array( $safe_subscription_name, $feed['id'], $safe_billing_cycle, $safe_trial_period, $safe_payment_amount, $currency ) ) );

		$this->log_debug( __METHOD__ . '(): ' . $plan_id );

		return $plan_id;

	}

	/**
	 * Enables use of the gform_stripe_field_value filter to override the field value.
	 *
	 * @since 2.1.1 Making the $meta_key parameter available to the gform_stripe_field_value filter.
	 *
	 * @used-by GFAddOn::get_field_value()
	 *
	 * @param string $field_value The field value to be filtered.
	 * @param array  $form        The form currently being processed.
	 * @param array  $entry       The entry currently being processed.
	 * @param string $field_id    The ID of the Field currently being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {
		$meta_key = $this->_current_meta_key;
		$form_id  = $form['id'];

		/**
		 * Allow the mapped field value to be overridden.
		 *
		 * @since 2.1.1 Added the $meta_key parameter.
		 * @since 1.9.10.11
		 *
		 * @param string $field_value The field value to be filtered.
		 * @param array  $form        The form currently being processed.
		 * @param array  $entry       The entry currently being processed.
		 * @param string $field_id    The ID of the Field currently being processed.
		 * @param string $meta_key    The custom meta key currently being processed.
		 */
		$field_value = apply_filters( 'gform_stripe_field_value', $field_value, $form, $entry, $field_id, $meta_key );
		$field_value = apply_filters( "gform_stripe_field_value_{$form_id}", $field_value, $form, $entry, $field_id, $meta_key );
		$field_value = apply_filters( "gform_stripe_field_value_{$form_id}_{$field_id}", $field_value, $form, $entry, $field_id, $meta_key );

		return $field_value;
	}

	/**
	 * Get Stripe Card field for form.
	 *
	 * @since 2.6
	 *
	 * @param array $form Form object. Defaults to null.
	 *
	 * @return boolean
	 */
	public function has_stripe_card_field( $form = null ) {
		// Get form
		if ( is_null( $form ) ) {
			$form = $this->get_current_form();
		}

		return $this->get_stripe_card_field( $form ) !== false;
	}

	/**
	 * Gets Stripe credit card field object.
	 *
	 * @since 2.6
	 *
	 * @param array $form The Form Object.
	 *
	 * @return bool|GF_Field_Stripe_CreditCard The Stripe card field object, if found. Otherwise, false.
	 */
	public function get_stripe_card_field( $form ) {
		$fields = GFAPI::get_fields_by_type( $form, array( 'stripe_creditcard' ) );

		return empty( $fields ) ? false : $fields[0];
	}

	/**
	 * Prepare fields for field mapping in feed settings.
	 *
	 * @since 2.6
	 *
	 * @return array $fields
	 */
	public function billing_info_fields() {
		$fields = array(
			array(
				'name'       => 'address_line1',
				'label'      => __( 'Address', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_line2',
				'label'      => __( 'Address 2', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_city',
				'label'      => __( 'City', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_state',
				'label'      => __( 'State', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_zip',
				'label'      => __( 'Zip', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_country',
				'label'      => __( 'Country', 'gravityformsstripe' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
		);

		return $fields;

	}

	/**
	 * Returns the specified plugin setting.
	 *
	 * @since 2.6.0.1
	 * @since 3.4     Set the default checkout_method to "stripe_elements".
	 *
	 * @param string $setting_name The setting to be returned.
	 *
	 * @return mixed|string
	 */
	public function get_plugin_setting( $setting_name ) {
		$setting = parent::get_plugin_setting( $setting_name );

		if ( ! $setting && $setting_name === 'checkout_method' ) {
			$setting = 'stripe_elements';
		}

		return $setting;
	}

	/***
	 * Force Payment Collection Method as "Stripe Elements" in the add-on settings.
	 *
	 * @since 3.4
	 *
	 * @param string     $setting_name  The field or input name.
	 * @param string     $default_value Optional. The default value.
	 * @param bool|array $settings      Optional. THe settings array.
	 *
	 * @return string|array
	 */
	public function get_setting( $setting_name, $default_value = '', $settings = false ) {
		$setting = parent::get_setting( $setting_name, $default_value, $settings );

		if ( $setting_name === 'checkout_method' && $setting === 'credit_card' ) {
			$setting = 'stripe_elements';
		}

		return $setting;
	}

	/**
	 * Target of gform_before_delete_field hook. Sets relevant payment feeds to inactive when the Stripe Card field is deleted.
	 *
	 * @since 2.6.1
	 *
	 * @param int $form_id ID of the form being edited.
	 * @param int $field_id ID of the field being deleted.
	 */
	public function before_delete_field( $form_id, $field_id ) {
		parent::before_delete_field( $form_id, $field_id );

		$form = GFAPI::get_form( $form_id );
		if ( $this->has_stripe_card_field( $form ) ) {
			$field = $this->get_stripe_card_field( $form );

			if ( is_object( $field ) && $field->id == $field_id ) {
				$feeds = $this->get_feeds( $form_id );
				foreach ( $feeds as $feed ) {
					if ( $feed['is_active'] ) {
						$this->update_feed_active( $feed['id'], 0 );
					}
				}
			}
		}
	}

	/**
	 * Get Gravity API URL.
	 *
	 * @since 2.8
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function get_gravity_api_url( $path = '' ) {
		return ( defined( 'GRAVITY_API_URL' ) ? GRAVITY_API_URL : 'https://gravityapi.com/wp-json/gravityapi/v1' ) . $path;
	}

	/**
	 * Run required routines when upgrading from previous versions of Add-On.
	 *
	 * @since 3.0
	 *
	 * @param string $previous_version Previous version number.
	 */
	public function upgrade( $previous_version ) {
        require_once plugin_dir_path( __FILE__ ) . '/includes/upgrade/class-gf-stripe-upgrade-handler.php';
        $upgrade_handler = new GF_Stripe_Upgrade_Handler( $this );
        $upgrade_handler->upgrade( $previous_version );
	}


	/**
	 * Get post payment actions config.
	 *
	 * @since 3.1
	 *
	 * @param string $feed_slug The feed slug.
	 *
	 * @return array
	 */
	public function get_post_payment_actions_config( $feed_slug ) {

        // Support post payment actions for Stripe Checkout and for refactored payment methods.
		if ( $this->is_stripe_checkout_enabled() || $this->is_refactored_payment_method( $this->get_current_form() ) ) {
			return array(
				'position' => 'before',
				'setting'  => 'conditionalLogic',
                'default_value' => '1',
			);
		}

        return array();
	}

	/**
	 * AJAX helper function to create a payment intent on the server.
	 *
	 * @since 3.3
	 */
	public function create_payment_intent() {
		check_ajax_referer( 'gf_stripe_create_payment_intent', 'nonce' );

		$feed = $this->get_feed( intval( rgpost( 'feed_id' ) ) );

		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		$payment_method = rgpost( 'payment_method' );

		$data = array(
			'payment_method'       => rgar( $payment_method, 'id' ),
			'payment_method_types' => array( 'card' ),
			'amount'               => intval( rgpost( 'amount' ) ),
			'currency'             => sanitize_text_field( rgpost( 'currency' ) ),
			'capture_method'       => 'manual', // Use manual capture by default, because we cannot update the capture_method after the payment intent crated.
			'confirmation_method'  => 'manual',
			'confirm'              => 'false',
		);

		/**
		 * Filter to change the payment intent data before creating it.
		 *
		 * @since 3.5
		 *
		 * @param array $data The payment intent data.
		 * @param array $feed The feed object.
		 */
		$data = apply_filters( 'gform_stripe_payment_intent_pre_create', $data, $feed );
		// We need to check if setup_future_usage has been set
		// https://stripe.com/docs/api/payment_intents/create#create_payment_intent-setup_future_usage
		if ( rgar( $data, 'setup_future_usage' ) === 'off_session' ) {
			$data['confirmation_method'] = 'automatic';
			// https://stripe.com/docs/api/payment_intents/create#create_payment_intent-confirmation_method
		}

		$intent = $this->api->create_payment_intent( $data );

		if ( $intent->status === 'requires_confirmation' ) {
			wp_send_json_success(
				array(
					'id'            => $intent->id,
					'client_secret' => $intent->client_secret,
					'amount'        => $intent->amount,
				)
			);
		} else {
			/* translators: PaymentIntent status. */
			$error = sprintf( esc_html__( 'PaymentIntent status: %s is invalid.', 'gravityformsstripe' ), $intent->status );
			wp_send_json_error( array( 'message' => $error ) );
		}
	}

	/**
	 * AJAX helper function to update a payment intent on the server.
	 *
	 * @since 3.3
	 */
	public function update_payment_intent() {
		check_ajax_referer( 'gf_stripe_create_payment_intent', 'nonce' );

		$feed = $this->get_feed( intval( rgpost( 'feed_id' ) ) );

		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		$payment_intent = sanitize_text_field( rgpost( 'payment_intent' ) );
		$payment_method = rgpost( 'payment_method' );

		$data = array(
			'amount'   => intval( rgpost( 'amount' ) ),
			'currency' => sanitize_text_field( rgpost( 'currency' ) ),
		);

		if ( ! empty( $payment_method ) ) {
			$data['payment_method'] = rgar( $payment_method, 'id' );
		}

		$intent = $this->api->update_payment_intent( $payment_intent, $data );

		if ( $intent->status === 'requires_confirmation' ) {
			wp_send_json_success(
				array(
					'id'            => $intent->id,
					'client_secret' => $intent->client_secret,
					'amount'        => $intent->amount,
				)
			);
		} else {
			/* translators: PaymentIntent status. */
			$error = sprintf( esc_html__( 'PaymentIntent status: %s is invalid.', 'gravityformsstripe' ), $intent->status );
			wp_send_json_error( array( 'message' => $error ) );
		}
	}

	/**
	 * AJAX helper function to capture a payment that has been previously authorized.
	 *
	 * @since 4.2
	 */
	public function ajax_capture_payment() {

		check_ajax_referer( 'gfstripe_capture_payment', 'nonce' );

		$entry_id       = absint( rgpost( 'entry_id' ) );
		$transaction_id = sanitize_text_field( rgpost( 'transaction_id' ) );

		// Make sure we have the right entry.
		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			wp_send_json_error( esc_html__( 'Unable to find entry.', 'gravityformsstripe' ) );
		}

		// Make sure we have a payment feed.
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_payment_feed( $entry, $form );
		if ( is_wp_error( $feed ) ) {
			wp_send_json_error( esc_html__( 'Unable to find payment feed.', 'gravityformsstripe' ) );
		}

		// Including Stripe API.
		$result = $this->include_stripe_api_for_entry( $entry_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( esc_html__( 'Could not initialize Stripe API.', 'gravityformsstripe' ) . ' ' . $result->get_error_message() );
			return;
		}

		$result = $this->capture_payment( $transaction_id );
		if ( ! $result['is_captured'] ) {
			wp_send_json_error( $result['message'] );
			return;
		}

		// Set processing flag so that webhook does not also try to process this transaction.
		update_option( "gform_stripe_capturing_{$transaction_id}", true );

		// Mark authorized payment as Paid in Gravity Forms.
		$this->log_debug( __METHOD__ . '(): Payment has been captured for entry #' . $entry_id . '. Mark it as paid.' );

		// Updating entry. Firing hooks.
		$this->complete_payment(
			$entry,
			array(
				'type'           => 'complete_payment',
				'transaction_id' => $transaction_id,
				'amount'         => $this->get_amount_import( $result['amount'], $entry['currency'] ),
			)
		);

		// For transactions made via Stripe Checkout, run checkout_fulfillment().
		$this->maybe_checkout_fulfillment( $entry );

		// Cleaning up flag since processing is complete.
		delete_option( "gform_stripe_capturing_{$transaction_id}" );

		// Returning success message.
		wp_send_json_success();
	}


	/**
	 * Turn country into two digits for Stripe Elements.
	 *
	 * @since 3.3
	 */
	public function get_country_code() {
		check_ajax_referer( 'gf_stripe_create_payment_intent', 'nonce' );

		$feed            = $this->get_feed( intval( rgpost( 'feed_id' ) ) );
		$billing_country = rgars( $feed, 'meta/billingInformation_address_country' );
		$country         = sanitize_text_field( rgpost( 'country' ) );
		$code            = $country;

		if ( ! empty( $billing_country ) && strlen( $country ) > 2 ) {
			$code = GF_Fields::get( 'address' )->get_country_code( $country );
		}

		wp_send_json_success( array( 'code' => $code ) );
	}

	/**
	 * Refund a payment.
	 *
	 * @since 4.2
	 */
	public function ajax_refund() {
		check_ajax_referer( 'gf_stripe_refund', 'nonce' );

		$transaction_id = sanitize_text_field( wp_unslash( empty( $_POST['transaction_id'] ) ? '' : $_POST['transaction_id'] ) );
		$payment_intent = $this->is_payment_intent( $transaction_id );
		$entry_id       = sanitize_text_field( wp_unslash( empty( $_POST['entry_id'] ) ? '' : $_POST['entry_id'] ) );

		// Make sure we have the right entry.
		$entry    = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to find entry.', 'gravityformsstripe' ) ) );
		}

		// Make sure we have a payment feed.
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_payment_feed( $entry, $form );
		if ( is_wp_error( $feed ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to find payment feed.', 'gravityformsstripe' ) ) );
		}

		// Load the Stripe API library.
		if ( $this->is_feed_stripe_connect_enabled( $feed['id'] ) ) {
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		// Get the payment.
		if ( $payment_intent ) {
			$payment = $this->api->get_payment_intent( $transaction_id );
		} else {
			$payment = $this->api->get_charge( $transaction_id );
		}

		if ( is_wp_error( $payment ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to find payment on Stripe', 'gravityformsstripe' ) ) );
		}

		// Send the refund to Stripe.
		$this->log_debug( __METHOD__ . sprintf( '(): Processing refund for transaction %s for entry #%d.', $transaction_id, $entry['id'] ) );
		$refund = $this->api->create_refund( $transaction_id, $payment_intent );
		if ( is_wp_error( $refund ) ) {
			$this->log_error( __METHOD__ . '(): Unable to refund payment; ' . $refund->get_error_message() );
			wp_send_json_error( array( 'message' => $refund->get_error_message() ) );
		}

		// Save the refund details.
		$action = array(
			'payment_status'   => 'Refunded',
			'transaction_type' => 'refund',
			'transaction_id'   => $refund->id,
			'amount'           => GFCommon::to_money( $this->get_amount_import( $refund->amount ) ),
		);
		$this->refund_payment( $entry, $action );

		wp_send_json_success();
	}

	/**
	 * Alter product feeds payment data before sent with Stripe API.
	 *
	 * The filter was created to support the Charge API in 2.2.2, and updated to support the payment intents API in 3.3.
	 *
	 * @since 3.3
	 *
	 * @param array $payment_data    The properties for the charge to be created.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array
	 */
	public function get_product_payment_data( $payment_data, $feed, $submission_data, $form, $entry ) {

		/**
		 * Allow the charge properties to be overridden before the charge is created by the Stripe API.
		 *
		 * @param array $payment_data The properties for the charge to be created.
		 * @param array $feed The feed object currently being processed.
		 * @param array $submission_data The customer and transaction data.
		 * @param array $form The form object currently being processed.
		 * @param array $entry The entry object currently being processed.
		 *
		 * @since 2.2.2
		 */
		$payment_data = apply_filters( 'gform_stripe_charge_pre_create', $payment_data, $feed, $submission_data, $form, $entry );

		return $payment_data;
	}

	/**
	 * Filter subscription parameters when creating or updating a subscription.
	 *
	 * @since 3.4
	 *
	 * @param array            $subscription_params The subscription parameters.
	 * @param \Stripe\Customer $customer            The Stripe customer object.
	 * @param \Stripe\Plan     $plan                The Stripe plan object.
	 * @param array            $feed                The feed currently being processed.
	 * @param array            $entry               The entry currently being processed.
	 * @param array            $form                The form which created the current entry.
	 * @param int              $trial_period_days   The number of days the trial should last.
	 *
	 * @return array
	 */
	public function get_subscription_params( $subscription_params, $customer, $plan, $feed, $entry, $form, $trial_period_days ) {
		if ( has_filter( 'gform_stripe_subscription_params_pre_update_customer' ) ) {
			/**
			 * Allow the subscription parameters to be overridden before the customer is subscribed to the plan.
			 *
			 * @since 2.3.4
			 * @since 2.5.2 Added the $trial_period_days param.
			 *
			 * @param array            $subscription_params The subscription parameters.
			 * @param \Stripe\Customer $customer            The Stripe customer object.
			 * @param \Stripe\Plan     $plan                The Stripe plan object.
			 * @param array            $feed                The feed currently being processed.
			 * @param array            $entry               The entry currently being processed.
			 * @param array            $form                The form which created the current entry.
			 * @param int              $trial_period_days   The number of days the trial should last.
			 */
			$subscription_params = apply_filters( 'gform_stripe_subscription_params_pre_update_customer', $subscription_params, $customer, $plan, $feed, $entry, $form, $trial_period_days );

			// Backwards compatibility.
			if ( rgar( $subscription_params, 'plan' ) && empty( $subscription_params['items'] ) ) {
				$subscription_params['items'] = array( 'plan' => $plan->id );
				unset( $subscription_params['plan'] );
				$this->log_debug( __METHOD__ . '(): Change subscription parameters "plan" to "items[\'plan\']"' );
			}

			$this->log_debug( __METHOD__ . '(): Subscription parameters for form : "' . rgar( $form, 'title' ) . '" (id: ' . rgar( $form, 'id' ) . ') , feed : "' . rgars( $feed, 'meta/feedName' ) . '" (id: ' . rgar( $feed, 'id' ) . '), tracking id: ' . rgpost( 'tracking_id' ) . ' => ' . print_r( $subscription_params, true ) );
		}

		return $subscription_params;
	}

	/**
	 * Check if rate limits is enabled.
	 *
	 * @since 3.4
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	public function is_rate_limits_enabled( $form_id ) {
		/**
		 * Allow enabling or disable the rate limit check.
		 *
		 * @since 3.4
		 *
		 * @param bool $has_error The default is false.
		 * @param int  $form_id   The form ID.
		 */
		$this->_enable_rate_limits = apply_filters( 'gform_stripe_enable_rate_limits', $this->_enable_rate_limits, $form_id );

		return $this->_enable_rate_limits;
	}

	/**
	 * Update card error count for the current IP.
	 *
	 * @since 3.4
	 *
	 * @param bool $increase_count The default is false.
	 *
	 * @return bool
	 */
	public function get_card_error_count( $increase_count = false ) {
		$ip = GFFormsModel::get_ip();

		if ( empty( $ip ) ) {
			// It can return a comma separated list of IPs; use the first one.
			$ips = explode( ',', rgar( $_SERVER, 'REMOTE_ADDR' ) );
			$ip  = $ips[0];
		}

		$key         = $this->get_slug() . '_card_error_' . wp_hash( $ip );
		$error_count = (int) get_transient( $key );

		if ( $increase_count ) {
			$error_count ++;
			set_transient( $key, $error_count, HOUR_IN_SECONDS );
		}

		return $error_count;
	}

	/**
	 * Check if the current IP has hit the error rate limits.
	 *
	 * @since 3.4
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool|array
	 */
	public function maybe_hit_rate_limits( $form_id ) {
		if ( ! $this->is_rate_limits_enabled( $form_id ) ) {
			return false;
		}

		$error_count     = $this->get_card_error_count();
		$max_error_count = 5;
		if ( $error_count >= $max_error_count ) {
			$this->log_debug( __METHOD__ . '(): The current IP has hit the error rate limit. Block payments from it for an hour' );

			return array(
				'error_message' => esc_html__( 'We are not able to process your payment request at the moment. Please try again later.', 'gravityformsstripe' ),
				'is_success'    => false,
				'is_authorized' => false,
			);
		}

		$this->log_debug( __METHOD__ . '(): The current IP has card errors in total of ' . $error_count . ' times' );

		return false;
	}

	/**
	 * Get the authentication state, which was created from a wp nonce.
	 *
	 * @since 3.5.1
	 *
	 * @return string
	 */
	public function get_authentication_state_action() {
		return 'gform_stripe_authentication_state';
	}

	/**
	 * Retrieves stripe account display name.
	 *
	 * @param \Stripe\Account $stripe_account Stripe account object.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	private function get_stripe_display_name( $stripe_account ) {
		$display_name = rgars( $stripe_account, 'settings/dashboard/display_name' );
		// in some cases it is possible to have an account with any empty display name, stripe call it unnamed account.
		return ! empty( $display_name ) ? $display_name : esc_html__( 'Unnamed account', 'gravityformsstripe' );
	}

	/**
	 * Captures a payment using the appropriate Stripe API
	 *
	 * @param string $transaction_id Stripe transaction ID.
	 *
	 * @since 4.2
	 *
	 * @return array The result of the capture operation in the following format:
	 *      - is_captured (bool) Whether or not the payment is now captured (it could have been captured during this operation or have already been captured before).
	 *      - message (string) Error or success message.
	 */
	private function capture_payment( $transaction_id ) {

		$is_payment_intent = $this->is_payment_intent( $transaction_id );

		$payment_object = $is_payment_intent ? $this->api->get_payment_intent( $transaction_id ) : $this->api->get_charge( $transaction_id );
		$object_label   = $is_payment_intent ? 'payment intent' : 'charge';
		if ( is_wp_error( $payment_object ) ) {
			$this->log_error( __METHOD__ . '(): Cannot retrieve ' . $object_label . ' data from Stripe for transaction_id: ' . $transaction_id . '. error: ' . $payment_object->get_error_message() );

			return array(
				'is_captured' => false,
				'message'     => esc_html__( 'Could not retrieve payment information from Stripe.', 'gravityformsstripe' ),
			);
		}

		// Abort if payment has already been captured.
		if ( $this->is_payment_captured( $payment_object ) ) {
			return array(
				'is_captured'     => true,
				'message'         => esc_html__( 'Payment has already been captured.', 'gravityformsstripe' ),
				'amount'          => $this->get_captured_amount( $payment_object ),
			);
		}

		// Capturing payment.
		$captured_payment_object = $is_payment_intent ? $this->api->capture_payment_intent( $payment_object ) : $this->api->capture_charge( $payment_object );
		$this->log_debug( __METHOD__ . '(): Capturing payment using ' . $object_label . ' API. transaction_id: ' . $transaction_id );

		if ( is_wp_error( $captured_payment_object ) || ! $this->is_payment_captured( $captured_payment_object ) ) {

			$wp_error = is_wp_error( $captured_payment_object ) ? $captured_payment_object : new WP_Error( 'could_not_capture', esc_html__( 'Unable to capture payment', 'gravityformsstripe' ) );
			$this->log_error( __METHOD__ . '(): Cannot capture ' . $object_label . '. transaction_id: ' . $transaction_id . '. error: ' . $wp_error->get_error_message() );

			return array(
				'is_captured' => false,
				'message'     => $wp_error->get_error_message(),
			);
		}

		return array(
			'is_captured'    => true,
			'message'        => esc_html__( 'Payment captured successfully.', 'gravityformsstripe' ),
			'amount'         => $this->get_captured_amount( $captured_payment_object ),
		);
	}

	/**
	 * Returns whether or not the specified payment object (Payment Intent or Charge) has been captured.
	 *
	 * @param array $payment_object A Payment Intent or Charge object.
	 *
	 * @since 4.2
	 *
	 * @return bool True if payment has been captured. False otherwise.
	 */
	private function is_payment_captured( $payment_object ) {
		return $payment_object['object'] == 'payment_intent' ? $payment_object->status === 'succeeded' : $payment_object->captured;
	}

	/**
	 * Returns the captured amount for the specified payment object (Payment Intent or Charge).
	 *
	 * @param array $payment_object A Payment Intent or Charge object.
	 *
	 * @since 4.2
	 *
	 * @return float The amount captured
	 */
	private function get_captured_amount( $payment_object ) {
		return $payment_object['object'] == 'payment_intent' ? $payment_object->amount_received : $payment_object->amount;
	}

	/**
	 * Includes the Stripe API using the global Stripe account configured in settings or the account specific to the Feed associated with the entry.
	 *
	 * @param int $entry_id Current entry ID.
	 *
	 * @since 4.2
	 *
	 * @return bool|WP_Error True if the API could be included. WP_Error otherwise.
	 */
	private function include_stripe_api_for_entry( $entry_id ) {

		// Getting feeds associated with the entry.
		$feed_ids = $this->get_feeds_by_entry( $entry_id );
		if ( empty( $feed_ids ) ) {
			$this->log_error( __METHOD__ . '(): Cannot retrieve Stripe feed for entry_id:  ' . $entry_id );

			return new WP_Error( 'feed_not_found', esc_html__( 'Feed associated with entry could not be found.', 'gravityformsstripe' ) );
		}
		$feed_id = $feed_ids[0];

		// Include Stripe API library.
		if ( $this->is_feed_stripe_connect_enabled( $feed_id ) ) {
			$feed = $this->get_feed( $feed_id );
			$this->include_stripe_api( $this->get_api_mode( $feed['meta'], $feed['id'] ), $feed['meta'] );
		} else {
			$this->include_stripe_api();
		}

		return true;
	}

	/**
	 * Display the "Refund" button in the product details box on the entry page, if needed
	 *
	 * @since 4.2
	 *
	 * @param int   $form_id The id of the form.
	 * @param array $entry   The entry we're viewing.
	 */
	public function maybe_display_refund_button( $form_id, $entry ) {
		if ( $entry['payment_status'] !== 'Paid' || ! $this->is_payment_gateway( $entry['id'] ) || empty( $entry['transaction_id'] ) ) {
			return;
		}

		printf(
			'<p><button href="#" data-tid="%1$s" data-amount="%2$s" data-currency="%3$s" data-lid="%4$s" class="button stripe-refund">%5$s</button><span id="refund_wait_container" style="display: none; margin-left: 5px;"><i class="gficon-gravityforms-spinner-icon gficon-spin"></i>%6$s</span></p>
					<div class="alert_red gform_stripe_refund_alert" style="padding:10px; display:none;"></div>',
			esc_attr( $entry['transaction_id'] ),
			esc_attr( $entry['payment_amount'] ),
			esc_attr( $entry['currency'] ),
			esc_attr( $entry['id'] ),
			esc_html__( 'Refund Payment', 'gravityformsstripe' ),
			esc_html__( 'Sending refund request', 'gravityformsstripe' )
		);
	}

	/**
	 * Helper function that determines if a transaction ID was created by the Payment Intent API
	 *
	 * @param string $transaction_id Current transaction ID.
	 *
	 * @since 4.2
	 *
	 * @return bool Returns true if the specified transaction ID was created by the Payment Intent API. Returns false otherwise.
	 */
	private function is_payment_intent( $transaction_id ) {
		return substr( $transaction_id, 0, 3 ) === 'pi_';
	}

	/**
	 * Remove Cardholder Name as a merge tag option.
	 *
	 * @since 5.3.1
	 *
	 * @return void
	 */
	public function remove_cardholder_name_merge_tag() {
		$search_string = esc_html__( 'Cardholder Name', 'gravityformsstripe' );
		?>
		<script type="application/javascript">
			if ( gform.addFilter ) {
				gform.addFilter('gform_merge_tags', function( mergeTags ) {
					const optional = mergeTags.optional;
					const required = mergeTags.required;

					if ( ! optional.tags.length && ! required.tags.length ) {
						return mergeTags;
					}

					optional.tags = optional.tags.filter( function( item ) {
						return item.tag.indexOf( '<?php echo $search_string; ?>' ) === -1;
					} );

					mergeTags.optional = optional;

					required.tags = required.tags.filter( function( reqItem ) {
						return reqItem.tag.indexOf( '<?php echo $search_string; ?>' ) === -1;
					} );

					mergeTags.required = required;

					return mergeTags;
				});
			}
		</script>
		<?php
	}


	/**
	 * Ensures that feeds are delayed only in supported contexts.
	 *
	 * This version of Stripe has an upgrade routine that sets the delayed feed flag for all forms with the new Stripe Element field.
	 * This works well, but it also presents a risk that we could be in a situation where feeds are marked as delayed in contexts that do not support delayed feeds.
	 * For example, if the Stripe field is changed to Payment Element after the upgrade.
	 * This method returns the $is_delayed flag as false in those cases, ensuring that feeds will be delayed only when feed delay is supported.
	 *
	 * @param bool   $is_delayed Whether the feed is delayed.
	 * @param array  $form       The current form being processed.
	 * @param array  $entry      The current entry being processed.
	 * @param string $slug       The add-on slug for the feed. i.e gravityformshubspot, gravityformsmailchimp
	 *
	 * @return bool             True to delay the feed, false to allow immediate processing.
	 */
	public function maybe_delay_feed_processing( $is_delayed, $form, $entry, $slug )
	{
		// If this is not a Stripe feed, abort
		if ( ! $this->is_payment_gateway( $entry['id'] ) ) {
			return $is_delayed;
		}

		// Delayed feed is supported by stripe checkout and Stripe Element only.
		$is_delayed_feed_supported = $this->is_stripe_checkout_enabled() || $this->has_stripe_card_element( $form );

		// If feed delay is not supported in this context, force it to be disabled (otherwiwse it will create problems).
		if ( ! $is_delayed_feed_supported ) {
			return false;
		}

		// Process feed normally.
		return parent::maybe_delay_feed_processing( $is_delayed, $form, $entry, $slug );
	}
}
