<?php

defined('ABSPATH') || exit;

function ces_get_main_categories() {
    if( ! class_exists('WooCommerce')) return;
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

/**
 * Function to get subcategories by parent ID
 */
function ces_get_subcategories($parent_id) {
    return get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
        'orderby'    => 'name',
    ]);
}

//get author custom taxonomy
function ces_get_authors() {
    $args = [
        'taxonomy'   => 'books-author',
        'hide_empty' => false,
        'orderby'    => 'name',
        'meta_query' => [
            [
                'key'     => 'vendor_id',
                'value'   => get_current_user_id(),
                'compare' => '=',
            ],
        ],
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
function ces_display_cbz_preview_pages($cbz_file_path) {    

    if (!file_exists($cbz_file_path)) {
        return '<div class="cbz-error">' . __('CBZ file not found', 'ces') . '</div>';
    }

    // Open ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($cbz_file_path) !== true) {
        return '<div class="cbz-error">' . __('Could not open CBZ file', 'ces') . '</div>';
    }

    // Get all image files in archive
    $image_files = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (preg_match('/(\.jpg|\.jpeg|\.png|\.gif|\.webp)$/i', $filename)) {
            $image_files[] = $filename;
        }
    }

    // Improved sorting function for numeric filenames
    usort($image_files, function($a, $b) {
        // Extract just the filename without path
        $a_basename = basename($a);
        $b_basename = basename($b);
        
        // Extract numeric part from filename (e.g., "001" from "001.jpg")
        preg_match('/(\d+)/', $a_basename, $matches_a);
        preg_match('/(\d+)/', $b_basename, $matches_b);
        
        $num_a = isset($matches_a[1]) ? intval($matches_a[1]) : 0;
        $num_b = isset($matches_b[1]) ? intval($matches_b[1]) : 0;
        
        // If both have numbers, sort by number
        if ($num_a && $num_b) {
            return $num_a - $num_b;
        }
        
        // Fallback to natural case-insensitive sort
        return strnatcasecmp($a_basename, $b_basename);
    });

    $output = '<h3 class="ces-preview-title">'. __('Preview of The Book','ces').'</h3><div id="ces-preview-container">';
    $output .= '<div class="ces-slider-navigation">';
    $output .= '<button id="prev-page" class="ces-slider-button"><i class="fas fa-chevron-left"></i></button>';
    $output .= '<button id="next-page" class="ces-slider-button"><i class="fas fa-chevron-right"></i></button>';
    $output .= '</div>';
    
    $output .= '<div class="ces-cbz-image-wrapper">';

    // If user vendor or admin or purchased product
    if (current_user_can('administrator') || current_user_can('vendor')) {
        $display_images = $image_files; // Show all images
    } else {
        $settings = CES_Settings::get_instance();
        $preview_limit = $settings->get_preview_limit();
        $display_images = array_slice($image_files, 0, $preview_limit); // Limit to preview limit
    }
    
    foreach ($display_images as $image) {
        // Read image content directly from ZIP
        $image_content = $zip->getFromName($image);
        
        if ($image_content !== false) {
            // Get file extension to determine MIME type
            $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            $mime_type = '';
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $mime_type = 'image/jpeg';
                    break;
                case 'png':
                    $mime_type = 'image/png';
                    break;
                case 'gif':
                    $mime_type = 'image/gif';
                    break;
                case 'webp':
                    $mime_type = 'image/webp';
                    break;
                default:
                    $mime_type = 'image/jpeg'; // fallback
            }
            
            // Create data URI
            $base64_image = base64_encode($image_content);
            $data_uri = 'data:' . $mime_type . ';base64,' . $base64_image;
            
            // Output image with data URI
            $output .= '<div class="ces-cbz-image">';
            $output .= '<img src="' . $data_uri . '" alt="Page ' . (array_search($image, $image_files) + 1) . '" />';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    $output .= '</div>';
    $zip->close();

    return $output;
}
