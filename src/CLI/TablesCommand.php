<?php declare(strict_types=1);
namespace Gratis\Tables\CLI;

use Gratis\Tables\TableBlock;

final class TablesCommand
{
    /**
     * List tables.
     * @when after_wp_load
     */
    public function list($args, $assoc_args): void
    {
        $tables = get_posts(['post_type' => 'gratis_table', 'numberposts' => -1]);
        if (empty($tables)) { \WP_CLI::log("No tables."); return; }
        $rows = array_map(fn($t) => [
            'ID' => $t->ID, 'Title' => $t->post_title,
            'Rows' => count((get_post_meta($t->ID, '_table_data', true)['rows'] ?? [])),
        ], $tables);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Rows']);
    }

    /**
     * Import a CSV file as a table.
     * <file> : Path to CSV file.
     * [--title=<title>] : Table title.
     * @when after_wp_load
     */
    public function import($args, $assoc_args): void
    {
        $file = $args[0];
        if (!file_exists($file)) { \WP_CLI::error("File not found: {$file}"); }
        $csv = file_get_contents($file);
        $title = $assoc_args['title'] ?? basename($file, '.csv');
        $id = TableBlock::importCSV($csv, $title);
        if ($id) {
            \WP_CLI::success("Table #{$id} created: {$title}. Use [gratis-table id={$id}]");
        } else {
            \WP_CLI::error("Failed to import.");
        }
    }
}
