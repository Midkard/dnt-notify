<?php
/**
 * VK form message functionality for the DNT Notify plugin.
 *
 * @package dnt_notify\form
 */

namespace dnt_notify\form;

use dnt_notify\vk\VK_Message_Interface;
use dnt_notify\vk\VK_Settings;
use dnt_notify\user\User_Storage;
use function dnt_notify\get_container;

/**
 * Class for handling VK form messages.
 */
class VK_Form_Message implements VK_Message_Interface {

	/**
	 * Data for the form message.
	 *
	 * @var array<string, mixed>
	 */
	protected $data;

	/**
	 * Constructor for the VK_Form_Message class.
	 *
	 * @param array<string, mixed> $data The data for the form message.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Gets the recipients for the VK message.
	 *
	 * Retrieves all administrators and merges them with the configured groups.
	 *
	 * @return array<int|string> The list of peer IDs to send the message to.
	 */
	public function to(): array {
		$container = get_container();

		/**
		 * Administrators IDs.
		 *
		 * @var int[]
		 */
		$users = get_users(
			array(
				'role__in' => array( 'administrator' ),
				'number' => -1,
				'fields' => 'ID',
			)
		);
		$peers = $container->get( User_Storage::class )->get_vk_chats( $users );
		$groups = $container->get( VK_Settings::class )->get_groups();
		return array_merge( $peers, $groups );
	}

	/**
	 * Generates the content of the VK message.
	 *
	 * @return string The formatted VK message content.
	 */
	public function content(): string {
		$title = isset( $this->data['title'] ) && is_string( $this->data['title'] ) ? $this->data['title'] : 'Сообщение';
		$mess = $title . "\n";

		$mess .= $this->contact_table();

		return $mess;
	}

	/**
	 * Generates a contact table from the form data.
	 *
	 * @return string The formatted contact table.
	 */
	protected function contact_table(): string {
		$data = $this->data;

		unset( $data['title'] );
		$mess = $this->print_array( $data );
		return $mess;
	}

	/**
	 * Prints an array as a formatted string.
	 *
	 * @param array<int|string, mixed> $array The array to print.
	 * @param int                      $depth The current depth of the array.
	 * @return string The formatted string representation of the array.
	 */
	protected function print_array( array $array, int $depth = 0 ): string {
		$mess = '';
		foreach ( $array as $key => $value ) {
			if ( 'separator' === $value ) {
				$mess .= "\n";
				continue;
			}
			if ( is_int( $key ) ) {
				$mess .= str_pad( '', $depth * 2 );
				if ( count( $array ) > 1 ) {
					$mess .= (string) ( $key + 1 ) . '.';
				}
			} else {
				$key_str = $key;
				$mess .= str_pad( '', ( $depth + 1 ) * 2 ) . $key_str . ':';
			}
			if ( is_array( $value ) ) {
				$mess .= "\n";
				$mess .= $this->print_array( $value, $depth + 1 );
			} else {
				$value_str = is_scalar( $value ) ? (string) $value : '';
				$mess .= " {$value_str}\n";
			}
		}
		return $mess;
	}
}
