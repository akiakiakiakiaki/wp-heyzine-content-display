<?php
// Register settings for the plugin
function hcd_register_settings() {
    register_setting('hcd_options_group', 'hcd_client_id');
    register_setting('hcd_options_group', 'hcd_api_key');
    register_setting('hcd_options_group', 'hcd_flipbook_width', array(
        'type' => 'string',
        'default' => '100%',
        'sanitize_callback' => 'hcd_sanitize_flipbook_width',
    ));
    register_setting('hcd_options_group', 'hcd_flipbook_height', array(
        'type' => 'string',
        'default' => '600',
        'sanitize_callback' => 'hcd_sanitize_flipbook_height',
    ));
    register_setting('hcd_options_group', 'hcd_breakpoint_mobile_tablet', array(
        'type' => 'integer',
        'default' => 600,
        'sanitize_callback' => 'hcd_sanitize_breakpoint_mobile_tablet',
    ));
    register_setting('hcd_options_group', 'hcd_breakpoint_tablet_desktop', array(
        'type' => 'integer',
        'default' => 782,
        'sanitize_callback' => 'hcd_sanitize_breakpoint_tablet_desktop',
    ));
    register_setting('hcd_options_group', 'hcd_pretty_urls', array(
        'type' => 'string',
        'default' => '1',
        'sanitize_callback' => 'hcd_sanitize_pretty_urls',
    ));
    register_setting('hcd_options_group', 'hcd_rewrite_slug', array(
        'type' => 'string',
        'default' => 'magazin-ansicht',
        'sanitize_callback' => 'hcd_sanitize_rewrite_slug',
    ));
}

function hcd_sanitize_pretty_urls($value) {
    return empty($value) ? '0' : '1';
}

function hcd_sanitize_dimension($value, $fallback) {
    $value = strtolower(trim((string) $value));
    if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)(px|%|vh|vw|rem|em)?$/', $value, $matches)) {
        return $fallback;
    }

    $amount = (float) $matches[1];
    $unit = isset($matches[2]) ? $matches[2] : '';
    $maximum = in_array($unit, array('%', 'vh', 'vw'), true) ? 100 : (in_array($unit, array('rem', 'em'), true) ? 1000 : 10000);
    if ($amount <= 0 || $amount > $maximum) {
        return $fallback;
    }

    return $matches[1] . $unit;
}

function hcd_sanitize_flipbook_width($value) {
    return hcd_sanitize_dimension($value, '100%');
}

function hcd_sanitize_flipbook_height($value) {
    return hcd_sanitize_dimension($value, '600');
}

function hcd_sanitize_breakpoint($value, $fallback) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return ($value !== false && $value >= 1 && $value <= 10000) ? $value : $fallback;
}

function hcd_sanitize_breakpoint_mobile_tablet($value) {
    return hcd_sanitize_breakpoint($value, 600);
}

function hcd_sanitize_breakpoint_tablet_desktop($value) {
    return hcd_sanitize_breakpoint($value, 782);
}

// Add options page to the admin menu
function hcd_register_options_page() {
    add_options_page(
        'Heyzine Content Display Settings',
        'Heyzine Content Display',
        'manage_options',
        'heyzine-content-display',
        'hcd_options_page'
    );
}

