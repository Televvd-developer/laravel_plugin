<?php

if (!defined('ABSPATH')) {
  exit;
}

function send_user_api($user_id)
{
  $user_info = get_userdata($user_id);

  $data = [
    'name' => $user_info->display_name,
    'email' => $user_info->user_email,
    'username' => $user_info->user_login,
    'web' => $user_info->user_url,
    'password' => $_POST['pass1'],
    'user_login' => $user_info->user_login,
  ];

  $url_laravel = 'http://127.0.0.1:8000/api/newIspUserWordpress';

  $response_laravel = wp_remote_post($url_laravel, [
    'method' => 'POST',
    'body' => json_encode($data),
    'headers' => [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ],
  ]);


  if (is_wp_error($response_laravel)) {
    error_log('Error al enviar el usuario a la API: ' . $response_laravel->get_error_message());
    wp_send_json_error(['message' => 'Error al enviar el usuario a la API'], 500);
  }

  $body = wp_remote_retrieve_body($response_laravel);
  $responseData = json_decode($body, true);

  if (isset($responseData['error']) && $responseData['error']) {
    wp_send_json_error(['message' => $responseData['error']], 400);
  }

  wp_redirect(admin_url('users.php?message=' . urlencode('Usuario registrado correctamente.')));
  exit;
}

add_action('user_register', 'send_user_api');

add_action('profile_update', 'user_update_api', 10, 2);

function user_update_api($user_id, $old_data) {
  $user = get_userdata($user_id);


}