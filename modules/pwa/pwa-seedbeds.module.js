/* #archivo: frontend/modules/pwa/pwa-seedbeds.module.js
   CU17 — Consultar semilleros por facultad (PWA). ROL: ESTUDIANTE (solo lectura)
   ─────────────────────────────────────────────────────────────────────── */

import { apiFetch }            from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }      from "../../core/escape.js";
import { navigateTo }      from "../../core/router.js";

/* facultad -> lista de esa facultad; null -> pantalla de facultades */
let currentFacultyId = null;
let allSeedbeds = [];

export const pwaSeedbedsModule = {

    async init() {
        currentFacultyId = null;
        renderSkeleton();
        initLayoutController();
        await loadAndRender();
    }
};

function renderSkeleton() {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            Semilleros
        </h2>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>`);
}

async function loadAndRender() {
    try {
        const data = await apiFetch("/seedbeds");
        allSeedbeds = (data.seedbeds || []).filter(s => s.status === "ACTIVO");
    } catch (err) {
        /* E1: sin conexión y sin caché previa (apiFetch ya intentó la caché
           offline antes de llegar aquí — si llegó, es que no había ninguna). */
        const msg = (err.status || 0) === 0
            ? "Conéctese a internet para ver los semilleros."
            : err.message;
        renderError(msg);
        return;
    }
    renderFacultyList();
}

/* Facultades a las que pertenece un semillero: todas las de sus programas
   (CU13 Ronda B: un semillero puede tener varios programas). */
function facultiesOf(seedbed) {
    const map = new Map();
    (seedbed.programs || []).forEach(p => {
        if (p.faculty) map.set(p.faculty.id, p.faculty.name);
    });
    return [...map.entries()].map(([id, name]) => ({ id, name }));
}

function renderError(msg) {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>${escapeHtml(msg)}</span>
        </div>
    </div>`);
    initLayoutController();
}

/* =========================================================
   PANTALLA 1: FACULTADES (paso 2/3)
   ========================================================= */

function renderFacultyList() {
    currentFacultyId = null;

    if (allSeedbeds.length === 0) {
        document.getElementById("app").innerHTML = LayoutView(`
        <div style="padding:var(--space-4)">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-seedling"></i></div>
                <h3>Sin semilleros publicados</h3>
                <p>Aún no hay semilleros publicados.</p>
            </div>
        </div>`);
        initLayoutController();
        return;
    }

    const byFaculty = new Map();
    allSeedbeds.forEach(s => {
        facultiesOf(s).forEach(f => {
            if (!byFaculty.has(f.id)) byFaculty.set(f.id, { name: f.name, count: 0 });
            byFaculty.get(f.id).count++;
        });
    });

    const cards = [...byFaculty.entries()].map(([id, f]) => `
        <div class="pwa-card faculty-card" data-faculty-id="${escapeHtml(id)}" style="cursor:pointer">
            <div class="card-avatar avatar-green" style="font-size:1rem;font-weight:700">
                <i class="fas fa-building-columns"></i>
            </div>
            <div class="card-body">
                <div class="card-title">${escapeHtml(f.name)}</div>
                <div class="card-subtitle">${f.count} semillero${f.count === 1 ? "" : "s"}</div>
            </div>
            <i class="fas fa-chevron-right card-arrow"></i>
        </div>`).join("");

    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        ${searchBarHtml()}
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-seedling" style="color:var(--color-primary);margin-right:8px"></i>
            Facultades
        </h2>
        <div id="facultyList">${cards}</div>
        <div id="searchResults" style="display:none"></div>
    </div>
    ${detailSheetHtml()}
    ${fabHtml()}`);

    initLayoutController();
    bindCommonEvents();

    document.getElementById("facultyList").addEventListener("click", e => {
        const card = e.target.closest(".faculty-card");
        if (!card) return;
        renderFacultySeedbeds(parseInt(card.dataset.facultyId));
    });
}

/* =========================================================
   PANTALLA 2: SEMILLEROS DE UNA FACULTAD (paso 4/5)
   ========================================================= */

function renderFacultySeedbeds(facultyId) {
    currentFacultyId = facultyId;
    const seedbeds = allSeedbeds.filter(s => facultiesOf(s).some(f => f.id === facultyId));
    const facultyName = seedbeds[0] ? facultiesOf(seedbeds[0]).find(f => f.id === facultyId)?.name : "";

    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <button id="backToFaculties" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-3)">
            <i class="fas fa-arrow-left"></i> Facultades
        </button>
        ${searchBarHtml()}
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            ${escapeHtml(facultyName || "Semilleros")}
            <span style="font-size:var(--text-sm);font-weight:400;color:var(--color-text-muted);margin-left:6px">
                (${seedbeds.length})
            </span>
        </h2>
        <div id="seedbedsList">${seedbedCardsHtml(seedbeds)}</div>
        <div id="searchResults" style="display:none"></div>
    </div>
    ${detailSheetHtml()}
    ${fabHtml()}`);

    initLayoutController();
    bindCommonEvents();

    document.getElementById("backToFaculties")?.addEventListener("click", renderFacultyList);
    bindSeedbedCardClicks(seedbeds);
}

