/* #archivo: /frontend/modules/auth/auth.controller.js */

import { apiFetch }        from "../../services/api.service.js";
import { setToken, setUser, setTokenExpiry, consumeIntendedRoute, needsDataConsent } from "../../services/storage.service.js";
import { navigateTo }      from "../../core/router.js";
import { initPushOnLogin } from "../../services/push.service.js";

/* Común a CU01 (contraseña) y CU02 (Google): guarda la sesión y decide a
   dónde ir. Un estudiante sin autorización de datos pasa primero por el
   aviso (CU02 A1 / RF16). */
function startSession(response) {
    setToken(response.token);
    setUser(response.user);
    setTokenExpiry(response.expires_at);

    /* Prepara la suscripción push para cuando el layout monte */
    initPushOnLogin();

    if (needsDataConsent(response.user)) {
        navigateTo("/consent");
        return;
    }

    Swal.fire({
        icon:             "success",
        title:            "Bienvenido",
        timer:            1500,
        showConfirmButton: false,
    });

    /* CU01 A3: volver a la ruta que se intentaba abrir */
    navigateTo(consumeIntendedRoute() || "/dashboard");
}

/* =========================================================
   CU02 — Iniciar sesión con la cuenta de Google institucional
   Google Identity Services entrega un ID token (credential) que el servidor
   verifica. Si el servidor no tiene Client ID configurado, la sección no se
   muestra y el login sigue solo con contraseña.
   ========================================================= */

const GIS_SRC = "https://accounts.google.com/gsi/client";
const E4_MSG  = "Necesita conexión a internet para iniciar sesión";
const E5_MSG  = "No fue posible autenticarse con Google, intente nuevamente";

function loadGis() {
    if (window.google?.accounts?.id) return Promise.resolve();
    return new Promise((resolve, reject) => {
        const tag = document.createElement("script");
        tag.src = GIS_SRC;
        tag.async = true;
        tag.onload = resolve;
        tag.onerror = reject;
        document.head.appendChild(tag);
    });
}

async function initGoogleLogin() {
    const section = document.getElementById("google-login");
    const btnBox  = document.getElementById("google-btn");
    const alertEl = document.getElementById("google-alert");
    const alertTx = document.getElementById("google-alert-text");
    if (!section || !btnBox) return;

    const show = msg => { alertTx.textContent = msg; alertEl.hidden = false; };
    const hide = ()  => { alertEl.hidden = true; };

    /* E4: sin conexión no se puede ni consultar la configuración */
    if (!navigator.onLine) {
        section.hidden = false;
        show(E4_MSG);
        window.addEventListener("online", () => initGoogleLogin(), { once: true });
        return;
    }

    let cfg;
    try {
        cfg = await apiFetch("/auth/config", { auth: false });
    } catch {
        return;   /* sin configuración: queda solo el formulario */
    }
    if (!cfg?.google_client_id) return;

    try {
        await loadGis();
    } catch {
        section.hidden = false;
        show(navigator.onLine ? E5_MSG : E4_MSG);
        return;
    }

    /* La vista pudo cambiar mientras cargaba el script */
    if (!document.body.contains(btnBox)) return;

    hide();
    section.hidden = false;

    google.accounts.id.initialize({
        client_id: cfg.google_client_id,
        /* Sin «hd»: restringiría el selector a @ut.edu.co y un ADMIN_SISTEMA con
           otra cuenta no podría entrar. RN04 se aplica en el servidor (E1). */
        callback: async ({ credential }) => {
            /* E2: si cancela en Google no llega callback → no se muestra nada */
            if (!credential) return;
            hide();
            if (!navigator.onLine) { show(E4_MSG); return; }
            btnBox.setAttribute("aria-busy", "true");
            try {
                const response = await apiFetch("/auth/google", {
                    method: "POST",
                    body:   JSON.stringify({ credential }),
                    auth:   false,
                });
                startSession(response);
            } catch (error) {
                /* E1 (dominio), E3 (inactivo), E5 (Google) — mensaje del servidor */
                show(error.status === 0 ? E4_MSG
                    : error.status === 429 ? "Demasiados intentos. Espere un minuto e intente de nuevo."
                    : (error.message || E5_MSG));
            } finally {
                btnBox.removeAttribute("aria-busy");
            }
        },
        cancel_on_tap_outside: true,
        context: "signin",
        ux_mode: "popup",
        itp_support: true,
    });

    const width = Math.min(360, Math.max(240, Math.floor(btnBox.getBoundingClientRect().width || 320)));
    google.accounts.id.renderButton(btnBox, {
        type: "standard", theme: "outline", size: "large", shape: "pill",
        text: "signin_with", logo_alignment: "left", locale: "es", width,
    });
}

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

    initGoogleLogin();

    /* CU02 A1.4: mensaje tras «No acepto» en el aviso de privacidad */
    let flash = null;
    try { flash = sessionStorage.getItem("ut_login_flash"); sessionStorage.removeItem("ut_login_flash"); } catch {}

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
        if (!isValidEmail(val)) { showError(emailInput, "Ingrese un correo válido"); return false; }
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

    if (flash) showAlert(flash);

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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Ingresando...';
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

            startSession(response);

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
                ? "No se pudo conectar con el servidor. Revise su conexión."
                : (error.message || "Credenciales incorrectas"));
            resetButton();
            passInput?.focus();
        }
    });
}
