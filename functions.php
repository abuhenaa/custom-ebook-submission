<?php
//direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Add custom mime types for ebook uploads
function ces_allow_epub_uploads( $mime_types )
{
    $mime_types[ 'epub' ] = 'application/epub+zip';
    $mime_types[ 'cbz' ]  = 'application/vnd.comicbook+zip';
    return $mime_types;
}
add_filter( 'upload_mimes', 'ces_allow_epub_uploads' );

// Enqueue epub js and jszip in admin
add_action( 'admin_enqueue_scripts', 'ces_enqueue_epub_js' );
function ces_enqueue_epub_js()
{
    // Enqueue CSS for the image slider ces-image-slider
    wp_enqueue_style( 'ces-image-slider', plugin_dir_url( __FILE__ ) . 'assets/css/ces-image-slider.css' );
    // Enqueue JS for the image slider ces-image-slider
    wp_enqueue_script( 'ces-image-slider', plugin_dir_url( __FILE__ ) . 'assets/js/ces-image-slider.js', array( 'jquery' ), '1.0.0', true );
    //nonce
    wp_localize_script( 'ces-image-slider', 'ces_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ces_preview_nonce' ),
    ) );

    // Enqueue JSZip and ePub.js
    wp_enqueue_script( 'jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.5.0/jszip.min.js', array(), '3.5.0', true );
    wp_enqueue_script( 'epub-js', 'https://cdn.jsdelivr.net/npm/epubjs@0.3.88/dist/epub.min.js', array( 'jszip' ), '0.3.88', true );
}

//add necessary meta boxes to the product
add_action( 'add_meta_boxes', 'ces_add_meta_box' );
function ces_add_meta_box()
{

    //ebook file preview
    add_meta_box(
        '_ces_ebook_file_preview',
        __( 'Ebook File Preview', 'ces' ),
        'ces_display_ebook_file_preview_meta_box',
        'product',
        'normal',
        'high'
    );

    add_meta_box(
        '_category_suggestion', // Unique ID
        'Requested Categories', // Box title
        'ces_display_requested_categories_meta_box', // Content callback
        'product', // Post type
        'side', // Context (normal, side, advanced)
        'high' // Priority (high, core, default, low)
    );

    add_meta_box(
        '_ces_subtitle', // Unique ID
        'Subtitle', // Box title
        'ces_display_subtitle_meta_box', // Content callback
        'product', // Post type
        'normal', // Context (normal, side, advanced)
        'high' // Priority (high, core, default, low)
    );

    add_meta_box(
        '_ces_series',
        'Series',
        'ces_display_series_meta_box',
        'product',
        'normal',
        'high'
    );
    add_meta_box(
        '_ces_external_link',
        'External Link',
        'ces_display_external_link_meta_box',
        'product',
        'normal',
        'high'
    );
    //add publisher and isbn metabox to the product
    add_meta_box(
        '_ces_publisher',
        'Publisher',
        'ces_display_publisher_meta_box',
        'product',
        'normal',
        'high'
    );
    add_meta_box(
        '_ces_isbn',
        'ISBN',
        'ces_display_isbn_meta_box',
        'product',
        'normal',
        'high'
    );
}

