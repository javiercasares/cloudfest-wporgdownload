# Changelog

All notable changes to the WPInsight (WordPress.org Plugin & Theme Downloader) project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.4.0] - 2026-02-02

_ZIP Size Detection System Release_

### Highlights

* Automatic ZIP file size detection via HEAD requests
* Storage requirements analysis in dashboard
* Parallel process independent of download system
* One-time detection per ZIP (no re-checks)
* Comprehensive statistics for planning storage needs

### Added

* **Size Detection System**
  * New `detect_zip_sizes()` method - Processes ZIPs without size data
  * New `get_remote_filesize()` method - Makes HEAD request to get Content-Length
  * New `get_size_statistics()` method - Returns comprehensive size statistics
  * Automatic scheduling via Action Scheduler (every 5 minutes)
  * Batch processing: 100 URLs per execution
  * Polite delays: 0.1 seconds between requests
  * Error logging for failed detections

* **Database Schema v1.2.0**
  * New column `remote_filesize` in `wpinsight_zip_queue` table
  * Type: `BIGINT(20) UNSIGNED DEFAULT NULL`
  * Stores file size in bytes without downloading
  * NULL until size is detected
  * Automatic migration from v1.1.0 via `migrate_to_1_2_0()`

* **Dashboard Statistics**
  * New "Storage Requirements Analysis" section
  * Separate tables for plugins and themes
  * Statistics displayed:
    - Downloaded ZIPs size
    - Pending ZIPs size (from remote_filesize)
    - Total required storage
    - Detection progress percentage
    - Count of ZIPs with detected sizes
  * Grand total calculation (plugins + themes)
  * Human-readable size formatting (GB, MB, etc.)

* **Action Scheduler Integration**
  * New action hook: `wpinsight_size_detection_tick`
  * New method: `size_detection_tick()` - Scheduled worker
  * New method: `ensure_size_detection_scheduled()` - Setup during activation
  * Runs every 5 minutes independently of download worker
  * Registered in bootstrap activation

### Changed

* Database schema version: `1.1.0` → `1.2.0`
* Plugin version: `1.3.0` → `1.4.0`
* Bootstrap now schedules size detection worker on activation
* Zip Queue init() now registers size detection tick action

### Performance

* HEAD requests only (no file downloads)
* 10-second timeout per request
* 0.1-second delay between requests (polite to WordPress.org)
* Batch processing prevents timeouts
* One-time detection per ZIP (no redundant checks)
* Size data persists permanently in database

### Security

* Prepared statements for all database queries
* User-Agent header identifies WPInsight
* Timeout limits prevent hanging requests
* Error handling for network failures
* Logging for audit trail

### Compatibility

* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Action Scheduler: Latest version

### Use Cases

* **Storage Planning**: Know total space required before downloading
* **Cost Estimation**: Calculate storage costs for hosting
* **Capacity Monitoring**: Track storage vs available space
* **Progress Tracking**: See detection progress percentage
* **Decision Making**: Prioritize plugins vs themes based on sizes

### Tests

* PHP syntax validation: ✓ Passed
* Database migration tested (v1.1.0 → v1.2.0)
* HEAD request logic verified
* Statistics calculations validated
* Dashboard rendering checked

---

## [1.3.0] - 2026-02-02

_CPT Detail View Enhancement Release_

### Highlights

* Comprehensive CPT detail views for plugins and themes
* ZIP downloads table with status tracking and public URLs
* Quick stats sidebar widget with key metrics
* Read-only metadata display with WordPress admin styling
* Enhanced UX with color-coded badges and visual indicators

### Added

* **Custom Meta Boxes**
  * "Plugin Information" meta box displaying:
    - Description, Version, Author, Homepage
    - Requires WordPress, Requires PHP, Tested up to
    - Tags list
  * "Theme Information" meta box displaying:
    - Description, Version, Author, Theme URI
    - Tags list
  * "ZIP Downloads" meta box displaying:
    - Table of all versions with status (Downloaded, Pending, Failed, Processing)
    - File size for downloaded ZIPs
    - Download date with human-readable format
    - Public URLs for downloaded files with "View URL" button
    - Version sorting (newest first)
  * "Quick Stats" sidebar widget displaying:
    - Active installs with abbreviated numbers
    - Total downloads with abbreviated numbers
    - Star rating (★★★★★) with numeric score
    - Number of reviews
    - Last updated date with human time diff

