# Implementation Plan: WordPress.org Plugin/Theme Downloader

**Project:** Cloudfest Hackathon - WordPress.org Complete Archive Downloader
**Goal:** Download and archive ALL WordPress.org plugins and themes, including historical versions, with metadata stored in WordPress CPTs.

---

## Technical Requirements

- **WordPress:** 6.9+
- **PHP:** 8.4+
- **Architecture:** Object-oriented (Classes)
- **Multisite:** Not supported (single-site only - archiving doesn't benefit from multisite)
- **Dependencies:** Action Scheduler (required)

---

## Executive Summary

This plugin will create a complete mirror of WordPress.org's plugin and theme repository, including:
- **Metadata** stored in Custom Post Types (one CPT entry per plugin/theme)
- **Historical versions** fetched from individual plugin API calls and tracked in custom database tables
- **ZIP archives** organized in local filesystem directories (one folder per plugin/theme)
- **Automated synchronization** via Action Scheduler (required dependency)
- **WP-CLI commands** for manual operations and bulk processing
- **Rate limiting** to 3 concurrent downloads maximum

---

## Architecture Overview

### Core Components

1. **CPT System** (`class-wpinsight-cpt.php`)
   - `wpinsight_plugin` - One post per plugin slug
   - `wpinsight_theme` - One post per theme slug
   - Post meta stores: slug, current version, last_updated, requirements, description, etc.

2. **Custom Database Tables** (`class-wpinsight-db.php`)
   - `wpinsight_sync_state` - Tracks sync progress (cursor for pagination, last run timestamps)
   - `wpinsight_zip_queue` - Job queue for ZIP downloads (status, attempts, errors)
   - `wpinsight_artifacts` - Records all downloaded ZIPs with version, path, SHA256 hash

3. **WordPress.org API Client** (`class-wpinsight-wporg-client.php`)
   - Queries plugins/themes via https://api.wordpress.org/plugins/info/1.2/
   - **Two API actions:**
     - `query_plugins` with `browse=updated` - Lists plugins by last update (pagination support)
     - `plugin_information` - Fetches individual plugin details including version history
   - Handles pagination, field selection, and error handling
   - Respects rate limits and timeouts

4. **Sync Engine** (`class-wpinsight-sync.php`)
   - **Incremental mode**: Fetches recent updates via `browse=updated`
   - **Full sync mode**: Paginates through ALL plugins/themes sorted by last update
   - **Historical versions**: Calls `plugin_information` API to fetch version history for each plugin
   - Creates/updates CPT entries
   - Enqueues all historical versions of ZIPs for download

5. **ZIP Download Queue** (`class-wpinsight-zip-queue.php`)
   - Background worker processes jobs from queue table
   - **Maximum 3 concurrent downloads** (rate limiting)
   - Handles retries (max 5 attempts by default)
   - Tracks download status: queued → running → done/failed
   - Powered by Action Scheduler (required dependency)

6. **Storage Manager** (`class-wpinsight-storage.php`)
   - Downloads ZIPs to: `wp-content/uploads/wpinsight/{plugin|theme}/{slug}/{slug}.{version}.zip`
   - Uses streaming download to handle large files
   - Calculates SHA256 hash for verification
   - Records artifact in database

7. **Admin UI** (`class-wpinsight-admin.php`)
   - Basic dashboard showing sync status
   - Manual trigger buttons (TO BE EXPANDED)
   - Settings configuration (intervals, batch sizes)

8. **WP-CLI Commands** (`class-wpinsight-cli.php`)
   - `wp wpinsight sync` - Manual sync trigger
   - `wp wpinsight zip` - Manual ZIP worker trigger
   - (TO BE ADDED) Bulk operations, status reports, cleanup commands

---

## Data Flow

```
WordPress.org API (browse=updated)
       ↓
[Sync Engine] → Lists all plugins, paginates through them
       ↓
WordPress.org API (plugin_information) ← Fetch individual plugin details + version history
       ↓
[Sync Engine] → Creates/Updates CPT entries + Enqueues ALL versions to ZIP queue
       ↓
[ZIP Worker via Action Scheduler] → Downloads max 3 concurrent
       ↓
[Storage] → Saves to local filesystem + records in artifacts table
```

---

## File Organization Strategy

Due to the massive volume (60,000+ plugins, each with multiple versions):

```
wp-content/uploads/wpinsight/
├── plugin/
│   ├── akismet/
│   │   ├── akismet.5.3.0.zip
│   │   ├── akismet.5.2.1.zip
│   │   └── akismet.5.2.0.zip
│   ├── jetpack/
│   │   ├── jetpack.12.9.zip
│   │   └── ...
│   └── ...
└── theme/
    ├── twentytwentyfour/
    │   ├── twentytwentyfour.1.0.zip
    │   └── ...
    └── ...
```

**Rationale:**
- One folder per plugin/theme avoids filesystem limits (most filesystems handle 10-100K files per directory)
- Easy to locate, backup, or delete individual plugins
- Atomic operations (tmp file → rename to final location)

---

## Key Implementation Decisions (APPROVED)

### 1. Sync Strategy ✅ DECIDED

**Implementation:**
- Full sync uses `browse=updated` to paginate through ALL plugins/themes sorted by last update date
- For each plugin found, make individual `plugin_information` API call to get complete details
- Pagination cursor stored in `wpinsight_sync_state` table to enable resume on interruption
- Process plugins sequentially to avoid overwhelming the API

**Flow:**
1. Call `query_plugins` with `browse=updated`, `per_page=100`, `page=N`
2. For each plugin slug returned, call `plugin_information` to get full details + version history
3. Create/update CPT entry with metadata
4. Enqueue ALL versions found to ZIP download queue
5. Increment page counter, repeat until no more results

### 2. Historical Versions ✅ DECIDED

**Implementation:**
- Historical versions ARE available via WordPress.org API
- Use `plugin_information` action with specific slug to fetch complete plugin data
- API response includes `versions` array with all available versions and their download URLs
- No SVN scraping needed - API provides everything

**Example API call:**
```
https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=akismet
```

Response includes:
- Current version
- `versions` object: `{"5.3.0": "https://...", "5.2.1": "https://...", ...}`
- Metadata, requirements, ratings, etc.

### 3. Volume & Performance ✅ DECIDED

**Storage:** Local filesystem only (for now)
- Path: `wp-content/uploads/wpinsight/{plugin|theme}/{slug}/{slug}.{version}.zip`

**Rate Limiting:** Maximum 3 concurrent downloads
- Implement semaphore/lock mechanism to prevent more than 3 simultaneous downloads
- Queue processes via Action Scheduler with appropriate delays

**Progress Tracking:**
- Use `wpinsight_sync_state.cursor_text` to store: `{"page": 42, "last_slug": "akismet"}`
- Resume capability if sync is interrupted

**Estimates:**
- 60,000+ plugins × average 10 versions = 600,000 ZIP files
- Average ZIP size: 500KB - 2MB (highly variable)
- Total storage: 300GB - 1.2TB (rough estimate)

### 4. Database Scalability ✅ DECIDED

**Strategy:**
- CPT posts: 60K+ entries (manageable with proper indexing)
- `wpinsight_artifacts`: 600K+ rows (add composite indexes on entity_type+slug+version)
- `wpinsight_zip_queue`: Keep ALL records for audit trail (status field for filtering)

**Artifacts:** Keep all downloaded versions (this is an archive after all)

**Queue cleanup:** Mark as 'done' or 'failed', keep records indefinitely for troubleshooting

### 5. Action Scheduler ✅ DECIDED

**Implementation:**
- **REQUIRED dependency** - plugin will NOT work without Action Scheduler
- Check for Action Scheduler on activation, show admin notice if missing
- No fallback to WP-Cron
- Document in readme.txt that Action Scheduler is required

**Installation methods:**
- User installs Action Scheduler plugin separately (recommended)
- OR user has WooCommerce installed (includes Action Scheduler)

### 6. Multisite Compatibility ✅ DECIDED

**Implementation:**
- **NOT multisite compatible** - single site only
- Plugin header: `Network: false` (explicitly disable network activation)
- Rationale: Archiving all WordPress.org plugins makes no sense in multisite context
- Each installation maintains its own complete archive

**Plugin Header:**
```php
/**
 * Network: false
 */
```

---

## Implementation Phases

### Phase 1: Core Infrastructure (IDEA.md baseline)
- ✅ Already designed in IDEA.md
- CPT registration
- Database tables
- API client
- Incremental sync (new/updated feeds)
- ZIP queue + worker
- Storage manager
- Basic admin UI
- WP-CLI commands

### Phase 2: Full Sync Mode
- [ ] Implement pagination cursor in `wpinsight_sync_state`
- [ ] Add "full sync" method using `browse=updated` with pagination
- [ ] For each plugin, call `plugin_information` API to get version history
- [ ] Parse `versions` array from API response
- [ ] Enqueue ALL versions to ZIP download queue
- [ ] WP-CLI command: `wp wpinsight sync --mode=full`
- [ ] Admin UI button to start/stop full sync
- [ ] Progress indicator (X of Y plugins processed)

### Phase 3: Rate Limiting & Download Management
- [ ] Implement 3-concurrent-download limit in ZIP worker
- [ ] Add semaphore/lock mechanism (transient or database flag)
- [ ] Modify `class-wpinsight-zip-queue.php` to respect concurrency limit
- [ ] Add delay between download batches if needed
- [ ] Monitor and log rate limit violations

### Phase 4: Admin UI Enhancements
- [ ] Dashboard with statistics (total plugins, themes, ZIPs, storage used)
- [ ] Queue status table (queued, running, done, failed counts)
- [ ] Settings page (intervals, batch sizes, rate limits)
- [ ] Manual sync buttons ("Sync Now", "Full Sync", "Process Queue")
- [ ] Failed jobs retry interface

### Phase 5: Performance & Optimization
- [ ] Database query optimization (add missing indexes)
- [ ] Implement rate limiting (requests per minute)
- [ ] Add memory limit checks before large operations
- [ ] Batch processing with progress persistence (resume capability)
- [ ] Error logging and admin notifications

### Phase 6: Cleanup & Packaging
- [ ] Complete `uninstall.php` (with user opt-in for data deletion)
- [ ] Add `bin/deploy.sh` script
- [ ] Write `readme.txt` following DOCUMENTATION-readme.txt.md template
  - Document Action Scheduler as required dependency
  - Specify minimum requirements: WordPress 6.9+, PHP 8.4+
  - Note: Not compatible with Multisite
- [ ] Create `CHANGELOG.md` and `changelog.txt`
- [ ] Run PHPCS on all files
- [ ] Test on WordPress 6.9 with PHP 8.4
- [ ] Verify Action Scheduler dependency check on activation

---

## Security Considerations

Per AGENTS.md security requirements:

1. **Capability checks**: All admin operations require `manage_options`
2. **Nonces**: Add to all manual sync buttons and settings forms
3. **Input validation**:
   - Sanitize slug, version strings from API responses
   - Validate download URLs before fetching
4. **Output escaping**: All data displayed in admin UI must be escaped
5. **File operations**:
   - Use `wp_mkdir_p()` for directory creation
   - Atomic writes (tmp file → rename)
   - Prevent directory traversal (validate paths)
6. **Database queries**:
   - Use `$wpdb->prepare()` for all queries
   - Never use direct superglobal access
7. **Error handling**:
   - Catch exceptions, log securely
   - Don't expose sensitive paths/data in error messages

---

## Testing Strategy

1. **Unit tests** (if time permits):
   - API client response parsing
   - Storage path generation
   - Queue status transitions

2. **Integration tests**:
   - Full sync of 10-20 test plugins
   - Verify all versions downloaded correctly
   - Check database consistency

3. **Manual testing checklist**:
   - [ ] Fresh install on WordPress 6.9 + PHP 8.4
   - [ ] Verify plugin activation fails gracefully without Action Scheduler
   - [ ] Install Action Scheduler, activate plugin successfully
   - [ ] Run incremental sync (verify CPTs created)
   - [ ] Run full sync on 10 test plugins (verify version history fetched)
   - [ ] Run ZIP worker (verify max 3 concurrent downloads)
   - [ ] Verify files downloaded to correct paths with proper naming
   - [ ] Check for PHP errors in `debug.log`
   - [ ] Verify no database warnings
   - [ ] Test uninstall process (with and without data deletion option)

---

## Future Enhancements (Post-Hackathon)

These features are out of scope for the initial hackathon implementation but could be added later:

1. **Storage backend**: Add S3/cloud storage support as alternative to local filesystem
2. **Themes support**: Currently focused on plugins; themes support can be added (API is identical)
3. **Public access**: Expose archive via REST API for external consumption
4. **Compression**: Store unzipped plugin contents for searchability/analysis
5. **Deduplication**: Detect and dedupe identical files across plugins (vendor libraries)
6. **Monitoring**: Health checks, Slack/email notifications for failures
7. **Analytics**: Dashboard with charts showing plugin ecosystem trends over time
8. **Search**: Full-text search across plugin code (requires unzipping + indexing)

---

## Timeline Estimate (Hackathon Context)

Assuming 2-3 developers working in parallel:

- **Day 1 Morning**: Implement Phase 1 (baseline from IDEA.md)
- **Day 1 Afternoon**: Test incremental sync, fix bugs, add Phase 4 basic UI
- **Day 2 Morning**: Implement Phase 2 (full sync mode)
- **Day 2 Afternoon**: Implement Phase 3 (rate limiting) + focus on Phase 5 (optimization)
- **Day 3 Morning**: Testing, debugging, documentation
- **Day 3 Afternoon**: Demo preparation, deploy script, final polish

**Critical path:** Get Phase 1 + Phase 2 working reliably with version history fetching. Rate limiting (Phase 3) is essential for not getting blocked by WordPress.org.

---

## Next Steps

1. **Review and approve this plan**
2. **Make decisions** on all items marked "DECISION NEEDED"
3. **Create initial file structure** (folders, stub files)
4. **Implement Phase 1** following IDEA.md code skeletons
5. **Test on local WordPress instance**
6. **Iterate based on feedback**

---

## Notes

- All code must follow AGENTS.md guidelines (KISS, security, WordPress standards)
- Remember to run PHPCS before committing any code
- Keep documentation updated as implementation evolves
- Focus on working prototype first, then optimize
