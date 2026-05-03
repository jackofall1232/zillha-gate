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
	 * Hook the restriction check into template_redirect and noindex output into wp_head.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_restrict' ) );
		add_action( 'wp_head', array( $this, 'maybe_output_noindex' ), 1 );
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
	 * Output a noindex meta tag for restricted pages so search engines cannot
	 * index gated content. Fires at priority 1 (early in <head>). Applies to
	 * all visitors — Googlebot is never logged in.
	 *
	 * @return void
	 */
	public function maybe_output_noindex() {
		if ( ! $this->is_page_restricted() ) {
			return;
		}
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}

	/**
	 * Determine whether the current page is subject to the subscriber gate,
	 * independent of the current user's role.
	 *
	 * @return bool
	 */
	private function is_page_restricted() {
		if ( ! is_singular( 'page' ) ) {
			return false;
		}

		$queried = get_queried_object();
		if ( ! $queried || empty( $queried->post_name ) ) {
			return false;
		}
		$slug = $queried->post_name;

		$mode  = get_option( 'zsg_mode', 'allowlist' );
		$slugs = (array) get_option( 'zsg_restricted_slugs', array() );

		if ( 'blocklist' === $mode ) {
			if ( in_array( $slug, $this->get_safety_slugs(), true ) ) {
				return false;
			}
			if ( in_array( $slug, $slugs, true ) ) {
				return false;
			}
			return true;
		}

		return in_array( $slug, $slugs, true );
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
