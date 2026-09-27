/* #archivo: frontend/modules/auth/forgot-password.module.js
   Página pública "¿Olvidaste tu contraseña?" — antes el link del login
   apuntaba a href="#" (no hacía nada). Reutiliza el estilo del login.
   (Jose, 2026-08-31)
   ─────────────────────────────────────────────────────────────────── */

import { apiFetch } from "../../services/api.service.js";

export const forgotPasswordModule = {
    init() {
        render();
        bindEvents();
    }
};

function render() {
    document.getElementById("app").innerHTML = `
    <div class="login-bg">
        <main class="login-main" style="display:flex;align-items:center;justify-content:center">
            <div class="login-form-side" style="max-width:480px;width:100%">
                <div class="login-card">

                    <div class="login-card-header">
                        <div class="login-logo-wrapper">
                            <img src="assets/images/login/logo.png" alt="Universidad del Tolima" style="height:80px;width:auto">
                        </div>
                        <h1>¿Olvidaste tu contraseña?</h1>
                        <p>Ingresa tu correo institucional y te enviaremos un enlace para restablecerla.</p>
                        <div class="login-divider"></div>
                    </div>

                    <div id="fpBanner" style="display:none;margin-bottom:16px"></div>

                    <form id="forgotPasswordForm">
                        <div class="login-input-group">
                            <label for="fp-email">
                                <i class="fas fa-user" style="margin-right:6px;color:var(--color-text-4)"></i>
                                Correo electrónico
                            </label>
                            <input type="email" id="fp-email" name="email" class="login-input"
                                   placeholder="correo@ut.edu.co" autocomplete="username" required>
                            <span class="login-field-error" id="err-fp-email"></span>
                        </div>

                        <button type="submit" class="login-submit-btn" id="fpSubmitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Enviar enlace
                        </button>
                    </form>

                    <div style="text-align:center;margin-top:20px">
                        <a href="/" style="font-size:14px;color:var(--color-text-4);text-decoration:none">
                            <i class="fas fa-arrow-left"></i> Volver a iniciar sesión
                        </a>
                    </div>

                </div>
            </div>
        </main>
    </div>`;
}

function bindEvents() {
    document.getElementById("forgotPasswordForm")?.addEventListener("submit", async e => {
        e.preventDefault();

        const email = document.getElementById("fp-email").value.trim();
        const btn = document.getElementById("fpSubmitBtn");
        const banner = document.getElementById("fpBanner");
        const errEl = document.getElementById("err-fp-email");

        errEl.textContent = "";
        banner.style.display = "none";

        if (!email) {
            errEl.textContent = "Ingresa tu correo electrónico";
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            const data = await apiFetch("/forgot-password", {
                method: "POST",
                body: JSON.stringify({ email }),
            });

            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:8px;font-size:14px;background:var(--color-success-light,#dcfce7);
                border:1px solid var(--color-success-border,#86efac);color:var(--color-success-text,#166534)`;
            banner.innerHTML = `<i class="fas fa-check-circle"></i> ${data?.message || "Si el correo existe, recibirás un enlace en unos minutos."}`;

            document.getElementById("forgotPasswordForm").reset();

        } catch (err) {
            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:8px;font-size:14px;background:var(--color-error-light,#fee2e2);
                border:1px solid var(--color-error-border,#fca5a5);color:var(--color-error-text,#991b1b)`;
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${err.message}`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar enlace';
        }
    });
}
