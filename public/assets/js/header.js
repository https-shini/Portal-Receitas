/* ════════════════════════════════════════════════════════════════
   header.js — Comportamento do header global · HomeMadeGourmet
   Incluído via partials/footer.php — portanto ativo em TODA página
   que use o header (antes só rodava em index.php via home.js, o que
   deixava o menu do usuário "preso" aberto em perfil/termos/etc.).

   Responsabilidades:
   ① Alterna .is-scrolled no <header> conforme a rolagem (glass mais
      forte + sombra), com throttle via requestAnimationFrame.
   ② Fecha o menu do usuário (<details id="userMenu">) ao clicar fora
      ou pressionar Esc, devolvendo o foco ao botão que o abriu.
   ════════════════════════════════════════════════════════════════ */

"use strict";

(function () {
    const SCROLL_THRESHOLD = 8; // px

    /* ── Header: estado de rolagem ───────────────────────────────── */
    function initScrollState() {
        const header = document.getElementById("siteHeader");
        if (!header) return;

        let ticking = false;

        function update() {
            header.classList.toggle("is-scrolled", window.scrollY > SCROLL_THRESHOLD);
            ticking = false;
        }

        function onScroll() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(update);
        }

        update();
        window.addEventListener("scroll", onScroll, { passive: true });
    }

    /* ── Menu do usuário: fecha ao clicar fora / Esc ─────────────── */
    function initUserMenu() {
        const menu = document.getElementById("userMenu");
        if (!menu) return;

        document.addEventListener("click", (e) => {
            if (menu.open && !menu.contains(e.target)) menu.open = false;
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && menu.open) {
                menu.open = false;
                menu.querySelector("summary")?.focus();
            }
        });
    }

    function init() {
        initScrollState();
        initUserMenu();
    }

    window.addEventListener("DOMContentLoaded", init);
})();
