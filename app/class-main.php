<?php
/**
 * Main plugin class
 *
 * @package dnt_notify
 */

namespace dnt_notify;

use dnt_notify\form\Tg_Form_Message;
use dnt_notify\form\VK_Form_Message;
use dnt_notify\tg\Tg;
use dnt_notify\tg\TG_Admin;
use dnt_notify\tg\TG_Settings;
use dnt_notify\vk\VK;
use dnt_notify\vk\VK_Admin;
use dnt_notify\vk\VK_Settings;
use dnt_notify\woo\New_Order_Tg_Message;
use dnt_notify\woo\Woo_Integration;


if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Main class - the main entry point of the plugin
 */
class Main {



	/**
	 * DI container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor
	 *
	 * @param Container|null $container Container instance (optional).
	 */
	public function __construct( ?Container $container = null ) {
		// If container is not provided, create a new one.
		if ( null === $container ) {
			$container = new Container();
			// @phpstan-ignore argument.type
			$container->set( Tg_Form_Message::class, fn( $container, $props ) => new Tg_Form_Message( $props ), false );
			// @phpstan-ignore argument.type
			$container->set( New_Order_Tg_Message::class, fn( $container, $props ) => new New_Order_Tg_Message( $props ), false );
			// @phpstan-ignore argument.type
			$container->set( VK_Form_Message::class, fn( $container, $props ) => new VK_Form_Message( $props ), false );
		}

		$this->container = $container;
		add_action( 'after_setup_theme', array( $this, 'init' ) );
	}

	/**
	 * Get container
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Plugin initialization
	 *
	 * @return void
	 */
	public function init() {
		// Get services from the container.
		// $assets = $this->container->get( Assets::class ).
		// $api = $this->container->get( API::class ).

		// WordPress hooks.
		$this->container->get( TG_Settings::class )->register_settings();
		$this->container->get( Tg::class )->init();

		$this->container->get( VK_Settings::class )->register_settings();
		$this->container->get( VK::class )->init();

		$this->container->get( Woo_Integration::class )->init();
		// $assets->init();
		// $api->init();

		// Admin panel initialization (only in admin).
		if ( is_admin() ) {
			$this->container->get( Admin_Group::class )->init();
			$this->container->get( TG_Admin::class )->init();
			$this->container->get( VK_Admin::class )->init();
		}
	}



	/**
	 * Plugin activation
	 *
	 * @return void
	 */
	public function activate() {
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 *
	 * @return void
	 */
	public function deactivate() {
	}
}
