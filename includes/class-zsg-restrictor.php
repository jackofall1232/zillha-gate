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
 * away from pages whose slug appears in the restricted list.
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

		$queried = get_queried_object();
		if ( ! $queried || empty( $queried->post_name ) ) {
			return;
		}
		$slug = $queried->post_name;

		$restricted = (array) get_option( 'zsg_restricted_slugs', array() );
		if ( ! in_array( $slug, $restricted, true ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) );
			exit;
		}

		$user           = wp_get_current_user();
		$user_roles     = (array) $user->roles;
		$allowed_roles  = $this->get_allowed_roles();
		$has_allowed    = array_intersect( $user_roles, $allowed_roles );

		if ( empty( $has_allowed ) ) {
			$redirect_url = get_option( 'zsg_redirect_url', home_url( '/subscribe/' ) );
			wp_safe_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}
	}

	/**
	 * Roles permitted to view restricted pages.
	 *
	 * @return string[]
	 */
	private function get_allowed_roles() {
		return array( 'subscriber', 'administrator', 'editor', 'author', 'contributor' );
	}
}
