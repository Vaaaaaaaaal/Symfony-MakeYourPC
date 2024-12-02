document.addEventListener("DOMContentLoaded", function () {
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

  function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, "").replace(/\D/g, "");
    let maskedValue = value.replace(/\d/g, "X");
    let formattedValue = maskedValue.replace(/(.{4})/g, "$1 ").trim();
    formattedValue = formattedValue.substring(0, 19);
    input.value = formattedValue;
    input.dataset.realValue = value;
  }

  function formatExpiry(input) {
    let value = input.value.replace(/\D/g, "");
    let maskedValue = value.replace(/\d/g, "X");
    let formattedValue = "";
    if (maskedValue.length >= 2) {
      formattedValue =
        maskedValue.substr(0, 2) + "/" + maskedValue.substr(2, 2);
    } else {
      formattedValue = maskedValue;
    }
    input.dataset.realValue = value;
    input.value = formattedValue;
  }

  function formatCVC(input) {
    let value = input.value.replace(/\D/g, "");
    input.value = value.substr(0, 4);
  }

  const cardNumber = document.getElementById("checkout_cardNumber");
  const cardExpiry = document.getElementById("checkout_cardExpiry");
  const cardCVC = document.getElementById("checkout_cardCvc");

  if (cardNumber) {
    cardNumber.addEventListener("keydown", onlyNumbers);
    cardNumber.addEventListener("input", () => formatCardNumber(cardNumber));
    cardNumber.setAttribute("maxlength", "19");
  }

  if (cardExpiry) {
    cardExpiry.addEventListener("keydown", onlyNumbers);
    cardExpiry.addEventListener("input", () => formatExpiry(cardExpiry));
    cardExpiry.setAttribute("maxlength", "5");
  }

  if (cardCVC) {
    cardCVC.addEventListener("keydown", onlyNumbers);
    cardCVC.addEventListener("input", () => formatCVC(cardCVC));
    cardCVC.setAttribute("maxlength", "4");
  }
});
