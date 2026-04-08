# DNT Notify Plugin

A WordPress plugin that adds support for notifications in VK and Telegram, as well as SMTP configuration.

## Features

- Send notifications to VK groups
- Send notifications to Telegram chats
- Configure SMTP settings for email notifications

## Installation

1. Upload the `dnt-notify` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the 'DNT Notify' menu

## Configuration

### VK Settings

1. Go to the 'DNT Notify' menu in the WordPress admin panel
2. Enter your VK access token and group ID
3. Save the settings

### Telegram Settings

1. Go to the 'DNT Notify' menu in the WordPress admin panel
2. Enter your Telegram bot token and chat ID
3. Save the settings

### SMTP Settings

1. Go to the 'DNT Notify' menu in the WordPress admin panel
2. Enter your SMTP host, username, password, port, from email, and from name
3. Save the settings

## Usage

### Sending Telegram Notifications

```php
send_tg_message('Your notification message');
```

### Sending Email Notifications

The SMTP settings will automatically configure the WordPress email system. Use the standard `wp_mail()` function to send emails.

## Support

For support, please contact the plugin author or open an issue on the plugin repository.


