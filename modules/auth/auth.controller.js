/* #archivo: /frontend/modules/auth/auth.controller.js */

import { apiFetch }        from "../../services/api.service.js";
import { setToken, setUser, consumeIntendedRoute } from "../../services/storage.service.js";
import { navigateTo }      from "../../core/router.js";
import { initPushOnLogin } from "../../services/push.service.js";

/* CU01 — Iniciar sesión en el panel web.
   Pasos, alternos (A*) y excepciones (E*) según
   docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md §CU01. */
export function initLoginController() {

    const form      = document.getElementById("loginForm");
    if (!form) return;

    const emailInput = document.getElementById("email");
    const passInput  = document.getElementById("password");
    const submitBtn  = form.querySelector('button[type="submit"]');
    const alertBox   = document.getElementById("login-alert");
    const alertText  = document.getElementById("login-alert-text");
    const origText   = submitBtn?.innerHTML;

    /* ================================
       PASSWORD TOGGLE
    ================================ */

    const toggleBtn = document.getElementById("togglePassword");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            const eye     = document.getElementById("eyeIcon");
            const showing = passInput.type === "text";
            passInput.type = showing ? "password" : "text";
            eye.classList.toggle("fa-eye",       showing);
            eye.classList.toggle("fa-eye-slash", !showing);
        });
    }

    /* ================================
       VALIDACIÓN POR CAMPO (E1)
    ================================ */

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    }

    function showError(input, msg) {
        const err = document.getElementById(`err-${input.id}`);
        input.classList.add("field-invalid");
        input.classList.remove("field-valid");
        input.setAttribute("aria-invalid", "true");
        if (err) err.textContent = msg;
    }

    function clearError(input) {
        const err = document.getElementById(`err-${input.id}`);
        input.classList.remove("field-invalid");
        input.removeAttribute("aria-invalid");
        if (err) err.textContent = "";
    }

    function validateEmail() {
        const val = emailInput.value.trim();
        if (!val) { showError(emailInput, "El correo es obligatorio"); return false; }
        if (!isValidEmail(val)) { showError(emailInput, "Ingresa un correo válido"); return false; }
        clearError(emailInput);
        return true;
    }

    function validatePassword() {
        if (!passInput.value) { showError(passInput, "La contraseña es obligatoria"); return false; }
        clearError(passInput);
        return true;
    }

    /* Errores del servidor (E2, E3, E4, red): una sola alerta con role=alert */
    function showAlert(msg) {
        if (!alertBox) return;
        alertText.textContent = msg;
        alertBox.hidden = false;
    }

    function hideAlert() {
        if (alertBox) alertBox.hidden = true;
    }

    function resetButton() {
        if (!submitBtn) return;
        submitBtn.disabled = false;
        submitBtn.removeAttribute("aria-busy");
        submitBtn.innerHTML = origText;
    }

    emailInput?.addEventListener("blur",  validateEmail);
    passInput?.addEventListener("blur",   validatePassword);
    emailInput?.addEventListener("input", () => { clearError(emailInput); hideAlert(); });
    passInput?.addEventListener("input",  () => { clearError(passInput);  hideAlert(); });

    /* ================================
       LOGIN SUBMIT
    ================================ */

    form.addEventListener("submit", async e => {

        e.preventDefault();
        hideAlert();

        /* E1: no se consulta la API si hay campos vacíos o mal formados */
        const emailOk = validateEmail();
        const passOk  = validatePassword();
        if (!emailOk || !passOk) {
            (emailOk ? passInput : emailInput)?.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute("aria-busy", "true");
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
        }

        const data = {
            email:    emailInput.value.trim(),
            password: passInput.value,
            remember: !!document.getElementById("remember")?.checked,   /* A2 */
        };

        try {
            const response = await apiFetch("/login", {
                method: "POST",
                body:   JSON.stringify(data),
                auth:   false,
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

            /* A3: volver a la ruta que se intentaba abrir */
            navigateTo(consumeIntendedRoute() || "/dashboard");

        } catch (error) {

            /* E4 / RN14: bloqueo temporal. El aviso es fijo (no se re-anuncia
               cada segundo); la cuenta regresiva vive solo en el botón. */
            if (error.status === 429) {
                let left = Number(error.payload?.retry_after) || 60;
                showAlert(`Demasiados intentos fallidos. Podrás intentar de nuevo en ${left} segundos.`);
                const paint = () => {
                    if (submitBtn) submitBtn.innerHTML = `<i class="fas fa-hourglass-half" aria-hidden="true"></i> Espera ${left} s`;
                };
                submitBtn?.removeAttribute("aria-busy");
                paint();
                const timer = setInterval(() => {
                    left -= 1;
                    if (left > 0) return paint();
                    clearInterval(timer);
                    hideAlert();
                    resetButton();
                }, 1000);
                return;
            }

            /* E2 (credenciales) y E3 (inactivo): mensaje del servidor */
            showAlert(error.status === 0
                ? "No se pudo conectar con el servidor. Revisa tu conexión."
                : (error.message || "Credenciales incorrectas"));
            resetButton();
            passInput?.focus();
        }
    });
}
