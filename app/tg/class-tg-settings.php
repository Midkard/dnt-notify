<?php
/**
 * Class for plugin admin panel
 *
 * @package dnt_notify
 */

namespace dnt_notify\tg;

use dnt_notify\Base_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Settings class - plugin settings
 */
class TG_Settings extends Base_Settings {

	/**
	 * Group for settings
	 *
	 * @var string
	 */
	public $group = 'dnt_notify_tg';

	/**
	 * Key for storing settings
	 *
	 * @var string
	 */
	public $key = 'dnt_notify_tg';


	/**
	 * Get the default value for the settings.
	 *
	 * @return array<mixed> The default value.
	 */
	protected function get_default_value(): array {
		return array();
	}

	/**
	 * Get bot name
	 *
	 * @return string|null
	 */
	public function get_bot_name(): ?string {
		return $this->get_string( 'bot_name' );
	}

	/**
	 * Get bot token
	 *
	 * @return string|null
	 */
	public function get_bot_token(): ?string {
		return $this->get_string( 'bot_token' );
	}

	/**
	 * Check if bot is ready
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return ! empty( $this->get_bot_name() ) && ! empty( $this->get_bot_token() );
	}

	/**
	 * Get URL prefix
	 *
	 * @return string
	 */
	public function get_url_prefix(): string {
		/**
		 * Get URL prefix from options or generate a new one
		 *
		 * @var string
		 */
		$token = get_option( $this->key . '_url_prefix' );
		if ( empty( $token ) ) {
			$token = wp_generate_password( 20, false );
			update_option( $this->key . '_url_prefix', $token );
		}
		return $token;
	}

	/**
	 * Get token to add group
	 *
	 * @return string
	 */
	public function token_to_add_group(): string {
		/**
		 * Get token to add group from transient or generate a new one
		 *
		 * @var string
		 */
		$token = get_transient( $this->key . '_token_to_add_group' );
		if ( empty( $token ) ) {
			$token = wp_generate_password( 20, false );
			set_transient( $this->key . '_token_to_add_group', $token );
		}
		return $token;
	}

	/**
	 * Add group
	 *
	 * @param string $chat_id Chat ID.
	 */
	public function add_group( string $chat_id ): void {
		$groups = $this->get_groups();
		$groups[] = $chat_id;
		$groups = array_unique( $groups );
		update_option( $this->key . '_groups', $groups );
	}

	/**
	 * Get groups
	 *
	 * @return list<string>
	 */
	public function get_groups(): array {
		/**
		 * Get groups from options or return empty array
		 *
		 * @var list<string>|null
		 */
		$groups = get_option( $this->key . '_groups', array() );
		return is_array( $groups ) ? $groups : array();
	}

	/**
	 * Remove group
	 *
	 * @param string $group_id Group ID.
	 */
	public function remove_group( string $group_id ): void {
		$groups = $this->get_groups();
		$groups = array_filter(
			$groups,
			function ( $id ) use ( $group_id ): bool {
				return strval( $id ) !== strval( $group_id );
			}
		);
		update_option( $this->key . '_groups', array_values( $groups ) );
	}

	/**
	 * Get settings schema
	 *
	 * @return mixed
	 */
	public function get_schema() {
		return array(
			'type' => 'object',
			'properties' => array(
				'bot_token' => array(
					'type' => 'string',
					'title' => 'Bot Token',
				),
				'bot_name' => array(
					'type' => 'string',
					'title' => 'Bot Name',
				),
			),
		);
	}
}
