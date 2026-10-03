/* =========================================================
   #archivo: /frontend/modules/reports/reports.module.js
   Consultar reportes y estadísticas (RF15 / CU28) — ADMIN_SISTEMA, ADMINISTRATIVO y
   LIDER_SEMILLERO (A1: el líder solo ve los datos de sus semilleros; lo aplica el servidor).
   - Paso 2: cuatro indicadores (semilleros activos por facultad, solicitudes por semillero y
     estado, 10 áreas con más propuestas, integrantes activos por programa y nivel).
   - Paso 3-4: filtros por rango de fechas (hora de Bogotá) o por CAT.
   - Paso 5-6: «Exportar CSV» del reporte elegido.
   - E1: «Sin datos para el periodo».  E2: si la consulta supera 10 s el servidor responde 503
     con la sugerencia de reducir el rango; se muestra tal cual.
   Es de solo lectura.
   ========================================================= */

import { apiFetch, apiDownload } from "../../services/api.service.js";
import { LayoutView }            from "../../layout/layout.view.js";
import { initLayoutController }  from "../../layout/layout.controller.js";
import { escapeHtml }            from "../../core/escape.js";

const REPORTS = [
    { key: "seedbeds_by_faculty",      title: "Semilleros activos por facultad" },
    { key: "requests_by_seedbed",      title: "Solicitudes por semillero y estado" },
    { key: "top_areas",                title: "Las 10 áreas con más propuestas" },
    { key: "members_by_program_level", title: "Integrantes activos por programa y nivel" },
];

let filters = { from: "", to: "", cat_id: "" };

export const reportsModule = {

    async init() {
        filters = { from: "", to: "", cat_id: "" };

        let cats = [];
        try { cats = (await apiFetch("/reports/options")).cats || []; } catch { /* funciona sin el selector de CAT */ }

        renderShell(cats);
        await load();
    }

};

const query = () => {
    const p = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) p.set(k, v); });
    return p.toString();
};

function renderShell(cats) {

    const content = `

    <h2>Reportes y estadísticas</h2>

    <p style="color:var(--color-text-muted);font-size:.9em;margin-top:0">
        Indicadores calculados sobre los datos actuales del sistema. Las fechas se interpretan en hora de Bogotá.
        El filtro por CAT no aplica a las propuestas, que no pertenecen a un CAT.
    </p>

    <form id="reportFilters" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:1rem">
        <label>Desde<br><input type="date" name="from"></label>
        <label>Hasta<br><input type="date" name="to"></label>
        <label>CAT<br>
            <select name="cat_id">
                <option value="">Todos</option>
                ${cats.map(c => `<option value="${escapeHtml(c.id)}">${escapeHtml(c.name)}</option>`).join("")}
            </select>
        </label>
        <button type="submit">Consultar</button>
        <button type="button" id="reportClear">Limpiar</button>
    </form>

    <div id="reportError" role="alert" style="color:#c0392b;font-size:.9em;margin-bottom:.5rem"></div>
    <div id="reportResults" aria-live="polite"></div>
    `;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();

    document.getElementById("reportFilters").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        const f = Object.fromEntries(new FormData(ev.target).entries());
        filters = { from: f.from, to: f.to, cat_id: f.cat_id };
        await load();
    });

    document.getElementById("reportClear").addEventListener("click", async () => {
        document.getElementById("reportFilters").reset();
        filters = { from: "", to: "", cat_id: "" };
        await load();
    });

    document.getElementById("reportResults").addEventListener("click", async (ev) => {
        const btn = ev.target.closest("[data-export]");
        if (btn) await exportCsv(btn.dataset.export);
    });
}

async function load() {
    const box = document.getElementById("reportResults");
    const err = document.getElementById("reportError");
    err.textContent = "";
    box.innerHTML = `<p>Calculando…</p>`;

    try {
        const q = query();
        const data = await apiFetch(`/reports${q ? "?" + q : ""}`);
        box.innerHTML = (data.message
            ? `<p role="status" style="font-weight:600">${escapeHtml(data.message)}</p>`
            : "") + REPORTS.map(r => section(r, data.reports?.[r.key])).join("");
    } catch (e) {
        box.innerHTML = "";
        // E2 (503): el servidor ya redacta «Reduce el rango de fechas»; 422: validación de filtros.
        const detail = e?.payload?.errors ? Object.values(e.payload.errors).flat().join(" ") : "";
        err.textContent = detail || e.message || "No se pudieron cargar los reportes";
    }
}

