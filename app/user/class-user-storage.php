<?php
/**
 * User storage functionality for the DNT Notify plugin.
 *
 * This file contains the User_Storage class which handles user-related data storage.
 *
 * @package dnt_notify\user
 */

namespace dnt_notify\user;

/**
 * Class for handling user storage operations.
 *
 * This class provides methods to manage user-related data storage.
 */
class User_Storage {


	/**
	 * Gets the unique parameter for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @return string The unique parameter for the user.
	 */
	public function get_user_unique_parameter( $user ): string {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		$unique_parameter = get_user_meta( $user_id, 'unique_parameter', true );
		if ( ! is_string( $unique_parameter ) || empty( $unique_parameter ) ) {
			$unique_parameter = wp_generate_password( 20, false );
			update_user_meta( $user_id, 'unique_parameter', $unique_parameter );
		}
		return $unique_parameter;
	}

	/**
	 * Gets a user by their unique parameter.
	 *
	 * @param string $parameter The unique parameter to search for.
	 * @return \WP_User|null The user object if found, otherwise null.
	 */
	public function get_user_by_unique_parameter( string $parameter ): ?\WP_User {

		if ( empty( $parameter ) ) {
			return null;
		}

		/**
		 * Gets users by unique parameter.
		 *
		 * @var \WP_User[]
		 */
		$users = get_users(
			array(
				'meta_key' => 'unique_parameter',
				'meta_value' => $parameter,
				'number' => 1,
			)
		);
		if ( count( $users ) > 0 ) {
			return $users[0];
		}
		return null;
	}

	/**
	 * Gets the Telegram chat ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @return string|false The Telegram chat ID if found, otherwise false.
	 */
	public function get_tg_chat( $user ): string|false {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		$chat_id = get_user_meta( $user_id, 'chat_id', true );
		return is_string( $chat_id ) ? $chat_id : false;
	}

	/**
	 * Gets the Telegram chat IDs for a users.
	 *
	 * @param int[] $users The users ID.
	 * @return string[] The Telegram chat ID if found.
	 */
	public function get_tg_chats( $users ): array {
		if ( empty( $users ) ) {
			return array();
		}
		/**
		 * User IDs with chat_id meta field.
		 *
		 * @var int[]
		 */
		$users = get_users(
			array(
				'meta_key' => 'chat_id',
				'meta_compare' => 'EXISTS',
				'number' => -1,
				'fields' => 'ID',
				'include' => $users,
			)
		);
		$chats = array_map(
			function ( $n ): string {
				$chat_id = get_user_meta( (int) $n, 'chat_id', true );
				return is_string( $chat_id ) ? $chat_id : '';
			},
			$users
		);
		return array_filter( $chats );
	}

	/**
	 * Removes the Telegram chat ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @return void
	 */
	public function remove_tg_chat( $user ): void {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		delete_user_meta( $user_id, 'chat_id' );
	}

	/**
	 * Updates the Telegram chat ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @param string       $chat_id The Telegram chat ID to update.
	 * @return void
	 */
	public function update_tg_chat( $user, string $chat_id ): void {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		update_user_meta( $user_id, 'chat_id', $chat_id );
	}

	/**
	 * Gets the VK peer ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @return string|false The VK peer ID if found, otherwise false.
	 */
	public function get_vk_chat( $user ): string|false {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		$peer_id = get_user_meta( $user_id, 'vk_peer_id', true );
		return is_string( $peer_id ) ? $peer_id : false;
	}

	/**
	 * Gets the VK peer IDs for a users.
	 *
	 * @param int[] $users The users ID.
	 * @return string[] The VK peer ID if found.
	 */
	public function get_vk_chats( $users ): array {
		if ( empty( $users ) ) {
			return array();
		}
		/**
		 * User IDs with vk_peer_id meta field.
		 *
		 * @var int[]
		 */
		$users = get_users(
			array(
				'meta_key' => 'vk_peer_id',
				'meta_compare' => 'EXISTS',
				'number' => -1,
				'fields' => 'ID',
				'include' => $users,
			)
		);
		$chats = array_map(
			function ( $n ): string {
				$vk_peer_id = get_user_meta( (int) $n, 'vk_peer_id', true );
				return is_string( $vk_peer_id ) ? $vk_peer_id : '';
			},
			$users
		);
		return array_filter( $chats );
	}

	/**
	 * Removes the VK peer ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @return void
	 */
	public function remove_vk_chat( $user ): void {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		delete_user_meta( $user_id, 'vk_peer_id' );
	}

	/**
	 * Updates the VK peer ID for a user.
	 *
	 * @param \WP_User|int $user The user ID or user object.
	 * @param string       $peer_id The VK peer ID to update.
	 * @return void
	 */
	public function update_vk_chat( $user, string $peer_id ): void {
		$user_id = is_scalar( $user ) ? (int) $user : $user->ID;
		update_user_meta( $user_id, 'vk_peer_id', $peer_id );
	}
}
