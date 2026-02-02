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
