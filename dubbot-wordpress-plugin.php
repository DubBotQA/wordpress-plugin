<?php
/*
Plugin Name: DubBot Wordpress Plugin
Description: Fetch DubBot data and show it to content editors
Version: 1.0
Author: DubBot
*/


/* The Admin configuration stuff */
function dubbot_plugin_register_settings() {
  register_setting('dubbot_plugin_settings_group', 'embed_key');
}
add_action('admin_init', 'dubbot_plugin_register_settings');

function dubbot_plugin_menu() {
    add_options_page(
        'DubBot Plugin Settings',     // Page title
        'DubBot Plugin',              // Menu title
        'manage_options',             // Capability
        'dubbot-plugin-settings',     // Menu slug
        'dubbot_plugin_settings_page' // Function to display the settings page
    );
}
add_action('admin_menu', 'dubbot_plugin_menu');

function dubbot_plugin_settings_page() {
  ?>
    <div class="wrap">
      <h1>DubBot Plugin Settings</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields('dubbot_plugin_settings_group');

          // Output setting sections and their fields
          do_settings_sections('iframe_plugin_settings_group');

          // Get the stored embed_key value
          $embed_key = get_option('embed_key');
        ?>

        <table class="form-table">
          <tr valign="top">
            <th scope="row">Embed Key</th>
            <td>
              <input type="text" name="embed_key" value="<?php echo esc_attr($embed_key); ?>" />
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
}



/* The box that shows up during editing */
function dubbot_meta_box_setup() {
  $screens = array('post', 'page'); // Add post and page
  foreach ($screens as $screen) {
    add_meta_box(
      'dubbot_meta_box', // Meta box ID
      'DubBot', // Title of the meta box
      'dubbot_meta_box_content', // Callback to display the meta box content
      $screen,
      'side', // Context (normal, side, advanced)
      'default' // Priority
    );
  }
}
add_action('add_meta_boxes', 'dubbot_meta_box_setup');

function dubbot_meta_box_content($post) {
    // Add a nonce field for security
    wp_nonce_field('dubbot_meta_box_nonce', 'dubbot_meta_box_nonce_field');

    // Build the iframe URL using the post ID and embed_key
    $iframe_url = get_dubbot_iframe_url($post->ID);

    // Render the iframe
    echo '<div style="margin:0;padding:0;width:100%"><iframe src="' . esc_url($iframe_url) . '" width="100%" height="500" frameborder="0"></iframe></div>';
}


function get_dubbot_iframe_url($page_id) {
  // Retrieve the embed_key from the plugin settings
  $embed_key = get_option('embed_key');
  $permalink = get_permalink($page_id);
  //$dubbot_host = 'https://api.dubbot.com';
  $dubbot_host = 'http://localhost:3000';

  // Build the iframe URL with the embed key
  if (!empty($embed_key)) {
    return $dubbot_host . '/embeds/'. esc_attr($embed_key) . '?url=' . $permalink;
  } else {
    return null;
  }
}


?>
