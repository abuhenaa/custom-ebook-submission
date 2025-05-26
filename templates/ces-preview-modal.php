<?php 

$ebook_file_url = get_post_meta($_GET['product_id'], '_ces_ebook_file', true);
?>
<script>

// HTML page (outside form)
const modalHTML = `
<div id="ces-preview-modal" class="ces-modal">
    <div class="ces-modal-content">
        <div class="ces-modal-header">
            <h2 id="ces-preview-title"><?php _e('eBook Preview', 'ces'); ?></h2>
            <span class="ces-modal-close">&times;</span>
        </div>
        <div class="ces-modal-controls">
            <button id="ces-prev-page" class="ces-nav-btn" title="<?php _e('Previous Page', 'ces'); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <button id="ces-next-page" class="ces-nav-btn" title="<?php _e('Next Page', 'ces'); ?>">
               <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
        <div class="ces-modal-body">
            <div id="ces-epub-viewer" class="ces-viewer"></div>
            <div id="ces-cbz-viewer" class="ces-viewer"></div>
        </div>
    </div>
</div>
`;

// Append modal HTML to the body
document.addEventListener('DOMContentLoaded', function() {
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add CSS for the modal
    const style = document.createElement('style');
    style.textContent = `
        .ces-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .ces-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 960px;
            height: 90vh;
            display: flex;
            flex-direction: column;
            border-radius: 5px;
        }
        
        .ces-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            align-content: flex-start;
        }
        
        .ces-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -10px;
        }
        
        .ces-modal-close:hover,
        .ces-modal-close:focus {
            color: black;
            text-decoration: none;
        }
        
        .ces-modal-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .ces-nav-btn {
            background: none;
            border: 1px solid #ddd;
            cursor: pointer;
            padding: 8px 16px;
            margin: 0 10px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ces-nav-btn:hover {
            background-color: #f0f0f0;
        }
        
        .ces-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .ces-modal-body {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .ces-viewer {
            width: 100%;
            height: 100%;
            display: none;
            overflow: auto;
        }
        
        #ces-epub-viewer {
            background: #f9f9f9;
        }
        
        #ces-cbz-viewer {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f0f0f0;
            text-align: center;
        }
        
        #ces-cbz-viewer img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .ces-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 16px;
            color: #666;
        }
    `;
    document.head.appendChild(style);
    
    initPreviewFunctionality();
});

