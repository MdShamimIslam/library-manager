<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LM_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Library Manager',
            'Library Manager',
            'manage_options',
            'lm-library-manager',
            array( $this, 'render_admin_page' ),
            'dashicons-book',
            20
        );
    }

    public function render_admin_page() {
        echo '<div id="lm-root"></div>';
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_lm-library-manager' ) {
            return;
        }

        $index_asset = require LM_PLUGIN_DIR_PATH . 'build/index.asset.php';

        wp_enqueue_style(
            'lm-admin-style',
            LM_PLUGIN_DIR_URL . 'build/index.css',
            [],
            $index_asset['version']
        );

        wp_enqueue_script(
            'lm-admin-app',
            LM_PLUGIN_DIR_URL . 'build/index.js',
            $index_asset[ 'dependencies' ],
            $index_asset[ 'version' ],
            [ 'in_footer' => true ]
        );

        $rest_url = esc_url_raw( rest_url( 'library/v1' ) );
        wp_localize_script( 'lm-admin-app', 'LM_SETTINGS', array(
            'rest_url' => $rest_url,
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ) );
    }
}

new LM_Admin();
