<?php
/**
 * Template for individual flipbooks
 */
get_header();
$flipbook_id = get_query_var('flipbook_id');
if ($flipbook_id) {
    $flipbook_id .= '.pdf';
    echo '<main>';
    echo '<section class="flipbook-embed"><div class="content">';
    echo hcd_render_flipbook_embed($flipbook_id, false, '95%', '90%');
    echo '</div></section>';
    echo '</main>';
} else {
    echo '<main><p>' . esc_html__('No flipbook was specified.', 'heyzine-content-display') . '</p></main>';
}

get_footer();