// Render the options page
function hcd_options_page() {
    ?>
    <div class="wrap">
        <h1>Heyzine Content Display Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('hcd_options_group');
            do_settings_sections('hcd_options_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Client ID</th>
                    <td><input type="text" name="hcd_client_id" value="<?php echo esc_attr(get_option('hcd_client_id')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="hcd_api_key" value="<?php echo esc_attr(get_option('hcd_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Standard Flipbook Embed Breite</th>
                    <td><input type="text" name="hcd_flipbook_width" value="<?php echo esc_attr(hcd_get_default_flipbook_width()); ?>" /> <p class="description">Zulässig sind Maße wie <code>100%</code>, <code>800px</code> oder <code>90vh</code>.</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Standard Flipbook Embed Höhe</th>
                    <td><input type="text" name="hcd_flipbook_height" value="<?php echo esc_attr(hcd_get_default_flipbook_height()); ?>" /> <p class="description">Zulässig sind Maße wie <code>600</code>, <code>90%</code> oder <code>90vh</code>.</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Breakpoint Mobile zu Tablet</th>
                    <td><input type="number" min="1" max="10000" step="1" name="hcd_breakpoint_mobile_tablet" value="<?php echo esc_attr(hcd_get_breakpoint_mobile_tablet()); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Breakpoint Tablet zu Desktop</th>
                    <td><input type="number" min="1" max="10000" step="1" name="hcd_breakpoint_tablet_desktop" value="<?php echo esc_attr(hcd_get_breakpoint_tablet_desktop()); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Magazin-URLs</th>
                    <td>
                        <input type="hidden" name="hcd_pretty_urls" value="0" />
                        <label>
                            <input type="checkbox" name="hcd_pretty_urls" value="1" <?php checked(hcd_is_pretty_urls_enabled()); ?> />
                            Schöne URLs mit Rewrite verwenden
                        </label>
                        <p class="description">Benötigt mod_rewrite und funktionierende WordPress-Permalinks auf dem Server. Ist die Option aus, verwendet das Plugin die Query-URL mit <code>?flipbook_id=…</code>.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="hcd_rewrite_slug">URL-Pfad</label></th>
                    <td>
                        <input type="text" id="hcd_rewrite_slug" name="hcd_rewrite_slug" value="<?php echo esc_attr(hcd_get_rewrite_slug()); ?>" class="regular-text" />
                        <p class="description">Aktueller URL-Pfad: <code>/<span id="hcd-rewrite-slug-preview"><?php echo esc_html(hcd_get_rewrite_slug()); ?></span>/flipbook-id</code>.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <hr>
        <h2>API Test</h2>
        <button id="hcd-api-test-btn" class="button button-secondary">API Test</button>
        <span id="hcd-api-test-result" style="margin-left:1em;"></span>
    </div>
    <script type="text/javascript">
    (function($){
        var hcdTestApiNonce = <?php echo wp_json_encode(wp_create_nonce('hcd_test_api')); ?>;

        $('#hcd_rewrite_slug').on('input', function(){
            var slug = $(this).val().toLowerCase();
            if (slug.normalize) {
                slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }
            slug = slug.replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
            $('#hcd-rewrite-slug-preview').text(slug || 'magazin-ansicht');
        });

        $('#hcd-api-test-btn').on('click', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $result = $('#hcd-api-test-result');
            $result.text('Teste Verbindung...');
            $btn.prop('disabled', true);
            $.post(ajaxurl, { action: 'hcd_test_api', nonce: hcdTestApiNonce }, function(response){
                if(response && response.success){
                    $result.empty().append(
                        $('<span>').css('color', 'green').text('✓ API Verbindung erfolgreich!'),
                        $('<pre>').text(response.data.data || '')
                    );
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Unbekannter Fehler.';
                    $result.empty().append(
                        $('<span>').css('color', 'red').text('✗ API Fehler: ' + msg)
                    );
                }
                $btn.prop('disabled', false);
            }).fail(function(xhr){
                $result.empty().append(
                    $('<span>').css('color', 'red').text('✗ API Fehler: Netzwerkfehler')
                );
                $btn.prop('disabled', false);
            });
        });
    })(jQuery);
    </script>
    <?php
}

// Functions to get default flipbook dimensions
function hcd_get_default_flipbook_width() {
    return hcd_sanitize_flipbook_width(get_option('hcd_flipbook_width', '100%'));
}

function hcd_get_default_flipbook_height() {
    return hcd_sanitize_flipbook_height(get_option('hcd_flipbook_height', '600'));
}

function hcd_get_breakpoint_mobile_tablet() {
    return hcd_sanitize_breakpoint_mobile_tablet(get_option('hcd_breakpoint_mobile_tablet', 600));
}

function hcd_get_breakpoint_tablet_desktop() {
    return hcd_sanitize_breakpoint_tablet_desktop(get_option('hcd_breakpoint_tablet_desktop', 782));
}

add_action('admin_init', 'hcd_register_settings');
add_action('admin_menu', 'hcd_register_options_page');

// AJAX handler for API Test
add_action('wp_ajax_hcd_test_api', 'hcd_ajax_test_api');
function hcd_ajax_test_api() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Keine Berechtigung.'), 403);
    }
    check_ajax_referer('hcd_test_api', 'nonce');

    $client_id = get_option('hcd_client_id');
    $api_key = get_option('hcd_api_key');
    if (empty($client_id) || empty($api_key)) {
        wp_send_json_error(array('message' => 'Bitte Client ID und API Key speichern.'));
    }
    // Prepare API call to Heyzine using hcd_call_api function
    $response = hcd_call_api('flipbook-list', ['limit' => 1]);
    if ($response === false) {
        wp_send_json_error(array('message' => 'API Verbindung fehlgeschlagen.'));
    }
    wp_send_json_success(array(
        'message' => 'API Verbindung erfolgreich!',
        'data' => json_encode($response, JSON_PRETTY_PRINT)
    ));
}
