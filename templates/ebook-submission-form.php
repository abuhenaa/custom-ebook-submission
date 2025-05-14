<?php if (isset($_GET['submitted'])): ?>
    <p class="ces-success"><?php _e('Your eBook has been submitted for review!', 'textdomain'); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="ces-form">
    <?php wp_nonce_field('ces_submit_nonce', 'ces_nonce'); ?>

    <div class="ces-grid">
        <div class="ces-field">
            <label for="ces-title"><?php _e('Title', 'textdomain'); ?>:</label>
            <input type="text" name="title" id="ces-title" required />
        </div>

        <div class="ces-field">
            <label for="ces-main-category"><?php _e('Main Category', 'textdomain'); ?>:</label>
            <select name="main_category" id="ces-main-category">
                <?php foreach (ces_get_main_categories() as $cat): ?>
                    <option value="<?= esc_attr($cat->term_id); ?>"><?= esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ces-field">
            <label for="ces-category-suggestion"><?php _e('Suggest a new category (optional)', 'textdomain'); ?>:</label>
            <input type="text" name="category_suggestion" id="ces-category-suggestion" />
        </div>

        <div class="ces-field">
            <label for="ces-tags"><?php _e('Tags (comma-separated)', 'textdomain'); ?>:</label>
            <input type="text" name="tags" id="ces-tags" />
        </div>

        <div class="ces-field">
            <label for="ces-price"><?php _e('Price', 'textdomain'); ?>:</label>
            <input type="number" name="price" step="0.01" id="ces-price" />
        </div>

        <div class="ces-field">
            <label for="ces-sale-price"><?php _e('Sale Price', 'textdomain'); ?>:</label>
            <input type="number" name="sale_price" step="0.01" id="ces-sale-price" />
        </div>

        <div class="ces-field full">
            <label for="ces-ebook-file"><?php _e('Upload File (EPUB, DOCX, CBZ or images)', 'textdomain'); ?>:</label>
            <input type="file" name="ebook_file" id="ces-ebook-file" />
        </div>

        <div class="ces-field full">
            <label for="ces-external-link"><?php _e('External Print Link (optional)', 'textdomain'); ?>:</label>
            <input type="url" name="external_link" id="ces-external-link" />
        </div>
    </div>

    <div class="ces-submit">
        <input type="submit" name="ces_submit_form" value="<?php esc_attr_e('Submit eBook', 'textdomain'); ?>" />
    </div>
</form>
