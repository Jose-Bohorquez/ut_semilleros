/* =========================================================
   #archivo: /frontend/modules/audits/audits.module.js
   Consultar auditoría (RF14 / CU30) — solo ADMIN_SISTEMA.
   - Paso 2-4: registros más recientes, filtrados y paginados POR EL SERVIDOR
     (usuario, colección, acción, rango de fechas; fechas en hora de Bogotá).
   - Paso 5-6: «Ver» muestra la comparación de valores anteriores y nuevos.
   - A1: «Exportar CSV» de los resultados filtrados.
   - E1: «No hay registros para los filtros seleccionados».
   La auditoría es de solo lectura: no hay botones de crear, editar ni borrar (RN07).
   ========================================================= */

import { apiFetch, apiDownload } from "../../services/api.service.js";
import { LayoutView }            from "../../layout/layout.view.js";
import { initLayoutController }  from "../../layout/layout.controller.js";
import { escapeHtml }            from "../../core/escape.js";

const PER_PAGE = 25;

const ACTION_LABEL = {
    CREATE:         "Creado",
    UPDATE:         "Modificado",
    STATUS_CHANGE:  "Cambio de estado",
    DELETE:         "Eliminado",
    RESTORE:        "Restaurado",
    LOGIN:          "Inicio de sesión",
    LOGOUT:         "Cierre de sesión",
    CONSENT:        "Autorización de datos",
    PASSWORD_RESET: "Contraseña restablecida",
};

const label = (action) => ACTION_LABEL[action] || action;

const emptyFilters = () => ({ user_id: "", table: "", action: "", from: "", to: "" });

let filters = emptyFilters();
let page    = 1;
let rows    = [];

export const auditsModule = {

    async init() {
        filters = emptyFilters();
        page = 1;
        rows = [];

        let options = { actions: [], tables: [], users: [] };
        try { options = await apiFetch("/audits/options"); } catch { /* la pantalla funciona sin selectores */ }

        renderShell(options);
        await loadPage();
    }

};


/* =========================================================
   ESTRUCTURA (se dibuja una vez; solo cambia #auditResults)
   ========================================================= */

function optionHtml(value, text, selected) {
    return `<option value="${escapeHtml(value)}" ${String(value) === String(selected) ? "selected" : ""}>${escapeHtml(text)}</option>`;
}

function renderShell(options) {

    const content = `

    <h2>Registro de Auditoría</h2>

    <p style="color:var(--color-text-muted);font-size:.9em;margin-top:0">
        Historial de cambios del sistema. Es de solo lectura: los registros no se pueden modificar ni eliminar.
        Las fechas están en hora de Bogotá.
    </p>

    <form id="auditFilters" class="filter-bar">

        <label>Usuario<br>
            <select name="user_id">
                <option value="">Todos</option>
                ${(options.users || []).map(u => optionHtml(u.id, u.name, "")).join("")}
            </select>
        </label>

        <label>Colección<br>
            <select name="table">
                <option value="">Todas</option>
                ${(options.tables || []).map(t => optionHtml(t, t, "")).join("")}
            </select>
        </label>

        <label>Acción<br>
            <select name="action">
                <option value="">Todas</option>
                ${(options.actions || []).map(a => optionHtml(a, label(a), "")).join("")}
            </select>
        </label>

        <label>Desde<br><input type="date" name="from"></label>
        <label>Hasta<br><input type="date" name="to"></label>

        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <button type="button" id="auditClear" class="btn btn-secondary btn-sm">Limpiar</button>
        <button type="button" id="auditExport" class="btn btn-secondary btn-sm">Exportar CSV</button>

    </form>

    <div id="auditError" role="alert" style="color:#c0392b;font-size:.9em;margin-bottom:.5rem"></div>

    <div id="auditResults" aria-live="polite"></div>
    `;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();

    document.getElementById("auditFilters").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        const f = Object.fromEntries(new FormData(ev.target).entries());
        filters = { user_id: f.user_id, table: f.table, action: f.action, from: f.from, to: f.to };
        page = 1;
        await loadPage();
    });

    document.getElementById("auditClear").addEventListener("click", async () => {
        document.getElementById("auditFilters").reset();
        filters = emptyFilters();
        page = 1;
        await loadPage();
    });

    document.getElementById("auditExport").addEventListener("click", exportCsv);

    document.getElementById("auditResults").addEventListener("click", async (ev) => {
        const view = ev.target.closest(".auditViewBtn");
        if (view) { showDetail(view.dataset.id); return; }

        const go = ev.target.closest("[data-audit-page]");
        if (go && !go.disabled) {
            page = parseInt(go.dataset.auditPage, 10);
            await loadPage();
        }
    });
}

function queryString(extra = {}) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
    Object.entries(extra).forEach(([k, v]) => params.set(k, v));
    return params.toString();
}

function showError(message) {
    const box = document.getElementById("auditError");
    if (box) box.textContent = message || "";
}


