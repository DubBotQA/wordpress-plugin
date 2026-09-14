<?php

if (!function_exists('dubbot_get_setting') || !function_exists('dubbot_update_setting') || !function_exists('dubbot_get_api_url') || !function_exists('dubbot_get_editor_selector') || !function_exists('dubbot_iframe_url') || !function_exists('dubbot_page_metadata')) {
  WP_CLI::error('DubBot plugin functions were not loaded.');
}

if (dubbot_get_setting('dubbot_embed_key') !== 'test-key') {
  WP_CLI::error('DubBot settings were not read from the WordPress options store.');
}

if (dubbot_get_api_url() !== get_option('dubbot_api_url') || dubbot_get_editor_selector() !== get_option('dubbot_editor_selector')) {
  WP_CLI::error('DubBot setting defaults were not resolved correctly.');
}

$post_id = wp_insert_post(array(
  'post_title' => 'DubBot smoke test',
  'post_content' => 'A test page for the DubBot plugin.',
  'post_status' => 'publish',
  'post_type' => 'page',
));

if (is_wp_error($post_id) || !$post_id) {
  WP_CLI::error('Could not create the smoke-test page.');
}

$api_url = dubbot_get_api_url();
$expected_embed_url = "$api_url/embeds/test-key?url=";
$iframe_url = dubbot_iframe_url($post_id);
if (strpos($iframe_url, $expected_embed_url) !== 0) {
  WP_CLI::error("Unexpected iframe URL: $iframe_url");
}

$custom_page_url = 'https://published.example.test/about/?source=wordpress&audience=staff';
$custom_url_filter = static function ($url, $filtered_post_id) use ($post_id, $custom_page_url) {
  return $filtered_post_id === $post_id ? $custom_page_url : $url;
};
add_filter('dubbot_page_url', $custom_url_filter, 10, 2);

foreach (array(dubbot_iframe_url($post_id), dubbot_json_url($post_id)) as $embed_url) {
  parse_str(wp_parse_url($embed_url, PHP_URL_QUERY), $query_args);
  if (($query_args['url'] ?? null) !== $custom_page_url) {
    WP_CLI::error('The custom page URL filter was not used for an embed URL.');
  }
}

remove_filter('dubbot_page_url', $custom_url_filter, 10);

$metadata = dubbot_page_metadata($post_id);
if (!is_array($metadata) || ($metadata['total_issues_count'] ?? null) !== 3) {
  WP_CLI::error('The mock DubBot API response was not read correctly.');
}

wp_set_current_user(1);
dubbot_enqueue_iframe_plugin_scripts('post.php');

global $wp_scripts;
foreach (array('dubbot-highlight', 'dubbot-iframe') as $handle) {
  if (!isset($wp_scripts->registered[$handle])) {
    WP_CLI::error("Expected script was not registered: $handle");
  }
}

$localized = $wp_scripts->registered['dubbot-iframe']->extra['data'] ?? '';
if (is_array($localized)) {
  $localized = implode("\n", $localized);
}
if (strpos($localized, $expected_embed_url) === false) {
  WP_CLI::error('The iframe URL was not passed to the browser script.');
}

WP_CLI::success('DubBot WordPress smoke checks passed.');
