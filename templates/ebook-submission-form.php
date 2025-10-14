<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if( isset($_GET['print_book_added']) && $_GET['print_book_added'] == 'true' ) {
    echo '<div class="ces-success-message woocommerce-message">';
    echo '<h2>' . __('Print Book Link Added Successfully', 'ces') . '</h2>';
    echo '<p>' . __('Thank you for adding your print book links.', 'ces') . '</p>';
    echo '</div>';
    return;
}

if (isset($_GET['submitted']) && isset($_GET['product_id'])): 
    $product_id = intval($_GET['product_id']);
    $product = wc_get_product($product_id);
    
    // Only proceed if we have a valid product
    if ($product): 
        // Get product data
        $title = $product->get_title();
        $price = $product->get_price_html();
        $thumbnail_id = $product->get_image_id();
        
        // Get custom meta data
        $subtitle = get_post_meta($product_id, '_ces_subtitle', true);
        $publisher = get_post_meta($product_id, '_ces_publisher', true);
        
        // Get author
        $author_terms = wp_get_object_terms($product_id, 'books-author');
        $author_name = !empty($author_terms) ? $author_terms[0]->name : '';
    ?>
    <div class="ces-success-message woocommerce-message">
        <h2><?php _e('Submission Successful', 'ces'); ?></h2>
        <p><?php _e('Thank you for submitting your eBook. It will be reviewed shortly.', 'ces'); ?></p>
        
        <!-- Optional Print Book Section -->
        <div class="ces-print-book-section">
            <h3><?php _e('Would you like to sell your printed book?', 'ces'); ?></h3>
            <p><?php _e('Please add links where readers can purchase your printed book.', 'ces'); ?></p>
            
            <div class="ces-ebook-details">
                <?php if ($thumbnail_id): ?>
                    <div class="ces-ebook-cover">
                        <?php echo wp_get_attachment_image($thumbnail_id, 'medium'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="ces-ebook-info">
                    <h4><?php echo esc_html($title); ?></h4>
                    <?php if (!empty($subtitle)): ?>
                        <p class="ces-subtitle"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($author_name)): ?>
                        <p><strong><?php _e('Author:', 'ces'); ?></strong> <?php echo esc_html($author_name); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($publisher)): ?>
                        <p><strong><?php _e('Publisher:', 'ces'); ?></strong> <?php echo esc_html($publisher); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <form id="ces-print-book-form" method="post" action="">
                <?php wp_nonce_field('ces_print_book_action', 'ces_print_book_nonce'); ?>
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                <input type="hidden" id="ces-file-type" name="ces_file_type" value="<?php echo esc_attr($_GET['ces_file_type']); ?>">
                
                <div class="form-field">
                    <label for="personal_website_link"><?php _e('Link to your website or where the printed book is available (optional):', 'ces'); ?></label>
                    <input type="url" name="personal_website_link" id="personal_website_link">
                </div>
                
                <div class="form-field">
                    <label for="bookstore_link"><?php _e('Link to an independent bookstore (optional):', 'ces'); ?></label>
                    <input type="url" name="bookstore_link" id="bookstore_link">
                    <span class="description"><?php _e('If provided, your book will be marked with "Supports bookstores" badge', 'ces'); ?></span>
                </div>
                <div class="form-field">
                    <label for="paperbook_price"><?php _e('Price of the printed book:', 'ces'); ?> <span class="ces-required">*</span></label>
                    <input type="number" name="paperbook_price" id="paperbook_price" step="0.01" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="submit_print_book" class="button button-primary"><?php _e('Add Print Book', 'ces'); ?></button>
                    <!-- <a href="<?php //echo esc_url(home_url()); ?>" class="button"><?php //_e('Skip this step', 'ces'); ?></a> -->
                </div>
               
            </form>
        </div>
    </div>
<?php 
    else: 
?>
    <div class="ces-success-message woocommerce-message">
        <h2><?php _e('Submission Successful', 'ces'); ?></h2>
        <p><?php _e('Thank you for submitting your eBook. It will be reviewed shortly.', 'ces'); ?></p>
    </div>
<?php 
    endif; 
    return;
endif; ?>

<div class="ces-form-header">
    <h2><?php _e('Submit Your eBook', 'ces'); ?></h2>
    <p><?php _e('Please fill out the form below to submit your eBook for review.', 'ces'); ?></p>