/*
 * Function to display the publisher meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_requested_categories_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_requested_categories', 'ces_requested_categories_nonce' );

    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_category_suggestion', true );

    if ( !empty( $value ) ) {
        $value = esc_html( $value );
    } else {
        $value = 'No categories requested';
    }

    echo "<strong>" . esc_html( $value ) . "</strong>";
}

/*
 * Function to display the ebook file preview meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_ebook_file_preview_meta_box( $post )
{
    // Retrieve the current value of the meta field
    $file_url  = get_post_meta( $post->ID, '_ces_ebook_file', true );
    $file_path = get_post_meta( $post->ID, '_ces_ebook_file_path', true );

    // Only show preview if a file exists
    if ( !empty( $file_url ) ) {
        $file_type = wp_check_filetype( basename( $file_path ), null );
        $extension = $file_type[ 'ext' ];

        // Display preview button
        echo '<div class="ces-preview-container">';
        echo '<button type="button" class="button ces-preview-button" data-file="' . esc_attr( $file_url ) . '" data-type="' . esc_attr( $extension ) . '" data-path="' . esc_attr( $file_path ) . '">Preview E-book</button>';
        echo '</div>';

        // Add popup markup
        echo '<div id="ces-preview-modal" class="ces-modal">
            <div class="ces-modal-content">
                <span class="ces-close">&times;</span>
                <h3>E-book Preview</h3>
                <div class="ces-navigation">
                    <button id="prev-page" class="ces-nav-button"><span class="dashicons dashicons-arrow-left"></span></button>
                    <button id="next-page" class="ces-nav-button"><span class="dashicons dashicons-arrow-right"></span></button>
                </div>
                <div id="ces-preview-container"></div>
            </div>
        </div>';

    } else {
        echo '<p>Upload an e-book file to enable preview.</p>';
    }
}

// AJAX handler for processing CBZ files
add_action( 'wp_ajax_ces_process_cbz', 'ces_process_cbz_ajax' );
function ces_process_cbz_ajax()
{
    // Check nonce
    if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'ces_preview_nonce' ) ) {
        wp_send_json_error( 'Security check failed' );
    }

    // Get file path
    $file_path = isset( $_POST[ 'file_path' ] ) ? sanitize_text_field( $_POST[ 'file_path' ] ) : '';

    if ( empty( $file_path ) || !file_exists( $file_path ) ) {
        wp_send_json_error( 'File not found' );
    }

    // Process CBZ file
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
    global $wp_filesystem;

    // Create temp directory
    $upload_dir = wp_upload_dir();
    $temp_dir   = $upload_dir[ 'basedir' ] . '/cbz_temp_' . uniqid();
    wp_mkdir_p( $temp_dir );

    // Extract CBZ (which is a ZIP file)
    $result = unzip_file( $file_path, $temp_dir );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( 'Failed to extract CBZ file: ' . $result->get_error_message() );
    }

    // Get list of extracted images
    $allowed_types = array( 'jpg', 'jpeg', 'png', 'gif' );
    $images        = array();

    $files = list_files( $temp_dir );

    foreach ( $files as $file ) {
        $ext = pathinfo( $file, PATHINFO_EXTENSION );
        if ( in_array( strtolower( $ext ), $allowed_types ) ) {
            // Convert server path to URL
            $file_url = str_replace(
                $upload_dir[ 'basedir' ],
                $upload_dir[ 'baseurl' ],
                $file
            );
            $images[  ] = $file_url;
        }
    }

    // Sort images naturally
    natsort( $images );
    $images = array_values( $images );

    wp_send_json_success( $images );
}
/*
 * Function to display the subtitle meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_subtitle_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_subtitle', 'ces_subtitle_nonce' );

    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_ces_subtitle', true );

    if ( !empty( $value ) ) {
        $value = esc_html( $value );
    } else {
        $value = 'No subtitle';
    }

    echo "<strong>" . esc_html( $value ) . "</strong>";
}

/*
 * Function to display the series meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_series_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_series', 'ces_series_nonce' );

    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_ces_series', true );

    if ( !empty( $value ) ) {
        $value = esc_html( $value );
    } else {
        $value = 'No series';
    }

    echo "<strong>" . esc_html( $value ) . "</strong>";
}

/*
 * Function to display the publisher meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_publisher_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_publisher', 'ces_publisher_nonce' );

    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_ces_publisher', true );

    if ( !empty( $value ) ) {
        $value = esc_html( $value );
    } else {
        $value = 'No publisher';
    }

    echo "<strong>" . esc_html( $value ) . "</strong>";
}
/*
 * Function to display the ISBN meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_isbn_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_isbn', 'ces_isbn_nonce' );

    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_ces_isbn', true );

    if ( !empty( $value ) ) {
        $value = esc_html( $value );
    } else {
        $value = 'No ISBN';
    }

    echo "<strong>" . esc_html( $value ) . "</strong>";
}

/*
 * Function to display the external link meta box
 *
 * @param WP_Post $post The current post object
 */
