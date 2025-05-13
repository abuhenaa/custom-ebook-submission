<?php

// File: includes/class-file-handler.php

defined('ABSPATH') || exit;

class CES_File_Handler {
    private $product_id;

    public function __construct($product_id) {
        $this->product_id = $product_id;
    }

    public function handle_upload($files) {
        if (!isset($files['ebook_file']) || $files['ebook_file']['error'] !== UPLOAD_ERR_OK) return;
        
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $uploaded = wp_handle_upload($files['ebook_file'], ['test_form' => false]);
        if (isset($uploaded['url'])) {
            $file_url = $uploaded['url'];
            $file_path = $uploaded['file'];
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            switch ($ext) {
                case 'epub':
                    $this->process_epub($file_path);
                    break;
                case 'docx':
                    $this->convert_docx_to_epub($file_path);
                    break;
                case 'cbz':
                    $this->process_cbz($file_path);
                    break;
                case 'zip':
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $this->process_images_or_zip($file_path, $ext);
                    break;
                default:
                    // Handle unsupported formats
                    break;
            }

            update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($file_url));
        }
    }

    private function process_epub($file_path) {
        // Extract metadata (e.g. title) if needed
        // Store file path for preview with EPUB.js
    }

    private function convert_docx_to_epub($file_path) {
        $epub_output = str_replace('.docx', '.epub', $file_path);
        $cmd = "pandoc " . escapeshellarg($file_path) . " -o " . escapeshellarg($epub_output);
        shell_exec($cmd);

        if (file_exists($epub_output)) {
            $epub_url = str_replace(ABSPATH, site_url('/'), $epub_output);
            update_post_meta($this->product_id, '_ces_converted_epub', esc_url_raw($epub_url));
        }
    }

    private function process_cbz($file_path) {
        // Extract cover image and 1 sample page for preview (optional enhancement)
    }

    private function process_images_or_zip($file_path, $ext) {
        // If images were uploaded, create CBZ archive
        // Use ZipArchive to zip images into a .cbz file and store URL
    }
}
