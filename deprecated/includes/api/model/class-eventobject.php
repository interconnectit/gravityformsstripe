<?php

namespace Gravity_Forms_Stripe\API\Model;

use Gravity_Forms_Stripe\API\Model\Base;

require_once( 'class-base.php' );

/**
 * Object representing a Webhook Event Object.
 *
 * @since 5.5.0
 */
class EventObject extends Base {

	/**
	 * This method is not supported by this object
	 *
	 * @since 5.5.0
	 *
	 * @param $id
	 * @param $params
	 * @param $opts
	 * @return \WP_Error
	 */
	public function update( $id, $params = null, $opts = null ) {
		return new \WP_Error( 'invalid-request', __( 'Event Object cannot be updated.', 'gravityformsstripe' ) );
	}

}
