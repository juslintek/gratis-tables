<?php
/**
 * Plugin Name: Gratis Tables
 * Plugin URI: https://github.com/juslintek/gratis-tables
 * Description: Data tables block. Sortable, searchable, responsive. Replaces TablePress ($49/yr).
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-tables
 *
 * @package Gratis\Tables
 */

defined('ABSPATH') || exit;

define('GRATIS_TABLES_VERSION', '1.0.0');
define('GRATIS_TABLES_FILE', __FILE__);
define('GRATIS_TABLES_DIR', plugin_dir_path(__FILE__));
define('GRATIS_TABLES_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Tables\TableBlock::class, 'register']);
add_shortcode('gratis-table', [\Gratis\Tables\TableBlock::class, 'shortcode']);

add_action('add_meta_boxes', [\Gratis\Tables\MetaBox::class, 'register']);
add_action('save_post_gratis_table', [\Gratis\Tables\MetaBox::class, 'save'], 10, 2);

add_action('admin_menu', [\Gratis\Tables\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Tables\Settings::class, 'register_settings']);

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-tables', \Gratis\Tables\CLI\TablesCommand::class);
    }
});
