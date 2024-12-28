document.addEventListener("DOMContentLoaded", () => {
  document.addEventListener("contextmenu", function(event) {
    // If you only want to disable for images:
    // if (event.target.tagName.toLowerCase() === 'img') {
    event.preventDefault();
    //alert("Right-click is disabled to protect the images on this site.");
    // }
  });
});

