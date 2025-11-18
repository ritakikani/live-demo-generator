<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LDG_Generator_Shortcodes {

    /** @var self */
    private static $instance = null;

    private function __construct() {
        add_shortcode( 'live_demo_form', array( $this, 'render_form' ) );
    }

    /**
     * @return self
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_form( $atts ) {
        $builder = LDG_Generator::instance();
        $dep_map = $builder->get_dep_map();

        $script_url = plugins_url( 'assets/frontend.js', dirname( __FILE__ ) . '/live-demo-generator.php' );

        wp_enqueue_script( 'live-demo-generator-frontend', $script_url, array( 'jquery' ), '1.0', true );
        wp_localize_script( 'live-demo-generator-frontend', 'LDG_DEMO', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'live_demo_nonce' ),
        ) );

        ob_start();
        $dep_map_local = $dep_map;
        include dirname( __DIR__ ) . '/templates/demo-form.php';
        return ob_get_clean();
    }
}
