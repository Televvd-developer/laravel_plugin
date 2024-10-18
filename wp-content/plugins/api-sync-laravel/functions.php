<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '/Controllers/WordpressController.php';

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
