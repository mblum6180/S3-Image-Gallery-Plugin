document.addEventListener("DOMContentLoaded", () => {
  const images = document.querySelectorAll(".s3-gallery-link");
  let currentIndex = 0;

  // Create the overlay elements
  const overlay = document.createElement("div");
  overlay.classList.add("s3-gallery-overlay");

  const img = document.createElement("img");

  // Watermark element
  const watermark = document.createElement("img");
  watermark.classList.add("s3-gallery-watermark");
  watermark.src = S3GallerySettings.watermarkUrl || ""; // Use the URL from admin settings

  const prevButton = document.createElement("button");
  prevButton.innerText = "<";
  prevButton.classList.add("s3-gallery-button", "s3-gallery-button--prev");

  const nextButton = document.createElement("button");
  nextButton.innerText = ">";
  nextButton.classList.add("s3-gallery-button", "s3-gallery-button--next");

  const closeButton = document.createElement("button");
  closeButton.innerText = "X";
  closeButton.classList.add("s3-gallery-button", "s3-gallery-button--close");

  // Append elements
  overlay.appendChild(prevButton);
  overlay.appendChild(nextButton);
  overlay.appendChild(closeButton);
  overlay.appendChild(img);
  overlay.appendChild(watermark);
  document.body.appendChild(overlay);

  // Event listeners for overlay interactions
  prevButton.addEventListener("click", () => {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    showImage();
  });

  nextButton.addEventListener("click", () => {
    currentIndex = (currentIndex + 1) % images.length;
    showImage();
  });

  closeButton.addEventListener("click", () => {
    overlay.style.display = "none";
  });

  overlay.addEventListener("click", (e) => {
    // Close overlay if user clicks on the background
    if (e.target === overlay) {
      overlay.style.display = "none";
    }
  });

  // Keyboard navigation
  document.addEventListener("keydown", (event) => {
    if (overlay.style.display === "flex") {
      if (event.key === "ArrowRight") {
        currentIndex = (currentIndex + 1) % images.length;
        showImage();
      } else if (event.key === "ArrowLeft") {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage();
      } else if (event.key === "Escape") {
        overlay.style.display = "none";
      }
    }
  });

  // Setup click events for gallery items
  images.forEach((link, index) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      currentIndex = index;
      showImage();
      overlay.style.display = "flex";
    });
  });

  // Display the current image in the overlay
  function showImage() {
    const currentLink = images[currentIndex];
    const screenWidth = window.innerWidth;

    // Default to src720
    let bestSrc = currentLink.dataset.src720;

    // Choose higher resolutions if the screen is large enough
    if (screenWidth >= 1920) {
      bestSrc = currentLink.dataset.src1920;
    } else if (screenWidth >= 1440) {
      bestSrc = currentLink.dataset.src1440;
    } else if (screenWidth >= 960) {
      bestSrc = currentLink.dataset.src960;
    }

    // Apply to overlay
    img.src = bestSrc;

    // No caption logic required; watermark is handled separately
  }
});
