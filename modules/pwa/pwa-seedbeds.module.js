/* #archivo: frontend/modules/pwa/pwa-seedbeds.module.js
   Consulta de semilleros — ROL: ESTUDIANTE (solo lectura)
   ─────────────────────────────────────────────────────── */

import { apiFetch }            from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";

export const pwaSeedbedsModule = {

    async init() {
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
    let seedbeds = [];
    try {
        const data = await apiFetch("/seedbeds");
        seedbeds   = (data.seedbeds || []).filter(s => s.status === "ACTIVO");
    } catch (err) {
        renderError(err.message);
        return;
    }
    renderList(seedbeds);
    bindEvents(seedbeds);
}

function renderList(seedbeds) {
    const cards = seedbeds.length === 0
        ? `<div class="empty-state">
               <div class="empty-state-icon"><i class="fas fa-seedling"></i></div>
               <h3>Sin semilleros activos</h3>
               <p>No hay semilleros activos disponibles en este momento.</p>
           </div>`
        : seedbeds.map(s => {
            const prog = s.program?.name || "";
            const initials = s.name.split(" ").slice(0,2).map(w => w[0]).join("").toUpperCase();
            return `
            <div class="pwa-card seedbed-card" data-id="${s.id}" style="cursor:pointer">
                <div class="card-avatar avatar-green" style="font-size:1rem;font-weight:700">
                    ${initials}
                </div>
                <div class="card-body">
                    <div class="card-title">${s.name}</div>
                    ${prog ? `<div class="card-subtitle"><i class="fas fa-graduation-cap" style="margin-right:4px"></i>${prog}</div>` : ""}
                    <div class="card-meta">
                        <span class="badge-pwa badge-pwa-success" style="margin-top:4px;display:inline-flex">Activo</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right card-arrow"></i>
            </div>`;
        }).join("");

    const content = `
    <div style="padding:var(--space-4)">

        <!-- Buscador -->
        <div style="position:relative;margin-bottom:var(--space-4)">
            <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);
               color:var(--color-text-faint);font-size:0.875rem"></i>
            <input id="seedbedSearch" class="pwa-input"
                   type="search" placeholder="Buscar semillero..."
                   style="padding-left:40px">
        </div>

        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-seedling" style="color:var(--color-primary);margin-right:8px"></i>
            Semilleros
            <span style="font-size:var(--text-sm);font-weight:400;color:var(--color-text-muted);margin-left:6px">
                (${seedbeds.length} activo${seedbeds.length !== 1 ? "s" : ""})
            </span>
        </h2>

        <div id="seedbedsList">${cards}</div>

    </div>

    <!-- Bottom sheet: detalle del semillero -->
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

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
}

function renderError(msg) {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Error al cargar semilleros: ${msg}</span>
        </div>
    </div>`);
    initLayoutController();
}

function bindEvents(seedbeds) {

    /* Buscador */
    document.getElementById("seedbedSearch")?.addEventListener("input", e => {
        const q     = e.target.value.toLowerCase();
        const cards = document.querySelectorAll(".seedbed-card");
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(q) ? "" : "none";
        });
    });

    /* Abrir detalle */
    document.addEventListener("click", async e => {
        const card = e.target.closest(".seedbed-card");
        if (!card) return;
        const id = parseInt(card.dataset.id);
        const seedbed = seedbeds.find(s => s.id === id);
        if (!seedbed) return;
        openDetail(seedbed);
        await loadObjectivesForSeedbed(id);
    });

    /* Cerrar detalle */
    document.getElementById("closeDetail")?.addEventListener("click", () => {
        document.getElementById("seedbedDetail").style.display = "none";
    });

    document.getElementById("seedbedDetail")?.addEventListener("click", e => {
        if (e.target.id === "seedbedDetail")
            e.target.style.display = "none";
    });
}

function openDetail(seedbed) {
    const sheet = document.getElementById("seedbedDetail");
    document.getElementById("detailTitle").textContent = seedbed.name;
    document.getElementById("detailContent").innerHTML = `
        <div style="display:flex;flex-direction:column;gap:var(--space-3);margin-bottom:var(--space-5)">
            ${seedbed.program?.name ? `
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Programa</span>
                <span style="font-size:var(--text-sm);font-weight:500">${seedbed.program.name}</span>
            </div>` : ""}
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Estado</span>
                <span class="badge-pwa badge-pwa-success">Activo</span>
            </div>
        </div>
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
                ${o.content}
            </p>
        </div>`).join("");
    } catch {
        container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--color-error)">
            Error al cargar objetivos.</p>`;
    }
}
