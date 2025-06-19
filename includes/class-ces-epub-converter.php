<?php
// Add this to your main plugin file or functions.php

class CES_EPUB_Converter {
    
    private $product_id;
    
    public function __construct() {
        add_action('wp_ajax_convert_docx_to_epub', array($this, 'ajax_convert_docx_to_epub'));
        add_action('wp_ajax_nopriv_convert_docx_to_epub', array($this, 'ajax_convert_docx_to_epub'));

    }
    
    public function ajax_convert_docx_to_epub() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ces_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['docx_file']) || $_FILES['docx_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded or upload error occurred.'));
            return;
        }
        
        $file = $_FILES['docx_file'];
        
        // Validate file type
        $allowed_types = array('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) && $file_type['ext'] !== 'docx') {
            wp_send_json_error(array('message' => 'Only DOCX files are allowed.'));
            return;
        }
        
        // Handle file upload
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            wp_send_json_error(array('message' => $uploaded_file['error']));
            return;
        }
        
        // Convert DOCX to EPUB
        $result = $this->convert_docx_to_epub($uploaded_file['file']);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'File converted successfully!',
                'file_url' => $result['file_url']
            ));
        } else {
            wp_send_json_error(array('message' => 'Conversion failed. Please try again.'));
        }
    }
    
    private function convert_docx_to_epub($file_path) {
        $upload_dir = wp_upload_dir();
        $books_dir = $upload_dir['basedir'] . '/books';
        
        // Create books folder if not exists
        if (!file_exists($books_dir)) {
            wp_mkdir_p($books_dir);
        }
        
        $filename = basename($file_path);
        $epub_filename = str_replace('.docx', '.epub', $filename);
        $epub_output = $books_dir . '/' . $epub_filename;
        
        // Convert DOCX to EPUB using pandoc
        $cmd = "pandoc " . escapeshellarg($file_path) . " -o " . escapeshellarg($epub_output);
        shell_exec($cmd);
        
        // Check if conversion succeeded
        if (file_exists($epub_output)) {
            return [
                'file_url' => $upload_dir['baseurl'] . '/books/' . $epub_filename,
                'file_path' => $epub_output
            ];
        }
        
        return null;
    }
}