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
            $('#ces-epub-file, #ces-docx-file, #ces-cbz-file, #ces-comic-images').prop('required', false);

            $('#ces-preview-btn').removeClass('ces-convert-btn');
            $('.ces-convert-btn').attr('id', 'ces-preview-btn');
            $('#ces-preview-btn').text(ces_ajax.strings.preview_ebook);
            switch(selectedType) {
                case 'epub':
                    $('#epub-upload-field').show();
                    $('#ces-epub-file').prop('required', true);
                    $('.ces-docx-prev-btn').hide();
                    $('#ces-preview-btn').parent().show();
                    break;
                case 'docx':
                    $('#docx-upload-field').show();
                    $('#ces-docx-file').prop('required', true);
                    $('#ces-preview-btn').text(ces_ajax.strings.convert_into_epub);
                    $('#ces-preview-btn').addClass('ces-convert-btn');
                    break;
                case 'cbz':
                    $('#cbz-upload-field').show();
                    $('#ces-cbz-file').prop('required', true);
                    $('.ces-docx-prev-btn').hide();                    
                    $('#ces-preview-btn').parent().show();
                    break;
                case 'comic_images':
                    $('#comic-images-upload-field').show();
                    $('#ces-comic-images').prop('required', true);
                    $('.ces-docx-prev-btn').hide();                    
                    $('#ces-preview-btn').parent().show();
                    break;
            }
        });

        // Initialize with the default selection (EPUB)
        $('#epub-upload-field').show();

