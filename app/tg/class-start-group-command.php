<?php
/**
 * Start group command for the Telegram bot.
 *
 * This file contains the Start_Group_Command class which handles the /startgroup command.
 *
 * @package dnt_notify\tg
 */

namespace dnt_notify\tg;

use dnt_notify\Environment;
use Telegram\Bot\Commands\Command;
use function dnt_notify\get_container;

/**
 * Class for handling the /startgroup command.
 *
 * This class extends the Telegram Bot Command class and handles the /startgroup command.
 */
class Start_Group_Command extends Command {


	/**
	 * The name of the command.
	 *
	 * @var string
	 */
	protected string $name = 'startgroup';

	/**
	 * The description of the command.
	 *
	 * @var string
	 */
	protected string $description = 'Start the bot';

	/**
	 * Handles the /startgroup command.
	 *
	 * This method processes the /startgroup command and adds the chat to the list of groups.
	 *
	 * @return void
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
		 * The start parameter from the message.
		 *
		 * @var string|null $start_parameter
		 */
		// @phpstan-ignore method.notFound
		$start_parameter = $message->getText();
		if ( ! is_string( $start_parameter ) ) {
			return;
		}

		$entity_length = isset( $this->entity['length'] ) && is_scalar( $this->entity['length'] ) ? (int) $this->entity['length'] : 0;
		$parameter = substr( $start_parameter, $entity_length + 1 ); // Get the parameter after "start-group".

		$settings = get_container()->get( TG_Settings::class );
		$token = $settings->token_to_add_group();

		try {
			if ( $parameter === $token ) {
				$chat_id_str = is_scalar( $chat_id ) ? (string) $chat_id : '';
				$settings->add_group( $chat_id_str );
				$reply = 'Оповещения будут направляться в эту группу';
			} else {
				$reply = 'Токен не верен, либо устарел. Попробуйте обновить страницу в админ панели сайта.';
			}
			if ( get_container()->get( Environment::class )->is_develop() ) {
				error_log( $reply );
			} else {
				$this->replyWithMessage( array( 'text' => $reply ) );
			}
		} catch ( \Throwable $th ) {
			error_log( $th->getMessage() );
		}
	}
}
