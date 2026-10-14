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
    /* ── Painel de filtros ───────────────────────────────────────────
       Regra única: o painel SÓ abre por ação explícita do usuário no
       botão. Carregamento, recarga, navegação (incluindo voltar/avançar
       com filtros ativos) e re-renderizações sempre começam fechados —
       o estado nunca é inferido dos filtros aplicados. */
    function initFilterToggle() {
        const btn = document.getElementById("btnFiltros");
        const panel = document.getElementById("filtersPanel");
        if (!btn || !panel) return;

        function setOpen(open) {
            panel.hidden = !open;
            btn.setAttribute("aria-expanded", open ? "true" : "false");
        }

        // Estado inicial: fechado, sempre. O servidor já entrega o painel
        // com `hidden`; aqui apenas sincronizamos o rótulo acessível.
        setOpen(false);

        // bfcache: voltar para a página restaura o DOM como estava ao sair.
        // Sem isto, um painel aberto reaparece aberto ao usar "voltar".
        window.addEventListener("pageshow", () => setOpen(false));

        btn.addEventListener("click", () => {
            const abrindo = panel.hidden;
            setOpen(abrindo);
            // Teclado: ao abrir, leva o foco para o primeiro controle do
            // painel; ao fechar, devolve o foco ao botão.
            if (abrindo) {
                panel.querySelector("input:not(:disabled), select, button")?.focus();
            } else {
                btn.focus();
            }
        });

        // Esc fecha de qualquer lugar enquanto o painel estiver aberto.
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && !panel.hidden) {
                setOpen(false);
                btn.focus();
            }
        });
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
                /* erro de rede: nextUrl continua apontando para a mesma
                   página, então o botão segue disponível para nova tentativa */
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