// Handle cover image preview and validate dimensions
$('#ces-cover-image').on('change', function () {
    const file = this.files[0];
    const $preview = $('#cover-image-preview');
    const $error = $('#cover-image-error');

    $preview.empty();
    $error.remove(); // remove old error

    if (file) {
        const reader = new FileReader();

        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                const width = img.width;
                const height = img.height;
                const ratio = width / height;
                const expectedRatio = 2 / 3;
                const tolerance = 0.5;

                const isValid =
                    Math.abs(ratio - expectedRatio) < tolerance;

                if (!isValid) {
                    $('<div id="cover-image-error" style="color: red; margin-top: 5px;">' + ces_ajax.strings.please_upload_image_ratio + '</div>')
                        .insertAfter($preview);
                    $('#ces-cover-image').val('');
                    return;
                }

                // Valid image — show preview
                $preview.html('<img src="' + e.target.result + '" alt="Cover Preview" style="max-height: 150px;" />');
            };

            img.onerror = function () {
                $('<div id="cover-image-error" style="color: red; margin-top: 5px;">' + ces_ajax.strings.invalid_image_file + '</div>')
                    .insertAfter($preview);
                $('#ces-cover-image').val('');
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
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
        
// Common function to sanitize and validate price input
function sanitizePrice(element) {
    let inputVal = element.val();
    let cursorPos = element[0].selectionStart;
    let dotAdded = false;
    
    // Store original length
    const originalLength = inputVal.length;
    
    // Replace any commas with dots
    if (inputVal.indexOf(',') !== -1) {
        const commaPos = inputVal.indexOf(',');
        inputVal = inputVal.replace(/,/g, '.');
        if (cursorPos > commaPos) {
            dotAdded = true;
        }
    }
    
    // Allow only one decimal point
    const firstDotPos = inputVal.indexOf('.');
    if (firstDotPos !== -1 && firstDotPos !== inputVal.lastIndexOf('.')) {
        const newVal = inputVal.substring(0, firstDotPos + 1) + 
                       inputVal.substring(firstDotPos + 1).replace(/\./g, '');
        inputVal = newVal;
    }
    
    // Limit to 2 decimal places
    if (firstDotPos !== -1 && inputVal.length > firstDotPos + 3) {
        inputVal = inputVal.substring(0, firstDotPos + 3);
    }
    
    // Only update if value changed to avoid cursor jumping
    if (element.val() !== inputVal) {
        element.val(inputVal);
        
        // Restore cursor position
        if (dotAdded) {
            cursorPos = Math.min(cursorPos, inputVal.length);
            element[0].setSelectionRange(cursorPos, cursorPos);
        }
    }
    
    // If empty, reset and return null
    if (!inputVal) {
        $('.price-notice').text('');
        return null;
    }
    
    // Allow partial input like "2." during typing
    if (/^\d+\.?$/.test(inputVal)) {
        $('.price-notice').text('');
        return parseFloat(inputVal || 0);
    }
    
    // Check if the value is a valid number with max 2 decimal places
    if (!/^\d+(\.\d{1,2})?$/.test(inputVal)) {
        $('.price-notice').text('Please enter a valid price with no more than 2 decimal places.');
        return null;
    } else {
        // Clear error message
        $('.price-notice').text('');
    }
    
    const price = parseFloat(inputVal);
    if (isNaN(price) || price < 0) {
        element.val('0.00');
        $('.price-notice').text('Please enter a valid positive price.');
        return null;
    }
    
    return price;
}

// Format price on blur
function setBlurHandler(element) {
    element.off('blur').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
}

// VAT rate
const VAT_RATE = 5.5;

// Handle price without VAT input
$('.ces-field #ces-price').on('input change', function() {
    const price = sanitizePrice($(this));
    if (price === null) {
        $('#ces-vat-price').val('0.00');
        return;
    }
    
    // Calculate price with VAT
    const priceWithVat = price * (1 + (VAT_RATE / 100));
    $('#ces-vat-price').val(priceWithVat.toFixed(2));
    
    setBlurHandler($(this));
});

// Handle price with VAT input
$('.ces-field #ces-vat-price').on('input change', function() {
    const priceWithVat = sanitizePrice($(this));
    if (priceWithVat === null) {
        $('#ces-price').val('0.00');
        return;
    }
    
    // Calculate price without VAT
    const priceWithoutVat = priceWithVat / (1 + (VAT_RATE / 100));
    $('#ces-price').val(priceWithoutVat.toFixed(2));
    
    setBlurHandler($(this));
});
// Initialize the sortable functionality
$('#comic-images-preview').sortable({
    items: '.comic-image-item',
    placeholder: 'comic-image-placeholder',
    cursor: 'move',
    update: function(event, ui) {
        // Renumber the images after sorting
        renumberComicImages();
        
        // Update the hidden field with the new order
        updateComicImagesOrder();
        
        // Update the file input with the new order
        updateFileInput();
        
        // Check and update required attribute
        checkComicImagesAndUpdateRequired();
    }
});

// Store files globally to maintain references
let uploadedFiles = [];
let fileCounter = 0;

// Trigger file input when clicking on the dropzone (except when clicking on existing images)
$('#comic-images-preview').on('click', function(e) {
    if (!$(e.target).closest('.comic-image-item').length) {
        $('#ces-comic-images').click();
    }
});

// Handle file selection via the file input
$('#ces-comic-images').on('change', function(e) {
    const files = e.target.files;
    if (files.length > 0) {
        // Don't clear existing files - just add new ones
        handleFiles(files);
    }
});

function checkComicImagesAndUpdateRequired() {
    var orderValue = $('#comic-images-order').val();
    var isEmpty = (orderValue === '' || orderValue === '[]');
    
    if (isEmpty) {
        $('#ces-comic-images').attr('required', 'required');
    } else {
        $('#ces-comic-images').removeAttr('required');
    }
}

// Drag and drop events for the dropzone
const dropZone = document.getElementById('comic-images-preview');

// Prevent default behavior for drag events
if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight the dropzone when dragging over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('drag-over');
    }

    function unhighlight() {
        dropZone.classList.remove('drag-over');
    }

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        handleFiles(files);
    }
}

// Process the files (both from input and drop)
function handleFiles(files) {
    // Remove the dropzone message once files are added
    $('.dropzone-message').hide();
    
    // Process each file
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const currentIndex = fileCounter++;
            uploadedFiles.push({ file, index: currentIndex });
            
            // Create image preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageItem = $(`
                    <div class="comic-image-item" data-index="${currentIndex}">
                        <div class="comic-image-number">${$('#comic-images-preview .comic-image-item').length + 1}</div>
                        <div class="comic-image-preview">
                            <img src="${e.target.result}" alt="Comic Image Preview" />
                        </div>
                        <div class="comic-image-actions">
                            <button type="button" class="remove-comic-image" data-index="${currentIndex}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                `);
                
                $('#comic-images-preview').append(imageItem);
                updateComicImagesOrder();
                
                // Update the file input with all files
                updateFileInput();
            }
            reader.readAsDataURL(file);
        }
    });
}

