<?php
/**
 * The docs site's settings, for the container. The database is a SQLite file
 * in memory (/tmp/fam), copied from the image at start; the salts are made at
 * start (docker/start.sh). Nothing is edited through the web: the site's
 * must-use plugin refuses every sign-in, and Apache and the host return 404
 * for the admin, sign-in, XML-RPC and REST paths.
 */

define( 'DB_PATH', '/tmp/fam/site.sqlite' );
// Unused with SQLite; WordPress still expects them
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_';

require '/tmp/fam/salts.php';

// FAM_URL changes the address for a local run; the hosted site uses its own
define( 'WP_HOME', getenv( 'FAM_URL' ) ?: 'https://themes.andrew.cmu.edu/forbes-and-moreword/0' );
define( 'WP_SITEURL', WP_HOME );
// The host's nginx ends HTTPS and says so
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

// Nothing changes WordPress, its theme or plugins while it runs
define( 'DISALLOW_FILE_MODS', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'DISABLE_WP_CRON', true );
// No outgoing requests at run time; the image build reaches PlaceMellon
if ( ! getenv( 'FAM_BUILD' ) ) {
	define( 'WP_HTTP_BLOCK_EXTERNAL', true );
}
define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
