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
