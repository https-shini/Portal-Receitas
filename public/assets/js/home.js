/* ════════════════════════════════════════════════════════════════
   home.js — Página inicial · HomeMadeGourmet DS
   Modal de receita acessível (foco gerenciado, Esc, backdrop),
   conteúdo clonado de <template> (iframes só carregam ao abrir)
   e skeleton de feedback ao buscar.
   (Comportamento do header/menu do usuário está em assets/js/header.js,
   carregado globalmente via partials/footer.php.)
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

        _initVideoConsent(conteudo);
    }

    /*
     * Consentimento de terceiros (LGPD): o iframe do YouTube fica num
     * <template> aninhado e só entra no DOM quando o usuário clica em
     * "Carregar vídeo" — antes disso nenhum dado é enviado ao Google.
     */
    function _initVideoConsent(conteudo) {
        const consent = conteudo.querySelector(".js-video-consent");
        const botao = consent?.querySelector(".js-carregar-video");
        const tpl = consent?.querySelector(".js-video-tpl");
        if (!consent || !botao || !tpl) return;

        botao.addEventListener("click", () => {
            consent.replaceChildren(tpl.content.cloneNode(true));
            consent.classList.remove("video-consent");
        });
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

    function init() {
        _initModal();
        _initSearchFeedback();
    }

    return { init };
})();

window.addEventListener("DOMContentLoaded", HomePage.init);
