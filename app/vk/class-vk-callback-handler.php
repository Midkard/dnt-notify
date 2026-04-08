<?php
/**
 * VK Callback API handler
 *
 * @package dnt_notify
 */

namespace dnt_notify\vk;

use dnt_notify\Environment;
use dnt_notify\Service;
use dnt_notify\user\User_Storage;

/**
 * VK Callback API handler class
 */
class VK_Callback_Handler extends Service {

	/**
	 * Get VK settings
	 *
	 * @return VK_Settings
	 */
	protected function settings(): VK_Settings {
		return $this->container->get( VK_Settings::class );
	}

	/**
	 * Handle callback request
	 */
	public function handle(): void {
		$input = file_get_contents( 'php://input' );
		if ( false === $input ) {
			echo 'error';
			exit;
		}

		/**
		 * Request data.
		 *
		 * @var array<string, mixed>|null
		 */
		$data = json_decode( $input, true );
		if ( ! is_array( $data ) || ! isset( $data['type'] ) ) {
			echo 'error';
			exit;
		}

		$type = is_string( $data['type'] ) ? $data['type'] : '';

		switch ( $type ) {
			case 'confirmation':
				$this->handle_confirmation();
				break;

			case 'message_new':
				$this->handle_message_new( $data );
				echo 'ok';
				break;

			default:
				// For other event types, just return ok.
				echo 'ok';
				break;
		}

		exit;
	}

	/**
	 * Handle confirmation request
	 */
	protected function handle_confirmation(): void {
		$code = $this->settings()->get_confirmation_code();
		echo esc_html( $code ?? '' );
	}

	/**
	 * Handle new message
	 *
	 * @param array<string, mixed> $data Request data.
	 */
	protected function handle_message_new( array $data ): void {
		if ( ! isset( $data['object'] ) || ! is_array( $data['object'] ) ) {
			return;
		}

		$object = $data['object'];

		// VK API version 5.80+ has 'message' key.
		if ( isset( $object['message'] ) && is_array( $object['message'] ) ) {
			$message = $object['message'];
		} else {
			// Older API versions.
			$message = $object;
		}

		if ( ! isset( $message['text'] ) || ! is_string( $message['text'] ) ) {
			return;
		}

		$text = $message['text'];
		$peer_id = isset( $message['peer_id'] ) && is_scalar( $message['peer_id'] ) ? (int) $message['peer_id'] : 0;

		if ( 0 === $peer_id ) {
			return;
		}

		// Handle /start command.
		if ( str_starts_with( $text, '/start ' ) ) {
			$this->handle_start_command( $text, $peer_id );
		}

		// Handle /startgroup command for adding groups.
		if ( str_starts_with( $text, '/startgroup ' ) ) {
			$this->handle_startgroup_command( $text, $peer_id );
		}

		/**
		 * Fires on VK message received.
		 *
		 * @param mixed $message Message data.
		 * @param int   $peer_id Peer ID.
		 */
		do_action( 'dnt_notify_vk_message', $message, $peer_id );
	}

	/**
	 * Handle /start command
	 *
	 * @param string $text    Message text.
	 * @param int    $peer_id Peer ID.
	 */
	protected function handle_start_command( string $text, int $peer_id ): void {
		$parts = explode( ' ', $text, 2 );
		if ( count( $parts ) < 2 ) {
			return;
		}

		$token = sanitize_text_field( $parts[1] );

		$user_storage = $this->container->get( User_Storage::class );
		$user = $user_storage->get_user_by_unique_parameter( $token );

		if ( $user ) {
			$user_storage->update_vk_chat( $user, (string) $peer_id );

			// Send welcome message.
			$this->send_message( $peer_id, "Добро пожаловать, {$user->user_nicename}! Уведомления VK подключены." );
		} else {
			$this->send_message( $peer_id, 'Пользователь не найден. Пожалуйста, получите новую ссылку в админ-панели.' );
		}
	}

	/**
	 * Handle /startgroup command
	 *
	 * @param string $text    Message text.
	 * @param int    $peer_id Peer ID.
	 */
	protected function handle_startgroup_command( string $text, int $peer_id ): void {
		$parts = explode( ' ', $text, 2 );
		if ( count( $parts ) < 2 ) {
			return;
		}

		$token = sanitize_text_field( $parts[1] );

		// Verify token.
		$expected_token = $this->settings()->token_to_add_group();
		if ( $token !== $expected_token ) {
			$this->send_message( $peer_id, 'Неверный токен. Пожалуйста, получите новый токен в админ-панели.' );
			return;
		}

		// Add group.
		$this->settings()->add_group( (string) $peer_id );

		// Get conversation info for welcome message.
		$vk = $this->container->get( VK::class );
		$info = $vk->get_conversation_info( (string) $peer_id );
		$title = is_array( $info ) ? $info['title'] : 'беседа';

		$this->send_message( $peer_id, "Группа '{$title}' успешно подключена для уведомлений!" );
	}

	/**
	 * Send message to peer
	 *
	 * @param int    $peer_id Peer ID.
	 * @param string $text    Message text.
	 */
	protected function send_message( int $peer_id, string $text ): void {
		if ( $this->is_develop() ) {
			$environment = $this->container->get( Environment::class );
			$path = $environment->logs_path();
			if ( null !== $path ) {
				$fp = fopen( $path . '/vk.log', 'a+' );
				if ( false !== $fp ) {
					fwrite( $fp, "Reply to {$peer_id}: {$text}\n" );
					fclose( $fp );
				}
			}
			return;
		}

		$vk = $this->container->get( VK::class );
		$api = $vk->get_api();
		if ( null === $api ) {
			return;
		}

		$access_token = $this->settings()->get_access_token();
		if ( empty( $access_token ) ) {
			return;
		}

		try {
			$api->messages()->send(
				$access_token,
				array(
					'peer_id' => $peer_id,
					'message' => $text,
					'random_id' => wp_rand( 1, PHP_INT_MAX ),
				)
			);
		} catch ( \Throwable $th ) {
			error_log( 'VK Send Error: ' . $th->getMessage() );
		}
	}
}
