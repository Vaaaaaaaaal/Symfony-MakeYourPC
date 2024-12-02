document.addEventListener("DOMContentLoaded", function () {
  const addressSelect = document.querySelector("#checkout_savedAddress");
  if (addressSelect) {
    addressSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption.value) {
        fetch(`/address/${selectedOption.value}/get-data`)
          .then((response) => response.json())
          .then((data) => {
            document.querySelector("#checkout_firstName").value =
              data.firstname;
            document.querySelector("#checkout_lastName").value = data.lastname;
            document.querySelector("#checkout_address").value = data.address;
            document.querySelector("#checkout_postalCode").value = data.postal;
            document.querySelector("#checkout_city").value = data.city;
            document.querySelector("#checkout_phone").value = data.phone || "";
          });
      }
    });
  }
});
