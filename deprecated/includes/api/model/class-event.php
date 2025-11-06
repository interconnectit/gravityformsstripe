<?php

namespace Gravity_Forms_Stripe\API\Model;

require_once( 'class-base.php' );
require_once( 'class-eventdata.php' );

/**
 * Object representing a Webhook Event.
 *
 * @since 5.5.0
 */
class Event extends Base {

	/**
	 * Initialize properties that will be used throughout this class and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $account;
	public $api_version;
	public $data;
	public $livemode;
	public $request;
	public $type;
	public $pending_webhooks;

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
		return new \WP_Error( 'invalid-request', __( 'Events cannot be updated.', 'gravityformsstripe' ) );
	}

	/**
	 * Gets the nested object that should be expanded when this object is created.
	 *
	 * @since 5.5.0
	 *
	 * @return array Returns an array of nested objects that should be expanded when this object is created.
	 */
	public function get_nested_objects() {

		return array(
			'data' => '\Gravity_Forms_Stripe\API\Model\EventData',
		);
	}

	const EXPECTED_SCHEME   = 'v1';
	const DEFAULT_TOLERANCE = 300;

	/**
	 * Returns an Event instance using the provided JSON payload.
	 *
	 * @param string $payload    The payload sent by Stripe.
	 * @param string $sig_header The contents of the signature header sent by Stripe.
	 * @param string $secret     Secret used to generate the signature.
	 * @param GF_Stripe_API $api Stripe API instance that called this method.
	 * @param int $tolerance     Maximum difference allowed between the header's timestamp and the current time.
	 *
	 * @return Event|WP_Error Returns the Event instance or a WP_Error if the payload is not valid JSON or if the signature verification fails for any reason.
	 */
	public static function construct_event( $payload, $sig_header, $secret, $api, $tolerance = self::DEFAULT_TOLERANCE ) {

		try {
			self::verify_header( $payload, $sig_header, $secret, $tolerance );
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getMessage() );
		}

		$data       = \json_decode( $payload, true );
		$json_error = \json_last_error();
		if ( null === $data && \JSON_ERROR_NONE !== $json_error ) {
			$msg = "Invalid payload: {$payload} " . "(json_last_error() was {$json_error})";

			return new \WP_Error( $msg );
		}

		return new Event( $data, $api );
	}

	/**
	 * Verifies the signature header sent by Stripe. Throws an
	 * exception if the verification fails for any reason.
	 *
	 * @param string $payload the payload sent by Stripe
	 * @param string $header the contents of the signature header sent by Stripe
	 * @param string $secret secret used to generate the signature
	 * @param int $tolerance maximum difference allowed between the header's timestamp and the current time
	 *
	 * @throws \Exception if the verification fails
	 *
	 * @return bool
	 */
	public static function verify_header( $payload, $header, $secret, $tolerance = null ) {
		// Extract timestamp and signatures from header
		$timestamp  = self::get_timestamp( $header );
		$signatures = self::get_signatures( $header, self::EXPECTED_SCHEME );
		if ( -1 === $timestamp ) {
			throw new \Exception( 'Unable to extract timestamp and signatures from header' );
		}
		if ( empty( $signatures ) ) {
			throw new \Exception( 'No signatures found with expected scheme' );
		}

		// Check if expected signature is found in list of signatures from
		// header
		$signed_payload     = "{$timestamp}.{$payload}";
		$expected_signature = self::compute_signature( $signed_payload, $secret );
		$signature_found    = false;
		foreach ( $signatures as $signature ) {
			if ( self::secure_compare( $expected_signature, $signature ) ) {
				$signature_found = true;

				break;
			}
		}
		if ( ! $signature_found ) {
			throw new \Exception( 'No signatures found matching the expected signature for payload' );
		}

		// Check if timestamp is within tolerance
		if ( ( $tolerance > 0 ) && ( \abs( \time() - $timestamp ) > $tolerance ) ) {
			throw new \Exception( 'Timestamp outside the tolerance zone' );
		}

		return true;
	}

	private static $isHashEqualsAvailable;

	/**
	 * Compares two strings for equality. The time taken is independent of the
	 * number of characters that match.
	 *
	 * @param string $a one of the strings to compare
	 * @param string $b the other string to compare
	 *
	 * @return bool true if the strings are equal, false otherwise
	 */
	public static function secure_compare( $a, $b ) {

		if ( null === self::$isHashEqualsAvailable ) {
			self::$isHashEqualsAvailable = \function_exists( 'hash_equals' );
		}

		if ( self::$isHashEqualsAvailable ) {
			return \hash_equals( $a, $b );
		}
		if ( \strlen( $a ) !== \strlen( $b ) ) {
			return false;
		}

		$result = 0;
		for ( $i = 0; $i < \strlen( $a ); ++$i ) {
			$result |= \ord( $a[ $i ] ) ^ \ord( $b[ $i ] );
		}

		return 0 === $result;
	}

	/**
	 * Extracts the timestamp in a signature header.
	 *
	 * @param string $header the signature header
	 *
	 * @return int the timestamp contained in the header, or -1 if no valid
	 *  timestamp is found
	 */
	private static function get_timestamp( $header ) {
		$items = \explode( ',', $header );

		foreach ( $items as $item ) {
			$item_parts = \explode( '=', $item, 2 );
			if ( 't' === $item_parts[0] ) {
				if ( ! \is_numeric( $item_parts[1] ) ) {
					return -1;
				}

				return (int) ( $item_parts[1] );
			}
		}

		return -1;
	}

	/**
	 * Extracts the signatures matching a given scheme in a signature header.
	 *
	 * @param string $header the signature header
	 * @param string $scheme the signature scheme to look for
	 *
	 * @return array the list of signatures matching the provided scheme
	 */
	private static function get_signatures( $header, $scheme ) {
		$signatures = array();
		$items      = \explode( ',', $header );

		foreach ( $items as $item ) {
			$item_parts = \explode( '=', $item, 2 );
			if ( $item_parts[0] === $scheme ) {
				\array_push( $signatures, $item_parts[1] );
			}
		}

		return $signatures;
	}

	/**
	 * Computes the signature for a given payload and secret.
	 *
	 * The current scheme used by Stripe ("v1") is HMAC/SHA-256.
	 *
	 * @param string $payload the payload to sign
	 * @param string $secret the secret used to generate the signature
	 *
	 * @return string the signature as a string
	 */
	private static function compute_signature( $payload, $secret ) {
		return \hash_hmac( 'sha256', $payload, $secret );
	}
}
