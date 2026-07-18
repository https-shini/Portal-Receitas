/* ════════════════════════════════════════════════════════════════
   catalog.js — Catálogo (home) · HomeMadeGourmet DS
   ① Abre/fecha o painel de filtros (acessível).
   ② Ordenação com auto-submit.
   ③ "Carregar mais" como progressive enhancement sobre a paginação
      numerada (que funciona sem JS): busca a próxima página e anexa os
      cards à grade.
   ════════════════════════════════════════════════════════════════ */

"use strict";

(function () {
    /* ── Painel de filtros ───────────────────────────────────────── */
    function initFilterToggle() {
        const btn = document.getElementById("btnFiltros");
        const panel = document.getElementById("filtersPanel");
        if (!btn || !panel) return;

        function setOpen(open) {
            panel.hidden = !open;
            btn.setAttribute("aria-expanded", open ? "true" : "false");
        }

        // Começa aberto se já houver categoria marcada (filtro ativo).
        setOpen(!!panel.querySelector("input[type=checkbox]:checked"));

        btn.addEventListener("click", () => setOpen(panel.hidden));
    }

    /* ── Ordenação: aplica ao mudar ──────────────────────────────── */
    function initSort() {
        const select = document.querySelector(".js-ordenar");
        select?.addEventListener("change", () => select.form?.submit());
    }

    /* ── Carregar mais (anexa a próxima página) ──────────────────── */
    function initLoadMore() {
        const nav = document.getElementById("pagination");
        const grid = document.getElementById("gradeReceitas");
        if (!nav || !grid) return;

        let nextUrl = nav.dataset.next || "";
        if (!nextUrl) return; // só há páginas anteriores; mantém a navegação numerada

        nav.innerHTML = '<button type="button" class="btn btn--soft" id="btnCarregarMais">Carregar mais receitas</button>';
        const btn = document.getElementById("btnCarregarMais");

        btn.addEventListener("click", async () => {
            if (!nextUrl) return;
            btn.setAttribute("aria-busy", "true");
            btn.textContent = "Carregando…";
            try {
                const res = await fetch(nextUrl, { headers: { "X-Requested-With": "fetch" } });
                const doc = new DOMParser().parseFromString(await res.text(), "text/html");
                doc.querySelectorAll("#gradeReceitas > li").forEach((li) => grid.appendChild(li));
                nextUrl = doc.getElementById("pagination")?.dataset.next || "";
            } catch (e) {
                nextUrl = nextUrl; // erro de rede: permite tentar de novo
            }
            if (nextUrl) {
                btn.removeAttribute("aria-busy");
                btn.textContent = "Carregar mais receitas";
            } else {
                nav.remove();
            }
        });
    }

    window.addEventListener("DOMContentLoaded", () => {
        initFilterToggle();
        initSort();
        initLoadMore();
    });
})();
