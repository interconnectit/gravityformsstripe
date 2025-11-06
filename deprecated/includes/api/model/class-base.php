<?php

namespace Gravity_Forms_Stripe\API\Model;

/**
 * This is a base class for all the Stripe API models.
 *
 * @since 5.5.0
 */
#[\AllowDynamicProperties]
class Base implements \ArrayAccess {

	/**
	 * The API object
	 *
	 * @since 5.5.0
	 *
	 * @var \GF_Stripe_API The API object.
	 */
	protected $api;

	/**
	 * The original values used when intantiating the object.
	 *
	 * @since 5.5.0
	 *
	 * @var array The original values used when intantiating the object.
	 */
	private $original_values = array();

	/**
	 * Initialize properties that will be used throughout this class, all subclasses, and link to the Stripe API.
	 *
	 * @since 5.5.2
	 */
	public $id;
	public $object;
	public $created;

	/**
	 * Instantiates the object.
	 *
	 * @since 5.5.0
	 *
	 * @param $data array         The data to hydrate the object.
	 * @param $api \GF_Stripe_API The api object to use for making requests.
	 */
	public function __construct( $data, $api ) {
		$this->api = $api;
		$this->refresh( $data );
	}

	/**
	 * Updates the object in Stripe via the API.
	 *
	 * @since 5.5.0
	 *
	 * @param string $id  The ID of the object to be updated.
	 * @param array $data The data to be updated.
	 * @param array $opts The options to be used when making the request
	 *
	 * @return $this|\WP_Error Returns the updated object or WP_Error if there was an error.
	 */
	public function update( $id, $data, $opts = null ) {
		$response = $this->api->make_request( $this->api_endpoint() . '/' . $id, $data, 'POST', 200 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$this->refresh( $response );
		return $this;
	}

	/**
	 * Saves this object in Stripe via the API.
	 *
	 * @since 5.5.0
	 *
	 * @param $opts Options to be used when making the request.
	 *
	 * @return $this|\WP_Error Returns the updated objec, or a WP_Error if the request failed.
	 */
	public function save( $opts = null ) {
		return $this->update( $this->id, $this->get_update_parameters(), $opts );
	}

	/**
	 * Refreshes this object with the specified data.
	 *
	 * @since 5.5.0
	 *
	 * @param $data The data to be refresh the object with.
	 * @return void
	 */
	public function refresh( $data ) {
		$this->original_values = $data;

		// Dynamically creating properties based on the specified data.
		foreach ( $data as $key => $value ) {
			$this->{$key} = $this->maybe_expand( $key, $value );
		}
	}

	/**
	 * Maybe expands array into objects if $key is defined in $this->get_nested_objects().
	 *
	 * @since 5.5.0
	 *
	 * @param string $key         Name of property (array key)
	 * @param string|array $value Value to be expanded.
	 *
	 * @return mixed Returns the original value, or a new object if $key has been configured to be expanded.
	 */
	protected function maybe_expand( $key, $value ) {
		$nested_objects = $this->get_nested_objects();
		$class_name     = rgar( $nested_objects, $key );
		if ( $class_name && is_array( $value ) ) {
			return new $class_name( $value, $this->api );
		}
		return $value;
	}

	/**
	 * Gets the parameters supported by the update API endpoint. To be overridden by child classes.
	 *
	 * @since 5.5.0
	 *
	 * @return array Returns the parameters to be used when updating the object.
	 */
	public function get_nested_objects() {
		return array();
	}

	/**
	 * Gets properties that have been updated since the object was instantiated.
	 *
	 * @since 5.5.0
	 *
	 * @return array Returns an array of all properties that have been updated since the object was instantiated.
	 */
	private function get_updated_properties() {
		$updated_properties = array();

		$ary = get_object_vars( $this );
		foreach ( $ary as $key => $value ) {
			if ( $key == 'original_values' ) {
				continue;
			}
			if ( $value != rgar( $this->original_values, $key ) ) {
				$updated_properties[ $key ] = $value;
			}
		}
		return $updated_properties;
	}

	/**
	 * Gets the parameters to be sent to API endpoints.
	 *
	 * @since 5.5.0
	 *
	 * @param $supported_params array The parameters supported by the API endpoint.
	 *
	 * @return array Returns an array of parameters to be sent to the API endpoint.
	 */
	protected function serialize_parameters( $supported_params ) {

		$updated = $this->get_updated_properties();

		$parameters = array();
		foreach ( $updated as $key => $value ) {
			if ( in_array( $key, $supported_params ) ) {
				$parameters[ $key ] = $value;
			}
		}

		return $parameters;
	}

	// ArrayAccess methods

	/**
	 * Part of ArrayAccess implementation. Sets a value at the specified offset.
	 *
	 * @since 5.5.0
	 *
	 * @param string $offset The offset to set the value at (property name).
	 * @param mixed $value   The value to set.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->{$offset} = $value;
	}

	/**
	 * Part of ArrayAccess implementation. Checks if a key exists.
	 *
	 * @since 5.5.0
	 *
	 * @param string $offset The offset (property name) to check.
	 *
	 * @return bool Returns true if the offset exists, false otherwise.
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->{$offset} );
	}

	/**
	 * Part of ArrayAccess implementation. Unsets a value at the specified offset.
	 *
	 * @since 5.5.0
	 *
	 * @param string $offset The offset (property name) to unset.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->{$offset} );
	}

	/**
	 * Part of ArrayAccess implementation. Gets a value at the specified offset.
	 *
	 * @since 5.5.0
	 *
	 * @param string $offset The offset (property name) to get.
	 *
	 * @return mixed Returns the value at the specified offset, or null if it doesn't exist.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return isset( $this->{$offset} ) ? $this->{$offset} : null;
	}

	/**
	 * Returns an associative array with the key and values composing the object.
	 *
	 * @since 5.5.0
	 *
	 * @return array the associative array representing the current object.
	 */
	//phpcs:ignore
	public function toArray() {

		$keys = get_object_vars( $this );

		$array = array();

		//loop through the keys and add them to the array
		foreach ( $keys as $key => $value ) {

			//if the value is an object, call the toArray method
			if ( is_object( $value ) && method_exists( $value, 'toArray' ) ) {
				$array[ $key ] = $value->toArray();
			} else {
				$array[ $key ] = $value;
			}
		}

		return $array;
	}
}
