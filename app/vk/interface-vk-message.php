<?php
/**
 * Interface for VK message
 *
 * @package dnt_notify
 */

namespace dnt_notify\vk;

interface VK_Message_Interface {
	/**
	 * Get message content
	 *
	 * @return string
	 */
	public function content(): string;

	/**
	 * Users ids
	 *
	 * @return int[]
	 */
	public function to();
}
