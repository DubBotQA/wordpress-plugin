<?php
/*
Plugin Name: DubBot
Description: See DubBot results in WordPress
Version: 1.1.0
Author: DubBot
Author URI: https://dubbot.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;

const DUBBOT_API_URL = 'https://api.dubbot.com';
const DUBBOT_EDITOR_SELECTOR = '#editor iframe';

/**
 * Retrieve a DubBot setting from the appropriate WordPress options store.
 *
 * In multisite, an explicit site setting takes precedence over the network
 * default when site overrides are permitted.
 *
 * @param string $option        Option name.
 * @param mixed  $default_value Value to return when the option is not set.
 * @return mixed
 */
function dubbot_get_setting($option, $default_value = false) {
  if (is_multisite()) {
    if (dubbot_site_overrides_allowed()) {
      $site_value = dubbot_get_site_override($option);
      if (null !== $site_value) {
        return $site_value;
      }
    }

    return dubbot_get_network_setting($option, $default_value);
  }

  return get_option($option, $default_value);
}

/**
 * Retrieve a DubBot setting that is shared by every site in a network.
 *
 * @param string $option        Option name.
 * @param mixed  $default_value Value to return when the option is not set.
 * @return mixed
 */
function dubbot_get_network_setting($option, $default_value = false) {
  if (is_multisite()) {
    return get_site_option($option, $default_value);
  }

  return get_option($option, $default_value);
}

function dubbot_site_overrides_allowed() {
  if (!is_multisite()) {
    return true;
  }

  return 1 === (int) dubbot_get_network_setting('dubbot_allow_site_overrides', 1);
}

/**
 * Retrieve a site's own value for a DubBot setting in multisite.
 *
 * A missing or empty site value means the site uses the network default.
 *
 * @param string $option Option name.
 * @return mixed|null The site value, or null when the site does not override it.
 */
function dubbot_get_site_override($option) {
  $site_value = get_option($option, null);

  return (null === $site_value || '' === $site_value) ? null : $site_value;
}

function dubbot_has_site_setting($option) {
  return is_multisite() && null !== dubbot_get_site_override($option);
}

/**
 * Remove per-site defaults written by earlier plugin versions.
 *
 * Earlier versions called add_option() for the API URL and editor selector
 * on every admin page load, so most sites have stored copies of the defaults.
 * Left in place, they would hide the network settings on every site.
 */
function dubbot_maybe_clean_legacy_site_defaults() {
  if (!is_multisite() || get_option('dubbot_legacy_defaults_cleaned')) {
    return;
  }

  $legacy_defaults = array(
    'dubbot_api_url' => DUBBOT_API_URL,
    'dubbot_editor_selector' => DUBBOT_EDITOR_SELECTOR,
  );

  foreach ($legacy_defaults as $option => $legacy_default) {
    if ($legacy_default === get_option($option, null)) {
      delete_option($option);
    }
  }

  update_option('dubbot_legacy_defaults_cleaned', 1);
}
add_action('init', 'dubbot_maybe_clean_legacy_site_defaults');

/**
 * Update a DubBot setting in the appropriate WordPress options store.
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 * @return bool
 */
function dubbot_update_setting($option, $value) {
  if (is_multisite()) {
    return update_site_option($option, $value);
  }

  return update_option($option, $value);
}

function dubbot_get_api_url() {
  $api_url = dubbot_get_setting('dubbot_api_url', DUBBOT_API_URL);

  return empty($api_url) ? DUBBOT_API_URL : $api_url;
}

function dubbot_get_network_api_url() {
  $api_url = dubbot_get_network_setting('dubbot_api_url', DUBBOT_API_URL);

  return empty($api_url) ? DUBBOT_API_URL : $api_url;
}

function dubbot_get_editor_selector() {
  $editor_selector = dubbot_get_setting('dubbot_editor_selector', DUBBOT_EDITOR_SELECTOR);

  return empty($editor_selector) ? DUBBOT_EDITOR_SELECTOR : $editor_selector;
}

