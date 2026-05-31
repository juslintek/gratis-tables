<?php
/**
 * Plugin Name: Gratis Tables
 * Description: Data tables + charts block. Sortable, searchable, responsive. Replaces TablePress ($49/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 * Text Domain: gratis-tables
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Tables\TableBlock::class, 'register']);
add_shortcode('gratis-table', [\Gratis\Tables\TableBlock::class, 'shortcode']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-tables', \Gratis\Tables\CLI\TablesCommand::class);
    }
});
