<?php

if (!defined('ABSPATH')) {
  exit;
}

class WordpressController
{
  private $apiUrl;
  private $deletingUser = false;

  public function __construct()
  {
    $this->apiUrl = get_option('wpapi_api_url');
  }

  public function createWordpressUser($user_id)
  {
    try {
      $user_info = get_userdata($user_id);

      $data = [
        'name' => $user_info->display_name,
        'email' => $user_info->user_email,
        'username' => $user_info->user_login,
        'web' => $user_info->user_url,
        'password' => $_POST['pass1'],
        'user_login' => $user_info->user_login,
      ];

      foreach ($data as $key => $value) {
        if (empty($value)) {
          throw new Exception("El campo {$key} esta vacio o no se ha recibido", 400);
        }
      }

      $response_laravel = wp_remote_post($this->apiUrl . 'newIspUserWordpress', [
        'method' => 'POST',
        'body' => json_encode($data),
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'timeout' => 45
      ]);

      if (is_wp_error($response_laravel)) {
        throw new Exception('Error al enviar el usuario a la API: ' . $response_laravel->get_error_message(), 400);
      }

      $body = wp_remote_retrieve_body($response_laravel);
      $responseData = json_decode($body, true);

      if (isset($responseData['error']) && $responseData['error']) {
        throw new Exception('Error en la API: ' . $responseData['error'], 500);
      }

      $successMessage = isset($responseData[1]) ? $responseData[1] : 'Usuario registrado correctamente en la API.';

      wp_redirect(admin_url('users.php?message=success&text=' . urlencode($successMessage)));
      exit;
    } catch (Exception $e) {
      error_log($e->getMessage());
      wp_die($e->getMessage(), 'Api Error', ['response' => $e->getCode()]);
      exit;
    }
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
      'timeout' => 45
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

    $successMessage = isset($responseData[1]) ? $responseData[1] : 'Usuario actualizado correctamente en la API.';

    wp_redirect(admin_url('users.php?message=' . urlencode($successMessage)));
    exit;
  }

  public function deleteWordpressUser($user_id)
  {
    if ($this->deletingUser) {
      return;
    }

    $this->deletingUser = true;

    try {
      $user_info = get_userdata($user_id);

      if (!$user_info) {
        throw new Exception("El usuario no existe", 404);
      }

      $data = [
        'email' => $user_info->user_email,
      ];

      if (empty($data['email'])) {
        throw new Exception("El campo email esta vacio o no se ha recibido", 400);
      }

      $response_laravel = wp_remote_post($this->apiUrl . 'deleteIspUserWordpress', [
        'method' => 'DELETE',
        'body' => json_encode($data),
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'timeout' => 45
      ]);

      if (is_wp_error($response_laravel)) {
        throw new Exception('Error al enviar el usuario a la API: ' . $response_laravel->get_error_message(), 400);
      }

      $body = wp_remote_retrieve_body($response_laravel);
      $responseData = json_decode($body, true);

      error_log("Borrando usuario: " . $user_id);

      if ($response_laravel['response']['code'] !== 200) {
        throw new Exception('Error en la API: ' . $responseData['message'], 500);
      }

      wp_delete_user($user_id);

      $successMessage = isset($responseData[1]) ? $responseData[1] : 'Usuario Eliminado correctamente en la API.';

      wp_redirect(admin_url('users.php?message=success&text=' . urlencode($successMessage)));
      exit;
    } catch (Exception $e) {
      error_log($e->getMessage());
      wp_die($e->getMessage(), 'Api Error', ['response' => $e->getCode()]);
      
    } finally {
      $this->deletingUser = false;
      
    }
  }
}
