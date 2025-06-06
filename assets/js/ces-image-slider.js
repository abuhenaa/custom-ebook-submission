// Add this CSS to your stylesheet or in a <style> tag in your header
jQuery(document).ready(function ($) {
    // Initialize slider functionality once document is ready
    function initImageSlider() {
        // Get container and its images
        const $container = $('#ces-preview-container');
        const $images = $container.find('.ces-cbz-image');

        // Don't proceed if there are no images or only one image
        if ($images.length <= 1) return;

        // Add necessary CSS classes
        $container.addClass('ces-slider-container');
        $images.addClass('ces-slider-image');

        // Hide all images except the first one
        $images.not(':first').hide();

        

        let currentIndex = 0;

        // Next button click handler
        $(document).on('click', '#prev-page', function (e) {
            $images.eq(currentIndex).fadeOut(300);
            currentIndex = (currentIndex + 1) % $images.length;
            $images.eq(currentIndex).fadeIn(300);
            //updateCounter();
            e.preventDefault();
        });

        // Previous button click handler
        $(document).on('click', '#next-page', function (e) {
            $images.eq(currentIndex).fadeOut(300);
            currentIndex = (currentIndex - 1 + $images.length) % $images.length;
            $images.eq(currentIndex).fadeIn(300);
            //updateCounter();
            e.preventDefault();
        });

        // Update counter display
        function updateCounter() {
            $container.find('.ces-slider-counter').text(`${currentIndex + 1}/${$images.length}`);
        }

        // Add keyboard navigation support
        $(document).on('keydown', function (e) {
            if (!$images.length) return;

            if (e.keyCode === 37) { // Left arrow key
                $container.find('#prev-page').click();
            } else if (e.keyCode === 39) { // Right arrow key
                $container.find('#next-page').click();
            }
        });

        // Add swipe support for touch devices
        let touchStartX = 0;
        let touchEndX = 0;

        $container.on('touchstart', function (e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });

        $container.on('touchend', function (e) {
            touchEndX = e.originalEvent.changedTouches[0].clientX;
            handleSwipe();
        });

        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                // Swipe left - next image
                $container.find('#next-page').click();
            } else if (touchEndX > touchStartX + 50) {
                // Swipe right - previous image
                $container.find('#prev-page').click();
            }
        }
    }

    // Two ways to initialize the slider:

    // 1. If images are loaded immediately
    initImageSlider();

    //2. Or wait for images to be added dynamically
    //Use a MutationObserver to detect when images are added to the container
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length > 0) {
                // Check if we have images and init/reinit the slider
                const $images = $('#ces-preview-container').find('.ces-cbz-image');
                if ($images.length > 0) {
                    // Small timeout to ensure all images are processed
                    setTimeout(initImageSlider, 100);
                }
            }
        });
    });

    // Start observing the container for changes
    prevContainer = document.getElementById('ces-preview-container');
    if (!prevContainer) {
        observer.observe(prevContainer, {
            childList: true,
            subtree: true
        });
    }


    // Preview button click handler
    $('.ces-preview-button').on('click', function () {
        var fileUrl = $(this).data('file');
        var fileType = $(this).data('type');
        var filePath = $(this).data('path');

        // Reset container
        $('#ces-preview-container').empty();

        // Open modal
        $('#ces-preview-modal').show();

        // Initialize appropriate preview based on file type
        if (fileType === 'epub') {
            initEpubReader(fileUrl);
        } else if (fileType === 'cbz' || fileType === 'zip') {
            initCbzViewer(fileUrl, filePath);
        } else {
            $('#ces-preview-container').html('<p>Unsupported file format. Please upload an EPUB or CBZ file.</p>');
        }
    });

    // Close button handler
    $('.ces-close').on('click', function () {
        $('#ces-preview-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function (event) {
        if ($(event.target).is('#ces-preview-modal')) {
            $('#ces-preview-modal').hide();
        }
    });

    // Initialize EPUB reader
    function initEpubReader(fileUrl) {
        // Create container for the viewer
        $('#ces-preview-container').html('<div id="epub-viewer" style="width:100%;height:100%"></div>');

        // Initialize EPUB reader
        var book = ePub(fileUrl);
        var rendition = book.renderTo("epub-viewer", {
            width: "100%",
            height: "100%"
        });

        rendition.display();

        // Navigation buttons
        $('#prev-page').on('click', function (e) {
            rendition.prev();
            e.preventDefault();
        });

        $('#next-page').on('click', function (e) {
            rendition.next();
            e.preventDefault();
        });
    }

    // Initialize CBZ viewer
    function initCbzViewer(fileUrl, filePath) {
        // Show loading indicator
        $('#ces-preview-container').html('<p>Loading CBZ content...</p>');

        // AJAX request to process the CBZ file
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                'action': 'ces_process_cbz',
                'file_path': filePath,
                'nonce': ces_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#ces-preview-container').empty();

                    // Display the CBZ images
                    $.each(response.data, function (index, imageUrl) {
                        $('#ces-preview-container').append('<img class="ces-cbz-image" src="' + imageUrl + '" alt="Page ' + (index + 1) + '">');
                    });
                } else {
                    $('#ces-preview-container').html('<p>Error: ' + response.data + '</p>');
                }
            },
            error: function () {
                $('#ces-preview-container').html('<p>Error processing the CBZ file.</p>');
            }
        });
    }

});