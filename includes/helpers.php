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
    // Hardcoded file path (for testing)
    //$cbz_file_path = "F:\laragon\www\client\wp-content\uploads\books\cbz.cbz";

    if (!file_exists($cbz_file_path)) {
        return '<div class="cbz-error">CBZ file not found</div>';
    }

    // Open ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($cbz_file_path) !== true) {
        return '<div class="cbz-error">Could not open CBZ file</div>';
    }

    // Temp directory for images
    $upload_dir = wp_upload_dir();
    $cbz_temp_dir = trailingslashit($upload_dir['basedir']) . 'cbz_previews/';
    $cbz_temp_url = trailingslashit($upload_dir['baseurl']) . 'cbz_previews/';

    if (!file_exists($cbz_temp_dir)) {
        wp_mkdir_p($cbz_temp_dir);
    }

    // Get all image files in archive
    $image_files = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (preg_match('/(\.jpg|\.jpeg|\.png|\.gif|\.webp)$/i', $filename)) {
            $image_files[] = $filename;
        }
    }

    natcasesort($image_files);
    $image_files = array_values($image_files);
    $output = '<h3>'. __('Preview of The Book','ces').'</h3><div class="cbz-preview-container" id="cbz-gallery">';

    $image_urls = [];

    foreach ($pages_to_show as $page_index) {
        if (isset($image_files[$page_index])) {
            $image_name = $image_files[$page_index];
            $image_basename = basename($image_name);
            $saved_path = $cbz_temp_dir . $image_basename;
            $saved_url = $cbz_temp_url . $image_basename;

            // Save image to temp directory if it doesn't exist
            if (!file_exists($saved_path)) {
                $image_content = $zip->getFromName($image_name);
                file_put_contents($saved_path, $image_content);
            }

            // Save URL to cookie-friendly array
            $image_urls[] = $saved_url;

            // Output image
            $output .= '<div class="cbz-preview-image">';
            $output .= '<img src="' . esc_url($saved_url) . '" alt="Page ' . ($page_index + 1) . '" />';
            $output .= '</div>';
        }
    }

    // Save image URLs to cookie
    //setcookie('cbz_preview_images', json_encode($image_urls), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);

    $output .= '</div>';
    $zip->close();

    return $output;
}
