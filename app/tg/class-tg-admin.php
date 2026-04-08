<?php
/**
 * Telegram admin functionality for the DNT Notify plugin.
 *
 * This file contains the TG_Admin class which handles Telegram-related admin functionality.
 *
 * @package dnt_notify\tg
 */

namespace dnt_notify\tg;

use dnt_notify\Admin_Group;
use dnt_notify\Base_Settings;
use dnt_notify\form\Tg_Form_Message;
use dnt_notify\Service;
use dnt_notify\user\User_Storage;
use function dnt_notify\send_tg_message;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for handling Telegram admin functionality.
 *
 * This class extends the Service class and provides methods to manage Telegram settings in the admin panel.
 */
class TG_Admin extends Service {






	/**
	 * Gets the menu slug for the admin page.
	 *
	 * @return string The menu slug.
	 */
	public function menu_slug(): string {
		return 'dnt-notify-tg';
	}

	/**
	 * Initializes the admin panel.
	 *
	 * This method sets up the necessary hooks for the admin panel.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Process group removal.
		add_action(
			'admin_init',
			function () {
				// Check capabilities.
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				// Handle manual group addition form.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['add_tg_group_submit'] ) ) {
					$this->action_add_group_manual();
				}

				if ( empty( $_GET['action'] ) ) {
					return;
				}
				/**
				 * Action from the GET request.
				 *
				 * @var string
				 * @phpstan-ignore argument.type
				 */
				$get_action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
				if ( ! str_starts_with( $get_action, $this->menu_slug() . '_' ) ) {
					return;
				}
				$action = str_replace( $this->menu_slug() . '_', '', $get_action );

				switch ( $action ) {
					case 'test_message':
						$this->action_test_message();
						break;
					case 'remove_group':
						$this->action_remove_group();
						break;
					case 'test_add_current_user':
						$this->action_test_add_current_user();
						break;
					case 'test_add_group':
						$this->action_test_add_group();
						break;
					case 'remove_current_user':
						$this->action_remove_current_user();
						break;
					case 'set_webhook':
						$this->action_set_webhook();
						break;
				}
				// If no settings errors were registered add a general 'updated' message.
				$errors = get_settings_errors();
				if ( ! count( $errors ) ) {
					add_settings_error( $this->get_settings()->key, 'settings_updated', __( 'Settings saved.', 'dnt_notify' ), 'success' );
				}

				set_transient( 'settings_errors', get_settings_errors(), 30 ); // 30 seconds.

