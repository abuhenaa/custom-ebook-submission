// Add this CSS to your stylesheet or in a <style> tag in your header
jQuery(document).ready(function($) {
  // Initialize slider functionality once document is ready
  function initImageSlider() {
    // Get container and its images
    const $container = $('#ces-preview-container');
    const $images = $container.find('.ces-cbz-image');
    
    // Don't proceed if there are no images or only one image
    if ($images.length <= 1) return;
    
    // Add necessary CSS classes
    $container.addClass('ces-slider-container');
    $images.addClass('ces-slider-image');
    
    // Hide all images except the first one
    $images.not(':first').hide();
    
    // Add navigation controls
    $container.append(`
      <div class="ces-slider-nav">
        <button class="ces-slider-prev">Previous</button>
        <span class="ces-slider-counter">1/${$images.length}</span>
        <button class="ces-slider-next">Next</button>
      </div>
    `);
    
    let currentIndex = 0;
    
    // Next button click handler
    $container.on('click', '.ces-slider-next', function() {
      $images.eq(currentIndex).fadeOut(300);
      currentIndex = (currentIndex + 1) % $images.length;
      $images.eq(currentIndex).fadeIn(300);
      updateCounter();
    });
    
    // Previous button click handler
    $container.on('click', '.ces-slider-prev', function() {
      $images.eq(currentIndex).fadeOut(300);
      currentIndex = (currentIndex - 1 + $images.length) % $images.length;
      $images.eq(currentIndex).fadeIn(300);
      updateCounter();
    });
    
    // Update counter display
    function updateCounter() {
      $container.find('.ces-slider-counter').text(`${currentIndex + 1}/${$images.length}`);
    }
    
    // Add keyboard navigation support
    $(document).on('keydown', function(e) {
      if (!$images.length) return;
      
      if (e.keyCode === 37) { // Left arrow key
        $container.find('.ces-slider-prev').click();
      } else if (e.keyCode === 39) { // Right arrow key
        $container.find('.ces-slider-next').click();
      }
    });
    
    // Add swipe support for touch devices
    let touchStartX = 0;
    let touchEndX = 0;
    
    $container.on('touchstart', function(e) {
      touchStartX = e.originalEvent.touches[0].clientX;
    });
    
    $container.on('touchend', function(e) {
      touchEndX = e.originalEvent.changedTouches[0].clientX;
      handleSwipe();
    });
    
    function handleSwipe() {
      if (touchEndX < touchStartX - 50) {
        // Swipe left - next image
        $container.find('.ces-slider-next').click();
      } else if (touchEndX > touchStartX + 50) {
        // Swipe right - previous image
        $container.find('.ces-slider-prev').click();
      }
    }
  }
  
  // Two ways to initialize the slider:
  
  // 1. If images are loaded immediately
  initImageSlider();
  
  // 2. Or wait for images to be added dynamically
  // Use a MutationObserver to detect when images are added to the container
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.addedNodes.length > 0) {
        // Check if we have images and init/reinit the slider
        const $images = $('#ces-preview-container').find('.ces-cbz-image');
        if ($images.length > 0) {
          // Small timeout to ensure all images are processed
          setTimeout(initImageSlider, 100);
        }
      }
    });
  });
  
  // Start observing the container for changes
  observer.observe(document.getElementById('ces-preview-container'), {
    childList: true,
    subtree: true
  });
});