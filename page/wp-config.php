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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'crealwp05_ramrockwp' );

/** Database username */
define( 'DB_USER', 'crealwp05_rock' );

/** Database password */
define( 'DB_PASSWORD', 'Ns5zLwu3' );

/** Database hostname */
define( 'DB_HOST', 'mysql705b.xserver.jp' );

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
define( 'AUTH_KEY',         'UJze,L^%J74?IL&d6e-)<R}3,]w3=l:*$t#v]23`>,&&6Pjy+3mTn] 6r0(3DuFX' );
define( 'SECURE_AUTH_KEY',  'xYF@M|r&[dk=(UC.=L8|1l|<;{{~o3zkD&5usFl,]x7YF(U~-gT_I ]i W#m>n)b' );
define( 'LOGGED_IN_KEY',    '!qJJh&Ygc=ct8[Zrg]ZgAg_BzLQwoxO9Bx8ycnlBqNx,]Z,i,q4By,&j@Uj)|TF7' );
define( 'NONCE_KEY',        '>^Q>8*KHkK>98))(Dj.p0T3/M!0]F>_IMV_n,<S!rH(S{+Q&$ +|H!jh!-)S+z+W' );
define( 'AUTH_SALT',        'A![P6(FH7zp[FRl3}AT}0%vMCSpRm&H!)Ifz|OB228oIih4q{M2,qm^En*1Wl%fM' );
define( 'SECURE_AUTH_SALT', 'z9[~afs!b8V~HnlQu@O>Y<`us)XC#p!73jxXw!lD$ 3r@/Q?a2z<kK~%RBRL/;C&' );
define( 'LOGGED_IN_SALT',   '#CYycr00B)))z@HAJ0{r(P6G@F:K(39VcV#/IX;;d*b2!J>zTk;J%DaXuDU%,)q6' );
define( 'NONCE_SALT',       '$Sb%]Oe(9db&EZLIDNkmt|3kZ;5xufU](qq D)#w|.ucD+f*3+L{g&Er}R?#SiPt' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