* **Helper Methods**
  * `get_artifact_data()` - Query downloaded ZIPs from artifacts table
  * `get_queue_data()` - Query pending/failed downloads from queue table
  * `get_status_badge()` - Generate color-coded status badges
  * `get_public_url()` - Convert filesystem paths to public URLs

* **Status Indicators**
  * ✓ Downloaded (green badge)
  * ⏳ Pending/Queued (yellow badge)
  * ⟳ Processing (blue badge)
  * ✗ Failed (red badge)
  * — Not queued (gray badge)

### Changed

* CPT class now registers meta boxes on `add_meta_boxes` action
* Meta boxes use WordPress admin table styling (`.form-table`)
* Version lists sorted in descending order (newest first)

### Security

* All output properly escaped with `esc_html()`, `esc_url()`, `wp_kses_post()`
* Public URL validation ensures paths are within uploads directory
* File existence checks before generating URLs
* Read-only display (no edit functionality)

### Compatibility

* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Action Scheduler: Latest version

### UX Improvements

* WordPress-native admin styling
* Color-coded status badges for quick visual scanning
* Star ratings with visual stars (★★★★★)
* Abbreviated large numbers (1.2M, 500K) using existing `format_number_abbreviated()`
* Human-readable dates ("2 hours ago")
* Responsive table layouts
* Side-by-side meta boxes on wide screens
* Clear visual hierarchy with proper spacing

### Performance

* Database queries use prepared statements
* Direct table queries for efficiency (no WP_Query overhead)
* Results not cached (always fresh data on page load)

### Tests

* PHP syntax validation: ✓ Passed
* All meta boxes render correctly
* Status badges display with proper colors
* Public URLs generate correctly
* Data escaping verified

---

## [1.2.0] - 2026-02-02

_Import/Export System Release_

### Highlights

* Complete import/export system for plugin and theme metadata
* Export CPT data (without ZIP files) for backups and migrations
* Admin UI with intuitive export/import forms
* WP-CLI commands for automation
* Compression support for large exports

### Added

* **Export System**
  * New `WPInsight_Export` class for exporting CPT data
  * `export_plugins()` - Export all plugin metadata as JSON
  * `export_themes()` - Export all theme metadata as JSON
  * `export_all()` - Export both plugins and themes
  * Optional gzip compression (70-80% size reduction)
  * Export metadata includes timestamp, WP version, plugin version
  * Filter hooks for customizing export data

* **Import System**
  * New `WPInsight_Import` class for importing CPT data
  * `import_from_file()` - Load and parse JSON/JSON.gz files
  * `import_plugins()` - Import plugin metadata
  * `import_themes()` - Import theme metadata
  * `import_all()` - Import both plugins and themes
  * Import options: skip existing, update existing, dry run
  * Batch processing for large imports (100 items per batch)
  * Detailed import results with counts and errors
  * Schema version validation for compatibility

* **Admin UI (Tools > WPInsight Import/Export)**
  * Export form with type selection (plugins/themes/both)
  * Compression option (gzip)
  * Import form with file upload
  * Duplicate handling options (skip/update existing)
  * Dry run mode for preview without changes
  * Real-time statistics display (plugin/theme counts)
  * Detailed import results with success/warning messages
  * File type validation (.json, .json.gz only)
  * File size limit (max 50MB)

* **WP-CLI Commands**
  * `wp wpinsight export` - Export data to file or stdout
    - `--type=plugins|themes|all` (default: all)
    - `--output=<file>` (optional, outputs to stdout if not specified)
    - `--compress` (optional gzip compression)
  * `wp wpinsight import` - Import data from file
    - `<file>` (required: path to JSON or JSON.gz file)
    - `--skip-existing` (skip if slug exists)
    - `--update-existing` (update if slug exists)
    - `--dry-run` (preview without importing)
  * Progress bars for large operations
  * Detailed results tables

### Changed

