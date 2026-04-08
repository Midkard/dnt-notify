<?php
/**
 * Interface for Telegram message
 *
 * @package dnt_notify
 */

namespace dnt_notify\tg;

interface Tg_Message_Interface {
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
