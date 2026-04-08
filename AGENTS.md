# DNT Notify - AI Agent Guide

## Project Overview

**DNT Notify** is a WordPress plugin that provides notification integration with Telegram and VK, along with SMTP configuration capabilities. The plugin is designed to send notifications for various events, with a primary focus on WooCommerce order notifications.

- **Plugin Name**: DNT Notify
- **Text Domain**: `dnt_notify`
- **Namespace**: `dnt_notify`
- **PHP Version**: 8.1+
- **WordPress Version**: 6.5+
- **License**: GPL-2.0-or-later

## Technology Stack

### Core Technologies
- **PHP 8.1+**: Primary language with strict typing
- **WordPress**: CMS framework (minimum version 6.5)
- **Composer**: Dependency management and autoloading

### External Dependencies (Production)
- `irazasyed/telegram-bot-sdk: ^3.15` - Telegram Bot API integration
- `vkcom/vk-php-sdk: ^5.131` - VK API integration (included but not actively used in current codebase)

### Development Dependencies
- **PHP_CodeSniffer** with WPCS (WordPress Coding Standards)
- **PHPCompatibilityWP** - PHP version compatibility checks
- **PHPStan** (v2.1.32) with WordPress extension - Static analysis
- **PHPUnit** (v9) with Yoast polyfills - Unit testing
- **Mockery** - Mocking framework for tests
- **WooCommerce Stubs** - For WooCommerce integration development

## Project Structure

```
dnt-notify/
├── dnt-notify.php          # Main plugin file (entry point)
├── composer.json           # Composer configuration
├── phpunit.xml.dist        # PHPUnit configuration
├── phpcs.xml.dist          # PHP_CodeSniffer configuration
├── phpstan.neon.dist       # PHPStan configuration
├── .env                    # Environment configuration (env=develop)
├── .distignore             # Files to exclude from distribution
├── .devcontainer.json      # VS Code DevContainer configuration
├── app/                    # Main application code
│   ├── abstract/           # Abstract classes and interfaces
│   │   ├── class-base-settings.php   # Base settings class with schema validation
│   │   ├── class-service.php         # Base service class with container access
│   │   └── interface-container.php   # Container interface contract
│   ├── form/               # Form-related functionality
│   │   └── class-tg-form-message.php # Telegram message for form submissions
│   ├── tg/                 # Telegram integration
│   │   ├── class-tg.php                # Main Telegram class (API, webhook)
│   │   ├── class-tg-admin.php          # Admin panel for Telegram settings
│   │   ├── class-tg-settings.php       # Telegram settings management
│   │   ├── class-tg-test.php           # Test helpers for Telegram (dev only)
│   │   ├── class-start-command.php     # /start bot command handler
│   │   ├── class-start-group-command.php # /startgroup command handler
│   │   └── interface-tg-message.php    # Message interface contract
│   ├── user/               # User-related functionality
│   │   └── class-user-storage.php      # User meta storage for Telegram chat IDs
│   ├── woo/                # WooCommerce integration
│   │   ├── class-woo-integration.php   # WooCommerce hooks integration
│   │   └── class-new-order-tg-message.php # New order notification formatter
│   ├── class-admin-group.php           # Main admin menu registration
│   ├── class-container.php             # DI container implementation
│   ├── class-environment.php           # Environment detection and .env parsing
│   ├── class-main.php                  # Main plugin class (initialization)
│   └── helpers.php                     # Global helper functions
├── public/                 # Public assets
│   └── admin.css           # Admin panel styles
├── tests/                  # PHPUnit tests
│   ├── bootstrap.php       # Test bootstrap
│   ├── test-tg.php         # Telegram integration tests
│   └── test-mail.php       # Mail functionality tests (minimal)
└── vendor/                 # Composer dependencies
```

## Architecture

### Dependency Injection Container
The plugin uses a custom DI container (`Container` class) implementing `Container_Interface`:

- **Singleton pattern**: Services can be registered as shared (singleton) or transient
- **Factory functions**: Services are created via factory closures
- **Lazy loading**: Services are instantiated only when requested
- **WordPress filters**: `dnt_notify_container_factory` and `dnt_notify_container_service` allow external modification

```php
// Service registration in Main.php constructor
$container->set( Tg_Form_Message::class, fn( $container, $props ) => new Tg_Form_Message( $props ), false );

// Service retrieval
$tg = $container->get( Tg::class );
```

### Service Pattern
All major components extend the `Service` abstract class which provides:
- Access to the DI container via `$this->container`
- Environment detection via `$this->is_develop()`

### Settings Management
Settings are managed through `Base_Settings` abstract class:
- JSON Schema validation using `rest_validate_value_from_schema()`
- WordPress options API integration
- Sanitization hooks

### Telegram Integration Architecture
1. **Tg class**: Core integration, webhook handling, message sending
2. **Commands**: `/start` (user registration), `/startgroup` (group registration)
3. **Messages**: Implement `Tg_Message_Interface` with `to()` and `content()` methods
4. **User Storage**: WordPress user meta for chat ID persistence
5. **Settings**: Bot token, bot name, registered groups

### Environment Modes
Development mode is activated by:
- `.env` file with `env=develop`
- `WP_ENV` environment variable set to `develop`

