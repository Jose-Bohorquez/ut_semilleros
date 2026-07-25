/* #archivo: /frontend/modules/dashboard/dashboard.controller.js */

import { apiFetch } from "../../services/api.service.js";
import { getUser }  from "../../services/storage.service.js";

export function initDashboardController() {
    const role = getUser()?.role || "";
    loadKPIs(role);
    if (["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(role)) {
        loadCharts();
    }
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
                    y: { grid: { display: false }, ticks: {
                        callback: v => v.length > 20 ? v.substring(0, 18) + "…" : v,
                    }},
                },
            },
        });
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

/* Agrupa semilleros por facultad a través del programa */
function buildSeedbedsByFaculty(seedbeds, programs, faculties) {
    const progFaculty = {};
    programs.forEach(p => { progFaculty[p.id] = p.faculty_id; });

    const facCount = {};
    seedbeds.forEach(s => {
        const facId = progFaculty[s.program_id];
        if (facId) facCount[facId] = (facCount[facId] || 0) + 1;
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
