Estructura propuesta (lista para empezar a codear) + esqueletos de archivos clave. Todo el código/comentarios en **inglés**.

---

## 1) Estructura de carpetas

```
wp-plugin-insight-ingestor/
├─ wp-plugin-insight-ingestor.php
├─ readme.txt
├─ uninstall.php
├─ assets/
├─ includes/
│  ├─ class-wpinsight-bootstrap.php
│  ├─ class-wpinsight-cpt.php
│  ├─ class-wpinsight-db.php
│  ├─ class-wpinsight-settings.php
│  ├─ class-wpinsight-admin.php
│  ├─ class-wpinsight-wporg-client.php
│  ├─ class-wpinsight-sync.php
│  ├─ class-wpinsight-zip-queue.php
│  ├─ class-wpinsight-storage.php
│  └─ class-wpinsight-cli.php
└─ templates/
   └─ admin-page.php
```

---

## 2) Archivo principal del plugin

### `wp-plugin-insight-ingestor.php`

```php
<?php
/**
 * Plugin Name: WP Plugin Insight Ingestor
 * Description: Mirrors WordPress.org plugin/theme metadata into CPTs and downloads ZIPs via a queue.
 * Version: 0.1.0
 * Author: WP Plugin Insight
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

define('WPINSIGHT_VERSION', '0.1.0');
define('WPINSIGHT_PLUGIN_FILE', __FILE__);
define('WPINSIGHT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPINSIGHT_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-bootstrap.php';

register_activation_hook(__FILE__, ['WPInsight_Bootstrap', 'activate']);
register_deactivation_hook(__FILE__, ['WPInsight_Bootstrap', 'deactivate']);

add_action('plugins_loaded', ['WPInsight_Bootstrap', 'init']);
```

---

## 3) Bootstrap

