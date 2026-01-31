# CloudFest WPOrg Download

WordPress plugin for CloudFest Hackathon that downloads and archives ALL WordPress.org plugins including historical versions.

## Project Information

- **Plugin URI:** https://github.com/javiercasares/cloudfest-wporgdownload
- **Author:** CloudFest Team
- **Author URI:** https://hackathon.cloudfest.com/
- **License:** GPL-3.0-or-later

## Requirements

- **WordPress:** 6.9+
- **PHP:** 8.4+
- **MariaDB:** 10.6+
- **Dependencies:** Action Scheduler (required)
- **Storage:** ~1TB for complete archive
- **Architecture:** Object-oriented (Classes)

## Features

- Full sync of 60,000+ WordPress.org plugins
- Historical version tracking via API
- Background ZIP downloads (max 3 concurrent)
- Custom Post Types for metadata storage
- Local filesystem storage organized by plugin
- Action Scheduler powered queue system
- WP-CLI commands for manual operations
- Admin dashboard with statistics

## Documentation

- **[AGENTS.md](AGENTS.md)** - General contribution guidelines
- **[CLAUDE.md](CLAUDE.md)** - Project-specific guidance for Claude Code
- **[docs/PLAN.md](docs/PLAN.md)** - Detailed implementation plan
- **[docs/DECISIONS.md](docs/DECISIONS.md)** - Architectural decisions reference
- **[docs/IDEA.md](docs/IDEA.md)** - Original code skeletons
- **[docs/ROADMAP.md](docs/ROADMAP.md)** - Granular implementation steps

## Development

This project follows strict KISS principles and WordPress coding standards.

### Setup

1. Clone repository
2. Install Action Scheduler plugin
3. Activate this plugin
4. Follow ROADMAP.md for implementation steps

### Testing

```bash
# Run PHPUnit tests
vendor/bin/phpunit

# Run PHPCS
vendor/bin/phpcs --standard=WordPress includes/*.php *.php
```

### WP-CLI Commands

```bash
# Sync plugins (incremental)
wp wpinsight sync --entity=plugin --feed=updated

# Full sync
wp wpinsight sync --mode=full

# Process download queue
wp wpinsight zip

# Show status
wp wpinsight status
```

## File Organization

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

## Database Tables

- `wpinsight_sync_state` - Tracks pagination cursor and sync status
- `wpinsight_zip_queue` - Download job queue (~600K rows expected)
- `wpinsight_artifacts` - Downloaded ZIP records (~600K rows expected)

## Contributing

Follow the guidelines in [AGENTS.md](AGENTS.md) and implement features according to [docs/ROADMAP.md](docs/ROADMAP.md).

All code must:
- Follow WordPress Coding Standards
- Include PHPDoc documentation
- Have PHPUnit tests where applicable
- Pass PHPCS validation
- Be validated at each step before proceeding

## Known Limitations

- Single site only (no Multisite support)
- Local storage only (no cloud storage)
- Plugins only (themes support can be added later)
- Requires Action Scheduler plugin

## License

GPL-3.0-or-later - See [LICENSE](LICENSE) file for details.