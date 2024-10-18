<?php

if (!defined('ABSPATH')) {
  exit;
}

class WordpressController
{
  private $apiUrl = 'http://localhost:8000/api/';

  public function createWordpressUser($user_id)
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

    $response_laravel = wp_remote_post($this->apiUrl . 'newIspUserWordpress', [
      'method' => 'POST',
      'body' => json_encode($data),
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
    ]);

    if (is_wp_error($response_laravel)) {
      error_log('Error al enviar el usuario a la API: ' . $response_laravel->get_error_message());
      wp_die('Error al enviar el usuario a la API: ' . $response_laravel->get_error_message(), 'Api Error', ['response' => 400]);
    }

    $body = wp_remote_retrieve_body($response_laravel);
    $responseData = json_decode($body, true);

    if (isset($responseData['error']) && $responseData['error']) {
      wp_die('Error en la API: ' . $responseData['error'], 'API Error', ['response' => 500]);
    }

    wp_redirect(admin_url('users.php?message=' . urlencode('Usuario registrado correctamente.')));
    exit;
  }

  public function updateWordpressUser($user_id, $old_data)
  {
    $user = get_userdata($user_id);

    $data = [
      'name' => $user->display_name,
      'email' => $user->user_email,
      'username' => $user->user_login,
      'web' => $user->user_url,
      'password' => $_POST['pass1'],
      'user_login' => $user->user_login,
    ];

    $response_laravel = wp_remote_post($this->apiUrl . 'updateIspUserWordpress', [
      'method' => 'POST',
      'body' => json_encode($data),
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
    ]);

    if (is_wp_error($response_laravel)) {
      error_log('Error al enviar el usuario a la API: '. $response_laravel->get_error_message());
      wp_die('Error al enviar el usuario a la API: '. $response_laravel->get_error_message(), 'Api Error', ['response' => 400]);
    }

    $body = wp_remote_retrieve_body($response_laravel);
    $responseData = json_decode($body, true);

    if (isset($responseData['error']) && $responseData['error']) {
      wp_die('Error en la API: '. $responseData['error'], 'API Error', ['response' => 500]);
    }

    wp_redirect(admin_url('users.php?message='. urlencode('Usuario actualizado correctamente.')));
    exit;

  }
}
