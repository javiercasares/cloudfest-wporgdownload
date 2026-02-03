/**
 * Dashboard Live Updates
 *
 * Provides real-time AJAX updates for the WPInsight dashboard.
 * Features:
 * - Auto-refreshing queue statistics
 * - Live progress bars for active downloads
 * - Sync worker status updates
 * - Interactive action buttons
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage JavaScript
 * @since      1.7.0
 */

(function($) {
	'use strict';

	/**
	 * Dashboard Live Updates Manager
	 */
	const WPInsightLive = {
		/**
		 * Configuration
		 */
		config: {
			pollInterval: 5000, // 5 seconds
			nextUpdateCountdown: 5,
			endpoints: {},
			nonce: '',
		},

		/**
		 * State
		 */
		state: {
			polling: false,
			countdownTimer: null,
			lastStats: {},
		},

		/**
		 * Initialize
		 */
		init: function() {
			// Get configuration from localized script data
			if (typeof wpinsightLive !== 'undefined') {
				this.config.endpoints = wpinsightLive.endpoints;
				this.config.nonce = wpinsightLive.nonce;
				this.config.pollInterval = wpinsightLive.pollInterval || 5000;
			}

			// Start polling
			this.startPolling();

			// Bind action buttons
			this.bindActionButtons();

			console.log('[WPInsight Live] Initialized - polling every', this.config.pollInterval / 1000, 'seconds');
		},

		/**
		 * Start polling for updates
		 */
		startPolling: function() {
			if (this.state.polling) {
				return;
			}

			this.state.polling = true;

			// Initial fetch
			this.fetchAllUpdates();

			// Set up interval
			setInterval(() => {
				this.fetchAllUpdates();
			}, this.config.pollInterval);

			// Start countdown timer
			this.startCountdownTimer();
		},

		/**
		 * Fetch all updates (stats, downloads, sync status)
		 */
		fetchAllUpdates: function() {
			this.fetchDashboardStats();
			this.fetchActiveDownloads();
			this.fetchSyncStatus();
		},

		/**
		 * Fetch dashboard statistics
		 */
		fetchDashboardStats: function() {
			$.ajax({
				url: this.config.endpoints.dashboardStats,
				method: 'GET',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				success: (response) => {
					if (response.success && response.data) {
						this.updateDashboardStats(response.data);
					}
				},
				error: (xhr, status, error) => {
					console.error('[WPInsight Live] Failed to fetch dashboard stats:', error);
				}
			});
		},

		/**
		 * Update dashboard statistics in DOM
		 */
		updateDashboardStats: function(data) {
			const oldStats = this.state.lastStats;
			this.state.lastStats = data;

			// Update pending count
			this.updateStatElement('[data-stat="pending"]', data.pending, oldStats.pending);

			// Update processing count
			this.updateStatElement('[data-stat="processing"]', data.processing, oldStats.processing);

			// Update completed count
			this.updateStatElement('[data-stat="completed"]', data.completed, oldStats.completed);

			// Update failed count
			this.updateStatElement('[data-stat="failed"]', data.failed, oldStats.failed);

			// Update pause/resume button state
			this.updatePauseResumeButton(data.paused);
		},

		/**
		 * Update a single stat element with animation
		 */
		updateStatElement: function(selector, newValue, oldValue) {
			const $element = $(selector);
			if (!$element.length) {
				return;
			}

			// Format number with commas
			const formattedValue = newValue.toLocaleString();

			// Update value
			$element.text(formattedValue);

			// Add change indicator if value changed
			if (oldValue !== undefined && oldValue !== newValue) {
				const $parent = $element.closest('[data-stat-container]');
				if ($parent.length) {
					$parent.removeClass('stat-increased stat-decreased');

					if (newValue > oldValue) {
						$parent.addClass('stat-increased');
						setTimeout(() => $parent.removeClass('stat-increased'), 2000);
					} else if (newValue < oldValue) {
						$parent.addClass('stat-decreased');
						setTimeout(() => $parent.removeClass('stat-decreased'), 2000);
					}
				}
			}
		},

		/**
		 * Update pause/resume button state
		 */
		updatePauseResumeButton: function(paused) {
			const $pauseBtn = $('[data-action="pause-downloads"]');
			const $resumeBtn = $('[data-action="resume-downloads"]');

			if (paused) {
				$pauseBtn.hide();
				$resumeBtn.show();
			} else {
				$pauseBtn.show();
				$resumeBtn.hide();
			}
		},

		/**
		 * Fetch active downloads
		 */
		fetchActiveDownloads: function() {
			$.ajax({
				url: this.config.endpoints.activeDownloads,
				method: 'GET',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				success: (response) => {
					if (response.success && response.data) {
						this.updateActiveDownloads(response.data);
					}
				},
				error: (xhr, status, error) => {
					console.error('[WPInsight Live] Failed to fetch active downloads:', error);
				}
			});
		},

		/**
		 * Update active downloads list
		 */
		updateActiveDownloads: function(downloads) {
			const $container = $('[data-active-downloads]');
			if (!$container.length) {
				return;
			}

			if (downloads.length === 0) {
				$container.html('<p style="color: #646970; font-style: italic;">No active downloads</p>');
				return;
			}

			let html = '<div style="display: flex; flex-direction: column; gap: 15px;">';

			downloads.forEach((download) => {
				html += this.renderDownloadProgressBar(download);
			});

			html += '</div>';

			$container.html(html);
		},

		/**
		 * Render single download progress bar
		 */
		renderDownloadProgressBar: function(download) {
			return `
				<div style="background: #f6f7f7; padding: 12px; border-radius: 4px; border-left: 3px solid #2271b1;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
						<strong style="font-size: 13px; color: #1d2327;">${download.filename}</strong>
						<span style="font-size: 12px; color: #646970;">${download.speed_text}</span>
					</div>
					<div style="background: #dcdcde; border-radius: 10px; height: 8px; overflow: hidden; margin-bottom: 5px;">
						<div style="background: #2271b1; height: 100%; width: ${download.progress}%; transition: width 0.3s ease;"></div>
					</div>
					<div style="display: flex; justify-content: space-between; font-size: 11px; color: #646970;">
						<span>${download.progress}%</span>
						<span>Elapsed: ${this.formatElapsedTime(download.elapsed)}</span>
					</div>
				</div>
			`;
		},

		/**
		 * Fetch sync status
		 */
		fetchSyncStatus: function() {
			$.ajax({
				url: this.config.endpoints.syncStatus,
				method: 'GET',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				success: (response) => {
					if (response.success && response.data) {
						this.updateSyncStatus(response.data);
					}
				},
				error: (xhr, status, error) => {
					console.error('[WPInsight Live] Failed to fetch sync status:', error);
				}
			});
		},

		/**
		 * Update sync status in DOM
		 */
		updateSyncStatus: function(data) {
			// Update plugin sync
			this.updateSyncWorkerStatus('[data-sync="plugin"]', data.plugin);

			// Update theme sync
			this.updateSyncWorkerStatus('[data-sync="theme"]', data.theme);
		},

		/**
		 * Update sync worker status element
		 */
		updateSyncWorkerStatus: function(selector, syncData) {
			const $container = $(selector);
			if (!$container.length) {
				return;
			}

			// Update status badge
			const $statusBadge = $container.find('[data-sync-status]');
			if ($statusBadge.length) {
				$statusBadge.text(this.formatSyncStatus(syncData.status));
			}

			// Update progress text
			const $progressText = $container.find('[data-sync-progress-text]');
			if ($progressText.length) {
				$progressText.text(`${syncData.items_processed.toLocaleString()} / ${syncData.total_items.toLocaleString()} (${syncData.progress}%)`);
			}

			// Update progress bar
			const $progressBar = $container.find('[data-sync-progress-bar]');
			if ($progressBar.length) {
				$progressBar.css('width', syncData.progress + '%');
			}
		},

		/**
		 * Format sync status for display
		 */
		formatSyncStatus: function(status) {
			const statusMap = {
				'idle': 'Idle',
				'queued': '⟳ Queued',
				'running': '⟳ Running',
				'completed': '✓ Completed',
				'error': '✗ Error'
			};

			return statusMap[status] || status;
		},

		/**
		 * Format elapsed time
		 */
		formatElapsedTime: function(seconds) {
			if (seconds < 60) {
				return seconds + 's';
			}

			const minutes = Math.floor(seconds / 60);
			const secs = seconds % 60;

			return minutes + 'm ' + secs + 's';
		},

		/**
		 * Start countdown timer for "Next update in..."
		 */
		startCountdownTimer: function() {
			const $countdown = $('[data-next-update]');
			if (!$countdown.length) {
				return;
			}

			let seconds = this.config.nextUpdateCountdown;

			this.state.countdownTimer = setInterval(() => {
				seconds--;

				if (seconds <= 0) {
					seconds = this.config.nextUpdateCountdown;
				}

				$countdown.text(`Next update in: ${seconds}s...`);
			}, 1000);
		},

		/**
		 * Bind action buttons
		 */
		bindActionButtons: function() {
			// Download Now button
			$(document).on('click', '[data-action="download-now"]', (e) => {
				e.preventDefault();
				const $btn = $(e.currentTarget);
				const slug = $btn.data('slug');
				const version = $btn.data('version');
				const type = $btn.data('type');

				this.downloadNow($btn, slug, version, type);
			});

			// Pause Downloads button
			$(document).on('click', '[data-action="pause-downloads"]', (e) => {
				e.preventDefault();
				this.pauseDownloads($(e.currentTarget));
			});

			// Resume Downloads button
			$(document).on('click', '[data-action="resume-downloads"]', (e) => {
				e.preventDefault();
				this.resumeDownloads($(e.currentTarget));
			});
		},

		/**
		 * Download Now action
		 */
		downloadNow: function($btn, slug, version, type) {
			const originalText = $btn.text();
			$btn.prop('disabled', true).text('⟳ Enqueueing...');

			$.ajax({
				url: this.config.endpoints.downloadNow,
				method: 'POST',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				data: {
					slug: slug,
					version: version,
					type: type
				},
				success: (response) => {
					if (response.success) {
						$btn.text('✓ Enqueued');
						setTimeout(() => {
							$btn.prop('disabled', false).text(originalText);
							// Refresh stats immediately
							this.fetchAllUpdates();
						}, 2000);
					} else {
						alert('Error: ' + (response.message || 'Failed to enqueue download'));
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: (xhr, status, error) => {
					alert('Error: ' + error);
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Pause Downloads action
		 */
		pauseDownloads: function($btn) {
			const originalText = $btn.text();
			$btn.prop('disabled', true).text('⟳ Pausing...');

			$.ajax({
				url: this.config.endpoints.pauseDownloads,
				method: 'POST',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				success: (response) => {
					if (response.success) {
						$btn.hide();
						$('[data-action="resume-downloads"]').show();
						// Refresh stats immediately
						this.fetchAllUpdates();
					} else {
						alert('Error: ' + (response.message || 'Failed to pause downloads'));
					}
					$btn.prop('disabled', false).text(originalText);
				},
				error: (xhr, status, error) => {
					alert('Error: ' + error);
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Resume Downloads action
		 */
		resumeDownloads: function($btn) {
			const originalText = $btn.text();
			$btn.prop('disabled', true).text('⟳ Resuming...');

			$.ajax({
				url: this.config.endpoints.resumeDownloads,
				method: 'POST',
				beforeSend: (xhr) => {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				},
				success: (response) => {
					if (response.success) {
						$btn.hide();
						$('[data-action="pause-downloads"]').show();
						// Refresh stats immediately
						this.fetchAllUpdates();
					} else {
						alert('Error: ' + (response.message || 'Failed to resume downloads'));
					}
					$btn.prop('disabled', false).text(originalText);
				},
				error: (xhr, status, error) => {
					alert('Error: ' + error);
					$btn.prop('disabled', false).text(originalText);
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		// Only initialize on dashboard page
		if ($('[data-wpinsight-dashboard]').length) {
			WPInsightLive.init();
		}
	});

})(jQuery);
