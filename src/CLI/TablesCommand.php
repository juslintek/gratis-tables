<?php declare(strict_types=1);

namespace Gratis\Tables\CLI;

use Gratis\Tables\TableBlock;

defined('ABSPATH') || exit;

/**
 * WP-CLI helpers for Gratis Tables.
 */
final class TablesCommand {

    /**
     * List tables.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function list($args, $assoc_args): void {
        unset($args, $assoc_args);
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- admin CLI listing.
        $tables = get_posts(['post_type' => TableBlock::POST_TYPE, 'numberposts' => -1, 'post_status' => 'any']);
        if (empty($tables)) {
            \WP_CLI::log('No tables.');
            return;
        }
        $rows = array_map(static fn($t) => [
            'ID'    => $t->ID,
            'Title' => $t->post_title,
            'Rows'  => count(TableBlock::getData($t->ID)['rows']),
        ], $tables);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Rows']);
    }

    /**
     * Import a CSV file as a table.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to CSV file.
     *
     * [--title=<title>]
     * : Table title.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args.
     * @param array<string,string> $assoc_args Associative args.
     */
    public function import($args, $assoc_args): void {
        $file = (string) ($args[0] ?? '');
        if ('' === $file || !file_exists($file)) {
            \WP_CLI::error("File not found: {$file}");
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local CLI-supplied path, not a URL.
        $csv = (string) file_get_contents($file);
        $title = (string) ($assoc_args['title'] ?? basename($file, '.csv'));
        $id = TableBlock::importCSV($csv, $title);
        if ($id) {
            \WP_CLI::success("Table #{$id} created: {$title}. Use [gratis-table id={$id}]");
        } else {
            \WP_CLI::error('Failed to import.');
        }
    }
}
