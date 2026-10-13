<?php

function hcd_call_api($endpoint, $params = array()) {
    $api_key = get_option('hcd_api_key');

    if (empty($api_key)) {
        return array(
            'success' => false,
            'code' => 401,
            'msg' => 'API key not set'
        );
    }

    $base_url = 'https://heyzine.com/api1/';
    $url = $base_url . ltrim($endpoint, '/');

    if (!empty($params)) {
        $url = add_query_arg($params, $url);
    }

    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept' => 'application/json',
        ),
        'timeout' => 15,
    );

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'code' => 500,
            'msg' => $response->get_error_message(),
        );
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Prüfen, ob die Antwort ein Array (erfolgreich) oder Objekt mit success=false (Fehler) ist
    if (!is_array($data)) {
        return array(
            'success' => false,
            'code' => isset($data['code']) ? $data['code'] : 500,
            'msg' => isset($data['msg']) ? $data['msg'] : 'Unknown error',
        );
    }

    return $data;
}
