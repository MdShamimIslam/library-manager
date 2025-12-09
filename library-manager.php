<?php
/**
 * Plugin Name: Library Manager
 * Description: Manage books in a custom table with a REST API and React admin.
 * Version: 1.0.0
 * Author: Md. Shamim Islam
 * Author URI: https://shamim-v0.netlify.app
 * Text Domain: library-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LM_DB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.0' );
define( 'LM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'LM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );


register_activation_hook( __FILE__, 'lm_activate_plugin' );
register_deactivation_hook( __FILE__, 'lm_deactivate_plugin' );

function lm_activate_plugin() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    global $wpdb;
    $table_name = $wpdb->prefix . 'library_books';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      title VARCHAR(255) NOT NULL,
      description LONGTEXT,
      author VARCHAR(255),
      publication_year INT,
      status ENUM('available','borrowed','unavailable') DEFAULT 'available',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta( $sql );

    add_option( 'lm_db_version', LM_DB_VERSION );
}

function lm_deactivate_plugin() {
    // no destructive actions on deactivation
}

/* Load includes */
require_once LM_PLUGIN_DIR_PATH . 'includes/class-lm-rest.php';
require_once LM_PLUGIN_DIR_PATH . 'includes/class-lm-admin.php';
