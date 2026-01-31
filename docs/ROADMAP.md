# Implementation Roadmap - Granular Steps

**Project:** WordPress.org Plugin/Theme Downloader (WPInsight)
**Approach:** Incremental, testable, validable at each step
**Requirements:** Every class/function must have PHPDoc, PHPUnit tests where applicable

---

## General Principles

1. **One step at a time** - Complete, test, and validate each step before proceeding
2. **PHPDoc required** - All public functions, methods, classes, constants must be documented
3. **PHPUnit tests** - Create tests for all testable logic (utilities, calculations, transformations)
4. **Validation checkpoint** - Each step includes validation criteria that must pass
5. **No skipping** - Follow the order strictly; each step builds on the previous

---

## Phase 0: Project Foundation

### Step 0.1: Create base plugin file

**File:** `cloudfest-wporgdownload.php` (main plugin file)

**Tasks:**
- [ ] Create plugin header with all required fields
- [ ] Add security check: `if (!defined('ABSPATH')) exit;`
- [ ] Define plugin constants:
  - `WPINSIGHT_VERSION` (string)
  - `WPINSIGHT_PLUGIN_FILE` (__FILE__)
  - `WPINSIGHT_PLUGIN_DIR` (plugin_dir_path)
  - `WPINSIGHT_PLUGIN_URL` (plugin_dir_url)
- [ ] Add PHPDoc file header

**Plugin Header Fields:**
```php
/**
 * Plugin Name: CloudFest WPOrg Download
 * Plugin URI: https://github.com/javiercasares/cloudfest-wporgdownload
 * Description: Downloads and archives ALL WordPress.org plugins including historical versions
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.4
 * Author: CloudFest Team
 * Author URI: https://hackathon.cloudfest.com/
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cloudfest-wporgdownload
 * Domain Path: /languages
 * Network: false
 */
```

**PHPDoc:**
- File header explaining purpose
- Each constant with `@var` and description

**Validation:**
- [ ] Plugin appears in WordPress admin plugins list
- [ ] Constants are defined and accessible
- [ ] No PHP errors on plugin list page

---

### Step 0.2: Create uninstall.php

**File:** `uninstall.php`

**Tasks:**
- [ ] Security check: `if (!defined('WP_UNINSTALL_PLUGIN')) exit;`
- [ ] Add PHPDoc file header
- [ ] Create option name constant for "delete data on uninstall" setting
- [ ] Check if user opted in to data deletion
- [ ] If opted in:
  - Delete all CPT posts (to be implemented later, leave TODO comment)
  - Drop custom tables (to be implemented later, leave TODO comment)
  - Delete all options
  - Delete all transients
  - Delete uploaded files (to be implemented later, leave TODO comment)
- [ ] Add extensive inline comments explaining each step

**PHPDoc:**
- File header with description and security warnings

**Validation:**
- [ ] File exists and has proper security check
- [ ] No syntax errors
- [ ] Gracefully handles when option doesn't exist yet

---

### Step 0.3: Create directory structure

**Tasks:**
- [ ] Create `includes/` directory
- [ ] Create `templates/` directory
- [ ] Create `assets/` directory (optional for hackathon)
- [ ] Create `tests/` directory for PHPUnit tests
- [ ] Create `tests/bootstrap.php` for PHPUnit configuration
- [ ] Create `.gitignore` if not exists

**Directory structure:**
```
cloudfest-wporgdownload/
├── cloudfest-wporgdownload.php
├── uninstall.php
├── includes/
├── templates/
├── assets/
├── tests/
│   └── bootstrap.php
├── docs/
│   ├── PLAN.md
│   ├── DECISIONS.md
│   ├── IDEA.md
│   └── ROADMAP.md
└── README.md
```

**Validation:**
- [ ] All directories exist
- [ ] Proper permissions (755 for directories)

---

### Step 0.4: Setup PHPUnit configuration

**File:** `phpunit.xml`

**Tasks:**
- [ ] Create PHPUnit configuration file
- [ ] Define test suite directories
- [ ] Set bootstrap file
- [ ] Configure coverage (optional)

**Content:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="WPInsight Test Suite">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

**File:** `tests/bootstrap.php`

**Tasks:**
- [ ] Load WordPress test library
- [ ] Load plugin file
- [ ] Set up test environment

