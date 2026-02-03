	<!-- ZIP Size Statistics -->
	<?php
	// Defensive check: ensure required variable exists.
	if ( ! isset( $size_stats ) ) {
		return;
	}
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
