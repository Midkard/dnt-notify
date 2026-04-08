<?php
/**
 * Helper functions for the DNT Notify plugin.
 *
 * This file contains helper functions used throughout the plugin.
 *
 * @package dnt_notify
 */

namespace dnt_notify;

use dnt_notify\tg\Tg;
use dnt_notify\tg\Tg_Message_Interface;
use dnt_notify\vk\VK;
use dnt_notify\vk\VK_Message_Interface;

/**
 * Sends a Telegram message.
 *
 * This function sends a Telegram message using the provided message interface.
 *
 * @param Tg_Message_Interface $mes The Telegram message to send.
 *
 * @return void
 */
function send_tg_message( Tg_Message_Interface $mes ): void {
	get_container()->get( Tg::class )->send( $mes );
}

/**
 * Sends a VK message.
 *
 * This function sends a VK message using the provided message interface.
 *
 * @param VK_Message_Interface $mes The VK message to send.
 *
 * @return void
 */
function send_vk_message( VK_Message_Interface $mes ): void {
	get_container()->get( VK::class )->send( $mes );
}
