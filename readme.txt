=== Zillha Subscriber Gate ===
Contributors: zillha
Tags: subscriber, membership, page restriction, access control
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict specific WordPress pages to subscribers or higher via a simple admin panel.

== Description ==

Zillha Subscriber Gate lets you restrict access to specific WordPress pages (by slug) to logged-in subscribers or higher. A lightweight admin panel under Settings > Subscriber Gate lets the site owner add and remove restricted slugs and configure a fallback redirect URL without touching code.

Behavior:

* Non-logged-in visitors hitting a restricted page are sent to the WordPress login screen and returned to the gated page after login.
* Logged-in users whose role is not one of subscriber, contributor, author, editor, or administrator are redirected to a configurable URL (defaults to `/subscribe/`).
* Non-restricted pages are unaffected.

The plugin uses the WordPress Options API only; no custom database tables are created.

== Installation ==

1. Upload the `zillha-subscriber-gate` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Visit Settings > Subscriber Gate to configure restricted slugs and the redirect URL.

== Frequently Asked Questions ==

= How do I find a page's slug? =

The slug is the last segment of the URL before the trailing slash. For `https://example.com/worlds/darkwood/`, the slug is `darkwood`.

= Which roles can access restricted pages? =

Subscriber, Contributor, Author, Editor, and Administrator.

== Changelog ==

= 1.0.0 =
* Initial release.
