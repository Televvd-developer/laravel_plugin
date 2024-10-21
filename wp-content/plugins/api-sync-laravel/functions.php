<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '/Controllers/WordpressController.php';


/**
 * Registrar un nuevo usuario sincronizando los datos a nuplin.tv
 */
add_action('user_register', function ($user_id) {
  $controller = new WordpressController();
  $response = $controller->createWordpressUser($user_id);

  if ($response && isset($response['message'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($response['message']) . '</p></div>';
    });
  } elseif ($response && isset($response['error'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($response['error']) . '</p></div>';
    });
  }
});

/**
 * Actualizar los datos en Wordpress y en nuplin.tv
 */
add_action('profile_update', function ($user_id, $oldData) {

  $controller = new WordpressController();
  $response = $controller->updateWordpressUser($user_id, $oldData);

  if ($response && isset($response['message'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($response['message']) . '</p></div>';
    });
  } elseif ($response && isset($response['error'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($response['error']) . '</p></div>';
    });
  }
}, 10, 2);

/**
 * Eliminar usuario de Wordpress y de la base de datos
 */

add_action('delete_user', function ($user_id) {
  static $isDeleting = false;

  if($isDeleting) {
    return;
  }

  $isDeleting = true;

  $controller = new WordpressController();
  $response = $controller->deleteWordpressUser($user_id);

  if ($response && isset($response['message'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($response['message']) . '</p></div>';
    });
  } elseif ($response && isset($response['error'])) {
    add_action('admin_notices', function () use ($response) {
      echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($response['error']) . '</p></div>';
    });
  }

  $isDeleting = false;
}, 10, 1);


function wpapi_add_admin_menu()
{
  add_menu_page(
    'Configuracion de la API',
    'Api Settings',
    'manage_options',
    'wpapi-settings',
    'wpapi_settings_page',
  );
}

add_action('admin_menu', 'wpapi_add_admin_menu');

function wpapi_settings_page()
{
?>
  <div class="wrap">
    <h1>Configuración de API</h1>
    <?php settings_errors() ?>
    <form method="post" action="options.php">
      <?php
      // Muestra los campos necesarios para guardar la opción
      settings_fields('wpapi_settings_group');
      do_settings_sections('wpapi-settings');
      submit_button();
      ?>
    </form>
  </div>
<?php
}

function wpapi_register_settings()
{
  register_setting('wpapi_settings_group', 'wpapi_api_url', 'wpapi_test_api_connection');

  add_settings_section(
    'wpapi_settings_section',
    'Configuracion de la URL de la API',
    null,
    'wpapi-settings',
  );

  add_settings_field(
    'wpapi_api_url',
    'API URL',
    'wpapi_api_url_callback',
    'wpapi-settings',
    'wpapi_settings_section'
  );
}

add_action('admin_init', 'wpapi_register_settings');

function wpapi_api_url_callback()
{
  $apiUrl = get_option('wpapi_api_url');
  echo '<input type="text" name="wpapi_api_url" value="' . esc_attr($apiUrl) . '" class="regular-text" />';
}

/**
 * Test de conexion de la api
 */

function wpapi_test_api_connection($apiUrl)
{
  $response = wp_remote_get($apiUrl . 'testConnection', [
    'timeout' => 10
  ]);

  if (is_wp_error($response)) {
    add_settings_error(
      'wpapi_api_url',
      'wpapi_api_url_error',
      'Error en la conexion de la API: ' . $response->get_error_message(),
    );
    return $apiUrl;
  }

  $response_code = wp_remote_retrieve_response_code($response);
  $response_body = wp_remote_retrieve_body($response);

  if ($response_code === 200) {
    add_settings_error(
      'wpapi_api_url',
      'wpapi_api_url_success',
      'La conexion de la API ha sido exitosa ' . esc_html($response_body),
      'updated'
    );
  } else {
    add_settings_error(
      'wpapi_api_url',
      'wpapi_api_url_error',
      'Conexion fallida a la API: Response Code: ' . $response_code,
      'error'
    );
  }

  return $apiUrl;
}

/**
 * Mensajes al consumir la api
 */

add_action('admin_notices', 'wpapi_show_user_messages');

function wpapi_show_user_messages()
{
  if (isset($_GET['message']) && isset($_GET['text'])) {
    $message_type = sanitize_text_field($_GET['message']);
    $message = sanitize_text_field($_GET['text']);

    if ($message_type === 'success') {
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    } elseif ($message_type === 'error') {
      echo '<div class="notice notice-error| is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
  }
}
