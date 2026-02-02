=== WPInsight - WordPress.org Plugin & Theme Downloader ===
Contributors: javiercasares
Tags: wordpress.org, plugins, themes, archive, mirror, backup, downloader
Requires at least: 6.9
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 8.4
Requires Plugins: action-scheduler
Version: 1.1.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Creates a complete mirror of WordPress.org's plugin and theme repositories with full version history, metadata, and downloadable ZIP archives.

== Description ==

**WPInsight** is a comprehensive WordPress plugin developed for the CloudFest Hackathon that downloads and archives **ALL WordPress.org plugins and themes**, including their complete version history.

= Key Features =

* **Full Repository Mirror**: Syncs 60,000+ plugins and 11,000+ themes from WordPress.org
* **Historical Version Tracking**: Downloads every version of every plugin/theme (~600,000 ZIP files)
* **Metadata Storage**: Stores plugin/theme metadata in Custom Post Types for easy searching
* **Background Processing**: Uses Action Scheduler for reliable, non-blocking sync operations
* **Rate-Limited Downloads**: Maximum 3 concurrent downloads to respect WordPress.org servers
* **Incremental Sync**: Automatic sync every 5 minutes for new updates (configurable)
* **Full Sync Mode**: One-time complete sync of entire repository with version history
* **WP-CLI Integration**: Command-line tools for manual operations and automation
* **Admin Dashboard**: Comprehensive UI showing sync progress, download queue, and statistics
* **Storage Management**: Organized file structure in wp-content/uploads/wpinsight/

= Use Cases =

* Create a local WordPress.org plugin/theme mirror
* Archive historical versions for research or compatibility testing
* Build custom plugin/theme directory with all versions
* Offline development environment with full plugin/theme access
* Compliance and security auditing of plugin versions
* Educational purposes and WordPress ecosystem analysis

= Technical Highlights =

* **Modern PHP**: Requires PHP 8.4+ with typed properties and modern features
* **Database Optimization**: Custom tables with indexes for fast queries
* **Batch Operations**: Efficient bulk processing to minimize database load
* **Error Logging**: Comprehensive logging system with severity levels
* **Security**: OWASP Top 10 compliant with nonces, capability checks, sanitization
* **WordPress Standards**: Follows WordPress Coding Standards and Plugin Handbook

= Storage Requirements =

* **Database**: ~50 MB for metadata (60K plugins + 11K themes)
* **Disk Space**: ~200-300 GB for all ZIP files (estimated, varies)
* **Memory**: 256 MB PHP memory limit recommended
* **Processing**: Action Scheduler handles background jobs automatically

**IMPORTANT**: This plugin requires significant disk space. Plan for at least 500 GB free space before starting a full sync.

= Dependencies =

