/* #archivo: /frontend/modules/dashboard/dashboard.view.js */

import { getUser } from "../../services/storage.service.js";
import { escapeHtml }      from "../../core/escape.js";

export function DashboardView() {

    const user = getUser();
    const role = user?.role || "";
    const hour = new Date().getHours();
    const greeting = hour < 12 ? "Buenos días" : hour < 19 ? "Buenas tardes" : "Buenas noches";

    /* Quick-access links por rol — mismos íconos FontAwesome que el sidebar,
       para que un usuario reconozca la sección al primer vistazo en vez de
       aprenderse un segundo set de símbolos (antes usaban emoji). */
    const links = {
        ADMIN_SISTEMA: [
            { href: "/admin/users",     icon: "fa-users",          label: "Usuarios"      },
            { href: "/admin/faculties", icon: "fa-university",     label: "Facultades"    },
            { href: "/admin/programs",  icon: "fa-graduation-cap", label: "Programas"     },
            { href: "/admin/seedbeds",  icon: "fa-seedling",       label: "Semilleros"    },
            { href: "/coordinators",    icon: "fa-user-tie",       label: "Coordinadores" },
            { href: "/audits",          icon: "fa-clipboard-list", label: "Auditoría"     },
        ],
        ADMINISTRATIVO: [
            { href: "/admin/seedbeds", icon: "fa-seedling",        label: "Semilleros"  },
            { href: "/projects",       icon: "fa-project-diagram", label: "Proyectos"   },
            { href: "/products",       icon: "fa-flask",           label: "Productos"   },
            { href: "/results",        icon: "fa-chart-bar",       label: "Resultados"  },
        ],
        LIDER_SEMILLERO: [
            { href: "/admin/seedbeds", icon: "fa-seedling",        label: "Mi Semillero" },
            { href: "/objectives",     icon: "fa-bullseye",        label: "Objetivos"    },
            { href: "/results",        icon: "fa-chart-bar",       label: "Resultados"   },
            { href: "/projects",       icon: "fa-project-diagram", label: "Proyectos"    },
        ],
        ESTUDIANTE: [
            { href: "/requests",  icon: "fa-paper-plane", label: "Solicitudes" },
            { href: "/proposals", icon: "fa-lightbulb",   label: "Propuestas"  },
        ],
    };

    const quickLinks = (links[role] || links.ESTUDIANTE)
        .map(l => `<a href="${l.href}" data-link class="quick-link-card"><span class="ql-icon"><i class="fas ${l.icon}" aria-hidden="true"></i></span>${l.label}</a>`)
        .join("");

    /* Charts solo para roles con acceso a datos globales */
    const showCharts = ["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(role);
    const isSystemAdmin = role === "ADMIN_SISTEMA";
    const today = new Date().toLocaleDateString("es-CO", { weekday: "long", day: "numeric", month: "long" });

    return `

    <!-- ── WELCOME ─────────────────────────────────── -->
    <div class="dashboard-welcome">
        <p class="dashboard-date">${today.charAt(0).toUpperCase() + today.slice(1)}</p>
        <h1>${greeting}, ${escapeHtml(user?.name?.split(" ")[0] || "usuario")} 👋</h1>
        <p>Sistema de Semilleros de Investigación — Universidad del Tolima, IDEAD</p>
        <div class="accent-divider"></div>
    </div>

    <!-- ── KPI GRID ───────────────────────────────── -->
    <div class="kpi-grid" id="kpiGrid">

        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-label">Semilleros activos</span>
                <div class="kpi-icon kpi-icon-green"><i class="fas fa-seedling"></i></div>
            </div>
            <div class="kpi-value" id="kpi-seedbeds">
                <div class="skeleton skeleton-row" style="width:60px;height:36px"></div>
            </div>
            <span class="kpi-trend kpi-trend-flat" id="kpi-seedbeds-trend">—</span>
        </div>

        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-label">Usuarios</span>
                <div class="kpi-icon kpi-icon-blue"><i class="fas fa-users"></i></div>
            </div>
            <div class="kpi-value" id="kpi-users">
                <div class="skeleton skeleton-row" style="width:60px;height:36px"></div>
            </div>
            <span class="kpi-trend kpi-trend-flat" id="kpi-users-trend">—</span>
        </div>

        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-label" id="kpi-proposals-label">
                    ${role === "ESTUDIANTE" ? "Mis propuestas" : "Propuestas pendientes"}
                </span>
                <div class="kpi-icon kpi-icon-yellow"><i class="fas fa-lightbulb"></i></div>
            </div>
            <div class="kpi-value" id="kpi-proposals">
                <div class="skeleton skeleton-row" style="width:60px;height:36px"></div>
            </div>
            <span class="kpi-trend kpi-trend-flat" id="kpi-proposals-trend">—</span>
        </div>

        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-label" id="kpi-requests-label">
                    ${role === "ESTUDIANTE" ? "Mis solicitudes" : "Solicitudes pendientes"}
                </span>
                <div class="kpi-icon kpi-icon-red"><i class="fas fa-paper-plane"></i></div>
            </div>
            <div class="kpi-value" id="kpi-requests">
                <div class="skeleton skeleton-row" style="width:60px;height:36px"></div>
            </div>
            <span class="kpi-trend kpi-trend-flat" id="kpi-requests-trend">—</span>
        </div>

    </div>

    <!-- ── PANEL DEL SISTEMA (solo ADMIN_SISTEMA) ──── -->
    ${isSystemAdmin ? `
    <h3 class="section-title">
        <i class="fas fa-gauge-high" style="color:var(--color-primary);margin-right:6px"></i>
        Panel del sistema
    </h3>
    <div class="sys-overview-grid" id="sysOverviewGrid">

        <a href="/admin/sia" data-link class="sys-card">
            <div class="sys-card-header">
                <span class="sys-card-title"><i class="fas fa-robot"></i> SIA hoy</span>
                <span class="sys-card-badge" id="sys-sia-status">—</span>
            </div>
            <div class="sys-card-body" id="sys-sia-body">
                <div class="skeleton skeleton-row" style="width:100%;height:52px"></div>
            </div>
            <span class="sys-card-link">Ver panel de SIA <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="/admin/rbac" data-link class="sys-card">
            <div class="sys-card-header">
                <span class="sys-card-title"><i class="fas fa-user-shield"></i> Permisos (RBAC)</span>
            </div>
            <div class="sys-card-body" id="sys-rbac-body">
                <div class="skeleton skeleton-row" style="width:100%;height:52px"></div>
            </div>
            <span class="sys-card-link">Administrar permisos <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="/audits" data-link class="sys-card">
            <div class="sys-card-header">
                <span class="sys-card-title"><i class="fas fa-clipboard-list"></i> Auditoría</span>
            </div>
            <div class="sys-card-body" id="sys-audits-body">
                <div class="skeleton skeleton-row" style="width:100%;height:52px"></div>
            </div>
            <span class="sys-card-link">Ver registro completo <i class="fas fa-arrow-right"></i></span>
        </a>

    </div>
    ` : ""}

    <!-- ── CHARTS (solo admin / administrativo) ───── -->
    ${showCharts ? `
    <h3 class="section-title">
        <i class="fas fa-chart-line" style="color:var(--color-primary);margin-right:6px"></i>
        Estadísticas
    </h3>
    <div class="charts-grid">

        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <p class="chart-card-title">Semilleros por estado</p>
                    <p class="chart-card-subtitle">Distribución actual</p>
                </div>
                <i class="fas fa-chart-pie" style="color:var(--color-primary);font-size:20px"></i>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartSeedbedStatus"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <p class="chart-card-title">Propuestas por estado</p>
                    <p class="chart-card-subtitle">Pendientes / Aprobadas / Rechazadas</p>
                </div>
                <i class="fas fa-chart-bar" style="color:var(--color-primary);font-size:20px"></i>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartProposalStatus"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <p class="chart-card-title">Semilleros por facultad</p>
                    <p class="chart-card-subtitle">A través de los programas</p>
                </div>
                <i class="fas fa-university" style="color:var(--color-primary);font-size:20px"></i>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartSeedbedFaculty"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <p class="chart-card-title">Usuarios por rol</p>
                    <p class="chart-card-subtitle">Distribución de roles en el sistema</p>
                </div>
                <i class="fas fa-user-tag" style="color:var(--color-primary);font-size:20px"></i>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartUserRoles"></canvas>
            </div>
        </div>

    </div>
    ` : ""}

    <!-- ── QUICK ACCESS ────────────────────────────── -->
    <h3 class="section-title">
        <i class="fas fa-bolt" style="color:var(--color-primary);margin-right:6px"></i>
        Acceso rápido
    </h3>
    <div class="quick-links-grid">
        ${quickLinks}
    </div>
    `;
}