**Validation:**
- [ ] Run `vendor/bin/phpunit` (may have 0 tests, that's OK)
- [ ] No configuration errors

---

## Phase 1: Bootstrap & Activation

### Step 1.1: Create bootstrap class

**File:** `includes/class-wpinsight-bootstrap.php`

**Class:** `WPInsight_Bootstrap`

**Methods to implement:**

1. **`init(): void`** (static)
   - Load all required class files
   - Register CPT hook
   - Register admin hooks
   - Register WP-CLI if available
   - Hook Action Scheduler initialization

2. **`activate(): void`** (static)
   - Check Action Scheduler availability
   - If not available, deactivate plugin and show admin notice
   - Call database installation
   - Register CPTs
   - Flush rewrite rules
   - Schedule recurring jobs
   - Set activation timestamp option

3. **`deactivate(): void`** (static)
   - Flush rewrite rules
   - Unschedule all Action Scheduler actions
   - Keep all data (as per requirements)

4. **`check_action_scheduler(): bool`** (static, private)
   - Check if `function_exists('as_schedule_recurring_action')`
   - Return true/false

5. **`show_action_scheduler_notice(): void`** (static, private)
   - Display admin notice if Action Scheduler not found
   - Include installation instructions

**PHPDoc:**
- Class docblock with description, package, version
- Each method with description, `@return`, `@since`

**PHPUnit Tests:**
**File:** `tests/test-bootstrap.php`

- `test_check_action_scheduler_returns_bool()`
- `test_show_action_scheduler_notice_outputs_html()`

**Validation:**
- [ ] Class can be instantiated without errors
- [ ] All methods exist and have correct signatures
- [ ] PHPDoc is complete
- [ ] Tests pass

---

### Step 1.2: Register hooks in main plugin file

**File:** `cloudfest-wporgdownload.php`

**Tasks:**
- [ ] Require bootstrap class file
- [ ] Register activation hook: `register_activation_hook(__FILE__, ['WPInsight_Bootstrap', 'activate'])`
- [ ] Register deactivation hook: `register_deactivation_hook(__FILE__, ['WPInsight_Bootstrap', 'deactivate'])`
- [ ] Hook init on `plugins_loaded`: `add_action('plugins_loaded', ['WPInsight_Bootstrap', 'init'])`

**Validation:**
- [ ] Activate plugin in WordPress admin
- [ ] Check for Action Scheduler notice (should appear if AS not installed)
- [ ] Install Action Scheduler plugin
- [ ] Reactivate plugin
- [ ] No errors on activation
- [ ] Deactivate and reactivate works smoothly

---

## Phase 2: Database Infrastructure

### Step 2.1: Create database class

**File:** `includes/class-wpinsight-db.php`

**Class:** `WPInsight_DB`

**Constants:**
- `DB_VERSION` = 1 (class constant)

**Methods to implement:**

1. **`install(): void`** (static)
   - Get global `$wpdb`
   - Get charset collate
   - Define table names (use `$wpdb->prefix`)
   - Create SQL for `wpinsight_sync_state` table
   - Create SQL for `wpinsight_zip_queue` table
   - Create SQL for `wpinsight_artifacts` table
   - Use `dbDelta()` to create tables
   - Save DB version to options

2. **`maybe_upgrade(): void`** (static)
   - Get current DB version from options
   - Compare with `DB_VERSION` constant
   - If different, call `install()`

3. **`get_table_name(string $table): string`** (static)
   - Return full table name with prefix
   - Validate table name against whitelist

**Table Schemas:**

**wpinsight_sync_state:**
```sql
CREATE TABLE {prefix}wpinsight_sync_state (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type VARCHAR(10) NOT NULL,
  feed VARCHAR(10) NOT NULL,
  last_run_at DATETIME NULL,
  cursor_text TEXT NULL,
  notes TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY entity_feed (entity_type, feed)
) {charset_collate};
```

**wpinsight_zip_queue:**
```sql
CREATE TABLE {prefix}wpinsight_zip_queue (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type VARCHAR(10) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  version VARCHAR(50) NULL,
  download_url TEXT NOT NULL,
  priority INT NOT NULL DEFAULT 10,
  status VARCHAR(20) NOT NULL DEFAULT 'queued',
  attempts INT NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  hash_sha256 CHAR(64) NULL,
  filesize BIGINT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_job (entity_type, slug, version),
  KEY status_priority (status, priority),
  KEY updated_at (updated_at)
) {charset_collate};
```

**wpinsight_artifacts:**
```sql
CREATE TABLE {prefix}wpinsight_artifacts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type VARCHAR(10) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  version VARCHAR(50) NOT NULL,
  zip_path TEXT NOT NULL,
  hash_sha256 CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_art (entity_type, slug, version, hash_sha256(16))
) {charset_collate};
```

**PHPDoc:**
- Class docblock
- Each method with full documentation
- `@global` for `$wpdb` usage

**PHPUnit Tests:**
**File:** `tests/test-db.php`

- `test_get_table_name_returns_correct_format()`
- `test_get_table_name_validates_whitelist()`
- `test_install_creates_tables()` (requires WordPress test environment)

**Validation:**
- [ ] Activate plugin
- [ ] Check database for three new tables
- [ ] Verify table structure matches schema
- [ ] Verify indexes are created
- [ ] Run tests and all pass

---

### Step 2.2: Hook database initialization in bootstrap

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] In `activate()`: require DB class and call `WPInsight_DB::install()`
- [ ] In `init()`: hook `WPInsight_DB::maybe_upgrade()` to `admin_init`

**Validation:**
- [ ] Deactivate and delete tables manually
- [ ] Reactivate plugin
- [ ] Tables are recreated
- [ ] DB version option is saved

---

## Phase 3: Custom Post Types

### Step 3.1: Create CPT class

**File:** `includes/class-wpinsight-cpt.php`

**Class:** `WPInsight_CPT`

**Constants:**
- `CPT_PLUGIN` = 'wpinsight_plugin'
- `CPT_THEME` = 'wpinsight_theme'

**Methods to implement:**

1. **`register(): void`** (static)
   - Register `wpinsight_plugin` CPT
   - Register `wpinsight_theme` CPT
   - Set proper labels, capabilities, supports

2. **`get_plugin_cpt_args(): array`** (static, private)
   - Return CPT registration arguments for plugins
   - Include labels, public settings, menu icon, etc.

3. **`get_theme_cpt_args(): array`** (static, private)
   - Return CPT registration arguments for themes

**CPT Configuration:**
- `public` = false
- `show_ui` = true
- `show_in_menu` = false (we'll create custom menu)
- `supports` = ['title']
- `capability_type` = 'post'
- `capabilities` = ['create_posts' => 'do_not_allow'] (no manual creation)
- `map_meta_cap` = true

**PHPDoc:**
- Class docblock
- Each method documented
- `@return array` for args methods

**PHPUnit Tests:**
**File:** `tests/test-cpt.php`

- `test_get_plugin_cpt_args_returns_array()`
- `test_get_plugin_cpt_args_has_required_keys()`
- `test_get_theme_cpt_args_returns_array()`
- `test_cpt_registration()` (integration test)

**Validation:**
- [ ] Activate plugin
- [ ] CPTs appear in WordPress admin (should be registered)
- [ ] Navigate to CPT lists (should be empty)
- [ ] Tests pass

---

### Step 3.2: Hook CPT registration in bootstrap

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] Require CPT class file in `init()`
- [ ] Call `WPInsight_CPT::register()`
- [ ] In `activate()`: require CPT class and call `register()` before flush

