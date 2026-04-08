<?php
/**
 * Класс для админ-панели плагина
 *
 * @package dnt_notify
 */

namespace dnt_notify;

use dnt_notify\Base_Settings;


if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Класс Admin - админ-панель плагина
 */
class Admin_Group extends Service {


	/**
	 * Инициализация админ-панели
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string The menu slug.
	 */
	public function menu_slug(): string {
		return 'dnt-notify';
	}

	/**
	 * Подключение стилей для админ-панели
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style(
			'dnt-notify-admin-css',
			plugins_url( 'public/admin.css', __DIR__ ),
			array(),
			(string) filemtime( plugin_dir_path( __DIR__ ) . 'public/admin.css' )
		);
	}

	/**
	 * Добавление меню в админ-панель
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			'DNT Notify Settings',
			'Notify',
			'manage_options',
			$this->menu_slug(),
			'__return_empty_string'
		);
	}
}
