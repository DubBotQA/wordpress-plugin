<?php
/*
Plugin Name: DubBot Wordpress Plugin
Description: See DubBot results in WordPress
Version: 1.0
Author: DubBot
Author URI: https://dubbot.com
*/

const DUBBOT_API_URL = 'https://api.dubbot.com';
const EDITOR_SELECTOR = '#editor iframe';

/* The Admin configuration stuff */
function dubbot_register_settings() {
  // embed key
  register_setting('dubbot_settings_group', 'embed_key');

  // API url (default: api.dubbot.com)
  add_option('api_url', DUBBOT_API_URL);
  register_setting('dubbot_settings_group', 'api_url');

  // editor selector
  add_option('editor_selector', EDITOR_SELECTOR);
  register_setting('dubbot_settings_group', 'editor_selector');
}
add_action('admin_init', 'dubbot_register_settings');

function dubbot_menu() {
    add_options_page(
        'DubBot Settings',            // Page title
        'DubBot',                     // Menu title
        'manage_options',             // Capability
        'dubbot-settings',     // Menu slug
        'dubbot_settings_page' // Function to display the settings page
    );
}
add_action('admin_menu', 'dubbot_menu');

function dubbot_settings_page() {
  // TODO: Closing php so we can insert HTML with PHP inside it feels gross.
  // Is there some template-based thing we could do?
  $is_advanced = (isset($_GET['advanced']) && $_GET['advanced'] == 'y');
  ?>
    <div class="wrap">
      <h1>DubBot Settings</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields('dubbot_settings_group');

          // Output setting sections and their fields
          do_settings_sections('dubbot_settings_group');

          // Get the stored embed_key value
          $embed_key = get_option('embed_key');

          // and api url
          $api_url = get_option('api_url');
          if(!$api_url):
            $api_url = DUBBOT_API_URL;
          endif;

          // and editor selector
          $editor_selector = get_option('editor_selector');
          if(!$editor_selector):
            $editor_selector = EDITOR_SELECTOR;
          endif;
        ?>

        <table class="form-table">
          <tr valign="top">
            <th scope="row">Embed Key</th>
            <td>
              <input type="text" name="embed_key" value="<?php echo esc_attr($embed_key); ?>" />
              <p><em>Contact <a href="mailto:help@dubbot.com">DubBot Support</a> to request an embed key.</em></p>
            </td>
          </tr>

          <tr valign="top">
            <th scope="row">Editor Selector</th>
            <td>
              <input type="text" name="editor_selector" value="<?php echo esc_attr($editor_selector); ?>" />
              <p><em>The CSS selector for the element containing the editor's content</em></p>
            </td>
          </tr>

          <tr valign="top" style="display: <?php echo $is_advanced ? 'table-row' : 'none' ?>">
            <th scope="row">DubBot API URL</th>
            <td>
              <input type="text" name="api_url" value="<?php echo esc_attr($api_url); ?>" />
            </td>
          </tr>

        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
}

function get_dubbot_iframe_url($post_id) {
  return get_dubbot_url($post_id, "iframe");
}

function get_dubbot_json_url($post_id) {
  return get_dubbot_url($post_id, "json");
}

function get_dubbot_url($post_id, $type) {
  // Retrieve the embed_key from the plugin settings
  $embed_key = get_option('embed_key');
  $permalink = get_permalink($post_id);
  $dubbot_host = get_option('api_url');
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

function get_dubbot_page_metadata($post_id) {
  try {
    $url = get_dubbot_json_url($post_id);
    $response = @file_get_contents($url);
    if ($response === false) {
      throw new Exception($url . " failed");
    }

    $data = json_decode($response, true);
    return $data;
  } catch (Exception $e) {
    return ['total_issues_count' => 'N/A'];
  }
}

function enqueue_iframe_plugin_admin_scripts($hook) {
  $embed_key = get_option('embed_key');
  $api_url = get_option('api_url');
  $editor_selector = get_option('editor_selector');
  // Only load on post and page edit screens
  if (($hook !== 'post.php' && $hook !== 'post-new.php') || empty($embed_key)) {
      return;
  }

  // Enqueue jQuery (if not already included)
  wp_enqueue_script('jquery');
  wp_enqueue_script( 'jquery-ui-dialog');
  wp_enqueue_script( 'jquery-ui-resizable');
  wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');


  // Enqueue custom JavaScript for the modal
  wp_enqueue_script('dubbot-highlight', $api_url . '/embeds/highlight.js', null, null, true);
  wp_enqueue_script('dubbot-iframe', plugins_url('dubbot-iframe.js', __FILE__), array('jquery'), null, true);

  // Enqueue custom CSS for the modal
  wp_enqueue_style('dubbot-iframe', plugins_url('dubbot-iframe.css', __FILE__));

  $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
  $iframe_url = get_dubbot_iframe_url($post_id);
  $metadata = get_dubbot_page_metadata($post_id);
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
add_action('admin_enqueue_scripts', 'enqueue_iframe_plugin_admin_scripts');

?>