/* nombre, grupo y CAT (paso 5) */
function seedbedCardsHtml(seedbeds) {
    if (seedbeds.length === 0) {
        return `<div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-seedling"></i></div>
            <h3>Sin semilleros</h3>
            <p>Esta facultad no tiene semilleros activos.</p>
        </div>`;
    }
    return seedbeds.map(s => {
        const initials = s.name.split(" ").slice(0, 2).map(w => w[0]).join("").toUpperCase();
        return `
        <div class="pwa-card seedbed-card" data-id="${escapeHtml(s.id)}" style="cursor:pointer">
            <div class="card-avatar avatar-green" style="font-size:1rem;font-weight:700">
                ${escapeHtml(initials)}
            </div>
            <div class="card-body">
                <div class="card-title">${escapeHtml(s.name)}</div>
                ${s.group?.name ? `<div class="card-subtitle"><i class="fas fa-users" style="margin-right:4px"></i>${escapeHtml(s.group.name)}</div>` : ""}
                ${s.cat?.name ? `<div class="card-subtitle"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i>${escapeHtml(s.cat.name)}</div>` : ""}
            </div>
            <i class="fas fa-chevron-right card-arrow"></i>
        </div>`;
    }).join("");
}

/* =========================================================
   BUSCADOR (A1): por nombre u objetivo, cruza todas las facultades
   ========================================================= */

function searchBarHtml() {
    return `
    <div style="position:relative;margin-bottom:var(--space-4)">
        <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);
           color:var(--color-text-faint);font-size:0.875rem"></i>
        <input id="seedbedSearch" class="pwa-input"
               type="search" placeholder="Buscar por nombre u objetivo..."
               style="padding-left:40px">
    </div>`;
}

function renderSearchResults(query) {
    const q = query.trim().toLowerCase();
    const listEl = document.getElementById(currentFacultyId ? "seedbedsList" : "facultyList");
    const resultsEl = document.getElementById("searchResults");
    if (!q) {
        listEl.style.display = "";
        resultsEl.style.display = "none";
        return;
    }
    const matches = allSeedbeds.filter(s =>
        s.name.toLowerCase().includes(q) || (s.objetivo_general || "").toLowerCase().includes(q));
    listEl.style.display = "none";
    resultsEl.style.display = "";
    resultsEl.innerHTML = seedbedCardsHtml(matches);
    bindSeedbedCardClicks(matches);
}

function bindCommonEvents() {
    document.getElementById("seedbedSearch")?.addEventListener("input", e => renderSearchResults(e.target.value));
    document.getElementById("closeDetail")?.addEventListener("click", () => {
        document.getElementById("seedbedDetail").style.display = "none";
    });
    document.getElementById("seedbedDetail")?.addEventListener("click", e => {
        if (e.target.id === "seedbedDetail") e.target.style.display = "none";
    });
    /* A3: proponer idea (CU25) */
    document.getElementById("proposeIdeaFab")?.addEventListener("click", () => navigateTo("/proposals"));
}

function bindSeedbedCardClicks(seedbeds) {
    document.querySelectorAll(".seedbed-card").forEach(card => {
        card.addEventListener("click", async () => {
            const id = parseInt(card.dataset.id);
            const seedbed = seedbeds.find(s => s.id === id) || allSeedbeds.find(s => s.id === id);
            if (!seedbed) return;
            openDetail(seedbed);
            await loadObjectivesForSeedbed(id);
        });
    });
}

function fabHtml() {
    return `
    <button id="proposeIdeaFab" title="Proponer idea" style="position:fixed;right:20px;bottom:88px;
            width:56px;height:56px;border-radius:50%;background:var(--color-primary);color:#fff;
            border:none;box-shadow:var(--shadow-card);font-size:1.4rem;z-index:900;cursor:pointer">
        <i class="fas fa-plus"></i>
    </button>`;
}

