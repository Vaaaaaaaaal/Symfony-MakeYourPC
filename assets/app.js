/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import { Turbo } from "@hotwired/turbo-rails";
import { createIcons, Icons } from "lucide";
import "./js/animations.js";
import "./styles/admin.css";
import "./styles/app.css";

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");

createIcons({
  icons: Icons,
});
Turbo.start();
