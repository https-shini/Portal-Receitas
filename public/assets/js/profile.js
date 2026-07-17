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

    /*
     * Exclusão de conta (LGPD art. 18, VI): confirmação explícita em duas
     * etapas — senha atual + diálogo de confirmação — antes do POST.
     */
    const btnExcluir = document.getElementById("btnExcluirConta");
    const alerta = document.getElementById("alertaExclusao");

    function avisoExclusao(msg, tipo) {
        if (!alerta) return;
        alerta.textContent = msg;
        alerta.className = `alert show alert--${tipo}`;
    }

    btnExcluir?.addEventListener("click", async () => {
        const senha = document.getElementById("senhaExclusao")?.value || "";
        if (!senha) return avisoExclusao("Digite sua senha atual para confirmar.", "error");
        if (!window.confirm("Tem certeza? A exclusão é permanente e não pode ser desfeita.")) return;

        btnExcluir.setAttribute("aria-busy", "true");
        btnExcluir.textContent = "Excluindo…";

        try {
            const res = await fetch("./api/delete-account.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ senha }),
            });
            const data = await res.json();

            if (res.ok) {
                avisoExclusao(data.detail + " Redirecionando…", "success");
                setTimeout(() => { location.href = "login.php"; }, 1200);
            } else {
                avisoExclusao(data.detail || "Não foi possível excluir a conta.", "error");
                btnExcluir.removeAttribute("aria-busy");
                btnExcluir.textContent = "EXCLUIR CONTA DEFINITIVAMENTE";
            }
        } catch {
            avisoExclusao("Falha de conexão. Tente novamente.", "error");
            btnExcluir.removeAttribute("aria-busy");
            btnExcluir.textContent = "EXCLUIR CONTA DEFINITIVAMENTE";
        }
    });
});