function initPreviewFunctionality() {
    // Elements
    const previewBtn = document.getElementById('ces-preview-btn');
    const modal = document.getElementById('ces-preview-modal');
    const closeBtn = document.querySelector('.ces-modal-close');
    const epubViewer = document.getElementById('ces-epub-viewer');
    const cbzViewer = document.getElementById('ces-cbz-viewer');
    const prevPageBtn = document.getElementById('ces-prev-page');
    const nextPageBtn = document.getElementById('ces-next-page');
    
    // State variables
    let book = null;
    let rendition = null;
    let currentPage = 1;
    let totalPages = 1;
    let cbzImages = [];
    let previewType = null;
    
    // Event listeners
    previewBtn.addEventListener('click', handlePreviewClick);
    closeBtn.addEventListener('click', closeModal);
    prevPageBtn.addEventListener('click', goToPrevPage);
    nextPageBtn.addEventListener('click', goToNextPage);
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    function handlePreviewClick() {
       
        const fileUrl = "<?php echo esc_url_raw($ebook_file_url) ?>";
        const fileExtension = fileUrl.split('.').pop().toLowerCase();
        
        let fileType;
        if (fileExtension === 'epub') {
            fileType = 'epub';
        } else if (fileExtension === 'cbz') {
            fileType = 'cbz';
        } else {
            alert('<?php _e("Preview is only available for EPUB and CBZ files", "ces"); ?>');
            return;
        }
        
        previewEbook(fileUrl, fileType);
    }
    
    // Handle keyboard events for navigation
    window.addEventListener('keydown', function(event) {
        if (!modal.style.display || modal.style.display === 'none') return;
        
        if (event.key === 'ArrowLeft') {
            goToPrevPage();
        } else if (event.key === 'ArrowRight') {
            goToNextPage();
        } else if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    // Main function to preview file by URL/path
    window.previewEbook = function(fileUrl, fileType) {
        previewType = fileType.toLowerCase();
        
        // Reset the viewers
        epubViewer.style.display = 'none';
        cbzViewer.style.display = 'none';
        epubViewer.innerHTML = '<div class="ces-loading">Loading EPUB...</div>';
        cbzViewer.innerHTML = '<div class="ces-loading">Loading CBZ...</div>';
        
        // Show the modal
        modal.style.display = 'block';
        
        // Initialize the appropriate viewer
        if (previewType === 'epub') {
            initEpubViewerFromUrl(fileUrl);
        } else if (previewType === 'cbz') {
            initCbzViewerFromUrl(fileUrl);
        } else {
            alert('<?php _e("Preview is only available for EPUB and CBZ files", "ces"); ?>');
            closeModal();
        }
    };
    
    function initEpubViewerFromUrl(fileUrl) {
        // Show the EPUB viewer
        epubViewer.style.display = 'block';
        
        try {
            // Create a new book from URL
            book = ePub(fileUrl);
            
            // Generate rendition
            rendition = book.renderTo('ces-epub-viewer', {
                width: '100%',
                height: '100%',
                spread: 'none'
            });
            
            // Display the first page
            rendition.display();
            
            // Update navigation
            book.ready.then(() => {
                return book.locations.generate();
            }).then(() => {
                // Get the total number of pages
                totalPages = book.locations.length();
                currentPage = 1;
                
                // Enable/disable navigation buttons
                updateNavButtons();
               
               document.querySelector('.ces-loading').remove();
                
            }).catch(error => {
                console.error('Error loading EPUB:', error);
                epubViewer.innerHTML = '<div class="ces-loading">Error loading EPUB file</div>';
            });
            
            // Listen for page changes
            rendition.on('relocated', function(location) {
                if (book.locations && book.locations.locationFromCfi) {
                    currentPage = book.locations.locationFromCfi(location.start.cfi);
                    updateNavButtons();
                }
            });
            
        } catch (error) {
            console.error('Error initializing EPUB viewer:', error);
            epubViewer.innerHTML = '<div class="ces-loading">Error loading EPUB file</div>';
        }
    }
    
function initCbzViewerFromUrl(fileUrl) {
    // Show the CBZ viewer
    cbzViewer.style.display = 'block';
    
    // Reset images array
    cbzImages = [];
    currentPage = 1;
    
    // Fetch the CBZ file
    fetch(fileUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.arrayBuffer();
        })
        .then(arrayBuffer => {
            return JSZip.loadAsync(arrayBuffer);
        })
        .then(zip => {
            // Filter for image files only
            const imageFiles = [];
            
            zip.forEach(function(relativePath, zipEntry) {
                const lowerPath = relativePath.toLowerCase();
                if (!zipEntry.dir && (lowerPath.endsWith('.jpg') || 
                                     lowerPath.endsWith('.jpeg') || 
                                     lowerPath.endsWith('.png') || 
                                     lowerPath.endsWith('.gif'))) {
                    imageFiles.push({
                        name: relativePath,
                        zipEntry: zipEntry
                    });
                }
            });
            
            // Sort files by name (numeric sort to handle filenames like 1.jpg, 2.jpg, 10.jpg correctly)
            imageFiles.sort((a, b) => {
                return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
            });
            
            // Initialize cbzImages array with correct length
            cbzImages = new Array(imageFiles.length);
            
            // Extract each image and store it at the correct index
            const imagePromises = imageFiles.map((file, index) => {
                return file.zipEntry.async('blob')
                    .then(function(blob) {
                        const url = URL.createObjectURL(blob);
                        cbzImages[index] = url; // Store at the correct index
                    });
            });
            
            // Once all images are extracted, display the first one
            return Promise.all(imagePromises);
        })
        .then(() => {
            totalPages = cbzImages.length;
            updateNavButtons();
            document.querySelector('.ces-loading').remove();
            displayCbzPage(1);
        })
        .catch(error => {
            console.error('Error reading CBZ file:', error);
            cbzViewer.innerHTML = '<div class="ces-loading">Error loading CBZ file: ' + error.message + '</div>';
        });
}

function displayCbzPage(pageNum) {
    if (pageNum < 1 || pageNum > cbzImages.length) return;
    
    currentPage = pageNum;
    cbzViewer.innerHTML = '';
    
    const img = document.createElement('img');
    img.src = cbzImages[pageNum - 1];
    img.alt = 'Page ' + pageNum;
    img.onload = function() {
        updateNavButtons();
    };
    cbzViewer.appendChild(img);
}
    function goToPrevPage() {
        if (previewType === 'epub') {
            if (rendition) {
                rendition.prev();
            }
        } else if (previewType === 'cbz') {
            if (currentPage > 1) {
                displayCbzPage(currentPage - 1);
            }
        }
    }
    
    function goToNextPage() {
        if (previewType === 'epub') {
            if (rendition) {
                rendition.next();
            }
        } else if (previewType === 'cbz') {
            if (currentPage < totalPages) {
                displayCbzPage(currentPage + 1);
            }
        }
    }
    
    function updateNavButtons() {
        // Disable/enable prev button
        prevPageBtn.disabled = currentPage <= 1;
        
        // Disable/enable next button
        nextPageBtn.disabled = currentPage >= totalPages;
    }
    
    function closeModal() {
        modal.style.display = 'none';
        
        // Clean up resources
        if (book && rendition) {
            rendition.destroy();
            book = null;
            rendition = null;
        }
        
        // Revoke object URLs for CBZ images
        if (cbzImages.length > 0) {
            cbzImages.forEach(url => URL.revokeObjectURL(url));
            cbzImages = [];
        }
        
        // Reset state
        currentPage = 1;
        totalPages = 1;
        previewType = null;
    }
}


</script>