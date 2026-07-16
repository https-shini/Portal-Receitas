/* ════════════════════════════════════════════════════════════════
   home.js — Página inicial · HomeMadeGourmet DS
   Modal de receita acessível (foco gerenciado, Esc, backdrop),
   conteúdo clonado de <template> (iframes só carregam ao abrir),
   skeleton de feedback ao buscar e fechamento do menu do usuário.
   ════════════════════════════════════════════════════════════════ */

"use strict";

const HomePage = (() => {
    let ultimoFoco = null;

    /* ── Modal de receita ────────────────────────────────────────── */

    function abrirReceita(id, gatilho) {
        const backdrop = document.getElementById("modalReceita");
        const conteudo = document.getElementById("modalConteudo");
        const template = document.getElementById("tplReceita" + id);
        if (!backdrop || !conteudo || !template) return;

        ultimoFoco = gatilho;
        conteudo.replaceChildren(template.content.cloneNode(true));
        backdrop.hidden = false;
        backdrop.classList.add("open");
        document.body.style.overflow = "hidden";

        conteudo.querySelector(".js-fechar-receita")?.focus();
        conteudo.querySelector(".js-fechar-receita")
            ?.addEventListener("click", fecharReceita);
    }

    function fecharReceita() {
        const backdrop = document.getElementById("modalReceita");
        const conteudo = document.getElementById("modalConteudo");
        if (!backdrop || backdrop.hidden) return;

        backdrop.classList.remove("open");
        backdrop.hidden = true;
        conteudo.replaceChildren();          // descarta o iframe → para o vídeo
        document.body.style.overflow = "";
        ultimoFoco?.focus();
    }

    function _initModal() {
        document.querySelectorAll(".js-abrir-receita").forEach((btn) => {
            btn.addEventListener("click", () => abrirReceita(btn.dataset.receita, btn));
        });

        document.getElementById("modalReceita")?.addEventListener("click", (e) => {
            if (e.target === e.currentTarget) fecharReceita();
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") fecharReceita();
        });
    }

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

    /* ── Menu do usuário: fecha ao clicar fora / Esc ─────────────── */

    function _initUserMenu() {
        const menu = document.getElementById("userMenu");
        if (!menu) return;
        document.addEventListener("click", (e) => {
            if (menu.open && !menu.contains(e.target)) menu.open = false;
        });
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && menu.open) menu.open = false;
        });
    }

    function init() {
        _initModal();
        _initSearchFeedback();
        _initUserMenu();
    }

    return { init };
})();

window.addEventListener("DOMContentLoaded", HomePage.init);
