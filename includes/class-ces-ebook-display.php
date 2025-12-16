<?php
// direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

//class for displaying eBook in product page
class CES_Ebook_Display {
    private $settings;

    public function __construct() {
        $this->settings = CES_Settings::get_instance();
        add_action( 'woocommerce_after_single_product_summary', [ $this, 'display_epub_preview' ], 5 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action('wp_footer', [$this, 'add_purchase_overlay']);
    }

    /**
     * enqueue assets for the eBook display
     */
    public function enqueue_assets() {
            wp_enqueue_style( 'ces-ebook-display', CES_PLUGIN_URL . 'assets/css/ces-ebook-display.css', [], CES_PLUGIN_VERSION );
            wp_enqueue_style( 'ces-image-slider', CES_PLUGIN_URL . 'assets/css/ces-image-slider.css' );
            // Enqueue JS for the image slider ces-image-slider
            wp_enqueue_script( 'ces-image-slider', CES_PLUGIN_URL . 'assets/js/ces-image-slider.js', array( 'jquery' ), '1.0.0', true );
            // Load required JSZip library first (needed by ePub.js)
            wp_register_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.5.0/jszip.min.js', [], '3.5.0', true);
            wp_enqueue_script('jszip');
            
            // Load ePub.js with explicit version
            wp_register_script('epub-js', 'https://cdn.jsdelivr.net/npm/epubjs@0.3.93/dist/epub.min.js', ['jszip'], '0.3.93', true);
            wp_enqueue_script('epub-js');

    }

    /**
     * check if current user has purchased this product
     */
    private function user_has_purchased( $product_id ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        //check if admin or vendor or purchased
        if ( current_user_can( 'administrator' ) || current_user_can( 'vendor' ) ) {
            return true;
        }
        // Check if the user has purchased the product
        $orders = wc_get_orders( [
            'customer_id' => get_current_user_id(),
            'status'      => 'completed',
            'limit'       => -1,
        ] );
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                if ( (int) $item->get_product_id() === (int) $product_id ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Main frontend display method for EPUB preview
     */
    public function display_epub_preview() {
        global $post;
        $product_id = $post->ID;
        $epub_url = get_post_meta($product_id, '_ces_ebook_file', true);
        
        if ($epub_url && pathinfo($epub_url, PATHINFO_EXTENSION) == 'epub') {
        $has_access = $this->user_has_purchased($product_id);
        ?>
        <div id="epub-preview-container" style="margin-top: 40px;">
            <h3><?php esc_html_e('Preview this eBook', 'ces'); ?></h3>
            <?php if (!$has_access): ?>
                <p class="ces-sample-note"><?php esc_html_e('This is a sample preview. Purchase to unlock the full book.', 'ces'); ?></p>
            <?php endif; ?>
            <div class="ces-controls">
                <button id="prev-page"><?php esc_html_e('Previous', 'ces'); ?></button>
                <button id="next-page"><?php esc_html_e('Next', 'ces'); ?></button>
            </div>
            <div id="epub-viewer" style="height: 700px; border: 1px solid #ccc;"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            
            var hasAccess = <?php echo $has_access ? 'true' : 'false'; ?>;
            try {
                
                // Use a Book constructor with explicit options instead of shorthand format
                var book = ePub("<?php echo esc_url_raw($epub_url); ?>",{
                    openAs: "epub",
                });
                // Create rendition
                var rendition = book.renderTo("epub-viewer", {
                    method: "default",
                    width: "100%",
                    height: "100%",
                    padding: 10,
                    spread: "none",
                    flow: "paginated",
                    minSpreadWidth: 800,
                    gap: 10,
                });
            // Register single-page theme
                rendition.themes.register('image-fix', {
                    'body': {
                        'width': '100%',
                        'max-width': '100%',
                        'padding': '10px',
                        'margin': '0 auto',
                    },
                    'svg': {
                        'width': '60% !important',
                        'height': '100% !important',
                        'max-height': '100% !important',
                    },
                    'img': {
                        'width': 'auto !important',
                        'height': 'auto !important',
                        'object-fit': 'contain !important',
                        'display': 'block',
                        'margin': '0 auto',
                    }
                });

                rendition.themes.select('image-fix');
                var pageCounter = 0;
                var previewLimit = <?php echo $this->settings->get_preview_limit(); ?>; // Get the preview limit from settings
                var previewMessageShown = false;
                
                // For preview, start at the first chapter in TOC
                // book.loaded.navigation.then(function(nav) {
                //     var first = nav.toc[0];
                //     if (first) {
                //         rendition.display(first.href);
                //     } else {
                //         rendition.display()
                //     }
                // });
                rendition.display(0);

                // Track page direction for accurate counting
                var isForward = true;

                // Listen for the "relocated" event to track page changes
                rendition.on("relocated", function(location) {
                    
                    // Reset the direction flag
                    isForward = true;
                    // If user has access, allow unlimited pages
                    if (hasAccess) {
                        return;
                    }
                    // Check if preview limit is reached
                    if (pageCounter > previewLimit && !previewMessageShown) {
                        // Show purchase overlay
                        $("#ces-purchase-overlay").css("display", "flex");
                        previewMessageShown = true;
                    }
                });

                // Add event listeners for navigation
                $("#prev-page").on("click", function() {
                    rendition.prev();
                    isForward = false; // Set direction to backward
                    pageCounter = Math.max(0, pageCounter - 1); 
                });

                $("#next-page").on("click", function() {
                    if (hasAccess) {
                        rendition.next();
                    } else if (pageCounter <= previewLimit) {
                        // Allow forward navigation only if within the preview limit
                        rendition.next();
                        pageCounter++; 
                        console.log(pageCounter) // Increment counter on forward navigation
                    }else {
                        // If already at limit, just show the overlay
                        $("#ces-purchase-overlay").css("display", "flex");
                        previewMessageShown = true;
                    }
                });
            } catch (error) {
                $("#epub-viewer").html("<p>Error loading the ebook. Please try again later.</p>");
            }
        });
        </script>
        <?php
        } else {
            //CBZ preview
            $cbz_file_path = get_post_meta($product_id, '_ces_ebook_file_path', true);
            
            echo ces_display_cbz_preview_pages( $cbz_file_path );
           
        }
    }

    /**
     * Add purchase overlay
     */
    public function add_purchase_overlay() {
        if( ! class_exists('WooCommerce')) return;
        if ( ! is_product() || current_user_can( 'administrator' ) || current_user_can( 'vendor' ) ) {
            return;
        }
        ?>
        <div id="ces-purchase-overlay">
            <h2><?php esc_html_e('Unlock Full eBook', 'ces'); ?></h2>
            <p><?php esc_html_e('To unlock the full eBook, please purchase it.', 'ces'); ?></p>
            <button id="close-overlay"><?php esc_html_e('Close', 'ces'); ?></button>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $("#close-overlay").on("click", function() {
                $("#ces-purchase-overlay").fadeOut();
            });
        });
        </script>
        <?php
    }
}