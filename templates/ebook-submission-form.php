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
                    <p><strong><?php _e('Indicative Price:', 'ces'); ?></strong> <?php echo $price; ?></p>
                </div>
            </div>
            
            <form id="ces-print-book-form" method="post" action="">
                <?php wp_nonce_field('ces_print_book_action', 'ces_print_book_nonce'); ?>
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                
                <div class="form-field">
                    <label for="personal_website_link"><?php _e('Link to your website or where the printed book is available (required):', 'ces'); ?></label>
                    <input type="url" name="personal_website_link" id="personal_website_link" required>
                </div>
                
                <div class="form-field">
                    <label for="bookstore_link"><?php _e('Link to an independent bookstore (optional):', 'ces'); ?></label>
                    <input type="url" name="bookstore_link" id="bookstore_link">
                    <span class="description"><?php _e('If provided, your book will be marked with "Supports bookstores" badge', 'ces'); ?></span>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="submit_print_book" class="button button-primary"><?php _e('Add Print Book', 'ces'); ?></button>
                    <a href="<?php echo esc_url(home_url()); ?>" class="button"><?php _e('Skip this step', 'ces'); ?></a>
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
        </div>

        <!-- Author name input -->
        <div class="ces-field" id="new-author-field" style="display:none;">
            <label for="ces-new-author"><?php _e('New Author Name', 'ces'); ?>:</label>
            <input type="text" name="new_author" id="ces-new-author" />
            <span class="author-notice"><?php echo esc_html__( 'Please enter the author name','ces'); ?></span>
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

        <div class="ces-field">
            <label for="ces-category-suggestion"><?php _e('Suggest a new category (optional)', 'ces'); ?>:</label>
            <input type="text" name="category_suggestion" id="ces-category-suggestion" />
        </div>

        <div class="ces-field">
            <label for="ces-tags"><?php _e('Tags (comma-separated)', 'ces'); ?>:</label>
            <input type="text" name="tags" id="ces-tags" />
            <span class="tag-notice"> </span>
        </div>

        <div class="ces-field">
            <label for="ces-price"><?php _e('Price Without VAT', 'ces'); ?>: <span class="ces-required">*</span></label>
            <input type="number" name="price" step="0.01" id="ces-price" required />
            <span class="price-notice"></span>
        </div>

        <div class="ces-field">
            <label for="ces-vat-price"><?php _e('Price With VAT', 'ces'); ?>:</label>
            <input type="text" name="vat_price" id="ces-vat-price"/>
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
        <div class="ces-field">
            <label for="ces-preview"><?php _e('Preview', 'ces'); ?>:</label>
            <button type="button" id="ces-preview-btn" class="ces-preview-btn"><?php _e('Preview eBook', 'ces'); ?></button><br>
            <span class="preview-notice"><?php echo __('Preview only available for EPUB and CBZ file', 'ces') ?></span>
            
        </div>

    </div>

    <div class="ces-submit">
        <input id="submitBtn" type="submit" name="ces_submit_form" value="<?php esc_attr_e('Submit eBook', 'ces'); ?>" />
    </div>
</form>

<script>

    // First, let's add the modal HTML structure to your page

// Add this HTML to your page (outside of your form)
const modalHTML = `
<div id="ces-preview-modal" class="ces-modal">
    <div class="ces-modal-content">
        <div class="ces-modal-header">
            <h2 id="ces-preview-title"><?php _e('eBook Preview', 'ces'); ?></h2>
            <span class="ces-modal-close">&times;</span>
        </div>
        <div class="ces-modal-controls">
            <button id="ces-prev-page" class="ces-nav-btn" title="<?php _e('Previous Page', 'ces'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <span id="ces-page-info"><?php _e('Pagination', 'ces'); ?> </span>
            <button id="ces-next-page" class="ces-nav-btn" title="<?php _e('Next Page', 'ces'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        <div class="ces-modal-body">
            <div id="ces-epub-viewer" class="ces-viewer"></div>
            <div id="ces-cbz-viewer" class="ces-viewer"></div>
        </div>
    </div>
</div>
`;

