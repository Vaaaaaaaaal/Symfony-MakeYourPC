document.addEventListener("DOMContentLoaded", function () {
  const ratingContainer = document.querySelector(".rating-stars.interactive");
  if (!ratingContainer) return;

  const stars = ratingContainer.querySelectorAll(".star");
  const productId =
    ratingContainer.closest(".product-rating").dataset.productId;

  stars.forEach((star) => {
    star.addEventListener("click", function () {
      const rating = this.dataset.rating;

      fetch("/review/rate", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          productId: productId,
          rating: rating,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Mise à jour visuelle
            stars.forEach((s) => {
              s.classList.remove("filled");
              if (s.dataset.rating <= rating) {
                s.classList.add("filled");
              }
            });
            document.querySelector(
              ".rating-value"
            ).textContent = `${data.newRating}/5`;
          }
        })
        .catch((error) => {
          console.error("Erreur:", error);
          alert("Une erreur est survenue lors de la notation");
        });
    });
  });
});
