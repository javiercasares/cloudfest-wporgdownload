<?php
/**
 * Admin Dashboard Template
 *
 * Template for the main WPInsight dashboard page.
 * Displays statistics, sync status, and download queue information.
 *
 * Available variables:
 * - $plugin_count: Number of plugin posts.
 * - $theme_count: Number of theme posts.
 * - $artifact_count: Number of downloaded artifacts.
 * - $storage_size: Total storage size in bytes.
 * - $plugin_state: Plugin sync state array.
 * - $theme_state: Theme sync state array.
 * - $queue_stats: Download queue statistics.
 * - $plugin_progress: Plugin sync progress data (Phase 18.3).
 * - $theme_progress: Theme sync progress data (Phase 18.3).
 * - $active_downloads: Active downloads array (Phase 18.4).
 * - $disk_space: Disk space information array (Phase 18.4).
 * - $downloads_paused: Boolean flag for paused downloads (Phase 18.4).
 * - $system_health: System health check data (Phase 18.6).
 * - $overall_health: Overall health status summary (Phase 18.6).
 * - $api_health: API health monitor data (Phase 18.5).
 * - $recent_logs: Recent error logs array.
 * - $admin: WPInsight_Admin class for helper methods.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Templates
 * @since      0.1.0
 *
 * @phpstan-var class-string<WPInsight_Admin> $admin
 * @phpstan-var int $plugin_count
 * @phpstan-var int $theme_count
 * @phpstan-var int|string $artifact_count
 * @phpstan-var int $storage_size
 * @phpstan-var array{status: string, page: int, last_error: string, updated_at: string} $plugin_state
 * @phpstan-var array{status: string, page: int, last_error: string, updated_at: string} $theme_state
 * @phpstan-var array{total: int, pending: int, processing: int, completed: int, failed: int} $queue_stats
 * @phpstan-var array<int, array{id: int, severity: string, message: string, created_at: string, context: string}> $recent_logs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'wpinsight_dashboard' ); ?>

	<!-- Statistics Overview -->
	<div class="wpinsight-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
		<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( $admin::format_number_abbreviated( $plugin_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $plugin_count ) ); ?>"><?php echo esc_html( number_format_i18n( $plugin_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( $admin::format_number_abbreviated( $theme_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $theme_count ) ); ?>"><?php echo esc_html( number_format_i18n( $theme_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Downloaded ZIPs', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( $admin::format_number_abbreviated( $artifact_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $artifact_count ) ); ?>"><?php echo esc_html( number_format_i18n( $artifact_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Storage Used', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( (string) size_format( $storage_size, 2 ) ); ?></div>
		</div>
	</div>

	<!-- Sync Progress Dashboard (Phase 18.3) -->
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<h2 style="margin: 0 0 15px 0; font-size: 18px;">
			<?php esc_html_e( 'Sync Progress', 'cloudfest-wporgdownload' ); ?>
		</h2>

		<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
			<!-- Plugin Sync Progress -->
			<div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
					<h3 style="margin: 0; font-size: 14px;">
						<?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?>
					</h3>
					<span style="padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; background: <?php echo esc_attr( $plugin_progress['status_color'] ); ?>; color: #fff;">
						<?php echo esc_html( $plugin_progress['status_label'] ); ?>
					</span>
				</div>

				<!-- Progress Bar -->
				<div style="background: #f0f0f1; border-radius: 4px; height: 30px; position: relative; margin-bottom: 10px; overflow: hidden;">
					<div style="background: linear-gradient(90deg, #2271b1 0%, #135e96 100%); height: 100%; width: <?php echo esc_attr( $plugin_progress['progress_percent'] ); ?>%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center;">
						<span style="color: #fff; font-size: 12px; font-weight: 600; position: relative; z-index: 2;">
							<?php echo esc_html( $plugin_progress['progress_percent'] ); ?>%
						</span>
					</div>
				</div>

				<!-- Stats Grid -->
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 12px;">
					<div>
						<span style="color: #646970;"><?php esc_html_e( 'Synced:', 'cloudfest-wporgdownload' ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $plugin_progress['synced_items'] ) ); ?></strong>
						<span style="color: #646970;">/ <?php echo esc_html( number_format_i18n( $plugin_progress['total_items'] ) ); ?></span>
					</div>
					<?php if ( $plugin_progress['eta_formatted'] ) : ?>
						<div>
							<span style="color: #646970;"><?php esc_html_e( 'ETA:', 'cloudfest-wporgdownload' ); ?></span>
							<strong><?php echo esc_html( $plugin_progress['eta_formatted'] ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $plugin_progress['last_sync'] ) : ?>
						<div>
							<span style="color: #646970;"><?php esc_html_e( 'Last Sync:', 'cloudfest-wporgdownload' ); ?></span>
							<strong><?php echo esc_html( $plugin_progress['last_sync'] ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $plugin_progress['error_count'] > 0 ) : ?>
						<div>
							<span style="color: #d63638;"><?php esc_html_e( 'Errors:', 'cloudfest-wporgdownload' ); ?></span>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinsight-dashboard#error-logs' ) ); ?>" style="color: #d63638; text-decoration: none; font-weight: 600;">
								<?php echo esc_html( number_format_i18n( $plugin_progress['error_count'] ) ); ?> ⚠
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Theme Sync Progress -->
			<div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
					<h3 style="margin: 0; font-size: 14px;">
						<?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?>
					</h3>
					<span style="padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; background: <?php echo esc_attr( $theme_progress['status_color'] ); ?>; color: #fff;">
						<?php echo esc_html( $theme_progress['status_label'] ); ?>
					</span>
				</div>

				<!-- Progress Bar -->
				<div style="background: #f0f0f1; border-radius: 4px; height: 30px; position: relative; margin-bottom: 10px; overflow: hidden;">
					<div style="background: linear-gradient(90deg, #2271b1 0%, #135e96 100%); height: 100%; width: <?php echo esc_attr( $theme_progress['progress_percent'] ); ?>%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center;">
						<span style="color: #fff; font-size: 12px; font-weight: 600; position: relative; z-index: 2;">
							<?php echo esc_html( $theme_progress['progress_percent'] ); ?>%
						</span>
					</div>
				</div>

				<!-- Stats Grid -->
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 12px;">
					<div>
						<span style="color: #646970;"><?php esc_html_e( 'Synced:', 'cloudfest-wporgdownload' ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $theme_progress['synced_items'] ) ); ?></strong>
						<span style="color: #646970;">/ <?php echo esc_html( number_format_i18n( $theme_progress['total_items'] ) ); ?></span>
					</div>
					<?php if ( $theme_progress['eta_formatted'] ) : ?>
						<div>
							<span style="color: #646970;"><?php esc_html_e( 'ETA:', 'cloudfest-wporgdownload' ); ?></span>
							<strong><?php echo esc_html( $theme_progress['eta_formatted'] ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $theme_progress['last_sync'] ) : ?>
						<div>
							<span style="color: #646970;"><?php esc_html_e( 'Last Sync:', 'cloudfest-wporgdownload' ); ?></span>
							<strong><?php echo esc_html( $theme_progress['last_sync'] ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $theme_progress['error_count'] > 0 ) : ?>
						<div>
							<span style="color: #d63638;"><?php esc_html_e( 'Errors:', 'cloudfest-wporgdownload' ); ?></span>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinsight-dashboard#error-logs' ) ); ?>" style="color: #d63638; text-decoration: none; font-weight: 600;">
								<?php echo esc_html( number_format_i18n( $theme_progress['error_count'] ) ); ?> ⚠
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Combined Summary -->
		<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; font-size: 12px; text-align: center;">
				<div>
					<div style="color: #646970; margin-bottom: 5px;"><?php esc_html_e( 'Total Items', 'cloudfest-wporgdownload' ); ?></div>
					<div style="font-size: 20px; font-weight: 600; color: #2271b1;">
						<?php echo esc_html( number_format_i18n( $plugin_progress['synced_items'] + $theme_progress['synced_items'] ) ); ?>
					</div>
				</div>
				<div>
					<div style="color: #646970; margin-bottom: 5px;"><?php esc_html_e( 'Overall Progress', 'cloudfest-wporgdownload' ); ?></div>
					<div style="font-size: 20px; font-weight: 600; color: #2271b1;">
						<?php
						$total_synced = $plugin_progress['synced_items'] + $theme_progress['synced_items'];
						$total_items  = $plugin_progress['total_items'] + $theme_progress['total_items'];
						$overall_percent = $total_items > 0 ? round( ( $total_synced / $total_items ) * 100, 1 ) : 0;
						echo esc_html( $overall_percent );
						?>%
					</div>
				</div>
				<?php
				$total_errors = $plugin_progress['error_count'] + $theme_progress['error_count'];
				if ( $total_errors > 0 ) :
					?>
					<div>
						<div style="color: #d63638; margin-bottom: 5px;"><?php esc_html_e( 'Total Errors', 'cloudfest-wporgdownload' ); ?></div>
						<div style="font-size: 20px; font-weight: 600; color: #d63638;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinsight-dashboard#error-logs' ) ); ?>" style="color: #d63638; text-decoration: none;">
								<?php echo esc_html( number_format_i18n( $total_errors ) ); ?> ⚠
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Download Queue Monitor (Phase 18.4) -->
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid <?php echo $downloads_paused ? '#dba617' : '#00a32a'; ?>; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
			<h2 style="margin: 0; font-size: 18px;">
				<?php esc_html_e( 'Download Queue Monitor', 'cloudfest-wporgdownload' ); ?>
			</h2>
			<div style="display: flex; gap: 10px;">
				<?php if ( $downloads_paused ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'resume_downloads', 'page' => 'wpinsight-dashboard' ), admin_url( 'tools.php' ) ), 'wpinsight_download_control' ) ); ?>"
					   class="button button-primary"
					   style="background: #00a32a; border-color: #00a32a;">
						<?php esc_html_e( '▶ Resume Downloads', 'cloudfest-wporgdownload' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pause_downloads', 'page' => 'wpinsight-dashboard' ), admin_url( 'tools.php' ) ), 'wpinsight_download_control' ) ); ?>"
					   class="button"
					   style="background: #dba617; border-color: #dba617; color: #fff;">
						<?php esc_html_e( '⏸ Pause Downloads', 'cloudfest-wporgdownload' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $downloads_paused ) : ?>
			<div style="padding: 12px; background: #fcf3cf; border-left: 3px solid #dba617; border-radius: 4px; margin-bottom: 15px;">
				<strong style="color: #d97706;"><?php esc_html_e( '⏸ Downloads Paused', 'cloudfest-wporgdownload' ); ?></strong> -
				<?php esc_html_e( 'No new downloads will start until resumed.', 'cloudfest-wporgdownload' ); ?>
			</div>
		<?php endif; ?>

		<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
			<!-- Active Downloads -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Active Downloads', 'cloudfest-wporgdownload' ); ?>
					<span style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">
						<?php echo esc_html( count( $active_downloads ) ); ?>
					</span>
				</h3>

				<?php if ( empty( $active_downloads ) ) : ?>
					<div style="padding: 20px; text-align: center; background: #f6f7f7; border-radius: 4px; color: #646970;">
						<?php esc_html_e( 'No active downloads at the moment', 'cloudfest-wporgdownload' ); ?>
					</div>
				<?php else : ?>
					<table class="widefat" style="margin: 0;">
						<thead>
							<tr>
								<th style="padding: 8px;"><?php esc_html_e( 'ZIP File', 'cloudfest-wporgdownload' ); ?></th>
								<th style="padding: 8px; text-align: center;"><?php esc_html_e( 'Progress', 'cloudfest-wporgdownload' ); ?></th>
								<th style="padding: 8px; text-align: right;"><?php esc_html_e( 'Speed', 'cloudfest-wporgdownload' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $active_downloads as $download ) : ?>
								<tr>
									<td style="padding: 8px;">
										<strong><?php echo esc_html( $download['slug'] ); ?></strong>
										<span style="color: #646970; font-size: 11px;">.<?php echo esc_html( $download['version'] ); ?>.zip</span>
										<?php if ( $download['remote_filesize'] ) : ?>
											<div style="font-size: 11px; color: #646970;">
												<?php echo esc_html( size_format( $download['remote_filesize'], 2 ) ); ?>
											</div>
										<?php endif; ?>
									</td>
									<td style="padding: 8px; text-align: center;">
										<?php if ( null !== $download['progress_percent'] ) : ?>
											<div style="background: #f0f0f1; border-radius: 10px; height: 20px; position: relative; overflow: hidden;">
												<div style="background: #2271b1; height: 100%; width: <?php echo esc_attr( $download['progress_percent'] ); ?>%; transition: width 0.3s ease;"></div>
												<span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: 600; color: #1d2327;">
													<?php echo esc_html( round( $download['progress_percent'] ) ); ?>%
												</span>
											</div>
										<?php else : ?>
											<span style="color: #646970; font-size: 11px;">
												<?php
												/* translators: %s: elapsed time */
												echo esc_html( sprintf( __( '%s elapsed', 'cloudfest-wporgdownload' ), human_time_diff( time() - $download['elapsed_seconds'], time() ) ) );
												?>
											</span>
										<?php endif; ?>
									</td>
									<td style="padding: 8px; text-align: right;">
										<?php if ( null !== $download['estimated_speed'] ) : ?>
											<span style="font-weight: 600; color: #00a32a;">
												<?php echo esc_html( size_format( $download['estimated_speed'], 2 ) ); ?>/s
											</span>
										<?php else : ?>
											<span style="color: #646970;">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Disk Space -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Disk Space', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
					<!-- Disk Usage Progress Bar -->
					<div style="margin-bottom: 15px;">
						<div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
							<span style="color: #646970;"><?php esc_html_e( 'Used', 'cloudfest-wporgdownload' ); ?></span>
							<span style="font-weight: 600; color: #1d2327;"><?php echo esc_html( $disk_space['used_percent'] ); ?>%</span>
						</div>
						<div style="background: #dcdcde; border-radius: 10px; height: 10px; overflow: hidden;">
							<div style="background: <?php echo $disk_space['used_percent'] > 90 ? '#d63638' : ( $disk_space['used_percent'] > 75 ? '#dba617' : '#00a32a' ); ?>; height: 100%; width: <?php echo esc_attr( $disk_space['used_percent'] ); ?>%; transition: width 0.3s ease;"></div>
						</div>
					</div>

					<!-- Storage Stats -->
					<table style="width: 100%; font-size: 12px; margin: 0;">
						<tbody>
							<tr>
								<td style="padding: 4px 0; color: #646970;"><?php esc_html_e( 'Total:', 'cloudfest-wporgdownload' ); ?></td>
								<td style="padding: 4px 0; text-align: right; font-weight: 600;">
									<?php echo esc_html( size_format( $disk_space['total_space'], 2 ) ); ?>
								</td>
							</tr>
							<tr>
								<td style="padding: 4px 0; color: #646970;"><?php esc_html_e( 'Free:', 'cloudfest-wporgdownload' ); ?></td>
								<td style="padding: 4px 0; text-align: right; font-weight: 600; color: #00a32a;">
									<?php echo esc_html( size_format( $disk_space['free_space'], 2 ) ); ?>
								</td>
							</tr>
							<tr style="border-top: 1px solid #dcdcde;">
								<td style="padding: 4px 0; color: #646970;"><?php esc_html_e( 'Downloaded:', 'cloudfest-wporgdownload' ); ?></td>
								<td style="padding: 4px 0; text-align: right; font-weight: 600;">
									<?php echo esc_html( size_format( $disk_space['artifacts_size'], 2 ) ); ?>
								</td>
							</tr>
							<tr>
								<td style="padding: 4px 0; color: #646970;"><?php esc_html_e( 'Pending:', 'cloudfest-wporgdownload' ); ?></td>
								<td style="padding: 4px 0; text-align: right; font-weight: 600; color: #dba617;">
									<?php echo esc_html( size_format( $disk_space['pending_size'], 2 ) ); ?>
								</td>
							</tr>
							<tr style="border-top: 1px solid #dcdcde;">
								<td style="padding: 4px 0; color: #646970;"><strong><?php esc_html_e( 'Total Required:', 'cloudfest-wporgdownload' ); ?></strong></td>
								<td style="padding: 4px 0; text-align: right; font-weight: 600; color: #2271b1;">
									<?php echo esc_html( size_format( $disk_space['total_required'], 2 ) ); ?>
								</td>
							</tr>
						</tbody>
					</table>

					<?php if ( $disk_space['total_required'] > $disk_space['free_space'] ) : ?>
						<div style="margin-top: 10px; padding: 8px; background: #fcf3cf; border-left: 2px solid #dba617; font-size: 11px; color: #d97706; border-radius: 2px;">
							<strong>⚠ <?php esc_html_e( 'Warning:', 'cloudfest-wporgdownload' ); ?></strong>
							<?php esc_html_e( 'Not enough disk space for all pending downloads', 'cloudfest-wporgdownload' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- System Health Check (Phase 18.6) -->
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid <?php echo 'ok' === $overall_health['status'] ? '#00a32a' : ( 'warning' === $overall_health['status'] ? '#dba617' : '#d63638' ); ?>; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
			<h2 style="margin: 0; font-size: 18px;">
				<?php esc_html_e( 'System Health Check', 'cloudfest-wporgdownload' ); ?>
			</h2>
			<div style="display: flex; align-items: center; gap: 10px;">
				<?php if ( 'ok' === $overall_health['status'] ) : ?>
					<span style="padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; background: #00a32a; color: #fff;">
						✓ <?php echo esc_html( $overall_health['message'] ); ?>
					</span>
				<?php elseif ( 'warning' === $overall_health['status'] ) : ?>
					<span style="padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; background: #dba617; color: #fff;">
						⚠ <?php echo esc_html( $overall_health['message'] ); ?>
					</span>
				<?php else : ?>
					<span style="padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; background: #d63638; color: #fff;">
						✗ <?php echo esc_html( $overall_health['message'] ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
			<!-- Left Column: Software & Extensions -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Software Requirements', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<table class="widefat" style="margin: 0;">
					<tbody>
						<!-- PHP Version -->
						<tr>
							<td style="padding: 8px; width: 40%;">
								<strong><?php esc_html_e( 'PHP Version', 'cloudfest-wporgdownload' ); ?></strong>
							</td>
							<td style="padding: 8px;">
								<?php if ( 'ok' === $system_health['php_version']['status'] ) : ?>
									<span style="color: #00a32a; font-weight: 600;">✓</span>
								<?php else : ?>
									<span style="color: #d63638; font-weight: 600;">✗</span>
								<?php endif; ?>
								<?php echo esc_html( $system_health['php_version']['message'] ); ?>
							</td>
						</tr>

						<!-- WordPress Version -->
						<tr>
							<td style="padding: 8px;">
								<strong><?php esc_html_e( 'WordPress Version', 'cloudfest-wporgdownload' ); ?></strong>
							</td>
							<td style="padding: 8px;">
								<?php if ( 'ok' === $system_health['wp_version']['status'] ) : ?>
									<span style="color: #00a32a; font-weight: 600;">✓</span>
								<?php else : ?>
									<span style="color: #d63638; font-weight: 600;">✗</span>
								<?php endif; ?>
								<?php echo esc_html( $system_health['wp_version']['message'] ); ?>
							</td>
						</tr>

						<!-- Database Version -->
						<tr>
							<td style="padding: 8px;">
								<strong><?php esc_html_e( 'Database', 'cloudfest-wporgdownload' ); ?></strong>
							</td>
							<td style="padding: 8px;">
								<?php if ( 'ok' === $system_health['database']['status'] ) : ?>
									<span style="color: #00a32a; font-weight: 600;">✓</span>
								<?php elseif ( 'warning' === $system_health['database']['status'] ) : ?>
									<span style="color: #dba617; font-weight: 600;">⚠</span>
								<?php else : ?>
									<span style="color: #d63638; font-weight: 600;">✗</span>
								<?php endif; ?>
								<?php echo esc_html( $system_health['database']['message'] ); ?>
							</td>
						</tr>

						<!-- Action Scheduler -->
						<tr>
							<td style="padding: 8px;">
								<strong><?php esc_html_e( 'Action Scheduler', 'cloudfest-wporgdownload' ); ?></strong>
							</td>
							<td style="padding: 8px;">
								<?php if ( 'ok' === $system_health['action_scheduler']['status'] ) : ?>
									<span style="color: #00a32a; font-weight: 600;">✓</span>
								<?php else : ?>
									<span style="color: #d63638; font-weight: 600;">✗</span>
								<?php endif; ?>
								<?php echo esc_html( $system_health['action_scheduler']['message'] ); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<!-- PHP Extensions -->
				<h3 style="margin: 15px 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'PHP Extensions', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<table class="widefat" style="margin: 0;">
					<tbody>
						<?php foreach ( $system_health['extensions'] as $ext_name => $ext_data ) : ?>
							<tr>
								<td style="padding: 8px; width: 30%;">
									<code><?php echo esc_html( $ext_name ); ?></code>
								</td>
								<td style="padding: 8px;">
									<?php if ( 'ok' === $ext_data['status'] ) : ?>
										<span style="color: #00a32a; font-weight: 600;">✓</span>
									<?php elseif ( 'warning' === $ext_data['status'] ) : ?>
										<span style="color: #dba617; font-weight: 600;">⚠</span>
									<?php else : ?>
										<span style="color: #d63638; font-weight: 600;">✗</span>
									<?php endif; ?>
									<?php echo esc_html( $ext_data['message'] ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Right Column: Resources & Permissions -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'System Resources', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<!-- PHP Memory -->
				<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
						<strong style="font-size: 13px;">
							<?php esc_html_e( 'PHP Memory', 'cloudfest-wporgdownload' ); ?>
						</strong>
						<?php if ( 'ok' === $system_health['php_memory']['status'] ) : ?>
							<span style="color: #00a32a; font-weight: 600; font-size: 11px;">✓ OK</span>
						<?php elseif ( 'warning' === $system_health['php_memory']['status'] ) : ?>
							<span style="color: #dba617; font-weight: 600; font-size: 11px;">⚠ Warning</span>
						<?php else : ?>
							<span style="color: #d63638; font-weight: 600; font-size: 11px;">✗ Critical</span>
						<?php endif; ?>
					</div>
					<div style="font-size: 12px; color: #646970; margin-bottom: 8px;">
						<?php echo esc_html( $system_health['php_memory']['message'] ); ?>
					</div>
					<div style="background: #dcdcde; border-radius: 10px; height: 10px; overflow: hidden;">
						<div style="background: <?php echo 'ok' === $system_health['php_memory']['status'] ? '#00a32a' : ( 'warning' === $system_health['php_memory']['status'] ? '#dba617' : '#d63638' ); ?>; height: 100%; width: <?php echo esc_attr( $system_health['php_memory']['percent'] ); ?>%; transition: width 0.3s ease;"></div>
					</div>
				</div>

				<!-- Disk Space -->
				<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
						<strong style="font-size: 13px;">
							<?php esc_html_e( 'Disk Space', 'cloudfest-wporgdownload' ); ?>
						</strong>
						<?php if ( 'ok' === $system_health['disk_space']['status'] ) : ?>
							<span style="color: #00a32a; font-weight: 600; font-size: 11px;">✓ OK</span>
						<?php elseif ( 'warning' === $system_health['disk_space']['status'] ) : ?>
							<span style="color: #dba617; font-weight: 600; font-size: 11px;">⚠ Warning</span>
						<?php else : ?>
							<span style="color: #d63638; font-weight: 600; font-size: 11px;">✗ Critical</span>
						<?php endif; ?>
					</div>
					<div style="font-size: 12px; color: #646970; margin-bottom: 8px;">
						<?php echo esc_html( $system_health['disk_space']['message'] ); ?>
					</div>
					<div style="background: #dcdcde; border-radius: 10px; height: 10px; overflow: hidden;">
						<div style="background: <?php echo 'ok' === $system_health['disk_space']['status'] ? '#00a32a' : ( 'warning' === $system_health['disk_space']['status'] ? '#dba617' : '#d63638' ); ?>; height: 100%; width: <?php echo esc_attr( $system_health['disk_space']['percent'] ); ?>%; transition: width 0.3s ease;"></div>
					</div>
				</div>

				<!-- File Permissions -->
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Permissions', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
					<div style="display: flex; align-items: start; gap: 10px;">
						<?php if ( 'ok' === $system_health['permissions']['status'] ) : ?>
							<span style="color: #00a32a; font-weight: 600; font-size: 16px;">✓</span>
						<?php else : ?>
							<span style="color: #d63638; font-weight: 600; font-size: 16px;">✗</span>
						<?php endif; ?>
						<div style="flex: 1;">
							<div style="font-size: 12px; color: #646970; margin-bottom: 4px;">
								<?php echo esc_html( $system_health['permissions']['message'] ); ?>
							</div>
							<code style="font-size: 11px; color: #646970; word-break: break-all;">
								<?php echo esc_html( $system_health['permissions']['path'] ); ?>
							</code>
						</div>
					</div>
				</div>

				<?php if ( 'ok' !== $overall_health['status'] ) : ?>
					<div style="margin-top: 15px; padding: 12px; background: <?php echo 'error' === $overall_health['status'] ? '#fef2f2' : '#fcf3cf'; ?>; border-left: 3px solid <?php echo 'error' === $overall_health['status'] ? '#d63638' : '#dba617'; ?>; border-radius: 4px;">
						<strong style="color: <?php echo 'error' === $overall_health['status'] ? '#d63638' : '#d97706'; ?>; font-size: 12px;">
							<?php
							if ( 'error' === $overall_health['status'] ) {
								esc_html_e( 'Action Required:', 'cloudfest-wporgdownload' );
							} else {
								esc_html_e( 'Recommendations:', 'cloudfest-wporgdownload' );
							}
							?>
						</strong>
						<div style="font-size: 11px; color: #646970; margin-top: 4px;">
							<?php
							if ( 'error' === $overall_health['status'] ) {
								esc_html_e( 'Critical issues detected. The plugin may not work correctly until these are resolved.', 'cloudfest-wporgdownload' );
							} else {
								esc_html_e( 'Some warnings detected. The plugin will work but performance may be affected.', 'cloudfest-wporgdownload' );
							}
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- API Health Monitor (Phase 18.5) -->
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid <?php echo 'ok' === $api_health['response_time']['status'] && 'ok' === $api_health['error_rate']['status'] ? '#00a32a' : ( 'error' === $api_health['response_time']['status'] || 'error' === $api_health['error_rate']['status'] ? '#d63638' : '#dba617' ); ?>; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
			<h2 style="margin: 0; font-size: 18px;">
				<?php esc_html_e( 'API Health Monitor', 'cloudfest-wporgdownload' ); ?>
				<span style="font-size: 11px; font-weight: normal; color: #646970; margin-left: 10px;">
					<?php esc_html_e( 'WordPress.org API', 'cloudfest-wporgdownload' ); ?>
				</span>
			</h2>
			<?php if ( $api_health['last_test']['success'] ) : ?>
				<span style="padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; background: #00a32a; color: #fff;">
					✓ <?php esc_html_e( 'API Online', 'cloudfest-wporgdownload' ); ?>
				</span>
			<?php else : ?>
				<span style="padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; background: #d63638; color: #fff;">
					✗ <?php esc_html_e( 'API Offline', 'cloudfest-wporgdownload' ); ?>
				</span>
			<?php endif; ?>
		</div>

		<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
			<!-- Response Time -->
			<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
						<?php esc_html_e( 'Response Time', 'cloudfest-wporgdownload' ); ?>
					</h3>
					<?php if ( 'ok' === $api_health['response_time']['status'] ) : ?>
						<span style="color: #00a32a; font-weight: 600; font-size: 11px;">✓ Fast</span>
					<?php elseif ( 'warning' === $api_health['response_time']['status'] ) : ?>
						<span style="color: #dba617; font-weight: 600; font-size: 11px;">⚠ Slow</span>
					<?php else : ?>
						<span style="color: #d63638; font-weight: 600; font-size: 11px;">✗ Very Slow</span>
					<?php endif; ?>
				</div>
				<div style="font-size: 24px; font-weight: 600; color: <?php echo 'ok' === $api_health['response_time']['status'] ? '#00a32a' : ( 'warning' === $api_health['response_time']['status'] ? '#dba617' : '#d63638' ); ?>; margin-bottom: 4px;">
					<?php echo esc_html( $api_health['response_time']['value'] ); ?>s
				</div>
				<div style="font-size: 11px; color: #646970;">
					<?php echo esc_html( $api_health['response_time']['message'] ); ?>
				</div>
			</div>

			<!-- Error Rate -->
			<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
						<?php esc_html_e( 'Error Rate (24h)', 'cloudfest-wporgdownload' ); ?>
					</h3>
					<?php if ( 'ok' === $api_health['error_rate']['status'] ) : ?>
						<span style="color: #00a32a; font-weight: 600; font-size: 11px;">✓ Low</span>
					<?php elseif ( 'warning' === $api_health['error_rate']['status'] ) : ?>
						<span style="color: #dba617; font-weight: 600; font-size: 11px;">⚠ Medium</span>
					<?php else : ?>
						<span style="color: #d63638; font-weight: 600; font-size: 11px;">✗ High</span>
					<?php endif; ?>
				</div>
				<div style="font-size: 24px; font-weight: 600; color: <?php echo 'ok' === $api_health['error_rate']['status'] ? '#00a32a' : ( 'warning' === $api_health['error_rate']['status'] ? '#dba617' : '#d63638' ); ?>; margin-bottom: 4px;">
					<?php echo esc_html( $api_health['error_rate']['percent'] ); ?>%
				</div>
				<div style="font-size: 11px; color: #646970;">
					<?php echo esc_html( $api_health['error_rate']['message'] ); ?>
				</div>
			</div>

			<!-- Rate Limit -->
			<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
						<?php esc_html_e( 'Rate Limit', 'cloudfest-wporgdownload' ); ?>
					</h3>
					<?php if ( 'ok' === $api_health['rate_limit']['status'] ) : ?>
						<span style="color: #00a32a; font-weight: 600; font-size: 11px;">✓ Optimal</span>
					<?php elseif ( 'warning' === $api_health['rate_limit']['status'] ) : ?>
						<span style="color: #dba617; font-weight: 600; font-size: 11px;">⚠ High</span>
					<?php else : ?>
						<span style="color: #d63638; font-weight: 600; font-size: 11px;">✗ Too High</span>
					<?php endif; ?>
				</div>
				<div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px;">
					<span style="font-size: 24px; font-weight: 600; color: <?php echo 'ok' === $api_health['rate_limit']['status'] ? '#00a32a' : ( 'warning' === $api_health['rate_limit']['status'] ? '#dba617' : '#d63638' ); ?>;">
						<?php echo esc_html( $api_health['rate_limit']['current'] ); ?>
					</span>
					<?php if ( $api_health['rate_limit']['current'] === $api_health['rate_limit']['optimal'] ) : ?>
						<span style="font-size: 16px; color: #00a32a; font-weight: 600;">✓</span>
					<?php else : ?>
						<span style="font-size: 12px; color: #646970;">
							<?php
							/* translators: %d: optimal rate limit */
							echo esc_html( sprintf( __( '(optimal: %d)', 'cloudfest-wporgdownload' ), $api_health['rate_limit']['optimal'] ) );
							?>
						</span>
					<?php endif; ?>
				</div>
				<div style="font-size: 11px; color: #646970;">
					<?php echo esc_html( $api_health['rate_limit']['message'] ); ?>
				</div>
			</div>
		</div>

		<!-- Recommendations -->
		<?php if ( ! empty( $api_health['recommendations'] ) ) : ?>
			<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border-left: 3px solid #2271b1;">
				<h3 style="margin: 0 0 10px 0; font-size: 13px; color: #2271b1; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Recommendations', 'cloudfest-wporgdownload' ); ?>
				</h3>
				<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #646970;">
					<?php foreach ( $api_health['recommendations'] as $recommendation ) : ?>
						<li style="margin-bottom: 6px;">
							<?php echo esc_html( $recommendation ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<!-- Last Test Info -->
		<div style="margin-top: 15px; padding: 10px; background: #f6f7f7; border-radius: 4px; font-size: 11px; color: #646970; text-align: center;">
			<?php
			/* translators: %s: test result message */
			echo esc_html( sprintf( __( 'Last API test: %s', 'cloudfest-wporgdownload' ), $api_health['last_test']['message'] ) );
			?>
		</div>
	</div>

	<!-- Export/Import Diagnostics (Phase 18.7) -->
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #7e8993; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<h2 style="margin: 0 0 15px 0; font-size: 18px;">
			<?php esc_html_e( 'Diagnostic Tools', 'cloudfest-wporgdownload' ); ?>
		</h2>

		<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
			<!-- Export Section -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Export Diagnostic Report', 'cloudfest-wporgdownload' ); ?>
				</h3>
				<p style="margin: 0 0 15px 0; font-size: 13px; color: #646970;">
					<?php esc_html_e( 'Export a comprehensive diagnostic report for troubleshooting or sharing with support.', 'cloudfest-wporgdownload' ); ?>
				</p>

				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
					<!-- Full Report -->
					<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde;">
						<h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1d2327;">
							<?php esc_html_e( 'Full Report', 'cloudfest-wporgdownload' ); ?>
						</h4>
						<p style="margin: 0 0 12px 0; font-size: 12px; color: #646970; line-height: 1.5;">
							<?php esc_html_e( 'Includes all system information, settings, and file paths. Use for internal troubleshooting.', 'cloudfest-wporgdownload' ); ?>
						</p>
						<ul style="margin: 0 0 12px 0; padding-left: 20px; font-size: 11px; color: #646970;">
							<li><?php esc_html_e( 'System health data', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'API health metrics', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Database statistics', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'All settings (including sensitive)', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Recent error logs', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Environment details', 'cloudfest-wporgdownload' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'export_diagnostics', 'page' => 'wpinsight-dashboard' ), admin_url( 'tools.php' ) ), 'wpinsight_export_diagnostics' ) ); ?>"
						   class="button button-primary"
						   style="width: 100%; text-align: center;">
							<?php esc_html_e( '📥 Download Full Report', 'cloudfest-wporgdownload' ); ?>
						</a>
					</div>

					<!-- Anonymous Report -->
					<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde;">
						<h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1d2327;">
							<?php esc_html_e( 'Anonymous Report', 'cloudfest-wporgdownload' ); ?>
						</h4>
						<p style="margin: 0 0 12px 0; font-size: 12px; color: #646970; line-height: 1.5;">
							<?php esc_html_e( 'Sanitized version safe for sharing publicly or with third-party support.', 'cloudfest-wporgdownload' ); ?>
						</p>
						<ul style="margin: 0 0 12px 0; padding-left: 20px; font-size: 11px; color: #646970;">
							<li><?php esc_html_e( 'System health data', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'API health metrics', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Database statistics', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Safe settings (no emails/paths)', 'cloudfest-wporgdownload' ); ?></li>
							<li><?php esc_html_e( 'Redacted error logs', 'cloudfest-wporgdownload' ); ?></li>
							<li style="color: #d63638;"><?php esc_html_e( '✗ No environment details', 'cloudfest-wporgdownload' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'export_diagnostics_anon', 'page' => 'wpinsight-dashboard' ), admin_url( 'tools.php' ) ), 'wpinsight_export_diagnostics' ) ); ?>"
						   class="button"
						   style="width: 100%; text-align: center;">
							<?php esc_html_e( '📥 Download Anonymous Report', 'cloudfest-wporgdownload' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Import Section -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
					<?php esc_html_e( 'Import Settings', 'cloudfest-wporgdownload' ); ?>
				</h3>
				<p style="margin: 0 0 15px 0; font-size: 13px; color: #646970;">
					<?php esc_html_e( 'Import settings from another WPInsight installation.', 'cloudfest-wporgdownload' ); ?>
				</p>

				<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde;">
					<form method="post" enctype="multipart/form-data" style="margin: 0;">
						<?php wp_nonce_field( 'wpinsight_import_settings' ); ?>

						<div style="margin-bottom: 15px;">
							<label for="settings_file" style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #1d2327;">
								<?php esc_html_e( 'Select JSON File:', 'cloudfest-wporgdownload' ); ?>
							</label>
							<input type="file"
							       name="settings_file"
							       id="settings_file"
							       accept=".json"
							       required
							       style="width: 100%; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 12px;">
						</div>

						<div style="background: #fff; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid #2271b1;">
							<p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 600; color: #1d2327;">
								<?php esc_html_e( 'Settings Imported:', 'cloudfest-wporgdownload' ); ?>
							</p>
							<ul style="margin: 0; padding-left: 20px; font-size: 11px; color: #646970; line-height: 1.6;">
								<li><?php esc_html_e( 'Sync configuration', 'cloudfest-wporgdownload' ); ?></li>
								<li><?php esc_html_e( 'Download limits', 'cloudfest-wporgdownload' ); ?></li>
								<li><?php esc_html_e( 'Rate limiting', 'cloudfest-wporgdownload' ); ?></li>
								<li><?php esc_html_e( 'Worker intervals', 'cloudfest-wporgdownload' ); ?></li>
								<li><?php esc_html_e( 'Retry settings', 'cloudfest-wporgdownload' ); ?></li>
							</ul>
						</div>

						<div style="background: #fcf3cf; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid #dba617;">
							<p style="margin: 0; font-size: 11px; color: #d97706;">
								<strong><?php esc_html_e( 'Note:', 'cloudfest-wporgdownload' ); ?></strong>
								<?php esc_html_e( 'Sensitive settings (emails, paths) are never imported for security.', 'cloudfest-wporgdownload' ); ?>
							</p>
						</div>

						<button type="submit"
						        name="wpinsight_import_settings"
						        class="button button-primary"
						        style="width: 100%;">
							<?php esc_html_e( '📤 Import Settings', 'cloudfest-wporgdownload' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- ZIP Size Statistics -->
	<?php
	$size_stats = WPInsight_Zip_Queue::get_size_statistics();
	?>
	<div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #8c8f94; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<h2 style="margin: 0 0 15px 0; font-size: 18px; display: flex; align-items: center; justify-content: space-between;">
			<?php esc_html_e( 'Storage Requirements Analysis', 'cloudfest-wporgdownload' ); ?>
			<span style="font-size: 12px; font-weight: normal; color: #646970;">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: plugin detection progress, 2: theme detection progress */
						__( 'Detection progress: Plugins %1$s%% | Themes %2$s%%', 'cloudfest-wporgdownload' ),
						$size_stats['plugins']['detection_progress'],
						$size_stats['themes']['detection_progress']
					)
				);
				?>
			</span>
		</h2>
		<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
			<!-- Plugins -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #2271b1;">
					<?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?>
				</h3>
				<table class="widefat" style="margin: 0;">
					<tbody>
						<tr>
							<td style="padding: 8px;"><?php esc_html_e( 'Downloaded ZIPs:', 'cloudfest-wporgdownload' ); ?></td>
							<td style="padding: 8px; text-align: right;"><strong><?php echo esc_html( size_format( $size_stats['plugins']['downloaded_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr>
							<td style="padding: 8px;"><?php esc_html_e( 'Pending ZIPs:', 'cloudfest-wporgdownload' ); ?></td>
							<td style="padding: 8px; text-align: right;"><strong><?php echo esc_html( size_format( $size_stats['plugins']['pending_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr style="background: #f0f0f1;">
							<td style="padding: 8px;"><strong><?php esc_html_e( 'Total Required:', 'cloudfest-wporgdownload' ); ?></strong></td>
							<td style="padding: 8px; text-align: right;"><strong style="font-size: 16px; color: #2271b1;"><?php echo esc_html( size_format( $size_stats['plugins']['total_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr>
							<td style="padding: 8px;" colspan="2">
								<small style="color: #646970;">
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: count with size, 2: total count */
											__( 'Size detected for %1$s of %2$s ZIPs', 'cloudfest-wporgdownload' ),
											number_format_i18n( $size_stats['plugins']['count_with_size'] ),
											number_format_i18n( $size_stats['plugins']['total_count'] )
										)
									);
									?>
								</small>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Themes -->
			<div>
				<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #2271b1;">
					<?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?>
				</h3>
				<table class="widefat" style="margin: 0;">
					<tbody>
						<tr>
							<td style="padding: 8px;"><?php esc_html_e( 'Downloaded ZIPs:', 'cloudfest-wporgdownload' ); ?></td>
							<td style="padding: 8px; text-align: right;"><strong><?php echo esc_html( size_format( $size_stats['themes']['downloaded_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr>
							<td style="padding: 8px;"><?php esc_html_e( 'Pending ZIPs:', 'cloudfest-wporgdownload' ); ?></td>
							<td style="padding: 8px; text-align: right;"><strong><?php echo esc_html( size_format( $size_stats['themes']['pending_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr style="background: #f0f0f1;">
							<td style="padding: 8px;"><strong><?php esc_html_e( 'Total Required:', 'cloudfest-wporgdownload' ); ?></strong></td>
							<td style="padding: 8px; text-align: right;"><strong style="font-size: 16px; color: #2271b1;"><?php echo esc_html( size_format( $size_stats['themes']['total_size'], 2 ) ); ?></strong></td>
						</tr>
						<tr>
							<td style="padding: 8px;" colspan="2">
								<small style="color: #646970;">
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: count with size, 2: total count */
											__( 'Size detected for %1$s of %2$s ZIPs', 'cloudfest-wporgdownload' ),
											number_format_i18n( $size_stats['themes']['count_with_size'] ),
											number_format_i18n( $size_stats['themes']['total_count'] )
										)
									);
									?>
								</small>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px; font-size: 13px; color: #646970;">
			<strong><?php esc_html_e( 'Grand Total:', 'cloudfest-wporgdownload' ); ?></strong>
			<?php
			$grand_total = $size_stats['plugins']['total_size'] + $size_stats['themes']['total_size'];
			echo esc_html(
				sprintf(
					/* translators: %s: total size */
					__( '%s required for all ZIPs (downloaded + pending)', 'cloudfest-wporgdownload' ),
					size_format( $grand_total, 2 )
				)
			);
			?>
		</div>
	</div>

	<!-- Three Column Layout -->
	<div style="display: grid; grid-template-columns: repeat(3, 1fr); margin: 20px 0;">

		<!-- COLUMN 1 (33% - API) -->
		<div>
			<!-- API Sync Progress -->
			<div class="card">
				<h2><?php esc_html_e( 'API Sync Progress', 'cloudfest-wporgdownload' ); ?></h2>

				<!-- Plugin Sync Progress -->
				<div>
						<h3 style="margin: 0 0 15px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
							<?php esc_html_e( 'Plugin Sync', 'cloudfest-wporgdownload' ); ?>
						</h3>
						<?php if ( 'queued' === $plugin_state['status'] ) : ?>
							<!-- Queued State -->
							<div style="padding: 15px; text-align: center; background: #d5e5f2; border-left: 3px solid #2271b1; border-radius: 4px;">
								<div style="font-size: 14px; font-weight: 600; color: #135e96; margin-bottom: 5px;">
									<?php esc_html_e( 'Sync Queued', 'cloudfest-wporgdownload' ); ?>
								</div>
								<div style="font-size: 12px; color: #646970;">
									<?php esc_html_e( 'Waiting for cron to start processing... This page will auto-refresh.', 'cloudfest-wporgdownload' ); ?>
								</div>
							</div>
						<?php elseif ( 'running' === $plugin_state['status'] || 'syncing' === $plugin_state['status'] ) : ?>
							<?php
							$plugin_progress    = 0;
							$plugin_per_page    = isset( $plugin_state['per_page'] ) ? $plugin_state['per_page'] : 250;
							$plugin_total_pages = isset( $plugin_state['total_pages'] ) ? $plugin_state['total_pages'] : null;
							$plugin_total_items = isset( $plugin_state['total_items'] ) ? $plugin_state['total_items'] : null;

							if ( $plugin_total_pages && $plugin_total_pages > 0 ) {
								$plugin_progress = ( $plugin_state['page'] / $plugin_total_pages ) * 100;
							}
							$plugin_items_processed = $plugin_state['page'] * $plugin_per_page;
							$plugin_items_remaining = $plugin_total_items ? $plugin_total_items - $plugin_items_processed : 0;
							$plugin_pages_remaining = $plugin_total_pages ? $plugin_total_pages - $plugin_state['page'] : 0;
							?>
							<!-- Progress Bar -->
							<div style="background: #e0e0e0; height: 24px; border-radius: 4px; overflow: hidden; margin-bottom: 15px;">
								<div style="background: linear-gradient(90deg, #2271b1, #135e96); height: 100%; width: <?php echo esc_attr( number_format( $plugin_progress, 1 ) ); ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">
									<?php echo esc_html( number_format( $plugin_progress, 1 ) ); ?>%
								</div>
							</div>

							<!-- Stats Grid -->
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Current Page', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #1d2327;">
										<?php echo esc_html( number_format_i18n( $plugin_state['page'] ) ); ?>
										<?php if ( $plugin_total_pages ) : ?>
											<span style="font-size: 13px; color: #646970; font-weight: 400;">
												/ <?php echo esc_html( number_format_i18n( $plugin_total_pages ) ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Pages Remaining', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #d63638;">
										<?php echo esc_html( number_format_i18n( $plugin_pages_remaining ) ); ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Items Processed', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #1d2327;">
										<?php echo esc_html( number_format_i18n( $plugin_items_processed ) ); ?>
										<?php if ( $plugin_total_items ) : ?>
											<span style="font-size: 13px; color: #646970; font-weight: 400;">
												/ <?php echo esc_html( number_format_i18n( $plugin_total_items ) ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Items Remaining', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #d63638;">
										<?php echo esc_html( number_format_i18n( $plugin_items_remaining ) ); ?>
									</div>
								</div>
							</div>

							<div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #856404; font-size: 12px; color: #856404;">
								<strong><?php esc_html_e( 'Sync in progress...', 'cloudfest-wporgdownload' ); ?></strong>
								<?php esc_html_e( 'This page will auto-refresh every 10 seconds.', 'cloudfest-wporgdownload' ); ?>
							</div>
						<?php else : ?>
							<div style="padding: 20px; text-align: center; background: #f0f0f1; border-radius: 4px; color: #646970;">
								<div style="font-size: 14px;"><?php esc_html_e( 'No active sync', 'cloudfest-wporgdownload' ); ?></div>
								<div style="font-size: 12px; margin-top: 5px;">
									<?php esc_html_e( 'Click "Sync Now" or "Full Sync" to start', 'cloudfest-wporgdownload' ); ?>
								</div>
							</div>
						<?php endif; ?>
					</div>


				<!-- Theme Sync Progress -->
				<div style="margin-top: 30px;">
						<h3 style="margin: 0 0 15px 0; font-size: 14px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px;">
							<?php esc_html_e( 'Theme Sync', 'cloudfest-wporgdownload' ); ?>
						</h3>
						<?php if ( 'queued' === $theme_state['status'] ) : ?>
							<!-- Queued State -->
							<div style="padding: 15px; text-align: center; background: #d5e5f2; border-left: 3px solid #2271b1; border-radius: 4px;">
								<div style="font-size: 14px; font-weight: 600; color: #135e96; margin-bottom: 5px;">
									<?php esc_html_e( 'Sync Queued', 'cloudfest-wporgdownload' ); ?>
								</div>
								<div style="font-size: 12px; color: #646970;">
									<?php esc_html_e( 'Waiting for cron to start processing... This page will auto-refresh.', 'cloudfest-wporgdownload' ); ?>
								</div>
							</div>
						<?php elseif ( 'running' === $theme_state['status'] || 'syncing' === $theme_state['status'] ) : ?>
							<?php
							$theme_progress    = 0;
							$theme_per_page    = isset( $theme_state['per_page'] ) ? $theme_state['per_page'] : 250;
							$theme_total_pages = isset( $theme_state['total_pages'] ) ? $theme_state['total_pages'] : null;
							$theme_total_items = isset( $theme_state['total_items'] ) ? $theme_state['total_items'] : null;

							if ( $theme_total_pages && $theme_total_pages > 0 ) {
								$theme_progress = ( $theme_state['page'] / $theme_total_pages ) * 100;
							}
							$theme_items_processed = $theme_state['page'] * $theme_per_page;
							$theme_items_remaining = $theme_total_items ? $theme_total_items - $theme_items_processed : 0;
							$theme_pages_remaining = $theme_total_pages ? $theme_total_pages - $theme_state['page'] : 0;
							?>
							<!-- Progress Bar -->
							<div style="background: #e0e0e0; height: 24px; border-radius: 4px; overflow: hidden; margin-bottom: 15px;">
								<div style="background: linear-gradient(90deg, #2271b1, #135e96); height: 100%; width: <?php echo esc_attr( number_format( $theme_progress, 1 ) ); ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">
									<?php echo esc_html( number_format( $theme_progress, 1 ) ); ?>%
								</div>
							</div>

							<!-- Stats Grid -->
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Current Page', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #1d2327;">
										<?php echo esc_html( number_format_i18n( $theme_state['page'] ) ); ?>
										<?php if ( $theme_total_pages ) : ?>
											<span style="font-size: 13px; color: #646970; font-weight: 400;">
												/ <?php echo esc_html( number_format_i18n( $theme_total_pages ) ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Pages Remaining', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #d63638;">
										<?php echo esc_html( number_format_i18n( $theme_pages_remaining ) ); ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Items Processed', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #1d2327;">
										<?php echo esc_html( number_format_i18n( $theme_items_processed ) ); ?>
										<?php if ( $theme_total_items ) : ?>
											<span style="font-size: 13px; color: #646970; font-weight: 400;">
												/ <?php echo esc_html( number_format_i18n( $theme_total_items ) ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

								<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
									<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Items Remaining', 'cloudfest-wporgdownload' ); ?></div>
									<div style="font-weight: 600; font-size: 16px; color: #d63638;">
										<?php echo esc_html( number_format_i18n( $theme_items_remaining ) ); ?>
									</div>
								</div>
							</div>

							<div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #856404; font-size: 12px; color: #856404;">
								<strong><?php esc_html_e( 'Sync in progress...', 'cloudfest-wporgdownload' ); ?></strong>
								<?php esc_html_e( 'This page will auto-refresh every 10 seconds.', 'cloudfest-wporgdownload' ); ?>
							</div>
						<?php else : ?>
							<div style="padding: 20px; text-align: center; background: #f0f0f1; border-radius: 4px; color: #646970;">
								<div style="font-size: 14px;"><?php esc_html_e( 'No active sync', 'cloudfest-wporgdownload' ); ?></div>
								<div style="font-size: 12px; margin-top: 5px;">
									<?php esc_html_e( 'Click "Sync Now" or "Full Sync" to start', 'cloudfest-wporgdownload' ); ?>
								</div>
							</div>
						<?php endif; ?>
				</div>
			</div>


			<!-- Sync Status -->
			<div class="card">
				<h2><?php esc_html_e( 'Sync Status', 'cloudfest-wporgdownload' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Status / Progress', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Last Run', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'cloudfest-wporgdownload' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?></strong></td>
							<td>
							<code><?php echo esc_html( $plugin_state['status'] ); ?></code>
							<?php if ( 'running' === $plugin_state['status'] || 'syncing' === $plugin_state['status'] ) : ?>
								<?php
								if ( ! empty( $plugin_state['page'] ) && $plugin_state['page'] > 1 ) {
									echo '<div style="font-size: 11px; color: #646970; margin-top: 3px;">';
									// translators: %d is the page number.
									echo esc_html( sprintf( __( 'Page %d', 'cloudfest-wporgdownload' ), $plugin_state['page'] ) );
									echo '</div>';
								}
								?>
							<?php endif; ?>
							</td>
							<td>
							<?php
							if ( ! empty( $plugin_state['updated_at'] ) ) {
								$last_run = strtotime( $plugin_state['updated_at'] );
								if ( $last_run ) {
									echo '<span title="' . esc_attr( $plugin_state['updated_at'] ) . '">';
									// translators: %s is the time difference (e.g., "2 hours ago").
									echo esc_html( sprintf( __( '%s ago', 'cloudfest-wporgdownload' ), human_time_diff( $last_run ) ) );
									echo '</span>';
								}
							} else {
								echo '<span style="color: #646970;">—</span>';
							}
							?>
							</td>
							<td>
								<?php if ( 'error' === $plugin_state['status'] ) : ?>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
									<input type="hidden" name="wpinsight_action" value="sync_plugins">
									<button type="submit" class="button button-small button-primary"><?php esc_html_e( 'Resume', 'cloudfest-wporgdownload' ); ?></button>
								</form>
							<?php else : ?>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
									<input type="hidden" name="wpinsight_action" value="sync_plugins">
									<button type="submit" class="button button-small"><?php esc_html_e( 'Sync Now', 'cloudfest-wporgdownload' ); ?></button>
								</form>
								<?php endif; ?>
								<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="full_sync_plugins">
								<button type="submit" class="button button-small button-secondary" onclick="return confirm('<?php esc_attr_e( 'This will sync ALL plugins and ALL versions. This may take hours or days to complete. Continue?', 'cloudfest-wporgdownload' ); ?>');" title="<?php esc_attr_e( 'Download all plugins with full version history', 'cloudfest-wporgdownload' ); ?>"><?php esc_html_e( 'Full Sync', 'cloudfest-wporgdownload' ); ?></button>
							</form>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="reset_sync_plugins">
								<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset the sync state?', 'cloudfest-wporgdownload' ); ?>');"><?php esc_html_e( 'Reset', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?></strong></td>
						<td>
							<code><?php echo esc_html( $theme_state['status'] ); ?></code>
							<?php if ( 'running' === $theme_state['status'] || 'syncing' === $theme_state['status'] ) : ?>
								<?php
								if ( ! empty( $theme_state['page'] ) && $theme_state['page'] > 1 ) {
									echo '<div style="font-size: 11px; color: #646970; margin-top: 3px;">';
									// translators: %d is the page number.
									echo esc_html( sprintf( __( 'Page %d', 'cloudfest-wporgdownload' ), $theme_state['page'] ) );
									echo '</div>';
								}
								?>
							<?php endif; ?>
							</td>
							<td>
							<?php
							if ( ! empty( $theme_state['updated_at'] ) ) {
								$last_run = strtotime( $theme_state['updated_at'] );
								if ( $last_run ) {
									echo '<span title="' . esc_attr( $theme_state['updated_at'] ) . '">';
									// translators: %s is the time difference (e.g., "2 hours ago").
									echo esc_html( sprintf( __( '%s ago', 'cloudfest-wporgdownload' ), human_time_diff( $last_run ) ) );
									echo '</span>';
								}
							} else {
								echo '<span style="color: #646970;">—</span>';
							}
							?>
							</td>
							<td>
							<?php if ( 'error' === $theme_state['status'] ) : ?>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
									<input type="hidden" name="wpinsight_action" value="sync_themes">
									<button type="submit" class="button button-small button-primary"><?php esc_html_e( 'Resume', 'cloudfest-wporgdownload' ); ?></button>
								</form>
							<?php else : ?>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
									<input type="hidden" name="wpinsight_action" value="sync_themes">
									<button type="submit" class="button button-small"><?php esc_html_e( 'Sync Now', 'cloudfest-wporgdownload' ); ?></button>
								</form>
								<?php endif; ?>
								<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="full_sync_themes">
								<button type="submit" class="button button-small button-secondary" onclick="return confirm('<?php esc_attr_e( 'This will sync ALL themes and ALL versions. This may take several hours to complete. Continue?', 'cloudfest-wporgdownload' ); ?>');" title="<?php esc_attr_e( 'Download all themes with full version history', 'cloudfest-wporgdownload' ); ?>"><?php esc_html_e( 'Full Sync', 'cloudfest-wporgdownload' ); ?></button>
							</form>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="reset_sync_themes">
								<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset the sync state?', 'cloudfest-wporgdownload' ); ?>');"><?php esc_html_e( 'Reset', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						</td>
					</tr>
				</tbody>
			</table>
				<?php if ( ! empty( $plugin_state['last_error'] ) ) : ?>
					<div class="notice notice-error inline" style="margin: 10px 0;">
						<p><strong><?php esc_html_e( 'Plugin Sync Error:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( $plugin_state['last_error'] ); ?></p>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $theme_state['last_error'] ) ) : ?>
					<div class="notice notice-error inline" style="margin: 10px 0;">
						<p><strong><?php esc_html_e( 'Theme Sync Error:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( $theme_state['last_error'] ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- COLUMN 2 (33% - Downloads) -->
		<div>
			<!-- ZIP Size Detection Progress -->
			<div class="card">
				<h2><?php esc_html_e( 'ZIP Size Detection Progress', 'cloudfest-wporgdownload' ); ?></h2>

				<?php
				$size_stats = WPInsight_Zip_Queue::get_size_statistics();
				?>

				<!-- Plugin Size Detection -->
				<h3 style="margin: 15px 0 10px 0; font-size: 14px; color: #2271b1;">
					<?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<?php
				$plugin_detection_progress = $size_stats['plugins']['detection_progress'];
				$plugin_with_size          = $size_stats['plugins']['count_with_size'];
				$plugin_total              = $size_stats['plugins']['total_count'];
				?>

				<!-- Progress Bar -->
				<div style="background: #e0e0e0; height: 24px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
					<div style="background: linear-gradient(90deg, #2271b1, #135e96); height: 100%; width: <?php echo esc_attr( number_format( $plugin_detection_progress, 1 ) ); ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">
						<?php echo esc_html( number_format( $plugin_detection_progress, 1 ) ); ?>%
					</div>
				</div>

				<!-- Stats Grid -->
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; margin-bottom: 20px;">
					<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
						<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Size Detected', 'cloudfest-wporgdownload' ); ?></div>
						<div style="font-weight: 600; font-size: 16px; color: #00a32a;">
							<?php echo esc_html( $admin::format_number_abbreviated( $plugin_with_size ) ); ?>
							<span style="font-size: 13px; color: #646970; font-weight: 400;">
								/ <?php echo esc_html( $admin::format_number_abbreviated( $plugin_total ) ); ?>
							</span>
						</div>
					</div>

					<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
						<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Remaining', 'cloudfest-wporgdownload' ); ?></div>
						<div style="font-weight: 600; font-size: 16px; color: #d63638;">
							<?php echo esc_html( $admin::format_number_abbreviated( $plugin_total - $plugin_with_size ) ); ?>
						</div>
					</div>
				</div>

				<!-- Theme Size Detection -->
				<h3 style="margin: 15px 0 10px 0; font-size: 14px; color: #2271b1;">
					<?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?>
				</h3>

				<?php
				$theme_detection_progress = $size_stats['themes']['detection_progress'];
				$theme_with_size          = $size_stats['themes']['count_with_size'];
				$theme_total              = $size_stats['themes']['total_count'];
				?>

				<!-- Progress Bar -->
				<div style="background: #e0e0e0; height: 24px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
					<div style="background: linear-gradient(90deg, #2271b1, #135e96); height: 100%; width: <?php echo esc_attr( number_format( $theme_detection_progress, 1 ) ); ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">
						<?php echo esc_html( number_format( $theme_detection_progress, 1 ) ); ?>%
					</div>
				</div>

				<!-- Stats Grid -->
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
					<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
						<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Size Detected', 'cloudfest-wporgdownload' ); ?></div>
						<div style="font-weight: 600; font-size: 16px; color: #00a32a;">
							<?php echo esc_html( $admin::format_number_abbreviated( $theme_with_size ) ); ?>
							<span style="font-size: 13px; color: #646970; font-weight: 400;">
								/ <?php echo esc_html( $admin::format_number_abbreviated( $theme_total ) ); ?>
							</span>
						</div>
					</div>

					<div style="background: #f0f0f1; padding: 10px; border-radius: 3px;">
						<div style="color: #646970; font-size: 11px; margin-bottom: 3px;"><?php esc_html_e( 'Remaining', 'cloudfest-wporgdownload' ); ?></div>
						<div style="font-weight: 600; font-size: 16px; color: #d63638;">
							<?php echo esc_html( $admin::format_number_abbreviated( $theme_total - $theme_with_size ) ); ?>
						</div>
					</div>
				</div>

				<!-- Worker Status -->
				<div style="margin-top: 15px; padding: 10px; background: #f6f7f7; border-left: 3px solid #8c8f94; border-radius: 3px; font-size: 12px; color: #646970;">
					<strong><?php esc_html_e( 'Auto-detection:', 'cloudfest-wporgdownload' ); ?></strong>
					<?php
					$rate_limit = WPInsight_Settings::get( 'max_size_detection_rate', 3 );
					/* translators: 1: batch size, 2: requests per second */
					echo esc_html( sprintf( __( 'Runs every 5 minutes (600 ZIPs per batch, %d req/sec)', 'cloudfest-wporgdownload' ), $rate_limit ) );
					?>
				</div>
			</div>

			<!-- Download Queue -->
			<div class="card">
			<h2><?php esc_html_e( 'Download Queue', 'cloudfest-wporgdownload' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Pending', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><strong><?php echo esc_html( $admin::format_number_abbreviated( $queue_stats['pending'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Processing', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><?php echo esc_html( $admin::format_number_abbreviated( $queue_stats['processing'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Completed', 'cloudfest-wporgdownload' ); ?>:</th>
						<td style="color: #00a32a;"><strong><?php echo esc_html( $admin::format_number_abbreviated( $queue_stats['completed'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Failed', 'cloudfest-wporgdownload' ); ?>:</th>
						<td style="color: #d63638;"><strong><?php echo esc_html( $admin::format_number_abbreviated( $queue_stats['failed'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Total', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><strong><?php echo esc_html( $admin::format_number_abbreviated( $queue_stats['total'] ) ); ?></strong></td>
					</tr>
				</tbody>
			</table>

				<div style="margin-top: 15px;">
					<form method="post" style="display: inline;">
						<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
						<input type="hidden" name="wpinsight_action" value="process_queue">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Process Queue', 'cloudfest-wporgdownload' ); ?></button>
					</form>

					<?php if ( $queue_stats['failed'] > 0 ) : ?>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
							<input type="hidden" name="wpinsight_action" value="retry_failed">
							<button type="submit" class="button"><?php esc_html_e( 'Retry Failed', 'cloudfest-wporgdownload' ); ?></button>
						</form>
					<?php endif; ?>

					<?php if ( $queue_stats['completed'] > 0 ) : ?>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
							<input type="hidden" name="wpinsight_action" value="clear_completed">
							<button type="submit" class="button"><?php esc_html_e( 'Clear Completed', 'cloudfest-wporgdownload' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- COLUMN 3 (33% - Logs) -->
		<div>
			<!-- Error Logs -->
			<div class="card">
				<h2>
					<?php esc_html_e( 'Error Logs (Last 10)', 'cloudfest-wporgdownload' ); ?>
					<?php if ( ! empty( $recent_logs ) ) : ?>
						<span style="display: inline-block; margin-left: 8px; padding: 4px 10px; background: #d63638; color: #fff; border-radius: 10px; font-size: 12px; font-weight: 600;">
							<?php echo esc_html( count( $recent_logs ) ); ?>
						</span>
					<?php endif; ?>
				</h2>
				<?php if ( empty( $recent_logs ) ) : ?>
					<p><?php esc_html_e( 'No errors found.', 'cloudfest-wporgdownload' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Severity', 'cloudfest-wporgdownload' ); ?></th>
								<th><?php esc_html_e( 'Time', 'cloudfest-wporgdownload' ); ?></th>
								<th><?php esc_html_e( 'Message', 'cloudfest-wporgdownload' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_logs as $log ) : ?>
								<tr>
									<td><?php echo wp_kses_post( $admin::get_severity_badge_html( $log['severity'] ) ); ?></td>
									<td style="font-size: 0.85em;">
										<?php
										echo esc_html(
											human_time_diff(
												strtotime( $log['created_at'] ),
												time()
											)
										);
										?>
										<?php esc_html_e( 'ago', 'cloudfest-wporgdownload' ); ?>
									</td>
									<td><?php echo esc_html( wp_trim_words( $log['message'], 12 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
							<a href="<?php echo esc_url( admin_url( 'tools.php?page=wpinsight-error-log' ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View Full Log', 'cloudfest-wporgdownload' ); ?>
							</a>
						<?php endif; ?>
						<form method="post" style="display: inline; margin: 0;">
							<?php wp_nonce_field( 'wpinsight_clear_old_logs', 'wpinsight_clear_logs_nonce' ); ?>
							<input type="hidden" name="wpinsight_action" value="clear_old_logs">
							<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Clear logs older than 30 days?', 'cloudfest-wporgdownload' ); ?>');">
								<?php esc_html_e( 'Clear Old Logs', 'cloudfest-wporgdownload' ); ?>
							</button>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>

	</div>

</div>

<script>
(function() {
	// Check if any sync is running or queued
	var pluginSyncRunning = <?php echo wp_json_encode( in_array( $plugin_state['status'], array( 'running', 'syncing', 'queued' ), true ) ); ?>;
	var themeSyncRunning = <?php echo wp_json_encode( in_array( $theme_state['status'], array( 'running', 'syncing', 'queued' ), true ) ); ?>;

	if (pluginSyncRunning || themeSyncRunning) {
		// Auto-refresh every 10 seconds when sync is active
		setTimeout(function() {
			window.location.reload();
		}, 10000);
		
		// Add visual indicator
		console.log('WPInsight: Sync in progress, auto-refresh in 10 seconds...');
	}
})();
</script>
