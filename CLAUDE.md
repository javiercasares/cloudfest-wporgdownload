# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Prerequisites

**Always read AGENTS.md first** - it contains mandatory contribution guidelines for all WordPress plugins in this project family.

## Project Overview

This is a WordPress plugin for the Cloudfest Hackathon that downloads and archives ALL WordPress.org plugins, including historical versions.

**Project Name:** WordPress.org Plugin/Theme Downloader (WPInsight)

**Purpose:** Create a complete mirror of WordPress.org's plugin repository with metadata stored in CPTs and ZIP files organized in local filesystem.

**Key Features:**
- Full sync of 60,000+ plugins via WordPress.org API
- Historical version tracking (fetched from `plugin_information` API)
- Background ZIP downloads (max 3 concurrent) via Action Scheduler
- Local storage: `wp-content/uploads/wpinsight/{plugin|theme}/{slug}/{slug}.{version}.zip`
- WP-CLI commands for manual operations

This codebase follows strict KISS principles - simple, clear, and maintainable.

## Architecture

- **By default, plugins must be implemented using classes** (per AGENTS.md)
- Plugin must be self-contained within its directory
- Modular design preferred: classes or traits over global functions
- Avoid tight coupling with themes or other plugins

## Compatibility Requirements

**PROJECT-SPECIFIC (overrides AGENTS.md defaults for this plugin):**
- **WordPress:** 6.9+ only (not backward compatible)
- **PHP:** 8.4+ only (uses modern PHP features)
- **Database:** MariaDB 10.6+
- **Multisite:** NOT supported - explicitly set `Network: false` in plugin header
  - Rationale: Archiving WordPress.org doesn't benefit from multisite context
- **Dependencies:** Action Scheduler (REQUIRED - plugin will not work without it)

## Development Standards

### Coding Standards

- Follow PHP Coding Standards and WordPress Coding Standards (WordPress-Core, WordPress-Docs, WordPress-Extra)
- **Run PHPCS on all modified files before committing**
- All code in U.S. English
- All public functions/methods/classes/hooks must have phpDoc documentation

### Security (OWASP Top 10 + WordPress)

- Validate, sanitize, and escape all input/output
- Use WordPress filters and APIs instead of direct superglobal access
- Apply nonces to all state-changing actions
- Enforce capability checks (default: `manage_options` for admin features)
- Review for XSS, CSRF, SQL injection, privilege escalation, insecure deserialization
- Implement secure by default principles

### Database & Persistence

- **Use WordPress database APIs only** - no direct DB access (including in uninstall.php)
- Prefer caching (Object Cache, Transients API)
- Always create `uninstall.php`
- If admin interface exists: provide option for users to opt-in to data deletion on uninstall (default: preserve data)

### Internationalization

- All user-facing strings must use WordPress i18n functions
- Support RTL languages
- Follow WordPress accessibility guidelines

## Build & Deployment

- `bin/deploy.sh` must generate distributable ZIP in parent directory
- Deploy script reads version from plugin headers
- Generated package excludes dev files, tests, CI config, tooling artifacts
- **Deployments are always manual** - no automatic deploys allowed
- If using Composer: bundle production dependencies only, respect PHP version constraints

## Documentation

- All project documentation goes in `docs/` directory
- Follow `DOCUMENTATION-readme.txt.md` template for readme.txt
- Follow `DOCUMENTATION-changelog.txt.md` template for changelog.txt
- Update `CHANGELOG.md` when adding features or fixing bugs
- Include WordPress, PHP, and database versions used for testing

## Git Workflow

- **All Git operations are manual** - no automatic commits or PRs
- Clear, imperative commit messages (≤72 characters)
- Keep changes focused and minimal
- Update changelogs for user-facing changes

## Testing Requirements

- No PHP notices, warnings, or deprecated messages allowed
- Test on supported WordPress, PHP, and MariaDB versions
- Test single-site and Multisite scenarios
- Check `wp-content/debug.log` and browser console during manual testing
- Add/update automated tests when relevant

## Performance

- Avoid unnecessary database queries and repeated computations
- Use WordPress APIs appropriately (Options API, Transients API, Object Cache)
- Ensure correct behavior under high-traffic and Multisite environments

## License

- GPLv3.0 or later (default)
- Third-party code must be license-compatible and attributed
- No obfuscated, minified-without-source, or encrypted PHP code

## Project-Specific Documentation

Before implementing features, read these project documents in order:

1. **AGENTS.md** - General contribution guidelines for all WordPress plugins
2. **docs/PLAN.md** - Detailed implementation plan with phases
3. **docs/DECISIONS.md** - Quick reference for all architectural decisions
4. **docs/IDEA.md** - Original code skeletons and structure

## Key Implementation Details

### WordPress.org API Usage

**Full Sync Strategy:**
1. Use `query_plugins` with `browse=updated` to paginate through all plugins
2. For each plugin, call `plugin_information` API to get version history
3. Parse `versions` object from API response (contains all historical versions)
4. Enqueue ALL versions to download queue

**NO SVN scraping needed** - everything is in the WordPress.org API.

### Rate Limiting

**CRITICAL:** Maximum 3 concurrent downloads to avoid being banned by WordPress.org
- Implement semaphore/lock mechanism using transients
- Check active downloads before starting new ones
- Action Scheduler handles retries automatically

### Database Tables

Three custom tables (see `class-wpinsight-db.php` in IDEA.md):
- `wpinsight_sync_state` - Pagination cursor and sync status
- `wpinsight_zip_queue` - Download job queue (~600K rows expected)
- `wpinsight_artifacts` - Downloaded ZIP records (~600K rows expected)

### File Organization

```
wp-content/uploads/wpinsight/
├── plugin/{slug}/{slug}.{version}.zip
└── theme/{slug}/{slug}.{version}.zip
```

One directory per plugin/theme to avoid filesystem limits.

## Important Notes

- **KISS principle is mandatory** - avoid over-engineering, unnecessary abstraction, premature optimization
- Action Scheduler is REQUIRED dependency (check on activation, show admin notice if missing)
- No Multisite support - single site only
- All code must use PHP 8.4+ features (typed properties, constructor property promotion, etc.)
- Security issues must be reported responsibly (not disclosed publicly)
