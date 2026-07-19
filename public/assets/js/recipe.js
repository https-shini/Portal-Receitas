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

    /* ── Avaliar (estrelas, usuário autenticado) ─────────────────── */
    function initRate() {
        const widget = document.querySelector(".js-rate");
        if (!widget) return;

        const stars = Array.from(widget.querySelectorAll(".rate-star"));
        let current = Number(widget.dataset.score) || 0;

        function paint(score) {
            stars.forEach((b) => {
                const s = Number(b.dataset.score);
                b.classList.toggle("is-on", s <= score);
                const icon = b.querySelector("i");
                if (icon) icon.className = (s <= score ? "las" : "lar") + " la-star";
                b.setAttribute("aria-pressed", s === current ? "true" : "false");
            });
        }

        function updateAverage(average, count) {
            const box = document.querySelector(".recipe__rating");
            if (!box) return;
            if (count > 0) {
                const media = String(average).replace(".", ",");
                box.innerHTML = '<i class="las la-star" aria-hidden="true"></i> '
                    + '<span class="rating-value">' + media + "</span> "
                    + '<span class="rating-count">(' + count + " avaliaç" + (count === 1 ? "ão" : "ões") + ")</span>";
            } else {
                box.innerHTML = '<span class="rating-empty">Sem avaliações ainda</span>';
            }
        }

        stars.forEach((b) => {
            b.addEventListener("mouseenter", () => paint(Number(b.dataset.score)));
            b.addEventListener("mouseleave", () => paint(current));
            b.addEventListener("click", async () => {
                const s = Number(b.dataset.score);
                const nota = s === current ? 0 : s; // reclicar a própria nota remove
                stars.forEach((x) => { x.disabled = true; });
                try {
                    const res = await fetch("./api/ratings.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ idReceita: Number(widget.dataset.id), nota, _csrf: widget.dataset.csrf }),
                    });
                    if (res.status === 401) { window.location.href = "login.php?erro=1"; return; }
                    const data = await res.json();
                    if (res.ok) {
                        current = Number(data.userScore) || 0;
                        paint(current);
                        updateAverage(data.average, data.count);
                    }
                } catch (e) {
                    /* falha de rede: mantém o estado atual */
                } finally {
                    stars.forEach((x) => { x.disabled = false; });
                }
            });
        });
    }

    /* ── Galeria: miniatura troca a imagem principal ─────────────── */
    function initGallery() {
        const strip = document.querySelector(".js-galeria");
        const main = document.getElementById("galeriaPrincipal");
        if (!strip || !main) return;

        const thumbs = Array.from(strip.querySelectorAll(".recipe__thumb"));
        thumbs.forEach((btn) => {
            btn.addEventListener("click", () => {
                main.src = btn.dataset.src;
                thumbs.forEach((b) => {
                    const active = b === btn;
                    b.classList.toggle("is-active", active);
                    b.setAttribute("aria-pressed", active ? "true" : "false");
                });
            });
        });
    }

    window.addEventListener("DOMContentLoaded", () => {
        initVideoConsent();
        initShare();
        initFavorite();
        initRate();
        initGallery();
    });
})();
