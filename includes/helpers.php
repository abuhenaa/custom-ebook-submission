<?php

defined('ABSPATH') || exit;

function ces_get_main_categories() {
    $uncat = get_term_by('slug', 'uncategorized', 'product_cat');
    $uncat_id = $uncat ? $uncat->term_id : null;
    return get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0,
        'orderby'    => 'name',
        // remove uncategorized category
        'exclude'    => [$uncat_id],
    ]);
}

//get author custom taxonomy
function ces_get_authors() {
    $args = [
        'taxonomy'   => 'books-author',
        'hide_empty' => false,
        'orderby'    => 'name',
    ];
    return get_terms($args);
}


/**
 * Function to display a preview of the first two pages of a CBZ file
 *
 * @param string $cbz_file_path Path to the CBZ file
 * @param array $pages_to_show Array of page indices to show (default: [0, 1])
 * @return string HTML output for the preview
 */
function ces_display_cbz_preview_pages($cbz_file_path, $pages_to_show = [0, 1]) {
    // Verify file exists
    if (!file_exists($cbz_file_path)) {
        return '<div class="cbz-error">CBZ file not found</div>';
    }
    
    // Open the CBZ file (which is actually a ZIP file)
    $zip = new ZipArchive();
    if ($zip->open($cbz_file_path) !== true) {
        return '<div class="cbz-error">Could not open CBZ file</div>';
    }
    
    // Get all image files in the archive
    $image_files = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        // Filter for image files
        if (preg_match('/(\.jpg|\.jpeg|\.png|\.gif|\.webp)$/i', $filename)) {
            $image_files[] = $filename;
        }
    }
    
    // Sort image files (they are often named numerically)
    natcasesort($image_files);
    $image_files = array_values($image_files);
    $output = '<h3>Preview of Book</h3>';
    $output .= '<p>Click on the images to view them in full size.</p>';
    // Prepare output HTML
    $output .= '<div class="cbz-preview-container woocommerce-product-gallery__wrapper" id="cbz-gallery">';

    // Extract and display only the requested pages
    foreach ($pages_to_show as $page_index) {
        if (isset($image_files[$page_index])) {
            $image_name = $image_files[$page_index];
            
            // Extract image content
            $image_content = $zip->getFromName($image_name);
            
            // Convert to base64 for direct display
            $image_type = pathinfo($image_name, PATHINFO_EXTENSION);
            $base64 = base64_encode($image_content);
            $data_uri = "data:image/{$image_type};base64,{$base64}";
            
            // Use WooCommerce's standard gallery item structure
            $output .= sprintf(
                '<div class="woocommerce-product-gallery__image cbz-page-wrapper">
                    <a href="%1$s" 
                    class="cbz-page-link woocommerce-product-gallery__image" 
                    data-large_image="%1$s" 
                    data-large_image_width="1024" 
                    data-large_image_height="768">
                        <div class="cbz-page">
                            <img src="%1$s" alt="Page %2$d" class="wp-post-image" />
                        </div>
                    </a>
                </div>',
                $data_uri,
                $page_index + 1
            );
        }
    }

    $output .= '</div>';

    // Add script to activate PhotoSwipe for our CBZ images
    $output .= '<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Make sure CBZ images work with the existing WooCommerce PhotoSwipe implementation
        $("#cbz-gallery").on("click", ".cbz-page-link", function(e) {
            e.preventDefault();
            
            // If WooCommerce\'s PhotoSwipe trigger function exists, use it
            if (typeof wc_single_product_params !== "undefined" && 
                $(".woocommerce-product-gallery").data("photoswipe") && 
                typeof $(".woocommerce-product-gallery").data("photoswipe").openPhotoswipe === "function") {
                    
                // Create an event object similar to what WooCommerce expects
                var clickEvent = $.Event("click");
                clickEvent.target = $(this)[0];
                
                // Trigger WooCommerce\'s PhotoSwipe
                $(".woocommerce-product-gallery").data("photoswipe").openPhotoswipe(clickEvent);
            }
        });
    });
    </script>';

    return $output;
    
    // Close the ZIP archive
    $zip->close();
    
    return $output;
}
