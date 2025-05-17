<?php
//direct access
if (!defined('ABSPATH')) {
    exit;
}
// Add custom mime types for ebook uploads
function ces_allow_epub_uploads($mime_types) {
    $mime_types['epub'] = 'application/epub+zip';
    return $mime_types;
}
add_filter('upload_mimes', 'ces_allow_epub_uploads');