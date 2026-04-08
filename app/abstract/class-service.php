<?php
/**
 * Класс для админ-панели плагина
 *
 * @package dnt_notify
 */

namespace dnt_notify;

use dnt_notify\Container_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Базовый класс для использования контейнера
 */
class Service {


	/**
	 * Контейнер
	 *
	 * @var Container_Interface
	 */
	protected $container;

	/**
	 * Конструктор
	 *
	 * @param Container_Interface $container Экземпляр контейнера.
	 */
	public function __construct( Container_Interface $container ) {
		$this->container = $container;
	}

	/**
	 * Check if the environment is in development mode.
	 *
	 * @return bool True if in development mode, false otherwise.
	 */
	protected function is_develop(): bool {
		return $this->container->get( Environment::class )->is_develop();
	}
}
