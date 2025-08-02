# WordPress Database Optimization Tool

WordPress plugin for advanced database optimization: cleans orphaned data/revisions/transients/spam, adds custom tables, and performs safe schema migrations with UI and cron.

## Setup
1. Upload to /wp-content/plugins/ and activate.
2. Access Tools > DB Optimizer for manual runs, selections, rollback, and cron settings.
3. Weekly auto-runs on activation (customizable).

## Features
- UI with checkboxes for optimizations (orphaned data, revisions, transients, spam).
- Adds custom tables (e.g., user_analytics) on activation.
- Backups before changes; one-click rollback.
- Custom cron intervals (daily/weekly/monthly) and email notifications.
- Logs to optimization.log in plugin folder.

## License
MIT
