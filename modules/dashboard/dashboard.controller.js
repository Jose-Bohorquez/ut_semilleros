/* #archivo: /frontend/modules/dashboard/dashboard.controller.js */

import { apiFetch } from "../../services/api.service.js";
import { getUser }  from "../../services/storage.service.js";

export function initDashboardController() {
    const role = getUser()?.role || "";
    loadKPIs(role);
    if (["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(role)) {
        loadCharts();
    }
    if (role === "ADMIN_SISTEMA") {
        loadSystemOverview();
    }
}

/* ─── Panel del sistema (solo ADMIN_SISTEMA): SIA, RBAC, Auditoría ──── */

async function loadSystemOverview() {
    const safe = fn => fn.catch(() => null);
    const [sia, groups, audits] = await Promise.all([
        safe(apiFetch("/sia/admin/stats")),
        safe(apiFetch("/rbac/groups")),
        safe(apiFetch("/audits")),
    ]);

    renderSiaOverview(sia);
    renderRbacOverview(groups);
    renderAuditsOverview(audits);
}

function pct(a, b) { return b ? Math.min(100, Math.round(a / b * 100)) : 0; }

function renderSiaOverview(sia) {
    const body = document.getElementById("sys-sia-body");
    const status = document.getElementById("sys-sia-status");
    if (!body) return;
    if (!sia) { body.innerHTML = `<p class="sys-empty">SIA no está disponible.</p>`; return; }

    const t = sia.today, c = sia.conversations;
    const usagePct = pct(t.requests, t.requests_cap);
    if (status) {
        status.textContent = usagePct >= 80 ? "Cerca del tope" : "Activo";
        status.className = `sys-card-badge ${usagePct >= 80 ? "is-warn" : "is-ok"}`;
    }
    body.innerHTML = `
        <div class="sys-stat-row"><span>Consultas hoy</span><b>${t.requests} / ${t.requests_cap}</b></div>
        <div class="sia-bar"><span style="width:${usagePct}%" class="${usagePct >= 80 ? "is-hot" : ""}"></span></div>
        <div class="sys-stat-row"><span>Calificación promedio</span><b>${c.avg_rating ? `${c.avg_rating} / 5` : "—"}</b></div>
        <div class="sys-stat-row"><span>Por revisar</span><b>${c.unreviewed}</b></div>
    `;
}

function renderRbacOverview(groups) {
    const body = document.getElementById("sys-rbac-body");
    if (!body) return;
    if (!groups) { body.innerHTML = `<p class="sys-empty">No se pudo cargar.</p>`; return; }

    const list = groups.groups || [];
    const totalMembers = list.reduce((sum, g) => sum + (g.users_count || 0), 0);
    body.innerHTML = `
        <div class="sys-stat-row"><span>Grupos de permisos</span><b>${list.length}</b></div>
        <div class="sys-stat-row"><span>Personas en algún grupo</span><b>${totalMembers}</b></div>
        <div class="sys-stat-row"><span>Roles con permisos propios</span><b>3</b></div>
    `;
}

function renderAuditsOverview(audits) {
    const body = document.getElementById("sys-audits-body");
    if (!body) return;
    if (!audits?.audits) { body.innerHTML = `<p class="sys-empty">No se pudo cargar.</p>`; return; }

    const list = audits.audits;
    const weekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000;
    const lastWeek = list.filter(a => new Date(a.created_at).getTime() >= weekAgo).length;
    const latest = list[0];
    body.innerHTML = `
        <div class="sys-stat-row"><span>Eventos totales</span><b>${list.length}</b></div>
        <div class="sys-stat-row"><span>Últimos 7 días</span><b>${lastWeek}</b></div>
        ${latest ? `<p class="sys-latest">Último: ${latest.action} en ${latest.table_name}${latest.user ? " · " + latest.user.name : ""}</p>` : ""}
    `;
}

/* ─── KPIs ─────────────────────────────────────────── */

async function loadKPIs(role) {
    const safe = fn => fn.catch(() => null);
    const isAdmin = ["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(role);

    /* ESTUDIANTE usa endpoints /my (solo los suyos); otros usan el listado general */
    const proposalsEndpoint = role === "ESTUDIANTE" ? "/proposals/my" : "/proposals";
    const requestsEndpoint  = role === "ESTUDIANTE" ? "/requests/my"  : "/requests";

    const [seedbedsData, usersData, proposalsData, requestsData] = await Promise.all([
        safe(apiFetch("/seedbeds")),
        isAdmin ? safe(apiFetch("/users")) : Promise.resolve(null),
        safe(apiFetch(proposalsEndpoint)),
        safe(apiFetch(requestsEndpoint)),
    ]);

    if (seedbedsData?.seedbeds) {
        const total  = seedbedsData.seedbeds.length;
        const active = seedbedsData.seedbeds.filter(s => s.status === "ACTIVO").length;
        setKPI("kpi-seedbeds", active, `${total} total`, "up");
    }

    if (role === "LIDER_SEMILLERO") {
        renderMySeedbedSummary(seedbedsData?.seedbeds || []);
    }

    if (usersData?.users) {
        const total  = usersData.users.length;
        const active = usersData.users.filter(u => u.status === "ACTIVO").length;
        setKPI("kpi-users", total, `${active} activos`, "up");
    } else {
        hideKPI("kpi-users");
    }

    if (proposalsData?.proposals) {
        const list = proposalsData.proposals;
        if (role === "ESTUDIANTE") {
            /* Estudiante: muestra total de sus propuestas y estado */
            const pending = list.filter(p => p.status === "PENDIENTE").length;
            const label   = pending > 0 ? `${pending} pendiente${pending > 1 ? "s" : ""}` : "Ninguna pendiente";
            setKPI("kpi-proposals", list.length, label, pending > 0 ? "down" : "up");
        } else {
            const pending = list.filter(p => p.status === "PENDIENTE").length;
            setKPI("kpi-proposals", pending, pending > 0 ? "Requieren revisión" : "Al día",
                pending > 0 ? "down" : "up");
        }
    }

    if (requestsData?.requests) {
        const list = requestsData.requests;
        if (role === "ESTUDIANTE") {
            const pending = list.filter(r => r.status === "PENDIENTE").length;
            const label   = pending > 0 ? `${pending} pendiente${pending > 1 ? "s" : ""}` : "Ninguna pendiente";
            setKPI("kpi-requests", list.length, label, pending > 0 ? "down" : "up");
        } else {
            const pending = list.filter(r => r.status === "PENDIENTE").length;
            setKPI("kpi-requests", pending, pending > 0 ? "Pendientes de respuesta" : "Al día",
                pending > 0 ? "down" : "up");
        }
    }

    renderRecentActivity(proposalsData?.proposals || [], requestsData?.requests || []);
}

/* ─── Actividad reciente ─────────────────────────────
   Reutiliza los datos de propuestas/solicitudes ya cargados por loadKPIs
   (sin llamadas extra a la API). Combina ambos en una sola línea de tiempo
   ordenada por fecha, para que el dashboard no quede vacío en los roles
   sin gráficas (hallazgo de diseño real, 2026-09-30: Líder/Estudiante
   tenían una zona en blanco enorme debajo de "Acceso rápido"). */
const STATUS_META = {
    PENDIENTE:  { cls: "is-warn", label: "Pendiente" },
    APROBADA:   { cls: "is-ok",   label: "Aprobada"  },
    RECHAZADA:  { cls: "is-err",  label: "Rechazada" },
};

function timeAgo(dateStr) {
    const diffMs = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diffMs / 60000);
    if (mins < 1) return "hace un momento";
    if (mins < 60) return `hace ${mins} min`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours} h`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `hace ${days} d`;
    return new Date(dateStr).toLocaleDateString("es-CO", { day: "numeric", month: "short" });
}

function renderRecentActivity(proposals, requests) {
    const container = document.getElementById("recentActivity");
    if (!container) return;

    const items = [
        ...proposals.map(p => ({ type: "proposal", icon: "fa-lightbulb", title: p.title || "Propuesta", status: p.status, date: p.created_at })),
        ...requests.map(r => ({ type: "request", icon: "fa-paper-plane", title: r.seedbed?.name ? `Solicitud a ${r.seedbed.name}` : "Solicitud de ingreso", status: r.status, date: r.created_at })),
    ]
        .filter(i => i.date)
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 6);

    if (!items.length) {
        container.innerHTML = `
            <div class="empty-state" style="margin-top:0;">
                <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                <p>Aún no hay actividad para mostrar aquí.</p>
            </div>`;
        return;
    }

    container.innerHTML = items.map(i => {
        const meta = STATUS_META[i.status] || { cls: "", label: i.status || "—" };
        return `
        <div class="activity-row">
            <span class="activity-icon"><i class="fas ${i.icon}"></i></span>
            <div class="activity-body">
                <span class="activity-title">${escapeAttr(i.title)}</span>
                <span class="activity-time">${timeAgo(i.date)}</span>
            </div>
            <span class="sys-card-badge ${meta.cls}">${meta.label}</span>
        </div>`;
    }).join("");
}

function escapeAttr(str) {
    return String(str).replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

/* ─── Mi semillero (solo LIDER_SEMILLERO) ────────────
   Filtra client-side, sobre los seedbeds que loadKPIs ya trajo, los que
   este usuario lidera (pivot seedbed_user.role === LIDER) — sin llamada
   nueva a la API. Usa los campos ya calculados por el backend desde CU16
   (members_count, faculty_names) en vez de pedirlos aparte. */
function renderMySeedbedSummary(seedbeds) {
    const container = document.getElementById("mySeedbedSummary");
    if (!container) return;

    const me = getUser();
    const mine = seedbeds.filter(s =>
        (s.users || []).some(u => u.id === me?.id && u.pivot?.role === "LIDER")
    );

    if (!mine.length) {
        container.innerHTML = `
            <div class="empty-state" style="margin-top:0;grid-column:1/-1;">
                <div class="empty-state-icon"><i class="fas fa-seedling"></i></div>
                <h3>Aún no lideras ningún semillero</h3>
                <p>Cuando el sistema te asigne como responsable de un semillero, su resumen
                   aparecerá aquí.</p>
            </div>`;
        return;
    }

    container.innerHTML = mine.map(s => `
        <div class="my-seedbed-card">
            <div class="my-seedbed-card-header">
                <span class="my-seedbed-name">${escapeAttr(s.name)}</span>
                <span class="sys-card-badge ${s.status === "ACTIVO" ? "is-ok" : ""}">${escapeAttr(s.status)}</span>
            </div>
            ${s.faculty_names ? `<p class="my-seedbed-faculty"><i class="fas fa-university"></i> ${escapeAttr(s.faculty_names)}</p>` : ""}
            <div class="my-seedbed-stats">
                <span><i class="fas fa-users"></i> ${s.members_count ?? 0} integrante${(s.members_count ?? 0) === 1 ? "" : "s"}</span>
            </div>
            <a href="/admin/seedbeds" data-link class="sys-card-link">Ver detalle <i class="fas fa-arrow-right"></i></a>
        </div>
    `).join("");
}

function setKPI(id, value, trendLabel, direction) {
    const valueEl = document.getElementById(id);
    const trendEl = document.getElementById(`${id}-trend`);
    if (!valueEl) return;
    valueEl.textContent = value;
    if (trendEl) {
        const icon  = direction === "up" ? "fa-arrow-up" : direction === "down" ? "fa-arrow-down" : "fa-minus";
        const cls   = `kpi-trend kpi-trend-${direction === "up" ? "up" : direction === "down" ? "down" : "flat"}`;
        trendEl.innerHTML = `<i class="fas ${icon}"></i> ${trendLabel}`;
        trendEl.className = cls;
    }
}

function hideKPI(id) {
    document.getElementById(id)?.closest(".kpi-card")?.remove();
}

/* ─── CHARTS (Chart.js) ─────────────────────────────── */

/* Cuando no hay datos aún, el canvas se reemplaza por un estado vacío en vez
   de quedar en blanco sin ninguna explicación (bug real detectado en revisión
   de diseño, 2026-07-28: las 3 tarjetas con datos en 0 no mostraban nada). */
function emptyChart(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    const wrapper = canvas?.closest(".chart-wrapper");
    if (!wrapper) return;
    wrapper.innerHTML = `
        <div class="empty-state" style="margin-top:0;padding:var(--space-8) var(--space-4);">
            <div class="empty-state-icon"><i class="fas fa-chart-simple"></i></div>
            <p>${message}</p>
        </div>
    `;
}

async function loadCharts() {
    if (typeof Chart === "undefined") return;

    const safe = fn => fn.catch(() => null);
    const [seedbeds, proposals, programs, users, faculties] = await Promise.all([
        safe(apiFetch("/seedbeds")),
        safe(apiFetch("/proposals")),
        safe(apiFetch("/programs")),
        safe(apiFetch("/users")),
        safe(apiFetch("/faculties")),
    ]);

    /* Colores del tema activo */
    const cs = getComputedStyle(document.documentElement);
    const primary  = cs.getPropertyValue("--color-primary").trim()  || "#ef4444";
    const accent   = cs.getPropertyValue("--color-accent").trim()   || "#f97316";
    const success  = cs.getPropertyValue("--color-success").trim()  || "#10b981";
    const warning  = cs.getPropertyValue("--color-warning").trim()  || "#f59e0b";
    const info     = cs.getPropertyValue("--color-info").trim()     || "#3b82f6";
    const textMuted= cs.getPropertyValue("--color-text-muted").trim()|| "#6b7280";
    const border   = cs.getPropertyValue("--color-border").trim()   || "#e2e8f0";
    const surface  = cs.getPropertyValue("--color-surface").trim()  || "#ffffff";

    /* Defaults globales de Chart.js */
    Chart.defaults.color          = textMuted;
    Chart.defaults.borderColor    = border;
    Chart.defaults.backgroundColor = surface;
    Chart.defaults.font.family    = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size      = 12;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    /* 1. Doughnut — Semilleros por estado */
    const seedbedList = seedbeds?.seedbeds || [];
    if (seedbedList.length && document.getElementById("chartSeedbedStatus")) {
        const activo   = seedbedList.filter(s => s.status === "ACTIVO").length;
        const inactivo = seedbedList.filter(s => s.status === "INACTIVO").length;
        new Chart(document.getElementById("chartSeedbedStatus"), {
            type: "doughnut",
            data: {
                labels: ["Activo", "Inactivo"],
                datasets: [{
                    data: [activo, inactivo],
                    backgroundColor: [success, textMuted],
                    borderWidth: 2,
                    borderColor: surface,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",
                plugins: {
                    legend: { position: "bottom" },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / seedbedList.length * 100)}%)`,
                        },
                    },
                },
            },
        });
    } else {
        emptyChart("chartSeedbedStatus", "Aún no hay semilleros registrados.");
    }

    /* 2. Bar — Propuestas por estado */
    const proposalList = proposals?.proposals || [];
    if (proposalList.length && document.getElementById("chartProposalStatus")) {
        const counts = {
            PENDIENTE: proposalList.filter(p => p.status === "PENDIENTE").length,
            APROBADA:  proposalList.filter(p => p.status === "APROBADA").length,
            RECHAZADA: proposalList.filter(p => p.status === "RECHAZADA").length,
        };
        new Chart(document.getElementById("chartProposalStatus"), {
            type: "bar",
            data: {
                labels: ["Pendientes", "Aprobadas", "Rechazadas"],
                datasets: [{
                    label: "Propuestas",
                    data:  [counts.PENDIENTE, counts.APROBADA, counts.RECHAZADA],
                    backgroundColor: [warning, success, primary],
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: border },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    } else {
        emptyChart("chartProposalStatus", "Aún no hay propuestas registradas.");
    }

    /* 3. Horizontal bar — Semilleros por facultad */
    const seedbedsByFaculty = buildSeedbedsByFaculty(
        seedbeds?.seedbeds || [],
        programs?.programs || [],
        faculties?.faculties || []
    );
    if (seedbedsByFaculty.labels.length && document.getElementById("chartSeedbedFaculty")) {
        new Chart(document.getElementById("chartSeedbedFaculty"), {
            type: "bar",
            data: {
                labels: seedbedsByFaculty.labels,
                datasets: [{
                    label: "Semilleros",
                    data: seedbedsByFaculty.values,
                    backgroundColor: [primary, accent, info, success, warning, "#8b5cf6"],
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: border } },
                    /* Bug real (revisión de diseño, 2026-09-29): con indexAxis:"y" Chart.js
                       manda al callback el ÍNDICE numérico del tick, no el nombre — por eso
                       el eje mostraba 0,1,2… en vez de la facultad. this.getLabelForValue()
                       resuelve el índice al texto real (necesita function, no arrow, por el "this"). */
                    y: { grid: { display: false }, ticks: {
                        callback: function (val) {
                            const label = this.getLabelForValue(val);
                            return label.length > 20 ? label.substring(0, 18) + "…" : label;
                        },
                    }},
                },
            },
        });
    } else {
        emptyChart("chartSeedbedFaculty", "Aún no hay semilleros asociados a facultades.");
    }

    /* 4. Doughnut — Usuarios por rol */
    const userList = users?.users || [];
    if (userList.length && document.getElementById("chartUserRoles")) {
        const roles = ["ADMIN_SISTEMA", "ADMINISTRATIVO", "LIDER_SEMILLERO", "ESTUDIANTE"];
        const roleCounts = roles.map(r => userList.filter(u => u.role === r).length);
        const roleLabels = ["Admin", "Administrativo", "Líder Semillero", "Estudiante"];
        new Chart(document.getElementById("chartUserRoles"), {
            type: "doughnut",
            data: {
                labels: roleLabels,
                datasets: [{
                    data: roleCounts,
                    backgroundColor: [primary, info, success, accent],
                    borderWidth: 2,
                    borderColor: surface,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",
                plugins: {
                    legend: { position: "bottom" },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed}`,
                        },
                    },
                },
            },
        });
    }
}

/* Agrupa semilleros por facultad a través de sus programas (relación
   múltiple desde CU13 Ronda B: seedbed.programs[], ya no seedbed.program_id).
   Un semillero con programas de varias facultades cuenta una vez por cada
   facultad distinta a la que pertenece. */
function buildSeedbedsByFaculty(seedbeds, programs, faculties) {
    const progFaculty = {};
    programs.forEach(p => { progFaculty[p.id] = p.faculty_id; });

    const facCount = {};
    seedbeds.forEach(s => {
        const facIds = new Set((s.programs || []).map(p => progFaculty[p.id]).filter(Boolean));
        facIds.forEach(facId => { facCount[facId] = (facCount[facId] || 0) + 1; });
    });

    const labels = [], values = [];
    faculties.forEach(f => {
        if (facCount[f.id]) {
            labels.push(f.name);
            values.push(facCount[f.id]);
        }
    });

    return { labels, values };
}
