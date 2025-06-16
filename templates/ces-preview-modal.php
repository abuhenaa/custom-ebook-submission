<script>
jQuery(document).ready(function($) {

// HTML page (outside form)
const modalHTML = `
<div id="ces-preview-modal" class="ces-modal">
    <div class="ces-modal-content">
        <div class="ces-modal-header">
            <h2 id="ces-preview-title"><?php _e( 'eBook Preview', 'ces' ); ?></h2>
           
            <div class="ces-modal-controls">
                <button id="ces-prev-page" class="ces-nav-btn" title="<?php _e( 'Previous Page', 'ces' ); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <button id="ces-next-page" class="ces-nav-btn" title="<?php _e( 'Next Page', 'ces' ); ?>">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
            <span class="ces-modal-close">&times;</span>
        </div>
        <div class="ces-modal-body">
            <div id="ces-epub-viewer" class="ces-viewer"></div>
            <div id="ces-cbz-viewer" class="ces-viewer"></div>
        </div>
    </div>
</div>
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

                    rendition.themes.register('fix-images', {
                        'body': {
                            'display': 'flex',
                            'justify-content': 'center',
                            'padding': '20px',
                            'margin': '0 auto',
                            'height': 'auto',
                            'box-sizing': 'border-box'
                        },
                        'img': {
                            'max-width': '100%',
                            'height': 'auto',
                            'object-fit': 'contain'
                        }
                    });
                    rendition.themes.select('fix-images');


                    // Display the first page
                    rendition.display().then(() => {
                        jQuery('.ces-loading').remove();
                        
                        // Try to generate locations for navigation
                        return book.locations.generate(1024);
                    }).then((locations) => {
                        totalPages = locations.length;
                        currentPage = 1;
                        updateNavButtons();
                    }).catch(error => {
                        // Even if locations fail, we can still navigate
                        totalPages = 100; // Fallback
                        currentPage = 1;
                        updateNavButtons();
                        jQuery('.ces-loading').remove();
                    });

                    // Listen for page changes
                    rendition.on('relocated', function(location) {
                        if (book.locations && book.locations.locationFromCfi) {
                            currentPage = book.locations.locationFromCfi(location.start.cfi) || currentPage;
                            updateNavButtons();
                        }
                    });

                    // Handle rendition errors
                    rendition.on('rendered', function() {
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
        
        try {
            // Create a new book from URL
            book = ePub(fileUrl, {
                openAs: 'epub',
                encoding: 'binary'
            });

            // Generate rendition
            rendition = book.renderTo('ces-epub-viewer', {
                width: '600px',
                height: '100%',
                spread: 'none',
                allowScriptedContent: true
            });

            // Display the first page
            rendition.display().then(() => {
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