<?php declare(strict_types=1);

namespace Gratis\Tables;

defined('ABSPATH') || exit;

/**
 * Global settings for Gratis Tables.
 */
final class Settings {

    private const GROUP = 'gratis_tables_settings_group';
    private const PAGE  = 'gratis-tables-settings';

    private const OPT_SEARCH = 'gratis_tables_search';
    private const OPT_SORT   = 'gratis_tables_sort';

    /** Register the settings submenu under the Tables menu. */
    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . TableBlock::POST_TYPE,
            __('Tables Settings', 'gratis-tables'),
            __('Settings', 'gratis-tables'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register options. */
    public static function register_settings(): void {
        register_setting(self::GROUP, self::OPT_SEARCH, [
            'type'              => 'boolean',
            'sanitize_callback' => static fn($v) => (bool) $v,
            'default'           => true,
        ]);
        register_setting(self::GROUP, self::OPT_SORT, [
            'type'              => 'boolean',
            'sanitize_callback' => static fn($v) => (bool) $v,
            'default'           => true,
        ]);
    }

    /** Whether the search box is shown by default. */
    public static function defaultSearch(): bool {
        return (bool) get_option(self::OPT_SEARCH, true);
    }

    /** Whether column sorting is enabled by default. */
    public static function defaultSort(): bool {
        return (bool) get_option(self::OPT_SORT, true);
    }

    /** Render the settings screen. */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-tables'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Tables Settings', 'gratis-tables') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Search box', 'gratis-tables') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_SEARCH) . '" value="1"' . checked(self::defaultSearch(), true, false) . '> ';
        echo esc_html__('Show a search box above tables by default.', 'gratis-tables') . '</label></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Column sorting', 'gratis-tables') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_SORT) . '" value="1"' . checked(self::defaultSort(), true, false) . '> ';
        echo esc_html__('Allow visitors to sort columns by default.', 'gratis-tables') . '</label></td></tr>';

        echo '</tbody></table>';
        submit_button(__('Save settings', 'gratis-tables'));
        echo '</form>';

        echo '<hr><h2>' . esc_html__('Usage', 'gratis-tables') . '</h2>';
        echo '<p><code>[gratis-table id="12" search="true" sort="true"]</code></p>';
        echo '</div>';
    }
}
