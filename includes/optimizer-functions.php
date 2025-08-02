<?php
function wp_db_optimizer_backup_db() {
    global $wpdb;
    $backup_file = WP_CONTENT_DIR . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
    $output = '';
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        $create = $wpdb->get_row("SHOW CREATE TABLE $table_name", ARRAY_N);
        $output .= "\n\n" . $create[1] . ";\n\n";
        
        $rows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_N);
        foreach ($rows as $row) {
            $output .= "INSERT INTO $table_name VALUES(";
            foreach ($row as $data) {
                $output .= "'" . esc_sql($data) . "', ";
            }
            $output = substr($output, 0, -2);
            $output .= ");\n";
        }
    }
    
    file_put_contents($backup_file, $output);
    error_log("Database backup created: $backup_file", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    return $backup_file;
}

function wp_db_optimizer_run_selected($opts) {
    global $wpdb;
    $backup_file = wp_db_optimizer_backup_db();
    
    if (in_array('orphaned', $opts)) {
        $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");
        error_log("Cleaned orphaned post meta.", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    }
    
    if (in_array('revisions', $opts)) {
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");
        error_log("Cleaned post revisions.", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    }
    
    if (in_array('transients', $opts)) {
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'");
        error_log("Cleaned transients.", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    }
    
    if (in_array('spam', $opts)) {
        $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        error_log("Cleaned spam comments.", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    }
    
    // Optimize all tables
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE " . $table[0]);
    }
    error_log("Tables optimized.", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
    
    // Email notification (if admin email set)
    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail($admin_email, 'DB Optimization Complete', 'Optimization ran successfully. Backup: ' . $backup_file);
    }
}

function wp_db_optimizer_auto_optimize() {
    wp_db_optimizer_run_selected(['orphaned', 'revisions', 'transients', 'spam']);
}

function wp_db_optimizer_rollback($backup_file) {
    global $wpdb;
    if (!file_exists($backup_file)) {
        error_log("Backup file not found: $backup_file", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
        return;
    }
    $sql = file_get_contents($backup_file);
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        if (trim($query)) {
            $wpdb->query($query);
        }
    }
    error_log("Database rolled back from $backup_file", 3, WP_DB_OPTIMIZER_PATH . 'optimization.log');
}
