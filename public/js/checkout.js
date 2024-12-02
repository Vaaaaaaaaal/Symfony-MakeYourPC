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
    let formattedValue = "";

    if (value.length >= 2) {
      formattedValue = value.substr(0, 2) + "/" + value.substr(2, 2);
    } else {
      formattedValue = value;
    }

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
<<<<<<< HEAD
    cardNumber.setAttribute("maxlength", "19");
=======
    cardNumber.setAttribute("maxlength", "19"); 
>>>>>>> 4fc1b50709ed1167737bebce28ba0cf3e5872b38
  }

  if (cardExpiry) {
    cardExpiry.addEventListener("keydown", onlyNumbers);
    cardExpiry.addEventListener("input", () => formatExpiry(cardExpiry));
<<<<<<< HEAD
    cardExpiry.setAttribute("maxlength", "5");
=======
    cardExpiry.setAttribute("maxlength", "5"); 
>>>>>>> 4fc1b50709ed1167737bebce28ba0cf3e5872b38
  }

  if (cardCVC) {
    cardCVC.addEventListener("keydown", onlyNumbers);
    cardCVC.addEventListener("input", () => formatCVC(cardCVC));
    cardCVC.setAttribute("maxlength", "4");
  }
});
