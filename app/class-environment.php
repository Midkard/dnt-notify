<?php
/**
 * Environment class for handling environment variables
 *
 * @package dnt_notify
 */

namespace dnt_notify;

defined( 'ABSPATH' ) || die( -1 );

/**
 * Environment class for handling environment variables
 */
class Environment {

	/**
	 * Environment variables.
	 *
	 * @var array<string, mixed>
	 */
	protected $env = array(
		'env' => 'production',
		'develop' => false,

	);

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( getenv( 'WP_ENV' ) === 'develop' ) {
			$this->env['env'] = 'develop';
		}
		if ( file_exists( DNT_NOTIFY_PLUGIN_DIR . '/.env' ) ) {
			$parsed_env = $this->parse_env_file( DNT_NOTIFY_PLUGIN_DIR . '/.env' );
			$this->env = array_merge( $this->env, $parsed_env );
		}
	}

	/**
	 * Check if the environment is in development mode.
	 *
	 * @return bool True if in development mode, false otherwise.
	 */
	public function is_develop(): bool {
		return 'develop' === $this->env['env'];
	}

	/**
	 * Get the logs path.
	 *
	 * @return string|null The logs path or null if it cannot be created.
	 */
	public function logs_path(): ?string {
		$path = DNT_NOTIFY_PLUGIN_DIR . '/logs';
		if ( ! is_dir( $path ) ) {
			if ( ! wp_mkdir_p( $path ) ) {
				return null;
			}
		}
		return $path;
	}

	/**
	 * Parse an environment file.
	 *
	 * @param string $file The path to the environment file.
	 * @return array<string, string> The parsed environment variables.
	 */
	protected function parse_env_file( string $file ): array {
		$env = array();
		$env_lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( is_array( $env_lines ) ) {
			foreach ( $env_lines as $line ) {
				if ( false !== strpos( $line, '=' ) && 0 !== strpos( $line, '#' ) ) {
					list($name, $value) = explode( '=', $line, 2 );
					$env[ strtolower( $name ) ] = strtolower( $value );
				}
			}
		}
		return $env;
	}

	/**
	 * Get an environment variable.
	 *
	 * @param string $key The key of the environment variable.
	 * @return mixed The value of the environment variable.
	 */
	public function get( string $key ) {
		return $this->env[ strtolower( $key ) ] ?? null;
	}
}
