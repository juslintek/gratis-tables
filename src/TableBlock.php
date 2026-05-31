<?php declare(strict_types=1);
namespace Gratis\Tables;

final class TableBlock
{
    public static function register(): void
    {
        register_post_type('gratis_table', [
            'labels'       => ['name' => 'Tables', 'singular_name' => 'Table'],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-editor-table',
            'supports'     => ['title', 'custom-fields'],
        ]);

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', function () {
            if (!is_admin()) {
                wp_register_style('gratis-tables', false);
                wp_add_inline_style('gratis-tables', self::css());
                wp_enqueue_style('gratis-tables');
            }
        });
    }

    public static function shortcode($atts): string
    {
        $atts = shortcode_atts(['id' => 0, 'search' => 'true', 'sort' => 'true'], $atts);
        $table = get_post((int) $atts['id']);
        if (!$table) return '';

        $data = get_post_meta($table->ID, '_table_data', true);
        if (!$data || !is_array($data)) return '<p>No table data.</p>';

        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];
        $searchable = $atts['search'] === 'true';
        $sortable = $atts['sort'] === 'true';

        $html = '<div class="gratis-table-wrap">';
        if ($searchable) {
            $html .= '<input type="search" class="gratis-table-search" placeholder="Search..." data-table="gt-' . $table->ID . '">';
        }
        $html .= '<table class="gratis-table" id="gt-' . $table->ID . '">';
        $html .= '<thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th' . ($sortable ? ' class="sortable"' : '') . '>' . esc_html($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        if ($sortable || $searchable) {
            $html .= '<script>' . self::js() . '</script>';
        }

        return $html;
    }

    /** Import CSV data into a table post. */
    public static function importCSV(string $csv, string $title = 'Imported Table'): int
    {
        $lines = array_filter(explode("\n", trim($csv)));
        if (count($lines) < 2) return 0;

        $headers = str_getcsv(array_shift($lines));
        $rows = array_map('str_getcsv', $lines);

        $post_id = wp_insert_post([
            'post_type'   => 'gratis_table',
            'post_title'  => $title,
            'post_status' => 'publish',
        ]);

        if ($post_id) {
            update_post_meta($post_id, '_table_data', ['headers' => $headers, 'rows' => $rows]);
        }
        return $post_id;
    }

    private static function css(): string
    {
        return '.gratis-table-wrap{overflow-x:auto;margin:1.5em 0}.gratis-table{width:100%;border-collapse:collapse;font-size:0.9rem}.gratis-table th,.gratis-table td{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,0.08);text-align:left}.gratis-table th{font-weight:600;cursor:pointer;user-select:none}.gratis-table th:hover{background:rgba(255,255,255,0.05)}.gratis-table tr:hover td{background:rgba(255,255,255,0.03)}.gratis-table-search{width:100%;max-width:300px;padding:8px 12px;margin-bottom:12px;border:1px solid rgba(255,255,255,0.15);border-radius:6px;background:transparent;color:inherit;font-size:0.9rem}';
    }

    private static function js(): string
    {
        return '!function(){document.querySelectorAll(".gratis-table-search").forEach(function(s){s.addEventListener("input",function(){var t=document.getElementById(s.dataset.table),v=s.value.toLowerCase();t.querySelectorAll("tbody tr").forEach(function(r){r.style.display=r.textContent.toLowerCase().includes(v)?"":"none"})})});document.querySelectorAll(".gratis-table th.sortable").forEach(function(th){th.addEventListener("click",function(){var t=th.closest("table"),i=Array.from(th.parentNode.children).indexOf(th),rows=Array.from(t.querySelectorAll("tbody tr")),asc=th.dataset.sort!=="asc";rows.sort(function(a,b){return a.children[i].textContent.localeCompare(b.children[i].textContent,undefined,{numeric:true})*(asc?1:-1)});th.dataset.sort=asc?"asc":"desc";rows.forEach(function(r){t.querySelector("tbody").appendChild(r)})})})}()';
    }
}
