/* ════════════════════════════════════════════════════════════════
   recipe.js — Página individual de receita · HomeMadeGourmet DS
   ① Consentimento de terceiros (LGPD): o iframe do YouTube só entra no
      DOM após o usuário clicar em "Carregar vídeo".
   ② Compartilhar: Web Share API nativa com fallback para copiar o link.
   ════════════════════════════════════════════════════════════════ */

"use strict";

(function () {
    /* ── Vídeo: consentimento antes de carregar ──────────────────── */
    function initVideoConsent() {
        const consent = document.querySelector(".js-video-consent");
        const botao = consent?.querySelector(".js-carregar-video");
        const tpl = consent?.querySelector(".js-video-tpl");
        if (!consent || !botao || !tpl) return;

        botao.addEventListener("click", () => {
            consent.replaceChildren(tpl.content.cloneNode(true));
            consent.classList.remove("video-consent");
        });
    }

    /* ── Compartilhar ────────────────────────────────────────────── */
    function initShare() {
        const botao = document.querySelector(".js-compartilhar");
        if (!botao) return;

        botao.addEventListener("click", async () => {
            const dados = {
                title: botao.dataset.title || document.title,
                text: botao.dataset.title || "",
                url: window.location.href,
            };

            if (navigator.share) {
                try {
                    await navigator.share(dados);
                    return;
                } catch (e) {
                    if (e && e.name === "AbortError") return;
                }
            }

            try {
                await navigator.clipboard.writeText(window.location.href);
                flash(botao, "Link copiado!");
            } catch (e) {
                flash(botao, "Não foi possível copiar");
            }
        });
    }

    function flash(botao, msg) {
        const original = botao.innerHTML;
        botao.innerHTML = '<i class="las la-check" aria-hidden="true"></i> ' + msg;
        setTimeout(() => { botao.innerHTML = original; }, 1800);
    }

    /* ── Favoritar (usuário autenticado) ─────────────────────────── */
    function initFavorite() {
        const btn = document.querySelector(".js-favorito");
        if (!btn) return;

        btn.addEventListener("click", async () => {
            btn.disabled = true;
            try {
                const res = await fetch("./api/favorites.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ idReceita: Number(btn.dataset.id), _csrf: btn.dataset.csrf }),
                });

                if (res.status === 401) { window.location.href = "login.php?erro=1"; return; }

                const data = await res.json();
                if (res.ok) {
                    setState(btn, !!data.favorited);
                }
            } catch (e) {
                /* falha de rede: mantém o estado atual */
            } finally {
                btn.disabled = false;
            }
        });
    }

    function setState(btn, favorited) {
        btn.classList.toggle("is-active", favorited);
        btn.setAttribute("aria-pressed", favorited ? "true" : "false");
        const icon = btn.querySelector("i");
        if (icon) icon.className = favorited ? "las la-heart" : "lar la-heart";
        const label = btn.querySelector(".js-favorito-label");
        if (label) label.textContent = favorited ? "Favoritada" : "Favoritar";
    }

    window.addEventListener("DOMContentLoaded", () => {
        initVideoConsent();
        initShare();
        initFavorite();
    });
})();
