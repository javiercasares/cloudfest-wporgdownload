	<!-- API Health Monitor (Phase 18.5) -->
	<?php
	// Defensive check: ensure required variable exists.
	if ( ! isset( $api_health ) ) {
		return;
	}
	?>
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