**Validation:**
- [ ] Deactivate/reactivate plugin
- [ ] CPTs registered correctly
- [ ] Permalinks work (flush_rewrite_rules called)

---

## Phase 4: Settings System

### Step 4.1: Create settings class

**File:** `includes/class-wpinsight-settings.php`

**Class:** `WPInsight_Settings`

**Constants:**
- `OPTION_NAME` = 'wpinsight_settings'

**Methods to implement:**

1. **`defaults(): array`** (static)
   - Return array of default settings
   - Keys: `sync_interval_minutes`, `recent_limit`, `per_page`, `pages`, `zip_worker_batch`, `zip_max_attempts`, `delete_on_uninstall`

2. **`get(string $key): mixed`** (static)
   - Get single setting value
   - Merge with defaults
   - Return null if key doesn't exist

3. **`get_all(): array`** (static)
   - Get all settings merged with defaults

4. **`update(array $settings): bool`** (static)
   - Validate settings array
   - Merge with existing settings
   - Save to options table
   - Return success boolean

5. **`delete(): bool`** (static)
   - Delete settings option from database

**Default values:**
```php
[
    'sync_interval_minutes' => 5,
    'recent_limit' => 250,
    'per_page' => 100,
    'pages' => 3,
    'zip_worker_batch' => 3,
    'zip_max_attempts' => 5,
    'delete_on_uninstall' => false,
]
```

**PHPDoc:**
- Class docblock
- Each method with param types and return types
- Document array structure with `@return array { @type ... }`

**PHPUnit Tests:**
**File:** `tests/test-settings.php`

- `test_defaults_returns_array()`
- `test_defaults_has_all_required_keys()`
- `test_get_returns_default_when_option_not_set()`
- `test_get_returns_null_for_invalid_key()`
- `test_update_saves_to_database()`
- `test_get_all_merges_with_defaults()`
- `test_delete_removes_option()`

**Validation:**
- [ ] All tests pass
- [ ] Can get default values
- [ ] Can update and retrieve settings
- [ ] Invalid keys return null

---

### Step 4.2: Initialize settings in bootstrap

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] Require settings class in `init()`
- [ ] No additional initialization needed (settings are static)

**Validation:**
- [ ] Settings class is loaded
- [ ] Can call `WPInsight_Settings::get('sync_interval_minutes')` without errors

---

## Phase 5: Admin UI - Basic Structure

### Step 5.1: Create admin class

**File:** `includes/class-wpinsight-admin.php`

**Class:** `WPInsight_Admin`

**Methods to implement:**

1. **`init(): void`** (static)
   - Hook `add_admin_menu()` to `admin_menu`
   - Hook `register_settings()` to `admin_init`

2. **`add_admin_menu(): void`** (static)
   - Add top-level menu page: "WPInsight"
   - Callback: `render_dashboard_page()`
   - Capability: `manage_options`
   - Icon: `dashicons-download`
   - Add submenu: "Dashboard" (same as parent)
   - Add submenu: "Settings"

3. **`render_dashboard_page(): void`** (static)
   - Check capability: `manage_options`
   - Load template: `templates/admin-dashboard.php`

4. **`render_settings_page(): void`** (static)
   - Check capability: `manage_options`
   - Load template: `templates/admin-settings.php`

5. **`register_settings(): void`** (static)
   - Register setting: `wpinsight_settings`
   - Register section: "General Settings"
   - Register fields for each setting

**PHPDoc:**
- Class docblock
- Each method documented
- Note capability checks

**PHPUnit Tests:**
**File:** `tests/test-admin.php`

- `test_init_hooks_are_registered()` (check hooks exist)
- Mock tests for capability checks (if time permits)

**Validation:**
- [ ] Menu appears in WordPress admin
- [ ] Menu has correct icon and position
- [ ] Clicking menu loads page (may be empty template)
- [ ] Tests pass

---

### Step 5.2: Create admin dashboard template

**File:** `templates/admin-dashboard.php`

**Tasks:**
- [ ] Security check: `if (!defined('ABSPATH')) exit;`
- [ ] Create basic HTML structure with `.wrap` div
- [ ] Add page title: `<h1>WPInsight Dashboard</h1>`
- [ ] Add placeholder sections:
  - Statistics (empty for now)
  - Queue Status (empty for now)
  - Recent Activity (empty for now)
  - Quick Actions (empty for now)
- [ ] Add inline CSS for basic styling (optional)

**Template Structure:**
```php
<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wpinsight-dashboard">
        <div class="wpinsight-stats">
            <h2>Statistics</h2>
            <p>Coming soon...</p>
        </div>

        <div class="wpinsight-queue">
            <h2>Queue Status</h2>
            <p>Coming soon...</p>
        </div>
    </div>
</div>
```

**Validation:**
- [ ] Template loads without errors
- [ ] Page displays correctly in admin
- [ ] All text is escaped properly

---

### Step 5.3: Create admin settings template

**File:** `templates/admin-settings.php`

**Tasks:**
- [ ] Security check
- [ ] Create settings form using WordPress Settings API
- [ ] Add nonce field
- [ ] Add submit button
- [ ] Display settings_errors()
- [ ] Add fields for all settings from defaults

**Template Structure:**
```php
<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('wpinsight_settings_group');
        do_settings_sections('wpinsight_settings');
        submit_button();
        ?>
    </form>
</div>
```

**Validation:**
- [ ] Settings page loads
- [ ] Form displays correctly
- [ ] Submit button present
- [ ] No errors

---

### Step 5.4: Hook admin initialization in bootstrap

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] Require admin class in `init()`
- [ ] Call `WPInsight_Admin::init()`

