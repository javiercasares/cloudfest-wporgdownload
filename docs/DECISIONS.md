# Architectural Decisions

This document provides a quick reference for all key architectural decisions made for the WordPress.org Plugin/Theme Downloader project.

**Last Updated:** 2026-01-31

---

## Technical Stack

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| **WordPress Version** | 6.9+ | Latest stable version |
| **PHP Version** | 8.4+ | Modern PHP features, type declarations |
| **Architecture** | Object-oriented (Classes) | Per AGENTS.md requirements |
| **Multisite** | NOT supported | Archiving doesn't benefit from multisite context |
| **Task Scheduling** | Action Scheduler (required) | Reliable, scalable background job processing |

---

## API Strategy

### Full Sync Method

**Decision:** Use `browse=updated` with pagination

**API Calls:**
1. `query_plugins?action=query_plugins&browse=updated&per_page=100&page=N`
   - Returns list of plugins sorted by last update
   - Paginate through ALL pages until exhausted

2. For each plugin slug: `plugin_information?action=plugin_information&slug={slug}`
   - Returns complete plugin metadata
   - Includes `versions` object with ALL historical versions and download URLs

**Example API Response (plugin_information):**
```json
{
  "name": "Akismet Anti-Spam",
  "slug": "akismet",
  "version": "5.3.0",
  "versions": {
    "5.3.0": "https://downloads.wordpress.org/plugin/akismet.5.3.0.zip",
    "5.2.1": "https://downloads.wordpress.org/plugin/akismet.5.2.1.zip",
    "5.2.0": "https://downloads.wordpress.org/plugin/akismet.5.2.0.zip"
  }
}
```

**Why NOT `browse=popular`:**
- We need chronological order by last update to track new/changed plugins
- Updated feed is more relevant for incremental sync

---

## Historical Versions

**Decision:** Fetch from WordPress.org API (NOT SVN)

**Source:** Individual plugin API calls (`plugin_information` action)

**Data Structure:**
- API response includes `versions` object
- Each key is a version number, value is the download URL
- Already includes ALL historical versions available on WordPress.org

**No SVN scraping needed** - everything is in the API

---

## Storage & File Organization

**Decision:** Local filesystem only (for now)

**Path Structure:**
```
wp-content/uploads/wpinsight/
├── plugin/
│   ├── {slug}/
│   │   ├── {slug}.{version}.zip
│   │   └── ...
│   └── ...
└── theme/
    └── ...
```

**Example:**
```
wp-content/uploads/wpinsight/plugin/akismet/
├── akismet.5.3.0.zip
├── akismet.5.2.1.zip
└── akismet.5.2.0.zip
```

**Why local?**
- Simpler for hackathon demo
- No external dependencies (S3 credentials, etc.)
- Easy to backup/move entire archive
- Cloud storage can be added later as enhancement

---

## Rate Limiting

**Decision:** Maximum 3 concurrent downloads

**Implementation:**
- Semaphore/lock mechanism in ZIP worker
- Check active downloads before starting new ones
- If 3 downloads already running, skip this worker tick
- Action Scheduler will retry on next tick (typically 1 minute)

**Why 3?**
- Conservative limit to avoid overwhelming WordPress.org servers
- Prevents getting IP banned
- Still allows reasonable throughput (3 downloads × 1MB average = 3MB/min minimum)

**Implementation Details:**
- Use transient with expiration as semaphore
- Key: `wpinsight_active_downloads`
- Value: Array of currently downloading job IDs
- Cleanup stale entries (downloads that crashed without releasing lock)

---

## Dependencies

### Action Scheduler

**Decision:** REQUIRED dependency (not bundled, not optional)

**Why required?**
- Reliable background job processing
- Handles 600K+ jobs gracefully
- Built-in retry logic
- Better than WP-Cron for long-running tasks

**Installation:**
- User must install Action Scheduler plugin separately
- OR user already has WooCommerce (includes Action Scheduler)