This plugin **REQUIRES** the Action Scheduler plugin (or WooCommerce which includes it):
* [Action Scheduler](https://wordpress.org/plugins/action-scheduler/) - Free, by Automattic

The plugin will **NOT activate** without Action Scheduler being available.

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in your WordPress admin
2. Search for "Action Scheduler" and install it first (REQUIRED)
3. Upload the WPInsight plugin ZIP file
4. Click Install Now and then Activate
5. Go to Tools > WPInsight to access the dashboard

= Manual Installation =

1. Install and activate Action Scheduler plugin first
2. Download the WPInsight plugin ZIP file
3. Extract contents and upload to `/wp-content/plugins/cloudfest-wporgdownload/`
4. Activate the plugin through the Plugins menu
5. Navigate to Tools > WPInsight

= First-Time Setup =

After activation:

1. **Check Dashboard**: Go to Tools > WPInsight Dashboard
2. **Configure Settings**: Visit Settings > WPInsight to adjust:
   - Sync interval (default: 5 minutes)
   - Items per page (default: 250)
   - Max concurrent downloads (default: 3)
   - Enable/disable plugin or theme syncing
3. **Start Incremental Sync**: Click "Sync Now" to fetch the latest 250 items
4. **Optional Full Sync**: Click "Full Sync" to download ALL versions (takes days)

The plugin will automatically sync new updates every 5 minutes once activated.

== Using the Plugin ==

= Admin Dashboard =

The dashboard (Tools > WPInsight) shows:

* **Statistics**: Plugin/theme counts, downloaded ZIPs, storage used
* **API Sync Progress**: Real-time progress bars for active syncs
* **Download Queue**: Pending, processing, completed, and failed downloads
* **Recent Errors**: Last 10 errors with severity levels

= Action Buttons =

* **Sync Now**: Fetch latest 250 plugins/themes (incremental sync)
* **Full Sync**: Download ALL plugins/themes with full version history
* **Reset**: Clear sync state and start from page 1
* **Retry Failed**: Retry failed download jobs
* **Clear Completed**: Remove completed jobs from queue

= WP-CLI Commands =

**Sync Commands:**
```
# Sync latest plugins (incremental)
wp wpinsight sync --type=plugins

# Sync all themes with full version history
wp wpinsight sync --type=themes --full

# Full sync with limits
wp wpinsight sync --type=plugins --full --max-pages=100
```

**Queue Management:**
```
# Process download queue manually
wp wpinsight zip

# Show statistics
wp wpinsight stats

# Retry failed downloads
wp wpinsight queue retry --limit=100

# Clear completed jobs
wp wpinsight queue clear --days=7
```

**State Management:**
```
# Reset plugin sync state
wp wpinsight reset --type=plugins

# Reset everything
wp wpinsight reset --type=both
```

= File Organization =

Downloaded ZIPs are organized as:
```
wp-content/uploads/wpinsight/
├── plugin/
│   ├── akismet/
│   │   ├── akismet.5.3.1.zip
│   │   ├── akismet.5.3.0.zip
│   │   └── akismet.5.2.0.zip
│   └── hello-dolly/
│       └── hello-dolly.1.7.2.zip
└── theme/
    ├── twentytwentyfour/
    │   └── twentytwentyfour.1.0.zip
    └── ...
```

= Settings =

Configure behavior in Settings > WPInsight:

**General:**
* Delete data on uninstall (default: preserve data)

**Sync Settings:**
* Auto-sync enabled (default: yes)
* Sync plugins enabled (default: yes)
* Sync themes enabled (default: yes)
* Download plugins enabled (default: yes)
* Download themes enabled (default: yes)
* Sync interval (default: 300 seconds)

**Rate Limiting:**
* Max concurrent downloads (default: 3)
* ZIP worker interval (default: 60 seconds)
* Max retry attempts (default: 3)

**Notifications (v1.1.0+):**
* Email notifications for critical errors (default: disabled)
* Notification email address
* Log retention days (default: 30)

== Frequently Asked Questions ==

= How long does a full sync take? =

A full sync of 60,000+ plugins with all versions (~600,000 ZIPs) can take **several days to weeks**, depending on:
* Your server speed
* Network bandwidth
* WordPress.org API response times
* Concurrent download limit (default: 3)

Start with incremental sync first to test before attempting a full sync.

= Can I run this on shared hosting? =

**Not recommended**. This plugin requires:
* Significant disk space (200-300 GB minimum)
* Reliable background processing via WP-Cron or system cron
* PHP 8.4+
* Sufficient memory (256 MB recommended)

A VPS or dedicated server is strongly recommended.

= Does this work with Multisite? =

**No**. The plugin explicitly does not support WordPress Multisite. It's designed for single-site installations only.

= Will this affect my site's performance? =

The plugin uses Action Scheduler for background processing, so:
* Sync operations run in the background
* Rate limiting prevents overwhelming your server
* Caching reduces database load
* No impact on front-end site performance

However, the database grows significantly (custom tables for queue and artifacts).

= How much disk space do I need? =

Plan for:
* **Minimum**: 100 GB (partial sync of popular plugins)
* **Recommended**: 500 GB (full plugin repository)
* **Comfortable**: 1 TB (plugins + themes with room to grow)

Monitor storage via the dashboard Statistics card.

= Can I pause and resume a full sync? =

**Yes**. The plugin saves sync state (current page) in the database. If interrupted:
* Stop the sync via "Reset" button or deactivate plugin
* Resume later - it continues from the last saved page

= What happens if a download fails? =

Failed downloads are:
1. Logged with error details
2. Marked as "failed" in the queue
3. Can be retried via "Retry Failed" button
4. Automatically retried up to 3 times (configurable)

= How do I access downloaded ZIPs? =

ZIPs are stored in `wp-content/uploads/wpinsight/`. You can:
* Access via SFTP/SSH
* Create custom code to serve files
* Query the `wpinsight_artifacts` database table for file paths

Future versions may include public URL generation.

= Is this approved by WordPress.org? =

This is an **independent archival tool** for research and development purposes. Always respect WordPress.org's:
* Terms of Service
* Rate limiting (plugin uses max 3 concurrent downloads)
* Server resources (plugin includes polite delays)

= Can I contribute? =

Yes! This plugin was developed during CloudFest Hackathon. Contributions welcome:
* GitHub: https://github.com/javiercasares/cloudfest-wporgdownload
* Issues: Report bugs via GitHub Issues
* Pull Requests: Follow WordPress Coding Standards

== Screenshots ==

1. Dashboard overview with statistics, sync progress, and download queue
2. Settings page with sync intervals and rate limiting options
3. Error log viewer (when WP_DEBUG is enabled)
4. WP-CLI commands output showing sync progress

== Changelog ==

= 1.1.0 - 2026-02-02 =

**Performance & Optimization Release**

* **Performance Optimizations**
  * Batch INSERT operations for queue jobs (90% faster)
  * Batch version checking with single SELECT query
  * Composite indexes on queue table (100x faster queries)
  * Query result caching with 60-second TTL
  * Stale lock cleanup for crashed downloads

* **Error Logging System**
  * New error_log database table with severity levels
  * Admin error log viewer page (Tools > WPInsight Errors, WP_DEBUG only)
  * Dismissible error notices with AJAX
  * Log retention policy (default: 30 days)
  * Email notifications for critical errors (opt-in)

* **Admin UI Enhancements**
  * Progress bars with completion percentage
  * Abbreviated number formatting (1.2M, 500K, etc.)
  * Error count badge on Error Logs heading
  * "Clear Old Logs" button in dashboard
  * Three-column responsive dashboard layout
  * Auto-refresh capability for active syncs

* **Bug Fixes**
  * Fixed missing last_error column in sync_state table
  * Fixed array vs object access bug in sync_full() method
  * Fixed N+1 query pattern in version enqueuing
  * Fixed memory issues with get_directory_size() on large storage

**Compatibility:**
* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Action Scheduler: Latest version

= 1.0.0 - 2026-02-01 =

**Core Functionality Release**

* Complete WordPress.org plugin and theme repository sync
* Full version history support (~600,000 ZIPs)
* Background processing via Action Scheduler
* WP-CLI commands for automation
* Admin dashboard with real-time progress
* Rate-limited downloads (max 3 concurrent)
* Custom Post Types for metadata storage
* ZIP download queue with retry logic
* Organized file storage structure
* Settings page with WordPress Settings API
* Security: OWASP Top 10 compliant

**Compatibility:**
* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+

= 0.1.0 - 2026-01-31 =

**Initial Development Release - CloudFest Hackathon**

* Project foundation and directory structure
* Core class architecture
* Basic sync functionality
* Prototype for CloudFest Hackathon demonstration

== Previous Versions ==

Full changelog with detailed changes available at:
[CHANGELOG.md](https://github.com/javiercasares/cloudfest-wporgdownload/blob/main/CHANGELOG.md)

== Upgrade Notice ==

= 1.1.0 =
Major performance improvements with 90%+ faster batch operations. Adds centralized error logging system and admin UI enhancements. Database schema upgraded to v1.1.0 (automatic migration).

= 1.0.0 =
Core functionality release with complete WordPress.org repository sync, background processing, and WP-CLI commands. Production-ready for archival projects.

= 0.1.0 =
Initial release. Requires WordPress 6.9+ and PHP 8.4+. Install Action Scheduler before activating this plugin.

== Compliance ==

This plugin adheres to the following security measures and review protocols:

* [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
* [WordPress Plugin Security](https://developer.wordpress.org/plugins/wordpress-org/plugin-security/)
* [WordPress APIs Security](https://developer.wordpress.org/apis/security/)
* [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
* [Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/)
* [OWASP Top 10](https://owasp.org/www-project-top-ten/)

**Security Features:**
* Nonce verification for all state-changing actions
* Capability checks (manage_options) for admin features
* Input sanitization using WordPress functions
* Output escaping for all user-facing data
* Prepared statements for all database queries
* No direct file system access outside uploads directory
* Rate limiting to prevent abuse
* No eval() or similar dangerous functions
* No obfuscated code

**Testing:**
* Tested on WordPress 6.9 with PHP 8.4
* Tested on MariaDB 10.6+
* Manual testing on single-site installations
* PHPUnit test suite included
* PHPCS validation with WordPress Coding Standards

== Privacy Policy ==

This plugin:
* Does NOT collect any user data
* Does NOT send data to external services (except WordPress.org API)
* Does NOT track users
* Does NOT use cookies
* Does NOT include analytics or tracking
* Only communicates with WordPress.org API to fetch plugin/theme metadata

Downloaded plugin/theme ZIPs are stored locally on your server.

== Support ==

* **Documentation**: See Installation and FAQ sections above
* **GitHub Issues**: https://github.com/javiercasares/cloudfest-wporgdownload/issues
* **WP-CLI Help**: Run `wp help wpinsight` for command documentation

== Credits ==

* Developed for CloudFest Hackathon 2026
* Built with WordPress Coding Standards
* Uses Action Scheduler by Automattic
* Follows KISS principles - Keep It Simple, Stupid

== License ==

This plugin is licensed under GPLv3 or later.
https://www.gnu.org/licenses/gpl-3.0.html

You are free to use, modify, and distribute this plugin under the terms of the GPL.
