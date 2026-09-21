<?php

if (!is_multisite()) {
  WP_CLI::error('This smoke test must run in a multisite installation.');
}

if (!function_exists('dubbot_get_setting') || !function_exists('dubbot_update_setting') || !function_exists('dubbot_site_overrides_allowed') || !function_exists('dubbot_iframe_url')) {
  WP_CLI::error('DubBot plugin functions were not loaded.');
}

if (is_main_site()) {
  WP_CLI::error('This smoke test must run on a non-main site.');
}

$network_embed_key = 'test-key';
$site_embed_key = 'site-specific-key';

dubbot_update_setting('dubbot_embed_key', $network_embed_key);
update_option('dubbot_embed_key', $site_embed_key);
update_site_option('dubbot_allow_site_overrides', 1);

if (!dubbot_site_overrides_allowed() || dubbot_get_setting('dubbot_embed_key') !== $site_embed_key) {
  WP_CLI::error('DubBot did not use the site-specific embed key.');
}

$post_id = wp_insert_post(array(
  'post_title' => 'DubBot multisite smoke test',
  'post_content' => 'A test page for multisite settings.',
  'post_status' => 'publish',
  'post_type' => 'page',
));

if (is_wp_error($post_id) || !$post_id) {
  WP_CLI::error('Could not create the multisite smoke-test page.');
}

$site_metadata = dubbot_page_metadata($post_id);
if (($site_metadata['total_issues_count'] ?? null) !== 7) {
  WP_CLI::error('DubBot did not fetch site-specific metadata.');
}

update_site_option('dubbot_allow_site_overrides', 0);
if (dubbot_site_overrides_allowed() || dubbot_get_setting('dubbot_embed_key') !== $network_embed_key) {
  WP_CLI::error('DubBot did not fall back to the network-wide embed key.');
}

$network_metadata = dubbot_page_metadata($post_id);
if (($network_metadata['total_issues_count'] ?? null) !== 3) {
  WP_CLI::error('DubBot did not fetch network metadata.');
}

update_site_option('dubbot_allow_site_overrides', 1);

$iframe_url = dubbot_iframe_url($post_id);
$expected_url = dubbot_get_api_url() . '/embeds/' . $site_embed_key . '?url=';

if (strpos($iframe_url, $expected_url) !== 0 || strpos($iframe_url, rawurlencode(get_permalink($post_id))) === false) {
  WP_CLI::error("Unexpected multisite iframe URL: $iframe_url");
}

// Legacy per-site defaults from earlier versions must not hide the network settings.
update_option('dubbot_api_url', DUBBOT_API_URL);
update_option('dubbot_editor_selector', DUBBOT_EDITOR_SELECTOR);
delete_option('dubbot_legacy_defaults_cleaned');
dubbot_maybe_clean_legacy_site_defaults();

if (null !== get_option('dubbot_api_url', null) || null !== get_option('dubbot_editor_selector', null)) {
  WP_CLI::error('DubBot did not remove legacy per-site defaults.');
}

if (dubbot_get_api_url() !== dubbot_get_network_api_url() || dubbot_has_site_setting('dubbot_api_url')) {
  WP_CLI::error('DubBot did not use the network API URL after removing legacy defaults.');
}

// Blank site values mean "use the network default".
update_option('dubbot_embed_key', '');
if (dubbot_get_setting('dubbot_embed_key') !== $network_embed_key || dubbot_has_site_setting('dubbot_embed_key')) {
  WP_CLI::error('DubBot did not treat a blank site embed key as the network default.');
}

if (!has_filter('network_admin_plugin_action_links_dubbot/dubbot.php', 'dubbot_add_settings_link')) {
  WP_CLI::error('DubBot did not register the network admin Settings link.');
}

WP_CLI::success('DubBot multisite smoke checks passed.');
