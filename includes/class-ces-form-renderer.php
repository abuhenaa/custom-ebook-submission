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
        wp_enqueue_style('ces-style', CES_PLUGIN_URL . 'assets/css/ces-style.css');
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');
        //select 2

        wp_enqueue_script('ces-form', CES_PLUGIN_URL . 'assets/js/ces-scripts.js', ['jquery'], null, true);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('ces-form-script', CES_PLUGIN_URL . '/assets/js/ces-form.js', array('jquery', 'jquery-ui-sortable'), '1.0.0', true);
        //select2
        wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', 'jquery', '4.1.0', false);
        wp_localize_script('ces-form-script', 'ces_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ces_nonce')
        ));
    }

    public function render_form() {

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to submit an eBook.', 'ces') . '</p>';
        }

        ob_start();
        ?>
        <div class="ces-form-container">           

            <?php
                include CES_PLUGIN_DIR . 'templates/ebook-submission-form.php';
            ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
}
