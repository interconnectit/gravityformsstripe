<?php
/**
 * Service Provider for the Elements Service
 *
 * @package Gravity_forms\Gravity_Forms_Stripe\Elements
 */
namespace Gravity_forms\Gravity_Forms_Stripe\Elements;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms_Stripe\Elements\Config\GF_Stripe_Elements_Config;

/**
 * Class Experimental_GF_Elements_Service_Provider
 *
 * Service provider for the Elements Service.
 * NOTE: Do not use this class directly. It is currently in experimental phase and will most likely have breaking changes in the near future.
 */
class Experimental_GF_Elements_Service_Provider extends GF_Service_Provider {

	const STRIPE_ELEMENTS_HANDLER = 'gf_stripe_elements_handler';
	const STRIPE_ELEMENTS_CONFIG = 'gf_stripe_elements_config';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @since 6.0
	 *
	 * @param GF_Service_Container $container Container singleton object.
	 */
	public function register( GF_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-elements-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'config/class-gf-stripe-elements-config.php';

		// Registering handler.
		$container->add(
			self::STRIPE_ELEMENTS_HANDLER,
			function () {
				return new Experimental_GF_Elements_Handler();
			}
		);

		// Registering config.
		$container->add(
			self::STRIPE_ELEMENTS_CONFIG,
			function () use ( $container ) {
				return new GF_Stripe_Elements_Config( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			}
		);
		$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( self::STRIPE_ELEMENTS_CONFIG ) );

	}

	/**
	 * Initializes service.
	 *
	 * @since 6.0
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function init( GF_Service_Container $container ) {
		parent::init( $container );

		$handler = $container->get( self::STRIPE_ELEMENTS_HANDLER );

		add_filter( 'gform_field_validation', array( $handler, 'handle_gform_field_validation' ), 10, 4 );
		add_action( 'gform_post_save_feed_settings', array( $handler, 'maybe_add_timeout_confirmation' ), 10, 4 );
		add_filter( "gform_gravityformsstripe_frontend_feed", array( $handler, 'hydrate_frontend_feed' ), 10, 3 );

		// Ajax handlers
		add_action( 'wp_ajax_nopriv_gfstripe_elements_create_payment_intent', array( $handler, 'ajax_create_payment_intent' ) );
		add_action( 'wp_ajax_gfstripe_elements_create_payment_intent', array( $handler, 'ajax_create_payment_intent' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_elements_create_subscription', array( $handler, 'ajax_create_subscription' ) );
		add_action( 'wp_ajax_gfstripe_elements_create_subscription', array( $handler, 'ajax_create_subscription' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_elements_get_entry', array( $handler, 'ajax_get_entry' ) );
		add_action( 'wp_ajax_gfstripe_elements_get_entry', array( $handler, 'ajax_get_entry' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_elements_handle_successful_entry', array( $handler, 'ajax_handle_successful_entry' ) );
		add_action( 'wp_ajax_gfstripe_elements_handle_successful_entry', array( $handler, 'ajax_handle_successful_entry' ) );

		add_action( 'wp_ajax_nopriv_gfstripe_increase_error_count', array( $handler, 'ajax_increase_error_count' ) );
		add_action( 'wp_ajax_gfstripe_increase_error_count', array( $handler, 'ajax_increase_error_count' ) );
	}
}
