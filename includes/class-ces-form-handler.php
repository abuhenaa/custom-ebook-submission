<?php

defined('ABSPATH') || exit;

class CES_Form_Handler {
    public function __construct() {
        add_action('init', [$this, 'handle_submission']);
    }

    public function handle_submission() {
        if (!isset($_POST['ces_submit_form']) || !is_user_logged_in()) return;

        check_admin_referer('ces_submit_nonce', 'ces_nonce');

        $blacklist = new CES_Tag_Blacklist();
        if ($blacklist->has_blacklisted_words($_POST['tags'] ?? '')) {
            wp_die(__('Blacklisted word detected. Please revise your tags.', 'ces'));
        }

        // Handle product creation and metadata
        $product_id = wp_insert_post([
            'post_title'   => sanitize_text_field($_POST['title']),
            'post_status'  => 'pending',
            'post_type'    => 'product',
            'post_author'  => get_current_user_id(),
        ]);

        // Save additional meta
        update_post_meta($product_id, '_ces_external_link', esc_url_raw($_POST['external_link'] ?? ''));
        update_post_meta($product_id, '_ces_main_category', sanitize_text_field($_POST['main_category'] ?? ''));

        // File Handling
        $file_handler = new CES_File_Handler($product_id);
        $file_handler->handle_upload($_FILES);

        wp_redirect(add_query_arg('submitted', 'true', get_permalink()));
        exit;
    }
}
