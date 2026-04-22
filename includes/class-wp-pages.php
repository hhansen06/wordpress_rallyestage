<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_WP_Pages
{

    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rule']);
        add_filter('query_vars', [$this, 'add_query_var']);
        add_filter('template_include', [$this, 'maybe_load_template']);
        add_filter('document_title_parts', [$this, 'filter_title']);
    }

    public function add_rewrite_rule(): void
    {
        add_rewrite_rule(
            '^wp/([0-9]+)/?$',
            'index.php?rallyestage_wp_id=$matches[1]',
            'top'
        );
    }

    public function add_query_var(array $vars): array
    {
        $vars[] = 'rallyestage_wp_id';
        return $vars;
    }

    public function maybe_load_template(string $template): string
    {
        if (!get_query_var('rallyestage_wp_id')) {
            return $template;
        }
        
        // Prüfen, ob WP-Verlinkung aktiviert ist
        $enable_wp_links = get_option('rallyestage_enable_wp_links', true);
        if (!$enable_wp_links) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return get_404_template();
        }
        
        $custom = RALLYESTAGE_PLUGIN_DIR . 'templates/wp-detail.php';
        return file_exists($custom) ? $custom : $template;
    }

    public function filter_title(array $parts): array
    {
        $wp_id = intval(get_query_var('rallyestage_wp_id'));
        if (!$wp_id)
            return $parts;

        $data = Rallyestage_API::get_cached_data();
        if (!$data)
            return $parts;

        foreach ($data['schedule'] as $entry) {
            if ((int) $entry['id'] === $wp_id && $entry['is_wp']) {
                $parts['title'] = sanitize_text_field(Rallyestage_API::get_entry_title($entry));
                break;
            }
        }
        return $parts;
    }
}
