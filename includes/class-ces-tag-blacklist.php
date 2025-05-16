<?php

defined('ABSPATH') || exit;

class CES_Tag_Blacklist {
    private $option_key = 'ces_blacklist_words';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
    }

    public function register_admin_page() {
        add_menu_page('Blacklist Tags', 'eBook Settings', 'manage_options', 'ces_blacklist', [$this, 'render_admin_page']);
    }

    public function render_admin_page() {
        if (isset($_POST['ces_save_blacklist'])) {
            check_admin_referer('ces_blacklist_nonce');
            update_option($this->option_key, sanitize_textarea_field($_POST['ces_blacklist']));
            echo '<div class="updated"><p>Saved.</p></div>';
        }

        $blacklist = get_option($this->option_key, '');
        var_dump($blacklist); // Debugging line, remove in production
        ?>
        <div class="wrap">
            <h1>eBook Tag Blacklist</h1>
            <form method="post">
                <?php wp_nonce_field('ces_blacklist_nonce'); ?>
                <textarea name="ces_blacklist" rows="10" cols="70"><?php echo esc_textarea($blacklist); ?></textarea><br>
                <button type="submit" name="ces_save_blacklist" class="button-primary">Save Blacklist</button>
            </form>
        </div>
        <?php
    }

    public function has_blacklisted_words($tags) {
        $blacklist = explode(",", get_option($this->option_key, ''));
        $tags = explode(',', $tags);
        foreach ($tags as $tag) {
            if (in_array(trim(strtolower($tag)), array_map('strtolower', array_map('trim', $blacklist)))) {
                return true;
            }
        }
        return false;
    }
}
