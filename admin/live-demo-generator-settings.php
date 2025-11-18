<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Settings {

    /** @var LDG_Generator */
    protected $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'live_demo_generator', LDG_Generator::OPTION_API_KEY, array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'live_demo_generator', LDG_Generator::OPTION_TEMPLATE_ID, array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'live_demo_generator', LDG_Generator::OPTION_DEP_MAP, array( $this, 'sanitize_dep_map' ) );
        register_setting( 'live_demo_generator', LDG_Generator::OPTION_DELETE_DATA, array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );

        add_settings_section( 'live_demo_generator_main', __( 'InstaWP Settings', 'live-demo-generator' ), null, 'live_demo_generator' );
        add_settings_field( LDG_Generator::OPTION_API_KEY, __( 'InstaWP API Key', 'live-demo-generator' ), array( $this, 'field_api_key' ), 'live_demo_generator', 'live_demo_generator_main' );
        add_settings_field( LDG_Generator::OPTION_TEMPLATE_ID, __( 'InstaWP Template / Snapshot ID', 'live-demo-generator' ), array( $this, 'field_template_id' ), 'live_demo_generator', 'live_demo_generator_main' );
        add_settings_field( LDG_Generator::OPTION_DEP_MAP, __( 'Dependency Map (JSON)', 'live-demo-generator' ), array( $this, 'field_dep_map' ), 'live_demo_generator', 'live_demo_generator_main' );
        add_settings_field( LDG_Generator::OPTION_DELETE_DATA, __( 'Delete data on uninstall', 'live-demo-generator' ), array( $this, 'field_delete_data' ), 'live_demo_generator', 'live_demo_generator_main' );
    }

    public function output() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'live-demo-generator' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Live Demo Generator', 'live-demo-generator' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'live_demo_generator' );
                do_settings_sections( 'live_demo_generator' );
                submit_button();
                ?>
            </form>
            <h2><?php esc_html_e( 'Quick Links', 'live-demo-generator' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Shortcode for the demo form: [live_demo_form]', 'live-demo-generator' ); ?></li>
                <li><?php esc_html_e( 'Webhook endpoint: /wp-json/live-demo-generator/v1/instawp-webhook', 'live-demo-generator' ); ?></li>
            </ul>
        </div>
        <?php
    }

    public function field_api_key() {
        $val = esc_attr( get_option( LDG_Generator::OPTION_API_KEY, '' ) );
        echo '<input type="text" name="' . esc_attr( LDG_Generator::OPTION_API_KEY ) . '" value="' . $val . '" style="width:420px" />';
    }

    public function field_template_id() {
        $val = esc_attr( get_option( LDG_Generator::OPTION_TEMPLATE_ID, '' ) );
        echo '<input type="text" name="' . esc_attr( LDG_Generator::OPTION_TEMPLATE_ID ) . '" value="' . $val . '" style="width:420px" />';
    }

    public function field_dep_map() {
        $current = get_option( LDG_Generator::OPTION_DEP_MAP, array() );
        if ( empty( $current ) ) {
            $current = $this->plugin->get_dep_map();
        }
        $val = esc_textarea( wp_json_encode( $current, JSON_PRETTY_PRINT ) );
        echo '<textarea name="' . esc_attr( LDG_Generator::OPTION_DEP_MAP ) . '" rows="12" style="width:98%;">' . $val . '</textarea>';
    }

    public function field_delete_data() {
        $val   = get_option( LDG_Generator::OPTION_DELETE_DATA, 'no' );
        $checked = ( 'yes' === $val ) ? 'checked="checked"' : '';
        echo '<label><input type="checkbox" name="' . esc_attr( LDG_Generator::OPTION_DELETE_DATA ) . '" value="yes" ' . $checked . ' /> ' . esc_html__( 'Delete all demo requests and settings when the plugin is deleted.', 'live-demo-generator' ) . '</label>';
    }

    public function sanitize_dep_map( $input ) {
        $decoded = json_decode( $input, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            return $decoded;
        }
        add_settings_error( LDG_Generator::OPTION_DEP_MAP, 'dep_map_error', __( 'Dependency map must be valid JSON.', 'live-demo-generator' ) );
        return get_option( LDG_Generator::OPTION_DEP_MAP, $this->plugin->get_dep_map() );
    }

    public function sanitize_checkbox( $value ) {
        return ( $value === 'yes' ) ? 'yes' : 'no';
    }
}
