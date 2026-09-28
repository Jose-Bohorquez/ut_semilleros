/* #archivo: frontend/modules/auth/reset-password.module.js
   Página pública para: (a) restablecer contraseña olvidada, y
   (b) activar una cuenta creada por un admin (mismo mecanismo de token de
   Laravel, mismo endpoint POST /reset-password — solo cambia el texto según
   el query param ?activation=1). Antes NO EXISTÍA esta página en el frontend
   aunque el backend ya generaba el enlace — el link llegaba a un 404.
   (Jose, 2026-08-31)
   ─────────────────────────────────────────────────────────────────── */

import { apiFetch } from "../../services/api.service.js";
import { escapeHtml }      from "../../core/escape.js";

export const resetPasswordModule = {
    init() {
        const params = new URLSearchParams(window.location.search);
        const token = params.get("token") || "";
        const email = params.get("email") || "";
        const isActivation = params.get("activation") === "1";

        render({ token, email, isActivation });
        bindEvents({ token, email });
    }
};

function render({ token, email, isActivation }) {
    const title = isActivation ? "Activa tu cuenta" : "Restablece tu contraseña";
    const subtitle = isActivation
        ? "Define tu contraseña para poder ingresar por primera vez."
        : "Ingresa tu nueva contraseña para recuperar el acceso.";

    if (!token || !email) {
        document.getElementById("app").innerHTML = `
        <div class="login-bg">
            <main class="login-main" style="display:flex;align-items:center;justify-content:center">
                <div class="login-form-side" style="max-width:480px;width:100%">
                    <div class="login-card" style="text-align:center">
                        <i class="fas fa-exclamation-triangle" style="font-size:40px;color:var(--color-error,#dc2626);margin-bottom:16px"></i>
                        <h1>Enlace inválido</h1>
                        <p>Este enlace no es válido o está incompleto. Solicita uno nuevo.</p>
                        <a href="/forgot-password" style="display:inline-block;margin-top:16px;color:var(--color-primary)">
                            Solicitar enlace de recuperación
                        </a>
                    </div>
                </div>
            </main>
        </div>`;
        return;
    }

    document.getElementById("app").innerHTML = `
    <div class="login-bg">
        <main class="login-main" style="display:flex;align-items:center;justify-content:center">
            <div class="login-form-side" style="max-width:480px;width:100%">
                <div class="login-card">

                    <div class="login-card-header">
                        <div class="login-logo-wrapper">
                            <img src="assets/images/login/logo.png" alt="Universidad del Tolima" style="height:80px;width:auto">
                        </div>
                        <h1>${title}</h1>
                        <p>${subtitle}</p>
                        <div class="login-divider"></div>
                    </div>

                    <p style="text-align:center;font-size:13px;color:var(--color-text-4);margin:-12px 0 16px">
                        <i class="fas fa-user"></i> ${escapeHtml(email)}
                    </p>

                    <div id="rpBanner" style="display:none;margin-bottom:16px"></div>

                    <form id="resetPasswordForm">
                        <div class="login-input-group">
                            <label for="rp-password">
                                <i class="fas fa-lock" style="margin-right:6px;color:var(--color-text-4)"></i>
                                ${isActivation ? "Crea tu contraseña" : "Nueva contraseña"}
                            </label>
                            <input type="password" id="rp-password" name="password" class="login-input"
                                   placeholder="Mínimo 6 caracteres" autocomplete="new-password" required minlength="6">
                            <span class="login-field-error" id="err-rp-password"></span>
                        </div>

                        <div class="login-input-group">
                            <label for="rp-password-confirm">
                                <i class="fas fa-lock" style="margin-right:6px;color:var(--color-text-4)"></i>
                                Confirmar contraseña
                            </label>
                            <input type="password" id="rp-password-confirm" name="password_confirmation" class="login-input"
                                   placeholder="Repite la contraseña" autocomplete="new-password" required minlength="6">
                            <span class="login-field-error" id="err-rp-password-confirm"></span>
                        </div>

                        <button type="submit" class="login-submit-btn" id="rpSubmitBtn">
                            <i class="fas fa-check"></i>
                            ${isActivation ? "Activar cuenta" : "Restablecer contraseña"}
                        </button>
                    </form>

                </div>
            </div>
        </main>
    </div>`;
}

function bindEvents({ token, email }) {
    document.getElementById("resetPasswordForm")?.addEventListener("submit", async e => {
        e.preventDefault();

        const password = document.getElementById("rp-password").value;
        const confirm = document.getElementById("rp-password-confirm").value;
        const btn = document.getElementById("rpSubmitBtn");
        const banner = document.getElementById("rpBanner");
        const errPass = document.getElementById("err-rp-password");
        const errConfirm = document.getElementById("err-rp-password-confirm");

        errPass.textContent = "";
        errConfirm.textContent = "";
        banner.style.display = "none";

        if (password.length < 6) {
            errPass.textContent = "Debe tener al menos 6 caracteres";
            return;
        }
        if (password !== confirm) {
            errConfirm.textContent = "Las contraseñas no coinciden";
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            await apiFetch("/reset-password", {
                method: "POST",
                body: JSON.stringify({
                    token,
                    email,
                    password,
                    password_confirmation: confirm,
                }),
            });

            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:8px;font-size:14px;background:var(--color-success-light,#dcfce7);
                border:1px solid var(--color-success-border,#86efac);color:var(--color-success-text,#166534)`;
            banner.innerHTML = `<i class="fas fa-check-circle"></i> Contraseña guardada. Redirigiendo al inicio de sesión...`;

            document.getElementById("resetPasswordForm").style.display = "none";

            setTimeout(() => { window.location.href = "/"; }, 2000);

        } catch (err) {
            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:8px;font-size:14px;background:var(--color-error-light,#fee2e2);
                border:1px solid var(--color-error-border,#fca5a5);color:var(--color-error-text,#991b1b)`;
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(err.message)}`;

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Guardar';
        }
    });
}
