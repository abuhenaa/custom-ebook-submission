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

            $processed = null;
            switch ($ext) {
                case 'epub':
                    $processed = $this->process_epub($file_path);
                    break;
                case 'docx':
                    $processed = $this->convert_docx_to_epub($file_path);
                    break;
                case 'cbz':
                    $processed = $this->process_cbz($file_path);
                    break;
                case 'zip':
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $processed = $this->process_images_or_zip($file_path, $ext);
                    break;
                default:
                    // Handle unsupported formats
                    break;
            }

            update_post_meta($this->product_id, '_ces_ebook_file', esc_url_raw($processed['file_url']));
            //title meta
            update_post_meta($this->product_id, '_ces_ebook_title', sanitize_text_field($processed['metadata']['title'] ?? ''));
            //author meta
            update_post_meta($this->product_id, '_ces_ebook_author', sanitize_text_field($processed['metadata']['author'] ?? ''));
        }
    }

    private function process_epub($file_path) {
       // Set target directory inside uploads/books/
       $upload_dir = wp_upload_dir();
       $books_dir = $upload_dir['basedir']. '/books';

        // Create books folder if not exists
        if( ! file_exists( $books_dir )){
            wp_mkdir_p( $books_dir );
        }

        // Move the file to /uploads/books/
        $filename = basename( $file_path );
        $destination = $books_dir . '/' . $filename;

        if ( ! rename($file_path, $destination) ) {
            return new WP_Error('epub_move_failed', 'Failed to move EPUB to books folder.');
        }

        // Extract metadata from the EPUB file (optional, basic)
        $zip = new ZipArchive;
        $metadata = [];

        if( $zip->open( $destination ) === TRUE){
             // Find the container.xml which tells where the OPF file is
            $container_xml = $zip->getFromName('META-INF/container.xml');
            if( $container_xml ){
                $container = simplexml_load_string( $container_xml );
                $opf_path = (string) $container->rootfiles->rootfile['full-path'];

                // Load the OPF file (usually contains title, creator, etc.)
                $opf_data = $zip->getFromName($opf_path);
                if ($opf_data) {
                    $opf = simplexml_load_string($opf_data);
                    $opf->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

                    $title = $opf->xpath('//dc:title');
                    $creator = $opf->xpath('//dc:creator');

                    $metadata['title'] = isset($title[0]) ? (string) $title[0] : '';
                    $metadata['author'] = isset($creator[0]) ? (string) $creator[0] : '';
                }
            }

            $zip->close();
        }

        return [
            'file_url' => $upload_dir['baseurl'] . '/books/' . $filename,
            'file_path' => $destination,
            'metadata' =>$metadata
        ];

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
