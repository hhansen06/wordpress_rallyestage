<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_API
{

    const CACHE_KEY = 'rallyestage_event_data';

    public static function get_options(): array
    {
        return [
            'base_url' => get_option('rallyestage_base_url', 'https://api.rallyestage.de'),
            'event_id' => get_option('rallyestage_event_id', ''),
            'bearer_token' => get_option('rallyestage_bearer_token', ''),
        ];
    }

    public static function get_cached_data(): ?array
    {
        $data = get_option(self::CACHE_KEY);
        return $data ?: null;
    }

    /**
     * Gibt den Titel eines Schedule-Eintrags zurück, ggf. mit Überschreibung.
     *
     * @param array $entry Der Schedule-Eintrag
     * @return string Der finale Titel
     */
    public static function get_entry_title(array $entry): string
    {
        $entry_id = intval($entry['id'] ?? 0);
        $original_title = $entry['title'] ?? '';

        $overrides = get_option('rallyestage_title_overrides', []);
        
        if (isset($overrides[$entry_id]) && !empty($overrides[$entry_id])) {
            return $overrides[$entry_id];
        }

        return $original_title;
    }

    /**
     * Prüft, ob ein Schedule-Eintrag ausgeblendet werden soll.
     *
     * @param array $entry Der Schedule-Eintrag
     * @return bool True wenn der Eintrag ausgeblendet werden soll
     */
    public static function is_entry_hidden(array $entry): bool
    {
        $entry_id = intval($entry['id'] ?? 0);
        $hidden = get_option('rallyestage_hidden_entries', []);
        
        return in_array($entry_id, $hidden, true);
    }

    /**
     * Ruft Eventdaten von der API ab, speichert sie im Cache (wp_options)
     * und triggert die Synchronisierung der WP-Seiten.
     *
     * @return array{ success: bool, message: string }
     */
    public static function fetch_and_cache(): array
    {
        $opts = self::get_options();

        if (empty($opts['event_id']) || empty($opts['bearer_token'])) {
            return ['success' => false, 'message' => 'Event ID oder Bearer Token fehlt.'];
        }

        $url = trailingslashit($opts['base_url']) . 'api/public/event/' . intval($opts['event_id']);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $opts['bearer_token'],
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return ['success' => false, 'message' => 'API-Fehler: HTTP ' . intval($code)];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Ungültige JSON-Antwort von der API.'];
        }

        update_option(self::CACHE_KEY, $data, false);

        return ['success' => true, 'message' => 'Cache erfolgreich aktualisiert.'];
    }
}