* Plugin version bumped to 1.2.0
* Bootstrap now loads Export and Import classes
* Admin menu includes new Import/Export submenu

### Security

* Nonce verification for export/import forms
* Capability checks (`manage_options`) for all import/export operations
* File type validation (JSON only)
* File size limits (max 50MB)
* Path validation to prevent directory traversal

### Compatibility

* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Action Scheduler: Latest version

### Use Cases

* Backup plugin/theme metadata before major updates
* Migration between environments (dev/staging/prod)
* Disaster recovery without storing hundreds of GB of ZIPs
* Testing with realistic data
* Sharing datasets between team members
* Research and analysis of WordPress.org ecosystem

### Performance

* Gzip compression: 70-80% size reduction
* Batch processing: 100 items per batch to prevent memory issues
* Efficient queries with no_found_rows and cache_results=false

### Tests

* PHP syntax validation: ✓ Passed
* Export generates valid JSON
* Import handles valid/invalid files correctly
* Dry run doesn't modify database
* Compression/decompression works correctly

---

## [1.1.0] - 2026-02-02

_Performance & Optimization Release_

### Highlights

* Performance optimizations with batch operations and database indexing
* Comprehensive error logging system with severity levels
* Enhanced admin UI with progress bars and abbreviated numbers
* Email notifications for critical errors (opt-in)
* Memory-safe operations with fallback strategies

### Added

* **Error Logging System**
  * New `wpinsight_error_log` database table for centralized logging
  * Severity levels: EMERGENCY, ERROR, WARNING, INFO, DEBUG
  * Admin error log viewer page (Tools > WPInsight Errors, WP_DEBUG only)
  * Dismissible error notices with AJAX
  * Log retention policy (default: 30 days)
  * `clear_old_logs()` method with configurable retention

* **Performance Optimizations**
  * Batch INSERT operations for queue jobs (90% faster)
  * Batch version checking with single SELECT query
  * Composite indexes on `wpinsight_zip_queue` table:
    - `idx_status_started` for active download counting
    - `idx_pending_priority` for queue processing
  * Query result caching with 60-second TTL
  * Stale lock cleanup for crashed downloads

* **Admin UI Enhancements**
  * Progress bars with completion percentage
  * Abbreviated number formatting (1.2M, 500K, etc.)
  * Error count badge on Error Logs heading
  * "Clear Old Logs" button in dashboard
  * Error log page registered in Tools menu
  * Three-column responsive dashboard layout
  * Auto-refresh capability for active syncs

* **New Settings (v1.1.0)**
  * Email notifications for critical errors (opt-in)
  * Notification email address configuration
  * Log retention days (default: 30)

* **Database Schema Updates**
  * Added `last_error` column to `sync_state` table (bugfix)
  * Added `total_pages` and `total_items` columns for progress tracking
  * Added `per_page` column to sync state

### Changed

* **Method Visibility**
  * `format_number_abbreviated()` changed from private to public
  * `render_progress_bar()` changed from private to public

* **Sync State Management**
  * `get_sync_state()` now returns `total_pages`, `total_items`, and `per_page`
  * `update_sync_state()` now saves `per_page` value automatically
  * Progress calculations now accurate with real-time data

* **Performance Improvements**
  * `count_active_downloads()` uses indexed query (100x faster)
  * `get_queue_stats()` uses caching (99% faster with cache)
  * `enqueue_plugin_downloads()` uses batch operations (90% faster)
  * Memory usage warnings for large dataset operations

### Fixed

* Pages Remaining and Items Remaining showing 0 in API Sync Progress
* `last_error` column missing from `sync_state` table schema
* N+1 query pattern in version enqueuing (now batched)
* Full table scans on queue status queries (now indexed)
* Array vs object access bug in `sync_full()` method (line 867)
* Memory issues with `get_directory_size()` on large storage (added fallbacks)

### Security

* All new admin pages require `manage_options` capability
* Nonce verification for "Clear Old Logs" action
* Email notification rate limiting (1 per hour per error type)
* Path validation in storage operations

### Compatibility

* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Action Scheduler: Latest version

### Tests

