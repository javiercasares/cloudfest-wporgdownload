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
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $plugin_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $plugin_count ) ); ?>"><?php echo esc_html( number_format_i18n( $plugin_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $theme_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $theme_count ) ); ?>"><?php echo esc_html( number_format_i18n( $theme_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Downloaded ZIPs', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $artifact_count ) ); ?></div>
			<div style="font-size: 11px; color: #646970; margin-top: 5px;" title="<?php echo esc_attr( number_format_i18n( $artifact_count ) ); ?>"><?php echo esc_html( number_format_i18n( $artifact_count ) ); ?> total</div>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Storage Used', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( (string) size_format( $storage_size, 2 ) ); ?></div>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
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

		<!-- Download Queue -->
		<div class="card">
			<h2><?php esc_html_e( 'Download Queue', 'cloudfest-wporgdownload' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Pending', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><strong><?php echo esc_html( number_format_i18n( $queue_stats['pending'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Processing', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><?php echo esc_html( number_format_i18n( $queue_stats['processing'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Completed', 'cloudfest-wporgdownload' ); ?>:</th>
						<td style="color: #00a32a;"><strong><?php echo esc_html( number_format_i18n( $queue_stats['completed'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Failed', 'cloudfest-wporgdownload' ); ?>:</th>
						<td style="color: #d63638;"><strong><?php echo esc_html( number_format_i18n( $queue_stats['failed'] ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Total', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><strong><?php echo esc_html( number_format_i18n( $queue_stats['total'] ) ); ?></strong></td>
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

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
		<!-- Recent Errors -->
		<?php
		// Phase 5.5: Add recent errors card here.
		?>

		<!-- Quick Links -->
		<div class="card">
			<h2><?php esc_html_e( 'Quick Links', 'cloudfest-wporgdownload' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wpinsight-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Settings', 'cloudfest-wporgdownload' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . WPInsight_CPT::get_plugin_post_type() ) ); ?>" class="button">
					<?php esc_html_e( 'View Plugins', 'cloudfest-wporgdownload' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . WPInsight_CPT::get_theme_post_type() ) ); ?>" class="button">
					<?php esc_html_e( 'View Themes', 'cloudfest-wporgdownload' ); ?>
				</a>
				<?php if ( defined( 'WP_CLI' ) && WP_CLI ) : ?>
					<a href="<?php echo esc_url( admin_url( 'tools.php?page=wpinsight-dashboard#cli-commands' ) ); ?>" class="button">
						<?php esc_html_e( 'WP-CLI Commands', 'cloudfest-wporgdownload' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
	</div>
</div>
