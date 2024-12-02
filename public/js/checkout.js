document.addEventListener("DOMContentLoaded", function () {
  function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    let formattedValue = '';
    const groups = value.match(/(\d{1,4})/g) || [];
    formattedValue = groups.join(' ');
    
    const cursorPosition = input.selectionStart;
    const addedSpaces = (formattedValue.match(/ /g) || []).length;
    const previousSpaces = (input.value.slice(0, cursorPosition).match(/ /g) || []).length;
    
    input.value = formattedValue;
    
    const newCursorPosition = cursorPosition + (addedSpaces - previousSpaces);
    input.setSelectionRange(newCursorPosition, newCursorPosition);
  }

  function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    let formattedValue = value;
    
    if (value.length >= 2) {
      const month = value.slice(0, 2);
      const year = value.slice(2, 4);
      
      if (parseInt(month) > 12) {
        formattedValue = '12' + year;
      } else {
        formattedValue = month + (year.length ? '/' + year : '');
      }
    }
    
    const cursorPosition = input.selectionStart;
    input.value = formattedValue;
    input.setSelectionRange(cursorPosition, cursorPosition);
  }

  function formatCVC(input) {
    let value = input.value.replace(/\D/g, '');
    input.value = value.slice(0, 4);
  }

  const cardNumber = document.getElementById("checkout_cardNumber");
  const cardExpiry = document.getElementById("checkout_cardExpiry");
  const cardCVC = document.getElementById("checkout_cardCvc");

  if (cardNumber) {
    cardNumber.addEventListener("input", (e) => {
      const cursorPosition = e.target.selectionStart;
      formatCardNumber(cardNumber);
      if (cursorPosition !== undefined) {
        e.target.setSelectionRange(cursorPosition, cursorPosition);
      }
    });
    cardNumber.setAttribute("maxlength", "19");
    cardNumber.setAttribute("placeholder", "XXXX XXXX XXXX XXXX");
  }

  if (cardExpiry) {
    cardExpiry.addEventListener("input", (e) => {
      const cursorPosition = e.target.selectionStart;
      formatExpiry(cardExpiry);
      if (cursorPosition !== undefined) {
        e.target.setSelectionRange(cursorPosition, cursorPosition);
      }
    });
    cardExpiry.setAttribute("maxlength", "5");
    cardExpiry.setAttribute("placeholder", "MM/YY");
  }

  if (cardCVC) {
    cardCVC.addEventListener("input", () => formatCVC(cardCVC));
    cardCVC.setAttribute("maxlength", "4");
    cardCVC.setAttribute("placeholder", "XXX");
  }
});
