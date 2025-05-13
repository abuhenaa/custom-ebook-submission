<?php if (isset($_GET['submitted'])): ?>
    <p class="ces-success">Your eBook has been submitted for review!</p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('ces_submit_nonce', 'ces_nonce'); ?>
    
    <label>Title:</label>
    <input type="text" name="title" required />

    <label>Main Category:</label>
    <select name="main_category">
        <?php foreach (ces_get_main_categories() as $cat): ?>
            <option value="<?= esc_attr($cat->term_id); ?>"><?= esc_html($cat->name); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Tags (comma-separated):</label>
    <input type="text" name="tags" />

    <label>Upload File (EPUB, DOCX, CBZ or images):</label>
    <input type="file" name="ebook_file" />

    <label>External Print Link (optional):</label>
    <input type="url" name="external_link" />

    <input type="submit" name="ces_submit_form" value="Submit eBook" />
</form>
