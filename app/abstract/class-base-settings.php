<?php
/**
 * Base class for settings
 *
 * @package dnt_notify
 */

namespace dnt_notify;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Base class for option
 */
abstract class Base_Settings extends Service {


	/**
	 * Key for storing settings
	 *
	 * @var string
	 */
	public $key = '';

	/**
	 * Settings group
	 *
	 * @var string
	 */
	public $group = 'dnt_notify';

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {

		register_setting( $this->group, $this->key );

		add_filter( "sanitize_option_{$this->key}", array( $this, 'filter_sanitize_option' ) );

		// Register settings group.
		// $this->key . '_settings',
		// $this->key,
		// array(
		// 'type' => 'object',
		// 'description' => 'Plugin settings',
		// 'default' => $this->get_default_settings(),
		// 'show_in_rest' => array(
		// 'schema' => $this->get_settings_schema(),
		// ),
		// )
		// ).
	}

	/**
	 * Filters an option value following sanitization.
	 *
	 * @param mixed $value          The sanitized option value.
	 * @return mixed The sanitized option value.
	 */
	public function filter_sanitize_option( $value ) {
		$schema = $this->get_schema();
		$error = rest_validate_value_from_schema( $value, $schema );
		if ( is_wp_error( $error ) ) {
			$value = $this->get_value();
			if ( function_exists( 'add_settings_error' ) ) {
				foreach ( $error->get_error_messages() as $message ) {
					add_settings_error( $this->key, "invalid_{$this->key}", $message );
				}
			}

			return $value;
		}

		$value = rest_sanitize_value_from_schema( $value, $schema );
		if ( is_wp_error( $value ) ) {
			if ( function_exists( 'add_settings_error' ) ) {
				foreach ( $value->get_error_messages() as $message ) {
					add_settings_error( $this->key, "invalid_{$this->key}", $message );
				}
			}
			$value = $this->get_value();
		}

		return $value;
	}

	/**
	 * Validate a value based on the schema.
	 *
	 * @param mixed $value The value to validate.
	 * @return \WP_Error|bool True if valid, WP_Error if invalid.
	 */
	public function validate( $value ): \WP_Error|bool {
		$schema = $this->get_schema();
		return rest_validate_value_from_schema( $value, $schema );
	}

	/**
	 * Get a specific value from the settings.
	 *
	 * @param string[] $path The path to the value.
	 * @return mixed The value or null if not found.
	 */
	public function get_value( array $path = array() ) {
		$option = get_option( $this->key, $this->get_default_value() );
		if ( ! empty( $path ) ) {
			$segment = $option;
			if ( is_array( $segment ) ) {
				foreach ( $path as $id ) {
					if ( is_array( $segment ) && isset( $segment[ $id ] ) ) {
						$segment = $segment[ $id ];
					} else {
						return null;
					}
				}
				return $segment;
			}
			return null;
		}
		return $option;
	}

	/**
	 * Get string settings
	 *
	 * @return string|null Plugin settings.
	 */
	/**
	 * Get a string value from the settings.
	 *
	 * @param string|string[] $name The name of the setting.
	 * @return string|null The string value or null if not found.
	 * @throws \Exception If the type could not be transformed into string.
	 */
	public function get_string( $name ): ?string {
		$name = is_array( $name ) ? $name : array( $name );
		$option = $this->get_value( $name );
		if ( null === $option ) {
			return $option;
		}
		if ( is_scalar( $option ) ) {
			return strval( $option );
		}
		throw new \Exception( 'Type could not be transformed into string', 1 );
	}


	/**
	 * Get the default value for the settings.
	 *
	 * @return array<mixed> The default value.
	 */
	protected function get_default_value(): array {
		return array();
	}

	/**
	 * Settings schema.
	 *
	 * "$schema": "http://json-schema.org/draft-04/schema#"
	 *
	 * @return array<string,mixed>
	 */
	abstract public function get_schema();
}
