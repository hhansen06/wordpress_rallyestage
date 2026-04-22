<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_Shortcode_WP_Map
{

    public function __construct()
    {
        add_shortcode('rallyestage_wp', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets(): void
    {
        wp_register_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
        wp_register_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );
        wp_register_script(
            'rallyestage-map',
            RALLYESTAGE_PLUGIN_URL . 'assets/js/map.js',
            ['leaflet'],
            RALLYESTAGE_VERSION,
            true
        );
    }

    public function render(array $atts): string
    {
        $atts = shortcode_atts(['id' => 0], $atts, 'rallyestage_wp');
        $entry_id = intval($atts['id']);

        if (!$entry_id) {
            return '<p class="rallyestage-error">Keine WP-ID angegeben.</p>';
        }

        $data = Rallyestage_API::get_cached_data();
        if (!$data) {
            return '<p class="rallyestage-error">Keine Eventdaten vorhanden.</p>';
        }

        // Schedule-Eintrag finden
        $entry = null;
        foreach ($data['schedule'] as $e) {
            if ((int) $e['id'] === $entry_id && $e['is_wp']) {
                $entry = $e;
                break;
            }
        }

        if (!$entry) {
            return '<p class="rallyestage-error">Wertungsprüfung nicht gefunden.</p>';
        }

        // Track über track_id aus dem Schedule-Eintrag finden
        $tracks = [];
        $track_id = isset($entry['track_id']) ? intval($entry['track_id']) : 0;
        if ($track_id) {
            foreach ($data['tracks'] as $t) {
                if ((int) $t['id'] === $track_id) {
                    $tracks[] = $t;
                    break;
                }
            }
        }

        $map_id = 'rallyestage-map-' . $entry_id;

        // Assets einbinden
        wp_enqueue_style('leaflet');
        wp_enqueue_style('rallyestage');
        wp_enqueue_script('rallyestage-map');

        $map_data_json = wp_json_encode(['tracks' => $tracks, 'entry' => $entry]);
        $map_data_base64 = base64_encode($map_data_json);

        ob_start();
        ?>
        <div class="rallyestage-wp-detail">
            <div class="rallyestage-wp-meta">
                <?php echo esc_html($this->format_date($entry['entry_date'])); ?>
                <?php if (!empty($entry['display_entry_time'])): ?>
                    &nbsp;&middot;&nbsp;
                    <?php
                    $time_str = substr($entry['display_entry_time'], 0, 5);
                    if (!empty($entry['display_entry_end_time'])) {
                        $time_str .= '–' . substr($entry['display_entry_end_time'], 0, 5);
                    }
                    echo esc_html($time_str) . ' Uhr';
                    ?>
                <?php endif; ?>
            </div>
            <div id="<?php echo esc_attr($map_id); ?>" class="rallyestage-map"
                data-mapdata="<?php echo esc_attr($map_data_base64); ?>"></div>
            <?php if (!empty($tracks)): ?>
                <?php
                // Icons und Labels pro POI-Typ sammeln (erster Fund pro Typ)
                $poi_labels = [
                    'ss_start'      => 'SS Start',
                    'flying_finish' => 'Flying Finish',
                    'spectator'     => 'Zuschauerpunkt',
                ];
                $legend_items = [];
                foreach ($tracks as $t) {
                    foreach ($t['pois'] ?? [] as $poi) {
                        $type = $poi['type'] ?? '';
                        if ($type && !isset($legend_items[$type])) {
                            $legend_items[$type] = [
                                'label' => $poi_labels[$type] ?? $type,
                                'icon'  => $poi['icon'] ?? null,
                            ];
                        }
                    }
                }
                ?>
                <?php if (!empty($legend_items)): ?>
                <div class="rallyestage-map-legend">
                    <?php foreach ($legend_items as $type => $item): ?>
                        <span class="rallyestage-legend-item rallyestage-legend-<?php echo esc_attr($type); ?>">
                            <?php if ($item['icon']): ?>
                                <img src="<?php echo esc_attr($item['icon']); ?>" alt="" class="rallyestage-legend-icon" />
                            <?php else: ?>
                                <span class="rallyestage-legend-dot"></span>
                            <?php endif; ?>
                            <?php echo esc_html($item['label']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function format_date(string $date): string
    {
        $ts = strtotime($date);
        if (!$ts)
            return $date;
        $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
        return $days[(int) gmdate('w', $ts)] . ', ' . gmdate('d.m.Y', $ts);
    }
}
