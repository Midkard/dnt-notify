<?php
/**
 * VK admin functionality for the DNT Notify plugin.
 *
 * @package dnt_notify\vk
 */

namespace dnt_notify\vk;

use dnt_notify\Admin_Group;
use dnt_notify\Base_Settings;
use dnt_notify\Service;
use dnt_notify\user\User_Storage;
use function dnt_notify\send_vk_message;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for handling VK admin functionality.
 */
class VK_Admin extends Service {


	/**
	 * Gets the menu slug for the admin page.
	 *
	 * @return string The menu slug.
	 */
	public function menu_slug(): string {
		return 'dnt-notify-vk';
	}

	/**
	 * Initializes the admin panel.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Process actions.
		add_action(
			'admin_init',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				// Handle manual peer addition form.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['add_vk_peer_submit'] ) ) {
					$this->action_add_peer_manual();
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
						'peer_id',
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
	 * Gets the VK settings.
	 *
	 * @return VK_Settings The VK settings instance.
	 */
	protected function get_settings(): VK_Settings {
		return $this->container->get( VK_Settings::class );
	}

	/**
	 * Gets the VK api.
	 *
	 * @return VK The VK instance.
	 */
	protected function get_api(): VK {
		return $this->container->get( VK::class );
	}

	/**
	 * Handles the test message action.
	 */
	protected function action_test_message(): void {
		$container = $this->container;

		$message = new class($container->get( VK_Settings::class )) implements VK_Message_Interface {
			/**
			 * Settings instance.
			 *
			 * @var VK_Settings
			 */
			private VK_Settings $settings;

			/**
			 * Constructor.
			 *
			 * @param VK_Settings $settings Settings instance.
			 */
			public function __construct( VK_Settings $settings ) {
				$this->settings = $settings;
			}

			/**
			 * Get recipients.
			 *
			 * @return array<int|string>
			 */
			public function to(): array {
				return $this->settings->get_groups();
			}

			/**
			 * Get message content.
			 *
			 * @return string
			 */
			public function content(): string {
				return "Тестовое сообщение от сайта\n\n<b>Дата:</b> " . current_time( 'mysql' );
			}
		};

		send_vk_message( $message );

		add_settings_error( $this->get_settings()->key, 'send', 'Сообщение отправлено в VK!', 'success' );
	}

	/**
	 * Handles the remove group action.
	 */
	protected function action_remove_group(): void {
		if ( empty( $_GET['peer_id'] ) || ! is_string( $_GET['peer_id'] ) ) {
			return;
		}
		$peer_id = sanitize_text_field( wp_unslash( $_GET['peer_id'] ) );
		if ( ! $this->verify_nonce( 'remove_vk_group_' . $peer_id ) ) {
			return;
		}

		$settings = $this->get_settings();
		$settings->remove_group( $peer_id );
		add_settings_error( $settings->key, 'removed', 'Группа успешно отключена!', 'success' );
	}

	/**
	 * Handles the test add group action.
	 */
	protected function action_test_add_group(): void {
		if ( ! $this->is_develop() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'test_add_vk_group' ) ) {
			return;
		}