In development mode:
- Telegram messages are logged to `logs/tg.log` instead of sent
- Test actions are available in admin panel
- Mock data is returned for API calls

## Build and Development Commands

All commands are run via Composer scripts defined in `composer.json`:

```bash
# Install dependencies
composer install

# Code style checking
composer run phpcs

# Code style auto-fixing
composer run phpcbf

# Static analysis
composer run phpstan

# Run PHPUnit tests
composer run tests

# WPify Scoper (for dependency scoping)
composer run wpify-scoper
```

## Code Style Guidelines

### WordPress Coding Standards (WPCS)
- Follows WordPress-Core, WordPress-Docs, and WordPress-Extra rulesets
- PHPCompatibilityWP for PHP 8.0+ compatibility
- **Prefix**: All globals must use `dnt_notify` prefix
- **Text Domain**: `dnt_notify` for internationalization

### Naming Conventions
- **Classes**: `Class_Name` (WordPress-style, not PSR-4)
- **Files**: `class-class-name.php` (lowercase with hyphens)
- **Namespaces**: `dnt_notify\subnamespace` (lowercase with underscores)
- **Constants**: `DNT_NOTIFY_*` prefix
- **Hooks**: `dnt_notify_*` prefix

### Key Code Patterns

```php
// Security check at file beginning
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Namespace declaration
namespace dnt_notify\tg;

// Strict typing for class properties
protected string $name = 'start';

// Array type hints with generics-style annotations
/**
 * @var array<string, callable>
 */
private $services = array();

// PHPStan ignore annotations for WordPress hooks
// @phpstan-ignore argument.type
```

## Testing Strategy

### Test Setup
- **Framework**: PHPUnit 9 with Yoast PHPUnit Polyfills
- **Bootstrap**: `tests/bootstrap.php` loads WordPress test environment
- **Environment Variable**: `TESTS_DIR` points to WordPress test directory (default: `/var/www/html/tests`)

### Test Structure
```bash
tests/
├── bootstrap.php      # WordPress test environment setup
├── test-tg.php        # Telegram functionality tests using Mockery
└── test-mail.php      # Mail functionality tests (placeholder)
```

### Running Tests
```bash
# Requires WordPress test environment
composer run tests

# Or directly
vendor/bin/phpunit
```

### Mocking Strategy
Tests use Mockery for mocking:
- Telegram API (`Telegram\Bot\Api`)
- Message interfaces (`Tg_Message_Interface`)
- Chat objects (`Telegram\Bot\Objects\Chat`)

## Admin Panel

### Menu Structure
- **Main Menu**: "Notify" (top-level)
- **Submenu**: "Telegram" (settings page)

### Telegram Admin Features
1. **Bot Configuration**: Token and bot name settings
2. **Webhook Management**: Set webhook URL
3. **User Management**: Connect/disconnect users for notifications
4. **Group Management**: Add/remove Telegram groups for notifications
5. **Testing Tools**: Send test messages (development mode only)

### Security
- All admin actions use WordPress nonces
- Capability checks (`manage_options`)
- Data sanitization with `sanitize_text_field()` and `wp_unslash()`
- Output escaping with `esc_html()`, `esc_url()`, `esc_attr()`

## WooCommerce Integration

### Order Notifications
The plugin sends Telegram notifications when:
- Order status changes to "processing"
- Notification is sent only once per order (tracked via `_dnt_notify_new_order_notify_sent` meta)

### Message Content Includes
- Order number and items
- Product attributes (excluding "Weight")
- Custom attributes (e.g., "pa_sposob-obzharki" - roasting method)
- Discounts and coupons
- Shipping details (with CDEK integration support)
- Customer information
- Payment details

## Security Considerations

1. **Direct Access Protection**: All files have `ABSPATH` checks
2. **Nonce Verification**: All admin actions verify nonces
3. **Capability Checks**: Admin features require `manage_options`
4. **Data Sanitization**: Input is sanitized before processing
5. **Output Escaping**: All output is escaped before rendering
6. **Error Handling**: Try-catch blocks around external API calls
7. **Logging**: Errors are logged via `error_log()`

## Deployment Notes

### Distribution Exclusions (`.distignore`)
```
logs
src
tools
eslint.config.js
tsconfig.json
phpstan.neon.dist
vite.config.js
vite.assetize.js
tests
```

### Environment Configuration
For production deployment:
1. Remove or modify `.env` file (set `env=production`)
2. Ensure `WP_ENV` is not set to `develop`
3. Set up proper webhook URL for Telegram bot
4. Configure bot token and name in admin panel

## Development Environment

### DevContainer Setup
The project includes VS Code DevContainer configuration:
- **Service**: WordPress (wp)
- **Port**: 80 forwarded
- **Extensions**: PHP Tools, WordPress Hooks, SearchWP Docs

### Required WordPress Plugins
- WooCommerce (for order notifications)
- CDEK shipping plugin (optional, for office information)

## Language and Localization

The plugin uses mixed language approach:
- **Code**: English (classes, variables, comments)
- **User Interface**: Russian (admin panel labels, Telegram messages)
- **Text Domain**: `dnt_notify` (ready for internationalization)

Key Russian phrases in UI:
- "Заказ №" - Order #
- "Итого" - Total
- "Доставка" - Shipping
- "Подключить" - Connect
- "Отключить" - Disconnect
