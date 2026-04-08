<?php
/**
 * Class for VK plugin settings
 *
 * @package dnt_notify
 */

namespace dnt_notify\vk;

use dnt_notify\Base_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Settings class - VK plugin settings
 */
class VK_Settings extends Base_Settings {

	/**
	 * Group for settings
	 *
	 * @var string
	 */
	public $group = 'dnt_notify_vk';

	/**
	 * Key for storing settings
	 *
	 * @var string
	 */
	public $key = 'dnt_notify_vk';


	/**
	 * Get the default value for the settings.
	 *
	 * @return array<mixed> The default value.
	 */
	protected function get_default_value(): array {
		return array();
	}

	/**
	 * Get access token
	 *
	 * @return string|null
	 */
	public function get_access_token(): ?string {
		return $this->get_string( 'access_token' );
	}

	/**
	 * Get group ID
	 *
	 * @return string|null
	 */
	public function get_group_id(): ?string {
		return $this->get_string( 'group_id' );
	}

	/**
	 * Get confirmation code
	 *
	 * @return string|null
	 */
	public function get_confirmation_code(): ?string {
		return $this->get_string( 'confirmation_code' );
	}

	/**
	 * Get secret key
	 *
	 * @return string|null
	 */
	public function get_secret_key(): ?string {
		return $this->get_string( 'secret_key' );
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
	 * Check if VK is ready
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return ! empty( $this->get_access_token() ) && ! empty( $this->get_group_id() );
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
	 * Add group
	 *
	 * @param string $peer_id Peer ID.
	 */
	public function add_group( string $peer_id ): void {
		$groups = $this->get_groups();
		$groups[] = $peer_id;
		$groups = array_unique( $groups );
		update_option( $this->key . '_groups', $groups );
	}

	/**
	 * Remove group
	 *
	 * @param string $peer_id Peer ID.
	 */
	public function remove_group( string $peer_id ): void {
		$groups = $this->get_groups();
		$groups = array_filter(
			$groups,
			function ( $id ) use ( $peer_id ): bool {
				return strval( $id ) !== strval( $peer_id );
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
				'access_token' => array(
					'type' => 'string',
					'title' => 'Access Token',
				),
				'group_id' => array(
					'type' => 'string',
					'title' => 'Group ID (без минуса)',
				),
				'confirmation_code' => array(
					'type' => 'string',
					'title' => 'Confirmation Code',
				),
				'secret_key' => array(
					'type' => 'string',
					'title' => 'Secret Key (опционально)',
				),
			),
		);
	}
}
