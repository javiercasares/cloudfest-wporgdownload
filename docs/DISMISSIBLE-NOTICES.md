# Dismissible Admin Notices - Implementation Documentation

## Overview

Implemented persistent dismissible admin error notices that stay dismissed until NEW errors occur (v1.1.0+).

## User Experience

When critical errors (EMERGENCY or ERROR severity) occur:
1. Admin notice appears at top of WPInsight admin pages
2. User can dismiss the notice by clicking the X button
3. Notice won't appear again UNLESS new errors are logged
4. Each user has their own dismiss state (per-user, not global)

## Technical Implementation

### Files Modified

1. **includes/class-wpinsight-logger.php** (107 new lines)
   - `display_admin_notices()` - Enhanced with dismiss state checking
   - `get_latest_error_timestamp()` - NEW private method to get latest critical error timestamp
   - `ajax_dismiss_errors()` - NEW public method to handle AJAX dismiss request

2. **includes/class-wpinsight-bootstrap.php** (34 new lines)
   - Registered AJAX handler: `wp_ajax_wpinsight_dismiss_errors`
   - Registered admin script enqueue: `admin_enqueue_scripts` hook
   - `enqueue_admin_scripts()` - NEW public method to load JavaScript with nonce

3. **assets/admin.js** (NEW FILE - 34 lines)
   - jQuery handler for notice dismiss button click
   - Sends AJAX request to store dismiss timestamp
   - No visual feedback needed (WordPress handles dismiss animation)

### Database Schema

No new tables required. Uses WordPress user meta:
- Meta key: `wpinsight_errors_dismissed_at`
- Meta value: MySQL datetime string (e.g., '2026-01-31 18:52:00')
- Scope: Per-user (each admin user has their own dismiss state)

### Logic Flow

1. **On page load** (`display_admin_notices()`):
   ```
   - Query: Get latest EMERGENCY/ERROR timestamp from error_log
   - If no errors: Don't show notice, exit
   - Get user meta: wpinsight_errors_dismissed_at
   - If dismissed_at exists AND latest_error <= dismissed_at:
       Don't show notice (already dismissed)
   - Else:
       Show notice (new errors or never dismissed)
   ```

2. **On dismiss click** (JavaScript):
   ```
   - User clicks .notice-dismiss button
   - jQuery sends AJAX POST request
   - PHP: Store current timestamp in user meta
   - Notice disappears (WordPress default behavior)
   ```

3. **When new errors occur**:
   ```
   - New error logged via WPInsight_Logger::error()
   - created_at timestamp > dismissed_at timestamp
   - Notice appears again on next page load
   ```

### Security

- Nonce validation: `wpinsight_dismiss_errors` nonce checked in AJAX handler
- User capability: Only logged-in users can dismiss (WordPress requirement)
- No XSS risk: All output properly escaped with `esc_attr()`, `esc_html()`
- No SQL injection: Uses prepared statements with `$wpdb->prepare()`

## Testing

### Manual Testing Steps

1. **Test dismissal persists**:
   ```bash
   # Trigger some errors
   wp wpinsight errors:create --severity=error --message="Test error 1"

   # Reload WPInsight admin page
   # - Notice should appear

   # Click dismiss button (X)
   # - Notice disappears

   # Reload page again
   # - Notice should NOT appear (stay dismissed)
   ```

2. **Test new errors re-trigger notice**:
   ```bash
   # After dismissing notice, trigger NEW error
   wp wpinsight errors:create --severity=error --message="New test error"

   # Reload WPInsight admin page
   # - Notice should appear again (new error)
   ```

3. **Test per-user dismiss state**:
   ```bash
   # Login as Admin User A
   # - Dismiss notice
   # - Notice stays dismissed

   # Login as Admin User B (different user)
   # - Notice should appear (different user, not dismissed yet)
   ```

### Browser Console Verification

Open browser console on WPInsight admin page:

```javascript
// Check JavaScript is loaded
typeof wpinsightAdmin
// Should output: "object"

// Check nonce exists
wpinsightAdmin.dismissErrorsNonce
// Should output: string like "a1b2c3d4e5..."

// Manually trigger dismiss (for testing)
jQuery.post(wpinsightAdmin.ajaxUrl, {
    action: 'wpinsight_dismiss_errors',
    nonce: wpinsightAdmin.dismissErrorsNonce
}).done(function(response) {
    console.log(response); // Should show {success: true}
});
```

### Database Verification

Check user meta table:

```sql
-- View all dismiss timestamps
SELECT user_id, meta_value
FROM wp_usermeta
WHERE meta_key = 'wpinsight_errors_dismissed_at';

-- View for specific user
SELECT meta_value
FROM wp_usermeta
WHERE meta_key = 'wpinsight_errors_dismissed_at'
AND user_id = 1;

-- View latest error timestamp
SELECT MAX(created_at)
FROM wp_wpinsight_error_log
WHERE severity IN ('emergency', 'error');
```

## Code Quality

All modified files pass:
- ✅ PHPCS (WordPress-Core, WordPress-Extra standards)
- ✅ PHPStan Level 8 (no type errors)
- ✅ Existing test suite (91/93 passing, 2 pre-existing failures unrelated)

## Performance Impact

- **Page load**: +1 query (check latest error timestamp), cached in PHP
- **User meta**: +1 row per user (tiny overhead, ~100 bytes)
- **AJAX call**: Non-blocking, fires on dismiss only

## Backwards Compatibility

- No breaking changes
- If user meta doesn't exist: Defaults to showing notice (safe)
- JavaScript gracefully degrades if jQuery unavailable (rare)

## Future Enhancements (Optional)

1. Add "Don't show this again" checkbox for permanent suppression
2. Group errors by type and dismiss individually
3. Email digest of dismissed errors (if notification emails enabled)
4. Show "X new errors since you last checked" counter

## Related Files

- Phase 5 Plan: `/root/.claude/plans/radiant-wondering-peach.md`
- Logger Class: `includes/class-wpinsight-logger.php`
- Bootstrap: `includes/class-wpinsight-bootstrap.php`
- JavaScript: `assets/admin.js`