// Handle removal of images
$(document).on('click', '.remove-comic-image', function() {
    const index = $(this).data('index');
    
    // Remove the file from our array
    uploadedFiles = uploadedFiles.filter(item => item.index !== index);
    
    // Remove the item from the DOM
    $(this).closest('.comic-image-item').remove();
    
    // If no images left, show the dropzone message again
    if ($('#comic-images-preview .comic-image-item').length === 0) {
        $('.dropzone-message').show();
    }
    
    // Renumber and update order
    renumberComicImages();
    updateComicImagesOrder();
    
    // Update the file input
    updateFileInput();
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

    checkComicImagesAndUpdateRequired();
}

// Function to update the file input with the current files in the correct order
function updateFileInput() {
    const fileInput = document.getElementById('ces-comic-images');
    const dt = new DataTransfer();
    
    // Get current order
    const order = JSON.parse($('#comic-images-order').val() || '[]');
    
    // Add files in the correct order
    order.forEach(index => {
        const fileObj = uploadedFiles.find(item => item.index === index);
        if (fileObj && fileObj.file) {
            dt.items.add(fileObj.file);
        }
    });
    
    // Update the file input's files
    fileInput.files = dt.files;
}

// Form submission handling - simplified since files are now in the input
$('.ces-form').on('submit', function(e) {
    if ($('#ces-file-type').val() === 'comic_images') {
        // Ensure the file input is updated with the correct order before submission
        updateFileInput();
        updateComicImagesOrder();
    }
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

    $('#ces-author').select2({
        placeholder: ces_ajax.strings.search_for_author,
        allowClear: true,
        width: '100%', // Make it responsive
        minimumInputLength: 0, // Start searching immediately
        escapeMarkup: function(markup) {
            return markup; // Allow HTML in options if needed
        },
        templateResult: function(option) {
            // Custom formatting for dropdown options
            if (!option.id) {
                return option.text;
            }
            
            // Highlight "Add New Author" option differently
            if (option.id === 'new_author') {
                return $('<span class="new-author-option"><i class="fa fa-plus"></i> ' + option.text + '</span>');
            }
            
            return option.text;
        },
        templateSelection: function(option) {
            // Custom formatting for selected option
            return option.text;
        }
    });

    // Initialize the subcategory population on page load if main category is already selected
    var mainCategorySelect = $('#ces-main-category');
    var subcategorySelect = $('#ces-subcategory');
    
    // Load subcategories when main category changes
    mainCategorySelect.on('change', function() {
        var parentId = $(this).val();
        
        if (!parentId) {
            // If no category selected, disable and reset subcategory select
            subcategorySelect.prop('disabled', true);
            subcategorySelect.html('<option value="">' + ces_ajax.strings.select_main_category_first + '</option>');
            return;
        }
        
        // Show loading state
        subcategorySelect.html('<option value="">' + ces_ajax.strings.loading + '</option>');
        
        // Fetch subcategories via AJAX
        $.ajax({
            url: ces_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ces_get_subcategories',
                parent_id: parentId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Enable the subcategory select and populate options
                    subcategorySelect.prop('disabled', false);
                    
                    var options = '<option value="">' + ces_ajax.strings.select_subcategory + '</option>';
                    $.each(response.data, function(index, subcat) {
                        options += '<option value="' + subcat.id + '">' + subcat.name + '</option>';
                    });
                    
                    subcategorySelect.html(options);
                } else {
                    // No subcategories available
                    subcategorySelect.prop('disabled', true);
                    subcategorySelect.html('<option value="">' + ces_ajax.strings.no_subcategories_available + '</option>');
                }
            },
            error: function() {
                // Error handling
                subcategorySelect.prop('disabled', true);
                subcategorySelect.html('<option value="">' + ces_ajax.strings.error_loading_subcategories + '</option>');
            }
        });
    });
    
    // If there's a pre-selected main category (like on form edit)
    if (mainCategorySelect.val()) {
        mainCategorySelect.trigger('change');
    }

});
    
})(jQuery)