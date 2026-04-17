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
	 * Hook the restriction check into template_redirect.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_restrict' ) );
	}

	/**
	 * Evaluate the current request and redirect if the page is gated.
	 *
	 * @return void
	 */
	public function maybe_restrict() {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		// Non-negotiable bypass: administrators and editors are never blocked.
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( array_intersect( (array) $current_user->roles, array( 'administrator', 'editor' ) ) ) {
				return;
			}
		}

		$queried = get_queried_object();
		if ( ! $queried || empty( $queried->post_name ) ) {
			return;
		}
		$slug = $queried->post_name;

		$mode  = get_option( 'zsg_mode', 'allowlist' );
		$slugs = (array) get_option( 'zsg_restricted_slugs', array() );

		if ( 'blocklist' === $mode ) {
			if ( in_array( $slug, $this->get_safety_slugs(), true ) ) {
				return;
			}
			if ( in_array( $slug, $slugs, true ) ) {
				return;
			}
			$restricted = true;
		} else {
			$restricted = in_array( $slug, $slugs, true );
		}

		if ( ! $restricted ) {
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
