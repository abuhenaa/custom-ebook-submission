<?php

// File: includes/class-file-handler.php

defined( 'ABSPATH' ) || exit;

class CES_File_Handler{
    private $product_id;

    public function __construct( $product_id )    {
        $this->product_id = $product_id;
    }

    /**
     * Handle uploads from the enhanced form with multiple file fields
     * 
     * @param array $files The $_FILES array
     * @param string $file_type The selected file type from the form
     * @return array|null Processed file data or null on failure
     */
    public function handle_upload( $files, $file_type = '' ) {
        // Determine which file field to process based on file_type
        $file_key = $this->get_file_key_from_type($file_type);
        
        // Check if we have a valid file to process
        if (!isset($files[$file_key]) || 
            ($file_key !== 'comic_images' && $files[$file_key]['error'] !== UPLOAD_ERR_OK)) {
            return null;
        }

        $processed = null;
        // Process based on file type
        switch ($file_type) {
            case 'epub':
                $processed = $this->handle_epub_upload($files[$file_key]);
                break;
            case 'cbz':
                $processed = $this->handle_cbz_upload($files[$file_key]);
                break;
            case 'comic_images':
                $processed = $this->handle_comic_images_upload($files[$file_key], $files);
                break;
            default:
                // For backwards compatibility, try to detect file type from extension
                if (!empty($files['ebook_file']) && $files['ebook_file']['error'] === UPLOAD_ERR_OK) {
                    return $this->legacy_handle_upload($files);
                }
        }

        //update ebook file URL in product meta
        if (isset($processed['file_url'])) {
            update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($processed['file_url']));
            update_post_meta($this->product_id, '_ces_ebook_file_path', $processed['file_path']);
            update_post_meta($this->product_id, '_ces_ebook_title', sanitize_text_field($processed['metadata']['title'] ?? ''));
            update_post_meta($this->product_id, '_ces_ebook_author', sanitize_text_field($processed['metadata']['author'] ?? ''));
        }

