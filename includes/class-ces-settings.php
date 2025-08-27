<?php

defined('ABSPATH') || exit;

class CES_Settings {
    private static $instance = null;
    private $option_keys = [
        'blacklist' => 'ces_blacklist_words',
        'preview_limit' => 'ces_preview_page_limit'
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
    }

    public function register_admin_page() {
        add_menu_page(
            'eBook Settings', // Page title
            'eBook Settings', // Menu title
            'manage_options', // Capability
            'ces_settings',  // Menu slug
            [$this, 'render_admin_page'], // Callback function
            'dashicons-book', // Icon
            55 // Position
        );
    }

    public function render_admin_page() {
        if (isset($_POST['ces_save_settings'])) {
            check_admin_referer('ces_settings_nonce');
            
            // Save blacklist
            update_option(
                $this->option_keys['blacklist'], 
                sanitize_textarea_field($_POST['ces_blacklist'])
            );
            
            // Save preview limit
            update_option(
                $this->option_keys['preview_limit'],
                absint($_POST['ces_preview_limit'])
            );
            
            echo '<div class="updated"><p>' . __('Settings saved successfully.', 'ces') . '</p></div>';
        }

        // Get current values
        $blacklist = get_option($this->option_keys['blacklist'], '');
        $preview_limit = get_option($this->option_keys['preview_limit'], 3);
        ?>
        <div class="wrap">
            <h1><?php _e('eBook Settings', 'ces'); ?></h1>
            
            <form method="post" class="ces-settings-form">
                <?php wp_nonce_field('ces_settings_nonce'); ?>
                
                <div class="ces-settings-section">
                    <h2><?php _e('Tag Blacklist', 'ces'); ?></h2>
                    <p class="description"><?php _e('Enter comma-separated tags that should be blocked. These tags will not be allowed when submitting ebooks.', 'ces'); ?></p>
                    <textarea 
                        name="ces_blacklist" 
                        rows="10" 
                        cols="70" 
                        class="large-text code"><?php echo esc_textarea($blacklist); ?></textarea>
                </div>

                <div class="ces-settings-section">
                    <h2><?php _e('Preview Settings', 'ces'); ?></h2>
                    <p class="description"><?php _e('Configure how many pages unpurchased users can preview before being prompted to buy.', 'ces'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ces_preview_limit"><?php _e('Preview Page Limit', 'ces'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="number" 
                                    name="ces_preview_limit" 
                                    id="ces_preview_limit"
                                    value="<?php echo esc_attr($preview_limit); ?>"
                                    min="1"
                                    max="50"
                                    step="1"
                                    class="small-text"
                                >
                                <p class="description"><?php _e('Number of pages users can preview before purchasing.', 'ces'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="ces_save_settings" class="button button-primary">
                        <?php _e('Save Settings', 'ces'); ?>
                    </button>
                </p>
            </form>
        </div>
        <style>
            .ces-settings-section {
                margin: 2em 0;
                padding: 1em;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .ces-settings-section h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }

    public function has_blacklisted_words($tags) {
        $blacklist = get_option($this->option_keys['blacklist'], '');
        if (empty($blacklist)) {
            return false; // No blacklist set
        }
        $blacklist = array_map('trim', explode(",", strtolower($blacklist)));
        $tags      = array_map('trim', explode(",", strtolower($tags)));

        foreach ($tags as $tag) {
            if (in_array($tag, $blacklist)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the preview page limit
     * 
     * @return int Number of pages that can be previewed before purchase
     */
    public function get_preview_limit() {
        return absint(get_option($this->option_keys['preview_limit'], 3));
    }
}
