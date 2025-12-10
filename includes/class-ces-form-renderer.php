<?php
//direct access
if (!defined('ABSPATH')) {
    exit;
}

//Form render class
class CES_Form_Renderer {

    public function __construct() {
        add_shortcode( 'ebook_submission_form', [$this, 'render_form']);
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    //enqueue assets
    public function enqueue_assets() {
        wp_enqueue_style('ces-style', CES_PLUGIN_URL . 'assets/css/ces-style.css', [], CES_PLUGIN_VERSION);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');
        //select 2

        wp_enqueue_script('ces-form', CES_PLUGIN_URL . 'assets/js/ces-scripts.js', ['jquery'], CES_PLUGIN_VERSION, true);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('ces-form-script', CES_PLUGIN_URL . '/assets/js/ces-form.js', array('jquery', 'jquery-ui-sortable'), CES_PLUGIN_VERSION, true);
        //select2
        wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', 'jquery', '4.1.0', false);
        wp_localize_script('ces-form-script', 'ces_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ces_nonce'),
            'strings'  => array(
                'please_upload_image_ratio' => __('Please upload an image with a 2:3 aspect ratio', 'ces'),
                'invalid_image_file' => __('Invalid image file.', 'ces'),
                'preview_ebook' => __('Preview Ebook', 'ces'),
                'convert_into_epub' => __('Convert Into EPUB', 'ces'),
                'search_for_author' => __('Search for an author...', 'ces'),
                'add_new_author' => __('Add New Author', 'ces'),
                'select_main_category_first' => __('Select a main category first', 'ces'),
                'loading' => __('Loading...', 'ces'),
                'select_subcategory' => __('Select a subcategory', 'ces'),
                'no_subcategories_available' => __('No subcategories available', 'ces'),
                'error_loading_subcategories' => __('Error loading subcategories', 'ces'),
                'please_select_epub_file' => __('Please select an EPUB file first', 'ces'),
                'please_select_cbz_file' => __('Please select a CBZ file first', 'ces'),
                'unsupported_file_type' => __('Unsupported file type for preview', 'ces'),
                'please_select_docx_file' => __('Please select a DOCX file first.', 'ces'),
                'please_select_buy_location' => __('Please select where you want to buy the paperbook.', 'ces'),
                'please_select_valid_docx_file' => __('Please select a valid DOCX file.', 'ces'),
                'converting' => __('Converting...', 'ces'),
                'conversion_failed' => __('Conversion failed', 'ces'),
                'error_occurred_during_conversion' => __('An error occurred during conversion. Please try again.', 'ces'),
                'preview_not_available' => __('Preview is not available', 'ces'),
                'no_images_to_preview' => __('No images to preview. Please add some images first.', 'ces'),
                'no_valid_images_found' => __('No valid images found to preview.', 'ces'),
                'price_restriction' => __('Price can not be less than 0.99', 'ces'),
                'positive_price' => __('Please enter a valid positive price.', 'ces'),
                'decimal_price' => __('Please enter a valid price with no more than 2 decimal places.', 'ces'),
                'too_many_tags' => __('You can enter a maximum of 20 tags.', 'ces'),
            )
        ));
    }

    public function render_form( $atts = [] ) {

        $atts = shortcode_atts( [
            'new_product' => 'yes',
        ], $atts, 'ebook_submission_form' );

        

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to submit an eBook.', 'ces') . '</p>';
        }

        ob_start();
        ?>
        <div class="ces-form-container">           

            <?php
                $new_product = $atts['new_product'] === 'yes' ? true : false;
                include CES_PLUGIN_DIR . 'templates/ebook-submission-form.php';
            ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
}
