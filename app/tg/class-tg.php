<?php
/**
 * Telegram integration class
 *
 * @package dnt_notify
 */

namespace dnt_notify\tg;

use dnt_notify\Environment;
use dnt_notify\Service;
use dnt_notify\user\User_Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Telegram integration class
 */
class Tg extends Service {


	/**
	 * Telegram API instance
	 *
	 * @var Api|null
	 */
	protected $api;

	/**
	 * Get TG settings
	 *
	 * @return TG_Settings
	 */
	protected function settings() {
		return $this->container->get( TG_Settings::class );
	}


	/**
	 * Get Telegram API instance
	 *
	 * @return Api
	 */
	public function get_api(): Api {
		if ( ! isset( $this->api ) ) {
			$this->api = new Api( $this->settings()->get_string( 'bot_token' ) );
			$this->api->addCommand( Start_Command::class );
			$this->api->addCommand( Start_Group_Command::class );
		}
		return $this->api;
	}

	/**
	 * Get URL prefix
	 *
	 * @return string
	 */
	protected function get_prefix(): string {
		return $this->settings()->get_url_prefix();
	}

	/**
	 * Initialize Telegram integration
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
	}

	/**
	 * Get webhook link
	 *
	 * @return string
	 */
	public function get_webhook_link(): string {
		return home_url( "{$this->get_prefix()}/webhook/" );
	}

	/**
	 * Set webhook
	 *
	 * @return bool
	 */
	public function set_webhook(): bool {
		return $this->get_api()->setWebhook( array( 'url' => $this->get_webhook_link() ) );
	}

	/**
	 * Get chat link
	 *
	 * @return string|null
	 */
	public function get_chat_link(): ?string {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$unique_parameter = $this->container->get( User_Storage::class )->get_user_unique_parameter( get_current_user_id() );
		$name = $this->settings()->get_string( 'bot_name' );
		return "https://t.me/{$name}?start={$unique_parameter}";
	}


	/**
	 * Send Telegram message
	 *
	 * @param Tg_Message_Interface $mes Message to send.
	 */
	public function send( Tg_Message_Interface $mes ): void {
		if ( $this->is_develop() ) {
			$environment = $this->container->get( Environment::class );
			$path = $environment->logs_path();
			if ( null !== $path ) {
				$fp = fopen( $path . '/tg.log', 'a+' );
				if ( false !== $fp ) {
					fwrite( $fp, 'To: ' . implode( ', ', $mes->to() ) . "\n" );
					fwrite( $fp, $mes->content() . "\n" );
					fclose( $fp );
				}
			}

			return;
		}

		foreach ( $mes->to() as $chat_id ) {
			try {
				$this->get_api()->sendMessage(
					array(
						'chat_id' => $chat_id,
						'text' => $mes->content(),
						'parse_mode' => 'html',
					)
				);
			} catch ( \Throwable $th ) {
				error_log( $th->getMessage() );
			}
		}
	}

	/**
	 * Fires before determining which template to load.
	 */
	public function action_template_redirect(): void {
		if ( ! get_query_var( 'tg' ) ) {
			return;
		}

		switch ( get_query_var( 'tg' ) ) {
			case 'webhook':
				try {
					/**
					 * Update object.
					 *
					 * @var Update
					 */
					$update = $this->get_api()->commandsHandler( true );
					/**
					 * Fires on telegram update recieve.
					 *
					 * @param Update $update         Update object.
					 */
					do_action( 'dnt_notify_telegram_update', $update );
				} catch ( \Throwable $th ) {
					error_log( $th->getMessage() );
					error_log( $th->getTraceAsString() );
				}

				print_r( 'ok' );
				break;

			default:
				// code...
				break;
		}
		exit;
	}

	/**
	 * Initialize rewrite rules
	 */
	public function action_init(): void {
		add_rewrite_rule( '^' . $this->get_prefix() . '/([^/]*)/?', 'index.php?tg=$matches[1]', 'top' );
		add_rewrite_tag( '%tg%', '([^&]+)' );
	}

	/**
	 * Get group info
	 *
	 * @param string $group_id Group ID.
	 * @return array{title:string,type:string}|null
	 */
	public function get_group_info( string $group_id ): ?array {
		try {
			if ( $this->is_develop() ) {
				return array(
					'title' => 'Тестовая группа',
					'type' => 'group',
				);
			} else {
				$api = $this->get_api();
				$chat = $api->getChat( array( 'chat_id' => $group_id ) );

				// @phpstan-ignore return.type
				return array(
					// @phpstan-ignore method.notFound
					'title' => $chat->getTitle(),
					// @phpstan-ignore method.notFound
					'type' => $chat->getType(),
				);
			}
		} catch ( \Exception $e ) {
			// If failed to get group info, return null.
			return null;
		}
	}
}