function ces_display_external_link_meta_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( 'ces_save_external_link', 'ces_external_link_nonce' );
    // Retrieve the current value of the meta field
    $value = get_post_meta( $post->ID, '_ces_bookstore_link', true );
    echo "<label for='ces_external_link'>External Link:</label>";
    echo "<input type='text' id='ces_external_link' name='_ces_bookstore_link' value='" . esc_html( $value ) . "' />";

}

// Add the custom field to the product save process
add_action( 'woocommerce_process_product_meta', 'ces_save_subtitle_series_meta_box' );
function ces_save_subtitle_series_meta_box( $post_id )
{
    // Check nonce for security
    if ( !isset( $_POST[ 'ces_subtitle_nonce' ] ) || !wp_verify_nonce( $_POST[ 'ces_subtitle_nonce' ], 'ces_save_subtitle' ) ) {
        return;
    }
    if ( !isset( $_POST[ 'ces_series_nonce' ] ) || !wp_verify_nonce( $_POST[ 'ces_series_nonce' ], 'ces_save_series' ) ) {
        return;
    }
    if ( !isset( $_POST[ 'ces_external_link_nonce' ] ) || !wp_verify_nonce( $_POST[ 'ces_external_link_nonce' ], 'ces_save_external_link' ) ) {
        return;
    }

    // Save the subtitle
    if ( isset( $_POST[ '_ces_subtitle' ] ) ) {
        update_post_meta( $post_id, '_ces_subtitle', sanitize_text_field( $_POST[ '_ces_subtitle' ] ) );
    }

    // Save the series
    if ( isset( $_POST[ '_ces_series' ] ) ) {
        update_post_meta( $post_id, '_ces_series', sanitize_text_field( $_POST[ '_ces_series' ] ) );
    }

    // Save the external link
    if ( isset( $_POST[ 'ces_external_link' ] ) ) {
        update_post_meta( $post_id, '_ces_bookstore_link', esc_url_raw( $_POST[ '_ces_bookstore_link' ] ) );
    }
}

//register author taxonomy to products
function ces_register_author_taxonomy()
{
    $labels = array(
        'name'              => _x( 'Authors', 'taxonomy general name', 'ces' ),
        'singular_name'     => _x( 'Author', 'taxonomy singular name', 'ces' ),
        'search_items'      => __( 'Search Authors', 'ces' ),
        'all_items'         => __( 'All Authors', 'ces' ),
        'parent_item'       => __( 'Parent Author', 'ces' ),
        'parent_item_colon' => __( 'Parent Author:', 'ces' ),
        'edit_item'         => __( 'Edit Author', 'ces' ),
        'update_item'       => __( 'Update Author', 'ces' ),
        'add_new_item'      => __( 'Add New Author', 'ces' ),
        'new_item_name'     => __( 'New Author Name', 'ces' ),
        'menu_name'         => __( 'Authors', 'ces' ),
    );

    $args = array(
        'hierarchical'      => true,
        'public'            => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'books-authors' ),
    );

    register_taxonomy( 'books-author', array( 'product' ), $args );
}

//init
add_action( 'init', 'ces_register_author_taxonomy' );

add_action( 'woocommerce_single_product_summary', 'ces_show_supporting_badge', 6 ); // before price

function ces_show_supporting_badge()
{
    global $post;
    $external_link = get_post_meta( $post->ID, '_ces_bookstore_link', true );

    if ( !empty( $external_link ) ) {
        echo '<div class="ces-supporting-badge"><a href="' . esc_url_raw( $external_link ) . '" rel="nofollow" target="_blank">' . __( 'Supports Bookstores', 'ces' ) . '</a></div>';
    }
}

