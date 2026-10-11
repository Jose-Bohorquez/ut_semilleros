/* #archivo: frontend/modules/pwa/pwa-seedbeds.module.js
   CU17 — Consultar semilleros por facultad (PWA). ROL: ESTUDIANTE (solo lectura)
   ─────────────────────────────────────────────────────────────────────── */

import { apiFetch }            from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }      from "../../core/escape.js";
import { navigateTo }      from "../../core/router.js";
import { getUser }         from "../../services/storage.service.js";

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

/* Facultades REALES a las que pertenece un semillero (para el detalle,
   CU18 paso 2): todas las de sus programas (CU13 Ronda B: un semillero
   puede tener varios programas). */
function facultiesOf(seedbed) {
    const map = new Map();
    (seedbed.programs || []).forEach(p => {
        if (p.faculty) map.set(p.faculty.id, p.faculty.name);
    });
    return [...map.entries()].map(([id, name]) => ({ id, name }));
}

/* Secciones para la pantalla principal (hallazgo real, 2026-10-11): el
   IDEAD no se divide en facultades — es una unidad académica paralela a
   las facultades presenciales que administra sus 12 programas agrupados
   por ÁREA DE ESTUDIO (programs.area_tematica), no por facultad. Mostrar
   "IDEAD" como una sola facultad agrupaba mal semilleros que en realidad
   pertenecen a áreas distintas. Las facultades presenciales siguen
   agrupándose como siempre (por facultad real).
   Un semillero recién cargado por lotes (seedbeds:import) que todavía
   tiene los 12 programas del IDEAD pegados como placeholder (ver
   ImportSeedbeds.php) cae en "IDEAD · Por clasificar" en vez de
   repetirse en las 3 áreas a la vez. */
