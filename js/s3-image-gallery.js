document.addEventListener("DOMContentLoaded", () => {
  const images = document.querySelectorAll(".s3-gallery-link");
  let currentIndex = 0;

  // Create the overlay elements
  const overlay = document.createElement("div");
  overlay.classList.add("s3-gallery-overlay");

  const img = document.createElement("img");

  let watermark;
  if (S3GallerySettings.watermarkEnabled) {
    watermark = document.createElement("img");
    watermark.classList.add("s3-gallery-watermark");
    watermark.src = S3GallerySettings.watermarkUrl || "";
    overlay.appendChild(watermark);
  }

  const prevButton = document.createElement("button");
  prevButton.innerText = "<";
  prevButton.classList.add("s3-gallery-button", "s3-gallery-button--prev");

  const nextButton = document.createElement("button");
  nextButton.innerText = ">";
  nextButton.classList.add("s3-gallery-button", "s3-gallery-button--next");

  const closeButton = document.createElement("button");
  closeButton.innerText = "X";
  closeButton.classList.add("s3-gallery-button", "s3-gallery-button--close");

  overlay.appendChild(prevButton);
  overlay.appendChild(nextButton);
  overlay.appendChild(closeButton);
  overlay.appendChild(img);
  document.body.appendChild(overlay);

  // Event listeners for overlay interactions
  prevButton.addEventListener("click", () => {
    if (currentIndex > 0) {
      currentIndex--; // Move to the previous image
      showImage();
    }
  });

  nextButton.addEventListener("click", () => {
    if (currentIndex < images.length - 1) {
      currentIndex++; // Move to the next image
      showImage();
    }
  });

  closeButton.addEventListener("click", () => {
    overlay.style.display = "none";
  });

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      overlay.style.display = "none";
    }
  });

  document.addEventListener("keydown", (event) => {
    if (overlay.style.display === "flex") {
      if (event.key === "ArrowRight" && currentIndex < images.length - 1) {
        currentIndex++;
        showImage();
      } else if (event.key === "ArrowLeft" && currentIndex > 0) {
        currentIndex--;
        showImage();
      } else if (event.key === "Escape") {
        overlay.style.display = "none";
      }
    }
  });

  images.forEach((link, index) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      currentIndex = index;
      showImage();
      overlay.style.display = "flex";
    });
  });

  function showImage() {
    const currentLink = images[currentIndex];
    const screenWidth = window.innerWidth;

    let bestSrc = currentLink.dataset.src720;

    if (screenWidth >= 1920) {
      bestSrc = currentLink.dataset.src1920;
    } else if (screenWidth >= 1440) {
      bestSrc = currentLink.dataset.src1440;
    } else if (screenWidth >= 960) {
      bestSrc = currentLink.dataset.src960;
    }

    img.src = bestSrc;

    if (S3GallerySettings.watermarkEnabled && watermark) {
      watermark.style.display = "block";
    }

    // Update button visibility
    prevButton.style.display = currentIndex === 0 ? "none" : "block";
    nextButton.style.display = currentIndex === images.length - 1 ? "none" : "block";
  }
});
