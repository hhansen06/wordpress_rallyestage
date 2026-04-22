<?php
/**
 * Plugin Name: Rallyestage
 * Plugin URI:  https://rallyestage.de
 * Description: Zeigt Zeitplan und Wertungsprüfungen von rallyestage.de
 * Version:     1.0.0
 * Author:      rallyestage.de
 * License:     GPL-2.0+
 * Text Domain: rallyestage
 */

if (!defined('ABSPATH'))
    exit;

define('RALLYESTAGE_VERSION', '1.0.0');
define('RALLYESTAGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RALLYESTAGE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-api.php';
require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-wp-pages.php';
require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-admin.php';
require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-shortcode-zeitplan.php';
require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-shortcode-wp-map.php';
require_once RALLYESTAGE_PLUGIN_DIR . 'includes/class-rest-routes.php';

register_activation_hook(__FILE__, function () {
    (new Rallyestage_WP_Pages())->add_rewrite_rule();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    new Rallyestage_Admin();
    new Rallyestage_Shortcode_Zeitplan();
    new Rallyestage_Shortcode_WP_Map();
    new Rallyestage_WP_Pages();
    new Rallyestage_Rest_Routes();
});
