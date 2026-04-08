<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package goodini_crm
 */


// Give access to tests_add_filter() function.
$goodini_crm_tests_dir = getenv( 'TESTS_DIR' );
if ( empty( $tests_dir ) )  {
	$goodini_crm_tests_dir = '/var/www/html/tests';
}
$goodini_crm_tests_dir = rtrim( $goodini_crm_tests_dir, '/\\' );
require_once $goodini_crm_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/dnt-notify.php';
	}
);

// Start up the WP testing environment.
require $goodini_crm_tests_dir . '/includes/bootstrap.php';
