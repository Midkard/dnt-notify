<?php
/**
 * New Order Telegram Message.
 *
 * This file contains the New_Order_Tg_Message class which handles Telegram messages for new orders.
 *
 * @package dnt_notify\woo
 */

namespace dnt_notify\woo;

use dnt_notify\tg\Tg_Message_Interface;
use dnt_notify\tg\TG_Settings;
use dnt_notify\user\User_Storage;
use function dnt_notify\get_container;

/**
 * Class for handling Telegram messages for new orders.
 */
class New_Order_Tg_Message implements Tg_Message_Interface {


	/**
	 * The WooCommerce order object.
	 *
	 * @var \WC_Order
	 */
	protected $order;

	/**
	 * Constructor for the New_Order_Tg_Message class.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 */
	public function __construct( $order ) {
		$this->order = $order;
	}

	/**
	 * Gets the list of recipients for the Telegram message.
	 *
	 * Retrieves all administrators and merges them with the configured groups.
	 *
	 * @return array<int|string> The list of recipient chat IDs.
	 */
	public function to(): array {
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
		$chats = get_container()->get( User_Storage::class )->get_tg_chats( $users );
		$groups = get_container()->get( TG_Settings::class )->get_groups();
		return array_merge( $chats, $groups );
	}

	/**
	 * Generates the content of the Telegram message for the new order.
	 *
	 * Formats the order details including items, discounts, shipping, and customer information
	 * into a structured Telegram message.
	 *
	 * @return string The formatted Telegram message content.
	 */
	public function content(): string {
		$order = $this->order;

		$mess = '<b>Заказ №' . $order->get_order_number() . "</b>\n";

		$basket = array();
		// Add line items.
		$key = 1;
		foreach ( $order->get_items() as $item ) {
			try {
				$quantity = $item->get_quantity();

				// Price per item.
				$price_per_item = $order->get_item_subtotal( $item );

				// Total amount for the item.
				$line_total = $order->get_line_subtotal( $item );

				// Collect attributes, excluding "Weight".
				/**
				 * Formatted meta data for the item.
				 *
				 * @var object{display_key:string,display_value:string}[] $formatted_meta_data
				 */
				$formatted_meta_data = $item->get_all_formatted_meta_data();
				$meta_parts = array();
				foreach ( $formatted_meta_data as $formatted_meta ) {
					if ( 'Вес' === $formatted_meta->display_key ) {
						continue;
					}
					$meta_parts[] = wp_strip_all_tags( trim( $formatted_meta->display_value ) );
				}

				// Get the "Roast" attribute.
				/**
				 * Product associated with the item.
				 *
				 * @var \WC_Product|false
				 */
				$product = method_exists( $item, 'get_product' ) ? $item->get_product() : false;
				if ( $product ) {
					$terms = get_the_terms( $product->get_id(), 'pa_sposob-obzharki' );
					if ( empty( $terms ) && $product->get_parent_id() ) {
						$terms = get_the_terms( $product->get_parent_id(), 'pa_sposob-obzharki' );
					}
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						$term = array_shift( $terms );
						$meta_parts[] = esc_html( $term->name );
					}
				}

				$meta_string = ! empty( $meta_parts ) ? ' (' . implode( ', ', $meta_parts ) . ')' : '';

				// Format the price as whole numbers.
				$formatted_line_total = wc_format_decimal( $line_total, 0 ) . ' ' . $order->get_currency();
				$formatted_price_per_item = wc_format_decimal( $price_per_item, 0 );

				$basket[] = sprintf(
					'%d. %s.%s — %s (%d × %s)',
					$key++,
					$item->get_name(),
					$meta_string,
					$formatted_line_total,
					$quantity,
					$formatted_price_per_item
				);
			} catch ( \Exception $e ) {
				$basket[] = 'Ошибка обработки товара: ' . $item->get_name();
				error_log( 'Telegram Notification Error: ' . $e->getMessage() );
				continue;
			}
		}

