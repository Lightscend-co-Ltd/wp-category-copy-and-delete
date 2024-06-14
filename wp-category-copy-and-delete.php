<?php
/*
* Plugin Name:       WP Category Copy And Delete
* Description:       This is a plugin that bulk copies/deletes all terms and custom fields (including ACF) between WordPress custom taxonomies. It features parent-child copies of terms and preserves ACF field settings.
* Version:           1.0.0
* Author:            Gayo
* Text Domain:       wpccad
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WCCAD_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WpCategoryCopyAndDelete {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_admin_pages() {
        add_submenu_page(
            'tools.php', // parent slug
            'タクソノミーコピーと削除', // page title
            'タクソノミーコピーと削除', // menu title
            'manage_options', // capability
            'wp-category-copy-and-delete', // menu slug
            array( $this, 'display_admin_page' ) // callable function
        );
    }

    public function display_admin_page() {
        // Load the template file here...
        include WCCAD_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function handle_form_submission() {
        if ( isset( $_POST['copy_taxonomy'] ) ) {
            $this->copy_all_taxonomy_data();
        }
        if ( isset( $_POST['delete_taxonomy'] ) ) {
            $this->remove_all_terms_and_fields();
        }
    }

    public function enqueue_admin_assets() {
        // Enqueue CSS and JS here...
        wp_enqueue_style( 'my-plugin-css', WCCAD_PLUGIN_DIR . '/assets/css/main.css' );
        wp_enqueue_script( 'my-plugin-js', WCCAD_PLUGIN_DIR . '/assets/js/main.js', array( 'jquery' ) );
    }



    function copy_all_taxonomy_data() {
        if ( isset( $_POST['source_taxonomy'] ) && isset( $_POST['destination_taxonomy'] ) ) {
            $source_taxonomy = sanitize_text_field( $_POST['source_taxonomy'] );
            $destination_taxonomy = sanitize_text_field( $_POST['destination_taxonomy'] );

            $terms = get_terms( array(
                'taxonomy' => $source_taxonomy,
                'hide_empty' => false,
                'parent' => 0,
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                foreach ( $terms as $term ) {
                    $this->copy_term( $term, $source_taxonomy, $destination_taxonomy );
                }
            }

            add_action( 'admin_notices', array($this, 'copy_taxonomy_data_success' ));
        }
    }

    function copy_taxonomy_data_success() {
        ?>
        <div class="notice notice-success is-dismissible">
        <p><?php _e( 'カスタムタクソノミーのデータをコピーしました！', 'textdomain' ); ?></p>
    </div>
        <?php
    }

    function copy_term( $term, $source_taxonomy, $destination_taxonomy, $parent_id = 0 ) {
        // Copy the term to the new taxonomy
        $new_term = wp_insert_term(
            $term->name, // the term
            $destination_taxonomy, // the taxonomy
            array(
                'description' => $term->description,
                'slug' => $term->slug,
                'parent' => $parent_id // set parent term
            )
        );

        // Check if there was an error
        if ( is_wp_error( $new_term ) ) {
            return;
        }

        if ( function_exists( 'get_fields' ) ) {
            // Get all the ACF fields
            $fields = get_fields( $term );
            if ( $fields ) {
                foreach ( $fields as $field_name => $value ) {
                    // Get the field object to get its settings
                    $field_object = get_field_object( $field_name, $term );

                    // Copy the field value and settings to the new taxonomy
                    update_field( $field_object['key'], $value, $destination_taxonomy . '_' . $new_term['term_id'] );
                }
            }
        }

        // Get all the non-ACF custom fields for the term
        $meta = get_term_meta( $term->term_id );

        if ( $meta ) {
            foreach ( $meta as $key => $value ) {
                // Skip ACF fields
                if ( strpos( $key, 'field_' ) === 0 ) {
                    continue;
                }

                // Add the field to the new term
                update_term_meta( $new_term['term_id'], $key, maybe_unserialize( $value[0] ) );
            }
        }

        // Get the children of the current term
        $children = get_terms( array(
            'taxonomy' => $source_taxonomy,
            'hide_empty' => false,
            'parent' => $term->term_id // get only children of current term
        ) );

        if ( ! empty( $children ) && ! is_wp_error( $children ) ){
            foreach ( $children as $child ) {
                $this->copy_term( $child, $source_taxonomy, $destination_taxonomy, $new_term['term_id'] ); // pass the new term id as parent id
            }
        }
    }

    function remove_all_terms_and_fields() {
        if ( isset( $_POST['taxonomy_to_remove'] ) ) {
            $taxonomy_to_remove = sanitize_text_field( $_POST['taxonomy_to_remove'] );

            $terms = get_terms( array(
                'taxonomy' => $taxonomy_to_remove,
                'hide_empty' => false,
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                foreach ( $terms as $term ) {
                    $this->remove_term_and_fields( $term, $taxonomy_to_remove );
                }
            }

            add_action( 'admin_notices', array($this, 'remove_terms_and_fields_success') );
        }
    }

    function remove_terms_and_fields_success() {
        ?>
        <div class="notice notice-success is-dismissible">
        <p><?php _e( 'タクソノミーから全タームとそのフィールドを削除しました！', 'textdomain' ); ?></p>
    </div>
        <?php
    }

    function remove_term_and_fields( $term, $taxonomy ) {
        // Check if ACF is active
        if ( function_exists( 'get_fields' ) ) {
            // Get all the ACF fields
            $fields = get_fields( $term );
            if ( $fields ) {
                foreach ( $fields as $field_name => $value ) {
                    // Delete the field value from the term
                    delete_field( $field_name, $taxonomy . '_' . $term->term_id );
                }
            }
        }

        // Delete the term
        wp_delete_term( $term->term_id, $taxonomy );
    }
}
$wccad = new WpCategoryCopyAndDelete();