function dubbot_get_network_editor_selector() {
  $editor_selector = dubbot_get_network_setting('dubbot_editor_selector', DUBBOT_EDITOR_SELECTOR);

  return empty($editor_selector) ? DUBBOT_EDITOR_SELECTOR : $editor_selector;
}

/* The Admin configuration stuff */
function dubbot_register_settings() {
  if (is_multisite() && !dubbot_site_overrides_allowed()) {
    return;
  }

  $setting_defaults = array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field');

  // embed key
  register_setting('dubbot_settings_group', 'dubbot_embed_key', $setting_defaults);

  // API url
  $api_url_defaults = array('type' => 'string', 'sanitize_callback' => 'esc_url_raw');
  register_setting('dubbot_settings_group', 'dubbot_api_url', $api_url_defaults);

  // editor selector
  register_setting('dubbot_settings_group', 'dubbot_editor_selector', $setting_defaults);
}
add_action('admin_init', 'dubbot_register_settings');

function dubbot_menu() {
  if (is_network_admin() || (is_multisite() && !dubbot_site_overrides_allowed())) {
    return;
  }

  add_options_page(
    'DubBot Settings',     // Page title
    'DubBot',              // Menu title
    'manage_options',      // Capability
    'dubbot-settings',     // Menu slug
    'dubbot_settings_page' // Function to display the settings page
  );
}
add_action('admin_menu', 'dubbot_menu');

/**
 * Output the DubBot settings fields.
 *
 * @param string $embed_key       Embed key field value.
 * @param string $api_url         API URL field value.
 * @param string $editor_selector Editor selector field value.
 * @param array  $placeholders    Optional placeholders keyed by option name.
 */
function dubbot_settings_fields($embed_key, $api_url, $editor_selector, $placeholders = array()) {
  $placeholders = wp_parse_args($placeholders, array(
    'dubbot_embed_key' => '',
    'dubbot_api_url' => '',
    'dubbot_editor_selector' => '',
  ));
  $is_advanced = isset($_GET['advanced']) && 'y' === sanitize_key(wp_unslash($_GET['advanced'])); // phpcs:ignore WordPress provides this query parameter.
  ?>
  <table class="form-table">
    <tr valign="top">
      <th scope="row">Embed Key</th>
      <td>
        <input type="text" name="dubbot_embed_key" value="<?php echo esc_attr($embed_key); ?>" placeholder="<?php echo esc_attr($placeholders['dubbot_embed_key']); ?>" />
        <p><em>Contact <a href="mailto:help@dubbot.com">DubBot Support</a> to request an embed key.</em></p>
      </td>
    </tr>

    <tr valign="top">
      <th scope="row">Editor Selector</th>
      <td>
        <input type="text" name="dubbot_editor_selector" value="<?php echo esc_attr($editor_selector); ?>" placeholder="<?php echo esc_attr($placeholders['dubbot_editor_selector']); ?>" />
        <p><em>The CSS selector for the element containing the editor's content</em></p>
      </td>
    </tr>

    <tr valign="top" style="display: <?php echo $is_advanced ? 'table-row' : 'none'; ?>">
      <th scope="row">DubBot API URL</th>
      <td>
        <input type="text" name="dubbot_api_url" value="<?php echo esc_attr($api_url); ?>" placeholder="<?php echo esc_attr($placeholders['dubbot_api_url']); ?>" />
      </td>
    </tr>
  </table>
  <?php
}

