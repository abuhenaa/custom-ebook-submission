/**
 * CES Form JavaScript
 * Handles file uploads, preview functionality, and sortable comic images
 */
(function($) {
    $(document).ready(function() {
        // Handle file type selection to show relevant upload fields
        $('#ces-file-type').on('change', function() {
            // Hide all file upload fields first
            $('.file-upload-field').hide();
            
            // Show the relevant field based on selection
            const selectedType = $(this).val();
            $('#ces-epub-file, #ces-docx-file, #ces-cbz-file, #ces-comic_images-file').prop('required', false);
            
            switch(selectedType) {
                case 'epub':
                    $('#epub-upload-field').show();
                    $('#ces-epub-file').prop('required', true);
                    break;
                case 'docx':
                    $('#docx-upload-field').show();
                    $('#ces-docx-file').prop('required', true);
                    break;
                case 'cbz':
                    $('#cbz-upload-field').show();
                    $('#ces-cbz-file').prop('required', true);
                    break;
                case 'comic_images':
                    $('#comic-images-upload-field').show();
                    $('#ces-comic-images').prop('required', true);
                    break;
            }
        });

        // Initialize with the default selection (EPUB)
        $('#epub-upload-field').show();

        // Handle cover image preview
        $('#ces-cover-image').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#cover-image-preview').html('<img src="' + e.target.result + '" alt="Cover Preview" />');
                }
                reader.readAsDataURL(file);
            } else {
                $('#cover-image-preview').empty();
            }
        });

        $('#submitBtn').on('click', function (e) {          

            const input = $('#ces-tags').val();
            const tags = input.split(',').map(tag => $.trim(tag)).filter(tag => tag.length > 0);

            if (tags.length > 20) {
            $('.tag-notice').text('You can enter a maximum of 20 tags.').css('color', 'red');
             e.preventDefault(); // prevent form submission if inside a form
            }else{
                //clear
                $('.tag-notice').text('');
            }
        });
        // Handle price input validation and VAT calculation
        $('.ces-field #ces-price').on('change', function () {
            let inputVal = $(this).val();

            // Check if the value is a valid number with max 2 decimal places
            if (!/^\d+(\.\d{1,2})?$/.test(inputVal)) {
                 $('.price-notice').text('Please enter a valid price with no more than 2 decimal places.');
                $(this).val(inputVal.toFixed(2));
                $('#ces-vat-price').val('0.00');
                return;
            }else{
                //clear
                $('.price-notice').text('');
            }

            const price = parseFloat(inputVal);
            if (isNaN(price) || price < 0) {
                $(this).val('0.00');
                $('.price-notice').text('Please enter a valid positive price.');
                $('#ces-vat-price').val('0.00');
                return;
            }else{
                //clear
                $('.price-notice').text('');
            }

            // Optionally format the input to 2 decimal places
            $(this).val(price.toFixed(2));

            const vatPrice = price + (price * 5.5 / 100);
            $('#ces-vat-price').val(vatPrice.toFixed(2));
        });


        // Handle comic images upload and preview
        $('#ces-comic-images').on('change', function() {
            const files = this.files;
            const previewContainer = $('#comic-images-preview');
            
            // Clear previous previews
            previewContainer.empty();
            
            if (files.length > 0) {
                // Create elements for each image with draggable functionality
                Array.from(files).forEach(function(file, index) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imageWrapper = $('<div class="comic-image-item" data-index="' + index + '"></div>');
                        const imagePreview = $('<div class="comic-image-preview"><img src="' + e.target.result + '" alt="Comic Image ' + (index + 1) + '" /></div>');
                        const imageInfo = $('<div class="comic-image-info"><span class="comic-image-number">' + (index + 1) + '</span><span class="comic-image-name">' + file.name + '</span></div>');
                        
                        imageWrapper.append(imagePreview);
                        imageWrapper.append(imageInfo);
                        previewContainer.append(imageWrapper);
                    }
                    reader.readAsDataURL(file);
                });

                // Update the order field with initial values
                updateComicImagesOrder();
            }
        });

        // Make comic images sortable
        $('#comic-images-preview').sortable({
            items: '.comic-image-item',
            placeholder: 'comic-image-placeholder',
            cursor: 'move',
            update: function(event, ui) {
                // Renumber the images after sorting
                renumberComicImages();
                
                // Update the hidden field with the new order
                updateComicImagesOrder();
            }
        });

        // Function to renumber the comic images after sorting
        function renumberComicImages() {
            $('#comic-images-preview .comic-image-item').each(function(index) {
                $(this).find('.comic-image-number').text(index + 1);
            });
        }

        // Function to update the hidden field with the current order of images
        function updateComicImagesOrder() {
            const order = [];
            $('#comic-images-preview .comic-image-item').each(function() {
                order.push($(this).data('index'));
            });
            $('#comic-images-order').val(JSON.stringify(order));
        }

        // Form submission handling
        $('.ces-form').on('submit', function(e) {
            // Add any validation if needed
            
            // For comic images, ensure the order is updated before submission
            if ($('#ces-file-type').val() === 'comic_images') {
                updateComicImagesOrder();
            }
        });
    });


    //new author field display
    $('#ces-author').on('change', function() {
        const selectedAuthor = $(this).val();
        if (selectedAuthor === 'new_author') {
            $('#new-author-field').show();
        } else {
            $('#new-author-field').hide();
        }
    });
})(jQuery);