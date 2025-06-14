<script>
jQuery(document).ready(function($) {

// HTML page (outside form)
const modalHTML = `
<div id="ces-preview-modal" class="ces-modal">
    <div class="ces-modal-content">
        <div class="ces-modal-header">
            <h2 id="ces-preview-title"><?php _e( 'eBook Preview', 'ces' ); ?></h2>
            <span class="ces-modal-close">&times;</span>
        </div>
        <div class="ces-modal-controls">
            <button id="ces-prev-page" class="ces-nav-btn" title="<?php _e( 'Previous Page', 'ces' ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <button id="ces-next-page" class="ces-nav-btn" title="<?php _e( 'Next Page', 'ces' ); ?>">
               <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
        <div class="ces-modal-body">
            <div id="ces-epub-viewer" class="ces-viewer"></div>
            <div id="ces-cbz-viewer" class="ces-viewer"></div>
        </div>
    </div>
</div>
<style>
/* Modal Overlay */
.ces-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(3px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Modal Content Container */
.ces-modal-content {
    position: relative;
    background-color: #ffffff;
    margin: 2% auto;
    width: 90%;
    max-width: 1200px;
    height: 90vh;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { 
        transform: translateY(-30px) scale(0.95);
        opacity: 0;
    }
    to { 
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

/* Modal Header */
.ces-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ces-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Close Button */
.ces-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 50%;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ces-modal-close:hover,
.ces-modal-close:focus {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

/* Modal Controls */
.ces-modal-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    padding: 15px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

/* Navigation Buttons */
.ces-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
    min-width: 50px;
    height: 44px;
}

.ces-nav-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #45a049 0%, #4CAF50 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
}

.ces-nav-btn:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3);
}

.ces-nav-btn:disabled {
    background: #cccccc;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.6;
}

.ces-nav-btn .dashicons {
    font-size: 18px;
    line-height: 1;
}

/* Modal Body */
.ces-modal-body {
    flex: 1;
    position: relative;
    overflow: hidden;
    background: #ffffff;
}

/* Viewers */
.ces-viewer {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    overflow: auto;
    background: #ffffff;
}

/* EPUB Viewer Specific */
#ces-epub-viewer {
    padding: 20px;
    background: #fdfdfd;
}

/* CBZ Viewer Specific */
#ces-cbz-viewer {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    position: relative;
}

#ces-cbz-viewer img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

/* Loading State */
.ces-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 18px;
    color: #666;
    background: #f9f9f9;
    position: relative;
}

.ces-loading::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Page Counter */
.ces-page-counter {
    position: absolute !important;
    bottom: 20px !important;
    right: 20px !important;
    background: rgba(0, 0, 0, 0.8) !important;
    color: white !important;
    padding: 8px 16px !important;
    border-radius: 20px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    z-index: 10;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ces-modal-content {
        width: 95%;
        height: 95vh;
        margin: 2.5% auto;
        border-radius: 8px;
    }
    
    .ces-modal-header {
        padding: 15px 20px;
    }
    
    .ces-modal-header h2 {
        font-size: 1.25rem;
    }
    
    .ces-modal-controls {
        padding: 10px 20px;
        gap: 15px;
    }
    
    .ces-nav-btn {
        padding: 10px 16px;
        min-width: 44px;
        height: 40px;
    }
    
    #ces-epub-viewer {
        padding: 15px;
    }
    
    .ces-page-counter {
        bottom: 15px !important;
        right: 15px !important;
        padding: 6px 12px !important;
        font-size: 12px !important;
    }
}

@media (max-width: 480px) {
    .ces-modal-content {
        width: 98%;
        height: 98vh;
        margin: 1% auto;
    }
    
    .ces-modal-header {
        padding: 12px 15px;
    }
    
    .ces-modal-header h2 {
        font-size: 1.1rem;
    }
    
    .ces-modal-close {
        width: 35px;
        height: 35px;
        font-size: 24px;
    }
    
    .ces-modal-controls {
        padding: 8px 15px;
        gap: 10px;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .ces-modal-content {
        background-color: #2c2c2c;
        color: #ffffff;
    }
    
    .ces-modal-controls {
        background: #3a3a3a;
        border-bottom-color: #555;
    }
    
    .ces-viewer {
        background: #2c2c2c;
    }
    
    #ces-epub-viewer {
        background: #333333;
        color: #ffffff;
    }
    
    #ces-cbz-viewer {
        background: #2a2a2a;
    }
    
    .ces-loading {
        color: #ccc;
        background: #333;
    }
}

/* Scrollbar Styling */
.ces-viewer::-webkit-scrollbar {
    width: 8px;
}

.ces-viewer::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.ces-viewer::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.ces-viewer::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Focus States for Accessibility */
.ces-modal-close:focus,
.ces-nav-btn:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .ces-modal {
        display: none !important;
    }
}
</style>
`;

function initPreviewFunctionality() {
    // State variables
    let book = null;
    let rendition = null;
    let currentPage = 1;
    let totalPages = 1;
    let cbzImages = [];
    let previewType = null;

    // Event listeners using jQuery
    jQuery('#ces-preview-btn').on('click', handlePreviewClick);
    jQuery(document).on('click', '.ces-modal-close', closeModal);
    jQuery(document).on('click', '#ces-prev-page', goToPrevPage);
    jQuery(document).on('click', '#ces-next-page', goToNextPage);

    // Close modal when clicking outside of it
    jQuery(document).on('click', function(event) {
        if (jQuery(event.target).is('#ces-preview-modal')) {
            closeModal();
        }
    });

    function handlePreviewClick() {
        const fileExtension = jQuery('#ces-file-type').val().toLowerCase();
        console.log('File extension:', fileExtension);
        
        let file = null;
        let fileUrl = "";
        let fileType = "";

        if (fileExtension === 'epub') {
            const epubField = jQuery('#ces-epub-file')[0];
            if (epubField.files && epubField.files[0]) {
                file = epubField.files[0];
                fileUrl = URL.createObjectURL(file);
                fileType = 'epub';
            } else {
                alert('<?php _e( "Please select an EPUB file first", "ces" ); ?>');
                return;
            }
        } else if (fileExtension === 'cbz') {
            const cbzField = jQuery('#ces-cbz-file')[0];
            if (cbzField.files && cbzField.files[0]) {
                file = cbzField.files[0];
                fileUrl = URL.createObjectURL(file);
                fileType = 'cbz';
            } else {
                alert('<?php _e( "Please select a CBZ file first", "ces" ); ?>');
                return;
            }
        } else if (fileExtension === 'comic_images') {
            previewComicImages();
            return;
        } else {
            alert('<?php _e( "Preview is only available for EPUB, CBZ files and Comic Images", "ces" ); ?>');
            return;
        }

        previewEbook(fileUrl, fileType);
    }

    // Handle keyboard events for navigation
    jQuery(document).on('keydown', function(event) {
        const modal = jQuery('#ces-preview-modal');
        if (!modal.is(':visible')) return;

        if (event.key === 'ArrowLeft') {
            goToPrevPage();
        } else if (event.key === 'ArrowRight') {
            goToNextPage();
        } else if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Main function to preview file by URL/path
    window.previewEbook = function(fileUrl, fileType) {
        previewType = fileType.toLowerCase();

        // Reset the viewers
        const epubViewer = jQuery('#ces-epub-viewer');
        const cbzViewer = jQuery('#ces-cbz-viewer');
        
        epubViewer.hide().html('<div class="ces-loading">Loading EPUB...</div>');
        cbzViewer.hide().html('<div class="ces-loading">Loading CBZ...</div>');

        // Show the modal
        jQuery('#ces-preview-modal').show();

        // Initialize the appropriate viewer
        if (previewType === 'epub') {
            initEpubViewerFromUrl(fileUrl);
        } else if (previewType === 'cbz') {
            initCbzViewerFromUrl(fileUrl);
        } else {
            alert('<?php _e( "Preview is only available for EPUB and CBZ files", "ces" ); ?>');
            closeModal();
        }
    };

    function initEpubViewerFromUrl(fileUrl) {
        // Show the EPUB viewer
        const epubViewer = jQuery('#ces-epub-viewer');
        epubViewer.show();

        // Method 1: Try with ArrayBuffer (more reliable for local files)
        const epubField = jQuery('#ces-epub-file')[0];
        const file = epubField.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    // Create book from ArrayBuffer
                    book = ePub(e.target.result);
                    
                    // Generate rendition
                    rendition = book.renderTo('ces-epub-viewer', {
                        width: '100%',
                        height: '100%',
                        spread: 'none',
                        allowScriptedContent: true
                    });

                    // Display the first page
                    rendition.display().then(() => {
                        console.log('EPUB rendered successfully');
                        jQuery('.ces-loading').remove();
                        
                        // Try to generate locations for navigation
                        return book.locations.generate(1024);
                    }).then((locations) => {
                        console.log('Locations generated:', locations.length);
                        totalPages = locations.length;
                        currentPage = 1;
                        updateNavButtons();
                    }).catch(error => {
                        console.error('Error with locations:', error);
                        // Even if locations fail, we can still navigate
                        totalPages = 100; // Fallback
                        currentPage = 1;
                        updateNavButtons();
                        jQuery('.ces-loading').remove();
                    });

                    // Listen for page changes
                    rendition.on('relocated', function(location) {
                        console.log('Page relocated:', location);
                        if (book.locations && book.locations.locationFromCfi) {
                            currentPage = book.locations.locationFromCfi(location.start.cfi) || currentPage;
                            updateNavButtons();
                        }
                    });

                    // Handle rendition errors
                    rendition.on('rendered', function() {
                        console.log('EPUB page rendered');
                        jQuery('.ces-loading').remove();
                    });

                } catch (error) {
                    console.error('Error initializing EPUB viewer:', error);
                    // Fallback to blob URL method
                    initEpubWithBlobUrl(fileUrl);
                }
            };
            
            reader.onerror = function(error) {
                console.error('FileReader error:', error);
                // Fallback to blob URL method
                initEpubWithBlobUrl(fileUrl);
            };
            
            // Read file as ArrayBuffer
            reader.readAsArrayBuffer(file);
        } else {
            // Fallback to blob URL method
            initEpubWithBlobUrl(fileUrl);
        }
    }

    // Fallback method using blob URL
    function initEpubWithBlobUrl(fileUrl) {
        console.log('Trying blob URL method:', fileUrl);
        
        try {
            // Create a new book from URL
            book = ePub(fileUrl, {
                openAs: 'epub',
                encoding: 'binary'
            });

            // Generate rendition
            rendition = book.renderTo('ces-epub-viewer', {
                width: '100%',
                height: '100%',
                spread: 'none',
                allowScriptedContent: true
            });

            // Display the first page
            rendition.display().then(() => {
                console.log('EPUB rendered with blob URL');
                jQuery('.ces-loading').remove();
                
                // Set basic navigation
                totalPages = 100; // Fallback
                currentPage = 1;
                updateNavButtons();
                
                // Try to generate locations
                return book.ready;
            }).then(() => {
                return book.locations.generate(1024);
            }).then((locations) => {
                totalPages = locations.length;
                updateNavButtons();
            }).catch(error => {
                console.error('Error with blob URL method:', error);
                jQuery('#ces-epub-viewer').html('<div class="ces-loading">Error loading EPUB file. This might be due to browser security restrictions.</div>');
            });

            // Listen for page changes
            rendition.on('relocated', function(location) {
                if (book.locations && book.locations.locationFromCfi) {
                    currentPage = book.locations.locationFromCfi(location.start.cfi) || currentPage;
                    updateNavButtons();
                }
            });

        } catch (error) {
            console.error('Error with blob URL method:', error);
            jQuery('#ces-epub-viewer').html('<div class="ces-loading">Error loading EPUB file. Browser may not support this file format.</div>');
        }
    }

    function initCbzViewerFromUrl(fileUrl) {
        // Show the CBZ viewer
        const cbzViewer = jQuery('#ces-cbz-viewer');
        cbzViewer.show();

        // Reset images array
        cbzImages = [];
        currentPage = 1;

        // Fetch the CBZ file
        fetch(fileUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.arrayBuffer();
            })
            .then(arrayBuffer => {
                return JSZip.loadAsync(arrayBuffer);
            })
            .then(zip => {
                // Filter for image files only
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

                // Sort files by name (numeric sort to handle filenames like 1.jpg, 2.jpg, 10.jpg correctly)
                imageFiles.sort((a, b) => {
                    return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
                });

                // Initialize cbzImages array with correct length
                cbzImages = new Array(imageFiles.length);

                // Extract each image and store it at the correct index
                const imagePromises = imageFiles.map((file, index) => {
                    return file.zipEntry.async('blob')
                        .then(function(blob) {
                            const url = URL.createObjectURL(blob);
                            cbzImages[index] = url; // Store at the correct index
                        });
                });

                // Once all images are extracted, display the first one
                return Promise.all(imagePromises);
            })
            .then(() => {
                totalPages = cbzImages.length;
                updateNavButtons();
                jQuery('.ces-loading').remove();
                displayCbzPage(1);
            })
            .catch(error => {
                console.error('Error reading CBZ file:', error);
                cbzViewer.html('<div class="ces-loading">Error loading CBZ file: ' + error.message + '</div>');
            });
    }

    function displayCbzPage(pageNum) {
        if (pageNum < 1 || pageNum > cbzImages.length) return;

        currentPage = pageNum;
        const cbzViewer = jQuery('#ces-cbz-viewer');
        cbzViewer.empty();

        const img = jQuery('<img>')
            .attr('src', cbzImages[pageNum - 1])
            .attr('alt', 'Page ' + pageNum)
            .on('load', function() {
                updateNavButtons();
            });
        
        cbzViewer.append(img);
    }

    function updateNavButtons() {
        // Disable/enable prev button
        jQuery('#ces-prev-page').prop('disabled', currentPage <= 1);

        // Disable/enable next button
        jQuery('#ces-next-page').prop('disabled', currentPage >= totalPages);
    }

    // Function to preview comic images from drag and drop field
    function previewComicImages() {
        // Get images in the correct order from the drag and drop field
        const comicImagesOrder = JSON.parse(jQuery('#comic-images-order').val() || '[]');

        if (comicImagesOrder.length === 0) {
            alert('<?php _e( "No images to preview. Please add some images first.", "ces" ); ?>');
            return;
        }

        // Collect image data URLs in the correct order
        const imageUrls = [];
        comicImagesOrder.forEach(index => {
            const imageItem = jQuery(`.comic-image-item[data-index="${index}"]`);
            if (imageItem.length > 0) {
                const imgSrc = imageItem.find('img').attr('src');
                if (imgSrc) {
                    imageUrls.push(imgSrc);
                }
            }
        });

        if (imageUrls.length === 0) {
            alert('<?php _e( "No valid images found to preview.", "ces" ); ?>');
            return;
        }

        // Use the existing modal and CBZ viewer functionality
        previewType = 'comic';
        cbzImages = imageUrls; // Reuse the cbzImages array
        currentPage = 1;
        totalPages = imageUrls.length;

        // Reset the viewers
        const epubViewer = jQuery('#ces-epub-viewer');
        const cbzViewer = jQuery('#ces-cbz-viewer');

        epubViewer.hide();
        cbzViewer.show().empty();

        // Show the modal
        jQuery('#ces-preview-modal').show();

        // Display the first image
        displayComicPage(1);
        updateNavButtons();
    }

    // Function to display comic page (similar to displayCbzPage but for comic images)
    function displayComicPage(pageNum) {
        const cbzViewer = jQuery('#ces-cbz-viewer');

        if (pageNum < 1 || pageNum > cbzImages.length) return;

        currentPage = pageNum;
        cbzViewer.empty();

        const img = jQuery('<img>')
            .attr('src', cbzImages[pageNum - 1])
            .attr('alt', 'Comic Page ' + pageNum)
            .css({
                'max-width': '100%',
                'max-height': '100%',
                'object-fit': 'contain'
            })
            .on('load', function() {
                updateNavButtons();
            });

        cbzViewer.append(img);

        // Add page counter
        const pageCounter = jQuery('<div>')
            .addClass('ces-page-counter')
            .text(`${pageNum} / ${cbzImages.length}`)
            .css({
                'position': 'absolute',
                'bottom': '10px',
                'right': '10px',
                'background': 'rgba(0,0,0,0.7)',
                'color': 'white',
                'padding': '5px 10px',
                'border-radius': '4px',
                'font-size': '14px'
            });

        cbzViewer.append(pageCounter);
    }

    // Update the existing goToPrevPage and goToNextPage functions to handle comic images
    function goToPrevPage() {
        if (previewType === 'epub') {
            if (rendition) {
                rendition.prev();
            }
        } else if (previewType === 'cbz') {
            if (currentPage > 1) {
                displayCbzPage(currentPage - 1);
            }
        } else if (previewType === 'comic') {
            if (currentPage > 1) {
                displayComicPage(currentPage - 1);
            }
        }
    }

    function goToNextPage() {
        if (previewType === 'epub') {
            if (rendition) {
                rendition.next();
            }
        } else if (previewType === 'cbz') {
            if (currentPage < totalPages) {
                displayCbzPage(currentPage + 1);
            }
        } else if (previewType === 'comic') {
            if (currentPage < totalPages) {
                displayComicPage(currentPage + 1);
            }
        }
    }

    // Update the closeModal function to handle comic cleanup
    function closeModal() {
        jQuery('#ces-preview-modal').hide();

        // Clean up resources
        if (book && rendition) {
            rendition.destroy();
            book = null;
            rendition = null;
        }

        // For CBZ images, revoke object URLs (but not for comic images as they're data URLs)
        if (previewType === 'cbz' && cbzImages.length > 0) {
            cbzImages.forEach(url => {
                if (url.startsWith('blob:')) {
                    URL.revokeObjectURL(url);
                }
            });
        }

        // Reset state
        cbzImages = [];
        currentPage = 1;
        totalPages = 1;
        previewType = null;
    }

    // Function to add preview button if it doesn't exist
    function addPreviewButton() {
        // Check if button already exists
        if (jQuery('#ces-preview-btn').length > 0) {
            return;
        }

        const previewButton = jQuery('<button>')
            .attr({
                'id': 'ces-preview-btn',
                'type': 'button'
            })
            .addClass('button button-secondary')
            .html('<span class="dashicons dashicons-visibility"></span> <?php _e( "Preview", "ces" ); ?>')
            .css('margin-top', '10px');

        // Add the button after the file type selection
        jQuery('#ces-file-type').closest('.ces-field').after(previewButton);
    }

    // Add the preview button
    addPreviewButton();
}

// Add the modal HTML to the page if it doesn't exist
if (jQuery('#ces-preview-modal').length === 0) {
    jQuery('body').append(modalHTML);
    initPreviewFunctionality();
}

});
</script>