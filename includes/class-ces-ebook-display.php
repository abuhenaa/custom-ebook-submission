<?php
// direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

//class for displaying eBook in product page
class CES_Ebook_Display {
    public function __construct() {
        add_action( 'woocommerce_after_single_product_summary', [ $this, 'display_epub_preview' ], 5 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * enqueue assets for the eBook display
     */
    public function enqueue_assets() {
        if ( is_product() ) {
            wp_enqueue_style( 'ces-ebook-display', CES_PLUGIN_URL . 'assets/css/ces-ebook-display.css', [], CES_PLUGIN_VERSION );
            wp_enqueue_script( 'ces-ebook-display', CES_PLUGIN_URL . 'assets/js/ces-ebook-display.js', [ 'jquery' ], CES_PLUGIN_VERSION, true );
            
            // Load required JSZip library first (needed by ePub.js)
            wp_register_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.5.0/jszip.min.js', [], '3.5.0', true);
            wp_enqueue_script('jszip');
            
            // Load ePub.js with explicit version
            wp_register_script('epub-js', 'https://cdn.jsdelivr.net/npm/epubjs@0.3.88/dist/epub.min.js', ['jszip'], '0.3.88', true);
            wp_enqueue_script('epub-js');
        }
    }

    /**
     * check if current user has purchased this product
     */
    private function user_has_purchased( $product_id ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
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
        
        if (!$epub_url || pathinfo($epub_url, PATHINFO_EXTENSION) !== 'epub') return;        
      
        $has_access = false; //$this->user_has_purchased($product_id); disabled for testing
        ?>
        <div id="epub-preview-container" style="margin-top: 40px;">
            <h3><?php esc_html_e('Preview this eBook', 'textdomain'); ?></h3>
            <?php if (!$has_access): ?>
                <p class="ces-sample-note"><?php esc_html_e('This is a sample preview. Purchase to unlock the full book.', 'textdomain'); ?></p>
            <?php endif; ?>
            <div class="ces-controls">
                <button id="prev-page"><?php esc_html_e('Previous', 'textdomain'); ?></button>
                <button id="next-page"><?php esc_html_e('Next', 'textdomain'); ?></button>
            </div>
            <div id="epub-viewer" style="height: 500px; border: 1px solid #ccc;"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            
            try {
                
                // Use a Book constructor with explicit options instead of shorthand format
                var book = ePub("<?php echo esc_url_raw($epub_url); ?>",{
                    openAs: "epub",
                });
                // Create rendition
                var rendition = book.renderTo("epub-viewer", {
                    width: "100%",
                    height: "100%",
                    spread: "none",
                    flow: "paginated",
                    minSpreadWidth: 800
                });
                
                // Display the book
                <?php if ($has_access): ?>
                    // For full access, start at the beginning
                    rendition.display().then(function() {
                        console.log("Book displayed successfully");
                    }).catch(function(error) {
                        console.error("Error displaying book:", error);
                    });
                <?php else: ?>
                    // For preview, start at the first chapter in TOC
                    book.loaded.navigation.then(function(nav) {
                        var first = nav.toc[0];
                        if (first) {
                            rendition.display(first.href);
                        } else {
                            rendition.display()
                        }
                    }).catch(function(error) {
                        console.error("Error loading navigation:", error);
                        rendition.display().catch(function(error) {
                            console.error("Error displaying default page after nav error:", error);
                        });
                    });
                <?php endif; ?>
                
                // Add event listeners for navigation
                $("#prev-page").on("click", function() {
                    rendition.prev();
                });
                
                $("#next-page").on("click", function() {
                    rendition.next();
                });
                
                // Handle keyboard navigation
                $(document).keydown(function(e) {
                    if (e.keyCode == 37) { // left arrow
                        rendition.prev();
                    }
                    if (e.keyCode == 39) { // right arrow
                        rendition.next();
                    }
                });
            } catch (error) {
                console.error("Error initializing EPUB reader:", error);
                $("#epub-viewer").html("<p>Error loading the ebook. Please try again later.</p>");
            }
        });
        </script>
        <?php
    }
}