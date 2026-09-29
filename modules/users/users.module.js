/* # archivo: /frontend/modules/users/users.module.js */
/* =========================================================
   #archivo: /frontend/modules/users/users.module.js
   ---------------------------------------------------------
   Módulo CRUD de gestión de usuarios usando CRUD Engine
   ========================================================= */

import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }      from "../../core/escape.js";

/* ─────────────────────────────────────────────────────────────────
   Importación masiva de usuarios (Jose, 2026-08-31)
   El admin pega/sube una lista de correos (una por línea o CSV con
   columnas nombre,correo,rol) y aquí se arma una tabla editable con
   un nombre "sugerido" (derivado del usuario del correo, NUNCA
   presentado como un nombre real confirmado) para que el admin lo
   revise/corrija antes de enviar. Contraseña se deja vacía siempre
   → dispara el correo de activación en el backend.
   ───────────────────────────────────────────────────────────────── */

const ROLES = ["ADMIN_SISTEMA", "ADMINISTRATIVO", "LIDER_SEMILLERO", "ESTUDIANTE"];


/* Convierte "jaaldanah" (usuario de correo) en una sugerencia legible tipo
   "Jaaldanah" — es solo una capitalización del texto crudo, no un nombre
   real deducido; el admin debe revisarlo/corregirlo siempre. */
function suggestNameFromEmail(email) {
    const user = String(email || "").split("@")[0] || "";
    if (!user) return "";
    return user.charAt(0).toUpperCase() + user.slice(1).toLowerCase();
}

function parseImportInput(raw) {
    const lines = raw.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
    const rows = [];
    for (const line of lines) {
        const parts = line.split(/[,;\t]/).map(p => p.trim());
        let name = "", email = "", role = "ESTUDIANTE";

        if (parts.length >= 2) {
            /* Detectar cuál columna es el correo */
            const emailIdx = parts.findIndex(p => p.includes("@"));
            if (emailIdx === -1) continue;
            email = parts[emailIdx];
            name  = parts.find((p, i) => i !== emailIdx && !ROLES.includes(p.toUpperCase())) || "";
            const roleGuess = parts.find(p => ROLES.includes(p.toUpperCase()));
            if (roleGuess) role = roleGuess.toUpperCase();
        } else if (parts[0]?.includes("@")) {
            email = parts[0];
        } else {
            continue;
        }

        if (!email.includes("@")) continue;
        if (!name) name = suggestNameFromEmail(email);

        rows.push({ name, email, role });
    }
    return rows;
}

function importRowHtml(row, idx) {
    const roleOptions = ROLES.map(r =>
        `<option value="${r}" ${row.role === r ? "selected" : ""}>${r}</option>`
    ).join("");

    return `
    <tr class="import-row" data-idx="${idx}">
        <td>
            <input type="text" class="import-name-input" value="${escapeHtml(row.name)}"
                   style="width:100%">
        </td>
        <td>
            <input type="email" class="import-email-input" value="${escapeHtml(row.email)}"
                   style="width:100%">
        </td>
        <td>
            <select class="import-role-select" style="width:100%">${roleOptions}</select>
        </td>
        <td style="text-align:center">
            <button type="button" class="btn btn-secondary import-remove-row" title="Quitar" style="padding:6px 10px">
                <i class="fas fa-trash"></i>
            </button>
        </td>
        <td class="import-row-status" style="font-size:var(--text-xs);color:var(--color-text-4)"></td>
    </tr>`;
}

