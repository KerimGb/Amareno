<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'eDl>@q]aLe:xKAh7^4>Rd00%>+jMaDRpc-FTs:(Z&FP+0ma|1>>Py+cHJC7E _0k' );
define( 'SECURE_AUTH_KEY',   'sZ-;xehE_FK!YD%-3VHslHyUf]bs1;[dwQQ:AjZ0L4xMUa[td{r$x2<W4zSua`Z!' );
define( 'LOGGED_IN_KEY',     '$4r2YRv!Zxogf<Ij*-R;b1*HMi;CB(M<{H=y0D%-8h9czum3&wH!.C6(7H1l;jhh' );
define( 'NONCE_KEY',         'yi/|Smn^@%:fsR&>(1l-JxG gCfV0GD~aXj>3@ek4N*%!d!lxPG.83/#$ToI.bb,' );
define( 'AUTH_SALT',         'Px|cwK@NmK1+%e%ewujD-U+J-+INVMVc45N]*+7!>D0.Kvv+G!-:qI=+i,6eEgQe' );
define( 'SECURE_AUTH_SALT',  '@-vV_|Uj(&``e{j=lXi<$ik&* q!o}9hkC3KdmEBE0|U%{&35c(Ok}K95qD(;&}Z' );
define( 'LOGGED_IN_SALT',    '%=4>cXqj1_Wono=;aW2.e-a#FamZ=P)&9TiR:dj3)~ELy6{.S8 2?U7$_m_KvER-' );
define( 'NONCE_SALT',        'dQACMp_Cf[Hbt8V7@*G(p)<=,^=XYgIA1)u(Ot6.Yuaa+?WpME,H%j7OG ({HD 9' );
define( 'WP_CACHE_KEY_SALT', 'Goj1NavNA43{^lypTCZ|TchRjI6d>W,^0&9ZtTSSg|d?xEE]<[0cY:B^M.-([}Hi' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