**Validation:**
- [ ] Admin menu appears
- [ ] Both pages (Dashboard and Settings) load correctly
- [ ] No PHP errors

---

## Phase 6: WordPress.org API Client

### Step 6.1: Create API client class

**File:** `includes/class-wpinsight-wporg-client.php`

**Class:** `WPInsight_WPOrg_Client`

**Constants:**
- `API_BASE_URL` = 'https://api.wordpress.org/plugins/info/1.2/'
- `API_TIMEOUT` = 20

**Methods to implement:**

1. **`request(string $endpoint, array $args): array`** (static, private)
   - Build query URL
   - Set user agent
   - Call `wp_remote_get()`
   - Check for WP_Error
   - Check HTTP status code
   - Parse response body (unserialize for WP.org API)
   - Return array or throw RuntimeException

2. **`query_plugins(string $browse, int $per_page, int $page, array $fields = []): array`** (static)
   - Build request args for `action=query_plugins`
   - Call `request()`
   - Return parsed response

3. **`query_themes(string $browse, int $per_page, int $page, array $fields = []): array`** (static)
   - Build request args for `action=query_themes`
   - Call `request()`
   - Return parsed response

4. **`plugin_information(string $slug, array $fields = []): array`** (static)
   - Build request args for `action=plugin_information`
   - Call `request()`
   - Return parsed response with version history

5. **`get_user_agent(): string`** (static, private)
   - Return user agent string: "WPInsight/{version}; {site_url}"

**PHPDoc:**
- Class docblock
- Each method with `@param`, `@return`, `@throws`
- Note that this makes external HTTP requests

**PHPUnit Tests:**
**File:** `tests/test-api-client.php`

- `test_get_user_agent_format()`
- `test_query_plugins_builds_correct_args()`
- `test_plugin_information_builds_correct_args()`
- Mock tests for HTTP requests (if time permits)

**Validation:**
- [ ] Class can be instantiated
- [ ] All methods exist
- [ ] Tests pass
- [ ] Can make test API call manually (WordPress test environment)

---

### Step 6.2: Test API client with real requests

**File:** Create temporary test file `test-api-manual.php` in plugin root (delete after testing)

**Tasks:**
- [ ] Include WordPress
- [ ] Include API client class
- [ ] Test `query_plugins('updated', 10, 1)`
- [ ] Test `plugin_information('akismet')`
- [ ] Verify response structure
- [ ] Check for `versions` array in plugin_information response
- [ ] Print results and verify data

**Validation:**
- [ ] API calls succeed
- [ ] Response contains expected data
- [ ] Version history is present in plugin_information
- [ ] No errors or warnings

---

## Phase 7: Sync Engine - Part 1 (Incremental)

### Step 7.1: Create sync class - skeleton

**File:** `includes/class-wpinsight-sync.php`

**Class:** `WPInsight_Sync`

**Methods to implement (Step 7.1):**

1. **`init(): void`** (static)
   - Hook Action Scheduler action: `wpinsight_sync_tick`

2. **`ensure_scheduled(): void`** (static)
   - Check if action already scheduled
   - Schedule recurring action every 5 minutes (or as per settings)

3. **`run_tick(): void`** (static)
   - Entry point for scheduled job
   - Call sync methods (to be implemented)

**PHPDoc:**
- Class docblock
- Each method documented

**Validation:**
- [ ] Class loads without errors
- [ ] Methods exist but don't do anything yet

---

### Step 7.2: Implement incremental sync method

**File:** `includes/class-wpinsight-sync.php`

**Method to implement:** `sync_recent(string $entity_type, string $feed): void`

**Logic:**
1. Get settings: `per_page`, `pages`, `recent_limit`
2. Define fields array to fetch from API
3. Loop through pages
4. Call `WPInsight_WPOrg_Client::query_plugins()` or `query_themes()`
5. Deduplicate by slug
6. Slice to `recent_limit`
7. For each item, call `upsert_cpt_and_maybe_enqueue_zip()`
8. Handle exceptions, log errors

**Method to implement:** `upsert_cpt_and_maybe_enqueue_zip(string $entity_type, array $row): void` (private)

**Logic:**
1. Sanitize all input data from API
2. Find existing CPT post by slug
3. If exists:
   - Compare `last_updated` and `version`
   - Update post and meta if changed
4. If not exists:
   - Create new CPT post
   - Set post meta
5. If new or changed, enqueue ZIP download (stub for now)

**Method to implement:** `find_post_id_by_slug(string $cpt, string $slug): int` (private)

**Logic:**
1. Use WP_Query with meta_query
2. Query for slug meta
3. Return post ID or 0

**PHPDoc:**
- Each method fully documented
- Note side effects (creates posts, updates database)

**PHPUnit Tests:**
**File:** `tests/test-sync.php`

- `test_find_post_id_by_slug_returns_zero_when_not_found()`
- `test_find_post_id_by_slug_returns_id_when_found()`
- Integration tests for sync methods (require WordPress test environment)

**Validation:**
- [ ] Can call `sync_recent('plugin', 'updated')`
- [ ] CPT posts are created
- [ ] Post meta is saved correctly
- [ ] No duplicate posts created
- [ ] Tests pass

---

### Step 7.3: Hook sync into Action Scheduler

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] Require sync class in `init()`
- [ ] Call `WPInsight_Sync::init()`
- [ ] In `activate()`, call `WPInsight_Sync::ensure_scheduled()`

**File:** `includes/class-wpinsight-sync.php`

**Tasks:**
- [ ] In `run_tick()`, call `sync_recent('plugin', 'updated')`
- [ ] In `run_tick()`, call `sync_recent('plugin', 'new')`

**Validation:**
- [ ] Activate plugin
- [ ] Check Action Scheduler logs (Tools > Scheduled Actions)
- [ ] Verify `wpinsight_sync_tick` is scheduled
- [ ] Wait 5 minutes or trigger manually
- [ ] Verify CPT posts are created
- [ ] Check for errors in logs