function openImportModal() {
    const existing = document.getElementById("crudModal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "crudModal";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.innerHTML = `
    <div class="crudModalBox" style="max-width:900px">
        <h3>
            <i class="fas fa-file-import" style="color:var(--color-primary);margin-right:8px"></i>
            Importar usuarios
        </h3>
        <p class="form-legend">
            Pega una lista de correos (uno por línea) o CSV con columnas
            <code>nombre,correo,rol</code>. El nombre se sugiere automáticamente
            a partir del correo — <strong>revísalo antes de enviar</strong>,
            no es un nombre confirmado. La contraseña se deja vacía siempre:
            cada usuario recibe un correo para activar su cuenta y crear la suya.
        </p>

        <div class="form-group">
            <label for="importRawInput">Pegar lista</label>
            <textarea id="importRawInput" rows="4" placeholder="correo1@ut.edu.co
nombre,correo2@ut.edu.co,ESTUDIANTE"></textarea>
        </div>

        <div style="display:flex;gap:8px;margin-bottom:16px">
            <button type="button" class="btn btn-secondary" id="importParseBtn">
                <i class="fas fa-list"></i> Generar tabla
            </button>
            <label class="btn btn-secondary" style="cursor:pointer;margin:0">
                <i class="fas fa-upload"></i> Subir archivo .csv/.txt
                <input type="file" id="importFileInput" accept=".csv,.txt" style="display:none">
            </label>
        </div>

        <div id="importPreviewWrap" style="display:none">
            <div style="overflow-x:auto">
                <table class="display" id="importPreviewTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nombre (sugerido — revisa)</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th></th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="importPreviewBody"></tbody>
                </table>
            </div>
        </div>

        <div id="importResultBanner" style="display:none;margin-top:12px"></div>

        <div class="modal-actions" style="margin-top:16px">
            <button type="button" class="btn btn-secondary" id="importCancelBtn">Cerrar</button>
            <button type="button" class="btn btn-primary" id="importSubmitBtn" style="display:none">
                <i class="fas fa-paper-plane"></i> Crear usuarios
            </button>
        </div>
    </div>`;

    document.body.appendChild(modal);
    bindImportModalEvents();
}

function bindImportModalEvents() {
    const modal      = document.getElementById("crudModal");
    const rawInput    = document.getElementById("importRawInput");
    const fileInput   = document.getElementById("importFileInput");
    const parseBtn    = document.getElementById("importParseBtn");
    const previewWrap = document.getElementById("importPreviewWrap");
    const previewBody = document.getElementById("importPreviewBody");
    const submitBtn   = document.getElementById("importSubmitBtn");
    const cancelBtn   = document.getElementById("importCancelBtn");
    const resultBanner = document.getElementById("importResultBanner");

    function renderRows(rows) {
        previewBody.innerHTML = rows.map((r, i) => importRowHtml(r, i)).join("");
        previewWrap.style.display = rows.length ? "block" : "none";
        submitBtn.style.display = rows.length ? "inline-flex" : "none";
    }

    parseBtn.addEventListener("click", () => {
        const rows = parseImportInput(rawInput.value);
        if (!rows.length) {
            Swal.fire({ icon: "warning", title: "Sin datos", text: "No se detectó ningún correo válido." });
            return;
        }
        renderRows(rows);
    });

    fileInput.addEventListener("change", async () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        const text = await file.text();
        rawInput.value = text;
        const rows = parseImportInput(text);
        renderRows(rows);
    });

    previewBody.addEventListener("click", e => {
        const btn = e.target.closest(".import-remove-row");
        if (!btn) return;
        btn.closest("tr")?.remove();
        submitBtn.style.display = previewBody.children.length ? "inline-flex" : "none";
    });

    cancelBtn.addEventListener("click", () => {
        modal.remove();
        usersModule.init();
    });

    submitBtn.addEventListener("click", async () => {
        const rows = [...previewBody.querySelectorAll(".import-row")].map(tr => ({
            tr,
            name:  tr.querySelector(".import-name-input").value.trim(),
            email: tr.querySelector(".import-email-input").value.trim(),
            role:  tr.querySelector(".import-role-select").value,
        }));

        const invalid = rows.filter(r => !r.name || !r.email.includes("@"));
        if (invalid.length) {
            Swal.fire({ icon: "warning", title: "Revisa la tabla", text: "Hay filas sin nombre o con correo inválido." });
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            const response = await apiFetch("/users/import", {
                method: "POST",
                body: JSON.stringify({
                    users: rows.map(r => ({ name: r.name, email: r.email, role: r.role })),
                }),
            });

            const resultados = response?.resultados || [];
            resultados.forEach((res, i) => {
                const row = rows[i];
                if (!row) return;
                const statusCell = row.tr.querySelector(".import-row-status");
                if (res.success) {
                    statusCell.innerHTML = res.mail_ok === false
                        ? '<i class="fas fa-exclamation-triangle" style="color:var(--color-warning)"></i> Creado (correo falló)'
                        : '<i class="fas fa-check-circle" style="color:var(--color-success)"></i> Creado';
                } else {
                    statusCell.innerHTML = `<i class="fas fa-times-circle" style="color:var(--color-error)"></i> ${escapeHtml(res.message || "Error")}`;
                }
            });

            resultBanner.style.display = "block";
            resultBanner.style.cssText = `display:block;padding:12px 14px;border-radius:8px;font-size:14px;
                background:var(--color-success-light,#dcfce7);border:1px solid var(--color-success-border,#86efac);
                color:var(--color-success-text,#166534)`;
            resultBanner.innerHTML = `<i class="fas fa-check-circle"></i> ${escapeHtml(response?.message || "Importación procesada.")}`;

            submitBtn.style.display = "none";

        } catch (err) {
            resultBanner.style.display = "block";
            resultBanner.style.cssText = `display:block;padding:12px 14px;border-radius:8px;font-size:14px;
                background:var(--color-error-light,#fee2e2);border:1px solid var(--color-error-border,#fca5a5);
                color:var(--color-error-text,#991b1b)`;
            resultBanner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(err.message)}`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Crear usuarios';
        }
    });
}

export const usersModule = createCrudModule({

entity: "users",
title: "Gestión de Usuarios",

fields: [

 { name:"id", label:"ID" },

 { name:"name", label:"Nombre", type:"text" },

 { name:"email", label:"Email", type:"text" },

 { name:"password", label:"Contraseña", type:"password", required:false,
   hint:"Déjala vacía para enviar un correo de activación (el usuario define su propia contraseña). RN10: mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo." },

 { name:"password_confirmation", label:"Confirmar contraseña", type:"password", required:false },

 {
  name:"role",
  label:"Rol",
  type:"select",
  options:[
   { value:"ADMIN_SISTEMA", label:"ADMIN_SISTEMA"},
   { value:"ADMINISTRATIVO", label:"ADMINISTRATIVO"},
   { value:"LIDER_SEMILLERO", label:"LIDER_SEMILLERO"},
   { value:"ESTUDIANTE", label:"ESTUDIANTE"}
  ]
 },

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   { value:"ACTIVO", label:"ACTIVO"},
   { value:"INACTIVO", label:"INACTIVO"}
  ]
 },

 /* RNF05 / RN02: obligatoria para todo rol distinto de Estudiante (el
    servidor la exige; aquí queda opcional porque el motor CRUD no admite
    "obligatorio según otro campo"). */
 { name:"authorization_reference", label:"Referencia de autorización", type:"text", required:false,
   hint:"Obligatoria salvo para Estudiante. Ej: Oficio 123 de 2026." }

],

readonlyFor: ['ADMINISTRATIVO', 'LIDER_SEMILLERO'],

/* CU06 / RF01: "listado paginado ... con filtros por rol y estado" — la
   paginación ya la da DataTables (crud.engine.js); esto agrega los 2
   filtros que faltaban. */
filters: [
    { field: "role", label: "Rol", options: ROLES.map(r => ({ value: r, label: r })) },
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

toolbarExtraHtml() {
    return `
    <button class="btn btn-secondary" id="importUsersBtn" type="button">
        <i class="fas fa-file-import"></i>
        Importar usuarios
    </button>`;
},

afterTableMount() {
    document.getElementById("importUsersBtn")?.addEventListener("click", openImportModal);
}

});