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

    /* ── Comentários (publicar / excluir o próprio) ──────────────── */
    function esc(s) {
        const d = document.createElement("div");
        d.textContent = String(s);
        return d.innerHTML;
    }

    function initComments() {
        const form = document.querySelector(".js-comment-form");
        const list = document.getElementById("comentarios");
        const count = document.getElementById("comentariosContagem");
        const vazio = document.getElementById("comentariosVazio");
        if (!list) return;

        const csrf = form ? form.dataset.csrf : "";

        function refreshCount() {
            const n = list.querySelectorAll(".comment").length;
            if (count) count.textContent = "(" + n + ")";
            if (vazio) vazio.hidden = n > 0;
        }

        function bindDelete(btn) {
            btn.addEventListener("click", async () => {
                const li = btn.closest(".comment");
                btn.disabled = true;
                try {
                    const res = await fetch("./api/comments.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ acao: "excluir", idComentario: Number(li.dataset.id), _csrf: csrf }),
                    });
                    if (res.ok) { li.remove(); refreshCount(); }
                } catch (e) {
                    /* mantém */
                } finally {
                    btn.disabled = false;
                }
            });
        }

        function render(c) {
            const li = document.createElement("li");
            li.className = "comment";
            li.dataset.id = c.id;
            li.innerHTML =
                '<div class="comment__head"><span class="comment__author"><i class="las la-user-circle" aria-hidden="true"></i> '
                + esc(c.autor) + '</span><span class="comment__date">' + esc(c.data) + "</span></div>"
                + '<p class="comment__text">' + esc(c.texto).replace(/\n/g, "<br>") + "</p>"
                + '<button type="button" class="comment__delete js-comment-delete" aria-label="Excluir comentário"><i class="las la-trash" aria-hidden="true"></i> Excluir</button>';
            return li;
        }

        list.querySelectorAll(".js-comment-delete").forEach(bindDelete);

        form?.addEventListener("submit", async (e) => {
            e.preventDefault();
            const ta = form.querySelector("textarea");
            const alerta = document.getElementById("comentarioAlerta");
            const texto = ta.value.trim();
            if (!texto) return;
            const btn = form.querySelector("button[type=submit]");
            btn.disabled = true;
            try {
                const res = await fetch("./api/comments.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ idReceita: Number(form.dataset.id), texto, _csrf: csrf }),
                });
                if (res.status === 401) { window.location.href = "login.php?erro=1"; return; }
                const data = await res.json();
                if (res.ok) {
                    const li = render(data);
                    list.prepend(li);
                    bindDelete(li.querySelector(".js-comment-delete"));
                    refreshCount();
                    ta.value = "";
                    if (alerta) { alerta.className = "alert"; alerta.textContent = ""; }
                } else if (alerta) {
                    alerta.className = "alert show alert--error";
                    alerta.textContent = data.detail || "Não foi possível comentar.";
                }
            } catch (err) {
                if (alerta) { alerta.className = "alert show alert--error"; alerta.textContent = "Falha de conexão."; }
            } finally {
                btn.disabled = false;
            }
        });
    }

    /* ── Calculadora de porções (nutrição total em tempo real) ───── */
    function initPortion() {
        const box = document.querySelector(".js-portion");
        if (!box) return;

        const perServing = {
            kcal: parseFloat(box.dataset.kcal) || 0,
            p: parseFloat(box.dataset.p),
            c: parseFloat(box.dataset.c),
            g: parseFloat(box.dataset.g),
        };
        let servings = Math.max(1, parseInt(box.dataset.servings, 10) || 1);

        const nEl = box.querySelector(".js-portion-n");
        const out = {
            kcal: box.querySelector(".js-portion-kcal"),
            p: box.querySelector(".js-portion-p"),
            c: box.querySelector(".js-portion-c"),
            g: box.querySelector(".js-portion-g"),
        };

        const fmt = (v) => (Math.round(v * 10) / 10).toFixed(1).replace(".", ",");

        function render() {
            if (nEl) nEl.textContent = servings;
            if (out.kcal) out.kcal.textContent = fmt(perServing.kcal * servings);
            if (out.p && !Number.isNaN(perServing.p)) out.p.textContent = fmt(perServing.p * servings);
            if (out.c && !Number.isNaN(perServing.c)) out.c.textContent = fmt(perServing.c * servings);
            if (out.g && !Number.isNaN(perServing.g)) out.g.textContent = fmt(perServing.g * servings);
        }

        box.querySelector(".js-portion-dec")?.addEventListener("click", () => {
            if (servings > 1) { servings--; render(); }
        });
        box.querySelector(".js-portion-inc")?.addEventListener("click", () => {
            if (servings < 50) { servings++; render(); }
        });
    }

    window.addEventListener("DOMContentLoaded", () => {
        initVideoConsent();
        initShare();
        initFavorite();
        initRate();
        initGallery();
        initComments();
        initPortion();
    });
})();
