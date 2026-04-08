<?php
/**
 * VK integration class
 *
 * @package dnt_notify
 */

namespace dnt_notify\vk;

use dnt_notify\Environment;
use dnt_notify\Service;
use dnt_notify\user\User_Storage;
use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * VK integration class
 */
class VK extends Service {

	/**
	 * VK API instance
	 *
	 * @var VKApiClient|null
	 */
	protected $api;

	/**
	 * Get VK settings
	 *
	 * @return VK_Settings
	 */
	protected function settings(): VK_Settings {
		return $this->container->get( VK_Settings::class );
	}

	/**
	 * Get VK API instance
	 *
	 * @return VKApiClient|null
	 */
	public function get_api(): ?VKApiClient {
		if ( ! isset( $this->api ) ) {
			$this->api = new VKApiClient();
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
	 * Initialize VK integration
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
	}

	/**
	 * Get callback URL
	 *
	 * @return string
	 */
	public function get_callback_url(): string {
		return home_url( "{$this->get_prefix()}/vk/callback/" );
	}

	/**
	 * Initialize rewrite rules
	 */
	public function action_init(): void {
		add_rewrite_rule( '^' . $this->get_prefix() . '/vk/([^/]*)/?', 'index.php?vk_callback=$matches[1]', 'top' );
		add_rewrite_tag( '%vk_callback%', '([^&]+)' );
	}

	/**
	 * Fires before determining which template to load.
	 */
	public function action_template_redirect(): void {
		if ( ! get_query_var( 'vk_callback' ) ) {
			return;
		}

		$callback_type = get_query_var( 'vk_callback' );

		switch ( $callback_type ) {
			case 'callback':
				$handler = new VK_Callback_Handler( $this->container );
				$handler->handle();
				break;

			default:
				// code...
				break;
		}
		exit;
	}

	/**
	 * Send VK message
	 *
	 * @param VK_Message_Interface $mes Message to send.
	 */
	public function send( VK_Message_Interface $mes ): void {
		$api = $this->get_api();
		if ( null === $api ) {
			return;
		}

		$access_token = $this->settings()->get_access_token();
		if ( empty( $access_token ) ) {
			return;
		}

		if ( $this->is_develop() ) {
			$environment = $this->container->get( Environment::class );
			$path = $environment->logs_path();
			if ( null !== $path ) {
				$fp = fopen( $path . '/vk.log', 'a+' );
				if ( false !== $fp ) {
					fwrite( $fp, 'To: ' . implode( ', ', $mes->to() ) . "\n" );
					fwrite( $fp, $mes->content() . "\n" );
					fclose( $fp );
				}
			}

			return;
		}

		foreach ( $mes->to() as $peer_id ) {
			try {
				$api->messages()->send(
					$access_token,
					array(
						'peer_id' => (int) $peer_id,
						'message' => $mes->content(),
						'random_id' => wp_rand( 1, PHP_INT_MAX ),
					)
				);
			} catch ( VKApiException $e ) {
				error_log( 'VK API Error: ' . $e->getMessage() );
			} catch ( VKClientException $e ) {
				error_log( 'VK Client Error: ' . $e->getMessage() );
			} catch ( \Throwable $th ) {
				error_log( $th->getMessage() );
			}
		}
	}

	/**
	 * Get chat link for user connection
	 *
	 * @return array{link:string,text:string}|null
	 */
	public function get_chat_link(): ?array {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$group_id = $this->settings()->get_group_id();
		if ( empty( $group_id ) ) {
			return null;
		}

		$unique_parameter = $this->container->get( User_Storage::class )->get_user_unique_parameter( get_current_user_id() );

		return array(
			'link' => "https://vk.com/write-{$group_id}",
			'text' => "/start {$unique_parameter}",
		);
	}

	/**
	 * Get conversation info
	 *
	 * @param string $peer_id Peer ID.
	 * @return array{title:string,type:string}|null
	 */
	public function get_conversation_info( string $peer_id ): ?array {
		$api = $this->get_api();
		if ( null === $api ) {
			return null;
		}

		$access_token = $this->settings()->get_access_token();
		if ( empty( $access_token ) ) {
			return null;
		}

		try {
			if ( $this->is_develop() ) {
				return array(
					'title' => 'Тестовая беседа',
					'type' => 'chat',
				);
			} else {
				$response = $api->messages()->getConversationsById(
					$access_token,
					array(
						'peer_ids' => array( (int) $peer_id ),
						'extended' => 1,
					)
				);

				if ( is_array( $response ) && isset( $response['items'] ) && is_array( $response['items'] ) && isset( $response['items'][0] ) && is_array( $response['items'][0] ) ) {
					$item = $response['items'][0];
					$chat_settings = isset( $item['chat_settings'] ) && is_array( $item['chat_settings'] ) ? $item['chat_settings'] : array();
					$title = isset( $chat_settings['title'] ) && is_scalar( $chat_settings['title'] ) ? strval( $chat_settings['title'] ) : 'Беседа';
					$type = isset( $item['type'] ) && is_scalar( $item['type'] ) ? strval( $item['type'] ) : 'unknown';
					return array(
						'title' => $title,
						'type' => $type,
					);
				}

				return null;
			}
		} catch ( \Exception $e ) {
			// If failed to get conversation info, return null.
			return null;
		}
	}
}
