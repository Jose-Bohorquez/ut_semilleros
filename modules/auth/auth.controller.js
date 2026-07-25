/* #archivo: /frontend/modules/auth/auth.controller.js */

import { apiFetch }        from "../../services/api.service.js";
import { setToken, setUser } from "../../services/storage.service.js";
import { navigateTo }      from "../../core/router.js";
import { initPushOnLogin } from "../../services/push.service.js";

export function initLoginController() {

    /* ================================
       PASSWORD TOGGLE
    ================================ */

    const toggleBtn = document.getElementById("togglePassword");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            const pwd     = document.getElementById("password");
            const eye     = document.getElementById("eyeIcon");
            const showing = pwd.type === "text";
            pwd.type      = showing ? "password" : "text";
            eye.classList.toggle("fa-eye",       showing);
            eye.classList.toggle("fa-eye-slash", !showing);
        });
    }

    /* ================================
       COUNTDOWN
    ================================ */

    function updateCountdown() {
        const now      = Date.now();
        const target   = now + 7 * 24 * 60 * 60 * 1000;
        const distance = target - now;

        const d = Math.floor(distance / (1000 * 60 * 60 * 24));
        const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((distance % (1000 * 60)) / 1000);

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(val).padStart(2, "0");
        };
        set("days",    d);
        set("hours",   h);
        set("minutes", m);
        set("seconds", s);
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    /* ================================
       INLINE VALIDATION HELPERS
    ================================ */

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    }

    function showError(inputId, msg) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(`err-${inputId}`);
        if (input) input.classList.add("field-invalid");
        if (err)   err.textContent = msg;
    }

    function clearError(inputId) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(`err-${inputId}`);
        if (input) {
            input.classList.remove("field-invalid");
            input.classList.add("field-valid");
        }
        if (err) err.textContent = "";
    }

    function validateEmail() {
        const val = (document.getElementById("email")?.value || "").trim();
        if (!val) { showError("email", "El correo es obligatorio"); return false; }
        if (!isValidEmail(val)) { showError("email", "Ingresa un correo válido"); return false; }
        clearError("email");
        return true;
    }

    function validatePassword() {
        const val = (document.getElementById("password")?.value || "").trim();
        if (!val) { showError("password", "La contraseña es obligatoria"); return false; }
        if (val.length < 4) { showError("password", "Contraseña demasiado corta"); return false; }
        clearError("password");
        return true;
    }

    /* Attach error spans to the login form fields */
    const emailInput = document.getElementById("email");
    const passInput  = document.getElementById("password");

    if (emailInput && !document.getElementById("err-email")) {
        const span = document.createElement("span");
        span.id        = "err-email";
        span.className = "login-field-error";
        emailInput.parentElement.appendChild(span);
    }

    if (passInput && !document.getElementById("err-password")) {
        const span = document.createElement("span");
        span.id        = "err-password";
        span.className = "login-field-error";
        passInput.closest(".relative")?.appendChild(span);
    }

    emailInput?.addEventListener("blur",  validateEmail);
    passInput?.addEventListener("blur",   validatePassword);
    emailInput?.addEventListener("input",  () => clearError("email"));
    passInput?.addEventListener("input",   () => clearError("password"));

    /* ================================
       LOGIN SUBMIT
    ================================ */

    const form = document.getElementById("loginForm");
    if (!form) return;

    form.addEventListener("submit", async e => {

        e.preventDefault();

        const emailOk = validateEmail();
        const passOk  = validatePassword();

        if (!emailOk || !passOk) {
            if (!emailOk) document.getElementById("email")?.focus();
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const origText  = submitBtn?.innerHTML;

        if (submitBtn) {
            submitBtn.disabled   = true;
            submitBtn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
        }

        const data = Object.fromEntries(new FormData(form).entries());

        try {
            const response = await apiFetch("/login", {
                method: "POST",
                body:   JSON.stringify(data),
            });

            setToken(response.token);
            setUser(response.user);

            /* Prepara la suscripción push para cuando el layout monte */
            initPushOnLogin();

            Swal.fire({
                icon:             "success",
                title:            "Bienvenido",
                timer:            1500,
                showConfirmButton: false,
            });

            navigateTo("/dashboard");

        } catch (error) {
            showError("email",    "");
            showError("password", "Credenciales incorrectas. Verifica e intenta de nuevo.");
            if (submitBtn) {
                submitBtn.disabled  = false;
                submitBtn.innerHTML = origText;
            }
            passInput?.focus();
        }
    });
}