/* Cada reporte: tabla + barra proporcional hecha con CSS (sin librerías) + botón de exportar. */
function section(def, report) {
    const rows  = report?.rows || [];
    const total = report?.total ?? 0;

    const head = `
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
        <h3 style="margin:.5rem 0">${escapeHtml(def.title)}</h3>
        <button type="button" data-export="${def.key}">Exportar CSV</button>
    </div>`;

    if (!rows.length) {
        return `<section style="margin-bottom:1.5rem">${head}<p style="color:var(--color-text-muted)">Sin datos para el periodo</p></section>`;
    }

    return `<section style="margin-bottom:1.5rem">${head}${table(def.key, rows, total)}</section>`;
}

const bar = (value, max) =>
    `<div style="background:var(--color-border,#ddd);border-radius:4px;height:10px;min-width:80px">
        <div style="width:${max ? Math.round((value / max) * 100) : 0}%;height:100%;background:var(--color-primary,#2e7d32);border-radius:4px"></div>
    </div>`;

function table(key, rows, total) {
    const th = (cols) => `<thead><tr>${cols.map(c => `<th style="text-align:left;padding:.35rem .5rem">${c}</th>`).join("")}</tr></thead>`;
    const td = (v) => `<td style="padding:.35rem .5rem">${escapeHtml(v)}</td>`;
    const max = Math.max(...rows.map(r => r.total || 0), 1);

    let head, body, foot;

    if (key === "seedbeds_by_faculty") {
        head = th(["Facultad", "Semilleros activos", ""]);
        body = rows.map(r => `<tr>${td(r.faculty)}${td(r.total)}<td>${bar(r.total, max)}</td></tr>`).join("");
        foot = `<td><strong>Total (semilleros distintos)</strong></td><td><strong>${escapeHtml(total)}</strong></td><td></td>`;
    } else if (key === "requests_by_seedbed") {
        head = th(["Semillero", "Pendientes", "Aprobadas", "Rechazadas", "Total"]);
        body = rows.map(r => `<tr>${td(r.seedbed)}${td(r.pendientes)}${td(r.aprobadas)}${td(r.rechazadas)}${td(r.total)}</tr>`).join("");
        const sum = (f) => rows.reduce((a, r) => a + (r[f] || 0), 0);
        foot = `<td><strong>Total</strong></td><td><strong>${sum("pendientes")}</strong></td><td><strong>${sum("aprobadas")}</strong></td><td><strong>${sum("rechazadas")}</strong></td><td><strong>${escapeHtml(total)}</strong></td>`;
    } else if (key === "top_areas") {
        head = th(["Área", "Propuestas", ""]);
        body = rows.map(r => `<tr>${td(r.area)}${td(r.total)}<td>${bar(r.total, max)}</td></tr>`).join("");
        foot = `<td><strong>Total</strong></td><td><strong>${escapeHtml(total)}</strong></td><td></td>`;
    } else {
        head = th(["Programa", "Nivel", "Integrantes activos", ""]);
        body = rows.map(r => `<tr>${td(r.program)}${td(r.level_label || r.level)}${td(r.total)}<td>${bar(r.total, max)}</td></tr>`).join("");
        foot = `<td><strong>Total</strong></td><td></td><td><strong>${escapeHtml(total)}</strong></td><td></td>`;
    }

    return `<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">${head}<tbody>${body}</tbody><tfoot><tr>${foot}</tr></tfoot></table></div>`;
}

async function exportCsv(key) {
    const err = document.getElementById("reportError");
    err.textContent = "";
    const q = query();
    try {
        await apiDownload(`/reports/export?report=${encodeURIComponent(key)}${q ? "&" + q : ""}`, `reporte_${key}.csv`);
    } catch (e) {
        err.textContent = e.message || "No se pudo exportar el reporte";
    }
}
