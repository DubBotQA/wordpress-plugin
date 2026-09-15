=== DubBot ===
Contributors: syldubbot
Tags: accessibility, spelling, links, seo, web governance
Tested up to: 7.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display results from your DubBot account within WordPress.

== Description ==

The DubBot Plugin connects to your [DubBot](https://dubbot.com) account and displays accessibility, spelling, links, SEO, best practices, and custom web governance rule results directly within WordPress.

This plugin requires an "Embed Key," which can be requested by contacting DubBot support at [help@dubbot.com](mailto:help@dubbot.com).

**Features:**
- Highlight issues from DubBot inside your WordPress editor.
- View results from:
  - Accessibility
  - Best Practices
  - Spelling
  - Links
  - Web Governance
  - SEO

A DubBot account is required to use this plugin.

== Installation ==

1. Download the plugin ZIP file.
2. Go to your WordPress Admin Dashboard.
3. Navigate to `Plugins > Add New`.
4. Click `Upload Plugin` and select the downloaded ZIP file.
5. Click `Install Now` and then `Activate`.
6. Navigate to `Settings > DubBot` to enter your "Embed Key."

== External services ==

This plugin requires a [DubBot](https://dubbot.com) account render results from the [DubBot API](https://api.dubbot.com/).

On the page/post view and edit screens, the plugin sends the Embed Key and the page/post's URL to the DubBot API to fetch and display any issues for the page.

For new DubBot customers, [request a demo](https://dubbot.com/request-demo/index.html) to get started.
For existing customers, contact DubBot support at [help@dubbot.com](mailto:help@dubbot.com) to request an Embed Key.

DubBot [Privacy Policy](https://dubbot.com/privacy-policy.html) and [Terms of Service](https://dubbot.com/terms-of-service.html)

== Troubleshooting ==

= Customizing the page URL =
By default, DubBot sends WordPress's permalink for the page or post to the DubBot API. If the public URL differs from the WordPress permalink, such as when a domain mapping plugin is used, add a `dubbot_page_url` filter in a site-specific plugin or your theme's functions.php file:

`add_filter('dubbot_page_url', function ($url, $post_id) { return domainmap_map_url($url); }, 10, 2);`

The filter receives the WordPress permalink and the post ID, and must return the URL that DubBot should use. It applies to both the displayed results and the metadata used for editor highlighting.

= Highlighting isn't working. =
You may need to update the Editor Selector in the Settings. This is the [CSS Selector](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_selectors) that corresponds to the HTML element containing the WordPress editor.

= Some things aren't highlighting =
Those things could be part of template content, such as a header or footer, which is usually not seen while editing page content.

== Changelog ==

= 1.0.3 =
* Added the `dubbot_page_url` filter for sites whose public URLs differ from WordPress permalinks.

= 1.0.2 =
* Tested with WordPress 7.1.

= 1.0.0 =
* Initial release with support for displaying DubBot results in WordPress.

== Upgrade Notice ==

= 1.0.2 =
Tested with WordPress 7.1.

= 1.0.0 =
This is the first version of the plugin. No upgrades are needed.

== Support ==

For any questions or support requests, please contact [help@dubbot.com](mailto:help@dubbot.com).

== License ==

This plugin is licensed under the GPLv2 (or later). For more information, see the [GPLv2 License](https://www.gnu.org/licenses/gpl-2.0.html).
