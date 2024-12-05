document.addEventListener("DOMContentLoaded", function () {
  // Gestion du formulaire
  const form = document.querySelector("form");
  let isSubmitting = false;

  if (form) {
    form.addEventListener("submit", function (e) {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }
      isSubmitting = true;
    });
  }

  // Formatage des champs de paiement
  function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, "");
    let formattedValue = "";

    for (let i = 0; i < value.length && i < 16; i++) {
      if (i > 0 && i % 4 === 0) {
        formattedValue += " ";
      }
      formattedValue += value[i];
    }

    input.value = formattedValue;
  }

  function formatExpiry(input) {
    let value = input.value.replace(/\D/g, "");
    let formattedValue = value;

    if (value.length > 0) {
      const month = value.slice(0, 2);
      const year = value.slice(2, 4);

      if (value.length >= 2) {
        if (parseInt(month) > 12) {
          formattedValue = "12" + year;
        } else {
          formattedValue = month + (value.length > 2 ? "/" + year : "");
        }
      }
    }

    input.value = formattedValue;
  }

  function formatCVC(input) {
    let value = input.value.replace(/\D/g, "");
    input.value = value.slice(0, 4);
  }

  // Sélection des champs
  const cardNumber = document.querySelector(
    '[data-checkout-field="cardNumber"]'
  );
  const cardExpiry = document.querySelector(
    '[data-checkout-field="cardExpiry"]'
  );
  const cardCVC = document.querySelector('[data-checkout-field="cardCvc"]');

  // Application des écouteurs d'événements
  if (cardNumber) {
    cardNumber.addEventListener("input", function () {
      formatCardNumber(this);
    });
  }

  if (cardExpiry) {
    cardExpiry.addEventListener("input", function () {
      formatExpiry(this);
    });
  }

  if (cardCVC) {
    cardCVC.addEventListener("input", function () {
      formatCVC(this);
    });
  }
});