---

## Phase 8: ZIP Download Queue - Part 1 (Infrastructure)

### Step 8.1: Create queue class - skeleton

**File:** `includes/class-wpinsight-zip-queue.php`

**Class:** `WPInsight_Zip_Queue`

**Methods to implement (Step 8.1):**

1. **`init(): void`** (static)
   - Hook Action Scheduler action: `wpinsight_zip_worker_tick`

2. **`ensure_scheduled(): void`** (static)
   - Check if action already scheduled
   - Schedule recurring action every 1 minute

3. **`enqueue(string $entity_type, string $slug, string $version, string $url, int $priority = 10): void`** (static)
   - Insert job into `wpinsight_zip_queue` table
   - Use INSERT IGNORE to prevent duplicates
   - Validate inputs, sanitize

4. **`run_worker(): void`** (static)
   - Entry point for scheduled job (stub for now)

**PHPDoc:**
- Class docblock
- Each method documented
- Note database operations

**PHPUnit Tests:**
**File:** `tests/test-queue.php`

- `test_enqueue_validates_entity_type()`
- `test_enqueue_sanitizes_slug()`
- Integration tests for database operations

**Validation:**
- [ ] Class loads
- [ ] Can call `enqueue()` manually
- [ ] Job appears in database table
- [ ] No duplicate jobs created

---

### Step 8.2: Integrate queue with sync engine

**File:** `includes/class-wpinsight-sync.php`

**Tasks:**
- [ ] In `upsert_cpt_and_maybe_enqueue_zip()`, when new or changed version detected:
  - Get download URL from API response
  - Call `WPInsight_Zip_Queue::enqueue($entity_type, $slug, $version, $url, 10)`

**Validation:**
- [ ] Run sync manually
- [ ] Check `wpinsight_zip_queue` table
- [ ] Jobs are created for new/updated plugins
- [ ] No errors

---

## Phase 9: Storage Manager

### Step 9.1: Create storage class

**File:** `includes/class-wpinsight-storage.php`

**Class:** `WPInsight_Storage`

**Methods to implement:**

1. **`download_and_store_zip(string $entity_type, string $slug, string $version, string $url): array`** (static)
   - Get upload directory
   - Create plugin-specific subdirectory
   - Generate file path
   - Download file via `wp_remote_get()` with streaming
   - Calculate SHA256 hash
   - Atomic move (tmp → final)
   - Call `record_artifact()`
   - Return array with path, hash, filesize

2. **`record_artifact(string $entity_type, string $slug, string $version, string $path, string $sha256): void`** (static, private)
   - Insert into `wpinsight_artifacts` table
   - Use INSERT IGNORE to prevent duplicates

3. **`get_storage_path(string $entity_type, string $slug): string`** (static)
   - Build path: `uploads/wpinsight/{type}/{slug}/`
   - Create directory if not exists
   - Return path

4. **`validate_download_path(string $path): bool`** (static, private)
   - Ensure path is within uploads directory
   - Prevent directory traversal

**PHPDoc:**
- Class docblock
- Each method with full documentation
- Note file operations and security

**PHPUnit Tests:**
**File:** `tests/test-storage.php`

- `test_get_storage_path_format()`
- `test_validate_download_path_prevents_traversal()`
- `test_validate_download_path_allows_valid_paths()`
- Mock tests for file operations

**Validation:**
- [ ] All tests pass
- [ ] Methods exist and are callable

---

### Step 9.2: Integrate storage with queue worker

**File:** `includes/class-wpinsight-zip-queue.php`

**Method to implement:** `process_job(array $job): void` (private)

**Logic:**
1. Update job status to 'running'
2. Set `started_at` timestamp
3. Try:
   - Call `WPInsight_Storage::download_and_store_zip()`
   - Update job status to 'done'
   - Set hash, filesize, `finished_at`
4. Catch:
   - Increment attempts counter
   - Set last_error
   - Update status to 'failed' if attempts >= max
   - Otherwise set to 'queued' for retry

**Method to implement in `run_worker()`:**

**Logic:**
1. Get batch size from settings
2. Query for jobs with status='queued', attempts < max
3. Order by priority ASC, updated_at ASC
4. Limit to batch size
5. For each job, call `process_job()`

**PHPDoc:**
- Full documentation

**Validation:**
- [ ] Manually enqueue a test job
- [ ] Trigger worker: `do_action('wpinsight_zip_worker_tick')`
- [ ] Check that file is downloaded
- [ ] Verify file exists at correct path
- [ ] Check artifact record in database
- [ ] Job status updated to 'done'

---

## Phase 10: Rate Limiting (3 Concurrent Downloads)

### Step 10.1: Add concurrency control to queue worker

**File:** `includes/class-wpinsight-zip-queue.php`

**Methods to implement:**

1. **`get_active_downloads(): array`** (static, private)
   - Get transient: `wpinsight_active_downloads`
   - Return array of job IDs currently downloading
   - Clean stale entries (started > 10 minutes ago)

2. **`acquire_download_lock(int $job_id): bool`** (static, private)
   - Get active downloads
   - If count >= 3, return false
   - Add job_id to array
   - Save transient with expiration (10 minutes)
   - Return true

3. **`release_download_lock(int $job_id): void`** (static, private)
   - Get active downloads
   - Remove job_id from array
   - Save transient

**Modify `run_worker()` method:**
- Before processing jobs, check `get_active_downloads()`
- Calculate available slots: `3 - count(active_downloads)`
- Limit query to available slots
- If 0 available, exit early (retry on next tick)

**Modify `process_job()` method:**
- Before starting download, call `acquire_download_lock($job_id)`
- If returns false, skip this job (should not happen if logic is correct)
- After finishing (success or failure), call `release_download_lock($job_id)`