				// Redirect back to the settings page that was submitted.
				$goback = add_query_arg( 'settings-updated', 'true' );
				$goback = remove_query_arg(
					array(
						'action',
						'group_id',
						'_wpnonce',
					),
					$goback
				);
				wp_redirect( $goback );
				exit;
			}
		);
	}

	/**
	 * Gets the Telegram settings.
	 *
	 * @return TG_Settings The Telegram settings instance.
	 */
	protected function get_settings(): TG_Settings {
		return $this->container->get( TG_Settings::class );
	}

	/**
	 * Gets the Telegram api.
	 *
	 * @return Tg The Telegram instance.
	 */
	protected function get_api(): Tg {
		return $this->container->get( Tg::class );
	}

	/**
	 * Handles the test message action.
	 *
	 * This method sends a test message to Telegram.
	 *
	 * @return void
	 */
	protected function action_test_message(): void {

		send_tg_message(
			$this->container->get(
				Tg_Form_Message::class,
				array(
					'title' => 'Тест',
					'name' => 'От сайта',
				)
			)
		);

		add_settings_error( $this->get_settings()->key, 'send', 'Уведомление отправлено!', 'success' );
	}

	/**
	 * Handles the remove group action.
	 *
	 * This method removes a group from the list of Telegram groups.
	 *
	 * @return void
	 */
	protected function action_remove_group(): void {
		if ( empty( $_GET['group_id'] ) || ! is_string( $_GET['group_id'] ) ) {
			return;
		}
		$group_id = sanitize_text_field( wp_unslash( $_GET['group_id'] ) );
		if ( ! $this->verify_nonce( 'remove_tg_group_' . $group_id ) ) {
			return;
		}

		$settings = $this->get_settings();
		$settings->remove_group( $group_id );
		add_settings_error( $settings->key, 'removed', 'Группа успешно отключена!', 'success' );
	}

	/**
	 * Handles the test add group action.
	 *
	 * @return void
	 */
	protected function action_test_add_group(): void {
		if ( ! $this->is_develop() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'test_add_group' ) ) {
			return;
		}

		$test = $this->container->get( TG_Test::class );
		$test->add_group();
		add_settings_error( $this->get_settings()->key, 'removed', 'Запрос отправлен', 'success' );
	}

	/**
	 * Handles the test add current user action.
	 *
	 * This method add the current user to the list of Telegram users.
	 *
	 * @return void
	 */
	protected function action_test_add_current_user(): void {
		if ( ! $this->is_develop() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'test_add_current_user' ) ) {
			return;
		}

		$test = $this->container->get( TG_Test::class );
		$test->add_current_user();
		add_settings_error( $this->get_settings()->key, 'removed', 'Запрос отправлен', 'success' );
	}


	/**
	 * Handles the remove current user action.
	 *
	 * This method removes the current user from the list of Telegram users.
	 *
	 * @return void
	 */
	protected function action_remove_current_user(): void {
		if ( ! $this->verify_nonce( 'remove_current_user' ) ) {
			return;
		}

		$this->container->get( User_Storage::class )->remove_tg_chat( get_current_user_id() );

		add_settings_error( $this->get_settings()->key, 'removed', 'Пользователь успешно отключен!', 'success' );
	}

	/**
	 * Handles the set webhook action.
	 *
	 * This method sets the webhook for the Telegram bot.
	 *
	 * @return void
	 */
	protected function action_set_webhook(): void {
		if ( ! $this->verify_nonce( 'set_webhook' ) ) {
			return;
		}
		$settings = $this->get_settings();
		$tg = $this->get_api();
		try {
			if ( $this->is_develop() ) {
				$response = rand( 1, 2 ) === 2;
			} else {
				$response = $tg->set_webhook();
			}
			add_settings_error( $settings->key, 'set_webhook', $response ? 'Webhook is ready' : 'Error during set webhook', $response ? 'success' : 'error' );
		} catch ( \Throwable $th ) {
			add_settings_error( $settings->key, 'set_webhook', $th->getMessage() );
		}
	}

	/**
	 * Handles manual group addition.
	 *
	 * This method adds a Telegram group or channel by its ID.
	 *
	 * @return void
	 */
	protected function action_add_group_manual(): void {
		// Verify nonce.
		if ( ! isset( $_POST['tg_group_nonce'] ) || ! is_string( $_POST['tg_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tg_group_nonce'] ) ), 'add_tg_group_manual' ) ) {
			add_settings_error( $this->get_settings()->key, 'nonce_error', 'Ошибка проверки безопасности.', 'error' );
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( $this->get_settings()->key, 'permission_error', 'Недостаточно прав.', 'error' );
			return;
		}

		// Get and validate group ID.
		if ( empty( $_POST['tg_group_id'] ) || ! is_string( $_POST['tg_group_id'] ) ) {
			add_settings_error( $this->get_settings()->key, 'empty_group', 'Укажите ID группы.', 'error' );
			return;
		}

		$group_id = sanitize_text_field( wp_unslash( $_POST['tg_group_id'] ) );

		// Trim and convert @username to ID if needed.
		$group_id = trim( $group_id );

		// Validate format.
		if ( ! preg_match( '/^-?\d+$/', $group_id ) && ! preg_match( '/^@\w+$/', $group_id ) ) {
			add_settings_error( $this->get_settings()->key, 'invalid_format', 'Неверный формат ID группы. Используйте числовой ID (например, -1001234567890) или @username.', 'error' );
			return;
		}

		// Add group.
		$settings = $this->get_settings();
		$settings->add_group( $group_id );

		add_settings_error( $settings->key, 'group_added', 'Группа успешно добавлена!', 'success' );
	}

	/**
	 * Nonce verification.
	 *
	 * @param string $action Action id.
	 * @return bool
	 */
	protected function verify_nonce( string $action ): bool {
		/**
		 * Nonce from the GET request.
		 *
		 * @var string
		 * @phpstan-ignore argument.type
		 */
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		$res = wp_verify_nonce( $nonce, $action ) !== false;
		if ( ! $res ) {
			add_settings_error( $this->get_settings()->key, 'nonce', 'Nonce is invalid' );
		}
		return $res;
	}

	/**
	 * Adds the admin menu.
	 *
	 * This method adds the Telegram settings menu to the WordPress admin panel.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			$this->container->get( Admin_Group::class )->menu_slug(),
			'TG Settings',
			'Telegram',
			'manage_options',
			$this->menu_slug(),
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Adds an action to the URL.
	 *
	 * @param string       $name The name of the action.
	 * @param string|false $url  The URL to add the action to.
	 * @return string The URL with the action added.
	 */
	protected function add_action( string $name, $url = false ): string {
		return add_query_arg( 'action', $this->menu_slug() . '_' . $name, $url );
	}

	/**
	 * Render input field.
	 *
	 * @param Base_Settings $settings The settings object.
	 * @param string[]      $key      The key for the setting.
	 * @return void
	 */
	public function render( Base_Settings $settings, array $key ): void {
		$name = implode( '][', $key );
		echo wp_kses(
			"<input type=\"text\" name=\"{$settings->key}[{$name}]\" value=\"" . esc_attr( $settings->get_string( $key ) ?? '' ) . '" />',
			array(
				'input' => array(
					'type' => array(),
					'name' => array(),
					'value' => array(),
				),
			)
		);
	}

	/**
	 * Tg admin page.
	 */
	public function admin_page(): void {
		$settings = $this->get_settings();

		/**
		 * Fields.
		 *
		 * @var array<string,array{title:string}>
		 * @phpstan-ignore offsetAccess.nonOffsetAccessible
		 */
		$fields = $settings->get_schema()['properties'];

		$set_webhook_link = wp_nonce_url(
			$this->add_action( 'set_webhook' ),
			'set_webhook'
		);

		?>
		<div class="wrap dnt-notify-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
			settings_errors( 'general' );
			settings_errors( $settings->key );
			?>
			<form method="post" action="options.php">
				<?php
				settings_fields( $settings->group );
				?>

				<div class="dnt-notify-settings-section">
					<h2>Telegram Settings</h2>
					<table class="form-table" role="presentation">
						<?php
						foreach ( $fields as $key => $field ) :
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $field['title'] ); ?></th>
								<td><?php $this->render( $settings, array( $key ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
					<?php if ( $settings->is_ready() ) : ?>
						<a href="<?php echo esc_url( $set_webhook_link ); ?>">set webhook</a>
					<?php endif; ?>
				</div>

				<?php
				submit_button( 'Save Settings', 'primary', 'submit', true );
				?>
			</form>

			<?php if ( $settings->is_ready() ) : ?>
				<h1>Уведомления TG</h1>
				<?php $this->admin_page_users(); ?>
				<?php $this->admin_page_groups(); ?>

				<h3>Тесты</h3>
				<a href="<?php echo esc_url( $this->add_action( 'test_message' ) ); ?>" class='button button-primary'>Тестовая
					заявка</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Groups part of admin page.
	 */
	protected function admin_page_groups(): void {
		$settings = $this->get_settings();
		$bot_name = $settings->get_string( 'bot_name' );
		$tg = $this->get_api();

		$group_token = $settings->token_to_add_group();
		$groups = $settings->get_groups();

		if ( $this->is_develop() ) {
			$add_link = wp_nonce_url(
				$this->add_action( 'test_add_group' ),
				'test_add_group'
			);
		} else {
			$add_link = false;
		}

		?>

		<h3>Подключенные группы</h3>
		<?php if ( ! empty( $groups ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID группы</th>
						<th>Название группы</th>
						<th>Тип</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $groups as $group_id ) :
						$group_info = $tg->get_group_info( $group_id );
						?>
						<tr>
							<td><code><?php echo esc_html( $group_id ); ?></code></td>
							<td>
								<?php if ( is_array( $group_info ) && $group_info['title'] ) : ?>
									<strong><?php echo esc_html( $group_info['title'] ); ?></strong>
								<?php else : ?>
									<span style="color: #999;">Не удалось получить информацию</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( is_array( $group_info ) ) : ?>
									<?php echo esc_html( ucfirst( $group_info['type'] ) ); ?>
								<?php else : ?>
									<span style="color: #999;">-</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="
								<?php
								echo esc_url(
									wp_nonce_url(
										add_query_arg( 'group_id', $group_id, $this->add_action( 'remove_group' ) ),
										'remove_tg_group_' . $group_id
									)
								);
								?>
								" class='button button-secondary button-small'
									onclick="return confirm('Вы уверены, что хотите отключить эту группу?')">
									Отключить
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p>Нет подключенных групп.</p>
		<?php endif; ?>



		<h3>Подключить группу</h3>
		<div class="group-instruction">
			<?php if ( $add_link ) : ?>
				<a href="<?php echo esc_url( $add_link ); ?>" class='button button-secondary'>Подключить тестовую
					группу</a>
			<?php endif; ?>
			<ol>
				<li>Добавьте бота
					<b><?php echo esc_html( ! empty( $bot_name ) ? $bot_name : '' ); ?></b> в
					группу
				</li>
				<li>Введите сообщение в группе:
					<code class="code">/startgroup <?php echo esc_html( $group_token ); ?></code>

				</li>
				<li>Или добавить группу вручную
					<form method="post" action="">
						<?php wp_nonce_field( 'add_tg_group_manual', 'tg_group_nonce' ); ?>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="tg_group_id">ID группы или канала</label></th>
								<td>
									<input type="text" name="tg_group_id" id="tg_group_id" class="regular-text"
										placeholder="-1001234567890 или @channelname" required>
									<p class="description">
										Для супергрупп и каналов: начинается с -100<br>
										Для обычных групп: отрицательное число (например, -123456)<br>
										Также можно использовать username (@channelname)
									</p>
								</td>
							</tr>
						</table>
						<?php submit_button( 'Добавить группу', 'secondary', 'add_tg_group_submit' ); ?>
					</form>
				</li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Users part of admin page.
	 */
	protected function admin_page_users(): void {

		$tg = $this->get_api();

		$remove_link = false;
		if ( $this->container->get( User_Storage::class )->get_tg_chat( get_current_user_id() ) ) {
			$remove_link = wp_nonce_url(
				$this->add_action( 'remove_current_user' ),
				'remove_current_user'
			);
		}

		if ( $this->is_develop() ) {
			$add_link = wp_nonce_url(
				$this->add_action( 'test_add_current_user' ),
				'test_add_current_user'
			);
		} else {
			$add_link = $tg->get_chat_link() ?? '';
		}

		?>

		<h3>Уведомления для текущего пользователя</h3>
		<p>
			<?php if ( $remove_link ) : ?>
				<a href="<?php echo esc_url( $remove_link ); ?>" class='button button-primary'>Отключить</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $add_link ); ?>" target='_blank' class='button button-primary'>Подключить</a>
			<?php endif; ?>
		</p>

		<?php
	}
}
