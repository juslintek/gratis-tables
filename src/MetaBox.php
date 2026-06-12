<?php declare(strict_types=1);

namespace Gratis\Tables;

defined('ABSPATH') || exit;

/**
 * Table editor meta box: edit table data as CSV and show the shortcode.
 */
final class MetaBox {

    private const NONCE_ACTION = 'gratis_tables_save_meta';
    private const NONCE_FIELD  = 'gratis_tables_meta_nonce';

    /** Register the meta boxes. */
    public static function register(): void {
        add_meta_box(
            'gratis-tables-data',
            __('Table Data (CSV)', 'gratis-tables'),
            [__CLASS__, 'renderData'],
            TableBlock::POST_TYPE,
            'normal',
            'high'
        );
        add_meta_box(
            'gratis-tables-shortcode',
            __('Shortcode', 'gratis-tables'),
            [__CLASS__, 'renderShortcode'],
            TableBlock::POST_TYPE,
            'side',
            'default'
        );
    }

    /** Render the CSV editor. */
    public static function renderData(\WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        $data = TableBlock::getData($post->ID);

        $lines = [];
        if (!empty($data['headers'])) {
            $lines[] = self::toCsvLine($data['headers']);
        }
        foreach ($data['rows'] as $row) {
            $lines[] = self::toCsvLine((array) $row);
        }
        $csv = implode("\n", $lines);

        echo '<p class="description">' . esc_html__('First row is treated as the header. One row per line, comma-separated.', 'gratis-tables') . '</p>';
        echo '<textarea name="gt_csv" rows="12" class="widefat code" style="font-family:monospace">' . esc_textarea($csv) . '</textarea>';
    }

    /** Render the shortcode reference. */
    public static function renderShortcode(\WP_Post $post): void {
        echo '<p>' . esc_html__('Embed this table with:', 'gratis-tables') . '</p>';
        echo '<input type="text" class="widefat code" readonly value="' . esc_attr('[gratis-table id="' . (int) $post->ID . '"]') . '" onclick="this.select()">';
    }

    /** Persist the CSV editor contents. */
    public static function save(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        if ('' === $nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id) || TableBlock::POST_TYPE !== $post->post_type) {
            return;
        }
        if (!isset($_POST['gt_csv'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; per-cell sanitisation happens in TableBlock::saveData().
        $csv  = (string) wp_unslash($_POST['gt_csv']);
        $data = TableBlock::parseCSV($csv);
        TableBlock::saveData($post_id, $data);
    }

    /** @param array<int,string> $fields */
    private static function toCsvLine(array $fields): string {
        return implode(',', array_map(static function ($f) {
            $f = (string) $f;
            if (preg_match('/[",\n]/', $f)) {
                return '"' . str_replace('"', '""', $f) . '"';
            }
            return $f;
        }, $fields));
    }
}