</div>
<form method="post" enctype="multipart/form-data" class="ces-form">
    <?php wp_nonce_field('ces_submit_nonce', 'ces_nonce'); ?>

    <div class="ces-grid">
        <div class="ces-field">
            <label for="ces-title"><?php _e('Title', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="text" name="title" id="ces-title" required />
        </div>
        <div class="ces-field">
            <label for="ces-subtitle"><?php _e('Subtitle (optional)', 'ces'); ?>:</label>
            <input type="text" name="subtitle" id="ces-subtitle" />
        </div>
        <div class="ces-field">
            <label for="ces-description"><?php _e('Description', 'ces'); ?>: <span class="ces-required">*</span></label>
            <textarea name="description" id="ces-description" rows="5" required></textarea>
        </div>
        <div class="ces-field">
            <label for="ces-short-description"><?php _e('Short Description', 'ces'); ?>:</label>
            <textarea name="short_description" id="ces-short-description" rows="3"></textarea>
        </div>
        <div class="ces-field">
            <label for="ces-series"><?php _e('Series (optional)', 'ces'); ?>:</label>
            <input type="text" name="series" id="ces-series" />
        </div>
        <div class="ces-field">
            <label for="ces-publisher"><?php _e('Publisher (optional)', 'ces'); ?>:</label>
            <input type="text" name="publisher" id="ces-publisher" />
        </div>
        <div class="ces-field">
            <label for="ces-publication-date"><?php _e('Publication Date', 'ces'); ?>: <span class="ces-required">*</span> </label>
            <input type="date" name="publication_date" id="ces-publication-date" required />
        </div>
        <div class="ces-field">
            <label for="ces-isbn"><?php _e('ISBN (optional)', 'ces'); ?>:</label>
            <input type="text" name="isbn" id="ces-isbn" />
        </div>
        <div class="ces-field">
            <label for="ces-page-number"><?php _e('Page Number', 'ces'); ?>:<span class="ces-required">*</span></label>
            <input type="number" name="page_number" id="ces-page-number" required />
        </div>

        <!-- Author selection -->
        <div class="ces-field">
            <label for="ces-author"><?php _e('Author', 'ces'); ?>:</label>
            <select name="author" id="ces-author" required>
                <option value=""><?php _e('Select Author', 'ces'); ?></option>                
                <option value="new_author"><?php _e('Add New Author', 'ces'); ?></option>
                
                <?php foreach (ces_get_authors() as $author): ?>
                    <option value="<?= esc_attr($author->term_id); ?>"><?= esc_html($author->name); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="author-notice"><?php echo esc_html__( 'If not found your author select Add new author','ces'); ?></span>
            <!-- Author name input -->
            <div class="ces-field" id="new-author-field" style="display:none;">
                <label for="ces-new-author"><?php _e('New Author Name', 'ces'); ?>:</label>
                <input type="text" name="new_author" id="ces-new-author" />
                <span class="author-notice"><?php echo esc_html__( 'Please enter the author name','ces'); ?></span>
            </div> 
        </div>

               

        <div class="ces-field">
            <label for="ces-main-category"><?php _e('Main Category', 'ces'); ?>: <span class="ces-required">*</span></label>
            <select name="main_category" id="ces-main-category" required>
                <option value=""><?php _e('Select a category', 'ces'); ?></option>
                <?php foreach (ces_get_main_categories() as $cat): ?>
                    <option value="<?= esc_attr($cat->term_id); ?>"><?= esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="ces-field">
            <label for="ces-subcategory"><?php _e('Subcategory', 'ces'); ?>:</label>
            <select name="subcategory" id="ces-subcategory" disabled>
                <option value=""><?php _e('Select a main category first', 'ces'); ?></option>
            </select>
        </div>
                
        <!-- Cover image upload -->
        <div class="ces-field cover-upload-field">
            <label for="ces-cover-image"><?php _e('Cover Image', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="file" name="cover_image" id="ces-cover-image" accept="image/*" required />
            <div id="cover-image-preview" class="image-preview"></div>
            <div id="cover-image-error" style="color: red; margin-top: 5px;"></div>
            <span class="author-notice"><?php echo esc_html__( 'Please upload an image with a 2:3 aspect ratio','ces'); ?></span>
        </div>

        <div class="ces-field">
            <label for="ces-tags"><?php _e('Tags (comma-separated)', 'ces'); ?>:</label>
            <input type="text" name="tags" id="ces-tags" />
            <span class="tag-notice"> </span>
        </div>
        <div class="ces-price-group">
            <div class="ces-field">
                <label for="ces-price"><?php _e('Price Without VAT', 'ces'); ?>: <span class="ces-required">*</span></label>
                <input type="text" name="price" step="0.01" id="ces-price" required />                
                <span class="price-notice"></span>
            </div>
            <div class="ces-field">
                <label for="ces-vat-price"><?php _e('Price With VAT', 'ces'); ?>:</label>
                <input type="text" name="vat_price" id="ces-vat-price"/>
            </div>
            <div class="ces-field">
                <label for="ces-royalty"><?php _e('Your Royalty (approx.)', 'ces'); ?>:</label>
                <input type="text" name="" id="ces-royalty" readonly disabled/>
            </div>
            <div class="ces-field">
                <label for="ces-rate"><?php _e('Rate', 'ces'); ?>:</label>
                <input type="text" name="" id="ces-rate" value="" readonly disabled />
            </div>
        </div>
        <div class="ces-field">
            <label for="ces-file-type"><?php _e('File Type', 'ces'); ?>:</label>
            <select name="file_type" id="ces-file-type">
                <option value="epub">EPUB</option>
                <option value="docx">DOCX</option>
                <option value="cbz">CBZ</option>
                <option value="comic_images">JPG/PNG Images</option>
            </select>
        </div>

        <!-- Dynamic file upload fields - will be shown/hidden based on file type selection -->
        <div class="ces-field full file-upload-field" id="epub-upload-field">
            <label for="ces-epub-file"><?php _e('Upload EPUB File', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="file" name="epub_file" id="ces-epub-file" accept=".epub" required/>
        </div>

        <div class="ces-field full file-upload-field" id="docx-upload-field" style="display:none;">
            <label for="ces-docx-file"><?php _e('Upload DOCX File', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="file" name="docx_file" id="ces-docx-file" accept=".docx" />
        </div>

        <div class="ces-field full file-upload-field" id="cbz-upload-field" style="display:none;">
            <label for="ces-cbz-file"><?php _e('Upload CBZ File', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="file" name="cbz_file" id="ces-cbz-file" accept=".cbz" />
        </div>

        <div class="ces-field full file-upload-field" id="comic-images-upload-field" style="display:none;">
            <label for="ces-comic-images"><?php _e('Upload Comic Images', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="file" name="comic_images[]" id="ces-comic-images" accept="image/*" multiple />
            <div class="comic-image-instructions"><?php _e('Upload multiple images that will be converted to CBZ format. You can drag and drop to reorder them.', 'ces'); ?></div>
            <div id="comic-images-preview" class="comic-images-sortable comic-images-dropzone">
                <div class="dropzone-message"><?php _e('Drag & drop images here or click to upload', 'ces'); ?></div>
            </div>
            <input type="hidden" name="comic_images_order" id="comic-images-order" />
        </div>
        <input type="hidden" name="_ces_ebook_file" id="converted_epub_file" value="" />
    </div>
    <?php
            
        //ob_start(); // Start output buffering
        include_once CES_PLUGIN_DIR . 'templates/ces-preview-modal.php'; // Include the preview modal
        //$preview_modal = ob_get_clean(); // Get the buffered content
        //return $preview_modal; // Output the modal HTML
    ?>
    <div class="ces-buttons">
        <div class="form-buttons">
            <button type="button" id="ces-preview-btn" class="ces-preview-btn"><?php _e('Preview eBook', 'ces'); ?></button>
        </div>
        <div class="form-buttons ces-docx-prev-btn" style="display:none;">
            <button type="button" id="ces-doc-epub-preview-btn" class="ces-doc-epub-preview-btn"><?php _e('Preview eBook', 'ces'); ?></button>
        </div>
        <div class="ces-submit">
            <input id="submitBtn" type="submit" name="ces_submit_form" value="<?php esc_attr_e('Submit eBook', 'ces'); ?>" />
        </div>
    </div>
</form>