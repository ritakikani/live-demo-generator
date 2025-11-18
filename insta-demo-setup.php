<?php
// insta-demo-setup.php - drop into your InstaWP snapshot root.
// This script creates a demo user using site options passed via InstaWP (site_meta or variables).
// Adjust based on how InstaWP supplies initial variables.
if (!defined('WPINC')) {
    require_once __DIR__ . '/wp-load.php';
}

function ldg_create_demo_user_from_site_options(){
    // The snapshot should be created with site options like demo_request_email and demo_request_role.
    $demo_email = get_option('demo_request_email', '');
    $demo_role = get_option('demo_request_role', 'administrator');

    if (empty($demo_email)) {
        // fallback to a generated email
        $demo_email = 'demo+' . wp_generate_password(6,false) . '@example.com';
    }

    if (email_exists($demo_email)) return;
    $username = sanitize_user( preg_replace('/[^a-z0-9]/i','', strstr($demo_email,'@', true) ) );
    if (username_exists($username)) $username .= '_' . wp_generate_password(4,false);

    $pw = wp_generate_password(12, false);
    $user_id = wp_create_user($username, $pw, $demo_email);
    if (!is_wp_error($user_id)) {
        $user = new WP_User($user_id);
        $user->set_role($demo_role);
        update_option('demo_created_user', [
            'user_id' => $user_id,
            'username' => $username,
            'password' => $pw,
            'email' => $demo_email,
            'role' => $demo_role
        ]);
    }
}
ldg_create_demo_user_from_site_options();
