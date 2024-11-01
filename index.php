<?php
/**
 * Plugin Name: Player with Playlist Block for WordPress Editor
 * Description: The Youtenberg Player extends the WordPress block editor functionality by adding useful extra block elements to it. Use it to simply add youtube videos, youtube playlists, youtube gallery on your website.
 * Version: 1.0
 * Author: AA-Team
 * Author URI: http://www.aa-team.com
 * Text Domain: video-playlist-lite
 * Domain Path: /languages
 *
 * @package youtube-video-playlist
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The full path of plugin
 *
 * @var string AZON_GUTENBERG_SEARCHBOX_PATH
 */
define( 'AAT_YVY_LITE_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The full path and filename of this bootstrap file with symlinks resolved.
 *
 * @var string AAT_YVY_LITE_BOOTSTRAP_FILE
 */
define( 'AAT_YVY_LITE_BOOTSTRAP_FILE', __FILE__ );

/**
 * The full path to the parent directory of this bootstrap file with symlinks resolved, with trailing slash.
 *
 * @var string AAT_YVY_LITE_DIR
 */
define( 'AAT_YVY_LITE_DIR', dirname( AAT_YVY_BOOTSTRAP_FILE ) . '/' );

/**
 * The relative path to this plugin directory, from WP_PLUGIN_DIR, with trailing slash.
 *
 * @var string AAT_YVY_LITE_REL_DIR
 */
define( 'AAT_YVY_LITE_REL_DIR', basename( AAT_YVY_DIR ) . '/' );

/**
 * The URL of the plugin directory, with trailing slash.
 *
 * Example: https://example.local/wp-content/plugins/hcmc-custom-objects/
 *
 * @const string AAT_YVY_LITE_URL
 */
define( 'AAT_YVY_LITE_URL', plugins_url( '/', AAT_YVY_LITE_BOOTSTRAP_FILE ) );


// Automatically load all "blocks" -- New blocks are required to have an index.php file in order to be loaded.
foreach ( glob( dirname( __FILE__ ) . '/blocks/*/index.php' ) as $block_logic ) {
    require $block_logic;
}
