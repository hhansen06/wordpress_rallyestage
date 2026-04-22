# Rallyestage WordPress Plugin

Ein WordPress-Plugin zur Einbindung von Rallye-Veranstaltungsdaten von der [rallyestage.de](https://rallyestage.de) API.

## Beschreibung

Das Plugin ruft Veranstaltungsdaten von der externen `rallyestage.de` API ab, speichert sie lokal im WordPress-Cache und stellt sie dem Besucher als interaktiven Zeitplan und detaillierte Wertungsprüfungs-Karten (OpenStreetMap / Leaflet) bereit.

## Funktionen

- **API-Integration** — Abruf strukturierter Rallye-Daten per Bearer-Token-Authentifizierung
- **Lokales Caching** — Speicherung der API-Antwort in `wp_options`; kein direkter API-Aufruf im Frontend
- **Zeitplan-Shortcode** — Responsiver, tageweise gruppierter Veranstaltungsplan; WP-Einträge werden hervorgehoben und verlinkt
- **Virtuelle WP-Detailseiten** — Seiten unter `/wp/{id}/` ohne eigene WordPress-Beiträge (Custom Rewrite Rules)
- **Interaktive Karte** — Leaflet/OpenStreetMap mit Streckenverlauf (rot) und farbcodierten POI-Markern
- **REST-Endpunkt** — Externer Webhook-Trigger zur Cache-Aktualisierung per Shared Secret
- **Admin-Einstellungsseite** — Konfiguration direkt im WordPress-Backend

## Installation

1. Plugin-Ordner `wordpress_rallyestage` in `/wp-content/plugins/` ablegen
2. Plugin im WordPress-Backend unter **Plugins** aktivieren
3. Einstellungen unter **Einstellungen → Rallyestage** vornehmen

## Konfiguration

Unter **Einstellungen → Rallyestage** stehen folgende Felder zur Verfügung:

| Einstellung | Beschreibung |
|---|---|
| API Base URL | Endpunkt-Auswahl: Produktion oder Beta |
| Event ID | ID der Veranstaltung aus der rallyestage.de API |
| Bearer Token | Authentifizierungs-Token für die API |
| Cache-Refresh Secret | Gemeinsames Geheimnis für den externen Webhook |

Nach der Konfiguration kann der Cache manuell über den Button **"Cache jetzt aktualisieren"** auf der Einstellungsseite befüllt werden.

## Shortcodes

### `[rallyestage_zeitplan]`

Gibt den vollständigen Veranstaltungszeitplan als tageweise gruppierten Plan aus. Wertungsprüfungen (WPs) werden fett, rot und als Link zur Detailseite dargestellt.

```
[rallyestage_zeitplan]
```

### `[rallyestage_wp id="123"]`

Gibt die interaktive Leaflet-Karte für die Wertungsprüfung mit der ID `123` aus, inklusive Streckenverlauf und POI-Markern.

```
[rallyestage_wp id="123"]
```

**POI-Marker-Legende:**

| Typ | Farbe |
|---|---|
| SS Start | Grün |
| Flying Finish | Rot |
| Zuschauerpunkt | Blau |

## Virtuelle Detailseiten

WP-Detailseiten sind unter folgendem URL-Muster erreichbar, ohne dass WordPress-Beiträge angelegt werden müssen:

```
/wp/{id}/
```

Das Template `templates/wp-detail.php` wird automatisch geladen und bindet den `[rallyestage_wp]`-Shortcode ein. Der Seitentitel wird dynamisch auf den Namen der Wertungsprüfung gesetzt.

## REST API

### Cache-Aktualisierung per Webhook

```
GET /wp-json/rallyestage/v1/refresh-cache?secret=DEIN_SECRET
```

| Antwort | Bedeutung |
|---|---|
| `{"success": true, ...}` | Cache erfolgreich aktualisiert |
| `{"success": false, ...}` | Fehler beim API-Abruf (HTTP 500) |
| HTTP 403 | Falsches oder fehlendes Secret |

## Dateistruktur

```
wordpress_rallyestage/
├── wordpress_rallyestage.php          # Plugin-Bootstrap
├── assets/
│   ├── css/rallyestage.css            # Frontend-Styles
│   └── js/map.js                      # Leaflet-Karteninitialisierung
├── includes/
│   ├── class-api.php                  # API-Abruf & Caching
│   ├── class-admin.php                # Admin-Einstellungsseite
│   ├── class-shortcode-zeitplan.php   # [rallyestage_zeitplan] Shortcode
│   ├── class-shortcode-wp-map.php     # [rallyestage_wp] Shortcode
│   ├── class-wp-pages.php             # Virtuelle WP-Detailseiten
│   └── class-rest-routes.php          # REST-API-Endpunkt
└── templates/
    └── wp-detail.php                  # Seitentemplate für WP-Detailseiten
```

## Datenbank

Das Plugin nutzt keine eigenen Datenbanktabellen. Alle Daten werden in der nativen `wp_options`-Tabelle gespeichert:

| Option | Beschreibung |
|---|---|
| `rallyestage_base_url` | API-Basis-URL |
| `rallyestage_event_id` | Veranstaltungs-ID |
| `rallyestage_bearer_token` | API-Bearer-Token |
| `rallyestage_cache_secret` | Webhook-Shared-Secret |
| `rallyestage_event_data` | Gecachte API-Antwort (autoload: false) |

## Abhängigkeiten

| Abhängigkeit | Typ | Version |
|---|---|---|
| WordPress | Core | — |
| Leaflet.js | CDN | 1.9.4 |
| OpenStreetMap | Tiles (CDN) | — |
| rallyestage.de API | Externe REST API | — |

## Lizenz

GPL-2.0+
