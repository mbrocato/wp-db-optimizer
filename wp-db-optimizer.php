<?php
/**
 * Plugin Name: WordPress Database Optimizer
 * Plugin URI: https://github.com/mbrocato/wp-db-optimizer
 * Description: Optimizes WordPress database with advanced SQL queries, custom tables, and safe migrations.
 * Version: 1.1
 * Author: Marc Brocato
 * Author URI: https://github.com/mbrocato
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WP_DB_OPTIMIZER_VERSION', '1.1');
define('WP_DB_OPTIMIZER_PATH', plugin_dir_path(__FILE__));
define('WP_DB_OPTIMIZER_URL', plugin_dir_url(__FILE__));

// Include optimizer functions
require_once WP_DB_OPTIMIZER_PATH . 'includes/optimizer-functions.php';

// Activation hook: Create custom table and backup
register_activation_hook(__FILE__, 'wp_db_optimizer_activate');

function wp_db_optimizer_activate() {
    global $wpdb;
    wp_db_optimizer_backup_db();
    
    // Add custom table for user analytics (example)
    $table_name = $wpdb->prefix . 'user_analytics';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        page_viewed varchar(255) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Schedule default weekly cron if not set
    if (!wp_next_scheduled('wp_db_optimizer_cron')) {
        wp_schedule_event(time(), 'weekly', 'wp_db_optimizer_cron');
    }
}

// Cron hook for auto-optimization
add_action('wp_db_optimizer_cron', 'wp_db_optimizer_auto_optimize');

// Admin menu for optimization page
add_action('admin_menu', 'wp_db_optimizer_add_menu');

function wp_db_optimizer_add_menu() {
    add_menu_page('DB Optimizer', 'DB Optimizer', 'manage_options', 'wp-db-optimizer', 'wp_db_optimizer_admin_page', 'dashicons-database');
}

// Admin page with UI upgrades
function wp_db_optimizer_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $log_content = file_exists(WP_DB_OPTIMIZER_PATH . 'optimization.log') ? file_get_contents(WP_DB_OPTIMIZER_PATH . 'optimization.log') : 'No logs yet.';
    
    if (isset($_POST['run_optimize'])) {
        $selected_opts = isset($_POST['optimizations']) ? $_POST['optimizations'] : [];
        wp_db_optimizer_run_selected($selected_opts);
        echo '<div class="notice notice-success"><p>Optimization complete! Check logs below.</p></div>';
    }
    
    if (isset($_POST['rollback'])) {
        $backup_file = $_POST['backup_file'];
        wp_db_optimizer_rollback($backup_file);
        echo '<div class="notice notice-success"><p>Rollback complete!</p></div>';
    }
    
    if (isset($_POST['update_cron'])) {
        $interval = sanitize_text_field($_POST['cron_interval']);
        wp_clear_scheduled_hook('wp_db_optimizer_cron');
        wp_schedule_event(time(), $interval, 'wp_db_optimizer_cron');
        echo '<div class="notice notice-success"><p>Cron interval updated!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>WordPress Database Optimizer</h1>
        
        <form method="post">
            <h2>Select Optimizations</h2>
            <label><input type="checkbox" name="optimizations[]" value="orphaned"> Clean Orphaned Data</label><br>
            <label><input type="checkbox" name="optimizations[]" value="revisions"> Clean Post Revisions</label><br>
            <label><input type="checkbox" name="optimizations[]" value="transients"> Clean Transients</label><br>
            <label><input type="checkbox" name="optimizations[]" value="spam"> Clean Spam Comments</label><br>
            <input type="submit" name="run_optimize" class="button button-primary" value="Run Selected Optimizations">
        </form>
        
        <h2>Rollback</h2>
        <form method="post">
            <input type="text" name="backup_file" placeholder="Path to backup.sql" required>
            <input type="submit" name="rollback" class="button button-secondary" value="Rollback Database">
        </form>
        
        <h2>Cron Settings</h2>
        <form method="post">
            <select name="cron_interval">
                <option value="daily">Daily</option>
                <option value="weekly" selected>Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
            <input type="submit" name="update_cron" class="button button-secondary" value="Update Cron Interval">
        </form>
        
        <h2>Optimization Logs</h2>
        <pre><?php echo esc_html($log_content); ?></pre>
        
        <script>
            // AJAX for real-time progress (simulated)
            document.querySelector('form').addEventListener('submit', function(e) {
                // e.preventDefault(); // Uncomment for full AJAX
                console.log('Running optimizations...');  // Placeholder for progress bar
            });
        </script>
    </div>
    <?php
}

// Deactivation: Unschedules cron
register_deactivation_hook(__FILE__, 'wp_db_optimizer_deactivate');

function wp_db_optimizer_deactivate() {
    $timestamp = wp_next_scheduled('wp_db_optimizer_cron');
    wp_unschedule_event($timestamp, 'wp_db_optimizer_cron');
}
