<?php
/**
 * DI container for dependency management
 *
 * @package dnt_notify
 */

namespace dnt_notify;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Container class - DI container
 */
class Container implements Container_Interface {

	/**
	 * Array of factory functions for creating services
	 *
	 * @var array<string,callable>
	 */
	private $services = array();

	/**
	 * Array of already created service instances (singletons)
	 *
	 * @var array<string,mixed>
	 */
	private $instances = array();

	/**
	 * Array of flags indicating whether a service is a singleton
	 *
	 * @var array<string,boolean>
	 */
	private $shared = array();

	/**
	 * Register a service in the container
	 *
	 * @param string   $id       Service identifier.
	 * @param callable $factory  Factory function for creating the service.
	 * @param bool     $shared   Is the service a singleton (default true).
	 * @return void
	 */
	public function set( string $id, ?callable $factory = null, bool $shared = true ) {
		if ( ! $factory ) {
			$factory = fn( $container ) => new $id( $container );
		}
		/**
		 * Filter to override the service factory function
		 *
		 * @param callable $factory Factory function.
		 * @param string   $id      Service identifier.
		 * @param Container $container   Container instance.
		 */
		$factory = apply_filters( 'dnt_notify_container_factory', $factory, $id, $this );

		$this->services[ $id ] = $factory;
		$this->shared[ $id ]   = $shared;

		// Remove the old instance if it was created.
		if ( isset( $this->instances[ $id ] ) ) {
			unset( $this->instances[ $id ] );
		}
	}

	/**
	 * Get a service from the container
	 *
	 * @template T
	 * @param class-string<T> $id Service identifier.
	 * @param mixed           $props Parameters.
	 * @return T
	 * @throws \Exception If the service is not found.
	 */
	public function get( $id, $props = null ) {
		if ( ! $this->has( $id ) ) {
			if ( class_exists( $id ) ) {
				$this->set( $id );
			} else {
				throw new \Exception( sprintf( "Service '%s' not found in container.", esc_html( $id ) ) );
			}
		}

		// If the service is a singleton and already created, return it.
		if ( $this->shared[ $id ] && isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		/**
		 *  Create a new service instance.
		 *
		 * @var T
		 */
		$service = call_user_func( $this->services[ $id ], $this, $props );

		/**
		 * Filter to override the service instance
		 *
		 * @param T    $service Service instance.
		 * @param string    $id      Service identifier.
		 * @param Container $container    Container instance.
		 * @param mixed $props    props.
		 */
		$service = apply_filters( 'dnt_notify_container_service', $service, $id, $this, $props );

		// If the service is a singleton, save its instance.
		if ( $this->shared[ $id ] ) {
			$this->instances[ $id ] = $service;
		}

		return $service;
	}

	/**
	 * Check if a service exists in the container
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->services[ $id ] );
	}

	/**
	 * Remove a service from the container
	 *
	 * @param string $id Service identifier.
	 * @return void
	 */
	public function remove( string $id ) {
		unset( $this->services[ $id ], $this->instances[ $id ], $this->shared[ $id ] );
	}

	/**
	 * Override a service instance
	 * Useful for testing or changing behavior from outside
	 *
	 * @param string $id       Service identifier.
	 * @param object $instance Service instance.
	 * @return void
	 */
	public function override( string $id, $instance ) {
		$this->instances[ $id ] = $instance;
		$this->shared[ $id ]    = true;
	}

	/**
	 * Get all registered service identifiers
	 *
	 * @return string[]
	 */
	public function get_service_ids(): array {
		return array_keys( $this->services );
	}
}
