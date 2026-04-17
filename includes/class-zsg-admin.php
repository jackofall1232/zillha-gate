<?php
/**
 * Admin class: settings page and form handlers.
 *
 * @package Zillha_Subscriber_Gate
 */

defined( 'ABSPATH' ) || exit;

/**
 * ZSG_Admin
 *
 * Registers the Settings > Subscriber Gate page and handles
 * add/remove slug and redirect URL form submissions.
 */
class ZSG_Admin {

	/**
	 * Menu/page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'zsg-settings';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_forms' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_options_page(
			__( 'Subscriber Gate', 'zillha-subscriber-gate' ),
			__( 'Subscriber Gate', 'zillha-subscriber-gate' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles on the settings page only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'zsg-admin',
			ZSG_URL . 'assets/admin.css',
			array(),
			ZSG_VERSION
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'zillha-subscriber-gate' ) );
		}

		$slugs        = (array) get_option( 'zsg_restricted_slugs', array() );
		$redirect_url = (string) get_option( 'zsg_redirect_url', home_url( '/subscribe/' ) );
		$mode         = get_option( 'zsg_mode', 'allowlist' );
		if ( ! in_array( $mode, array( 'allowlist', 'blocklist' ), true ) ) {
			$mode = 'allowlist';
		}
		$is_blocklist     = ( 'blocklist' === $mode );
		$slug_section_h2  = $is_blocklist
			? __( 'Exception Slugs', 'zillha-subscriber-gate' )
			: __( 'Restricted Slugs', 'zillha-subscriber-gate' );
		$slug_empty_copy  = $is_blocklist
			? __( 'No exception slugs. In blocklist mode, all pages are gated except the safety slugs (login, register, subscribe, home).', 'zillha-subscriber-gate' )
			: __( 'No slugs are currently restricted.', 'zillha-subscriber-gate' );
		$action_url       = admin_url( 'admin-post.php' );

		$age_enabled      = (bool) get_option( 'zsg_age_gate_enabled', false );
		$age_slugs        = (array) get_option( 'zsg_age_gate_slugs', array() );
		$age_redirect_url = (string) get_option( 'zsg_age_gate_redirect_url', home_url() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscriber Gate', 'zillha-subscriber-gate' ); ?></h1>

			<div class="zsg-section">
				<h2><?php esc_html_e( 'Mode', 'zillha-subscriber-gate' ); ?></h2>
				<form method="post" action="<?php echo esc_url( $action_url ); ?>">
					<input type="hidden" name="action" value="zsg_mode_save" />
					<?php wp_nonce_field( 'zsg_mode_save_nonce', 'zsg_mode_save_nonce' ); ?>
					<div class="zsg-mode-options">
						<label>
							<input type="radio" name="zsg_mode" value="allowlist" <?php checked( $mode, 'allowlist' ); ?> />
							<strong><?php esc_html_e( 'Allowlist', 'zillha-subscriber-gate' ); ?></strong>
							&mdash;
							<?php esc_html_e( 'only listed slugs are restricted (everything else is public).', 'zillha-subscriber-gate' ); ?>
						</label>
						<label>
							<input type="radio" name="zsg_mode" value="blocklist" <?php checked( $mode, 'blocklist' ); ?> />
							<strong><?php esc_html_e( 'Blocklist', 'zillha-subscriber-gate' ); ?></strong>
							&mdash;
							<?php esc_html_e( 'all pages are restricted except listed slugs.', 'zillha-subscriber-gate' ); ?>
						</label>
					</div>
					<p class="zsg-mode-help">
						<?php
						echo wp_kses(
							__( 'In blocklist mode, the slugs <code>login</code>, <code>register</code>, <code>subscribe</code>, and <code>home</code> are always allowed through to prevent lockouts. Administrators and editors are never blocked in either mode.', 'zillha-subscriber-gate' ),
							array( 'code' => array() )
						);
						?>
					</p>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Save Mode', 'zillha-subscriber-gate' ); ?>
						</button>
					</p>
				</form>
			</div>

			<div class="zsg-section">
				<h2><?php echo esc_html( $slug_section_h2 ); ?></h2>

				<?php if ( empty( $slugs ) ) : ?>
					<p><em><?php echo esc_html( $slug_empty_copy ); ?></em></p>
				<?php else : ?>
					<table class="zsg-slug-list widefat striped">
						<tbody>
						<?php foreach ( $slugs as $slug ) : ?>
							<tr>
								<td><code><?php echo esc_html( $slug ); ?></code></td>
								<td style="text-align:right;">
									<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="zsg_remove_slug" />
										<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>" />
										<?php wp_nonce_field( 'zsg_remove_slug_nonce', 'zsg_remove_slug_nonce' ); ?>
										<button type="submit" class="button button-secondary">
											<?php esc_html_e( 'Remove', 'zillha-subscriber-gate' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="zsg-add-form">
					<input type="hidden" name="action" value="zsg_add_slug" />
					<?php wp_nonce_field( 'zsg_add_slug_nonce', 'zsg_add_slug_nonce' ); ?>
					<input
						type="text"
						name="slug"
						placeholder="<?php esc_attr_e( 'enter-page-slug', 'zillha-subscriber-gate' ); ?>"
						required
					/>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Add Slug', 'zillha-subscriber-gate' ); ?>
					</button>
					<p class="description">
						<?php
						if ( $is_blocklist ) {
							echo wp_kses(
								__( 'Slugs added here become exceptions that remain publicly accessible. Example: for <code>/worlds/darkwood/</code> enter <code>darkwood</code>.', 'zillha-subscriber-gate' ),
								array( 'code' => array() )
							);
						} else {
							echo wp_kses(
								__( 'Enter the page slug exactly as it appears in the URL. Example: for <code>/worlds/darkwood/</code> enter <code>darkwood</code>.', 'zillha-subscriber-gate' ),
								array( 'code' => array() )
							);
						}
						?>
					</p>
				</form>
			</div>

			<div class="zsg-section">
				<h2><?php esc_html_e( 'Redirect URL', 'zillha-subscriber-gate' ); ?></h2>
				<form method="post" action="<?php echo esc_url( $action_url ); ?>">
					<input type="hidden" name="action" value="zsg_save_redirect" />
					<?php wp_nonce_field( 'zsg_redirect_url_nonce', 'zsg_redirect_url_nonce' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="zsg_redirect_url">
									<?php esc_html_e( 'Redirect non-subscribers to:', 'zillha-subscriber-gate' ); ?>
								</label>
							</th>
							<td>
								<input
									type="url"
									id="zsg_redirect_url"
									name="redirect_url"
									class="regular-text"
									value="<?php echo esc_attr( $redirect_url ); ?>"
								/>
								<p class="description">
									<?php esc_html_e( 'Users who are logged in but not subscribers will be sent here.', 'zillha-subscriber-gate' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Save', 'zillha-subscriber-gate' ); ?>
						</button>
					</p>
				</form>
			</div>

			<hr />

			<div class="zsg-section">
				<h2><?php esc_html_e( 'Age Gate', 'zillha-subscriber-gate' ); ?></h2>
				<p class="zsg-mode-help">
					<?php esc_html_e( 'When enabled, visitors to the listed pages will see a date-of-birth verification modal on their first visit. Administrators and editors always bypass this check. A 1-year cookie is set on confirmation.', 'zillha-subscriber-gate' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( $action_url ); ?>">
					<input type="hidden" name="action" value="zsg_age_gate_settings_save" />
					<?php wp_nonce_field( 'zsg_age_gate_settings_nonce', 'zsg_age_gate_settings_nonce' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Enable Age Gate', 'zillha-subscriber-gate' ); ?>
							</th>
							<td>
								<label>
									<input
										type="checkbox"
										name="zsg_age_gate_enabled"
										value="1"
										<?php checked( $age_enabled, true ); ?>
									/>
									<?php esc_html_e( 'Require date-of-birth verification on listed pages.', 'zillha-subscriber-gate' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="zsg_age_gate_redirect_url">
									<?php esc_html_e( 'Redirect under-18 visitors to:', 'zillha-subscriber-gate' ); ?>
								</label>
							</th>
							<td>
								<input
									type="url"
									id="zsg_age_gate_redirect_url"
									name="zsg_age_gate_redirect_url"
									class="regular-text"
									value="<?php echo esc_attr( $age_redirect_url ); ?>"
								/>
								<p class="description">
									<?php esc_html_e( 'Visitors who decline or report being under 18 will be sent here.', 'zillha-subscriber-gate' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Save Age Gate Settings', 'zillha-subscriber-gate' ); ?>
						</button>
					</p>
				</form>
			</div>

			<div class="zsg-section">
				<h2><?php esc_html_e( 'Age-Gated Slugs', 'zillha-subscriber-gate' ); ?></h2>

				<?php if ( empty( $age_slugs ) ) : ?>
					<p><em><?php esc_html_e( 'No pages are currently age-gated.', 'zillha-subscriber-gate' ); ?></em></p>
				<?php else : ?>
					<table class="zsg-slug-list widefat striped">
						<tbody>
						<?php foreach ( $age_slugs as $age_slug ) : ?>
							<tr>
								<td><code><?php echo esc_html( $age_slug ); ?></code></td>
								<td style="text-align:right;">
									<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="zsg_remove_age_slug" />
										<input type="hidden" name="slug" value="<?php echo esc_attr( $age_slug ); ?>" />
										<?php wp_nonce_field( 'zsg_remove_age_slug_nonce', 'zsg_remove_age_slug_nonce' ); ?>
										<button type="submit" class="button button-secondary">
											<?php esc_html_e( 'Remove', 'zillha-subscriber-gate' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="zsg-add-form">
					<input type="hidden" name="action" value="zsg_add_age_slug" />
					<?php wp_nonce_field( 'zsg_add_age_slug_nonce', 'zsg_add_age_slug_nonce' ); ?>
					<input
						type="text"
						name="slug"
						placeholder="<?php esc_attr_e( 'enter-page-slug', 'zillha-subscriber-gate' ); ?>"
						required
					/>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Add Age-Gated Slug', 'zillha-subscriber-gate' ); ?>
					</button>
					<p class="description">
						<?php
						echo wp_kses(
							__( 'Pages listed here show a date-of-birth modal on first visit. Example: for <code>/worlds/darkwood/</code> enter <code>darkwood</code>.', 'zillha-subscriber-gate' ),
							array( 'code' => array() )
						);
						?>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle admin-post form submissions for add/remove slug and redirect URL.
	 *
	 * @return void
	 */
	public function handle_forms() {
		if ( empty( $_POST['action'] ) || ! is_admin() ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['action'] ) );