         //add file url to the downloadable files array
         $downloadable_files = [
            'ces_ebook_file' => [
                'name' => sanitize_text_field($processed['metadata']['title'] ?? __('Ebook File', 'ces')),
                'file' => esc_url_raw($processed['file_url'])
            ]
        ];
        update_post_meta($this->product_id, '_downloadable_files', $downloadable_files);

    }

    /**
     * Maps file type selection to the corresponding form field name
     */
    private function get_file_key_from_type($file_type) {
        switch ($file_type) {
            case 'epub':
                return 'epub_file';
            case 'docx':
                return 'docx_file';
            case 'cbz':
                return 'cbz_file';
            case 'comic_images':
                return 'comic_images';
            default:
                return 'ebook_file'; // Legacy field name
        }
    }

    /**
     * Handle EPUB file upload
     */
    private function handle_epub_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($uploaded['url'])) {
            $processed = $this->process_epub($uploaded['file']);
        }
        return $processed;
        
    }

    /**
     * Handle CBZ file upload
     */
    private function handle_cbz_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($uploaded['url'])) {
            $processed = $this->process_cbz($uploaded['file']);
        }

        return $processed;
    }

    /**
     * Handle multiple comic images upload
     */
    private function handle_comic_images_upload($files, $all_files) {
        // Check if we have at least one image
        if (empty($files['name'][0])) {
            return null;
        }
        
        // Get image order if provided
        $image_order = isset($_POST['comic_images_order']) ? json_decode(stripslashes($_POST['comic_images_order']), true) : null;
        
        // Get product title for filename
        $product_title = get_the_title($this->product_id);
        if (empty($product_title)) {
            $product_title = 'comic-' . $this->product_id;
        }
        
        // Create CBZ from uploaded images
        $processed =  $this->create_cbz_from_images($files, $product_title, $image_order);
        return $processed;
    }

    /**
     * Legacy method to maintain backward compatibility
     */
    private function legacy_handle_upload( $files ) {
        if ( !isset( $files[ 'ebook_file' ] ) || $files[ 'ebook_file' ][ 'error' ] !== UPLOAD_ERR_OK ) {
            return;
        }

        if ( !function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $uploaded = wp_handle_upload( $files[ 'ebook_file' ], [ 'test_form' => false ] );
        
        if ( isset( $uploaded[ 'url' ] ) ) {
            $file_path = $uploaded[ 'file' ];
            $ext       = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

            $processed = null;
            switch ( $ext ) {
                case 'epub':
                    $processed = $this->process_epub( $file_path );
                    break;
                case 'docx':
                    $processed = $this->convert_docx_to_epub( $file_path );
                    break;
                case 'cbz':
                    $processed = $this->process_cbz( $file_path );
                    break;
                case 'zip':
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $processed = $this->process_images_or_zip( $file_path, $ext );
                    break;
                default:
                    // Handle unsupported formats
                    break;
            }

            if ($processed) {
                update_post_meta( $this->product_id, '_ces_ebook_file', esc_url_raw( $processed[ 'file_url' ] ) );
                // title meta
                update_post_meta( $this->product_id, '_ces_ebook_title', sanitize_text_field( $processed[ 'metadata' ][ 'title' ] ?? '' ) );
                // author meta
                update_post_meta( $this->product_id, '_ces_ebook_author', sanitize_text_field( $processed[ 'metadata' ][ 'author' ] ?? '' ) );
                
                return $processed;
            }
        }
        
        return null;
    }

    /**
     * Create a CBZ file from multiple uploaded images
     */
    private function create_cbz_from_images($files, $title, $image_order = null) {
        // Set up temporary directory for images
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/temp-cbz-' . uniqid();
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Create books directory if it doesn't exist
        $books_dir = $upload_dir['basedir'] . '/books';
        if (!file_exists($books_dir)) {
            wp_mkdir_p($books_dir);
        }
        
        // Prepare file names array for ordering
        $image_files = [];
        $file_count = count($files['name']);
        // Move uploaded files to temp directory
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $name = sanitize_file_name($files['name'][$i]);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Only process image files
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $dest_filename = sprintf('%03d.%s', $i + 1, $ext);
                    $dest_path = $temp_dir . '/' . $dest_filename;
                    
                    if (move_uploaded_file($tmp_name, $dest_path)) {
                        $image_files[$i] = [
                            'original_name' => $name,
                            'temp_path' => $dest_path,
                            'index' => $i
                        ];
                    }
                }
            }
        }
        
        // Rename files sequentially based on their new order
        foreach ($image_files as $index => $file_info) {
            $ext = strtolower(pathinfo($file_info['temp_path'], PATHINFO_EXTENSION));
            $new_filename = sprintf('%03d.%s', $index + 1, $ext);
            $new_path = $temp_dir . '/' . $new_filename;
            
            // Rename if the filename has changed
            if ($file_info['temp_path'] !== $new_path) {
                rename($file_info['temp_path'], $new_path);
                $image_files[$index]['temp_path'] = $new_path;
            }
        }
        
        // Create sanitized filename for the CBZ
        $sanitized_title = sanitize_title($title);
        $cbz_filename = $sanitized_title . '.cbz';
        $cbz_path = $books_dir . '/' . $cbz_filename;
        
        // Create the ZIP (CBZ) file
        $zip = new ZipArchive();
        if ($zip->open($cbz_path, ZipArchive::CREATE) !== true) {
            return null;
        }
        
        // Add all images to the ZIP
        foreach (glob($temp_dir . '/*') as $file) {
            $filename = basename($file);
            $zip->addFile($file, $filename);
        }
        
        $zip->close();
        
        // Clean up the temp directory
        foreach (glob($temp_dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($temp_dir);
        
        // Extract basic metadata (first image as cover, etc.)
        $metadata = [
            'title' => $title,
            'author' => get_the_author_meta('display_name', get_post_field('post_author', $this->product_id)) ?: '',
        ];
        
        // Save the CBZ file URL to product meta
        $cbz_url = $upload_dir['baseurl'] . '/books/' . $cbz_filename;
        update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($cbz_url));
        update_post_meta($this->product_id, '_ces_ebook_file_path', $cbz_path);
        update_post_meta($this->product_id, '_ces_ebook_title', sanitize_text_field($metadata['title']));
        update_post_meta($this->product_id, '_ces_ebook_author', sanitize_text_field($metadata['author']));
        
        // Also save the image count and original image information
        update_post_meta($this->product_id, '_ces_comic_image_count', count($image_files));
        
        $original_names = [];
        foreach ($image_files as $file) {
            $original_names[] = $file['original_name'];
        }
        update_post_meta($this->product_id, '_ces_comic_original_filenames', $original_names);
        
        return [
            'file_url' => $cbz_url,
            'file_path' => $cbz_path,
            'metadata' => $metadata,
        ];
    }

    private function process_epub( $file_path )
    {
        // Set target directory inside uploads/books/
        $upload_dir = wp_upload_dir();
        $books_dir  = $upload_dir[ 'basedir' ] . '/books';

        // Create books folder if not exists
        if ( !file_exists( $books_dir ) ) {
            wp_mkdir_p( $books_dir );
        }

        // Move the file to /uploads/books/
        $filename    = basename( $file_path );
        $destination = $books_dir . '/' . $filename;        

        if ( !rename( $file_path, $destination ) ) {
            return new WP_Error( 'epub_move_failed', 'Failed to move EPUB to books folder.' );
        }

        // Extract metadata from the EPUB file (optional, basic)
        $zip      = new ZipArchive;
        $metadata = [  ];

        if ( $zip->open( $destination ) === true ) {
            // Find the container.xml which tells where the OPF file is
            $container_xml = $zip->getFromName( 'META-INF/container.xml' );
            if ( $container_xml ) {
                $container = simplexml_load_string( $container_xml );
                $opf_path  = (string) $container->rootfiles->rootfile[ 'full-path' ];

                // Load the OPF file (usually contains title, creator, etc.)
                $opf_data = $zip->getFromName( $opf_path );
                if ( $opf_data ) {
                    $opf = simplexml_load_string( $opf_data );
                    $opf->registerXPathNamespace( 'dc', 'http://purl.org/dc/elements/1.1/' );

                    $title   = $opf->xpath( '//dc:title' );
                    $creator = $opf->xpath( '//dc:creator' );

                    $metadata[ 'title' ]  = isset( $title[ 0 ] ) ? (string) $title[ 0 ] : '';
                    $metadata[ 'author' ] = isset( $creator[ 0 ] ) ? (string) $creator[ 0 ] : '';
                }
            }

            $zip->close();
        }

        return [
            'file_url'  => $upload_dir[ 'baseurl' ] . '/books/' . $filename,
            'file_path' => $destination,
            'metadata'  => $metadata,
         ];
    }

    private function process_cbz( $file_path )
    {
        $upload_dir = wp_upload_dir();
        $books_dir  = $upload_dir[ 'basedir' ] . '/books';

        // Create books folder if not exists
        if ( !file_exists( $books_dir ) ) {
            wp_mkdir_p( $books_dir );
        }

        // Move the file to /uploads/books/
        $filename    = basename( $file_path );
        $destination = $books_dir . '/' . $filename;

        if ( !rename( $file_path, $destination ) ) {
            return new WP_Error( 'cbz_move_failed', 'Failed to move CBZ to books folder.' );
        }

        // Basic metadata
        $title = get_the_title($this->product_id) ?: pathinfo($filename, PATHINFO_FILENAME);
        $author = get_the_author_meta('display_name', get_post_field('post_author', $this->product_id)) ?: '';
        
        $metadata = [
            'title' => $title,
            'author' => $author
        ];

        // Save file metadata
        update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($upload_dir['baseurl'] . '/books/' . basename($destination)));
        update_post_meta($this->product_id, '_ces_ebook_file_path', $destination);
        update_post_meta($this->product_id, '_ces_ebook_title', sanitize_text_field($metadata['title']));
        update_post_meta($this->product_id, '_ces_ebook_author', sanitize_text_field($metadata['author']));

        return [
            'file_url' => $upload_dir['baseurl'] . '/books/' . basename($destination),
            'file_path' => $destination,
            'metadata' => $metadata,
        ];
    }

    private function process_images_or_zip( $file_path, $ext )
    {
        // For backwards compatibility with the old method
        if ($ext === 'zip') {
            // Handle ZIP file - extract images and create CBZ
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/temp-zip-' . uniqid();
            
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            // Extract ZIP contents
            $zip = new ZipArchive();
            if ($zip->open($file_path) === true) {
                $zip->extractTo($temp_dir);
                $zip->close();
                
                // Get all images from the extracted folder
                $images = [];
                foreach (glob($temp_dir . '/*') as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $images[] = $file;
                    }
                }
                
                // Sort images by name
                sort($images);
                
                if (!empty($images)) {
                    // Create a CBZ file
                    $title = get_the_title($this->product_id) ?: 'comic-' . $this->product_id;
                    $cbz_result = $this->create_cbz_from_extracted_images($images, $title);
                    
                    // Clean up
                    foreach (glob($temp_dir . '/*') as $file) {
                        unlink($file);
                    }
                    rmdir($temp_dir);
                    
                    return $cbz_result;
                }
                
                // Clean up if no images found
                foreach (glob($temp_dir . '/*') as $file) {
                    unlink($file);
                }
                rmdir($temp_dir);
            }
        } else if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Handle single image file - legacy case, should be handled by new comic_images field
            // Just move the image to the uploads folder
            $upload_dir = wp_upload_dir();
            $dest_path = $upload_dir['basedir'] . '/books/' . basename($file_path);
            
            if (!file_exists(dirname($dest_path))) {
                wp_mkdir_p(dirname($dest_path));
            }
            
            if (rename($file_path, $dest_path)) {
                $title = get_the_title($this->product_id) ?: pathinfo(basename($file_path), PATHINFO_FILENAME);
                $author = get_the_author_meta('display_name', get_post_field('post_author', $this->product_id)) ?: '';
                
                $metadata = [
                    'title' => $title,
                    'author' => $author
                ];
                
                return [
                    'file_url' => $upload_dir['baseurl'] . '/books/' . basename($file_path),
                    'file_path' => $dest_path,
                    'metadata' => $metadata
                ];
            }
        }
        
        return null;
    }

    /**
     * Helper method to create CBZ from extracted images
     */
    private function create_cbz_from_extracted_images($images, $title) {
        $upload_dir = wp_upload_dir();
        $books_dir = $upload_dir['basedir'] . '/books';
        
        // Create books directory if needed
        if (!file_exists($books_dir)) {
            wp_mkdir_p($books_dir);
        }
        
        // Create the CBZ file
        $sanitized_title = sanitize_title($title);
        $cbz_filename = $sanitized_title . '.cbz';
        $cbz_path = $books_dir . '/' . $cbz_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($cbz_path, ZipArchive::CREATE) !== true) {
            return null;
        }
        
        // Add images to the zip with sequential numbering
        foreach ($images as $index => $image_path) {
            $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
            $new_name = sprintf('%03d.%s', $index + 1, $ext);
            
            $zip->addFile($image_path, $new_name);
        }
        
        $zip->close();
        
        // Basic metadata
        $metadata = [
            'title' => $title,
            'author' => get_the_author_meta('display_name', get_post_field('post_author', $this->product_id)) ?: ''
        ];
        
        // Save file metadata
        update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($upload_dir['baseurl'] . '/books/' . $cbz_filename));
        update_post_meta($this->product_id, '_ces_ebook_file_path', $cbz_path);
        update_post_meta($this->product_id, '_ces_ebook_title', sanitize_text_field($metadata['title']));
        update_post_meta($this->product_id, '_ces_ebook_author', sanitize_text_field($metadata['author']));
        
        return [
            'file_url' => $upload_dir['baseurl'] . '/books/' . $cbz_filename,
            'file_path' => $cbz_path,
            'metadata' => $metadata
        ];
    }
}