function detailSheetHtml() {
    return `
    <div id="seedbedDetail" style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-end;justify-content:center">
        <div style="background:var(--color-surface);width:100%;max-width:600px;
                    border-radius:var(--radius-card) var(--radius-card) 0 0;
                    padding:var(--space-6);max-height:85vh;overflow-y:auto;
                    box-shadow:var(--shadow-card);animation:slideUpModal 200ms ease">

            <div style="display:flex;align-items:center;justify-content:space-between;
                         margin-bottom:var(--space-4)">
                <h3 id="detailTitle" style="margin:0;font-size:var(--text-xl);font-weight:700;color:var(--color-text)">
                    Detalle
                </h3>
                <button id="closeDetail" style="background:none;border:none;
                        color:var(--color-text-muted);cursor:pointer;font-size:1.2rem;
                        width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="detailContent">
                <div class="skeleton skeleton-row"></div>
                <div class="skeleton skeleton-row"></div>
            </div>
        </div>
    </div>`;
}

function openDetail(seedbed) {
    const sheet = document.getElementById("seedbedDetail");
    document.getElementById("detailTitle").textContent = seedbed.name;
    const progNames = (seedbed.programs || []).map(p => p.name).join(", ");
    document.getElementById("detailContent").innerHTML = `
        <div style="display:flex;flex-direction:column;gap:var(--space-3);margin-bottom:var(--space-5)">
            ${progNames ? `
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Programa</span>
                <span style="font-size:var(--text-sm);font-weight:500">${escapeHtml(progNames)}</span>
            </div>` : ""}
            ${seedbed.group?.name ? `
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Grupo</span>
                <span style="font-size:var(--text-sm);font-weight:500">${escapeHtml(seedbed.group.name)}</span>
            </div>` : ""}
            ${seedbed.cat?.name ? `
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">CAT</span>
                <span style="font-size:var(--text-sm);font-weight:500">${escapeHtml(seedbed.cat.name)}</span>
            </div>` : ""}
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Estado</span>
                <span class="badge-pwa badge-pwa-success">Activo</span>
            </div>
        </div>

        <h4 style="font-size:var(--text-base);font-weight:600;margin:0 0 var(--space-3);color:var(--color-text)">
            <i class="fas fa-align-left" style="color:var(--color-primary);margin-right:6px"></i>
            Descripción
        </h4>
        <p style="font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5;margin:0 0 var(--space-5);
                  ${seedbed.description ? "" : "font-style:italic;color:var(--color-text-muted)"}">
            ${seedbed.description
                ? seedbed.description.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
                : "Este semillero aún no tiene una descripción registrada."}
        </p>

        <h4 style="font-size:var(--text-base);font-weight:600;margin:0 0 var(--space-3);color:var(--color-text)">
            <i class="fas fa-bullseye" style="color:var(--color-primary);margin-right:6px"></i>
            Objetivos
        </h4>
        <div id="seedbedObjectives">
            <div class="skeleton skeleton-row"></div>
            <div class="skeleton skeleton-row"></div>
        </div>`;
    sheet.style.display = "flex";
}

async function loadObjectivesForSeedbed(seedbedId) {
    const container = document.getElementById("seedbedObjectives");
    if (!container) return;
    try {
        const data = await apiFetch("/objectives");
        const objs = (data.objectives || []).filter(o => o.seedbed_id === seedbedId);

        if (!objs.length) {
            container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--color-text-muted);
                font-style:italic">Sin objetivos registrados para este semillero.</p>`;
            return;
        }

        container.innerHTML = objs.map((o, i) => `
        <div style="display:flex;gap:var(--space-3);padding:var(--space-3) 0;
                     border-bottom:1px solid var(--color-border-light)">
            <div style="min-width:22px;height:22px;border-radius:50%;
                          background:var(--color-primary-light);color:var(--color-primary);
                          display:flex;align-items:center;justify-content:center;
                          font-size:var(--text-xs);font-weight:700;flex-shrink:0;margin-top:2px">
                ${i + 1}
            </div>
            <p style="margin:0;font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5">
                ${escapeHtml(o.content)}
            </p>
        </div>`).join("");
    } catch {
        container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--color-error)">
            Error al cargar objetivos.</p>`;
    }
}
