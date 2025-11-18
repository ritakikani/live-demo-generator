<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Install {

    public static function activate() {
        global $wpdb;
        $table           = $wpdb->prefix . 'demo_users';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          user_name VARCHAR(191) NULL,
          user_email VARCHAR(191) NOT NULL,
          role VARCHAR(50) NOT NULL,
          selected_plugins LONGTEXT NULL,
          resolved_plugins LONGTEXT NULL,
          template_id VARCHAR(255) NULL,
          instawp_site_id VARCHAR(255) NULL,
          demo_url VARCHAR(255) NULL,
          auto_login_url VARCHAR(255) NULL,
          status VARCHAR(50) DEFAULT 'pending',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          expires_at DATETIME NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function uninstall() {
        global $wpdb;
        $table = $wpdb->prefix . 'demo_users';

        $delete_data = get_option( LDG_Generator::OPTION_DELETE_DATA, 'no' );

        if ( 'yes' === $delete_data ) {
            $wpdb->query( "DROP TABLE IF EXISTS $table" );

            delete_option( LDG_Generator::OPTION_API_KEY );
            delete_option( LDG_Generator::OPTION_TEMPLATE_ID );
            delete_option( LDG_Generator::OPTION_DEP_MAP );
            delete_option( LDG_Generator::OPTION_DELETE_DATA );
        }
    }
}
