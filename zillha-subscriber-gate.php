<?php
/**
 * Plugin Name: Zillha Subscriber Gate
 * Plugin URI:  https://zillha.com
 * Description: Restrict specific pages by slug to subscribers or higher. Manage restricted slugs from the admin panel.
 * Version:     1.0.0
 * Author:      Joe (Zillha)
 * Author URI:  https://zillha.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zillha-subscriber-gate
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Zillha_Subscriber_Gate
 */

defined( 'ABSPATH' ) || exit;

define( 'ZSG_VERSION', '1.0.0' );
define( 'ZSG_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZSG_URL', plugin_dir_url( __FILE__ ) );

require_once ZSG_PATH . 'includes/class-zsg-restrictor.php';
require_once ZSG_PATH . 'includes/class-zsg-admin.php';

/**
 * Bootstrap the plugin on plugins_loaded.
 *
 * @return void
 */
function zsg_bootstrap() {
	new ZSG_Restrictor();
	new ZSG_Admin();
}
add_action( 'plugins_loaded', 'zsg_bootstrap' );
