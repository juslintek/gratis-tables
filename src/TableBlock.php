<?php declare(strict_types=1);

namespace Gratis\Tables;

defined('ABSPATH') || exit;

/**
 * Table CPT plus the `[gratis-table]` sortable / searchable renderer.
 */
final class TableBlock {

    public const POST_TYPE = 'gratis_table';

    /** Register the table CPT and front-end styles. */
    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels'       => [
                'name'          => __('Tables', 'gratis-tables'),
                'singular_name' => __('Table', 'gratis-tables'),
                'add_new_item'  => __('Add Table', 'gratis-tables'),
                'edit_item'     => __('Edit Table', 'gratis-tables'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-editor-table',
            'supports'     => ['title', 'custom-fields'],
        ]);

        add_action('wp_enqueue_scripts', static function () {
            if (!is_admin()) {
                wp_register_style('gratis-tables', false, [], GRATIS_TABLES_VERSION);
                wp_add_inline_style('gratis-tables', self::css());
                wp_enqueue_style('gratis-tables');
            }
        });
    }

    /**
     * `[gratis-table id="12" search="true" sort="true"]`.
     *
     * @param array<string,mixed>|string $atts Shortcode attributes.
     */
    public static function shortcode($atts): string {
        $atts = shortcode_atts([
            'id'     => 0,
            'search' => Settings::defaultSearch() ? 'true' : 'false',
            'sort'   => Settings::defaultSort() ? 'true' : 'false',
        ], $atts);

        $table = get_post((int) $atts['id']);
        if (!$table || self::POST_TYPE !== $table->post_type) {
            return '';
        }

        $data = self::getData($table->ID);
        if (empty($data['headers']) && empty($data['rows'])) {
            return '<p>' . esc_html__('No table data.', 'gratis-tables') . '</p>';
        }

        $headers    = $data['headers'];
        $rows       = $data['rows'];
        $searchable = 'true' === $atts['search'];
        $sortable   = 'true' === $atts['sort'];
        $table_id   = 'gt-' . (int) $table->ID;

        $html = '<div class="gratis-table-wrap">';
        if ($searchable) {
            $html .= '<input type="search" class="gratis-table-search" placeholder="'
                . esc_attr__('Search…', 'gratis-tables') . '" aria-label="'
                . esc_attr__('Search table', 'gratis-tables') . '" data-table="' . esc_attr($table_id) . '">';
        }
        $html .= '<table class="gratis-table" id="' . esc_attr($table_id) . '">';
        $html .= '<thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th' . ($sortable ? ' class="sortable" tabindex="0"' : '') . '>' . esc_html((string) $h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ((array) $row as $cell) {
                $html .= '<td>' . esc_html((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        if ($sortable || $searchable) {
            $html .= '<script>' . self::js() . '</script>';
        }
        return $html;
    }

    /**
     * Read normalised `{headers, rows}` table data for a post.
     *
     * @return array{headers:array<int,string>,rows:array<int,array<int,string>>}
     */
    public static function getData(int $postId): array {
        $data = get_post_meta($postId, '_table_data', true);
        return [
            'headers' => is_array($data['headers'] ?? null) ? array_values($data['headers']) : [],
            'rows'    => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
        ];
    }

    /**
     * Parse raw CSV text into `{headers, rows}`.
     *
     * @return array{headers:array<int,string>,rows:array<int,array<int,string>>}
     */
    public static function parseCSV(string $csv): array {
        $lines = array_values(array_filter(array_map('trim', explode("\n", trim($csv))), static fn($l) => '' !== $l));
        if (empty($lines)) {
            return ['headers' => [], 'rows' => []];
        }
        $headers = str_getcsv((string) array_shift($lines));
        $rows    = array_map(static fn($l) => str_getcsv($l), $lines);
        return ['headers' => $headers, 'rows' => $rows];
    }

    /** Persist normalised table data for a post. */
    public static function saveData(int $postId, array $data): void {
        update_post_meta($postId, '_table_data', [
            'headers' => array_map('sanitize_text_field', $data['headers'] ?? []),
            'rows'    => array_map(
                static fn($row) => array_map('sanitize_text_field', (array) $row),
                $data['rows'] ?? []
            ),
        ]);
    }

    /** Import CSV data into a new table post. */
    public static function importCSV(string $csv, string $title = 'Imported Table'): int {
        $data = self::parseCSV($csv);
        if (empty($data['headers'])) {
            return 0;
        }
        $post_id = wp_insert_post([
            'post_type'   => self::POST_TYPE,
            'post_title'  => $title,
            'post_status' => 'publish',
        ], true);
        if (is_wp_error($post_id)) {
            return 0;
        }
        self::saveData($post_id, $data);
        return $post_id;
    }

    private static function css(): string {
        return '.gratis-table-wrap{overflow-x:auto;margin:1.5em 0}.gratis-table{width:100%;border-collapse:collapse;font-size:0.9rem}.gratis-table th,.gratis-table td{padding:10px 14px;border-bottom:1px solid rgba(127,127,127,0.2);text-align:left}.gratis-table th.sortable{cursor:pointer;user-select:none}.gratis-table th.sortable:hover,.gratis-table th.sortable:focus{background:rgba(127,127,127,0.1)}.gratis-table tr:hover td{background:rgba(127,127,127,0.06)}.gratis-table-search{width:100%;max-width:300px;padding:8px 12px;margin-bottom:12px;border:1px solid rgba(127,127,127,0.3);border-radius:6px;background:transparent;color:inherit;font-size:0.9rem}';
    }

    private static function js(): string {
        return '!function(){document.querySelectorAll(".gratis-table-search").forEach(function(s){s.addEventListener("input",function(){var t=document.getElementById(s.dataset.table),v=s.value.toLowerCase();if(!t)return;t.querySelectorAll("tbody tr").forEach(function(r){r.style.display=r.textContent.toLowerCase().includes(v)?"":"none"})})});document.querySelectorAll(".gratis-table th.sortable").forEach(function(th){function sort(){var t=th.closest("table"),i=Array.prototype.indexOf.call(th.parentNode.children,th),rows=Array.prototype.slice.call(t.querySelectorAll("tbody tr")),asc=th.dataset.sort!=="asc";rows.sort(function(a,b){return a.children[i].textContent.localeCompare(b.children[i].textContent,undefined,{numeric:true})*(asc?1:-1)});th.dataset.sort=asc?"asc":"desc";var tb=t.querySelector("tbody");rows.forEach(function(r){tb.appendChild(r)})}th.addEventListener("click",sort);th.addEventListener("keydown",function(e){if(e.key==="Enter"||e.key===" "){e.preventDefault();sort()}})})}()';
    }
}
