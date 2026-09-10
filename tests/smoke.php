<?php

if (!function_exists('dubbot_iframe_url') || !function_exists('dubbot_page_metadata')) {
  WP_CLI::error('DubBot plugin functions were not loaded.');
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

$api_url = get_option('dubbot_api_url');
$expected_embed_url = "$api_url/embeds/test-key?url=";
$iframe_url = dubbot_iframe_url($post_id);
if (strpos($iframe_url, $expected_embed_url) !== 0) {
  WP_CLI::error("Unexpected iframe URL: $iframe_url");
}

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
