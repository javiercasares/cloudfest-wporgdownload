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
