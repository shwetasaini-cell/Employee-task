<?php
/**
* Plugin Name: Employee Task Tracker
* Description: Simple, standards-driven task tracking for employees & managers: projects, daily tasks, approvals, comments, and CSV export.
* Version: 1.0.0
* Author: Test
* Requires at least: 6.0
* Requires PHP: 7.4
* Text Domain: etracker
*/


if ( ! defined( 'ABSPATH' ) ) { exit; }


// Constants
define( 'ET_PLUGIN_FILE', __FILE__ );
define( 'ET_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


// PSR-4 like autoloader for this plugin namespace
spl_autoload_register( function( $class ) {
$prefix = 'ET\\';
$base_dir = ET_PLUGIN_PATH . 'includes/';
$len = strlen( $prefix );
if ( strncmp( $prefix, $class, $len ) !== 0 ) {
return;
}
$relative_class = substr( $class, $len );
$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
if ( file_exists( $file ) ) {
require $file;
}
} );


register_activation_hook( __FILE__, [ 'ET\\Core\\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'ET\\Core\\Installer', 'deactivate' ] );


// Bootstrap
add_action( 'plugins_loaded', function() {
ET\Core\Installer::maybe_update();
(new ET\Admin\Menu())->hooks();
(new ET\Admin\Assets())->hooks();
(new ET\Frontend\Shortcodes())->hooks();
(new ET\REST\Tasks_Controller())->hooks();
(new ET\Exports\Exporter())->hooks();
(new ET\Emails\Mailer())->hooks();
} );