		// Add a test group (for development purposes).
		$this->get_settings()->add_group( '2000000001' );
		add_settings_error( $this->get_settings()->key, 'added', 'Тестовая группа добавлена', 'success' );
	}

	/**
	 * Handles the test add current user action.
	 */
	protected function action_test_add_current_user(): void {
		if ( ! $this->is_develop() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'test_add_vk_current_user' ) ) {
			return;
		}

		// Add test peer_id for current user.
		$this->container->get( User_Storage::class )->update_vk_chat( get_current_user_id(), '123456789' );
		add_settings_error( $this->get_settings()->key, 'added', 'Тестовый пользователь VK добавлен', 'success' );
	}

	/**
	 * Handles the remove current user action.
	 */
	protected function action_remove_current_user(): void {
		if ( ! $this->verify_nonce( 'remove_vk_current_user' ) ) {
			return;
		}

		$this->container->get( User_Storage::class )->remove_vk_chat( get_current_user_id() );

		add_settings_error( $this->get_settings()->key, 'removed', 'Пользователь успешно отключен от VK!', 'success' );
	}

	/**
	 * Handles manual peer addition.
	 *
	 * This method adds a VK peer (chat/user/group) by its ID.
	 *
	 * @return void
	 */
	protected function action_add_peer_manual(): void {
		// Verify nonce.
		if ( ! isset( $_POST['vk_peer_nonce'] ) || ! is_string( $_POST['vk_peer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vk_peer_nonce'] ) ), 'add_vk_peer_manual' ) ) {
			add_settings_error( $this->get_settings()->key, 'nonce_error', 'Ошибка проверки безопасности.', 'error' );
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( $this->get_settings()->key, 'permission_error', 'Недостаточно прав.', 'error' );
			return;
		}

		// Get and validate peer ID.
		if ( empty( $_POST['vk_peer_id'] ) || ! is_string( $_POST['vk_peer_id'] ) ) {
			add_settings_error( $this->get_settings()->key, 'empty_peer', 'Укажите Peer ID.', 'error' );
			return;
		}

		$peer_id = sanitize_text_field( wp_unslash( $_POST['vk_peer_id'] ) );

		// Trim whitespace.
		$peer_id = trim( $peer_id );

		// Validate format (must be a number, can be negative).
		if ( ! preg_match( '/^-?\d+$/', $peer_id ) ) {
			add_settings_error( $this->get_settings()->key, 'invalid_format', 'Неверный формат Peer ID. Должно быть число (например, 123456 или -123456 или 2000000001).', 'error' );
			return;
		}

		// Add peer.
		$settings = $this->get_settings();
		$settings->add_group( $peer_id );

		add_settings_error( $settings->key, 'peer_added', 'Беседа/пользователь успешно добавлен(а)!', 'success' );
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
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			$this->container->get( Admin_Group::class )->menu_slug(),
			'VK Settings',
			'VK',
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
	 * VK admin page.
	 */
	public function admin_page(): void {
		$settings = $this->get_settings();
		$vk = $this->get_api();

		/**
		 * Fields.
		 *
		 * @var array<string,array{title:string}>
		 * @phpstan-ignore offsetAccess.nonOffsetAccessible
		 */
		$fields = $settings->get_schema()['properties'];

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
					<h2>VK Settings</h2>
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
						<div class="dnt-notify-callback-info"
							style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-left: 4px solid #0073aa;">
							<h3>Callback API URL</h3>
							<code><?php echo esc_url( $vk->get_callback_url() ); ?></code>
							<p class="description">Укажите этот URL в настройках Callback API вашего сообщества VK.</p>
						</div>
					<?php endif; ?>
				</div>

				<?php
				submit_button( 'Save Settings', 'primary', 'submit', true );
				?>
			</form>

			<?php if ( $settings->is_ready() ) : ?>
				<h1>Уведомления VK</h1>
				<?php $this->admin_page_users(); ?>
				<?php $this->admin_page_groups(); ?>

				<h3>Тесты</h3>
				<a href="<?php echo esc_url( $this->add_action( 'test_message' ) ); ?>" class='button button-primary'>Тестовое
					сообщение</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Groups part of admin page.
	 */
	protected function admin_page_groups(): void {
		$settings = $this->get_settings();
		$vk = $this->get_api();

		$groups = $settings->get_groups();

		if ( $this->is_develop() ) {
			$add_link = wp_nonce_url(
				$this->add_action( 'test_add_group' ),
				'test_add_vk_group'
			);
		} else {
			$add_link = false;
		}

		?>

		<h3>Подключенные беседы/группы VK</h3>
		<?php if ( ! empty( $groups ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Peer ID</th>
						<th>Название</th>
						<th>Тип</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $groups as $peer_id ) :
						$conversation_info = $vk->get_conversation_info( $peer_id );
						?>
						<tr>
							<td><code><?php echo esc_html( $peer_id ); ?></code></td>
							<td>
								<?php if ( is_array( $conversation_info ) && $conversation_info['title'] ) : ?>
									<strong><?php echo esc_html( $conversation_info['title'] ); ?></strong>
								<?php else : ?>
									<span style="color: #999;">Не удалось получить информацию</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( is_array( $conversation_info ) ) : ?>
									<?php echo esc_html( ucfirst( $conversation_info['type'] ) ); ?>
								<?php else : ?>
									<span style="color: #999;">-</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="
								<?php
								echo esc_url(
									wp_nonce_url(
										add_query_arg( 'peer_id', $peer_id, $this->add_action( 'remove_group' ) ),
										'remove_vk_group_' . $peer_id
									)
								);
								?>
								" class='button button-secondary button-small'
									onclick="return confirm('Вы уверены, что хотите отключить эту беседу?')">
									Отключить
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p>Нет подключенных бесед.</p>
		<?php endif; ?>

		<h3>Подключить сообщество</h3>
		<div class="group-instruction">
			<?php
			$group_id = $settings->get_group_id();
			$token = $settings->token_to_add_group();
			?>
			<?php if ( $add_link ) : ?>
				<a href="<?php echo esc_url( $add_link ); ?>" class='button button-secondary'>Добавить тестовую
					беседу</a>
			<?php endif; ?>
			<?php if ( ! empty( $group_id ) ) : ?>
				<p><strong>Инструкция по подключению беседы:</strong></p>
				<ol>
					<li>Добавьте сообщество в беседу VK</li>
					<li>Отправьте в беседе команду:
						<code class="code">/startgroup <?php echo esc_html( $token ); ?></code>
					</li>
					<li>Или Добавить беседу/пользователя вручную
						<form method="post" action="">
							<?php wp_nonce_field( 'add_vk_peer_manual', 'vk_peer_nonce' ); ?>
							<table class="form-table">
								<tr>
									<th scope="row"><label for="vk_peer_id">Peer ID</label></th>
									<td>
										<input type="text" name="vk_peer_id" id="vk_peer_id" class="regular-text"
											placeholder="2000000001" required>
										<p class="description">
											Для бесед: 2000000000 + chat_id<br>
											Для пользователей: ID пользователя<br>
											Для групп: -ID группы
										</p>
									</td>
								</tr>
							</table>
							<?php submit_button( 'Добавить', 'secondary', 'add_vk_peer_submit' ); ?>
						</form>
					</li>
				</ol>
			</div>
		<?php else : ?>
			<p style="color: #d63638;">Укажите Group ID в настройках для получения ссылки на подключение беседы.</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Users part of admin page.
	 */
	protected function admin_page_users(): void {
		$vk = $this->get_api();
		$user_storage = $this->container->get( User_Storage::class );

		$remove_link = false;
		if ( $user_storage->get_vk_chat( get_current_user_id() ) ) {
			$remove_link = wp_nonce_url(
				$this->add_action( 'remove_current_user' ),
				'remove_vk_current_user'
			);
		}

		if ( $this->is_develop() ) {
			$add_link = wp_nonce_url(
				$this->add_action( 'test_add_current_user' ),
				'test_add_vk_current_user'
			);
		} else {
			$chat_link = $vk->get_chat_link();
			$add_link = $chat_link['link'] ?? '';
			$cmd = $chat_link['text'] ?? '';
		}

		?>

		<h3>Уведомления VK для текущего пользователя</h3>
		<p>
			<?php if ( $remove_link ) : ?>
				<a href="<?php echo esc_url( $remove_link ); ?>" class='button button-primary'>Отключить VK</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $add_link ); ?>" target='_blank' class='button button-primary'>Подключить VK</a>
			<?php endif; ?>
		</p>
		<?php if ( ! $this->is_develop() && $add_link ) : ?>
			<p class="description">
				Нажмите кнопку для открытия диалога с сообществом. Отправьте команду для подключения уведомлений.
				<code class="code"><?php echo esc_html( $cmd ); ?></code>
			</p>
		<?php endif; ?>

		<?php
	}
}
