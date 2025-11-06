<?php
/**
 * Service Provider for the Elements Service
 *
 * @package Gravity_forms\Gravity_Forms_Stripe\Elements
 */
namespace Gravity_forms\Gravity_Forms_Stripe\Common;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_forms\Gravity_Forms_Stripe\Elements\Config\GF_Stripe_Elements_Config;
use Gravity_forms\Gravity_Forms_Stripe\Elements\Experimental_GF_Elements_Handler;

/**
 * Class Webhook_Service_Provider
 *
 * Service provider for Webhook processing.
 */
class Webhook_Service_Provider extends GF_Service_Provider {

	const STRIPE_WEBHOOK_HANDLER = 'gf_stripe_webhook_handler';

	/**
	 * Register handler only during webhook request.
	 *
	 * @param GF_Service_Container $container Container singleton object.
	 */
	public function register( GF_Service_Container $container ) {}

	/**
	 * Initialize service.
	 *
	 * @since 6.0
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function init( GF_Service_Container $container ) {
		parent::init( $container );

		// Webhook handler
		add_action( 'gform_stripe_pre_webhook', array( $this, 'handle_webhook' ), 10, 2 );

		// Delayed feeds
		add_action( 'gform_post_payment_completed', array( $this, 'handle_delayed_feeds' ) );
		add_action( 'gform_post_subscription_started', array( $this, 'handle_delayed_feeds' ) );
	}

	/**
	 * Handle the webhook request.
	 *
	 * @since 6.0
	 *
	 * @param string $action Action to be performed.
	 * @param array  $event Event data.
	 *
	 * @return bool
	 */
	public function handle_webhook( $action, $event ) {
		$handler = $this->init_webhook_handler();
		return $handler->handle_webhook( $action, $event );
	}

	/**
	 * Handle delayed feeds.
	 *
	 * @since 6.0
	 *
	 * @param array $entry Entry data.
	 *
	 * @return bool
	 */
	public function handle_delayed_feeds( $entry ) {
		$handler = $this->init_webhook_handler();
		return $handler->handle_delayed_feeds( $entry );
	}

	/**
	 * Initialize the webhook handler.
	 *
	 * @since 6.0
	 *
	 * @return Webhook_Handler
	 */
	private function init_webhook_handler() {

		$handler = $this->container->get( self::STRIPE_WEBHOOK_HANDLER );
		if ( ! $handler ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-webhook-handler.php';
			$this->container->add(
				self::STRIPE_WEBHOOK_HANDLER,
				function () {
					return new Webhook_Handler();
				}
			);
			$handler = $this->container->get( self::STRIPE_WEBHOOK_HANDLER );
		}

		return $handler;
	}

}
