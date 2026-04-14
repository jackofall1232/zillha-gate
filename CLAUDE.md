# Zillha Subscriber Gate — CLAUDE.md

## Project Overview

Build a production-ready WordPress plugin called **Zillha Subscriber Gate**.

This plugin restricts access to specific pages (by slug) to subscribers or higher. An admin panel lets the site owner add and remove restricted slugs without touching code. Non-logged-in users are redirected to the login page. Logged-in users without sufficient role are redirected to a configurable "subscribe" page.

---

## Plugin Details

| Field | Value |
|---|---|
| Plugin name | Zillha Subscriber Gate |
| Text domain | zillha-subscriber-gate |
| Plugin slug | zillha-subscriber-gate |
| Main file | zillha-subscriber-gate.php |
| Prefix | zsg_ |
| Option key | zsg_restricted_slugs (array of strings) |
| Min PHP | 7.4 |
| Min WP | 6.0 |
| License | GPL-2.0-or-later |

---

## File Structure

```
zillha-subscriber-gate/
├── zillha-subscriber-gate.php   # Main plugin file (headers + bootstrap)
├── includes/
│   ├── class-zsg-restrictor.php # Handles template_redirect logic
│   └── class-zsg-admin.php      # Admin panel (settings page)
├── assets/
│   └── admin.css                # Minimal admin styles
└── readme.txt                   # WordPress.org readme format
```

---

## Main Plugin File (zillha-subscriber-gate.php)

Standard WordPress plugin headers. Bootstrap by requiring includes files and initializing classes.

```php
/**
 * Plugin Name: Zillha Subscriber Gate
 * Plugin URI:  https://zillha.com
 * Description: Restrict specific pages by slug to subscribers or higher. Manage restricted slugs from the admin panel.
 * Version:     1.0.0
 * Author:      Joe (Zillha)
 * Author URI:  https://zillha.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zillha-subscriber-gate
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
```

- Define `ZSG_VERSION`, `ZSG_PATH`, `ZSG_URL` constants
- Require both class files
- Instantiate both classes on `plugins_loaded`

---

## class-zsg-restrictor.php

**Purpose:** Hook into `template_redirect` and redirect users who don't have access.

### Logic

```
On template_redirect:
1. Return early if not is_singular('page')
2. Get current page slug via get_queried_object()->post_name
3. Load restricted slugs from option: get_option('zsg_restricted_slugs', [])
4. If slug not in restricted list → return (allow through)
5. If not is_user_logged_in() → wp_redirect( wp_login_url( get_permalink() ) ) + exit
6. Get current user roles
7. Allowed roles: subscriber, administrator, editor, author, contributor
8. If user has none of the allowed roles → wp_redirect to the configured redirect URL + exit
```

### Allowed roles constant

Define a private method `get_allowed_roles()` returning the array. This makes it easy to modify later.

### Redirect URL for non-qualifying users

Retrieved via `get_option('zsg_redirect_url', home_url('/subscribe/'))`.

---

## class-zsg-admin.php

**Purpose:** WordPress Settings API admin page for managing restricted slugs and the redirect URL.

### Admin menu

- Add under **Settings** menu: `Settings > Subscriber Gate`
- Menu slug: `zsg-settings`
- Capability: `manage_options`

### Settings page UI

The page has two sections:

#### Section 1: Restricted Slugs

Display a styled list of all currently restricted slugs. Each row shows:
- The slug in a `<code>` tag
- A "Remove" button (submits a form with the slug to remove)

Below the list, a form to add a new slug:
- Text input: placeholder "enter-page-slug"
- Add button
- Brief helper text: "Enter the page slug exactly as it appears in the URL. Example: for `/worlds/darkwood/` enter `darkwood`."

#### Section 2: Redirect URL

- Label: "Redirect non-subscribers to:"
- Text input (url type), default `home_url('/subscribe/')`
- Save button
- Helper: "Users who are logged in but not subscribers will be sent here."

### Form handling

Handle both forms in `admin_init` via `admin_post` actions or direct `$_POST` checks with nonce verification. Use `wp_redirect` + `exit` after saves to prevent double-submit.

**Nonce names:**
- Add slug: `zsg_add_slug_nonce`
- Remove slug: `zsg_remove_slug_nonce`
- Save redirect URL: `zsg_redirect_url_nonce`

### Data handling

- Slugs stored as `array` in `get_option('zsg_restricted_slugs', [])`
- Sanitize slugs with `sanitize_title()` before storing
- Sanitize redirect URL with `esc_url_raw()`
- When adding: check for duplicates before pushing
- When removing: use `array_filter` to exclude the target slug, then `array_values` to reindex

### Admin notices

Show a `notice-success` or `notice-error` dismissible admin notice after each action using a transient keyed to the current user: `zsg_admin_notice_{user_id}`.

---

## assets/admin.css

Minimal styles only. Keep it clean and native WP-looking.

```css
/* Slug list table */
.zsg-slug-list { margin-bottom: 20px; }
.zsg-slug-list tr td { vertical-align: middle; padding: 6px 10px; }
.zsg-slug-list code { font-size: 13px; background: #f0f0f1; padding: 2px 6px; border-radius: 3px; }

/* Add slug form */
.zsg-add-form input[type="text"] { width: 260px; }

/* Section spacing */
.zsg-section { margin-top: 30px; }
```

---

## readme.txt

WordPress.org format. Include:

```
=== Zillha Subscriber Gate ===
Contributors: (your WP.org username)
Tags: subscriber, membership, page restriction, access control
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict specific WordPress pages to subscribers or higher via a simple admin panel.

== Description ==
...
== Installation ==
...
== Changelog ==
= 1.0.0 =
* Initial release
```

---

## Security Requirements

- All `$_POST` handling must verify nonce with `wp_verify_nonce()`
- All capability checks with `current_user_can('manage_options')`
- Sanitize all inputs before storing
- Escape all outputs with `esc_html()`, `esc_attr()`, `esc_url()`
- No direct file access: `defined('ABSPATH') || exit;` at top of every file

---

## WordPress Coding Standards

- Snake_case for functions and variables
- PascalCase for class names
- Tabs for indentation
- Doc blocks on all classes and public methods
- No closing `?>` PHP tags
- Prefix everything with `zsg_`

---

## What NOT to build

- No Gutenberg blocks
- No REST API endpoints
- No JavaScript (PHP forms only)
- No database tables (options API is sufficient)
- No premium/freemius integration
- No multisite handling (out of scope for 1.0)

---

## Testing Checklist (for Claude Code to verify)

- [ ] Plugin activates without errors
- [ ] Settings page appears under Settings menu
- [ ] Can add a slug and it appears in the list
- [ ] Can remove a slug and it disappears from the list
- [ ] Duplicate slugs are not added
- [ ] Redirect URL saves and persists
- [ ] Logged-out user hitting a restricted page → login redirect with return URL
- [ ] Logged-in subscriber hitting restricted page → allowed through
- [ ] Logged-in user with no role hitting restricted page → redirect URL
- [ ] Admin hitting restricted page → allowed through
- [ ] Non-restricted page → always allowed through regardless of login state
- [ ] Nonces verified on all form submissions
- [ ] No PHP warnings or notices at WP_DEBUG = true
