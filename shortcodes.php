<?php
// Shortcode to display the newest flipbook
function hcd_shortcode_newest($atts) {
    return hcd_render_flipbook_embed(null, true);
}
add_shortcode('display_heyzine_newest', 'hcd_shortcode_newest');

// Shortcode to display a list of items
function hcd_shortcode_list($atts) {
    return hcd_flipbook_list($atts);
}
add_shortcode('display_heyzine_list', 'hcd_shortcode_list');
