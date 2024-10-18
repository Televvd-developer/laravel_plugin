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
  register_setting('wpapi_settings_group', 'wpapi_api_url');

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
