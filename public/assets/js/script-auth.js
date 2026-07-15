/* ============================================================
   script-auth.js — Login e Cadastro via API JSON
   Padrão de referência: AuthService · HomeMadeGourmet
   ============================================================ */

"use strict";

const AuthPage = (() => {
    /* ── Helpers ─────────────────────────────────────────────── */

    const el = (id) => document.getElementById(id);

    function setBtn(id, loading, label) {
        const btn = el(id);
        if (!btn) return;
        btn.disabled = loading;
        btn.textContent = label;
    }

    function showAlert(id, msg, type) {
        const box = el(id);
        if (!box) return;
        box.textContent = msg;
        box.className = `alert show ${type}`;
    }

    function clearAlert(id) {
        const box = el(id);
        if (!box) return;
        box.className = "alert";
        box.textContent = "";
    }

    function bindEnter(inputId, callback) {
        const input = el(inputId);
        if (input) {
            input.addEventListener("keydown", (e) => {
                if (e.key === "Enter") callback();
            });
        }
    }

    function parseApiError(data) {
        if (!data || !data.detail) return "Erro desconhecido.";
        return String(data.detail);
    }

    /* ── Toggle login ⇄ cadastro ─────────────────────────────── */

    function _initToggle() {
        const container = el("auth-container");
        if (!container) return;

        const showRegister = (e) => { if (e) e.preventDefault(); container.classList.add("active"); };
        const showLogin = (e) => { if (e) e.preventDefault(); container.classList.remove("active"); };

        el("btn-show-register")?.addEventListener("click", showRegister);
        el("btn-show-login")?.addEventListener("click", showLogin);
        el("btnR-mob")?.addEventListener("click", showRegister);
        el("btnL-mob")?.addEventListener("click", showLogin);
    }

    /* ── Medidor de força de senha (5 níveis) ────────────────── */

    function _initPasswordStrength() {
        el("reg-senha")?.addEventListener("input", _updateStrength);
    }

    function _updateStrength() {
        const value = el("reg-senha")?.value || "";
        const fill = el("strength-fill");
        const label = el("strength-label");
        if (!fill || !label) return;

        let score = 0;
        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        if (value.length >= 14) score++;

        const levels = [
            { pct: "0%", bg: "transparent", txt: "", color: "" },
            { pct: "20%", bg: "#f87171", txt: "FRACA", color: "#f87171" },
            { pct: "45%", bg: "#fbbf24", txt: "RAZOÁVEL", color: "#fbbf24" },
            { pct: "65%", bg: "#facc15", txt: "BOA", color: "#facc15" },
            { pct: "85%", bg: "#4ade80", txt: "FORTE", color: "#4ade80" },
            { pct: "100%", bg: "#22c55e", txt: "EXCELENTE", color: "#22c55e" },
        ];

        const lvl = levels[Math.min(score, levels.length - 1)];
        fill.style.width = lvl.pct;
        fill.style.background = lvl.bg;
        label.textContent = lvl.txt;
        label.style.color = lvl.color;
    }

    /* ── Cadastro ────────────────────────────────────────────── */

    async function register() {
        clearAlert("reg-alert");

        const nome = (el("reg-nome")?.value || "").trim();
        const email = (el("reg-email")?.value || "").trim();
        const senha = el("reg-senha")?.value || "";
        const senha2 = el("reg-senha2")?.value || "";
        const categoria = document.querySelector('input[name="categoria"]:checked')?.value || "";

        if (!nome) return showAlert("reg-alert", "Informe seu nome de usuário.", "error");
        if (!email) return showAlert("reg-alert", "Informe seu email.", "error");
        if (!senha) return showAlert("reg-alert", "Informe sua senha.", "error");
        if (senha !== senha2) return showAlert("reg-alert", "As senhas não coincidem.", "error");
        if (!categoria) return showAlert("reg-alert", "Selecione sua categoria favorita.", "error");

        setBtn("btn-reg", true, "AGUARDE...");

        try {
            const res = await fetch("./api/register.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nome, email, senha, categoria }),
            });
            const data = await res.json();

            if (res.ok) {
                showAlert("log-alert", "Cadastro realizado com sucesso! Faça login.", "success");
                _clearRegisterForm();
                el("auth-container")?.classList.remove("active");
            } else {
                showAlert("reg-alert", parseApiError(data), "error");
            }
        } catch (err) {
            showAlert("reg-alert", "Falha de conexão. Tente novamente.", "error");
        } finally {
            setBtn("btn-reg", false, "CADASTRAR");
        }
    }

    function _clearRegisterForm() {
        ["reg-nome", "reg-email", "reg-senha", "reg-senha2"].forEach((id) => {
            const input = el(id);
            if (input) input.value = "";
        });
        const checked = document.querySelector('input[name="categoria"]:checked');
        if (checked) checked.checked = false;
        _updateStrength();
    }

    /* ── Login ───────────────────────────────────────────────── */

    async function login() {
        clearAlert("log-alert");

        const email = (el("log-email")?.value || "").trim();
        const senha = el("log-senha")?.value || "";

        if (!email) return showAlert("log-alert", "Informe seu email.", "error");
        if (!senha) return showAlert("log-alert", "Informe sua senha.", "error");

        setBtn("btn-log", true, "AUTENTICANDO...");

        try {
            const res = await fetch("./api/login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, senha }),
            });
            const data = await res.json();

            if (res.ok) {
                showAlert("log-alert", "Autenticado! Redirecionando...", "success");
                setTimeout(() => { location.href = "index.php"; }, 700);
            } else {
                showAlert("log-alert", parseApiError(data), "error");
                setBtn("btn-log", false, "CONECTAR");
            }
        } catch (err) {
            showAlert("log-alert", "Falha de conexão. Tente novamente.", "error");
            setBtn("btn-log", false, "CONECTAR");
        }
    }

    /* ── Boot ────────────────────────────────────────────────── */

    function init() {
        _initToggle();
        _initPasswordStrength();
        bindEnter("log-senha", login);
        bindEnter("reg-senha2", register);
    }

    return { init, register, login };
})();

function registerUser() { AuthPage.register(); }
function loginUser() { AuthPage.login(); }

function toggleEye(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    const icon = btn.querySelector("i");
    if (icon) {
        icon.className = isPassword ? "las la-eye-slash" : "las la-eye";
        icon.style.color = isPassword ? "#1DA1F2" : "#111";
    }
}

window.addEventListener("DOMContentLoaded", AuthPage.init);