### `includes/class-wpinsight-bootstrap.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Bootstrap {

  public static function init(): void {
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-db.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cpt.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-settings.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-admin.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-wporg-client.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-sync.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-zip-queue.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-storage.php';

    // CPTs
    WPInsight_CPT::register();

    // DB hooks (upgrade path if needed)
    add_action('admin_init', ['WPInsight_DB', 'maybe_upgrade']);

    // Admin UI
    WPInsight_Admin::init();

    // Scheduling + jobs
    WPInsight_Sync::init();
    WPInsight_Zip_Queue::init();

    // WP-CLI
    if (defined('WP_CLI') && WP_CLI) {
      require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cli.php';
      WPInsight_CLI::register();
    }
  }

  public static function activate(): void {
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-db.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cpt.php';

    WPInsight_DB::install();
    WPInsight_CPT::register();
    flush_rewrite_rules();

    // Schedule recurring sync + download workers
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-sync.php';
    require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-zip-queue.php';
    WPInsight_Sync::ensure_scheduled();
    WPInsight_Zip_Queue::ensure_scheduled();
  }

  public static function deactivate(): void {
    flush_rewrite_rules();

    // Unschedule jobs (we keep DB + CPT content by design)
    if (function_exists('as_unschedule_all_actions')) {
      as_unschedule_all_actions('wpinsight_sync_tick');
      as_unschedule_all_actions('wpinsight_zip_worker_tick');
    }
  }
}
```

---

## 4) CPTs (Plugins + Themes)

### `includes/class-wpinsight-cpt.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_CPT {
  const CPT_PLUGIN = 'wpinsight_plugin';
  const CPT_THEME  = 'wpinsight_theme';

  public static function register(): void {
    register_post_type(self::CPT_PLUGIN, [
      'label' => 'WPInsight Plugins',
      'public' => false,
      'show_ui' => true,
      'supports' => ['title'],
      'menu_icon' => 'dashicons-admin-plugins',
    ]);

    register_post_type(self::CPT_THEME, [
      'label' => 'WPInsight Themes',
      'public' => false,
      'show_ui' => true,
      'supports' => ['title'],
      'menu_icon' => 'dashicons-admin-appearance',
    ]);
  }
}
```

---

## 5) Tablas (sync state + zip queue + artifacts)

### `includes/class-wpinsight-db.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_DB {
  const DB_VERSION = 1;

  public static function install(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();

    $sync = $wpdb->prefix . 'wpinsight_sync_state';
    $queue = $wpdb->prefix . 'wpinsight_zip_queue';
    $art = $wpdb->prefix . 'wpinsight_artifacts';

    $sql1 = "CREATE TABLE $sync (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      entity_type VARCHAR(10) NOT NULL,
      feed VARCHAR(10) NOT NULL,
      last_run_at DATETIME NULL,
      cursor_text TEXT NULL,
      notes TEXT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY entity_feed (entity_type, feed)
    ) $charset;";

    $sql2 = "CREATE TABLE $queue (
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
    ) $charset;";

    $sql3 = "CREATE TABLE $art (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      entity_type VARCHAR(10) NOT NULL,
      slug VARCHAR(200) NOT NULL,
      version VARCHAR(50) NOT NULL,
      zip_path TEXT NOT NULL,
      hash_sha256 CHAR(64) NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_art (entity_type, slug, version, hash_sha256(16))
    ) $charset;";

    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);

    update_option('wpinsight_db_version', self::DB_VERSION);
  }

  public static function maybe_upgrade(): void {
    $v = (int) get_option('wpinsight_db_version', 0);
    if ($v < self::DB_VERSION) self::install();
  }
}
```

---

## 6) Settings (intervalo, batch size, etc.)

### `includes/class-wpinsight-settings.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Settings {

  public static function defaults(): array {
    return [
      'sync_interval_minutes' => 5,
      'recent_limit' => 250,
      'per_page' => 100,
      'pages' => 3, // 3 * 100 = 300 candidates -> trim to 250
      'zip_worker_batch' => 10,
      'zip_max_attempts' => 5,
    ];
  }

  public static function get(string $key) {
    $opts = get_option('wpinsight_settings', []);
    $opts = array_merge(self::defaults(), is_array($opts) ? $opts : []);
    return $opts[$key] ?? null;
  }

  public static function update(array $new): void {
    $opts = array_merge(self::defaults(), get_option('wpinsight_settings', []));
    $opts = array_merge($opts, $new);
    update_option('wpinsight_settings', $opts);
  }
}
```

---

## 7) Cliente WordPress.org (plugins/themes)

### `includes/class-wpinsight-wporg-client.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_WPOrg_Client {

  private static function request(string $endpoint, array $args): array {
    $url = add_query_arg($args, $endpoint);

    $res = wp_remote_get($url, [
      'timeout' => 20,
      'user-agent' => 'WPInsight/' . WPINSIGHT_VERSION . '; ' . home_url('/'),
    ]);

    if (is_wp_error($res)) {
      throw new RuntimeException($res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);

    if ($code < 200 || $code >= 300) {
      throw new RuntimeException("HTTP $code from WP.org");
    }

    // WP.org returns PHP-serialized payload for these endpoints.
    $data = maybe_unserialize($body);
    if (!is_array($data)) {
      throw new RuntimeException("Unexpected response format");
    }

    return $data;
  }

  public static function query_plugins(string $browse, int $per_page, int $page, array $fields = []): array {
    $endpoint = 'https://api.wordpress.org/plugins/info/1.2/';
    $args = [
      'action' => 'query_plugins',
      'request' => [
        'browse' => $browse,
        'per_page' => $per_page,
        'page' => $page,
        'fields' => $fields,
      ],
    ];
    return self::request($endpoint, $args);
  }

  public static function query_themes(string $browse, int $per_page, int $page, array $fields = []): array {
    $endpoint = 'https://api.wordpress.org/themes/info/1.2/';
    $args = [
      'action' => 'query_themes',
      'request' => [
        'browse' => $browse,
        'per_page' => $per_page,
        'page' => $page,
        'fields' => $fields,
      ],
    ];
    return self::request($endpoint, $args);
  }
}
```

---

## 8) Sync job: new/updated + “full sync”

### `includes/class-wpinsight-sync.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Sync {

  public static function init(): void {
    add_action('wpinsight_sync_tick', [__CLASS__, 'run_tick']);
  }

  public static function ensure_scheduled(): void {
    if (function_exists('as_next_scheduled_action')) {
      if (!as_next_scheduled_action('wpinsight_sync_tick')) {
        // Every 5 minutes via custom interval is not native; Action Scheduler supports recurring.
        as_schedule_recurring_action(time() + 60, 5 * MINUTE_IN_SECONDS, 'wpinsight_sync_tick', [], 'wpinsight');
      }
    } else {
      // Fallback: WP-Cron every 5 minutes requires custom schedules; kept minimal here.
      if (!wp_next_scheduled('wpinsight_sync_tick')) {
        wp_schedule_event(time() + 60, 'hourly', 'wpinsight_sync_tick');
      }
    }
  }

  public static function run_tick(): void {
    // Process both entity types and both feeds.
    self::sync_recent('plugin', 'updated');
    self::sync_recent('plugin', 'new');
    self::sync_recent('theme', 'updated');
    self::sync_recent('theme', 'new');
  }

  public static function sync_recent(string $entity_type, string $feed): void {
    $per_page = (int) WPInsight_Settings::get('per_page');
    $pages = (int) WPInsight_Settings::get('pages');
    $limit = (int) WPInsight_Settings::get('recent_limit');

    $fields = [
      'downloadlink' => true,
      'last_updated' => true,
      'requires' => true,
      'requires_php' => true,
      'tested' => true,
      'short_description' => true,
      'rating' => true,
      'active_installs' => true,
    ];

    $items = [];

    try {
      for ($p = 1; $p <= $pages; $p++) {
        $data = ($entity_type === 'plugin')
          ? WPInsight_WPOrg_Client::query_plugins($feed, $per_page, $p, $fields)
          : WPInsight_WPOrg_Client::query_themes($feed, $per_page, $p, $fields);

        $list = $data['plugins'] ?? $data['themes'] ?? [];
        if (!is_array($list)) $list = [];

        foreach ($list as $row) {
          if (empty($row['slug'])) continue;
          $items[$row['slug']] = $row; // dedupe by slug
        }
      }
    } catch (Throwable $e) {
      // Minimal logging; production should use error log + admin notice storage.
      error_log('[WPInsight] Sync error: ' . $e->getMessage());
      return;
    }

    $items = array_values($items);
    $items = array_slice($items, 0, $limit);

    foreach ($items as $row) {
      self::upsert_cpt_and_maybe_enqueue_zip($entity_type, $row);
    }
  }

  private static function upsert_cpt_and_maybe_enqueue_zip(string $entity_type, array $row): void {
    $slug = sanitize_key($row['slug']);
    $name = sanitize_text_field($row['name'] ?? $slug);
    $version = sanitize_text_field($row['version'] ?? '');
    $last_updated = sanitize_text_field($row['last_updated'] ?? '');
    $download = $row['download_link'] ?? $row['downloadlink'] ?? '';

    $cpt = ($entity_type === 'plugin') ? WPInsight_CPT::CPT_PLUGIN : WPInsight_CPT::CPT_THEME;

    $existing_id = self::find_post_id_by_slug($cpt, $slug);
    $needs_zip = false;

    if ($existing_id) {
      $prev_last = (string) get_post_meta($existing_id, 'last_updated', true);
      $prev_ver  = (string) get_post_meta($existing_id, 'version', true);
      if ($last_updated && $last_updated !== $prev_last) $needs_zip = true;
      if ($version && $version !== $prev_ver) $needs_zip = true;

      wp_update_post([
        'ID' => $existing_id,
        'post_title' => $name,
      ]);
      $post_id = $existing_id;
    } else {
      $post_id = wp_insert_post([
        'post_type' => $cpt,
        'post_status' => 'publish',
        'post_title' => $name,
      ]);
      $needs_zip = true;
    }

    if (is_wp_error($post_id) || !$post_id) return;

    update_post_meta($post_id, 'slug', $slug);
    update_post_meta($post_id, 'version', $version);
    update_post_meta($post_id, 'last_updated', $last_updated);
    update_post_meta($post_id, 'requires', sanitize_text_field($row['requires'] ?? ''));
    update_post_meta($post_id, 'requires_php', sanitize_text_field($row['requires_php'] ?? ''));
    update_post_meta($post_id, 'tested', sanitize_text_field($row['tested'] ?? ''));
    update_post_meta($post_id, 'download_link', esc_url_raw($download));
    update_post_meta($post_id, 'short_description', wp_kses_post($row['short_description'] ?? ''));

    if ($needs_zip && $download) {
      WPInsight_Zip_Queue::enqueue($entity_type, $slug, $version, $download, 10);
    }
  }

  private static function find_post_id_by_slug(string $cpt, string $slug): int {
    $q = new WP_Query([
      'post_type' => $cpt,
      'posts_per_page' => 1,
      'fields' => 'ids',
      'meta_query' => [
        [
          'key' => 'slug',
          'value' => $slug,
          'compare' => '=',
        ],
      ],
    ]);
    return !empty($q->posts[0]) ? (int) $q->posts[0] : 0;
  }
}
```

---

## 9) Cola + worker de ZIP

### `includes/class-wpinsight-zip-queue.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Zip_Queue {

  public static function init(): void {
    add_action('wpinsight_zip_worker_tick', [__CLASS__, 'run_worker']);
  }

  public static function ensure_scheduled(): void {
    if (function_exists('as_next_scheduled_action')) {
      if (!as_next_scheduled_action('wpinsight_zip_worker_tick')) {
        as_schedule_recurring_action(time() + 90, 1 * MINUTE_IN_SECONDS, 'wpinsight_zip_worker_tick', [], 'wpinsight');
      }
    } else {
      if (!wp_next_scheduled('wpinsight_zip_worker_tick')) {
        wp_schedule_event(time() + 90, 'hourly', 'wpinsight_zip_worker_tick');
      }
    }
  }

  public static function enqueue(string $entity_type, string $slug, string $version, string $url, int $priority = 10): void {
    global $wpdb;
    $table = $wpdb->prefix . 'wpinsight_zip_queue';

    $now = current_time('mysql');

    // Insert or ignore based on unique (entity_type, slug, version)
    $wpdb->query($wpdb->prepare(
      "INSERT IGNORE INTO $table
        (entity_type, slug, version, download_url, priority, status, attempts, created_at, updated_at)
       VALUES (%s, %s, %s, %s, %d, 'queued', 0, %s, %s)",
      $entity_type, $slug, $version, $url, $priority, $now, $now
    ));
  }

  public static function run_worker(): void {
    global $wpdb;

    $batch = (int) WPInsight_Settings::get('zip_worker_batch');
    $max_attempts = (int) WPInsight_Settings::get('zip_max_attempts');

    $table = $wpdb->prefix . 'wpinsight_zip_queue';

    $jobs = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM $table
         WHERE status='queued' AND attempts < %d
         ORDER BY priority ASC, updated_at ASC
         LIMIT %d",
        $max_attempts, $batch
      ),
      ARRAY_A
    );

    foreach ($jobs as $job) {
      self::process_job($job);
    }
  }

  private static function process_job(array $job): void {
    global $wpdb;
    $table = $wpdb->prefix . 'wpinsight_zip_queue';
    $id = (int) $job['id'];

    $wpdb->update($table, [
      'status' => 'running',
      'started_at' => current_time('mysql'),
      'updated_at' => current_time('mysql'),
    ], ['id' => $id]);

    try {
      $result = WPInsight_Storage::download_and_store_zip(
        $job['entity_type'],
        $job['slug'],
        (string) $job['version'],
        $job['download_url']
      );

      $wpdb->update($table, [
        'status' => 'done',
        'hash_sha256' => $result['sha256'],
        'filesize' => $result['filesize'],
        'finished_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
      ], ['id' => $id]);

    } catch (Throwable $e) {
      $wpdb->query($wpdb->prepare(
        "UPDATE $table SET
          status='failed',
          attempts = attempts + 1,
          last_error=%s,
          updated_at=%s
         WHERE id=%d",
        $e->getMessage(),
        current_time('mysql'),
        $id
      ));
      error_log('[WPInsight] ZIP job failed: ' . $e->getMessage());
    }
  }
}
```

---

## 10) Storage: descarga streaming + sha256 + guardado

### `includes/class-wpinsight-storage.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Storage {

  public static function download_and_store_zip(string $entity_type, string $slug, string $version, string $url): array {
    $upload = wp_upload_dir();
    if (empty($upload['basedir'])) {
      throw new RuntimeException('Uploads directory not available');
    }

    $base = trailingslashit($upload['basedir']) . 'wpinsight/' . $entity_type . '/' . $slug . '/';
    wp_mkdir_p($base);

    $safe_ver = $version !== '' ? $version : 'unknown';
    $dest = $base . $slug . '.' . $safe_ver . '.zip';
    $tmp  = $dest . '.tmp';

    $fp = fopen($tmp, 'wb');
    if (!$fp) throw new RuntimeException('Cannot open temp file for writing');

    $res = wp_remote_get($url, [
      'timeout' => 60,
      'stream' => true,
      'filename' => $tmp,
      'user-agent' => 'WPInsight/' . WPINSIGHT_VERSION . '; ' . home_url('/'),
    ]);

    if (is_wp_error($res)) {
      @fclose($fp);
      @unlink($tmp);
      throw new RuntimeException($res->get_error_message());
    }

    // Ensure file exists and has content
    if (!file_exists($tmp) || filesize($tmp) < 100) {
      @unlink($tmp);
      throw new RuntimeException('Downloaded file is missing or too small');
    }

    $sha256 = hash_file('sha256', $tmp);
    $filesize = filesize($tmp);

    // Atomically move into place
    rename($tmp, $dest);

    self::record_artifact($entity_type, $slug, $safe_ver, $dest, $sha256);

    return [
      'path' => $dest,
      'sha256' => $sha256,
      'filesize' => $filesize,
    ];
  }

  private static function record_artifact(string $entity_type, string $slug, string $version, string $path, string $sha256): void {
    global $wpdb;
    $table = $wpdb->prefix . 'wpinsight_artifacts';

    $wpdb->query($wpdb->prepare(
      "INSERT IGNORE INTO $table (entity_type, slug, version, zip_path, hash_sha256, created_at)
       VALUES (%s, %s, %s, %s, %s, %s)",
      $entity_type, $slug, $version, $path, $sha256, current_time('mysql')
    ));
  }
}
```

---

## 11) Admin UI (mínimo)

### `includes/class-wpinsight-admin.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_Admin {
  public static function init(): void {
    add_action('admin_menu', [__CLASS__, 'menu']);
  }

  public static function menu(): void {
    add_menu_page(
      'WPInsight',
      'WPInsight',
      'manage_options',
      'wpinsight',
      [__CLASS__, 'page'],
      'dashicons-search'
    );
  }

  public static function page(): void {
    include WPINSIGHT_PLUGIN_DIR . 'templates/admin-page.php';
  }
}
```

### `templates/admin-page.php`

```php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
  <h1>WPInsight Ingestor</h1>

  <p>This plugin mirrors WordPress.org plugin/theme metadata and downloads ZIPs using an internal queue.</p>

  <h2>Manual Actions</h2>
  <p>Recommended: use WP-CLI for manual runs. Scheduled jobs run automatically if Action Scheduler is available.</p>
