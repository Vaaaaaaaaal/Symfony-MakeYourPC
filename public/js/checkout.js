document.addEventListener("DOMContentLoaded", function () {
  // Fonction pour permettre uniquement les chiffres
  function onlyNumbers(event) {
    if (
      !/[0-9]/.test(event.key) &&
      event.key !== "Backspace" &&
      event.key !== "Delete" &&
      event.key !== "Tab"
    ) {
      event.preventDefault();
    }
  }

  // Fonction pour formater le numéro de carte
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

  // Fonction pour formater la date d'expiration
  function formatExpiry(input) {
    let value = input.value.replace(/\D/g, "");
    let formattedValue = "";

    if (value.length >= 2) {
      formattedValue = value.substr(0, 2) + "/" + value.substr(2, 2);
    } else {
      formattedValue = value;
    }

    input.value = formattedValue;
  }

  // Fonction pour formater le CVC
  function formatCVC(input) {
    let value = input.value.replace(/\D/g, "");
    input.value = value.substr(0, 4);
  }

  // Récupération des éléments
  const cardNumber = document.getElementById("checkout_cardNumber");
  const cardExpiry = document.getElementById("checkout_cardExpiry");
  const cardCVC = document.getElementById("checkout_cardCvc");

  // Application des événements pour le numéro de carte
  if (cardNumber) {
    cardNumber.addEventListener("keydown", onlyNumbers);
    cardNumber.addEventListener("input", () => formatCardNumber(cardNumber));
    cardNumber.setAttribute("maxlength", "19"); // 16 chiffres + 3 espaces
  }

  // Application des événements pour la date d'expiration
  if (cardExpiry) {
    cardExpiry.addEventListener("keydown", onlyNumbers);
    cardExpiry.addEventListener("input", () => formatExpiry(cardExpiry));
    cardExpiry.setAttribute("maxlength", "5"); // MM/YY
  }

  // Application des événements pour le CVC
  if (cardCVC) {
    cardCVC.addEventListener("keydown", onlyNumbers);
    cardCVC.addEventListener("input", () => formatCVC(cardCVC));
    cardCVC.setAttribute("maxlength", "4");
  }
});
