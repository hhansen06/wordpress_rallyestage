<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_rallyestage_refresh_cache', [$this, 'handle_refresh']);
        add_action('admin_post_rallyestage_save_overrides', [$this, 'handle_save_overrides']);
    }

    public function add_menu(): void
    {
        add_options_page(
            'Rallyestage',
            'Rallyestage',
            'manage_options',
            'rallyestage',
            [$this, 'render_page']
        );
        add_submenu_page(
            'options-general.php',
            'Rallyestage – Text-Überschreibungen',
            'Rallyestage Texte',
            'manage_options',
            'rallyestage-overrides',
            [$this, 'render_overrides_page']
        );
    }

    public function register_settings(): void
    {
        register_setting('rallyestage_settings', 'rallyestage_base_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://api.rallyestage.de',
        ]);
        register_setting('rallyestage_settings', 'rallyestage_event_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ]);
        register_setting('rallyestage_settings', 'rallyestage_bearer_token', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('rallyestage_settings', 'rallyestage_cache_secret', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('rallyestage_settings', 'rallyestage_enable_wp_links', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);
    }

    public function handle_refresh(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        check_admin_referer('rallyestage_refresh_cache');

        $result = Rallyestage_API::fetch_and_cache();
        $status = $result['success'] ? 'refreshed=1' : 'refresh_error=' . rawurlencode($result['message']);

        wp_safe_redirect(admin_url('options-general.php?page=rallyestage&' . $status));
        exit;
    }

    public function handle_save_overrides(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        check_admin_referer('rallyestage_save_overrides');

        // Text-Überschreibungen verarbeiten
        $overrides = [];
        if (isset($_POST['title_override']) && is_array($_POST['title_override'])) {
            foreach ($_POST['title_override'] as $id => $title) {
                $title = sanitize_text_field($title);
                if (!empty($title)) {
                    $overrides[intval($id)] = $title;
                }
            }
        }

        // Ausgeblendete Einträge verarbeiten
        $hidden = [];
        if (isset($_POST['entry_hidden']) && is_array($_POST['entry_hidden'])) {
            foreach ($_POST['entry_hidden'] as $id => $value) {
                if ($value === '1') {
                    $hidden[] = intval($id);
                }
            }
        }

        update_option('rallyestage_title_overrides', $overrides, false);
        update_option('rallyestage_hidden_entries', $hidden, false);

        wp_safe_redirect(admin_url('options-general.php?page=rallyestage-overrides&saved=1'));
        exit;
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options'))
            return;

        $base_url = get_option('rallyestage_base_url', 'https://api.rallyestage.de');
        $event_id = get_option('rallyestage_event_id', '');
        $bearer_token = get_option('rallyestage_bearer_token', '');
        $cache_secret = get_option('rallyestage_cache_secret', '');
        $enable_wp_links = get_option('rallyestage_enable_wp_links', true);
        $rest_url = rest_url('rallyestage/v1/refresh-cache');
        $cached = Rallyestage_API::get_cached_data();
        ?>
        <div class="wrap">
            <h1>Rallyestage – Einstellungen</h1>

            <?php if (isset($_GET['refreshed'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Cache wurde erfolgreich aktualisiert.</p>
                </div>
            <?php elseif (isset($_GET['refresh_error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p>Fehler: <?php echo esc_html(sanitize_text_field(wp_unslash($_GET['refresh_error']))); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('rallyestage_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rallyestage_base_url">API Base URL</label></th>
                        <td>
                            <select name="rallyestage_base_url" id="rallyestage_base_url">
                                <option value="https://api.rallyestage.de" <?php selected($base_url, 'https://api.rallyestage.de'); ?>>
                                    https://api.rallyestage.de (Produktion)
                                </option>
                                <option value="https://beta.rallyestage.de" <?php selected($base_url, 'https://beta.rallyestage.de'); ?>>
                                    https://beta.rallyestage.de (Beta)
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rallyestage_event_id">Event ID</label></th>
                        <td>
                            <input type="number" min="1" name="rallyestage_event_id" id="rallyestage_event_id"
                                value="<?php echo esc_attr($event_id); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rallyestage_bearer_token">Bearer Token</label></th>
                        <td>
                            <input type="text" name="rallyestage_bearer_token" id="rallyestage_bearer_token"
                                value="<?php echo esc_attr($bearer_token); ?>" class="regular-text" autocomplete="off" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rallyestage_cache_secret">Cache-Refresh Secret</label></th>
                        <td>
                            <input type="text" name="rallyestage_cache_secret" id="rallyestage_cache_secret"
                                value="<?php echo esc_attr($cache_secret); ?>" class="regular-text" autocomplete="off" />
                            <?php if ($cache_secret): ?>
                                <p class="description">
                                    REST-Endpunkt zum Cache erneuern:<br>
                                    <code><?php echo esc_html($rest_url); ?>?secret=<?php echo esc_html($cache_secret); ?></code>
                                </p>
                            <?php else: ?>
                                <p class="description">Legen Sie ein Secret fest, um den REST-Endpunkt zu aktivieren.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rallyestage_enable_wp_links">WP-Verlinkung</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rallyestage_enable_wp_links" id="rallyestage_enable_wp_links"
                                    value="1" <?php checked($enable_wp_links, true); ?> />
                                Wertungsprüfungen verlinken
                            </label>
                            <p class="description">Wenn deaktiviert, werden WPs im Zeitplan nicht verlinkt und die Detailseiten sind nicht aufrufbar.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Einstellungen speichern'); ?>
            </form>

            <hr>

            <h2>Shortcodes</h2>
            <table class="widefat striped" style="max-width:700px;margin-bottom:1.5em;">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[rallyestage_zeitplan]</code></td>
                        <td>Zeigt den tabellarischen Zeitplan des Events. Pro Veranstaltungstag eine Spalte, Termine
                            untereinander. Wertungsprüfungen werden hervorgehoben und verlinken zur Detailseite.</td>
                    </tr>
                    <tr>
                        <td><code>[rallyestage_nennungen]</code></td>
                        <td>Zeigt alle Teilnehmer in einem responsiven Karten-Grid. Anzeige: Startnummer, Fahrer, Beifahrer, Fahrzeug und Klasse.</td>
                    </tr>
                    <tr>
                        <td><code>[rallyestage_wp id="<em>ID</em>"]</code></td>
                        <td>Zeigt die OpenStreetMap-Karte einer Wertungsprüfung mit Streckenverlauf und POIs (SS Start, Flying
                            Finish, Zuschauerpunkte). Wird automatisch auf den generierten WP-Seiten eingebunden.</td>
                    </tr>
                </tbody>
            </table>

            <hr>

            <h2>Cache</h2>
            <?php if ($cached): ?>
                <p>
                    Cache vorhanden: <strong><?php echo esc_html($cached['name'] ?? 'Unbekanntes Event'); ?></strong>
                    (<?php echo esc_html($cached['date_from'] ?? ''); ?> – <?php echo esc_html($cached['date_to'] ?? ''); ?>)
                </p>
            <?php else: ?>
                <p>Kein Cache vorhanden. Bitte Event ID und Bearer Token speichern und dann den Cache aktualisieren.</p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="rallyestage_refresh_cache">
                <?php wp_nonce_field('rallyestage_refresh_cache'); ?>
                <?php submit_button('Cache jetzt aktualisieren', 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public function render_overrides_page(): void
    {
        if (!current_user_can('manage_options'))
            return;

        $cached = Rallyestage_API::get_cached_data();
        $overrides = get_option('rallyestage_title_overrides', []);
        $hidden = get_option('rallyestage_hidden_entries', []);
        ?>
        <div class="wrap">
            <h1>Rallyestage – Text-Überschreibungen</h1>

            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Text-Überschreibungen wurden erfolgreich gespeichert.</p>
                </div>
            <?php endif; ?>

            <?php if (!$cached || empty($cached['schedule'])): ?>
                <div class="notice notice-warning">
                    <p>Keine Eventdaten vorhanden. Bitte erst den Cache aktualisieren unter <a
                            href="<?php echo esc_url(admin_url('options-general.php?page=rallyestage')); ?>">Rallyestage
                            Einstellungen</a>.</p>
                </div>
            <?php else: ?>
                <p>Hier können Sie für jedes Terminelement den angezeigten Text überschreiben oder den Eintrag ausblenden. Lassen Sie ein Feld leer, um den
                    Original-Text zu verwenden.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="rallyestage_save_overrides">
                    <?php wp_nonce_field('rallyestage_save_overrides'); ?>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th style="width:100px;">Datum</th>
                                <th style="width:80px;">Zeit</th>
                                <th style="width:40px;">WP</th>
                                <th>Original-Text</th>
                                <th style="width:300px;">Überschreiben mit</th>
                                <th style="width:100px;">Ausblenden</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Einträge nach Datum und Zeit sortieren
                            $schedule = $cached['schedule'];
                            usort($schedule, function ($a, $b) {
                                $date_cmp = strcmp($a['entry_date'] ?? '', $b['entry_date'] ?? '');
                                if ($date_cmp !== 0)
                                    return $date_cmp;
                                return strcmp($a['display_entry_time'] ?? '', $b['display_entry_time'] ?? '');
                            });

                            foreach ($schedule as $entry):
                                $entry_id = intval($entry['id']);
                                $override_value = $overrides[$entry_id] ?? '';
                                $is_hidden = in_array($entry_id, $hidden, true);
                                ?>
                                <tr>
                                    <td><?php echo esc_html($entry_id); ?></td>
                                    <td><?php echo esc_html($entry['entry_date'] ?? ''); ?></td>
                                    <td><?php echo esc_html($entry['display_entry_time'] ?? ''); ?></td>
                                    <td style="text-align:center;"><?php echo $entry['is_wp'] ? '✓' : ''; ?></td>
                                    <td><strong><?php echo esc_html($entry['title'] ?? ''); ?></strong></td>
                                    <td>
                                        <input type="text" name="title_override[<?php echo esc_attr($entry_id); ?>]"
                                            value="<?php echo esc_attr($override_value); ?>" class="widefat"
                                            placeholder="<?php echo esc_attr($entry['title'] ?? ''); ?>" />
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="entry_hidden[<?php echo esc_attr($entry_id); ?>]"
                                            value="1" <?php checked($is_hidden, true); ?> />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit">
                        <?php submit_button('Überschreibungen speichern', 'primary', 'submit', false); ?>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
