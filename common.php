<?php

// renders the iframe embed html element
function hcd_generate_flipbook_iframe($embed_url, $width = null, $height = null) {
    if ($width === null) {
        $width = hcd_get_default_flipbook_width();
    } else {
        $width = hcd_sanitize_dimension($width, hcd_get_default_flipbook_width());
    }
    if ($height === null) {
        $height = hcd_get_default_flipbook_height();
    } else {
        $height = hcd_sanitize_dimension($height, hcd_get_default_flipbook_height());
    }

    $embed_url = hcd_get_safe_embed_url($embed_url);
    if ($embed_url === '') {
        return '<p>' . esc_html__('Invalid Heyzine embed URL.', 'heyzine-content-display') . '</p>';
    }

    return '<iframe src="' . esc_url($embed_url) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" style="border:0;width:' . esc_attr($width) . ';height:' . esc_attr($height) . ';" frameborder="0" allowfullscreen></iframe>';
}

function hcd_get_safe_embed_url($url) {
    $url = trim((string) $url);
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https' || empty($parts['host'])) {
        return '';
    }

    $host = strtolower($parts['host']);
    if (!in_array($host, array('heyzine.com', 'www.heyzine.com'), true)) {
        return '';
    }
    if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
        return '';
    }

    return esc_url_raw($url, array('https'));
}