**PHPDoc:**
- Document concurrency control
- Note transient usage

**PHPUnit Tests:**
**File:** `tests/test-queue.php`

- `test_acquire_lock_succeeds_when_slots_available()`
- `test_acquire_lock_fails_when_max_reached()`
- `test_release_lock_removes_job_id()`
- `test_get_active_downloads_cleans_stale()`

**Validation:**
- [ ] Enqueue 10 jobs
- [ ] Trigger worker multiple times rapidly
- [ ] Check that max 3 jobs are 'running' at any time
- [ ] All other jobs remain 'queued'
- [ ] Tests pass

---

## Phase 11: Full Sync Implementation

### Step 11.1: Add full sync method to sync class

**File:** `includes/class-wpinsight-sync.php`

**Method to implement:** `sync_full(string $entity_type): void`

**Logic:**
1. Get sync state from database
2. Parse cursor (page number, last slug)
3. Loop through pages:
   - Call `query_plugins` with `browse=updated`
   - For each plugin:
     - Call `plugin_information` to get full details + version history
     - Create/update CPT
     - Parse `versions` array
     - Enqueue ALL versions to download queue
   - Update cursor after each page
   - Check for timeout/memory limit, exit gracefully if needed
4. When no more results, mark full sync as complete

**Method to implement:** `get_sync_state(string $entity_type, string $feed): array`

**Logic:**
1. Query `wpinsight_sync_state` table
2. Return cursor and last_run_at
3. Return empty array if not found

**Method to implement:** `update_sync_state(string $entity_type, string $feed, array $cursor): void`

**Logic:**
1. Serialize cursor as JSON
2. Update or insert into `wpinsight_sync_state` table

**PHPDoc:**
- Full documentation
- Note long-running operation

**Validation:**
- [ ] Can call `sync_full('plugin')` manually
- [ ] Paginate through at least 2 pages
- [ ] For each plugin, fetch version history
- [ ] Multiple versions enqueued per plugin
- [ ] Cursor saved correctly
- [ ] Can resume from cursor

---

### Step 11.2: Add full sync UI button

**File:** `templates/admin-dashboard.php`

**Tasks:**
- [ ] Add "Full Sync" section
- [ ] Add form with nonce
- [ ] Add submit button: "Start Full Sync"
- [ ] Add status indicator (running/idle)

**File:** `includes/class-wpinsight-admin.php`

**Method to implement:** `handle_full_sync_request(): void`

**Logic:**
1. Check capability and nonce
2. Check if full sync already running (option flag)
3. If not running:
   - Set flag option
   - Schedule one-time Action Scheduler action
   - Show success notice
4. If running:
   - Show error notice

**Method to implement:** `render_full_sync_status(): array`

**Logic:**
1. Check sync state from database
2. Return array with status, progress, ETA

**Tasks in admin init:**
- [ ] Hook form handler to `admin_post_wpinsight_full_sync`

**Validation:**
- [ ] Click "Start Full Sync" button
- [ ] Full sync starts
- [ ] CPT posts created
- [ ] Versions enqueued
- [ ] Status updates on dashboard

---

## Phase 12: WP-CLI Commands

### Step 12.1: Create CLI class

**File:** `includes/class-wpinsight-cli.php`

**Class:** `WPInsight_CLI`

**Methods to implement:**

1. **`register(): void`** (static)
   - Register WP-CLI commands

2. **`sync($args, $assoc_args): void`** (static)
   - Parse arguments: `--entity`, `--feed`, `--mode`
   - Call appropriate sync method
   - Output progress
   - Show success/error message

3. **`zip($args, $assoc_args): void`** (static)
   - Trigger ZIP worker manually
   - Output number of jobs processed
   - Show success message

4. **`status($args, $assoc_args): void`** (static)
   - Show statistics:
     - Total CPT posts
     - Total artifacts
     - Queue status (queued/running/done/failed)
     - Storage used
   - Format as table

**PHPDoc:**
- Class docblock
- Each method with WP-CLI annotations

**Validation:**
- [ ] Commands registered in WP-CLI
- [ ] `wp wpinsight sync --entity=plugin --feed=updated` works
- [ ] `wp wpinsight zip` works
- [ ] `wp wpinsight status` shows correct data

---

### Step 12.2: Hook CLI in bootstrap

**File:** `includes/class-wpinsight-bootstrap.php`

**Tasks:**
- [ ] In `init()`, check `if (defined('WP_CLI') && WP_CLI)`
- [ ] Require CLI class
- [ ] Call `WPInsight_CLI::register()`

**Validation:**
- [ ] Run `wp wpinsight` to see command list
- [ ] All commands work correctly

---

## Phase 13: Admin UI Enhancements

### Step 13.1: Add statistics to dashboard

**File:** `templates/admin-dashboard.php`

**Tasks:**
- [ ] Query count of CPT posts
- [ ] Query count of artifacts
- [ ] Query queue status counts
- [ ] Calculate total storage used (sum of filesizes)
- [ ] Display in dashboard cards/boxes
- [ ] Add CSS for styling

**File:** `includes/class-wpinsight-admin.php`

**Method to implement:** `get_dashboard_stats(): array`

**Logic:**
1. Count CPT posts: `wp_count_posts('wpinsight_plugin')`
2. Query artifacts count: `SELECT COUNT(*) FROM wpinsight_artifacts`
3. Query queue status: `SELECT status, COUNT(*) FROM wpinsight_zip_queue GROUP BY status`
4. Sum filesizes: `SELECT SUM(filesize) FROM wpinsight_zip_queue WHERE status='done'`
5. Return associative array

**PHPDoc:**
- Full documentation

**Validation:**
- [ ] Dashboard shows correct statistics
- [ ] Numbers update after sync/download
- [ ] No PHP errors

---

### Step 13.2: Add queue status table

**File:** `templates/admin-dashboard.php`

