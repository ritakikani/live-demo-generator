<?php
/**
 * Plugin Name: Live Demo Generator
 * Description: Create InstaWP sandbox demos with dynamic add-on installation, dependency resolution, and timed expiry. Install this plugin on your main/marketing WordPress site (Kinsta).
 * Version: 1.0.0
 * Author: Rita Kikani
 * Author URI: https://profiles.wordpress.org/kikanirita/
 * License: GPL2+
 * Text Domain: live-demo-generator
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('LDG_Generator')) {

    class LDG_Generator {

        const DB_TABLE = 'demo_users';
        const OPTION_API_KEY       = 'live_demo_generator_api_key';
        const OPTION_TEMPLATE_ID   = 'live_demo_generator_template_id';
        const OPTION_DEP_MAP       = 'live_demo_generator_dep_map';
        const OPTION_DELETE_DATA   = 'live_demo_generator_delete_data';

        private static $instance;
        private $dep_map = [];

        /**
         * Get singleton instance
         * @return LDG_Generator
         * @since 1.0.0
         */
        public static function instance() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor
         * @since 1.0.0
         */
        private function __construct() {
            $this->load_dep_map();

            // Activation / uninstall handled by install class.
            register_activation_hook(__FILE__, ['LDG_Generator_Install', 'activate']);
            register_uninstall_hook(__FILE__, ['LDG_Generator_Install', 'uninstall']);
            require_once plugin_dir_path(__FILE__) . 'includes/live-demo-generator-install.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-live-demo-generator-instawp-api.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-live-demo-generator-ajax.php';
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
            if ( is_admin() ) {
                require_once plugin_dir_path(__FILE__) . 'admin/live-demo-generator-settings.php';
                require_once plugin_dir_path(__FILE__) . 'admin/live-demo-generator-admin.php';
                new LDG_Generator_Admin($this);
            } else {
                require_once plugin_dir_path(__FILE__) . 'shortcodes/live-demo-generator-shortcodes.php';
                LDG_Generator_Shortcodes::instance();
            }

            LDG_Generator_Ajax::init();

            add_action('rest_api_init', ['LDG_Generator_Instawp_API', 'register_routes']);
        }

        /**
         * Load dependency map from options
         * @since 1.0.0
         */
        private function load_dep_map() {
            // Load dependency map from option if set, otherwise default example map.
            $map = get_option(self::OPTION_DEP_MAP);
            if ($map && is_array($map)) {
                $this->dep_map = $map;
                return;
            }
            // Default stub map - replace with your full 30+ mapping in admin settings.
            
        }

        public function get_dep_map() {
            return $this->dep_map;
        }

        private function resolve_dependencies($selected) {
            $map = $this->dep_map;
            $stack = $selected;
            $result = [];
            while (!empty($stack)) {
                $p = array_pop($stack);
                if (in_array($p, $result)) continue;
                if (!isset($map[$p])) {
                    $result[] = $p;
                    continue;
                }
                $result[] = $p;
                foreach ($map[$p] as $d) {
                    if (!in_array($d, $result)) $stack[] = $d;
                }
            }
            // ensure core present
            // uniqueness
            return array_values(array_unique($result));
        }

        /**
         * Load plugin text domain for translations.
         */
        public function load_textdomain() {
            load_plugin_textdomain(
                'live-demo-generator',
                false,
                dirname( plugin_basename( __FILE__ ) ) . '/languages/'
            );
        }

    }

    LDG_Generator::instance();
}