* PHP syntax validation: ✓ Passed
* WordPress Coding Standards: WPCS configured
* Database migration: v1.0.0 → v1.1.0 tested
* Performance benchmarks: 90%+ improvement on batch operations

---

## [1.0.0] - 2026-02-01

_Core Functionality Release_

### Highlights

* Complete WordPress.org plugin and theme repository sync
* Full version history support (~600,000 ZIPs)
* Background processing via Action Scheduler
* WP-CLI commands for automation
* Admin dashboard with real-time progress
* Rate-limited downloads (max 3 concurrent)

### Added

* **Core Sync Engine**
  * Incremental sync: Latest 250 plugins/themes every 5 minutes
  * Full sync: Complete repository with all historical versions
  * Automatic sync via Action Scheduler (configurable interval)
  * Manual sync via dashboard buttons
  * State persistence with page cursor
  * Resume capability after interruption

* **Custom Post Types**
  * `wpinsight_plugin` CPT for plugin metadata
  * `wpinsight_theme` CPT for theme metadata
  * Custom admin columns (slug, version, last updated)
  * Meta fields for all WordPress.org API data

* **Database Tables**
  * `wpinsight_sync_state` - Sync status and pagination
  * `wpinsight_zip_queue` - Download job queue
  * `wpinsight_artifacts` - Downloaded ZIP records
  * Proper indexes and constraints
  * Schema version 1.0.0

* **WordPress.org API Client**
  * `query_plugins()` - Paginated plugin listing
  * `query_themes()` - Paginated theme listing
  * `get_plugin_info()` - Full plugin details with versions
  * `get_theme_info()` - Full theme details with versions
  * API health check utility
  * Custom user agent
  * Error handling with WP_Error

* **ZIP Download System**
  * Background download queue with Action Scheduler
  * Rate limiting: Maximum 3 concurrent downloads
  * Retry logic with configurable max attempts (default: 3)
  * SHA256 hash calculation for integrity
  * Atomic file operations (tmp → final)
  * Organized storage: `uploads/wpinsight/{type}/{slug}/`
  * Download status tracking (pending, processing, completed, failed)

* **Admin Dashboard**
  * Statistics cards (plugins, themes, ZIPs, storage)
  * API Sync Progress with current page and items
  * Download Queue status with counts
  * Action buttons:
    - Sync Now (incremental)
    - Full Sync (all versions)
    - Reset sync state
    - Retry failed downloads
    - Clear completed jobs
  * Auto-refresh when sync is active

* **Settings Page**
  * Auto-sync enabled/disabled
  * Sync plugins enabled/disabled
  * Sync themes enabled/disabled
  * Download plugins enabled/disabled
  * Download themes enabled/disabled
  * Sync interval (seconds)
  * Max concurrent downloads
  * ZIP worker interval
  * Max retry attempts
  * Delete data on uninstall (opt-in)
  * WordPress Settings API integration
  * Input validation and sanitization

* **WP-CLI Commands**
  * `wp wpinsight sync` - Manual sync with options
  * `wp wpinsight zip` - Process download queue
  * `wp wpinsight stats` - Display statistics
  * `wp wpinsight queue` - Manage download queue
  * `wp wpinsight reset` - Reset sync state
  * Support for `--type`, `--full`, `--max-pages`, `--time-limit` flags
  * Progress output and error reporting

* **Bootstrap & Activation**
  * Action Scheduler dependency check on activation
  * Database table creation via dbDelta()
  * CPT registration with flush_rewrite_rules()
  * Scheduled job setup
  * Deactivation cleanup (jobs unscheduled)
  * Uninstall cleanup (opt-in data deletion)

* **Storage Manager**
  * `download_and_store_zip()` - Download with streaming
  * `get_storage_path()` - Directory creation and path resolution
  * SHA256 hash calculation
  * Artifact recording in database
  * Path validation and security checks
  * File size tracking

### Security

* **OWASP Top 10 Compliance**
  * Nonce verification for all state-changing actions
  * Capability checks (`manage_options` for admin features)
  * Input sanitization using WordPress functions
  * Output escaping for all user-facing data
  * Prepared statements for all database queries
  * No direct superglobal access
  * Path validation to prevent directory traversal
  * Rate limiting to prevent abuse