**Tasks:**
- [ ] Add section: "Recent Queue Jobs"
- [ ] Query last 20 jobs from queue
- [ ] Display in HTML table with columns:
  - Entity Type
  - Slug
  - Version
  - Status
  - Attempts
  - Started At
  - Finished At
- [ ] Add pagination (optional)
- [ ] Add "Retry Failed Jobs" button

**File:** `includes/class-wpinsight-admin.php`

**Method to implement:** `get_recent_queue_jobs(int $limit = 20): array`

**Method to implement:** `retry_failed_jobs(): int`

**Logic:**
1. Update all jobs with status='failed' and attempts < max
2. Set status='queued', reset started_at and finished_at
3. Return count of retried jobs

**Validation:**
- [ ] Queue table displays correctly
- [ ] Data is accurate
- [ ] Retry button works

---

## Phase 14: Settings Page Implementation

### Step 14.1: Implement settings fields rendering

**File:** `includes/class-wpinsight-admin.php`

**Method to implement:** `register_settings_fields(): void`

**Logic:**
1. For each setting in defaults:
   - Call `add_settings_field()`
   - Set callback to render field
   - Pass field name and type

**Methods to implement for each field:**
- `render_number_field(string $field_name, string $label, string $description)`
- `render_checkbox_field(string $field_name, string $label, string $description)`

**Tasks:**
- [ ] Register all settings fields
- [ ] Add field labels and descriptions
- [ ] Add input validation callbacks

**Validation:**
- [ ] Settings page shows all fields
- [ ] Can save settings
- [ ] Settings persist after save
- [ ] Validation works (e.g., negative numbers rejected)

---

## Phase 15: Error Handling & Logging

### Step 15.1: Create logger utility class

**File:** `includes/class-wpinsight-logger.php`

**Class:** `WPInsight_Logger`

**Methods to implement:**

1. **`log(string $message, string $level = 'info', array $context = []): void`** (static)
   - Write to WordPress debug.log if WP_DEBUG enabled
   - Optionally store in custom log table
   - Format: `[timestamp] [level] [WPInsight] message`

2. **`error(string $message, array $context = []): void`** (static)
   - Call `log()` with level='error'

3. **`info(string $message, array $context = []): void`** (static)
   - Call `log()` with level='info'

4. **`debug(string $message, array $context = []): void`** (static)
   - Call `log()` with level='debug'

**PHPDoc:**
- Full documentation

**Validation:**
- [ ] Can call logger methods
- [ ] Messages appear in debug.log
- [ ] No errors

---

### Step 15.2: Add error handling throughout codebase

**Tasks:**
- [ ] Wrap API calls in try-catch
- [ ] Wrap file operations in try-catch
- [ ] Log all exceptions
- [ ] Add admin notices for user-facing errors
- [ ] Test error scenarios:
  - API timeout
  - File write failure
  - Invalid API response

**Validation:**
- [ ] Errors are caught gracefully
- [ ] Logged appropriately
- [ ] User sees meaningful error messages
- [ ] Plugin doesn't crash

---

## Phase 16: Testing & Quality Assurance

### Step 16.1: Complete PHPUnit test coverage

**Tasks:**
- [ ] Review all classes
- [ ] Ensure all testable methods have tests
- [ ] Aim for >80% code coverage
- [ ] Add integration tests for critical workflows

**Files to review:**
- `tests/test-bootstrap.php`
- `tests/test-db.php`
- `tests/test-cpt.php`
- `tests/test-settings.php`
- `tests/test-admin.php`
- `tests/test-api-client.php`
- `tests/test-sync.php`
- `tests/test-queue.php`
- `tests/test-storage.php`
- `tests/test-logger.php`

**Validation:**
- [ ] Run `vendor/bin/phpunit`
- [ ] All tests pass
- [ ] No skipped tests
- [ ] Review coverage report

---

### Step 16.2: Run PHPCS on all files

**Tasks:**
- [ ] Install PHPCS: `composer require --dev squizlabs/php_codesniffer`
- [ ] Install WordPress Coding Standards: `composer require --dev wp-coding-standards/wpcs`
- [ ] Configure phpcs.xml
- [ ] Run PHPCS on all PHP files
- [ ] Fix all errors and warnings

**Command:**
```bash
vendor/bin/phpcs --standard=WordPress includes/*.php *.php
```

**Validation:**
- [ ] PHPCS runs without errors
- [ ] All files pass WordPress Coding Standards
- [ ] No warnings

---

### Step 16.3: Manual testing checklist

**Environment Setup:**
- [ ] Fresh WordPress 6.9 installation
- [ ] PHP 8.4
- [ ] MariaDB 10.6+
- [ ] Install Action Scheduler plugin

**Test Scenarios:**

1. **Installation & Activation:**
   - [ ] Install plugin
   - [ ] Try activating without Action Scheduler (should fail with notice)
   - [ ] Install Action Scheduler
   - [ ] Activate plugin successfully
   - [ ] Verify tables created
   - [ ] Verify CPTs registered
   - [ ] Verify scheduled actions created

2. **Incremental Sync:**
   - [ ] Trigger manual sync via WP-CLI: `wp wpinsight sync`
   - [ ] Verify CPT posts created
   - [ ] Verify post meta saved
   - [ ] Verify jobs enqueued

3. **ZIP Downloads:**
   - [ ] Trigger worker: `wp wpinsight zip`
   - [ ] Verify max 3 concurrent downloads
   - [ ] Verify files downloaded to correct paths
   - [ ] Verify artifacts recorded in database
   - [ ] Verify SHA256 hashes calculated

4. **Full Sync:**
   - [ ] Start full sync via dashboard button
   - [ ] Monitor progress
   - [ ] Verify version history fetched
   - [ ] Verify all versions enqueued
   - [ ] Interrupt and resume (test cursor)

