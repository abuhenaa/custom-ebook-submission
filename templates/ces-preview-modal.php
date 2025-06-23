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
        } else if( fileExtension === 'docx'){
            handleDocxConversion();
            return;
        } else {
            alert('<?php _e( "Unsupported file type for preview", "ces" ); ?>');
            return;
        }

        previewEbook(fileUrl, fileType);
    }
    // Handle DOCX to EPUB conversion
    function handleDocxConversion() {
        // Get the file input
        var fileInput = $('input[type="file"][accept=".docx"]');

        var file = fileInput[0].files[0];
        if (!file) {
            alert('Please select a DOCX file first.');
            return;
        }

        // Validate file type
        if (!file.name.toLowerCase().endsWith('.docx')) {
            alert('Please select a valid DOCX file.');
            return;
        }

        // Show loading state
        $('.ces-convert-btn').text('<?php _e( "Converting...", "ces" ); ?>');
        // Show loading indicator if exists
        $('.conversion-loading').show();

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'convert_docx_to_epub');
        formData.append('docx_file', file);
        formData.append('nonce', ces_ajax.nonce);

        // Make AJAX request
        $.ajax({
            url: ces_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var fileUrl = response.data.file_url;
                    // Update hidden field with file URL
                    $('input[name="_ces_ebook_file"]').val(fileUrl);

                    // Show success message
                    alert(response.data.message);
                    //hide the ces-preview-btn and add new preview button with id #ces-doc-epub-preview-btn after
                     
                    $('.ces-docx-prev-btn').show();
                    $('.ces-preview-btn').parent('.form-buttons').hide();


                } else {
                    alert(response.data.message || 'Conversion failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An error occurred during conversion. Please try again.');
            },
            complete: function() {
                $('.conversion-loading').hide();

            }
        });
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

    
    
    // Handle preview button click for DOCX
    // This button is added dynamically after conversion
    $('#ces-doc-epub-preview-btn').on('click', function() {
        previewEbook($('input[name="_ces_ebook_file"]').val(), 'docx');
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
        } else if( previewType === 'docx'){
            initEpubViewerFromUrl(fileUrl);
        } else {
            alert('<?php _e( "Preview is not available", "ces" ); ?>');
            closeModal();
        }
    };

async function initEpubViewerFromUrl(fileUrl) {
    // Show the EPUB viewer
    const epubViewer = jQuery('#ces-epub-viewer');
    epubViewer.show();

    var fileType = jQuery('#ces-file-type').val().toLowerCase();
    convertedFileUrl = jQuery('#converted_epub_file').val();

    // If it's DOCX type, use the converted EPUB URL directly
    if (fileType === 'docx') {
        await initEpubFromUrl(fileUrl);
        console.log('DOCX file preview initialized directly from converted EPUB URL:', fileUrl);
        return;
    }

    // For local files, use FileReader
    const epubField = jQuery('#ces-epub-file')[0];
    const file = epubField.files[0];

    if (file && fileType == 'epub') {
        const reader = new FileReader();
        reader.onload = function(e) {
            initEpubFromData(e.target.result);
        };

        reader.onerror = function(error) {
            console.error('FileReader error:', error);
            jQuery('.ces-loading').remove();
        };

        // Read file as ArrayBuffer
        reader.readAsArrayBuffer(file);
    } else if (fileUrl && fileType == 'docx') {
        console.log('condition met');
        // If no local file but fileUrl is provided, use the URL
       await initEpubFromUrl(fileUrl);
    } else {
        console.error('No file or URL provided');
        jQuery('.ces-loading').remove();
    }
}

async function initEpubFromUrl(url) {
    try {
        // Fetch the EPUB file as a blob
        const response = await fetch(url);
        const blob = await response.blob();
        
        // Create a blob URL
        const blobUrl = URL.createObjectURL(blob);
        console.log('Blob URL created:', blobUrl);
        // Use the blob URL with ePub.js
        book = ePub(blobUrl, {
            restore: true,
            openAs: 'epub'
        });
        
        setupEpubRendition();
    } catch (error) {
        console.error('Error:', error);
    }
}

function initEpubFromData(arrayBuffer) {
    try {
        // Create book from ArrayBuffer
        book = ePub(arrayBuffer);
        setupEpubRendition();
    } catch (error) {
        console.error('Error initializing EPUB from data:', error);
        jQuery('.ces-loading').remove();
    }
}
function setupEpubRendition() {
    const viewerElement = document.getElementById('ces-epub-viewer');
    const rect = viewerElement.getBoundingClientRect();

    rendition = book.renderTo('ces-epub-viewer', {
        width: '100%', //rect.width || 800,
        height: '100%', //rect.height || 600,
        spread: 'none',
        flow: 'scrolled-doc',
    });

    // Register single-page theme
    rendition.themes.register('image-fix', {
         'body': {
            'display': 'flex',
            'justify-content': 'center',
            'padding': '10px',
            'margin': '0 auto',
            'height': 'auto',
            'box-sizing': 'border-box'
        },
        'img': {
            'max-width': '100%',
            'height': 'auto',
            'object-fit': 'contain',
        }
    });

    rendition.themes.select('image-fix');

    // Display and force proper layout
    rendition.display().then(() => {
        jQuery('.ces-loading').remove();

        // Force resize to fix layout
        setTimeout(() => {
            rendition.resize();
        }, 100);

        return book.locations.generate(1024);
    }).then((locations) => {
        totalPages = locations.length;
        currentPage = 1;
        updateNavButtons();
    }).catch(error => {
        totalPages = 100;
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
        if (previewType === 'epub' || previewType === 'docx') {
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
        if (previewType === 'epub' || previewType === 'docx') {
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