		switch ( $action ) {
			case 'zsg_add_slug':
				$this->process_add_slug();
				break;
			case 'zsg_remove_slug':
				$this->process_remove_slug();
				break;
			case 'zsg_save_redirect':
				$this->process_save_redirect();
				break;
			case 'zsg_mode_save':
				$this->process_save_mode();
				break;
			case 'zsg_age_gate_settings_save':
				$this->process_save_age_gate_settings();
				break;
			case 'zsg_add_age_slug':
				$this->process_add_age_slug();
				break;
			case 'zsg_remove_age_slug':
				$this->process_remove_age_slug();
				break;
			default:
				return;
		}
	}

	/**
	 * Save the operating mode option (allowlist or blocklist).
	 *
	 * @return void
	 */
	private function process_save_mode() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_mode_save_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_mode_save_nonce'] ) ), 'zsg_mode_save_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$mode = isset( $_POST['zsg_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['zsg_mode'] ) ) : 'allowlist';
		if ( ! in_array( $mode, array( 'allowlist', 'blocklist' ), true ) ) {
			$mode = 'allowlist';
		}

		update_option( 'zsg_mode', $mode );
		$this->set_notice(
			'blocklist' === $mode
				? __( 'Mode saved: Blocklist. All pages are restricted except listed slugs.', 'zillha-subscriber-gate' )
				: __( 'Mode saved: Allowlist. Only listed slugs are restricted.', 'zillha-subscriber-gate' ),
			'success'
		);
		$this->redirect_back();
	}

	/**
	 * Add a slug to the restricted list.
	 *
	 * @return void
	 */
	private function process_add_slug() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_add_slug_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_add_slug_nonce'] ) ), 'zsg_add_slug_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$raw  = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$slug = sanitize_title( $raw );

		if ( '' === $slug ) {
			$this->set_notice( __( 'Please enter a valid slug.', 'zillha-subscriber-gate' ), 'error' );
			$this->redirect_back();
		}

		$slugs = (array) get_option( 'zsg_restricted_slugs', array() );
		if ( in_array( $slug, $slugs, true ) ) {
			$this->set_notice(
				/* translators: %s: slug name. */
				sprintf( __( 'The slug "%s" is already restricted.', 'zillha-subscriber-gate' ), $slug ),
				'error'
			);
			$this->redirect_back();
		}

		$slugs[] = $slug;
		update_option( 'zsg_restricted_slugs', array_values( $slugs ) );
		$this->set_notice(
			/* translators: %s: slug name. */
			sprintf( __( 'Added "%s" to restricted slugs.', 'zillha-subscriber-gate' ), $slug ),
			'success'
		);
		$this->redirect_back();
	}

	/**
	 * Remove a slug from the restricted list.
	 *
	 * @return void
	 */
	private function process_remove_slug() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_remove_slug_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_remove_slug_nonce'] ) ), 'zsg_remove_slug_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$raw    = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$target = sanitize_title( $raw );

		if ( '' === $target ) {
			$this->set_notice( __( 'Invalid slug.', 'zillha-subscriber-gate' ), 'error' );
			$this->redirect_back();
		}

		$slugs    = (array) get_option( 'zsg_restricted_slugs', array() );
		$filtered = array_values(
			array_filter(
				$slugs,
				static function ( $s ) use ( $target ) {
					return $s !== $target;
				}
			)
		);
		update_option( 'zsg_restricted_slugs', $filtered );
		$this->set_notice(
			/* translators: %s: slug name. */
			sprintf( __( 'Removed "%s" from restricted slugs.', 'zillha-subscriber-gate' ), $target ),
			'success'
		);
		$this->redirect_back();
	}

	/**
	 * Save the redirect URL option.
	 *
	 * @return void
	 */
	private function process_save_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_redirect_url_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_redirect_url_nonce'] ) ), 'zsg_redirect_url_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$raw = isset( $_POST['redirect_url'] ) ? wp_unslash( $_POST['redirect_url'] ) : '';
		$url = esc_url_raw( trim( $raw ) );

		if ( '' === $url ) {
			$this->set_notice( __( 'Please enter a valid URL.', 'zillha-subscriber-gate' ), 'error' );
			$this->redirect_back();
		}

		update_option( 'zsg_redirect_url', $url );
		$this->set_notice( __( 'Redirect URL saved.', 'zillha-subscriber-gate' ), 'success' );
		$this->redirect_back();
	}

	/**
	 * Save the age gate settings: enabled flag and under-18 redirect URL.
	 *
	 * @return void
	 */
	private function process_save_age_gate_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_age_gate_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_age_gate_settings_nonce'] ) ), 'zsg_age_gate_settings_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$enabled = ! empty( $_POST['zsg_age_gate_enabled'] );
		update_option( 'zsg_age_gate_enabled', $enabled );

		$raw = isset( $_POST['zsg_age_gate_redirect_url'] ) ? wp_unslash( $_POST['zsg_age_gate_redirect_url'] ) : '';
		$url = esc_url_raw( trim( $raw ) );
		if ( '' === $url ) {
			$url = home_url();
		}
		update_option( 'zsg_age_gate_redirect_url', $url );

		$this->set_notice( __( 'Age gate settings saved.', 'zillha-subscriber-gate' ), 'success' );
		$this->redirect_back();
	}

	/**
	 * Add a slug to the age-gated list.
	 *
	 * @return void
	 */
	private function process_add_age_slug() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_add_age_slug_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_add_age_slug_nonce'] ) ), 'zsg_add_age_slug_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$raw  = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$slug = sanitize_title( $raw );

		if ( '' === $slug ) {
			$this->set_notice( __( 'Please enter a valid slug.', 'zillha-subscriber-gate' ), 'error' );
			$this->redirect_back();
		}

		$slugs = (array) get_option( 'zsg_age_gate_slugs', array() );
		if ( in_array( $slug, $slugs, true ) ) {
			// Silently reject duplicates per spec; show a neutral confirmation.
			$this->set_notice(
				/* translators: %s: slug name. */
				sprintf( __( 'The slug "%s" is already age-gated.', 'zillha-subscriber-gate' ), $slug ),
				'success'
			);
			$this->redirect_back();
		}

		$slugs[] = $slug;
		update_option( 'zsg_age_gate_slugs', array_values( $slugs ) );
		$this->set_notice(
			/* translators: %s: slug name. */
			sprintf( __( 'Added "%s" to age-gated slugs.', 'zillha-subscriber-gate' ), $slug ),
			'success'
		);
		$this->redirect_back();
	}

	/**
	 * Remove a slug from the age-gated list.
	 *
	 * @return void
	 */
	private function process_remove_age_slug() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'zillha-subscriber-gate' ) );
		}
		if ( ! isset( $_POST['zsg_remove_age_slug_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zsg_remove_age_slug_nonce'] ) ), 'zsg_remove_age_slug_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zillha-subscriber-gate' ) );
		}

		$raw    = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$target = sanitize_title( $raw );

		if ( '' === $target ) {
			$this->set_notice( __( 'Invalid slug.', 'zillha-subscriber-gate' ), 'error' );
			$this->redirect_back();
		}

		$slugs    = (array) get_option( 'zsg_age_gate_slugs', array() );
		$filtered = array_values(
			array_filter(
				$slugs,
				static function ( $s ) use ( $target ) {
					return $s !== $target;
				}
			)
		);
		update_option( 'zsg_age_gate_slugs', $filtered );
		$this->set_notice(
			/* translators: %s: slug name. */
			sprintf( __( 'Removed "%s" from age-gated slugs.', 'zillha-subscriber-gate' ), $target ),
			'success'
		);
		$this->redirect_back();
	}

	/**
	 * Store a user-scoped admin notice in a transient.
	 *
	 * @param string $message Notice message.
	 * @param string $type    'success' or 'error'.
	 * @return void
	 */
	private function set_notice( $message, $type = 'success' ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		set_transient(
			'zsg_admin_notice_' . $user_id,
			array(
				'message' => $message,
				'type'    => 'error' === $type ? 'error' : 'success',
			),
			60
		);
	}

	/**
	 * Render any pending admin notice for the current user.
	 *
	 * @return void
	 */
	public function render_notices() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$key    = 'zsg_admin_notice_' . $user_id;
		$notice = get_transient( $key );
		if ( empty( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		delete_transient( $key );
		$class = 'error' === $notice['type'] ? 'notice-error' : 'notice-success';
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Redirect back to the settings page and exit.
	 *
	 * @return void
	 */
	private function redirect_back() {
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}