function dubbot_settings_page() {
  if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to manage DubBot settings.');
  }

  $placeholders = array();
  if (is_multisite()) {
    // Show only the site's own values; blank fields fall back to the network defaults.
    $embed_key = (string) dubbot_get_site_override('dubbot_embed_key');
    $api_url = (string) dubbot_get_site_override('dubbot_api_url');
    $editor_selector = (string) dubbot_get_site_override('dubbot_editor_selector');
    $placeholders = array(
      'dubbot_embed_key' => dubbot_get_network_setting('dubbot_embed_key', ''),
      'dubbot_api_url' => dubbot_get_network_api_url(),
      'dubbot_editor_selector' => dubbot_get_network_editor_selector(),
    );
    $has_site_override = dubbot_has_site_setting('dubbot_embed_key') || dubbot_has_site_setting('dubbot_api_url') || dubbot_has_site_setting('dubbot_editor_selector');
  } else {
    $embed_key = dubbot_get_setting('dubbot_embed_key', '');
    $api_url = dubbot_get_api_url();
    $editor_selector = dubbot_get_editor_selector();
  }
  ?>
  <div class="wrap">
    <h1>DubBot Settings</h1>
    <?php if (is_multisite()) : ?>
      <p>
        <?php echo $has_site_override ? 'This site is using some site-specific DubBot settings.' : 'This site is using the DubBot network defaults.'; ?>
        Leave a field blank to use the network default.
      </p>
    <?php endif; ?>
    <form method="post" action="options.php">
      <?php
      settings_fields('dubbot_settings_group');
      dubbot_settings_fields($embed_key, $api_url, $editor_selector, $placeholders);
      submit_button();
      ?>
    </form>
  </div>
  <?php
}

function dubbot_network_menu() {
  add_submenu_page(
    'settings.php',
    'DubBot Settings',
    'DubBot',
    'manage_network_options',
    'dubbot-settings',
    'dubbot_network_settings_page'
  );
}
add_action('network_admin_menu', 'dubbot_network_menu');

function dubbot_network_settings_page() {
  if (!current_user_can('manage_network_options')) {
    wp_die('You do not have permission to manage DubBot network settings.');
  }

  $embed_key = dubbot_get_network_setting('dubbot_embed_key', '');
  $api_url = dubbot_get_network_api_url();
  $editor_selector = dubbot_get_network_editor_selector();
  $site_overrides_allowed = dubbot_site_overrides_allowed();
  ?>
  <div class="wrap">
    <h1>DubBot Network Settings</h1>
    <?php if (isset($_GET['updated'])) : // phpcs:ignore WordPress redirect query parameter. ?>
      <div class="notice notice-success is-dismissible"><p>DubBot settings saved.</p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(network_admin_url('admin-post.php')); ?>">
      <?php
      wp_nonce_field('dubbot_save_network_settings');
      ?>
      <input type="hidden" name="action" value="dubbot_save_network_settings" />
      <?php
      dubbot_settings_fields($embed_key, $api_url, $editor_selector);
      ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Site Overrides</th>
          <td>
            <label>
              <input type="checkbox" name="dubbot_allow_site_overrides" value="1" <?php checked($site_overrides_allowed); ?> />
              Allow site administrators to override these network settings.
            </label>
            <p><em>Enabled by default. Existing site-specific DubBot settings are preserved if this is disabled, but they will not be used.</em></p>
          </td>
        </tr>
      </table>
      <?php
      submit_button();
      ?>
    </form>
  </div>
  <?php
}

function dubbot_save_network_settings() {
  if (!is_multisite() || !current_user_can('manage_network_options')) {
    wp_die('You do not have permission to manage DubBot network settings.');
  }

  check_admin_referer('dubbot_save_network_settings');

  $embed_key = isset($_POST['dubbot_embed_key']) ? sanitize_text_field(wp_unslash($_POST['dubbot_embed_key'])) : '';
  $api_url = isset($_POST['dubbot_api_url']) ? esc_url_raw(wp_unslash($_POST['dubbot_api_url'])) : DUBBOT_API_URL;
  $editor_selector = isset($_POST['dubbot_editor_selector']) ? sanitize_text_field(wp_unslash($_POST['dubbot_editor_selector'])) : DUBBOT_EDITOR_SELECTOR;

  dubbot_update_setting('dubbot_embed_key', $embed_key);
  dubbot_update_setting('dubbot_api_url', $api_url);
  dubbot_update_setting('dubbot_editor_selector', $editor_selector);
  dubbot_update_setting('dubbot_allow_site_overrides', isset($_POST['dubbot_allow_site_overrides']) ? 1 : 0);

  wp_safe_redirect(add_query_arg(
    array(
      'page' => 'dubbot-settings',
      'updated' => 'true',
    ),
    network_admin_url('settings.php')
  ));
  exit;
}
add_action('admin_post_dubbot_save_network_settings', 'dubbot_save_network_settings');

