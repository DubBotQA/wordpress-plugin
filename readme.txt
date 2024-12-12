=== DubBot ===
Contributors: dubbot
Donate link: https://dubbot.com
Tags: accessibility, spelling, links, seo, web governance
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
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


== Troubleshooting ==

= Highlighting isn't working. =
You may need to update the Editor Selector in the Settings. This is the [CSS Selector](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_selectors) that corresponds to the HTML element containing the WordPress editor.

= Some things aren't highlighting =
Those things could be part of template content, such as a header or footer, which is usually not seen while editing page content.

== Changelog ==

= 1.0.0 =
* Initial release with support for displaying DubBot results in WordPress.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of the plugin. No upgrades are needed.

== Support ==

For any questions or support requests, please contact [help@dubbot.com](mailto:help@dubbot.com).

== License ==

This plugin is licensed under the GPLv2 (or later). For more information, see the [GPLv2 License](https://www.gnu.org/licenses/gpl-2.0.html).
