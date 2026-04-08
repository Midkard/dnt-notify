<?php
/**
 * Telegram admin functionality for the DNT Notify plugin.
 *
 * This file contains the TG_Admin class which handles Telegram-related admin functionality.
 *
 * @package dnt_notify\tg
 */

namespace dnt_notify\tg;

use dnt_notify\Service;
use dnt_notify\user\User_Storage;
use WpOrg\Requests\Response;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for testing Telegram admin functionality.
 *
 * @phpstan-type WPResponse array{headers: \WpOrg\Requests\Utility\CaseInsensitiveDictionary, body: string, response: array{code: int, message: string}, cookies: array<int, \WP_Http_Cookie>, filename: string|null, http_response: \WP_HTTP_Requests_Response}
 */
class TG_Test extends Service {


	/**
	 * Gets the Telegram api.
	 *
	 * @return Tg The Telegram instance.
	 */
	protected function get_api(): Tg {
		return $this->container->get( Tg::class );
	}


	/**
	 * Handles the test add current user action.
	 *
	 * This method add the current user to the list of Telegram users.
	 *
	 * @return WPResponse
	 * @throws \Exception Invalid environment.
	 */
	public function add_current_user() {
		if ( ! $this->is_develop() ) {
			throw new \Exception( 'Only in dev environment', 1 );
		}

		return $this->request_to_webhook(
			array(
				'update_id' => 10000,
				'message' => array(
					'date' => 1441645532,
					'chat' => array(
						'last_name' => 'Test Lastname',
						'id' => 1111111,
						'type' => 'private',
						'first_name' => 'Test Firstname',
						'username' => 'Testusername',
					),
					'message_id' => 1365,
					'from' => array(
						'last_name' => 'Test Lastname',
						'id' => 1111111,
						'first_name' => 'Test Firstname',
						'username' => 'Testusername',
					),
					'text' => '/start ' . $this->container->get( User_Storage::class )->get_user_unique_parameter( get_current_user_id() ),
					'entities' => array(
						array(
							'offset' => 0,
							'length' => 6,
							'type' => 'bot_command',
						),
					),
				),
			)
		);
	}

	/**
	 * Handles the test add group.
	 *
	 * @return WPResponse
	 *
	 * @throws \Exception Invalid environment.
	 */
	public function add_group() {
		if ( ! $this->is_develop() ) {
			throw new \Exception( 'Only in dev environment', 1 );
		}

		return $this->request_to_webhook(
			array(
				'update_id' => 10000,
				'message' => array(
					'date' => 1441645532,
					'chat' => array(
						'last_name' => 'Test Lastname',
						'id' => rand(),
						'type' => 'group',
						'first_name' => 'Test Firstname',
						'username' => 'Testusername',
					),
					'message_id' => 1365,
					'from' => array(
						'last_name' => 'Test Lastname',
						'id' => 1111111,
						'first_name' => 'Test Firstname',
						'username' => 'Testusername',
					),
					'text' => '/startgroup ' . $this->container->get( TG_Settings::class )->token_to_add_group(),
					'entities' => array(
						array(
							'offset' => 0,
							'length' => 11,
							'type' => 'bot_command',
						),
					),
				),
			)
		);
	}

	/**
	 * Handles the test action.
	 *
	 * @param array<int|string,mixed> $data Data to send.
	 *
	 * @return WPResponse
	 * @throws \Exception Response error.
	 */
	protected function request_to_webhook( $data ) {
		$args = array(
			'body' => $data,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method' => 'POST',
			'data_format' => 'body',
		);

		$response = wp_remote_post( $this->get_api()->get_webhook_link(), $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			throw new \Exception( esc_html( $error_message . ' Url:' . $this->get_api()->get_webhook_link() ), 1 );
		}

		return $response;
	}
}
