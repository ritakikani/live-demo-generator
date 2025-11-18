<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Instawp_API {

    public static function prepare_payload( $request_id, $resolved_plugins, $role, $duration ) {
        $template_id = get_option( LDG_Generator::OPTION_TEMPLATE_ID );
        $site_name   = 'live-demo-' . substr( md5( $request_id . time() ), 0, 8 );
        $notify      = rest_url( 'live-demo-generator/v1/instawp-webhook' ) . '?request_id=' . $request_id;

        $payload = array(
            'template_id'         => $template_id,
            'site_name'           => $site_name,
            'plugins'             => array_values( $resolved_plugins ),
            'expiry_seconds'      => intval( $duration ),
            'auto_login'          => true,
            'role_for_auto_login' => $role,
            'post_creation_script'=> 'insta-demo-setup.php',
            'notify_webhook'      => $notify,
        );

        return $payload;
    }

    public static function call_api( $payload, $api_key ) {
        $api_url = 'https://api.instawp.io/v1/sites/create';

        $args = array(
            'body'    => wp_json_encode( $payload ),
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 60,
        );

        return wp_remote_post( $api_url, $args );
    }

    public static function register_routes() {
        register_rest_route( 'live-demo-generator/v1', '/instawp-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'webhook_handler' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public static function webhook_handler( WP_REST_Request $request ) {
        $body       = $request->get_json_params();
        $request_id = isset( $_GET['request_id'] ) ? sanitize_text_field( $_GET['request_id'] ) : 0;
        if ( empty( $request_id ) ) {
            return new WP_REST_Response( array( 'error' => 'missing request_id' ), 400 );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'demo_users';

        $insta_id   = isset( $body['site_id'] ) ? sanitize_text_field( $body['site_id'] ) : '';
        $site_url   = isset( $body['site_url'] ) ? esc_url_raw( $body['site_url'] ) : '';
        $auto_login = isset( $body['auto_login_url'] ) ? esc_url_raw( $body['auto_login_url'] ) : '';
        $status     = isset( $body['status'] ) ? sanitize_text_field( $body['status'] ) : 'created';
        $expires_at = null;
        if ( ! empty( $body['expiry_seconds'] ) ) {
            $expires_at = date( 'Y-m-d H:i:s', time() + intval( $body['expiry_seconds'] ) );
        }

        $wpdb->update( $table, array(
            'instawp_site_id' => $insta_id,
            'demo_url'        => $site_url,
            'auto_login_url'  => $auto_login,
            'status'          => $status,
            'expires_at'      => $expires_at,
        ), array( 'id' => $request_id ) );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT user_name, user_email FROM $table WHERE id=%d", $request_id ), ARRAY_A );
        if ( $row ) {
            $name = $row['user_name'] ?: $row['user_email'];
            $to   = $row['user_email'];
            $subject = __( 'Your demo is ready', 'live-demo-generator' );
            $msg     = sprintf( __( "Hi %s,

Your demo site is ready: %s

One-click login: %s

This demo will expire automatically.
", 'live-demo-generator' ), $name, $site_url, $auto_login );
            wp_mail( $to, $subject, $msg );
        }

        return new WP_REST_Response( array( 'ok' => 1 ), 200 );
    }
}
