<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Admin {

    /** @var LDG_Generator */
    protected $plugin;

    /** @var LDG_Generator_Settings */
    public $settings_page;

    public function __construct( $plugin ) {
        $this->plugin        = $plugin;
        $this->settings_page = new LDG_Generator_Settings( $plugin );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'Live Demo Generator', 'live-demo-generator' ),
            __( 'Live Demo Generator', 'live-demo-generator' ),
            'manage_options',
            'live-demo-generator',
            array( $this->settings_page, 'output' ),
            'dashicons-admin-site'
        );

        add_submenu_page(
            'live-demo-generator',
            __( 'Active Demos', 'live-demo-generator' ),
            __( 'Active Demos', 'live-demo-generator' ),
            'manage_options',
            'live-demo-generator-requests',
            array( $this, 'admin_requests_page' )
        );
    }

    public function admin_requests_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'live-demo-generator' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'demo_requests';
        $rows  = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 200", ARRAY_A );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Active & Recent Demo Requests', 'live-demo-generator' ); ?></h1>
            <table class="widefat fixed">
                <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Role', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Plugins', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Demo URL', 'live-demo-generator' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'live-demo-generator' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $rows as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r['id'] ); ?></td>
                        <td><?php echo esc_html( $r['requester_email'] ); ?></td>
                        <td><?php echo esc_html( $r['role'] ); ?></td>
                        <td style="max-width:300px"><?php echo esc_html( maybe_unserialize( $r['resolved_plugins'] ) ); ?></td>
                        <td><?php echo esc_html( $r['status'] ); ?></td>
                        <td>
                            <?php if ( $r['demo_url'] ) : ?>
                                <a href="<?php echo esc_url( $r['demo_url'] ); ?>" target="_blank"><?php esc_html_e( 'Open', 'live-demo-generator' ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $r['created_at'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
