=== Zillha Subscriber Gate ===
Contributors: zillha
Tags: subscriber, membership, page restriction, access control, age gate
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict WordPress pages by role and/or age verification. Supports allowlist, blocklist, and per-slug age gate modes.

== Description ==

Zillha Subscriber Gate provides two independent access-control features for WordPress pages:

1. **Subscriber Gate** — server-side role check. Restricts pages to logged-in users with a sufficient role.
2. **Age Gate** — client-side date-of-birth modal that appears on listed pages for first-time visitors, with a 1-year cookie on confirmation.

A lightweight admin panel under Settings > Subscriber Gate lets the site owner manage slug lists, switch modes, and configure redirect URLs without touching code.

Subscriber gate modes:

* **Allowlist** (default) — only listed slugs are restricted; everything else is public.
* **Blocklist** — all pages are restricted except listed slugs. The safety slugs `login`, `register`, `subscribe`, and `home` are always allowed so the login flow cannot be accidentally locked out.

Behavior:

* Non-logged-in visitors hitting a restricted page are sent to the WordPress login screen and returned to the gated page after login.
* Logged-in users whose role is not one of subscriber, contributor, author, editor, or administrator are redirected to a configurable URL (defaults to `/subscribe/`).
* Administrators and editors are never blocked by this plugin, regardless of mode or slug list.
* Non-page content (posts, archives, home, 404) is never gated.

Age gate behavior:

* Disabled by default. Admins must opt in from the settings page.
* On first visit to a listed page, a modal prompts for date of birth.
* Visitors aged 18 or older get a 1-year cookie and the modal does not re-appear.
* Visitors under 18, or those who decline, are redirected to a configurable URL.
* Administrators and editors bypass the age gate entirely — the assets are not enqueued for them.
* No personal information is stored. The cookie value is simply `1`.

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

= 0.2.0 =
* Added client-side Age Gate: per-slug date-of-birth modal with a 1-year `zsg_age_verified` cookie on confirmation.
* Under-18 or deny responses redirect to a configurable URL.
* Administrators and editors bypass the age gate — assets are never enqueued for them.
* Admin UI adds an Age Gate section with enable toggle, separate slug list, and under-18 redirect URL.
* No PII stored; cookie value is simply `1`, `SameSite=Lax`, path `/`.
* Disabled by default — upgrading from 1.1.x produces zero behavior change until enabled.

= 1.1.0 =
* Added mode toggle: allowlist (restrict listed) or blocklist (restrict all except listed).
* Auto-exclude login, register, subscribe, and home slugs in blocklist mode to prevent lockouts.
* Administrators and editors are never blocked, regardless of mode or slug list.

= 1.0.0 =
* Initial release.
