<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
global $wpdb;
$table = $wpdb->prefix . 'demo_requests';
$wpdb->query("DROP TABLE IF EXISTS $table");
delete_option('live_demo_generator_api_key');
delete_option('live_demo_generator_template_id');
delete_option('live_demo_generator_dep_map');
