/* #archivo: /frontend/modules/auth/consent.module.js
   RF16 / CU02 A1 / RN09 — Aviso de privacidad y autorización de tratamiento
   de datos personales (Ley 1581 de 2012, Decreto 1377 de 2013).
   «Acepto» guarda la fecha en el servidor; «No acepto» cierra la sesión. */

import { apiFetch } from "../../services/api.service.js";
import { getToken, getUser, setUser, removeToken, removeUser, setTokenExpiry,
         clearOfflineCache, consumeIntendedRoute } from "../../services/storage.service.js";
import { navigateTo } from "../../core/router.js";
import { escapeHtml } from "../../core/escape.js";

const POLICY_URL = "https://administrativos.ut.edu.co/images/RES._0676_DEL_27-05-19_ADOPTA_MANUAL_DE_POLITICAS.pdf";
const REJECT_MSG = "No puede usar la aplicación sin la autorización de tratamiento de datos personales.";

function view(user) {
    return `
    <main class="consent-wrap">
      <section class="consent-card" aria-labelledby="consent-title">
        <h1 id="consent-title">Autorización de tratamiento de datos personales</h1>
        <p class="consent-lead">Hola, ${escapeHtml(user?.name || "")}. Antes de continuar, lea y responda este aviso.</p>

        <div class="consent-body" tabindex="0" aria-label="Texto del aviso de privacidad">
          <h2>Responsable</h2>
          <p>Universidad del Tolima — Sistema de Semilleros de Investigación IDEAD.</p>

          <h2>Datos que se tratan</h2>
          <ul>
            <li>Nombre y correo institucional (recibidos de su cuenta de Google o registrados por la universidad).</li>
            <li>Semilleros, solicitudes y propuestas que registre en la aplicación.</li>
            <li>Registros de acceso (fecha e inicio de sesión) con fines de seguridad.</li>
          </ul>

          <h2>Finalidad</h2>
          <p>Gestionar su participación en los semilleros de investigación: consulta de semilleros,
          solicitudes de vinculación, propuestas, notificaciones y reportes institucionales.
          Sus datos no se venden ni se ceden a terceros.</p>

          <h2>Sus derechos</h2>
          <p>Conocer, actualizar y rectificar sus datos; solicitar prueba de esta autorización;
          ser informado del uso de sus datos; presentar quejas ante la Superintendencia de Industria
          y Comercio; y revocar la autorización o pedir la supresión de sus datos cuando no exista un
          deber legal de conservarlos.</p>

          <h2>Política</h2>
          <p>Manual de políticas de tratamiento de datos personales de la Universidad del Tolima
          (Resolución 0676 de 2019):
          <a href="${POLICY_URL}" target="_blank" rel="noopener">consultar el documento</a>.</p>
        </div>

        <div id="consent-alert" class="login-alert" role="alert" hidden style="margin-top:var(--space-4)">
          <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
          <span id="consent-alert-text"></span>
        </div>

        <div class="consent-actions">
          <button type="button" id="consentReject" class="btn btn-secondary">No acepto</button>
          <button type="button" id="consentAccept" class="btn btn-primary">
            <i class="fas fa-check" aria-hidden="true"></i> Acepto
          </button>
        </div>
      </section>
    </main>`;
}

function endLocalSession() {
    removeToken(); removeUser(); setTokenExpiry(null); clearOfflineCache();
}

export const consentModule = {
    async init() {
        const user = getUser();
        if (!getToken() || !user) { navigateTo("/"); return; }

        /* Ya aceptó (p. ej. volvió con el botón atrás) */
        if (user.role !== "ESTUDIANTE" || user.data_consent_at) {
            navigateTo(consumeIntendedRoute() || "/dashboard");
            return;
        }

        document.getElementById("app").innerHTML = view(user);
        document.getElementById("consent-title")?.focus?.();

        const accept = document.getElementById("consentAccept");
        const reject = document.getElementById("consentReject");
        const alertEl = document.getElementById("consent-alert");
        const alertTx = document.getElementById("consent-alert-text");
        const busy = on => { accept.disabled = reject.disabled = on; };

        accept.addEventListener("click", async () => {
            busy(true);
            alertEl.hidden = true;
            try {
                const res = await apiFetch("/consent", { method: "POST", body: JSON.stringify({ accept: true }) });
                setUser(res.user);
                /* CU02 A1.3 → CU17 (listado de semilleros) */
                navigateTo(consumeIntendedRoute() || "/seedbeds");
            } catch (err) {
                alertTx.textContent = err.status === 0
                    ? "Necesita conexión a internet para registrar su autorización."
                    : (err.message || "No se pudo registrar la autorización. Intente de nuevo.");
                alertEl.hidden = false;
                busy(false);
            }
        });

        reject.addEventListener("click", async () => {
            busy(true);
            /* A1.4: el servidor revoca el token; si no hay red, igual se cierra
               la sesión en este dispositivo (el token vence solo a las 8 h). */
            try { await apiFetch("/consent", { method: "POST", body: JSON.stringify({ accept: false }) }); } catch {}
            endLocalSession();
            try { sessionStorage.setItem("ut_login_flash", REJECT_MSG); } catch {}
            navigateTo("/");
        });
    },
};
