/* ════════════════════════════════════════════════════════════════
   home.js — Catálogo (home) · HomeMadeGourmet DS
   Feedback imediato ao buscar (skeleton). As receitas agora abrem em
   página própria (/receita/{id}/{slug}) — não há mais modal.
   (Comportamento do header/menu do usuário está em assets/js/header.js.)
   ════════════════════════════════════════════════════════════════ */

"use strict";

const HomePage = (() => {
    /* ── Feedback imediato ao buscar (skeleton) ──────────────────── */

    function _initSearchFeedback() {
        const form = document.getElementById("formSearch");
        const grade = document.getElementById("gradeReceitas");
        form?.addEventListener("submit", () => {
            const btn = document.getElementById("btnBuscar");
            if (btn) { btn.setAttribute("aria-busy", "true"); btn.textContent = "Buscando…"; }
            if (grade) {
                grade.replaceChildren(...Array.from({ length: 8 }, () => {
                    const li = document.createElement("li");
                    li.className = "skeleton skeleton-card";
                    return li;
                }));
            }
        });
    }

    function init() {
        _initSearchFeedback();
    }

    return { init };
})();

window.addEventListener("DOMContentLoaded", HomePage.init);