function sectionsOf(seedbed) {
    const map = new Map();
    const ideadPrograms = [];

    (seedbed.programs || []).forEach(p => {
        if (!p.faculty) return;
        if (p.faculty.code === "IDEAD") {
            ideadPrograms.push(p);
        } else {
            map.set(`fac-${p.faculty.id}`, { name: p.faculty.name, programs: new Set() });
        }
    });

    (seedbed.programs || []).forEach(p => {
        if (p.faculty && p.faculty.code !== "IDEAD") {
            map.get(`fac-${p.faculty.id}`)?.programs.add(p.name);
        }
    });

    if (ideadPrograms.length) {
        const areas = new Set(ideadPrograms.map(p => p.area_tematica).filter(Boolean));
        if (areas.size === 1) {
            const area = [...areas][0];
            const key = `idead-${area}`;
            if (!map.has(key)) map.set(key, { name: area, programs: new Set() });
            ideadPrograms.forEach(p => map.get(key).programs.add(p.name));
        } else {
            const key = "idead-pending";
            if (!map.has(key)) map.set(key, { name: "IDEAD · Por clasificar", programs: new Set(), pending: true });
            ideadPrograms.forEach(p => map.get(key).programs.add(p.name));
        }
    }

    return [...map.entries()].map(([id, v]) => ({ id, ...v, programs: [...v.programs] }));
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

    /* Secciones: facultades presenciales + áreas del IDEAD (ver sectionsOf).
       Cantidad de semilleros + programas distintos como preview, para que
       la tarjeta se sienta como una sección propia y no una fila genérica
       (hallazgo real, 2026-10-10: con nombres largos como "Instituto de
       Educación a Distancia (IDEAD)" el .card-title de una línea lo
       truncaba; 2026-10-11: agrupar el IDEAD como una sola "facultad"
       mezclaba semilleros de áreas distintas). */
    const bySection = new Map();
    allSeedbeds.forEach(s => {
        sectionsOf(s).forEach(sec => {
            if (!bySection.has(sec.id)) bySection.set(sec.id, { name: sec.name, count: 0, programs: new Set(), pending: !!sec.pending });
            const entry = bySection.get(sec.id);
            entry.count++;
            sec.programs.forEach(p => entry.programs.add(p));
        });
    });

    const totalSections = bySection.size;
    const totalSeedbeds = allSeedbeds.length;

    const cards = [...bySection.entries()].map(([id, f]) => {
        const programs = [...f.programs];
        const shown = programs.slice(0, 3);
        const rest = programs.length - shown.length;
        const chips = shown.map(p => `<span class="faculty-chip">${escapeHtml(p)}</span>`).join("")
            + (rest > 0 ? `<span class="faculty-chip faculty-chip-more">+${rest}</span>` : "");

        return `
        <div class="faculty-section-card${f.pending ? " faculty-section-pending" : ""}" data-faculty-id="${escapeHtml(id)}">
            <div class="faculty-section-head">
                <div class="card-avatar ${f.pending ? "avatar-yellow" : "avatar-green"}" style="font-size:1rem;font-weight:700">
                    <i class="fas ${f.pending ? "fa-circle-question" : "fa-building-columns"}"></i>
                </div>
                <div class="faculty-section-name">${escapeHtml(f.name)}</div>
            </div>
            ${f.pending
                ? `<p class="pwa-hint">Semilleros cargados sin un programa específico asignado todavía.</p>`
                : (chips ? `<div class="faculty-chips">${chips}</div>` : "")}
            <div class="faculty-section-footer">
                <span>${f.count} semillero${f.count === 1 ? "" : "s"}</span>
                <span class="faculty-section-link">Ver semilleros <i class="fas fa-arrow-right"></i></span>
            </div>
        </div>`;
    }).join("");

    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        ${searchBarHtml()}
        <h2 style="margin:0 0 var(--space-1);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-seedling" style="color:var(--color-primary);margin-right:8px"></i>
            Facultades y áreas
        </h2>
        <p style="margin:0 0 var(--space-4);color:var(--color-text-muted);font-size:var(--text-sm)">
            ${totalSections} ${totalSections === 1 ? "sección" : "secciones"} ·
            ${totalSeedbeds} semillero${totalSeedbeds === 1 ? "" : "s"}
        </p>
        <div id="facultyList" class="faculty-section-grid">${cards}</div>
        <div id="searchResults" style="display:none"></div>
    </div>
    ${detailSheetHtml()}
    ${fabHtml()}`);

    initLayoutController();
    bindCommonEvents();

    document.getElementById("facultyList").addEventListener("click", e => {
        const card = e.target.closest(".faculty-section-card");
        if (!card) return;
        renderFacultySeedbeds(card.dataset.facultyId);
    });
}

/* =========================================================
   PANTALLA 2: SEMILLEROS DE UNA FACULTAD (paso 4/5)
   ========================================================= */

function renderFacultySeedbeds(sectionId) {
    currentFacultyId = sectionId;
    const seedbeds = allSeedbeds.filter(s => sectionsOf(s).some(sec => sec.id === sectionId));
    const facultyName = seedbeds[0] ? sectionsOf(seedbeds[0]).find(sec => sec.id === sectionId)?.name : "";

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
function seedbedCardsHtml(seedbeds, emptyText = "Esta facultad no tiene semilleros activos.") {
    if (seedbeds.length === 0) {
        return `<div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-seedling"></i></div>
            <h3>Sin semilleros</h3>
            <p>${emptyText}</p>
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
    resultsEl.innerHTML = seedbedCardsHtml(matches, "No encontramos semilleros que coincidan con tu búsqueda.");
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
            /* CU18 E1: antes de mostrar el detalle se consulta el semillero;
               si ya no está disponible se avisa y se vuelve al listado. */
            const fresh = await fetchAvailableSeedbed(seedbed);
            if (!fresh) return;
            openDetail(fresh);
            await loadObjectivesForSeedbed(id);
        });
    });
}

/* CU18 E1: consulta GET /seedbeds/{id}. Devuelve el semillero vigente, o null
   si el servidor dice que ya no está disponible (404: inactivado mientras el
   estudiante lo veía), tras mostrar «Este semillero ya no está disponible»,
   cerrar el detalle y refrescar el listado. Sin conexión apiFetch entrega la
   última copia guardada (con su aviso, CU18 A3) o falla con status 0; en ese
   caso, y ante cualquier otro error, se conserva lo que ya traía el listado. */
async function fetchAvailableSeedbed(fallback) {
    try {
        const data = await apiFetch(`/seedbeds/${fallback.id}`);
        const fresh = data?.seedbed;
        if (fresh && fresh.status && fresh.status !== "ACTIVO") {
            await handleSeedbedUnavailable(fallback.id);
            return null;
        }
        return fresh || fallback;
    } catch (err) {
        if (err.status === 404) {
            await handleSeedbedUnavailable(fallback.id);
            return null;
        }
        return fallback;
    }
}