**Activation Check:**
- On plugin activation, check if `function_exists('as_schedule_recurring_action')`
- If not found, show admin notice and prevent activation
- Document clearly in readme.txt

**No fallback to WP-Cron** - Action Scheduler is mandatory for this plugin to function

---

## Database Strategy

### CPT Posts

- One post per plugin/theme slug
- Post meta stores current version + metadata
- ~60,000 posts expected (manageable)

### Custom Tables

1. **wpinsight_sync_state**
   - Tracks pagination cursor
   - Stores last sync timestamp
   - Enables resume on interruption

2. **wpinsight_zip_queue**
   - Job queue for downloads
   - Tracks status, attempts, errors
   - Keep ALL records (audit trail)
   - ~600,000 rows expected

3. **wpinsight_artifacts**
   - Records all downloaded ZIPs
   - Stores: path, version, SHA256 hash, timestamp
   - Keep ALL records (this is an archive)
   - ~600,000 rows expected

### Indexing Strategy

```sql
-- wpinsight_zip_queue
KEY status_priority (status, priority)
KEY updated_at (updated_at)

-- wpinsight_artifacts
UNIQUE KEY uniq_art (entity_type, slug, version, hash_sha256(16))

-- CPT meta queries
KEY meta_key_value (meta_key, meta_value(191))
```

---

## Sync Workflow

### Incremental Sync (every 5 minutes)

1. Fetch `browse=updated` page 1-3 (top ~250 plugins)
2. For each plugin, check if CPT exists
3. If exists, compare `last_updated` timestamp
4. If changed or new, call `plugin_information` API
5. Update CPT, enqueue new/changed versions to queue

### Full Sync (manual trigger)

1. Start at page 1, `browse=updated`
2. For EACH plugin on page:
   - Call `plugin_information` API
   - Create/update CPT
   - Parse `versions` object
   - Enqueue ALL versions to download queue
3. Increment page, store cursor in `wpinsight_sync_state`
4. Continue until API returns no more results
5. Mark full sync as complete

**Resume capability:**
- Store `{"page": 42, "last_slug": "example"}` in sync_state
- On resume, start from stored page
- Skip plugins already processed (check CPT existence)

---

## Download Workflow

### ZIP Worker (runs every 1 minute via Action Scheduler)

1. Check active download count (transient lock)
2. If ≥3 downloads running, exit (retry on next tick)
3. Query `wpinsight_zip_queue` for `status='queued'` jobs
4. ORDER BY priority ASC, updated_at ASC
5. LIMIT to (3 - active_downloads)
6. For each job:
   - Mark status='running'
   - Acquire lock (add to active_downloads transient)
   - Download ZIP via `wp_remote_get()` with streaming
   - Calculate SHA256 hash
   - Save to `uploads/wpinsight/{type}/{slug}/{slug}.{version}.zip`
   - Record in `wpinsight_artifacts` table
   - Mark status='done'
   - Release lock
7. On error:
   - Increment attempts counter
   - Mark status='failed' if attempts ≥ 5
   - Otherwise mark status='queued' for retry

---

## Admin UI (Minimal for Hackathon)

**Phase 1 (MVP):**
- Dashboard showing:
  - Total plugins in CPT
  - Total versions in artifacts table
  - Queue status (queued/running/done/failed counts)
  - Storage used (sum of filesizes)
- Buttons:
  - "Sync Now" (incremental)
  - "Full Sync" (start/resume)
  - "Process Queue" (manual worker trigger)

**Future enhancements:**
- Settings page (intervals, rate limits)
- Failed jobs retry interface
- Progress bar for full sync
- Charts and analytics

---

## WP-CLI Commands

**MVP:**
```bash
wp wpinsight sync [--mode=full|incremental]
wp wpinsight zip
wp wpinsight status
```

