<?php
/**
 * Plugin Name: DNT Notify
 * Description: Adds support for notifications in VK and Telegram, as well as SMTP configuration.
 * Version: 0.0.0
 * Author: Dimenius Novus
 * Requires at least: 6.5
 * Tested up to: 6.8.3
 * Requires PHP: 8.1
 * Domain Path: /languages
 * Text Domain: dnt-notify
 *
 * @package dnt_notify
 */

namespace dnt_notify;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


// Define plugin constants.
define( 'DNT_NOTIFY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DNT_NOTIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Получение экземпляра плагина
 *
 * @return Main
 */
function get_plugin_instance() {
	/**
	 * Экземпляр плагина.
	 *
	 * @var Main|null
	 */
	static $ins;
	if ( ! empty( $ins ) ) {
		return $ins;
	}
	$ins = new Main();
	return $ins;
}

/**
 * Получение контейнера плагина
 *
 * @return Container
 */
function get_container() {
	$instance = get_plugin_instance();
	return $instance->get_container();
}

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		get_plugin_instance();
	}
);

// Activation and deactivation hooks.
register_activation_hook(
	__FILE__,
	function () {
		$main = get_plugin_instance();
		$main->activate();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		$main = get_plugin_instance();
		$main->deactivate();
	}
);