// add a table after the description about books info like subtitle, series, publisher, isbn
add_action( 'woocommerce_after_single_product_summary', 'ces_show_book_info_table', 5 );
function ces_show_book_info_table()
{
    global $post;

    // Get the custom fields
    $subtitle         = get_post_meta( $post->ID, '_ces_subtitle', true );
    $series           = get_post_meta( $post->ID, '_ces_series', true );
    $publisher        = get_post_meta( $post->ID, '_ces_publisher', true );
    $isbn             = get_post_meta( $post->ID, '_ces_isbn', true );
    $publication_date = get_post_meta( $post->ID, 'publication_date', true );

    // Only show the table if at least one field is not empty
    if ( !empty( $subtitle ) || !empty( $series ) || !empty( $publisher ) || !empty( $isbn ) ) {
        echo '<div class="ces-book-info-table">';
        echo '<h3 style="margin-bottom:15px">' . __( 'Book Information', 'ces' ) . '</h3>';
        echo '<table>';
        if ( !empty( $subtitle ) ) {
            echo '<tr><th>' . __( 'Subtitle', 'ces' ) . '</th><td>' . esc_html( $subtitle ) . '</td></tr>';
        }
        if ( !empty( $series ) ) {
            echo '<tr><th>' . __( 'Series', 'ces' ) . '</th><td>' . esc_html( $series ) . '</td></tr>';
        }
        if ( !empty( $publisher ) ) {
            echo '<tr><th>' . __( 'Publisher', 'ces' ) . '</th><td>' . esc_html( $publisher ) . '</td></tr>';
        }
        if ( !empty( $isbn ) ) {
            echo '<tr><th>' . __( 'ISBN', 'ces' ) . '</th><td>' . esc_html( $isbn ) . '</td></tr>';
        }
        if ( !empty( $publication_date ) ) {
            echo '<tr><th>' . __( 'Publication Date', 'ces' ) . '</th><td>' . esc_html( $publication_date ) . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
    }
}

/**
 * AJAX handler to get subcategories
 */
function ces_get_subcategories_ajax()
{
    if ( !isset( $_POST[ 'parent_id' ] ) ) {
        wp_send_json_error( 'Missing parent ID' );
        wp_die();
    }

    $parent_id     = intval( $_POST[ 'parent_id' ] );
    $subcategories = ces_get_subcategories( $parent_id );

    $options = [  ];
    foreach ( $subcategories as $subcat ) {
        $options[  ] = [
            'id'   => $subcat->term_id,
            'name' => $subcat->name,
         ];
    }

    wp_send_json_success( $options );
    wp_die();
}
add_action( 'wp_ajax_ces_get_subcategories', 'ces_get_subcategories_ajax' );
add_action( 'wp_ajax_nopriv_ces_get_subcategories', 'ces_get_subcategories_ajax' );

/**
 * Handle the print book form submission
 */
function ces_handle_print_book_submission()
{
    if ( isset( $_POST[ 'submit_print_book' ] ) && isset( $_POST[ 'ces_print_book_nonce' ] ) &&
        wp_verify_nonce( $_POST[ 'ces_print_book_nonce' ], 'ces_print_book_action' ) ) {

        // Get the product ID
        $product_id = isset( $_POST[ 'product_id' ] ) ? intval( $_POST[ 'product_id' ] ) : 0;

        if ( $product_id <= 0 ) {
            wp_die( __( 'Invalid product ID.', 'ces' ) );
        }

        // Get form data
        $personal_website_link = isset( $_POST[ 'personal_website_link' ] ) ? esc_url_raw( $_POST[ 'personal_website_link' ] ) : '';
        $bookstore_link        = isset( $_POST[ 'bookstore_link' ] ) ? esc_url_raw( $_POST[ 'bookstore_link' ] ) : '';

        // Save as post meta to the product
        update_post_meta( $product_id, '_ces_personal_website_link', $personal_website_link );
        update_post_meta( $product_id, '_ces_bookstore_link', $bookstore_link );
        update_post_meta( $product_id, 'paperbook_price', sanitize_text_field( $_POST[ 'paperbook_price' ] ) );

        // Set a flag if supporting bookstores
        if ( !empty( $bookstore_link ) ) {
            update_post_meta( $product_id, '_ces_supports_bookstores', 'yes' );
        } else {
            update_post_meta( $product_id, '_ces_supports_bookstores', 'no' );
        }

        // Redirect to a thank you page or the home page
        wp_redirect( add_query_arg( 'print_book_added', 'true', get_permalink() ) );
        exit;
    }
}
add_action( 'init', 'ces_handle_print_book_submission' );

/**
 * Display a "Supports bookstores" badge on products
 */
function ces_display_bookstore_badge()
{
    global $product;

    if ( !$product ) {
        return;
    }

    $product_id          = $product->get_id();
    $supports_bookstores = get_post_meta( $product_id, '_ces_supports_bookstores', true );

    if ( $supports_bookstores === 'yes' ) {
        echo '<span class="ces-bookstore-badge">' . __( 'Supports bookstores', 'ces' ) . '</span>';
    }
}
//add_action('woocommerce_before_shop_loop_item_title', 'ces_display_bookstore_badge', 15);
//add_action('woocommerce_single_product_summary', 'ces_display_bookstore_badge', 7);

/**
 * Add print book links to the product display
 */
function ces_display_print_book_links()
{
    global $product;

    if ( !$product ) {
        return;
    }

    $product_id            = $product->get_id();
    $personal_website_link = get_post_meta( $product_id, '_ces_personal_website_link', true );
    $bookstore_link        = get_post_meta( $product_id, 'ces_external_link', true );

    if ( !empty( $personal_website_link ) || !empty( $bookstore_link ) ) {
        echo '<div class="ces-print-book-links">';
        echo '<h4>' . __( 'Get the printed book:', 'ces' ) . '</h4>';
        echo '<ul>';

        if ( !empty( $personal_website_link ) ) {
            echo '<li><a href="' . esc_url( $personal_website_link ) . '" target="_blank" rel="nofollow">' .
            __( 'Author\'s Website', 'ces' ) . '</a></li>';
        }

        if ( !empty( $bookstore_link ) ) {
            echo '<li><a href="' . esc_url( $bookstore_link ) . '" target="_blank" rel="nofollow">' .
            __( 'Independent Bookstore', 'ces' ) . '</a></li>';
        }

        echo '</ul>';
        echo '</div>';
    }
}
//add_action('woocommerce_single_product_summary', 'ces_display_print_book_links', 25);

//woocommerce_product_meta_start
add_action( 'woocommerce_product_meta_start', 'ces_display_print_book_info', 25 );
function ces_display_print_book_info()
{
    global $product;

    if ( !$product ) {
        return;
    }

    $product_id      = $product->get_id();
    $paperbook_price = get_post_meta( $product_id, 'paperbook_price', true );
    $bookstore_link  = get_post_meta( $product_id, '_ces_bookstore_link', true );

    if ( !empty( $paperbook_price ) ) {
        echo '<div class="ces-print-book-info">';

        if ( !empty( $paperbook_price ) ) {
            echo '<strong>' . __( 'Paperbook Price:', 'ces' ). wc_price( $paperbook_price )  . '</strong> ';
        }
        //buy button
        if ( !empty( $bookstore_link ) ) {
            echo '<a href="' . esc_url( $bookstore_link ) . '" class="button ces-buy-button" target="_blank" rel="nofollow">' .
            __( 'Buy The Paperbook', 'ces' ) . '</a>';
        }

        echo '</div>';
    }
}
