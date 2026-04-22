<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Registers the [rallyestage_nennungen] shortcode.
 * 
 * Displays all participants in a card grid similar to driver-of-the-day plugin.
 * 
 * Usage: [rallyestage_nennungen]
 */
class Rallyestage_Shortcode_Nennungen
{

    public function __construct()
    {
        add_shortcode('rallyestage_nennungen', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void
    {
        wp_enqueue_style(
            'rallyestage-nennungen',
            RALLYESTAGE_PLUGIN_URL . 'assets/css/rallyestage-nennungen.css',
            [],
            RALLYESTAGE_VERSION
        );
    }

    public function render(): string
    {
        $data = Rallyestage_API::get_cached_data();

        if (!$data) {
            return '<p class="rallyestage-error">Keine Eventdaten vorhanden. Bitte Cache aktualisieren.</p>';
        }

        $participants = $data['participants'] ?? [];

        if (empty($participants)) {
            return '<p class="rallyestage-error">Keine Nennungen vorhanden.</p>';
        }

        // Sort participants by start number
        usort($participants, function ($a, $b) {
            return intval($a['start_nr'] ?? 0) - intval($b['start_nr'] ?? 0);
        });

        // Get BAM theme color
        $widget_style = '';
        if (get_stylesheet() === 'bam' || get_template() === 'bam') {
            $primary = sanitize_hex_color((string) get_theme_mod('bam_link_color', '#00aeef'));
            if (empty($primary)) {
                $primary = sanitize_hex_color((string) get_theme_mod('bam_primary_color', '#ff4f4f'));
            }
            if (!empty($primary)) {
                $widget_style = '--nennungen-primary:' . $primary . ';';
            }
        }

        ob_start();
        ?>
        <div class="rallyestage-nennungen-wrap" style="<?php echo esc_attr($widget_style); ?>">
            <div class="rallyestage-nennungen-grid">
                <?php foreach ($participants as $participant): ?>
                    <?php $this->render_card($participant); ?>
                <?php endforeach; ?>
            </div>
            <div class="rallyestage-nennungen-footer">
                Insgesamt <?php echo count($participants); ?> Nennungen
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_card(array $participant): void
    {
        $start_nr = $participant['start_nr'] ?? '';
        $driver = $participant['driver_name'] ?? '';
        $codriver = $participant['codriver_name'] ?? '';
        $vehicle = $participant['vehicle'] ?? '';
        $klasse = $participant['klasse'] ?? '';
        $nat = $participant['nationality'] ?? '';
        ?>
        <div class="rallyestage-nennungen-card">
            <div class="rallyestage-card-nr">#<?php echo esc_html($start_nr); ?></div>
            <div class="rallyestage-card-driver"><?php echo esc_html($driver); ?></div>
            <?php if ($codriver): ?>
                <div class="rallyestage-card-codriver"><?php echo esc_html($codriver); ?></div>
            <?php endif; ?>
            <?php if ($vehicle): ?>
                <div class="rallyestage-card-vehicle"><?php echo esc_html($vehicle); ?></div>
            <?php endif; ?>
            <?php if ($klasse): ?>
                <div class="rallyestage-card-klasse"><?php echo esc_html($klasse); ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
