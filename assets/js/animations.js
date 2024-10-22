document.addEventListener("DOMContentLoaded", (event) => {
  const animateOnScroll = () => {
    const elements = document.querySelectorAll(".animate-on-scroll");
    elements.forEach((element) => {
      const elementTop = element.getBoundingClientRect().top;
      const elementBottom = element.getBoundingClientRect().bottom;
      if (elementTop < window.innerHeight && elementBottom > 0) {
        element.classList.add("animated");
      }
    });
  };

  window.addEventListener("scroll", animateOnScroll);
  animateOnScroll();

  // Animation du logo
  const logo = document.querySelector(".logo");
  logo.addEventListener("mouseover", () => {
    logo.style.transform = "scale(1.1)";
  });
  logo.addEventListener("mouseout", () => {
    logo.style.transform = "scale(1)";
  });

  // Animation des liens de navigation
  const navLinks = document.querySelectorAll(".nav-links a");
  navLinks.forEach((link) => {
    link.addEventListener("mouseover", () => {
      link.style.textShadow = "0 0 5px var(--accent-color)";
    });
    link.addEventListener("mouseout", () => {
      link.style.textShadow = "none";
    });
  });
});
