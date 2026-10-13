<?php
// adds custom gutenberg blocks
if (!defined('ABSPATH')) exit;

function hcd_register_blocks() {

    // Register JS für Gutenberg
    wp_register_script(
        'hcd-blocks-js',
        plugins_url('blocks/index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'blocks/index.js')
    );

    wp_localize_script(
        'hcd-blocks-js',
        'hcdFlipbookBreakpoints',
        array(
            'mobileTablet' => function_exists('hcd_get_breakpoint_mobile_tablet') ? hcd_get_breakpoint_mobile_tablet() : 600,
            'tabletDesktop' => function_exists('hcd_get_breakpoint_tablet_desktop') ? hcd_get_breakpoint_tablet_desktop() : 782,
        )
    );

    wp_localize_script(
        'hcd-blocks-js',
        'hcdFlipbookDefaults',
        array(
            'width' => function_exists('hcd_get_default_flipbook_width') ? hcd_get_default_flipbook_width() : '100%',
            'height' => function_exists('hcd_get_default_flipbook_height') ? hcd_get_default_flipbook_height() : '600',
        )
    );

    wp_localize_script(
        'hcd-blocks-js',
        'hcdFlipbookAjax',
        array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hcd_get_flipbooks'),
        )
    );

    // Register Block
    register_block_type('heyzine/flipbook-embed', array(
        'editor_script' => 'hcd-blocks-js',
        'render_callback' => 'hcd_render_flipbook_block',
        'attributes' => array(
            'flipbookId' => array(
                'type' => 'string',
                'default' => '',
            ),
            'showNewest' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'width' => array(
                'type' => 'string',
                'default' => ''
            ),
            'height' => array(
                'type' => 'string',
                'default' => ''
            ),
        ),
    ));

    // Register Block for flipbook list
    register_block_type('heyzine/flipbook-list', array(
        'editor_script' => 'hcd-blocks-js',
        'render_callback' => 'hcd_render_flipbook_list_block',
        'attributes' => array(
            'start' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'count' => array(
                'type' => 'number',
                'default' => 10,
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'desc',
            ),
            'columns_mobile' => array(
                'type' => 'number',
                'default' => 2,
            ),
            'columns_tablet' => array(
                'type' => 'number',
                'default' => 3,
            ),
            'columns_desktop' => array(
                'type' => 'number',
                'default' => 5,
            ),
            'show_titles' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'open_in_new_window' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'open_on_site_embed' => array(
                'type' => 'boolean',
                'default' => true,
            ),
        ),
    ));
}
add_action('init', 'hcd_register_blocks');

function hcd_render_flipbook_block($attributes) {
    $flipbookId = isset($attributes['flipbookId']) ? $attributes['flipbookId'] : null;
    $showNewest = isset($attributes['showNewest']) ? $attributes['showNewest'] : false;
    $width = isset($attributes['width']) && $attributes['width'] ? $attributes['width'] : null;
    $height = isset($attributes['height']) && $attributes['height'] ? $attributes['height'] : null;

    return hcd_render_flipbook_embed($flipbookId, $showNewest, $width, $height);
}

function hcd_render_flipbook_list_block($attributes) {
    
    $atts = array(
        'start' => isset($attributes['start']) ? intval($attributes['start']) : 0,
        'count' => isset($attributes['count']) ? intval($attributes['count']) : 10,
        'order' => isset($attributes['order']) ? $attributes['order'] : 'desc',
        'columns_mobile' => isset($attributes['columns_mobile']) ? intval($attributes['columns_mobile']) : 2,
        'columns_tablet' => isset($attributes['columns_tablet']) ? intval($attributes['columns_tablet']) : 3,
        'columns_desktop' => isset($attributes['columns_desktop']) ? intval($attributes['columns_desktop']) : 5,
        'show_titles' => array_key_exists('show_titles', $attributes) ? (bool)$attributes['show_titles'] : false,
        'open_in_new_window' => array_key_exists('open_in_new_window', $attributes) ? (bool)$attributes['open_in_new_window'] : true,
        'open_on_site_embed' => array_key_exists('open_on_site_embed', $attributes) ? (bool)$attributes['open_on_site_embed'] : true,
        
    );

    return hcd_flipbook_list($atts);
}

add_action('wp_ajax_hcd_get_flipbooks', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Keine Berechtigung.'), 403);
    }
    check_ajax_referer('hcd_get_flipbooks', 'nonce');

    $flipbooks = hcd_call_api('flipbook-list');

    if (isset($flipbooks['success']) && $flipbooks['success'] === false) {
        wp_send_json(array(
            'success' => false,
            'code' => isset($flipbooks['code']) ? $flipbooks['code'] : 500,
            'msg'   => isset($flipbooks['msg']) ? $flipbooks['msg'] : 'Unknown API error',
        ));
    }

    if (empty($flipbooks) || !is_array($flipbooks)) {
        wp_send_json(array(
            'success' => false,
            'code' => 404,
            'msg'   => 'No flipbooks found',
        ));
    }

    $result = array_map(function($fb) {
        $thumbnail = isset($fb['links']['thumbnail']) ? esc_url_raw($fb['links']['thumbnail'], array('https')) : '';
        return [
            'id' => $fb['id'],
            'date' => $fb['date'],
            'title' => $fb['title'],
            'embed' => isset($fb['links']['base']) ? hcd_get_safe_embed_url($fb['links']['base']) : '',
            'thumbnail' => $thumbnail,
            'links' => array('thumbnail' => $thumbnail),
        ];
    }, $flipbooks);

    wp_send_json($result);
});
