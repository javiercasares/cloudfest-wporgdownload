<?php
/**
 * Admin Settings Template
 *
 * Template for the WPInsight settings page.
 * Displays settings form and system status information.
 *
 * Available variables:
 * - $admin: WPInsight_Admin class for helper methods.
 * - $settings_group: Settings API group name.
 * - $settings_page_slug: Settings page slug.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Templates
 * @since      0.1.0
 *
 * @phpstan-var class-string<WPInsight_Admin> $admin
 * @phpstan-var string $settings_group
 * @phpstan-var string $settings_page_slug
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( $settings_group );
		do_settings_sections( $settings_page_slug );
		submit_button();
		?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'System Status', 'cloudfest-wporgdownload' ); ?></h2>
	<table class="widefat">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Database Version', 'cloudfest-wporgdownload' ); ?>:</th>
				<td><?php echo esc_html( WPInsight_DB::get_schema_version() ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Plugin Post Type', 'cloudfest-wporgdownload' ); ?>:</th>
				<td><code><?php echo esc_html( WPInsight_CPT::get_plugin_post_type() ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Theme Post Type', 'cloudfest-wporgdownload' ); ?>:</th>
				<td><code><?php echo esc_html( WPInsight_CPT::get_theme_post_type() ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Action Scheduler', 'cloudfest-wporgdownload' ); ?>:</th>
				<td>
					<?php if ( function_exists( 'as_schedule_recurring_action' ) ) : ?>
						<span style="color: green;">✓ <?php esc_html_e( 'Installed', 'cloudfest-wporgdownload' ); ?></span>
					<?php else : ?>
						<span style="color: red;">✗ <?php esc_html_e( 'Not Found', 'cloudfest-wporgdownload' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
// Show Debug Tools only when WP_DEBUG is enabled.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	$admin::render_debug_tools();
}
?>
