<?php
/**
 * WooCommerce integration for the DNT Notify plugin.
 *
 * This file contains the Woo_Integration class which handles WooCommerce-related functionality.
 *
 * @package dnt_notify\woo
 */

namespace dnt_notify\woo;

use dnt_notify\Service;
use function dnt_notify\send_tg_message;

/**
 * Class for handling WooCommerce integration.
 *
 * This class provides methods to integrate with WooCommerce events.
 */
class Woo_Integration extends Service {

	/**
	 * Initializes the WooCommerce integration.
	 *
	 * This method sets up the necessary hooks for WooCommerce events.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add action for WooCommerce payment complete.
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_recieved' ) );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function order_recieved( $order ): void {
		$order = wc_get_order( $order );

		if ( is_object( $order ) && is_a( $order, 'WC_Order' ) ) {
			$crm_already_sent = $order->get_meta( '_dnt_notify_new_order_notify_sent' );
		} else {
			return;
		}

		if ( 'true' === $crm_already_sent ) {
			return;
		}

		send_tg_message( $this->container->get( New_Order_Tg_Message::class, $order ) );

		$order->update_meta_data( '_dnt_notify_new_order_notify_sent', 'true' );
		$order->save();
	}
}
