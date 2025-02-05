<?php
/*
Plugin Name: DubBot
Description: See DubBot results in WordPress
Version: 1.0.1
Author: DubBot
Author URI: https://dubbot.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;

const DUBBOT_API_URL = 'https://api.dubbot.com';
const DUBBOT_EDITOR_SELECTOR = '#editor iframe';

/* The Admin configuration stuff */
function dubbot_register_settings() {
  $setting_defaults = array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field');

  // embed key
  register_setting('dubbot_settings_group', 'dubbot_embed_key', $setting_defaults);

  // API url (default: api.dubbot.com)
  add_option('dubbot_api_url', DUBBOT_API_URL);
  register_setting('dubbot_settings_group', 'dubbot_api_url', $setting_defaults);

  // editor selector
  add_option('dubbot_editor_selector', DUBBOT_EDITOR_SELECTOR);
  register_setting('dubbot_settings_group', 'dubbot_editor_selector', $setting_defaults);
}
add_action('admin_init', 'dubbot_register_settings');

function dubbot_menu() {
    add_options_page(
        'DubBot Settings',     // Page title
        'DubBot',              // Menu title
        'manage_options',      // Capability
        'dubbot-settings',     // Menu slug
        'dubbot_settings_page' // Function to display the settings page
    );
}
add_action('admin_menu', 'dubbot_menu');

function dubbot_settings_page() {
  // TODO: Closing php so we can insert HTML with PHP inside it feels gross.
  // Is there some template-based thing we could do?
  $is_advanced = (isset($_GET['advanced']) && $_GET['advanced'] == 'y'); // phpcs:ignore
  ?>
    <div class="wrap">
      <h1>DubBot Settings</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields('dubbot_settings_group');

          // Output setting sections and their fields
          do_settings_sections('dubbot_settings_group');

          // Get the stored dubbot_embed_key value
          $dubbot_embed_key = get_option('dubbot_embed_key');

          // and api url
          $api_url = get_option('dubbot_api_url');
          if(!$api_url):
            $api_url = DUBBOT_API_URL;
          endif;

          // and editor selector
          $editor_selector = get_option('dubbot_editor_selector');
          if(!$editor_selector):
            $editor_selector = DUBBOT_EDITOR_SELECTOR;
          endif;
        ?>

        <table class="form-table">
          <tr valign="top">
            <th scope="row">Embed Key</th>
            <td>
              <input type="text" name="dubbot_embed_key" value="<?php echo esc_attr($dubbot_embed_key); ?>" />
              <p><em>Contact <a href="mailto:help@dubbot.com">DubBot Support</a> to request an embed key.</em></p>
            </td>
          </tr>

          <tr valign="top">
            <th scope="row">Editor Selector</th>
            <td>
              <input type="text" name="dubbot_editor_selector" value="<?php echo esc_attr($editor_selector); ?>" />
              <p><em>The CSS selector for the element containing the editor's content</em></p>
            </td>
          </tr>

          <tr valign="top" style="display: <?php echo $is_advanced ? 'table-row' : 'none' ?>">
            <th scope="row">DubBot API URL</th>
            <td>
              <input type="text" name="dubbot_api_url" value="<?php echo esc_attr($api_url); ?>" />
            </td>
          </tr>

        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
}

function dubbot_iframe_url($post_id) {
  return dubbot_url($post_id, "iframe");
}

function dubbot_json_url($post_id) {
  return dubbot_url($post_id, "json");
}

function dubbot_url($post_id, $type) {
  // Retrieve the embed_key from the plugin settings
  $embed_key = get_option('dubbot_embed_key');
  $permalink = get_permalink($post_id);
  $dubbot_host = get_option('dubbot_api_url');
  if(!$dubbot_host):
    $dubbot_host = DUBBOT_API_URL;
  endif;

  // Build the iframe URL with the embed key
  if (!empty($embed_key)) {
    $url = $dubbot_host . '/embeds/'. esc_attr($embed_key);
    if ($type == "json") {
      $url .= '.json';
    }
    $url .= '?url=' . $permalink;
    return $url;
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

  $embed_key = get_option('dubbot_embed_key');
  if(empty($embed_key)) {
    return;
  }

  $api_url = get_option('dubbot_api_url');
  $editor_selector = '.wp-site-blocks';

  if ($hook == 'post.php') {
    $editor_selector = get_option('dubbot_editor_selector');
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
    // Check if the plugin is active and add the settings link
    $settings_link = '<a href="options-general.php?page=dubbot-settings">Settings</a>';
    array_unshift($links, $settings_link); // Add the link at the beginning

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dubbot_add_settings_link');

?>
