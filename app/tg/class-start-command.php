<?php
/**
 * Start command for Telegram bot
 *
 * @package dnt_notify
 */

namespace dnt_notify\tg;

use dnt_notify\Environment;
use dnt_notify\user\User_Storage;
use Telegram\Bot\Commands\Command;
use function dnt_notify\get_container;

/**
 * Start command for Telegram bot
 */
class Start_Command extends Command {


	/**
	 * Command name
	 *
	 * @var string
	 */
	protected string $name = 'start';

	/**
	 * Command description
	 *
	 * @var string
	 */
	protected string $description = 'Start the bot';

	/**
	 * Handle start command
	 */
	public function handle(): void {
		$update = $this->getUpdate();

		$message = $update->getMessage();

		// @phpstan-ignore identical.alwaysFalse
		if ( null === $message ) {
			return;
		}
		// @phpstan-ignore method.notFound
		$chat = $message->getChat();
		if ( null === $chat ) {
			return;
		}
		// @phpstan-ignore method.nonObject
		$chat_id = $chat->getId();

		/**
		 * Get start parameter from message text
		 *
		 * @var string|null $start_parameter
		 */
		// @phpstan-ignore method.notFound
		$start_parameter = $message->getText();
		if ( ! is_string( $start_parameter ) ) {
			return;
		}

		$entity_length = isset( $this->entity['length'] ) && is_scalar( $this->entity['length'] ) ? (int) $this->entity['length'] : 0;
		$parameter = substr( $start_parameter, $entity_length + 1 ); // Get parameter after "start".

		// Find user by unique parameter.
		$user_storage = get_container()->get( User_Storage::class );
		$user = $user_storage->get_user_by_unique_parameter( $parameter );
		if ( $user ) {
			$chat_id_str = is_scalar( $chat_id ) ? (string) $chat_id : '';
			$user_storage->update_tg_chat( $user, $chat_id_str );
			$user_nicename = ! empty( $user->user_nicename ) ? $user->user_nicename : '';
			$reply = "Добро пожаловать, {$user_nicename}!";
		} else {
			$reply = 'Пользователь не найден.';
		}

		if ( get_container()->get( Environment::class )->is_develop() ) {
			error_log( $reply );
		} else {
			$this->replyWithMessage( array( 'text' => $reply ) );
		}
	}
}
