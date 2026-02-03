/**
 * WPInsight Admin JavaScript
 *
 * Handles dismissible error notices with persistent state.
 * When a user dismisses the error notice, it stores the dismiss timestamp
 * via AJAX so the notice won't appear again until NEW errors occur.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Assets
 * @since      1.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

(function($) {
	'use strict';

	/**
	 * Handle error notice dismissal.
	 *
	 * When the user clicks the dismiss button on the error notice,
	 * send an AJAX request to store the current timestamp. This prevents
	 * the notice from appearing again until new errors are logged.
	 */
	$(document).on('click', '.notice[data-wpinsight-notice="errors"] .notice-dismiss', function() {
		// Send AJAX request to store dismiss timestamp.
		$.ajax({
			url: wpinsightAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpinsight_dismiss_errors',
				nonce: wpinsightAdmin.dismissErrorsNonce
			}
		});
	});

})(jQuery);
