<?php
/**
 * Template für einzelne Flipbooks
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
    echo '<main><p>Kein Flipbook angegeben.</p></main>';
}

get_footer();