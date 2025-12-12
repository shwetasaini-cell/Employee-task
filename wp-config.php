<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'employee_task' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'S~1BkX(J56.)i;CdV`1@np9e[j T5+ax(i.,@KJldhqa,u -lyTad4*PCwwa* 6j' );
define( 'SECURE_AUTH_KEY',  '<qC!Y^WJvE0+{#RX2*3{?]n^hy:^zJ*hetyAcNs|D64+EcnJ:x>_JfZ@x8_E3q*o' );
define( 'LOGGED_IN_KEY',    'AlX/LYbg?Ub!sb{6D4=q>y:[?EKy+&+cCz]+oHn@6@M(dBqfeGyh.9xWo,<pG9yd' );
define( 'NONCE_KEY',        '8qUD%=?nM#=9wz^4`,2#bK&0N6Z@0/%m?76!h{G0GV9xi}3+&I7e~Maw9o1+#J}G' );
define( 'AUTH_SALT',        'bzKP8SXPE6WkyCgP Ys6]6nqFj|hk9+5iuWxHO)@.(RI%Ab,f$?Gge^_nAKG/{?v' );
define( 'SECURE_AUTH_SALT', 'n0eUet@M}yYS#:akg)ab+?gKx7gqD0d,Mifp2h|zuFzo7`!]<7AQ>~ SVvIV)sDL' );
define( 'LOGGED_IN_SALT',   '@|llW{l ;]jDq1@?[YJLf+X~;egkxRH`7D0u_({@Y&.@}T~Z#y491&6 Yo1t2<aS' );
define( 'NONCE_SALT',       'p|@3]V}nXlVq?M5Ed4AU,1>/v!mg^qSlN@6cJ%.dg? 2_>pd7W@?{W]<)>|(%h8<' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
