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
            <label for="ces-subtitle"><?php _e('Subtitle (optional)', 'textdomain'); ?>:</label>
            <input type="text" name="subtitle" id="ces-subtitle" />
        </div>
        <div class="ces-field">
            <label for="ces-series"><?php _e('Series (optional)', 'textdomain'); ?>:</label>
            <input type="text" name="series" id="ces-series" />
        </div>
        <div class="ces-field">
            <label for="ces-cover-image"><?php _e('Cover Image', 'textdomain'); ?>:</label>
            <input type="file" name="cover_image" id="ces-cover-image" accept="image/*" />
            <div id="cover-image-preview" class="image-preview"></div>
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
            <label for="ces-price"><?php _e('Price Without VAT', 'textdomain'); ?>:</label>
            <input type="number" name="price" step="0.01" id="ces-price" />
        </div>

        <div class="ces-field">
            <label for="ces-vat-price"><?php _e('Price With VAT', 'textdomain'); ?>:</label>
            <input type="text" name="vat_price" id="ces-vat-price" readonly/>
        </div>
        <div class="ces-field">
            <label for="ces-file-type"><?php _e('File Type', 'textdomain'); ?>:</label>
            <select name="file_type" id="ces-file-type">
                <option value="epub">EPUB</option>
                <option value="docx">DOCX</option>
                <option value="cbz">CBZ</option>
                <option value="comic_images">JPG/PNG Images</option>
            </select>
        </div>

        <!-- Dynamic file upload fields - will be shown/hidden based on file type selection -->
        <div class="ces-field full file-upload-field" id="epub-upload-field">
            <label for="ces-epub-file"><?php _e('Upload EPUB File', 'textdomain'); ?>:</label>
            <input type="file" name="epub_file" id="ces-epub-file" accept=".epub" />
        </div>

        <div class="ces-field full file-upload-field" id="docx-upload-field" style="display:none;">
            <label for="ces-docx-file"><?php _e('Upload DOCX File', 'textdomain'); ?>:</label>
            <input type="file" name="docx_file" id="ces-docx-file" accept=".docx" />
        </div>

        <div class="ces-field full file-upload-field" id="cbz-upload-field" style="display:none;">
            <label for="ces-cbz-file"><?php _e('Upload CBZ File', 'textdomain'); ?>:</label>
            <input type="file" name="cbz_file" id="ces-cbz-file" accept=".cbz" />
        </div>

        <div class="ces-field full file-upload-field" id="comic-images-upload-field" style="display:none;">
            <label for="ces-comic-images"><?php _e('Upload Comic Images', 'textdomain'); ?>:</label>
            <input type="file" name="comic_images[]" id="ces-comic-images" accept="image/*" multiple />
            <div class="comic-image-instructions"><?php _e('Upload multiple images that will be converted to CBZ format. You can drag and drop to reorder them.', 'textdomain'); ?></div>
            <div id="comic-images-preview" class="comic-images-sortable"></div>
            <input type="hidden" name="comic_images_order" id="comic-images-order" />
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

