<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LM_REST {
    private $namespace = 'library/v1';
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'library_books';
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/books', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_books' ),
                'args'     => array(
                    'status' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'author' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'year'   => array( 'sanitize_callback' => 'absint' ),
                    'page'   => array( 'sanitize_callback' => 'absint' ),
                    'per_page' => array( 'sanitize_callback' => 'absint' ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_book' ),
                'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
                'args' => $this->get_post_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/books/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_book' ),
                'args' => array(
                    'id' => array( 'validate_callback' => 'is_numeric' ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array( $this, 'update_book' ),
                'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
                'args' => $this->get_post_args(),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_book' ),
                'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
            ),
        ) );
    }

    private function get_post_args() {
        return array(
            'title' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'sanitize_callback' => 'wp_kses_post',
            ),
            'author' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'publication_year' => array(
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    public function get_books( $request ) {
        global $wpdb;
        $params = $request->get_params();
        $where = array();
        $values = array();

        if ( ! empty( $params['status'] ) ) {
            $where[] = "status = %s";
            $values[] = $params['status'];
        }
        if ( ! empty( $params['author'] ) ) {
            $where[] = "author = %s";
            $values[] = $params['author'];
        }
        if ( ! empty( $params['year'] ) ) {
            $where[] = "publication_year = %d";
            $values[] = $params['year'];
        }

        $sql = "SELECT * FROM {$this->table}";
        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        // pagination
        $page = isset( $params['page'] ) && $params['page'] > 0 ? (int) $params['page'] : 1;
        $per_page = isset( $params['per_page'] ) && $params['per_page'] > 0 ? (int) $params['per_page'] : 50;
        $offset = ( $page - 1 ) * $per_page;
        $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );

        $prepared = $wpdb->prepare( $sql, $values );
        $results = $wpdb->get_results( $prepared, ARRAY_A );

        if ( $results === null ) {
            return new WP_Error( 'db_error', 'Database error', array( 'status' => 500 ) );
        }

        // sanitize output
        $sanitized = array_map( array( $this, 'sanitize_book_output' ), $results );

        return rest_ensure_response( $sanitized );
    }

    public function get_book( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $sql = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id );
        $book = $wpdb->get_row( $sql, ARRAY_A );

        if ( ! $book ) {
            return new WP_Error( 'not_found', 'Book not found', array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->sanitize_book_output( $book ) );
    }

    public function create_book( $request ) {
        global $wpdb;
        $params = $request->get_params();

        // validation
        if ( empty( $params['title'] ) ) {
            return new WP_Error( 'missing_title', 'Title is required', array( 'status' => 422 ) );
        }
        $status = isset( $params['status'] ) ? $params['status'] : 'available';
        if ( ! in_array( $status, array( 'available', 'borrowed', 'unavailable' ), true ) ) {
            return new WP_Error( 'invalid_status', 'Invalid status', array( 'status' => 422 ) );
        }

        $inserted = $wpdb->insert(
            $this->table,
            array(
                'title' => $params['title'],
                'description' => isset( $params['description'] ) ? $params['description'] : '',
                'author' => isset( $params['author'] ) ? $params['author'] : '',
                'publication_year' => isset( $params['publication_year'] ) ? (int) $params['publication_year'] : null,
                'status' => $status,
            ),
            array( '%s', '%s', '%s', '%d', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_insert_error', 'Could not insert book', array( 'status' => 500 ) );
        }

        $id = (int) $wpdb->insert_id;
        $sql = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id );
        $book = $wpdb->get_row( $sql, ARRAY_A );

        return new WP_REST_Response( $this->sanitize_book_output( $book ), 201 );
    }

    public function update_book( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $sql = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id );
        $existing = $wpdb->get_row( $sql, ARRAY_A );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', 'Book not found', array( 'status' => 404 ) );
        }

        $params = $request->get_params();
        $data = array();
        $format = array();

        if ( isset( $params['title'] ) ) {
            $data['title'] = $params['title']; $format[] = '%s';
        }
        if ( isset( $params['description'] ) ) {
            $data['description'] = $params['description']; $format[] = '%s';
        }
        if ( isset( $params['author'] ) ) {
            $data['author'] = $params['author']; $format[] = '%s';
        }
        if ( isset( $params['publication_year'] ) ) {
            $data['publication_year'] = (int) $params['publication_year']; $format[] = '%d';
        }
        if ( isset( $params['status'] ) ) {
            if ( ! in_array( $params['status'], array( 'available', 'borrowed', 'unavailable' ), true ) ) {
                return new WP_Error( 'invalid_status', 'Invalid status', array( 'status' => 422 ) );
            }
            $data['status'] = $params['status']; $format[] = '%s';
        }

        if ( empty( $data ) ) {
            return rest_ensure_response( $this->sanitize_book_output( $existing ) );
        }

        $where = array( 'id' => $id );
        $where_format = array( '%d' );

        $updated = $wpdb->update( $this->table, $data, $where, $format, $where_format );

        if ( $updated === false ) {
            return new WP_Error( 'db_update_error', 'Could not update book', array( 'status' => 500 ) );
        }

        $sql = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id );
        $book = $wpdb->get_row( $sql, ARRAY_A );

        return rest_ensure_response( $this->sanitize_book_output( $book ) );
    }

    public function delete_book( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $deleted = $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );

        if ( $deleted === false ) {
            return new WP_Error( 'db_delete_error', 'Could not delete book', array( 'status' => 500 ) );
        }

        if ( $deleted === 0 ) {
            return new WP_Error( 'not_found', 'Book not found', array( 'status' => 404 ) );
        }

        return rest_ensure_response( null );
    }

    private function sanitize_book_output( $book ) {
        return array(
            'id' => (int) $book['id'],
            'title' => wp_kses_post( $book['title'] ),
            'description' => wp_kses_post( $book['description'] ),
            'author' => esc_html( $book['author'] ),
            'publication_year' => isset( $book['publication_year'] ) ? (int) $book['publication_year'] : null,
            'status' => esc_html( $book['status'] ),
            'created_at' => $book['created_at'],
            'updated_at' => $book['updated_at'],
        );
    }
}

new LM_REST();
