/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import { Turbo } from "@hotwired/turbo-rails";
import { createIcons, icons } from "lucide-static";
import "./js/animations.js";
import "./styles/admin.css";
import "./styles/app.css";

// Rendre lucide disponible globalement
window.lucide = {
  createIcons: createIcons,
  icons: icons,
};

// Initialisation au chargement
document.addEventListener("DOMContentLoaded", () => {
  window.lucide.createIcons();
});

// Réinitialisation après navigation Turbo
document.addEventListener("turbo:render", () => {
  window.lucide.createIcons();
});

Turbo.start();
