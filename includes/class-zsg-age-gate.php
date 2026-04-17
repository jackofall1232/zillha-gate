<?php
/**
 * Age Gate class: enqueues the client-side DOB modal on age-gated pages.
 *
 * @package Zillha_Subscriber_Gate
 */

defined( 'ABSPATH' ) || exit;

/**
 * ZSG_Age_Gate
 *
 * Decides, per request, whether to enqueue the client-side age verification
 * modal assets. All cookie logic lives in age-gate.js; PHP's only role is
 * to gate the enqueue by option flags, page slug, and user capability.
 *
 * Administrators and editors bypass this check and never see the modal.
 */
class ZSG_Age_Gate {

	/**
	 * Hook the enqueue check into wp_enqueue_scripts.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Evaluate the current request and enqueue age-gate assets if applicable.
	 *
	 * @return void
	 */
	public function maybe_enqueue() {
		if ( ! get_option( 'zsg_age_gate_enabled', false ) ) {
			return;
		}

		if ( ! is_singular( 'page' ) ) {
			return;
		}

		if ( $this->is_privileged_user() ) {
			return;
		}

		$queried = get_queried_object();
		if ( ! $queried || empty( $queried->post_name ) ) {
			return;
		}
		$slug = $queried->post_name;

		$slugs = (array) get_option( 'zsg_age_gate_slugs', array() );
		if ( ! in_array( $slug, $slugs, true ) ) {
			return;
		}

		wp_enqueue_style(
			'zsg-age-gate-style',
			ZSG_URL . 'assets/age-gate.css',
			array(),
			ZSG_VERSION
		);

		wp_enqueue_script(
			'zsg-age-gate',
			ZSG_URL . 'assets/age-gate.js',
			array(),
			ZSG_VERSION,
			true
		);

		wp_localize_script(
			'zsg-age-gate',
			'zsgAgeGate',
			array(
				'cookieName'     => 'zsg_age_verified',
				'cookieDays'     => 365,
				'redirectUrl'    => esc_url_raw( get_option( 'zsg_age_gate_redirect_url', home_url() ) ),
				'warningMessage' => __( 'This content contains mature themes and is intended for adults only. By continuing you confirm that you are 18 years of age or older.', 'zillha-subscriber-gate' ),
				'confirmLabel'   => __( 'I am 18 or older — Enter', 'zillha-subscriber-gate' ),
				'denyLabel'      => __( 'I am under 18 — Exit', 'zillha-subscriber-gate' ),
				'titleLabel'     => __( 'Age Verification Required', 'zillha-subscriber-gate' ),
				'legendLabel'    => __( 'Enter your date of birth', 'zillha-subscriber-gate' ),
				'monthLabel'     => __( 'Month', 'zillha-subscriber-gate' ),
				'dayLabel'       => __( 'Day', 'zillha-subscriber-gate' ),
				'yearLabel'      => __( 'Year', 'zillha-subscriber-gate' ),
				'dayPlaceholder' => __( 'DD', 'zillha-subscriber-gate' ),
				'yearPlaceholder'=> __( 'YYYY', 'zillha-subscriber-gate' ),
				'errorMessage'   => __( 'Please enter a valid date of birth.', 'zillha-subscriber-gate' ),
				'monthNames'     => array(
					__( 'January', 'zillha-subscriber-gate' ),
					__( 'February', 'zillha-subscriber-gate' ),
					__( 'March', 'zillha-subscriber-gate' ),
					__( 'April', 'zillha-subscriber-gate' ),
					__( 'May', 'zillha-subscriber-gate' ),
					__( 'June', 'zillha-subscriber-gate' ),
					__( 'July', 'zillha-subscriber-gate' ),
					__( 'August', 'zillha-subscriber-gate' ),
					__( 'September', 'zillha-subscriber-gate' ),
					__( 'October', 'zillha-subscriber-gate' ),
					__( 'November', 'zillha-subscriber-gate' ),
					__( 'December', 'zillha-subscriber-gate' ),
				),
			)
		);
	}

	/**
	 * Whether the current user is an administrator or editor.
	 *
	 * Mirrors the bypass check in ZSG_Restrictor so the modal is never
	 * enqueued for privileged users regardless of slug configuration.
	 *
	 * @return bool
	 */
	private function is_privileged_user() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		return (bool) array_intersect( (array) $user->roles, array( 'administrator', 'editor' ) );
	}
}
