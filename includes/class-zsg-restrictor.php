<?php
/**
 * Restrictor class: gates page access on template_redirect.
 *
 * @package Zillha_Subscriber_Gate
 */

defined( 'ABSPATH' ) || exit;

/**
 * ZSG_Restrictor
 *
 * Redirects unauthenticated or insufficiently-privileged users
 * away from gated pages. Supports two modes:
 *   - allowlist: only listed slugs are gated (default)
 *   - blocklist: all pages are gated except listed slugs and safety slugs
 *
 * Administrators and editors are never blocked by this class.
 */
class ZSG_Restrictor {

	/**
	 * Register hooks.
	 *
	 * maybe_send_noindex_header runs at priority 1 so the X-Robots-Tag HTTP
	 * header is added before maybe_restrict() (priority 10) can redirect and
	 * exit — the header therefore appears even on redirect responses seen by
	 * search engine crawlers. The wp_robots filter covers rendered pages for
	 * logged-in users and integrates with core/SEO-plugin robots handling.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_send_noindex_header' ), 1 );
		add_action( 'template_redirect', array( $this, 'maybe_restrict' ) );
		add_filter( 'wp_robots', array( $this, 'add_noindex_to_robots' ) );
	}

	/**
	 * Evaluate the current request and redirect if the page is gated.
	 *
	 * @return void
	 */
	public function maybe_restrict() {
		// Non-negotiable bypass: administrators and editors are never blocked.
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( array_intersect( (array) $current_user->roles, array( 'administrator', 'editor' ) ) ) {
				return;
			}
		}

		if ( ! $this->is_page_restricted() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) );
			exit;
		}

		$user          = wp_get_current_user();
		$allowed_roles = $this->get_allowed_roles();

		if ( ! array_intersect( (array) $user->roles, $allowed_roles ) ) {
			$redirect_url = get_option( 'zsg_redirect_url', home_url( '/subscribe/' ) );
			wp_safe_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}
	}

	/**
	 * Send an X-Robots-Tag HTTP header for restricted pages.
	 *
	 * Runs at template_redirect priority 1, before maybe_restrict() redirects
	 * and exits, so the header is present on both redirect responses (anonymous
	 * crawlers) and normal page responses (logged-in subscribers).
	 *
	 * @return void
	 */
	public function maybe_send_noindex_header() {
		if ( ! $this->is_page_restricted() ) {
			return;
		}
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		}
	}

	/**
	 * Add noindex/nofollow directives via the wp_robots filter (WP 5.7+).
	 *
	 * This covers rendered pages for logged-in users and integrates cleanly
	 * with WordPress core and third-party SEO plugins.
	 *
	 * @param array $robots Associative array of robots directives.
	 * @return array
	 */
	public function add_noindex_to_robots( $robots ) {
		if ( $this->is_page_restricted() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	/**
	 * Determine whether the current page is subject to the subscriber gate,
	 * independent of the current user's role.
	 *
	 * @return bool
	 */
	private function is_page_restricted() {
		static $result = null;

		if ( null !== $result ) {
			return $result;
		}

		if ( ! is_singular( 'page' ) ) {
			return $result = false;
		}

		$queried = get_queried_object();
		if ( ! $queried || empty( $queried->post_name ) ) {
			return $result = false;
		}
		$slug = $queried->post_name;

		$mode  = get_option( 'zsg_mode', 'allowlist' );
		$slugs = (array) get_option( 'zsg_restricted_slugs', array() );

		if ( 'blocklist' === $mode ) {
			if ( in_array( $slug, $this->get_safety_slugs(), true ) ) {
				return $result = false;
			}
			if ( in_array( $slug, $slugs, true ) ) {
				return $result = false;
			}
			return $result = true;
		}

		return $result = in_array( $slug, $slugs, true );
	}

	/**
	 * Roles permitted to view restricted pages.
	 *
	 * Administrator and editor are listed for completeness; in practice they
	 * return early via the bypass check in maybe_restrict().
	 *
	 * @return string[]
	 */
	private function get_allowed_roles() {
		return array( 'subscriber', 'administrator', 'editor', 'author', 'contributor' );
	}

	/**
	 * Slugs always allowed through in blocklist mode, to avoid redirect loops
	 * and lockouts on login/register/subscribe/home flows.
	 *
	 * @return string[]
	 */
	private function get_safety_slugs() {
		return array( 'login', 'register', 'subscribe', 'home' );
	}
}