// Append modal HTML to the body
document.addEventListener('DOMContentLoaded', function() {
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add CSS for the modal
    const style = document.createElement('style');
    style.textContent = `
        .ces-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .ces-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 960px;
            height: 90vh;
            display: flex;
            flex-direction: column;
            border-radius: 5px;
        }
        
        .ces-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            align-content: flex-start;

        }
        
        .ces-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -10px;
        }
        
        .ces-modal-close:hover,
        .ces-modal-close:focus {
            color: black;
            text-decoration: none;
        }
        
        .ces-modal-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .ces-nav-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin: 0 10px;
            border-radius: 50%;
        }
        
        .ces-nav-btn:hover {
            background-color: #f0f0f0;
        }
        
        .ces-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        #ces-page-info {
            margin: 0 20px;
            font-size: 14px;
        }
        
        .ces-modal-body {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .ces-viewer {
            width: 100%;
            height: 100%;
            display: none;
            overflow: auto;
        }
        
        #ces-epub-viewer {
            background: #f9f9f9;
        }
        
        #ces-cbz-viewer {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f0f0f0;
            text-align: center;
        }
        
        #ces-cbz-viewer img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    `;
    document.head.appendChild(style);
    
    initPreviewFunctionality();
});

function initPreviewFunctionality() {
    // Elements
    const fileTypeSelect = document.getElementById('ces-file-type');
    const previewBtn = document.getElementById('ces-preview-btn');
    const modal = document.getElementById('ces-preview-modal');
    const closeBtn = document.querySelector('.ces-modal-close');
    const epubViewer = document.getElementById('ces-epub-viewer');
    const cbzViewer = document.getElementById('ces-cbz-viewer');
    const prevPageBtn = document.getElementById('ces-prev-page');
    const nextPageBtn = document.getElementById('ces-next-page');
    const currentPageEl = document.getElementById('ces-current-page');
    const totalPagesEl = document.getElementById('ces-total-pages');
    
    // State variables
    let book = null;
    let rendition = null;
    let currentPage = 1;
    let totalPages = 1;
    let cbzImages = [];
    let previewType = null;
    
    // Event listeners
    previewBtn.addEventListener('click', handlePreviewClick);
    closeBtn.addEventListener('click', closeModal);
    prevPageBtn.addEventListener('click', goToPrevPage);
    nextPageBtn.addEventListener('click', goToNextPage);
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Also handle keyboard events for navigation
    window.addEventListener('keydown', function(event) {
        if (!modal.style.display || modal.style.display === 'none') return;
        
        if (event.key === 'ArrowLeft') {
            goToPrevPage();
        } else if (event.key === 'ArrowRight') {
            goToNextPage();
        } else if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    function handlePreviewClick() {
        const fileType = fileTypeSelect.value;
        
        // Check if we have a file to preview
        let fileInput;
        if (fileType === 'epub') {
            fileInput = document.getElementById('ces-epub-file');
            previewType = 'epub';
        } else if (fileType === 'cbz') {
            fileInput = document.getElementById('ces-cbz-file');
            previewType = 'cbz';
        } else {
            alert('<?php _e("Preview is only available for EPUB and CBZ files", "ces"); ?>');
            return;
        }
        
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('<?php _e("Please upload a file first", "ces"); ?>');
            return;
        }
        
        const file = fileInput.files[0];
        
        // Reset the viewers
        epubViewer.style.display = 'none';
        cbzViewer.style.display = 'none';
        epubViewer.innerHTML = '';
        cbzViewer.innerHTML = '';
        
        // Show the modal
        modal.style.display = 'block';
        
        // Initialize the appropriate viewer
        if (previewType === 'epub') {
            initEpubViewer(file);
        } else if (previewType === 'cbz') {
            initCbzViewer(file);
        }
    }
    
    function initEpubViewer(file) {
        // Show the EPUB viewer
        epubViewer.style.display = 'block';
        
        // Create a new book
        book = ePub(file);
        
        // Generate rendition
        rendition = book.renderTo('ces-epub-viewer', {
            width: '100%',
            height: '100%',
            spread: 'none'
        });
        
        // Display the first page
        rendition.display();
        
        // Update navigation
        book.ready.then(() => {
            book.locations.generate().then(() => {
                // Get the total number of pages
                totalPages = book.locations.length();
                currentPage = 1;
                
                // Update UI
                //updatePageInfo();
                
                // Enable/disable navigation buttons
                updateNavButtons();
            });
        });
        
        // Listen for page changes
        rendition.on('relocated', function(location) {
            currentPage = book.locations.locationFromCfi(location.start.cfi);
            //updatePageInfo();
            updateNavButtons();
        });
    }
    
    function initCbzViewer(file) {
        // Show the CBZ viewer
        cbzViewer.style.display = 'block';
        
        // Reset images array
        cbzImages = [];
        currentPage = 1;
        
        // Use JSZip to extract images from the CBZ file
        const reader = new FileReader();
        reader.onload = function(e) {
            JSZip.loadAsync(e.target.result)
                .then(function(zip) {
                    // Filter for image files only
                    const imagePromises = [];
                    const imageFiles = [];
                    
                    zip.forEach(function(relativePath, zipEntry) {
                        const lowerPath = relativePath.toLowerCase();
                        if (!zipEntry.dir && (lowerPath.endsWith('.jpg') || 
                                             lowerPath.endsWith('.jpeg') || 
                                             lowerPath.endsWith('.png') || 
                                             lowerPath.endsWith('.gif'))) {
                            imageFiles.push({
                                name: relativePath,
                                zipEntry: zipEntry
                            });
                        }
                    });
                    
                    // Sort files by name (simple numeric sort)
                    imageFiles.sort((a, b) => {
                        return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
                    });
                    
                    // Extract each image
                    for (const file of imageFiles) {
                        const promise = file.zipEntry.async('blob')
                            .then(function(blob) {
                                const url = URL.createObjectURL(blob);
                                cbzImages.push(url);
                            });
                        imagePromises.push(promise);
                    }
                    
                    // Once all images are extracted, display the first one
                    Promise.all(imagePromises).then(function() {
                        totalPages = cbzImages.length;
                        //updatePageInfo();
                        updateNavButtons();
                        displayCbzPage(1);
                    });
                })
                .catch(function(error) {
                    console.error('Error reading CBZ file:', error);
                    cbzViewer.innerHTML = '<p>Error loading CBZ file: ' + error.message + '</p>';
                });
        };
        reader.readAsArrayBuffer(file);
    }
    
    function displayCbzPage(pageNum) {
        if (pageNum < 1 || pageNum > cbzImages.length) return;
        
        currentPage = pageNum;
        cbzViewer.innerHTML = '';
        
        const img = document.createElement('img');
        img.src = cbzImages[pageNum - 1];
        img.alt = 'Page ' + pageNum;
        cbzViewer.appendChild(img);
        
        //updatePageInfo();
        updateNavButtons();
    }
    
    function goToPrevPage() {
        if (previewType === 'epub') {
            rendition.prev();
        } else if (previewType === 'cbz') {
            if (currentPage > 1) {
                displayCbzPage(currentPage - 1);
            }
        }
    }
    
    function goToNextPage() {
        if (previewType === 'epub') {
            rendition.next();
        } else if (previewType === 'cbz') {
            if (currentPage < totalPages) {
                displayCbzPage(currentPage + 1);
            }
        }
    }
    
    function updatePageInfo() {
        currentPageEl.textContent = currentPage;
        totalPagesEl.textContent = totalPages;
    }
    
    function updateNavButtons() {
        // Disable/enable prev button
        prevPageBtn.disabled = currentPage <= 1;
        
        // Disable/enable next button
        nextPageBtn.disabled = currentPage >= totalPages;
    }
    
    function closeModal() {
        modal.style.display = 'none';
        
        // Clean up resources
        if (book && rendition) {
            rendition.destroy();
            book = null;
            rendition = null;
        }
        
        // Revoke object URLs for CBZ images
        if (cbzImages.length > 0) {
            cbzImages.forEach(url => URL.revokeObjectURL(url));
            cbzImages = [];
        }
    }
}
</script>