</div>
```

---

## 12) WP-CLI comandos

### `includes/class-wpinsight-cli.php`

```php
<?php
if (!defined('ABSPATH')) exit;

final class WPInsight_CLI {

  public static function register(): void {
    WP_CLI::add_command('wpinsight sync', [__CLASS__, 'sync']);
    WP_CLI::add_command('wpinsight zip', [__CLASS__, 'zip']);
  }

  public static function sync($args, $assoc_args): void {
    $entity = $assoc_args['entity'] ?? 'plugin';
    $feed   = $assoc_args['feed'] ?? 'updated';

    WP_CLI::log("Sync: entity=$entity feed=$feed");
    WPInsight_Sync::sync_recent($entity, $feed);
    WP_CLI::success('Done');
  }

  public static function zip($args, $assoc_args): void {
    WP_CLI::log("ZIP worker tick");
    WPInsight_Zip_Queue::run_worker();
    WP_CLI::success('Done');
  }
}
```

Uso:

```bash
wp wpinsight sync --entity=plugin --feed=updated
wp wpinsight sync --entity=theme --feed=new
wp wpinsight zip
```

---

## 13) uninstall (opcional: limpiar tablas)

### `uninstall.php`

```php
<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// By default, keep data (safer).
// If you want cleanup, implement: drop tables + delete CPT posts + delete options.
```

---

## 14) Siguiente paso inmediato (para que compile y funcione)

1. Instalar/activar **Action Scheduler** (ideal) o asumir que WooCommerce ya lo trae.
2. Activar el plugin → crea tablas → schedule de `wpinsight_sync_tick` y `wpinsight_zip_worker_tick` (si hay AS).
3. Ejecutar manualmente:

   * `wp wpinsight sync --entity=plugin --feed=updated`
   * `wp wpinsight zip`

---

Si quieres que lo deje “redondo” para hackathon, lo siguiente sería añadir:

* página admin con botones “Sync now” y “Queue status”
* “full sync” (paginación completa) con cursor en `wpinsight_sync_state`
* selector de `recent_limit=250` y “pages/per_page” desde settings
* columnas en los CPTs (slug/version/last_updated/zip status)