5. **Settings:**
   - [ ] Change settings in admin
   - [ ] Verify settings saved
   - [ ] Verify changes take effect

6. **Error Handling:**
   - [ ] Simulate API timeout (disconnect internet briefly)
   - [ ] Verify error logged
   - [ ] Verify job marked as failed
   - [ ] Retry failed jobs

7. **Uninstall:**
   - [ ] Set "Delete on uninstall" option
   - [ ] Deactivate plugin
   - [ ] Delete plugin
   - [ ] Verify tables dropped (if opted in)
   - [ ] Verify files deleted (if opted in)

**Validation:**
- [ ] All test scenarios pass
- [ ] No PHP errors in debug.log
- [ ] No JavaScript errors in browser console
- [ ] Plugin works as expected

---

## Phase 17: Documentation & Packaging

### Step 17.1: Complete readme.txt

**File:** `readme.txt`

**Tasks:**
- [ ] Follow DOCUMENTATION-readme.txt.md template
- [ ] Fill in all sections:
  - Description
  - Installation
  - FAQ
  - Screenshots (optional for hackathon)
  - Changelog
  - Compatibility (WordPress 6.9+, PHP 8.4+)
- [ ] Document Action Scheduler requirement
- [ ] Document storage requirements (~1TB)
- [ ] Add WP-CLI commands documentation

**Validation:**
- [ ] readme.txt is complete
- [ ] No spelling errors
- [ ] Format is valid (test with WordPress.org validator)

---

### Step 17.2: Create CHANGELOG.md

**File:** `CHANGELOG.md`

**Tasks:**
- [ ] Follow format from DOCUMENTATION-changelog.txt.md
- [ ] Document all features implemented
- [ ] List all phases/versions
- [ ] Include compatibility info

**Validation:**
- [ ] Changelog is complete
- [ ] Format is consistent

---

### Step 17.3: Create deployment script

**File:** `bin/deploy.sh`

**Tasks:**
- [ ] Create bash script
- [ ] Read version from plugin header
- [ ] Create clean copy of plugin (exclude dev files)
- [ ] Generate ZIP file
- [ ] Save to parent directory
- [ ] Name format: `cloudfest-wporgdownload.{version}.zip`

**Exclude from ZIP:**
- `.git/`
- `tests/`
- `node_modules/`
- `vendor/` (dev dependencies)
- `.gitignore`
- `phpunit.xml`
- `phpcs.xml`
- `composer.lock`
- `test-*.php` files

**Script should:**
```bash
#!/bin/bash
VERSION=$(grep "Version:" cloudfest-wporgdownload.php | awk '{print $3}')
ZIP_NAME="cloudfest-wporgdownload.$VERSION.zip"
# ... rest of script
```

**Validation:**
- [ ] Script runs without errors
- [ ] ZIP file created
- [ ] ZIP contains only production files
- [ ] ZIP can be installed in WordPress

---

### Step 17.4: Final code review

**Tasks:**
- [ ] Review all PHPDoc comments
- [ ] Check all function/method signatures
- [ ] Verify all security checks (nonces, capabilities, sanitization, escaping)
- [ ] Verify all database queries use prepared statements
- [ ] Check for TODO comments (resolve or document)
- [ ] Verify all error handling is in place

**Validation:**
- [ ] Code review complete
- [ ] All issues addressed
- [ ] Code is production-ready

---

## Validation Checkpoints Summary

After each phase, verify:

1. **No PHP Errors:**
   - Check `wp-content/debug.log`
   - Check WordPress admin for notices/warnings

2. **Tests Pass:**
   - Run PHPUnit: `vendor/bin/phpunit`
   - All tests green

3. **PHPCS Clean:**
   - Run PHPCS on modified files
   - No errors or warnings

4. **Functionality Works:**
   - Test the specific feature implemented
   - Verify database changes
   - Check file system changes

5. **Documentation Complete:**
   - PHPDoc for all new code
   - README/CHANGELOG updated if needed

---

## Granularity Guidelines

- **Do not proceed to next step** if current step has failing tests
- **Do not proceed to next step** if PHPCS reports errors
- **Validate each method individually** before combining into workflows
- **Test edge cases** (empty inputs, invalid data, network failures)
- **Review security** at each step (especially user input, file operations, database queries)

---

## Progress Tracking

As each step is completed, mark it with:
- ✅ Implemented
- ✅ Tested (PHPUnit)
- ✅ Validated (Manual testing)
- ✅ Documented (PHPDoc + comments)
- ✅ PHPCS passed

Only move to next step when all checkmarks are complete for current step.

---

## Estimated Timeline

- **Phase 0-2:** 2-3 hours (Foundation + DB)
- **Phase 3-5:** 3-4 hours (CPT + Settings + Admin UI)
- **Phase 6-7:** 4-5 hours (API Client + Sync Engine)
- **Phase 8-10:** 5-6 hours (Queue + Storage + Rate Limiting)
- **Phase 11:** 3-4 hours (Full Sync)
- **Phase 12:** 2-3 hours (WP-CLI)
- **Phase 13-14:** 3-4 hours (Admin Enhancements)
- **Phase 15:** 2-3 hours (Error Handling)
- **Phase 16:** 4-5 hours (Testing & QA)
- **Phase 17:** 2-3 hours (Documentation + Packaging)

**Total:** ~35-45 hours of development time

**For Hackathon (3 days, 2-3 developers):**
- Day 1: Phases 0-7 (Foundation through Sync)
- Day 2: Phases 8-14 (Queue through Admin)
- Day 3: Phases 15-17 (Polish, Testing, Packaging)

---

## Notes

- This roadmap is designed for maximum safety and validation
- Each step is independently testable
- Progress can be paused and resumed at any checkpoint
- Granular approach reduces debugging time (issues caught early)
- Documentation debt is minimized (PHPDoc written with code)
- Test coverage grows incrementally with features