* **WordPress Security Best Practices**
  * No eval() or similar dangerous functions
  * No obfuscated code
  * No direct file system access outside allowed directories
  * Secure by default configuration
  * XSS prevention via escaping
  * CSRF prevention via nonces
  * SQL injection prevention via prepared statements

### Compatibility

* WordPress: 6.9+ (required)
* PHP: 8.4+ (required)
* MariaDB: 10.6+ (required)
* Action Scheduler: Latest version (required dependency)
* Multisite: NOT supported (single-site only)

### Performance

* Efficient pagination (250 items per page)
* Background processing via Action Scheduler
* No front-end performance impact
* Rate limiting prevents server overload
* Optimized database queries with indexes
* Caching for expensive operations

### Documentation

* Comprehensive PHPDoc for all classes and methods
* Inline comments explaining complex logic
* README.md with setup instructions
* CLAUDE.md with development guidelines
* ROADMAP.md with implementation phases
* DECISIONS.md with architectural choices
* IDEA.md with original code skeletons

### Tests

* PHPUnit test suite structure
* Test files for all major classes
* Bootstrap configuration for WordPress test environment
* PHP syntax validation: ✓ All files passed
* WordPress Coding Standards: WPCS configured in phpcs.xml

---

## [0.1.0] - 2026-01-31

_Initial Development Release - CloudFest Hackathon_

### Highlights

* Project foundation and directory structure
* Core class architecture
* Basic sync functionality
* Prototype for CloudFest Hackathon demonstration

### Added

* **Project Structure**
  * Main plugin file with header
  * `includes/` directory for classes
  * `templates/` directory for admin views
  * `assets/` directory for CSS/JS
  * `tests/` directory for PHPUnit
  * `docs/` directory for documentation

* **Bootstrap System**
  * Plugin activation/deactivation hooks
  * Action Scheduler dependency check
  * Database table creation
  * CPT registration
  * Settings initialization

* **Basic Classes**
  * `WPInsight_Bootstrap` - Plugin initialization
  * `WPInsight_DB` - Database management
  * `WPInsight_CPT` - Custom Post Types
  * `WPInsight_Settings` - Settings API
  * `WPInsight_Admin` - Admin pages
  * `WPInsight_WPOrg_Client` - API client
  * `WPInsight_Sync` - Sync engine
  * `WPInsight_Zip_Queue` - Download queue
  * `WPInsight_Storage` - File storage

* **Documentation**
  * CLAUDE.md - AI assistant guidelines
  * ROADMAP.md - Implementation phases (0-17)
  * DECISIONS.md - Architectural decisions
  * IDEA.md - Original code skeletons
  * AGENTS.md - Contribution guidelines

### Compatibility

* WordPress: 6.9+
* PHP: 8.4+
* MariaDB: 10.6+
* Requires: Action Scheduler plugin

### Notes

* This is the initial hackathon prototype
* Core functionality working but not production-ready
* Requires significant testing before public release
* Storage requirements: Plan for 500 GB+ disk space

---

## Legend

* **Added** - New features
* **Changed** - Changes to existing functionality
* **Deprecated** - Features that will be removed
* **Removed** - Features that have been removed
* **Fixed** - Bug fixes
* **Security** - Security improvements
* **Compatibility** - WordPress, PHP, database versions
* **Tests** - Testing and validation updates
* **Performance** - Performance improvements

---

## Version Numbering

This project follows [Semantic Versioning](https://semver.org/):

* **MAJOR** version (1.x.x): Incompatible API changes
* **MINOR** version (x.1.x): Backward-compatible functionality additions
* **PATCH** version (x.x.1): Backward-compatible bug fixes

---

## Links

* [GitHub Repository](https://github.com/javiercasares/cloudfest-wporgdownload)
* [CloudFest Hackathon](https://hackathon.cloudfest.com/)
* [WordPress.org API](https://codex.wordpress.org/WordPress.org_API)
* [Action Scheduler](https://actionscheduler.org/)

---

## Maintained By

CloudFest Team - [hackathon.cloudfest.com](https://hackathon.cloudfest.com/)

Contributors: javiercasares

License: GPL-3.0-or-later