// renders php to display a flipbook embed. can show a specific id or dynamically show the newest one
function hcd_render_flipbook_embed($flipbookId = null, $showNewest = false, $width = null, $height = null) {
    $items = hcd_call_api('flipbook-list', array());

    if (isset($items['success']) && $items['success'] === false) {
        return '<p>' . sprintf(esc_html__('API error: %s', 'heyzine-content-display'), esc_html($items['msg'])) . '</p>';
    }

    if (empty($items)) {
        return '<p>' . esc_html__('No flipbooks found.', 'heyzine-content-display') . '</p>';
    }

    if ($showNewest) {
        // Sort items by date descending
        usort($items, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $flipbook = $items[0];
    } elseif ($flipbookId !== null) {
        // Find flipbook by ID
        $flipbook = null;
        foreach ($items as $item) {
            if ($item['id'] == $flipbookId) {
                $flipbook = $item;
                break;
            }
        }
        if ($flipbook === null) {
            return '<p>' . esc_html__('Flipbook not found.', 'heyzine-content-display') . '</p>';
        }
    } else {
        return '<p>' . esc_html__('No flipbook was specified.', 'heyzine-content-display') . '</p>';
    }

    $embed_url = isset($flipbook['links']['base']) ? hcd_get_safe_embed_url($flipbook['links']['base']) : '';
    if ($embed_url === '') {
        return '<p>' . esc_html__('Invalid Heyzine embed URL.', 'heyzine-content-display') . '</p>';
    }
    $title = esc_html($flipbook['title']);

    return hcd_generate_flipbook_iframe($embed_url, $width, $height);
}

// renders a list of flipbook thumbs as links
function hcd_flipbook_list($atts) {

    $atts = shortcode_atts(array(
        'start' => 0,
        'count' => 10,
        'order' => 'desc',
        'columns_mobile' => 2,
        'columns_tablet' => 3,
        'columns_desktop' => 5,
        'show_titles' => false,
        'open_in_new_window' => false,
        'open_on_site_embed' => false,
    ), $atts, 'hcd_list');

    $params = array();

    $items = hcd_call_api('flipbook-list', $params);

    if (isset($items['success']) && $items['success'] === false) {
        return '<p>' . sprintf(esc_html__('API error: %s', 'heyzine-content-display'), esc_html($items['msg'])) . '</p>';
    }

    if (empty($items)) {
        return '<p>' . esc_html__('No flipbooks found.', 'heyzine-content-display') . '</p>';
    }

    // Sort items by date according to order
    usort($items, function($a, $b) use ($atts) {
        $timeA = strtotime($a['date']);
        $timeB = strtotime($b['date']);
        if ($atts['order'] === 'asc') {
            return $timeA - $timeB;
        } else {
            return $timeB - $timeA;
        }
    });

    // Slice items array from start for count elements
    $start = max(0, intval($atts['start']));
    $count = max(0, intval($atts['count']));
    $items = array_slice($items, $start, $count);

    if (empty($items)) {
        return '<p>' . esc_html__('No flipbooks found.', 'heyzine-content-display') . '</p>';
    }

    $columns_mobile = intval($atts['columns_mobile']);
    $columns_tablet = intval($atts['columns_tablet']);
    $columns_desktop = intval($atts['columns_desktop']);

    $style = "
        display: grid;
        grid-template-columns: repeat({$columns_mobile}, 1fr);
        gap: 1rem;
    ";

    $style .= "
        @media (min-width: 600px) {
            grid-template-columns: repeat({$columns_tablet}, 1fr);
        }
        @media (min-width: 1024px) {
            grid-template-columns: repeat({$columns_desktop}, 1fr);
        }
    ";

    // Because inline styles do not support media queries, we will add a style tag with a unique class
    $unique_class = 'hcd-grid-' . uniqid();

    $output = '<style>
        .' . $unique_class . ' {
            display: grid;
            grid-template-columns: repeat(' . $columns_mobile . ', 1fr);
            gap: 1rem;
            align-items: center;
        }
        @media (min-width: 600px) {
            .' . $unique_class . ' {
                grid-template-columns: repeat(' . $columns_tablet . ', 1fr);
            }
        }
        @media (min-width: 1024px) {
            .' . $unique_class . ' {
                grid-template-columns: repeat(' . $columns_desktop . ', 1fr);
            }
        }
    </style>';

    $output .= '<div class="hcd-list ' . esc_attr($unique_class) . '">';
    $target = $atts['open_in_new_window'] ? ' target="_blank" rel="noopener noreferrer"' : '';
    foreach ($items as $item) {
        $flipbook_id = pathinfo((string) $item['id'], PATHINFO_FILENAME);
        $url = $atts['open_on_site_embed'] ? esc_url(hcd_get_flipbook_url($flipbook_id)) : esc_url(hcd_get_safe_embed_url(isset($item['links']['base']) ? $item['links']['base'] : ''));
        $title = esc_html($item['title']);
        $thumbnail = isset($item['links']['thumbnail']) ? esc_url($item['links']['thumbnail']) : '';

        $output .= '<div class="hcd-list-item hover">';
        if ($thumbnail && $url) {
            $output .= '<a href="' . $url . '"' . $target . '><img src="' . $thumbnail . '" alt="' . $title . '" /></a>';
        } elseif ($thumbnail) {
            $output .= '<img src="' . $thumbnail . '" alt="' . $title . '" />';
        }
        if ($atts['show_titles']) {
            $output .= $url ? ' <a href="' . $url . '"' . $target . '>' . $title . '</a>' : ' ' . $title;
        }
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}

function hcd_sanitize_rewrite_slug($slug) {
    $slug = sanitize_title((string) $slug);
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'magazin-ansicht';
}

function hcd_get_rewrite_slug() {
    return hcd_sanitize_rewrite_slug(get_option('hcd_rewrite_slug', 'magazin-ansicht'));
}

function hcd_is_pretty_urls_enabled() {
    $enabled = get_option('hcd_pretty_urls', '1');
    return !in_array($enabled, array('0', 0, false), true);
}

function hcd_get_flipbook_url($flipbook_id) {
    $flipbook_id = (string) $flipbook_id;

    if (!hcd_is_pretty_urls_enabled()) {
        return add_query_arg('flipbook_id', $flipbook_id, home_url('/'));
    }

    return home_url('/' . hcd_get_rewrite_slug() . '/' . rawurlencode($flipbook_id) . '/');
}

function hcd_register_rewrite_rules() {
    add_rewrite_tag('%flipbook_id%', '([^&]+)');

    if (hcd_is_pretty_urls_enabled()) {
        add_rewrite_rule('^' . hcd_get_rewrite_slug() . '/([^/]+)/?$', 'index.php?flipbook_id=$matches[1]', 'top');
    }
}
add_action('init', 'hcd_register_rewrite_rules');

add_filter('template_include', function($template) {
    $flipbook_id = get_query_var('flipbook_id');
    if ($flipbook_id) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-flipbook.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
});

// add_filter('template_include', function($template) {
//     if (isset($_GET['id']) && is_page('flipbook')) {
//         $custom_template = plugin_dir_path(__FILE__) . 'templates/single-flipbook.php';
//         if (file_exists($custom_template)) {
//             return $custom_template;
//         }
//     }
//     return $template;
// });