		$mess .= implode( "\n", $basket );
		$mess .= "\n";
		$mess .= '<b>Итого по товарам:</b> ' . $order->get_subtotal() . ' ' . $order->get_currency() . "\n";
		$mess .= "\n";

		if ( 0 < $order->get_total_discount() ) {
			$coupons = $order->get_items( 'coupon' );
			$coupon = reset( $coupons );
			if ( $coupon ) {
				$mess .= '<b>Промокод:</b> ' . $coupon->get_name() . "\n";
			}
			$mess .= '<b>Скидка:</b> ' . $order->get_total_discount() . ' ' . $order->get_currency() . "\n";
		}

		$mess .= "\n";
		$mess .= '<b>Доставка:</b> ' . $order->get_shipping_total() . ' ' . $order->get_currency() . "\n";
		$mess .= $order->get_billing_city() . "\n";
		$address = $order->get_billing_address_1();

		/**
		 * Meta data for the order.
		 *
		 * @var \WC_Meta_Data[]
		 */
		$meta_data = $order->get_meta_data();
		foreach ( $meta_data as $value ) {
			$meta = $value->get_data();
			if ( '_yandex_delivery_destination_station_address' === $meta['key'] && '' !== $meta['value'] ) {
				/**
				 * Delivery address from meta data.
				 *
				 * @var string
				 */
				$address = $meta['value'];
			}
		}
		foreach ( $order->get_shipping_methods() as $shipping ) {
			$mess .= $shipping->get_name() . ":\n";
			/**
			 * Meta data for the shipping method.
			 *
			 * @var \WC_Meta_Data[]
			 */
			$meta_data = $shipping->get_meta_data();
			foreach ( $meta_data as $value ) {
				$meta = $value->get_data();

				if ( class_exists( '\Cdek\MetaKeys' ) && \Cdek\MetaKeys::OFFICE_CODE === $meta['key'] ) {
					/**
					 * Office code from meta data.
					 *
					 * @var string
					 */
					$office = $meta['value'];
					if ( '' !== $office && class_exists( '\Cdek\CdekApi' ) ) {
						try {
							/**
							 * Office information.
							 *
							 * @var array{location:array{address:string}}|null
							 */
							$office_info = ( new \Cdek\CdekApi() )->officeGet( $office );

							if ( null === $office_info ) {
								$address = esc_html__( 'Not available for order', 'dnt_notify' );
							} else {
								$address = sprintf(
									'%s (%s)',
									$office,
									$office_info['location']['address'],
								);
							}
						} catch ( \Exception $exception ) {
							$address = esc_html__( 'Not available for order', 'dnt_notify' );
						}
					}
				}
			}
			$mess .= $address . "\n";
		}

		$mess .= "\n";
		$mess .= '<b>ИТОГО:</b> ' . wc_format_decimal( $order->get_total() ) . ' ' . $order->get_currency() . "\n";
		if ( $order->get_date_paid() ) {
			/**
			 * Time format from WordPress settings.
			 *
			 * @var string
			 */
			$format = get_option( 'time_format' );
			$mess .= sprintf(
				/* translators: 1: date 2: time */
				__( 'Paid on %1$s @ %2$s', 'dnt_notify' ),
				wc_format_datetime( $order->get_date_paid() ),
				wc_format_datetime( $order->get_date_paid(), $format )
			);
			$mess .= "\n";
			$mess .= sprintf(
				'Номер транзакции: %1$s',
				$order->get_transaction_id()
			);
			$mess .= "\n";
		}
		$mess .= "\n";

		$mess .= '<b>Информация о покупателе</b>' . "\n";
		$mess .= 'ФИО: ' . $order->get_billing_first_name() . "\n";
		$mess .= 'Телефон: ' . $order->get_billing_phone() . "\n";
		$mess .= 'Email: ' . $order->get_billing_email() . "\n";

		return $mess;
	}
}
