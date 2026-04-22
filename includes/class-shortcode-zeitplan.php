<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_Shortcode_Zeitplan
{

    public function __construct()
    {
        add_shortcode('rallyestage_zeitplan', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void
    {
        wp_enqueue_style(
            'rallyestage',
            RALLYESTAGE_PLUGIN_URL . 'assets/css/rallyestage.css',
            [],
            RALLYESTAGE_VERSION
        );
    }

    public function render(): string
    {
        $data = Rallyestage_API::get_cached_data();

        if (!$data) {
            return '<p class="rallyestage-error">Keine Eventdaten vorhanden. Bitte Cache im Admin aktualisieren.</p>';
        }

        $schedule = $data['schedule'] ?? [];

        if (empty($schedule)) {
            return '<p class="rallyestage-error">Kein Zeitplan vorhanden.</p>';
        }

        // Einstellungen laden
        $enable_wp_links = get_option('rallyestage_enable_wp_links', true);

        // Einträge nach Datum gruppieren und Datum aufsteigend sortieren
        $days = [];
        foreach ($schedule as $entry) {
            $days[$entry['entry_date']][] = $entry;
        }
        ksort($days);

        ob_start();
        ?>
        <div class="rallyestage-zeitplan-wrap">
            <?php foreach ($days as $date => $entries): ?>
                <div class="rallyestage-zeitplan-day">
                    <div class="rallyestage-day-header"><?php echo esc_html($this->format_date($date)); ?></div>
                    <ul class="rallyestage-day-entries">
                        <?php foreach ($entries as $entry): ?>
                            <li class="rallyestage-entry<?php echo $entry['is_wp'] ? ' rallyestage-entry--wp' : ''; ?>">
                                <?php if ($this->format_time($entry)): ?>
                                    <span class="rallyestage-time"><?php echo esc_html($this->format_time($entry)); ?></span>
                                <?php endif; ?>
                                <?php if ($entry['is_wp'] && $enable_wp_links): ?>
                                    <span class="rallyestage-title">
                                        <a
                                            href="<?php echo esc_url($this->get_wp_page_url($entry['id'])); ?>"><?php echo esc_html(Rallyestage_API::get_entry_title($entry)); ?></a>
                                    </span>
                                <?php else: ?>
                                    <span class="rallyestage-title"><?php echo esc_html(Rallyestage_API::get_entry_title($entry)); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
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

    private function format_time(array $entry): string
    {
        $start = $entry['display_entry_time'] ?? null;
        $end = $entry['display_entry_end_time'] ?? null;

        if (!$start)
            return '';

        $start = substr($start, 0, 5);
        if ($end) {
            return $start . '–' . substr($end, 0, 5);
        }
        return $start . ' Uhr';
    }

    private function get_wp_page_url(int $entry_id): string
    {
        return home_url('/wp/' . $entry_id . '/');
    }
}