**Future:**
```bash
wp wpinsight plugin <slug>              # Show plugin details
wp wpinsight download <slug> [version]  # Download specific version
wp wpinsight cleanup [--dry-run]        # Remove failed jobs
wp wpinsight export <output-path>       # Export metadata as JSON
```

---

## Security Checklist

Per AGENTS.md requirements:

- ✅ All admin UI requires `manage_options` capability
- ✅ All forms use nonces
- ✅ All API responses sanitized (sanitize_text_field, sanitize_key, esc_url_raw)
- ✅ All output escaped (esc_html, esc_attr, wp_kses_post)
- ✅ All database queries use $wpdb->prepare()
- ✅ File paths validated to prevent directory traversal
- ✅ Atomic file writes (tmp file → rename)
- ✅ Error messages don't expose sensitive paths
- ✅ No direct superglobal access

---

## Performance Considerations

### Expected Load

- 60,000+ plugins to sync
- ~600,000 total ZIPs to download
- ~300GB-1.2TB total storage
- Full sync could take **days to weeks** depending on rate limiting

### Optimization Strategies

1. **Pagination cursor** - Resume interrupted syncs
2. **Rate limiting** - Avoid getting banned (3 concurrent max)
3. **Database indexes** - Fast queue queries even with 600K rows
4. **Streaming downloads** - Handle large ZIPs without memory exhaustion
5. **Action Scheduler** - Reliable background processing without blocking PHP
6. **Batch processing** - Process plugins in chunks, not all at once

### Bottlenecks

- **API rate limits** - WordPress.org may throttle requests
- **Disk I/O** - Writing 600K files takes time
- **Network bandwidth** - Downloading 1TB of data
- **PHP execution time** - Action Scheduler prevents timeout issues

---

## Testing Requirements

### Unit Tests (if time permits)

- API client response parsing
- Storage path generation
- Queue status transitions
- Semaphore lock/unlock logic

### Integration Tests

- Full sync of 10 test plugins
- Verify all versions downloaded
- Check database consistency
- Test resume capability (interrupt and restart sync)

### Manual Testing

- WordPress 6.9 + PHP 8.4
- Action Scheduler installed
- Incremental sync (250 plugins)
- Full sync (10 test plugins, all versions)
- Verify 3-concurrent limit enforced
- Check debug.log for errors
- Verify uninstall works correctly

---

## Deployment Checklist

- [ ] All code follows WordPress Coding Standards (run PHPCS)
- [ ] All functions have phpDoc comments
- [ ] readme.txt completed (using DOCUMENTATION-readme.txt.md template)
- [ ] CHANGELOG.md updated
- [ ] bin/deploy.sh script created
- [ ] Tested on WordPress 6.9 + PHP 8.4
- [ ] Action Scheduler dependency documented
- [ ] Plugin header includes `Network: false`
- [ ] License: GPLv3.0+

---

## Known Limitations (MVP)

1. **No themes support** - Plugins only (themes can be added later, API is identical)
2. **Local storage only** - No cloud storage support
3. **No search** - No full-text search across plugin code
4. **No analytics** - No charts or trend analysis
5. **Basic UI** - Minimal admin interface (can be enhanced post-hackathon)
6. **Single site only** - No multisite support

---

## Future Roadmap (Post-Hackathon)

1. Add themes support (same architecture, different CPT)
2. Cloud storage backend (S3, Google Cloud Storage)
3. Full-text search across plugin contents (requires unzipping)
4. Analytics dashboard (ecosystem trends over time)
5. REST API for external access to archive
6. Deduplication (detect identical vendor libraries)
7. Health monitoring (Slack/email alerts)
8. Performance optimizations (parallel downloads, CDN integration)

---

## References

- **WordPress.org API Docs:** https://codex.wordpress.org/WordPress.org_API
- **Action Scheduler:** https://actionscheduler.org/
- **Plugin Handbook:** https://developer.wordpress.org/plugins/
- **AGENTS.md:** Project contribution guidelines
- **PLAN.md:** Detailed implementation plan