/* =========================================================
   LISTADO (pasos 2-4)
   ========================================================= */

async function loadPage() {

    const results = document.getElementById("auditResults");
    if (!results) return;

    showError("");
    results.innerHTML = `<p>Cargando…</p>`;

    let data;
    try {
        data = await apiFetch(`/audits?${queryString({ page, per_page: PER_PAGE })}`);
    } catch (error) {
        results.innerHTML = "";
        showError(error.message || "No se pudo cargar la auditoría");
        return;
    }

    rows = data.audits || [];
    const meta = data.meta || { current_page: 1, last_page: 1, per_page: PER_PAGE, total: rows.length };

    // E1
    if (!rows.length) {
        results.innerHTML = `<p class="empty-state">${escapeHtml(data.message || "No hay registros para los filtros seleccionados")}</p>`;
        return;
    }

    const body = rows.map(a => `
        <tr>
            <td data-label="Fecha">${escapeHtml(a.created_at_local || a.created_at || "")}</td>
            <td data-label="Usuario">${escapeHtml(a.user?.name || "—")}</td>
            <td data-label="Acción">${escapeHtml(label(a.action))}</td>
            <td data-label="Colección">${escapeHtml(a.table_name)}</td>
            <td data-label="Documento">${escapeHtml(a.record_id)}</td>
            <td data-label="IP">${escapeHtml(a.ip_address || "—")}</td>
            <td data-label="Acciones"><button type="button" class="auditViewBtn btn btn-ghost btn-sm" data-id="${escapeHtml(a.id)}">Ver</button></td>
        </tr>
    `).join("");

    const from = (meta.current_page - 1) * meta.per_page + 1;
    const to   = from + rows.length - 1;

    results.innerHTML = `
        <table class="display mobile-card-table" style="width:100%">
            <thead>
                <tr>
                    <th>Fecha</th><th>Usuario</th><th>Acción</th><th>Colección</th>
                    <th>Documento</th><th>IP</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>${body}</tbody>
        </table>

        <nav aria-label="Paginación de la auditoría" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-top:1rem">
            <button type="button" class="btn btn-secondary btn-sm" data-audit-page="${meta.current_page - 1}" ${meta.current_page <= 1 ? "disabled" : ""}>Anterior</button>
            <span>Mostrando ${from} a ${to} de ${meta.total} registros · Página ${meta.current_page} de ${meta.last_page}</span>
            <button type="button" class="btn btn-secondary btn-sm" data-audit-page="${meta.current_page + 1}" ${meta.current_page >= meta.last_page ? "disabled" : ""}>Siguiente</button>
        </nav>
    `;
}


/* =========================================================
   DETALLE (pasos 5-6): comparación de valores anteriores y nuevos
   ========================================================= */

function formatValue(value) {
    if (value === null || value === undefined) return "—";
    return typeof value === "object" ? JSON.stringify(value) : String(value);
}

function showDetail(id) {

    const a = rows.find(r => String(r.id) === String(id));
    if (!a) return;

    const old = a.old_values || {};
    const now = a.new_values || {};
    const fields = [...new Set([...Object.keys(old), ...Object.keys(now)])];

    const comparison = fields.length
        ? `<table class="display" style="width:100%;margin-top:.5rem">
               <thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead>
               <tbody>${fields.map(f => `
                   <tr>
                       <td>${escapeHtml(f)}</td>
                       <td>${escapeHtml(formatValue(old[f]))}</td>
                       <td>${escapeHtml(formatValue(now[f]))}</td>
                   </tr>`).join("")}
               </tbody>
           </table>`
        : `<p><em>Este registro no guarda valores: es un evento sin cambios de campos (por ejemplo, un inicio de sesión) o es anterior a la auditoría con valores.</em></p>`;

    Swal.fire({
        title: `Registro #${escapeHtml(a.id)}`,
        width: 760,
        html: `
            <div style="text-align:left;line-height:1.5;font-size:.95em">
                <p><b>Fecha (hora de Bogotá):</b> ${escapeHtml(a.created_at_local || "")}<br>
                   <b>Usuario:</b> ${escapeHtml(a.user?.name || "—")}<br>
                   <b>Acción:</b> ${escapeHtml(label(a.action))}<br>
                   <b>Colección:</b> ${escapeHtml(a.table_name)} · <b>Documento:</b> ${escapeHtml(a.record_id)}<br>
                   <b>IP:</b> ${escapeHtml(a.ip_address || "—")}</p>
                ${comparison}
            </div>`,
        confirmButtonText: "Cerrar",
    });
}


/* =========================================================
   A1: exportar a CSV los resultados filtrados
   ========================================================= */

async function exportCsv() {
    const button = document.getElementById("auditExport");
    showError("");
    if (button) button.disabled = true;
    try {
        await apiDownload(`/audits/export?${queryString()}`, "auditoria.csv");
    } catch (error) {
        showError(error.message || "No se pudo exportar la auditoría");
    } finally {
        if (button) button.disabled = false;
    }
}
