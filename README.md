# WordPress Database Optimization Tool

WordPress plugin for advanced database optimization: cleans orphaned data/revisions/transients/spam, adds custom tables, and performs safe schema migrations with UI and cron.

## Features
- UI with checkboxes for selecting optimizations (orphaned data, revisions, transients, spam).
- Adds custom tables (e.g., user_analytics) on activation.
- Backups before changes; one-click rollback from admin page.
- Weekly auto-runs via cron (customizable intervals: daily/weekly/monthly).
- Email notifications on completion (to admin email).
- Logs all actions to `optimization.log` in the plugin folder for auditing.

## Requirements
- WordPress 5.0 or higher.
- PHP 7.0 or higher.
- Admin access to run optimizations (manage_options capability).

## Installation
1. **Download the Plugin:**
   - Clone the repo: `git clone https://github.com/mbrocato/wp-db-optimizer.git` or download as ZIP.

2. **Upload to WordPress:**
   - Upload the `wp-db-optimizer` folder to `/wp-content/plugins/`.
   - Go to WordPress Admin > Plugins, find "WordPress Database Optimizer," and activate it.

3. **Initial Setup:**
   - On activation, the plugin creates a custom table (e.g., `wp_user_analytics`) and schedules a weekly cron job.
   - Navigate to Tools > DB Optimizer in the WP admin menu to access the dashboard.

## Usage
### Manual Optimization
1. Go to Tools > DB Optimizer.
2. Select checkboxes for desired optimizations:
   - Clean Orphaned Data: Removes post meta without associated posts.
   - Clean Post Revisions: Deletes old post revisions.
   - Clean Transients: Removes expired or unnecessary transients.
   - Clean Spam Comments: Deletes spam-approved comments.
3. Click "Run Selected Optimizations."
   - A backup is created automatically before changes.
   - Results are logged, and a success notice appears.

### Rollback
1. On the admin page, enter the path to a backup SQL file (e.g., `/wp-content/db_backup_YYYY-MM-DD.sql`).
2. Click "Rollback Database."
   - The database is restored from the SQL dump.

### Cron Settings
1. On the admin page, select an interval (Daily, Weekly, Monthly) from the dropdown.
2. Click "Update Cron Interval."
   - The cron job runs `wp_db_optimizer_auto_optimize()`, performing all optimizations.

### Viewing Logs
- Logs are displayed on the admin page in a <pre> block.
- File location: Plugin folder `/optimization.log` – Download via FTP for full history.

## Advanced Configuration
- **Custom Table Example:** The plugin adds a `wp_user_analytics` table on activation. Modify the SQL in `wp_db_optimizer_activate()` for your needs.
- **Email Notifications:** Enabled by default to the admin email. Customize in `wp_db_optimizer_run_selected()` by editing `wp_mail()`.
- **Cron Customization:** For server-side cron (instead of WP cron), add to crontab: `*/5 * * * * /usr/bin/php /path/to/wp-cron.php` (adjust for your host).
- **Extending Optimizations:** Add new checkboxes and functions in `wp_db_optimizer_admin_page()` and `wp_db_optimizer_run_selected()`.

## Troubleshooting
- **Permissions Issues:** Ensure the plugin folder is writable for logs/backups (chmod 755).
- **Cron Not Running:** Check WP cron status with plugins like WP Crontrol. If disabled, use server cron.
- **Backup Fails:** If large DB, increase PHP memory limit in `wp-config.php` (`define('WP_MEMORY_LIMIT', '256M');`).
- **Errors Logged:** Check `optimization.log` for details; common issues include DB connection timeouts (increase in `my.cnf` if self-hosted).

## Development
- Test on staging sites—DB changes are irreversible without backups.
- Contribute: Fork on GitHub, submit PRs for new queries or features.

## License
MIT
