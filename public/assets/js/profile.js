/* ════════════════════════════════════════════════════════════════
   profile.js — Perfil do usuário · HomeMadeGourmet DS
   "Editar dados" libera os campos e o botão Salvar (prevenção de
   envio acidental); feedback de carregamento no submit.
   ════════════════════════════════════════════════════════════════ */

"use strict";

window.addEventListener("DOMContentLoaded", () => {
    const btnEditar = document.getElementById("alterDataButton");
    const btnSalvar = document.getElementById("btnSalvar");
    const form = document.getElementById("formPerfil");

    btnEditar?.addEventListener("click", () => {
        form?.querySelectorAll("input").forEach((input) => input.removeAttribute("disabled"));
        btnSalvar?.removeAttribute("disabled");
        btnEditar.setAttribute("disabled", "disabled");
        document.getElementById("nome")?.focus();
    });

    form?.addEventListener("submit", () => {
        if (btnSalvar) {
            btnSalvar.setAttribute("aria-busy", "true");
            btnSalvar.textContent = "Salvando…";
        }
    });
});
