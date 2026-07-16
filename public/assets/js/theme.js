/* ════════════════════════════════════════════════════════════════
   theme.js — Tema claro/escuro · HomeMadeGourmet DS
   Persiste em localStorage; respeita prefers-color-scheme no 1º acesso.
   Uso: <html> recebe data-theme antes do paint (snippet inline nas views)
        e qualquer botão .js-theme-toggle alterna o tema.
   ════════════════════════════════════════════════════════════════ */

"use strict";

const Theme = (() => {
    const KEY = "hmg_theme";

    function current() {
        return document.documentElement.getAttribute("data-theme") || "light";
    }

    function apply(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        try { localStorage.setItem(KEY, theme); } catch { /* storage indisponível */ }
        document.querySelectorAll(".js-theme-toggle").forEach((btn) => {
            btn.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
            const icon = btn.querySelector("i");
            if (icon) icon.className = theme === "dark" ? "las la-sun" : "las la-moon";
        });
    }

    function toggle() {
        apply(current() === "dark" ? "light" : "dark");
    }

    function init() {
        apply(current());
        document.querySelectorAll(".js-theme-toggle").forEach((btn) => {
            btn.addEventListener("click", toggle);
        });
    }

    return { init, toggle, current };
})();

window.addEventListener("DOMContentLoaded", Theme.init);
