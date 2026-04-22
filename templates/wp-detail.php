<?php
/**
 * Template für die virtuelle Wertungsprüfungs-Detailseite.
 * Wird geladen wenn der Query-Var "rallyestage_wp_id" gesetzt ist.
 */
if (!defined('ABSPATH'))
    exit;

$wp_id = intval(get_query_var('rallyestage_wp_id'));
$data = Rallyestage_API::get_cached_data();

$entry = null;
if ($data) {
    foreach ($data['schedule'] as $e) {
        if ((int) $e['id'] === $wp_id && $e['is_wp']) {
            $entry = $e;
            break;
        }
    }
}

get_header();
?>
<div class="rallyestage-page-content">
    <?php if (!$entry): ?>
        <p class="rallyestage-error">Wertungsprüfung nicht gefunden.</p>
    <?php else: ?>
        <h1 class="rallyestage-page-title"><?php echo esc_html(Rallyestage_API::get_entry_title($entry)); ?></h1>
        <?php echo do_shortcode('[rallyestage_wp id="' . $wp_id . '"]'); ?>
    <?php endif; ?>
</div>
<?php
get_footer();
