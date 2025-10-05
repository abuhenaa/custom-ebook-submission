<?php

defined( 'ABSPATH' ) || exit;

class CES_Form_Handler
{
    public function __construct()
    {
        add_action( 'init', [ $this, 'handle_submission' ] );
    }

    public function handle_submission()
    {
        if ( !isset( $_POST[ 'ces_submit_form' ] ) || !is_user_logged_in() ) {
            return;
        }

        check_admin_referer( 'ces_submit_nonce', 'ces_nonce' );

        $settings = CES_Settings::get_instance();
        if ( $settings->has_blacklisted_words( $_POST[ 'tags' ] ?? '' ) ) {
            wp_die( __( 'Blacklisted word detected. Please revise your tags.', 'ces' ) );
        }

        // Handle product creation and metadata
        $product_id = wp_insert_post( [
            'post_title'  => sanitize_text_field( $_POST[ 'title' ] ),
            'post_status' => 'pending',
            'post_type'   => 'product',
            'post_author' => get_current_user_id(),
         ] );

        //making product downloadable and virtual
        if ($product_id) {
            update_post_meta($product_id, '_downloadable', 'yes');
            update_post_meta($product_id, '_virtual', 'yes');
        }
        
         if ($product_id) {
            // Trigger WooCommerce product save action
            do_action('woocommerce_new_product', $product_id,10,2);
            do_action('woocommerce_update_product', $product_id,10,2);
            
            // Clear Dokan caches
            if (function_exists('dokan_clear_product_cache')) {
                dokan_clear_product_cache();
            }
            
            // Trigger Dokan product update hooks$product = wc_get_product($product_id);
            $product = wc_get_product($product_id);
            if ($product) {
                $product_data = $product->get_data(); // Get product data as array
                do_action('dokan_new_product_added', $product_id, $product_data);
            }
        }

        // Code to assign both main category and subcategory to the product
        if (!empty($_POST['main_category']) && !empty($_POST['subcategory'])) {
            wp_set_object_terms($product_id, (int) $_POST['subcategory'], 'product_cat');
        } elseif (!empty($_POST['main_category'])) {
            wp_set_object_terms($product_id, (int) $_POST['main_category'], 'product_cat');
        }
        
        // Handle tags
        if ( !empty( $_POST[ 'tags' ] ) ) {
            $tags = array_map( 'sanitize_text_field', explode( ',', $_POST[ 'tags' ] ) );
            wp_set_object_terms( $product_id, $tags, 'product_tag' );
        }

        //description update
        $description = isset( $_POST[ 'description' ] ) ? wp_kses_post( $_POST[ 'description' ] ) : '';
        wp_update_post( [
            'ID'           => $product_id,
            'post_content' => $description,
        ] );
        //short description update
        $short_description = isset( $_POST[ 'short_description' ] ) ? wp_kses_post( $_POST[ 'short_description' ] ) : '';
        wp_update_post( [
            'ID'                => $product_id,
            'post_excerpt'      => $short_description,
        ] );

        // Save additional meta
        update_post_meta( $product_id, '_ces_subtitle', sanitize_text_field( $_POST[ 'subtitle' ] ?? '' ) );
        update_post_meta( $product_id, '_ces_series', sanitize_text_field( $_POST[ 'series' ] ?? '' ) );
        update_post_meta( $product_id, '_ces_publisher', sanitize_text_field( $_POST[ 'publisher' ] ?? '' ) );
        update_post_meta( $product_id, '_ces_isbn', sanitize_text_field( $_POST[ 'isbn' ] ?? '' ) );
        update_post_meta( $product_id, '_ces_page_number', sanitize_text_field( $_POST[ 'page_number' ] ?? '' ) );
        //publication_date
        $publication_date = sanitize_text_field( $_POST[ 'publication_date' ] ?? '' );
        update_post_meta( $product_id, 'publication_date', $publication_date );

        //update_post_meta( $product_id, '_ces_external_link', esc_url_raw( $_POST[ 'external_link' ] ?? '' ) );
        update_post_meta( $product_id, '_ces_main_category', sanitize_text_field( $_POST[ 'main_category' ] ?? '' ) );
        update_post_meta( $product_id, '_category_suggestion', sanitize_text_field( $_POST[ 'category_suggestion' ] ?? '' ) );

        $regular_price = wc_clean( $_POST[ 'price' ] );
        $sale_price    = !empty( $_POST[ 'sale_price' ] ) ? wc_clean( $_POST[ 'sale_price' ] ) : '';

        update_post_meta( $product_id, '_regular_price', $regular_price );

        if ( $sale_price !== '' ) {
            update_post_meta( $product_id, '_sale_price', $sale_price );
            update_post_meta( $product_id, '_price', $sale_price );
        } else {
            update_post_meta( $product_id, '_sale_price', '' );
            update_post_meta( $product_id, '_price', $regular_price );
        }

        //cover image update
        if ( !empty( $_FILES[ 'cover_image' ][ 'name' ] ) ) {
            //include WordPress file handling functions
            if ( !function_exists( 'wp_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $cover_image = wp_handle_upload( $_FILES[ 'cover_image' ], [ 'test_form' => false ] );
            if ( isset( $cover_image[ 'file' ] ) ) {
                $attachment_id = wp_insert_attachment( [
                    'post_title'     => sanitize_file_name( $_FILES[ 'cover_image' ][ 'name' ] ),
                    'post_mime_type' => $cover_image[ 'type' ],
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                 ], $cover_image[ 'file' ], $product_id );

                // Generate attachment metadata and update the database record
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attach_data = wp_generate_attachment_metadata( $attachment_id, $cover_image[ 'file' ] );
                wp_update_attachment_metadata( $attachment_id, $attach_data );
                set_post_thumbnail( $product_id, $attachment_id );
            }
        }

        // Author handling
        if ( !empty( $_POST[ 'author' ] && $_POST['author'] !== 'new_author' ) ) {
            $author_id = (int) $_POST[ 'author' ];
            wp_set_object_terms( $product_id, $author_id, 'books-author' );
        } elseif ( !empty( $_POST[ 'new_author' ] ) ) {
            $new_author = sanitize_text_field( $_POST[ 'new_author' ] );
            $term_id    = wp_insert_term( $new_author, 'books-author' );
            if ( !is_wp_error( $term_id ) ) {
                wp_set_object_terms( $product_id, $term_id[ 'term_id' ], 'books-author' );
            }
        }

        // File Handling
        $file_type    = sanitize_text_field( $_POST[ 'file_type' ] ?? '' );
        
        // update converted file URL if docx to epub conversion is done
        $docx_to_epub_file_url = isset( $_POST[ '_ces_ebook_file' ] ) ? esc_url_raw( $_POST[ '_ces_ebook_file' ] ) : '';
        if( $file_type == 'docx' ){
            update_post_meta( $product_id, '_ces_ebook_file', $docx_to_epub_file_url );
        }

        // add the downloadable file for docx to epub conversion
        if( $file_type == 'docx' && !empty( $docx_to_epub_file_url ) ){
            $downloadable_files = [
                "ebook_file_url" => [
                    'name' => sanitize_file_name( basename( $docx_to_epub_file_url ) ),
                    'file' => $docx_to_epub_file_url,
                ],
            ];
            update_post_meta( $product_id, '_downloadable_files', $downloadable_files );
        }

        $file_handler = new CES_File_Handler( $product_id );
        $file_handler->handle_upload( $_FILES, $file_type );

        // Change this line in CES_Form_Handler class
        wp_redirect(add_query_arg(['submitted' => 'true', 'product_id' => $product_id, 'ces_file_type' => $file_type ], get_permalink()));
        exit;
    }
}
