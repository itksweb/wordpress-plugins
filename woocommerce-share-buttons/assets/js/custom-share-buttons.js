document.addEventListener("DOMContentLoaded", function () {
  const copyBtns = document.querySelectorAll(".share-btn.copy");

  // Create toast element
  const toast = document.createElement("div");
  toast.className = "copy-toast";
  toast.innerText = "Link copied!";
  document.body.appendChild(toast);

  function showToast() {
    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 2000);
  }

  copyBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const url = this.getAttribute("data-url");

      navigator.clipboard
        .writeText(url)
        .then(() => {
          // Change icon temporarily to checkmark
          this.innerHTML = '<i class="fas fa-check"></i>';
          showToast();

          setTimeout(() => {
            if (this.classList.contains("ig")) {
              this.innerHTML = '<i class="fab fa-instagram"></i>';
            } else if (this.classList.contains("tk")) {
              this.innerHTML = '<i class="fab fa-tiktok"></i>';
            } else {
              this.innerHTML = '<i class="fas fa-link"></i>';
            }
          }, 2000);
        })
        .catch((err) => {
          console.error("Copy failed:", err);
        });
    });
  });
});