function dubbot_iframe_url($post_id) {
  return dubbot_url($post_id, "iframe");
}

function dubbot_json_url($post_id) {
  return dubbot_url($post_id, "json");
}

function dubbot_url($post_id, $type) {
  // Retrieve the embed_key from the plugin settings
  $embed_key = dubbot_get_setting('dubbot_embed_key');
  $page_url = apply_filters('dubbot_page_url', get_permalink($post_id), $post_id);
  $dubbot_host = dubbot_get_api_url();

  // Build the iframe URL with the embed key
  if (!empty($embed_key)) {
    $url = $dubbot_host . '/embeds/'. esc_attr($embed_key);
    if ($type == "json") {
      $url .= '.json';
    }
    if (empty($page_url)) {
      return $url;
    }
    return add_query_arg('url', rawurlencode($page_url), $url);
  } else {
    return null;
  }
}

function dubbot_page_metadata($post_id) {
  try {
    $url = dubbot_json_url($post_id);
    $request = wp_remote_get($url);
    $response = wp_remote_retrieve_body($request);
    if ($response === false) {
      throw new Exception($url . " failed");
    }

    $data = json_decode($response, true);
    return $data;
  } catch (Exception $e) {
    return ['total_issues_count' => 'N/A'];
  }
}
function dubbot_enqueue_iframe_plugin_scripts($hook) {
  if(!is_user_logged_in()){
    return;
  }

  if (is_admin() && 'post.php' !== $hook) {
    return;
  }

  $embed_key = dubbot_get_setting('dubbot_embed_key');
  if(empty($embed_key)) {
    return;
  }

  $api_url = dubbot_get_api_url();
  $editor_selector = '.wp-site-blocks';

  if ($hook == 'post.php') {
    $editor_selector = dubbot_get_editor_selector();
  }


  // Enqueue jQuery (if not already included)
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-dialog');
  wp_enqueue_script('jquery-ui-resizable');
  wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'css/jquery-ui.css', null, '1.0');

  // Enqueue custom JavaScript for the modal
  wp_enqueue_script('dubbot-highlight', $api_url . '/embeds/highlight.js', null, '1.0', true);
  wp_enqueue_script('dubbot-iframe', plugin_dir_url(__FILE__) . 'js/dubbot-iframe.js', array('jquery'), '1.0', true);

  // Enqueue custom CSS for the modal
  wp_enqueue_style('dubbot-iframe', plugin_dir_url(__FILE__) . 'css/dubbot-iframe.css', null, '1.0');

  $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0; // phpcs:ignore
  $iframe_url = dubbot_iframe_url($post_id);
  $metadata = dubbot_page_metadata($post_id);
  $localize_data = array(
    'iframeURL' => $iframe_url,
    'post_id' => $post_id,
    'embed_key' => $embed_key,
    'api_url' => $api_url,
    'editor_selector' => $editor_selector,
    'metadata' => $metadata,
  );

  // Pass PHP variables to JavaScript
  wp_localize_script('dubbot-iframe', 'dubbot', $localize_data);
}
add_action('admin_enqueue_scripts', 'dubbot_enqueue_iframe_plugin_scripts');
add_action('wp_enqueue_scripts', 'dubbot_enqueue_iframe_plugin_scripts');

function dubbot_add_settings_link($links) {
  if (is_multisite()) {
    if (is_network_admin()) {
      $settings_url = network_admin_url('settings.php?page=dubbot-settings');
    } elseif (dubbot_site_overrides_allowed()) {
      $settings_url = admin_url('options-general.php?page=dubbot-settings');
    } else {
      return $links;
    }
  } else {
    $settings_url = admin_url('options-general.php?page=dubbot-settings');
  }

  $settings_link = '<a href="' . esc_url($settings_url) . '">Settings</a>';
  array_unshift($links, $settings_link);

  return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dubbot_add_settings_link');
add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'dubbot_add_settings_link');

?>
