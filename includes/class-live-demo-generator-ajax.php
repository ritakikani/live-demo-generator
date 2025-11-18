<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Ajax {

    public static function init() {
        add_action( 'wp_ajax_live_demo_create', array( __CLASS__, 'handle_create_demo' ) );
        add_action( 'wp_ajax_nopriv_live_demo_create', array( __CLASS__, 'handle_create_demo' ) );
    }

    public static function handle_create_demo() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'live_demo_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce' ), 400 );
        }

        $name   = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $email  = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Valid email required' ), 400 );
        }
        $role     = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'attendee';
        $duration = isset( $_POST['duration'] ) ? intval( $_POST['duration'] ) : 10800;
        $selected = isset( $_POST['plugins'] ) ? array_map( 'sanitize_text_field', (array) $_POST['plugins'] ) : array();

        if ( self::has_active_demo( $email ) ) {
            wp_send_json_error( array( 'message' => 'One active demo already exists for this email. Please wait or contact support.' ), 429 );
        }

        $builder  = LDG_Generator::instance();
        $resolved = $builder->resolve_dependencies( $selected );

        global $wpdb;
        $table = $wpdb->prefix . 'demo_users';
        $now   = current_time( 'mysql', 1 );
        $wpdb->insert( $table, array(
            'user_name'        => $name,
            'user_email'       => $email,
            'role'             => $role,
            'selected_plugins' => maybe_serialize( $selected ),
            'resolved_plugins' => maybe_serialize( $resolved ),
            'template_id'      => get_option( LDG_Generator::OPTION_TEMPLATE_ID ),
            'status'           => 'pending',
            'created_at'       => $now,
        ) );
        $request_id = $wpdb->insert_id;

        $payload = LDG_Generator_Instawp_API::prepare_payload( $request_id, $resolved, $role, $duration );

        $api_key = get_option( LDG_Generator::OPTION_API_KEY );
        if ( empty( $api_key ) || empty( $payload['template_id'] ) ) {
            $wpdb->update( $table, array( 'status' => 'failed' ), array( 'id' => $request_id ) );
            wp_send_json_error( array( 'message' => 'InstaWP API key and template id must be configured.' ), 500 );
        }

        $res = LDG_Generator_Instawp_API::call_api( $payload, $api_key );

        if ( is_wp_error( $res ) ) {
            $wpdb->update( $table, array( 'status' => 'failed' ), array( 'id' => $request_id ) );
            wp_send_json_error( array( 'message' => 'API request failed: ' . $res->get_message() ), 500 );
        }

        $code     = wp_remote_retrieve_response_code( $res );
        $body_raw = wp_remote_retrieve_body( $res );
        $body     = json_decode( $body_raw, true );

        if ( $code >= 200 && $code < 300 && ! empty( $body['site_url'] ) ) {
            $site_url  = esc_url_raw( $body['site_url'] );
            $auto_login = isset( $body['auto_login_url'] ) ? esc_url_raw( $body['auto_login_url'] ) : '';
            $insta_id   = isset( $body['site_id'] ) ? sanitize_text_field( $body['site_id'] ) : '';

            $expires_at = date( 'Y-m-d H:i:s', time() + $duration );
            $wpdb->update( $table, array(
                'instawp_site_id' => $insta_id,
                'demo_url'        => $site_url,
                'auto_login_url'  => $auto_login,
                'status'          => 'created',
                'expires_at'      => $expires_at,
            ), array( 'id' => $request_id ) );

            $subject = __( 'Your demo is ready', 'live-demo-generator' );
            $message = sprintf( __( "Hi %s,

Your demo is ready: %s

Auto-login: %s

This demo expires on %s.

Enjoy!", 'live-demo-generator' ), $name ?: $email, $site_url, $auto_login, $expires_at );
            wp_mail( $email, $subject, $message );

            wp_send_json_success( array( 'message' => 'Demo created. Check your email for the link.' ) );
        } else {
            $wpdb->update( $table, array( 'status' => 'failed' ), array( 'id' => $request_id ) );
            $err = is_array( $body ) ? json_encode( $body ) : $body_raw;
            wp_send_json_error( array( 'message' => 'Demo creation failed. InstaWP response: ' . $err ), 500 );
        }
    }

    private static function has_active_demo( $email ) {
        global $wpdb;
        $table = $wpdb->prefix . 'demo_users';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_email=%s AND status IN ('created','pending') ORDER BY id DESC LIMIT 1", $email ), ARRAY_A );
        if ( ! $row ) {
            return false;
        }
        if ( ! empty( $row['expires_at'] ) && strtotime( $row['expires_at'] ) < time() ) {
            return false;
        }
        return true;
    }
}
