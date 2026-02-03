	<!-- System Health Check (Phase 18.6) -->
	<?php
	// Defensive check: ensure required variables exist.
	if ( ! isset( $system_health ) || ! isset( $overall_health ) ) {
		return;
	}
	?>
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
