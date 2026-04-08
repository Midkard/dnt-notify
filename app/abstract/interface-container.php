<?php
/**
 * Интерфейс для DI контейнера
 *
 * @package dnt_notify
 */

namespace dnt_notify;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Interface Container_Interface
 */
interface Container_Interface {

	/**
	 * Регистрация сервиса в контейнере
	 *
	 * @param string   $id       Идентификатор сервиса.
	 * @param callable $factory  Фабричная функция для создания сервиса.
	 * @param bool     $shared   Является ли сервис синглтоном (по умолчанию true).
	 * @return void
	 */
	public function set( string $id, callable $factory, bool $shared = true );

	/**
	 * Получение сервиса из контейнера
	 *
	 * @template T
	 * @param class-string<T> $id Идентификатор сервиса.
	 * @param mixed           $props Параметры.
	 * @return T
	 * @throws \Exception Если сервис не найден.
	 */
	public function get( $id, $props = null );

	/**
	 * Проверка наличия сервиса в контейнере
	 *
	 * @param string $id Идентификатор сервиса.
	 * @return bool
	 */
	public function has( string $id ): bool;

	/**
	 * Удаление сервиса из контейнера
	 *
	 * @param string $id Идентификатор сервиса.
	 * @return void
	 */
	public function remove( string $id );
}