async function handleSeedbedUnavailable(seedbedId) {
    const sheet = document.getElementById("seedbedDetail");
    if (sheet) sheet.style.display = "none";
    allSeedbeds = allSeedbeds.filter(s => s.id !== seedbedId);

    await Swal.fire({
        icon: "info",
        title: "Este semillero ya no está disponible",
        confirmButtonText: "Aceptar",
    });

    /* Refresca el listado desde el servidor (que solo entrega activos); si no
       hay red se queda con la lista local ya depurada. */
    try {
        const data = await apiFetch("/seedbeds");
        allSeedbeds = (data.seedbeds || []).filter(s => s.status === "ACTIVO");
    } catch { /* se conserva allSeedbeds depurado */ }

    if (currentFacultyId && allSeedbeds.some(s => sectionsOf(s).some(sec => sec.id === currentFacultyId))) {
        renderFacultySeedbeds(currentFacultyId);
    } else {
        renderFacultyList();
    }
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

/* CU18 paso 2: nombre, facultad, grupo, CAT, coordinador (nombre y correo),
   objetivo general y pestañas Misión/Visión/Objetivos + botón «Ser miembro». */
function openDetail(seedbed) {
    const sheet = document.getElementById("seedbedDetail");
    document.getElementById("detailTitle").textContent = seedbed.name;
    const facultyNames = facultiesOf(seedbed).map(f => f.name).join(", ");

    const infoRow = (label, value) => value ? `
        <div style="display:flex;justify-content:space-between;align-items:center;
                     padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
            <span style="font-size:var(--text-sm);color:var(--color-text-muted)">${label}</span>
            <span style="font-size:var(--text-sm);font-weight:500">${value}</span>
        </div>` : "";

    document.getElementById("detailContent").innerHTML = `
        <div style="display:flex;flex-direction:column;gap:var(--space-3);margin-bottom:var(--space-5)">
            ${infoRow("Facultad", escapeHtml(facultyNames))}
            ${infoRow("Grupo", seedbed.group?.name ? escapeHtml(seedbed.group.name) : "")}
            ${infoRow("CAT", seedbed.cat?.name ? escapeHtml(seedbed.cat.name) : "")}
            ${infoRow("Coordinador", seedbed.coordinator?.name
                ? `${escapeHtml(seedbed.coordinator.name)}${seedbed.coordinator.email ? ` · ${escapeHtml(seedbed.coordinator.email)}` : ""}`
                : "")}
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Estado</span>
                <span class="badge-pwa badge-pwa-success">Activo</span>
            </div>
        </div>

        <h4 style="font-size:var(--text-base);font-weight:600;margin:0 0 var(--space-3);color:var(--color-text)">
            <i class="fas fa-bullseye" style="color:var(--color-primary);margin-right:6px"></i>
            Objetivo general
        </h4>
        <p style="font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5;margin:0 0 var(--space-5);
                  ${seedbed.objetivo_general ? "" : "font-style:italic;color:var(--color-text-muted)"}">
            ${escapeHtml(seedbed.objetivo_general || "Este semillero aún no tiene un objetivo general registrado.")}
        </p>

        <div style="display:flex;gap:4px;margin-bottom:var(--space-3);border-bottom:1px solid var(--color-border)">
            <button type="button" class="detail-tab-btn active" data-tab="mision" style="flex:1;padding:var(--space-2);border:none;background:none;
                    border-bottom:2px solid var(--color-primary);color:var(--color-primary);font-weight:600;cursor:pointer">Misión</button>
            <button type="button" class="detail-tab-btn" data-tab="vision" style="flex:1;padding:var(--space-2);border:none;background:none;
                    border-bottom:2px solid transparent;color:var(--color-text-muted);font-weight:600;cursor:pointer">Visión</button>
            <button type="button" class="detail-tab-btn" data-tab="objetivos" style="flex:1;padding:var(--space-2);border:none;background:none;
                    border-bottom:2px solid transparent;color:var(--color-text-muted);font-weight:600;cursor:pointer">Objetivos</button>
        </div>

        <div id="detailTabPanes" style="margin-bottom:var(--space-5)">
            <div data-tab-pane="mision">
                <p style="font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5;
                          ${seedbed.mision ? "" : "font-style:italic;color:var(--color-text-muted)"}">
                    ${escapeHtml(seedbed.mision || "Sin misión registrada.")}
                </p>
            </div>
            <div data-tab-pane="vision" style="display:none">
                <p style="font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5;
                          ${seedbed.vision ? "" : "font-style:italic;color:var(--color-text-muted)"}">
                    ${escapeHtml(seedbed.vision || "Sin visión registrada.")}
                </p>
            </div>
            <div data-tab-pane="objetivos" style="display:none" id="seedbedObjectives">
                <div class="skeleton skeleton-row"></div>
                <div class="skeleton skeleton-row"></div>
            </div>
        </div>

        <div id="membershipSection"></div>`;

    /* El listener va en #detailContent (contiene los botones Y los paneles). Antes estaba en #detailTabPanes, que solo
       envuelve los paneles: los botones Misión/Visión/Objetivos quedan fuera y nunca recibían el clic. Se asigna con
       onclick para no acumular un listener nuevo cada vez que se abre un semillero. */
    document.getElementById("detailContent").onclick = e => {
        const btn = e.target.closest(".detail-tab-btn");
        if (!btn) return;
        document.querySelectorAll(".detail-tab-btn").forEach(b => {
            const active = b === btn;
            b.classList.toggle("active", active);
            b.setAttribute("aria-selected", active ? "true" : "false");
            b.style.borderBottomColor = active ? "var(--color-primary)" : "transparent";
            b.style.color = active ? "var(--color-primary)" : "var(--color-text-muted)";
        });
        document.querySelectorAll("[data-tab-pane]").forEach(p => {
            p.style.display = p.dataset.tabPane === btn.dataset.tab ? "" : "none";
        });
    };

    sheet.style.display = "flex";
    renderMembershipSection(seedbed);
}

/* CU18 A1/A2: botón «Ser miembro» — muestra estado según si ya hay
   solicitud pendiente/aprobada para ESTE semillero (CU22). */
async function renderMembershipSection(seedbed) {
    const container = document.getElementById("membershipSection");
    if (!container) return;

    /* CU22 (regla del 2026-10-04): para postularse no debe tener ningún semillero asociado ni activo. El servidor
       decide (GET /requests/eligibility) y explica el motivo; así no se llena un formulario que será rechazado. */
    let elig = { can_apply: true, state: "free" };
    try {
        elig = await apiFetch(`/requests/eligibility?seedbed_id=${encodeURIComponent(seedbed.id)}`);
    } catch {
        /* si falla, se asume libre — el backend igual valida al enviar */
    }

    if (elig.state === "member_here" || elig.state === "approved_here") {
        container.innerHTML = `<button type="button" class="pwa-btn-primary" disabled style="opacity:.6">
            <i class="fas fa-check-circle"></i> Ya eres integrante</button>`;
        return;
    }
    if (elig.state === "pending_here") {
        container.innerHTML = `<button type="button" class="pwa-btn-primary" disabled style="opacity:.6">
            <i class="fas fa-clock"></i> Solicitud pendiente</button>
            ${elig.request_id ? `
            <button type="button" id="cancelPendingBtn" class="pwa-btn-secondary" style="margin-top:var(--space-3);color:var(--color-error)">
                <i class="fas fa-ban"></i> Cancelar mi solicitud</button>` : ""}`;
        document.getElementById("cancelPendingBtn")?.addEventListener("click", async () => {
            const ask = await Swal.fire({
                icon: "question", title: "¿Cancelar tu solicitud?",
                text: "Podrás postularte de nuevo, a este u otro semillero.",
                showCancelButton: true, confirmButtonText: "Sí, cancelar solicitud", cancelButtonText: "No, mantenerla", confirmButtonColor: "#dc2626",
            });
            if (!ask.isConfirmed) return;
            try {
                await apiFetch(`/requests/${encodeURIComponent(elig.request_id)}/cancel`, { method: "PUT" });
                await Swal.fire({ icon: "success", title: "Solicitud cancelada", timer: 1800, showConfirmButton: false });
            } catch (error) {
                await Swal.fire({ icon: "error", title: "No se pudo cancelar", text: error.message || "Inténtalo de nuevo." });
            }
            await renderMembershipSection(seedbed);        // vuelve a mostrar «Ser miembro» (o el motivo real)
        });
        return;
    }
    if (!elig.can_apply) {
        container.innerHTML = `
            <button type="button" class="pwa-btn-primary" disabled style="opacity:.6">
                <i class="fas fa-lock"></i> No disponible por ahora</button>
            <p id="eligibilityMsg" role="status" style="margin:var(--space-3) 0 0;font-size:var(--text-sm);line-height:1.5;color:var(--color-text-muted)">
                ${escapeHtml(elig.message || "No puedes postularte a este semillero en este momento.")}</p>`;
        return;
    }

    const programs = seedbed.programs || [];
    container.innerHTML = `
        <button type="button" class="pwa-btn-primary" id="joinSeedbedBtn">
            <i class="fas fa-user-plus"></i> Ser miembro
        </button>
        <form id="joinSeedbedForm" style="display:none;margin-top:var(--space-4);flex-direction:column;gap:var(--space-3)">
            <div class="pwa-form-group">
                <label class="pwa-label" for="join-program">Programa <span style="color:var(--color-error)">*</span></label>
                <select class="pwa-input pwa-select" id="join-program" required>
                    <option value="">Selecciona un programa...</option>
                    ${programs.map(p => `<option value="${escapeHtml(p.id)}">${escapeHtml(p.name)}</option>`).join("")}
                </select>
                <span class="pwa-field-error" id="err-join-program"></span>
            </div>
            <div class="pwa-form-group">
                <label class="pwa-label" for="join-phone">Teléfono <span style="color:var(--color-error)">*</span></label>
                <input class="pwa-input" id="join-phone" type="tel" placeholder="Ej: 3001234567" required>
                <span class="pwa-field-error" id="err-join-phone"></span>
            </div>
            <div class="pwa-form-group">
                <label class="pwa-label" for="join-message">Mensaje <span style="color:var(--color-error)">*</span></label>
                <textarea class="pwa-input" id="join-message" rows="3" placeholder="Cuéntale al líder por qué quieres unirte..." required></textarea>
                <span class="pwa-field-error" id="err-join-message"></span>
            </div>
            <div id="joinFormError" style="display:none;color:var(--color-error);font-size:var(--text-sm)"></div>
            <div style="display:flex;gap:var(--space-3)">
                <button type="button" class="pwa-btn-secondary" id="cancelJoinBtn" style="flex:1">Cancelar</button>
                <button type="submit" class="pwa-btn-primary" id="submitJoinBtn" style="flex:1">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
            </div>
        </form>`;

    const btn = document.getElementById("joinSeedbedBtn");
    const form = document.getElementById("joinSeedbedForm");
    btn.addEventListener("click", async () => {
        /* CU18 E1: el semillero pudo inactivarse mientras el estudiante lo veía. */
        btn.disabled = true;
        const ok = await fetchAvailableSeedbed(seedbed);
        if (!ok) return;   /* ya se avisó, se cerró el detalle y se refrescó el listado */
        btn.disabled = false;
        btn.style.display = "none";
        form.style.display = "flex";
    });
    document.getElementById("cancelJoinBtn").addEventListener("click", () => { form.style.display = "none"; btn.style.display = ""; });

    form.addEventListener("submit", async e => {
        e.preventDefault();
        const errBox = document.getElementById("joinFormError");
        errBox.style.display = "none";
        const submitBtn = document.getElementById("submitJoinBtn");
        submitBtn.disabled = true;
        try {
            await apiFetch("/requests", {
                method: "POST",
                body: JSON.stringify({
                    user_id: getUser()?.id,
                    seedbed_id: seedbed.id,
                    program_id: document.getElementById("join-program").value,
                    phone: document.getElementById("join-phone").value,
                    message: document.getElementById("join-message").value,
                    status: "PENDIENTE",
                }),
            });
            container.innerHTML = `<div class="alert-success" style="padding:var(--space-3);border-radius:var(--radius-btn)">
                Tu solicitud fue enviada. El líder del semillero te responderá.</div>`;
        } catch (err) {
            errBox.textContent = err.status === 0
                ? "No se pudo enviar. Revisa tu conexión."
                : (err.message || "No se pudo enviar la solicitud.");
            errBox.style.display = "block";
            submitBtn.disabled = false;
        }
    });
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
