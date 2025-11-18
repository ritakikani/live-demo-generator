=== Live Demo Generator ===
Contributors: kikanirita
Donate link:
Tags: demo, instawp, sandbox
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A production-ready live demo generator plugin that integrates with InstaWP.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/live-demo-generator` directory, or install the ZIP via the WP Admin -> Plugins -> Add New -> Upload Plugin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the "Demo Builder" admin menu. Enter your InstaWP API Key and Template ID (snapshot id).
4. Optionally paste your dependency map JSON and save.
5. Add the shortcode [live_demo_form] to a page to allow demo requests.
6. Configure InstaWP to accept post_creation_script 'insta-demo-setup.php' or pass site options so the snapshot script can create demo users.
