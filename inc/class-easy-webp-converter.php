<?php
/**
 * Easy & Simple WebP Converter - main class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Easy_WebP_Converter {

	/**
	 * Image mime types that can be converted to WebP.
	 *
	 * @var array
	 */
	protected $supported_mimes = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/bmp',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_action( 'wp_ajax_easy_webp_convert', array( $this, 'handle_convert' ) );
		add_filter( 'wp_handle_upload', array( $this, 'maybe_auto_convert_upload' ), 10, 2 );
	}

	/**
	 * Register the sidebar menu item.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Easy & Simple WebP Converter', 'easy-webp-converter' ),
			__( 'WebP Converter', 'easy-webp-converter' ),
			'manage_options',
			'easy-webp-converter',
			array( $this, 'render_page' ),
			'dashicons-format-gallery',
			26
		);
	}

	/**
	 * Register the quality and auto-convert settings.
	 */
	public function register_settings() {
		register_setting(
			'easy_webp_settings',
			EASYWEBP_OPTION_QUALITY,
			array(
				'type'              => 'integer',
				'default'           => 80,
				'sanitize_callback' => array( $this, 'sanitize_quality' ),
			)
		);

		register_setting(
			'easy_webp_settings',
			EASYWEBP_OPTION_AUTOCONVERT,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			)
		);
	}

	/**
	 * Sanitize the quality value to an integer between 0 and 100.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_quality( $value ) {
		$quality = (int) $value;
		return min( 100, max( 0, $quality ) );
	}

	/**
	 * Get the current quality setting.
	 *
	 * @return int
	 */
	public function get_quality() {
		return $this->sanitize_quality( get_option( EASYWEBP_OPTION_QUALITY, 80 ) );
	}

	/**
	 * Sanitize a checkbox value to a boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	/**
	 * Whether automatic conversion of new uploads is enabled.
	 *
	 * @return bool
	 */
	public function get_autoconvert() {
		return (bool) get_option( EASYWEBP_OPTION_AUTOCONVERT, false );
	}

	/**
	 * Enqueue admin assets only on the plugin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_easy-webp-converter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'easy-webp-admin',
			EASYWEBP_ROOT_URL . '/assets/css/admin.css',
			array(),
			EASYWEBP_VERSION
		);

		wp_enqueue_script(
			'easy-webp-admin',
			EASYWEBP_ROOT_URL . '/assets/js/admin.js',
			array( 'jquery' ),
			EASYWEBP_VERSION,
			true
		);

		wp_localize_script(
			'easy-webp-admin',
			'easyWebp',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'easy_webp_convert' ),
				'batchSize' => EASYWEBP_BATCH_SIZE,
				'i18n'      => array(
					'starting'      => __( 'Starting conversion...', 'easy-webp-converter' ),
					'converting'    => __( 'Converting images...', 'easy-webp-converter' ),
					'done'          => __( 'Conversion complete.', 'easy-webp-converter' ),
					'error'         => __( 'Conversion failed. Please try again.', 'easy-webp-converter' ),
					'noImages'      => __( 'No images to convert.', 'easy-webp-converter' ),
					'remaining'     => __( 'remaining', 'easy-webp-converter' ),
					'converted'     => __( 'Converted', 'easy-webp-converter' ),
					'skipped'       => __( 'Skipped', 'easy-webp-converter' ),
					'failed'        => __( 'Failed', 'easy-webp-converter' ),
				),
			)
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_page() {
		$webp_supported = $this->is_webp_supported();
		$pending        = $this->get_pending_ids();
		?>
		<div class="wrap easy-webp-wrap">
			<h1><?php esc_html_e( 'Easy & Simple WebP Converter', 'easy-webp-converter' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Convert every image in your media library to WebP with a single click. Original files are replaced by WebP versions after a successful conversion.', 'easy-webp-converter' ); ?>
			</p>

			<?php if ( ! $webp_supported ) : ?>
				<div class="notice notice-error inline">
					<p>
						<strong><?php esc_html_e( 'WebP is not available on this server.', 'easy-webp-converter' ); ?></strong>
						<?php esc_html_e( 'This plugin requires PHP with GD (or Imagick) compiled with WebP support. Please contact your host to enable it.', 'easy-webp-converter' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="easy-webp-card">
				<h2><?php esc_html_e( 'Settings', 'easy-webp-converter' ); ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'easy_webp_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="easy-webp-quality"><?php esc_html_e( 'WebP quality', 'easy-webp-converter' ); ?></label>
							</th>
							<td>
								<input type="range" id="easy-webp-quality-range" min="0" max="100" step="1" value="<?php echo esc_attr( $this->get_quality() ); ?>" data-target="#easy-webp-quality" />
								<input type="number" id="easy-webp-quality" name="<?php echo esc_attr( EASYWEBP_OPTION_QUALITY ); ?>" class="small-text" min="0" max="100" step="1" value="<?php echo esc_attr( $this->get_quality() ); ?>" />
								<p class="description">
									<?php esc_html_e( 'Higher values produce better quality but larger files. 0 is the lowest quality, 100 the highest.', 'easy-webp-converter' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="easy-webp-autoconvert"><?php esc_html_e( 'Auto-convert new uploads', 'easy-webp-converter' ); ?></label>
							</th>
							<td>
								<label for="easy-webp-autoconvert">
									<input type="checkbox" id="easy-webp-autoconvert" name="<?php echo esc_attr( EASYWEBP_OPTION_AUTOCONVERT ); ?>" value="1" <?php checked( $this->get_autoconvert() ); ?> />
									<?php esc_html_e( 'Automatically convert newly added images to WebP using the selected quality.', 'easy-webp-converter' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When enabled, every new image uploaded to the media library is converted to WebP as soon as it is added.', 'easy-webp-converter' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save settings', 'easy-webp-converter' ) ); ?>
				</form>
			</div>

			<div class="easy-webp-card">
				<h2><?php esc_html_e( 'Convert media library', 'easy-webp-converter' ); ?></h2>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: quality value. */
							__( 'Convert all media images to WebP using the selected quality (%d).', 'easy-webp-converter' ),
							$this->get_quality()
						)
					);
					?>
				</p>
				<?php if ( $webp_supported ) : ?>
					<button type="button" class="button button-primary button-hero" id="easy-webp-convert-button" <?php echo ! empty( $pending ) ? 'data-resume="1"' : ''; ?>>
						<?php esc_html_e( 'Convert all images to WebP', 'easy-webp-converter' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="button button-primary button-hero" disabled>
						<?php esc_html_e( 'Convert all images to WebP', 'easy-webp-converter' ); ?>
					</button>
				<?php endif; ?>

				<div id="easy-webp-progress-wrap" style="display:none;">
					<div class="easy-webp-progress">
						<div class="easy-webp-progress-bar" id="easy-webp-progress-bar"></div>
					</div>
					<p class="easy-webp-status" id="easy-webp-status"></p>
					<p class="easy-webp-stats" id="easy-webp-stats"></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render an admin notice when a conversion is in progress or errored.
	 */
	public function render_admin_notices() {
		$pending = $this->get_pending_ids();

		if ( ! empty( $pending ) && get_current_screen()->id === 'toplevel_page_easy-webp-converter' ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: number of remaining images. */
					__( 'A WebP conversion is in progress (%d images remaining). Click the convert button to continue.', 'easy-webp-converter' ),
					count( $pending )
				)
			);
			echo '</p></div>';
		}

		if ( get_transient( 'easy_webp_notice' ) ) {
			$notice = get_transient( 'easy_webp_notice' );
			$type   = isset( $notice['type'] ) ? $notice['type'] : 'updated';
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
			delete_transient( 'easy_webp_notice' );
		}
	}

	/**
	 * Whether the server can produce WebP images.
	 *
	 * @return bool
	 */
	public function is_webp_supported() {
		if ( class_exists( 'Imagick' ) && in_array( 'WEBP', \Imagick::queryFormats( 'WEBP' ), true ) ) {
			return true;
		}

		if ( function_exists( 'imagewebp' ) ) {
			$info = gd_info();
			return ! empty( $info['WebP Support'] );
		}

		return false;
	}

	/**
	 * Automatically convert a newly uploaded image to WebP when enabled.
	 *
	 * Hooks into the `wp_handle_upload` filter, which is fired for both the
	 * classic Media Library and the REST API media uploads. Returns the upload
	 * data unchanged when auto-conversion is disabled or the file is not an
	 * eligible image.
	 *
	 * @param array  $upload  Uploaded file data (file, url, type).
	 * @param string $context Upload context ('upload' or 'sideload').
	 * @return array
	 */
	public function maybe_auto_convert_upload( $upload, $context ) {
		if ( ! $this->get_autoconvert() ) {
			return $upload;
		}

		if ( empty( $upload['file'] ) || empty( $upload['type'] ) || ! file_exists( $upload['file'] ) ) {
			return $upload;
		}

		if ( ! in_array( $upload['type'], $this->supported_mimes, true ) ) {
			return $upload;
		}

		$extension = strtolower( pathinfo( $upload['file'], PATHINFO_EXTENSION ) );

		if ( 'webp' === $extension ) {
			return $upload;
		}

		$result = $this->convert_file_to_webp( $upload['file'] );

		if ( empty( $result ) ) {
			return $upload;
		}

		return array(
			'file' => $result['file'],
			'url'  => str_replace( wp_basename( $upload['file'] ), wp_basename( $result['file'] ), $upload['url'] ),
			'type' => 'image/webp',
		);
	}

	/**
	 * Convert a single image file to WebP on disk.
	 *
	 * @param string $file Full path to the source image.
	 * @return array|false Array with the new 'file' path, or false on failure.
	 */
	protected function convert_file_to_webp( $file ) {
		if ( ! $this->is_webp_supported() || ! file_exists( $file ) || ! is_readable( $file ) ) {
			return false;
		}

		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$editor->set_quality( $this->get_quality() );

		$webp_file = preg_replace( '/\.(jpe?g|png|gif|bmp)$/i', '.webp', $file );

		if ( ! $webp_file || $webp_file === $file ) {
			return false;
		}

		$saved = $editor->save( $webp_file, 'image/webp' );

		if ( is_wp_error( $saved ) ) {
			return false;
		}

		wp_delete_file( $file );

		return array( 'file' => $saved['path'] );
	}

	/**
	 * Get attachment IDs eligible for conversion.
	 *
	 * @return int[]
	 */
	protected function get_candidate_ids() {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => $this->supported_mimes,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return $query->posts;
	}

	/**
	 * Get the pending attachment IDs.
	 *
	 * @return int[]
	 */
	protected function get_pending_ids() {
		$pending = get_option( EASYWEBP_OPTION_PENDING, array() );
		return is_array( $pending ) ? $pending : array();
	}

	/**
	 * AJAX handler for batch conversion.
	 */
	public function handle_convert() {
		check_ajax_referer( 'easy_webp_convert', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'easy-webp-converter' ) ) );
		}

		if ( ! $this->is_webp_supported() ) {
			wp_send_json_error( array( 'message' => __( 'WebP is not supported on this server.', 'easy-webp-converter' ) ) );
		}

		$pending  = $this->get_pending_ids();
		$starting = false;

		if ( empty( $pending ) ) {
			$pending = $this->get_candidate_ids();

			if ( empty( $pending ) ) {
				wp_send_json_success(
					array(
						'done'      => true,
						'converted' => 0,
						'failed'    => 0,
						'skipped'   => 0,
						'remaining' => 0,
						'total'     => 0,
					)
				);
			}

			$starting = true;
			update_option( EASYWEBP_OPTION_TOTAL, count( $pending ) );
		}

		$total = (int) get_option( EASYWEBP_OPTION_TOTAL, count( $pending ) );

		$batch  = array_splice( $pending, 0, EASYWEBP_BATCH_SIZE );
		$result = $this->process_batch( $batch );

		if ( empty( $pending ) ) {
			delete_option( EASYWEBP_OPTION_PENDING );
			delete_option( EASYWEBP_OPTION_TOTAL );

			set_transient(
				'easy_webp_notice',
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: converted count, 2: skipped count, 3: failed count. */
						__( 'WebP conversion finished: %1$d converted, %2$d skipped, %3$d failed.', 'easy-webp-converter' ),
						$result['converted'],
						$result['skipped'],
						$result['failed']
					),
				),
				60
			);
		} else {
			update_option( EASYWEBP_OPTION_PENDING, $pending );
		}

		wp_send_json_success(
			array(
				'done'      => empty( $pending ),
				'converted' => $result['converted'],
				'failed'    => $result['failed'],
				'skipped'   => $result['skipped'],
				'remaining' => count( $pending ),
				'total'     => $total,
				'starting'  => $starting,
			)
		);
	}

	/**
	 * Process a batch of attachments.
	 *
	 * @param int[] $ids Attachment IDs.
	 * @return array
	 */
	protected function process_batch( $ids ) {
		$converted = 0;
		$failed    = 0;
		$skipped   = 0;

		foreach ( $ids as $id ) {
			$result = $this->convert_attachment( $id );

			switch ( $result['status'] ) {
				case 'converted':
					$converted++;
					break;
				case 'failed':
					$failed++;
					break;
				default:
					$skipped++;
			}
		}

		return array(
			'converted' => $converted,
			'failed'    => $failed,
			'skipped'   => $skipped,
		);
	}

	/**
	 * Convert a single attachment to WebP.
	 *
	 * @param int $id Attachment ID.
	 * @return array
	 */
	protected function convert_attachment( $id ) {
		$file = get_attached_file( $id );

		if ( ! $file || ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array( 'status' => 'skipped' );
		}

		$mime = get_post_mime_type( $id );

		if ( ! in_array( $mime, $this->supported_mimes, true ) ) {
			return array( 'status' => 'skipped' );
		}

		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

		if ( 'webp' === $extension || 'image/webp' === $mime ) {
			return array( 'status' => 'skipped' );
		}

		$old_meta = wp_get_attachment_metadata( $id );

		$webp_file = $this->convert_file_to_webp( $file );

		if ( empty( $webp_file ) ) {
			return array( 'status' => 'failed' );
		}

		$webp_file = $webp_file['file'];

		update_attached_file( $id, _wp_relative_upload_path( $webp_file ) );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$meta = wp_generate_attachment_metadata( $id, $webp_file );
		wp_update_attachment_metadata( $id, $meta );

		wp_update_post(
			array(
				'ID'             => $id,
				'post_mime_type' => 'image/webp',
			)
		);

		$this->delete_old_image_files( $file, $old_meta );

		return array( 'status' => 'converted' );
	}

	/**
	 * Delete the original file and its previously generated size variants.
	 *
	 * @param string $file     Full path to the original file.
	 * @param array  $old_meta The previous attachment metadata.
	 */
	protected function delete_old_image_files( $file, $old_meta ) {
		$files = array( $file );

		if ( ! empty( $old_meta ) && is_array( $old_meta ) ) {
			$upload_dir = wp_upload_dir();

			if ( ! empty( $old_meta['sizes'] ) && is_array( $old_meta['sizes'] ) ) {
				foreach ( $old_meta['sizes'] as $size ) {
					if ( ! empty( $size['file'] ) ) {
						$files[] = trailingslashit( $upload_dir['path'] ) . $size['file'];
					}
				}
			}

			if ( ! empty( $old_meta['file'] ) ) {
				$files[] = trailingslashit( $upload_dir['basedir'] ) . $old_meta['file'];
			}
		}

		foreach ( array_unique( $files ) as $path ) {
			if ( $path !== $file && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}

		wp_delete_file( $file );
	}
}
