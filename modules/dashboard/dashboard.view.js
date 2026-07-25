/* #archivo: /frontend/modules/dashboard/dashboard.view.js */

import { getUser } from "../../services/storage.service.js";

export function DashboardView() {

    const user = getUser();
    const role = user?.role || "";
    const hour = new Date().getHours();
    const greeting = hour < 12 ? "Buenos días" : hour < 19 ? "Buenas tardes" : "Buenas noches";

    /* Quick-access links por rol */
    const links = {
        ADMIN_SISTEMA: [
            { href: "/admin/users",     icon: "👤", label: "Usuarios"      },
            { href: "/admin/faculties", icon: "🏛️", label: "Facultades"    },
            { href: "/admin/programs",  icon: "🎓", label: "Programas"     },
            { href: "/admin/seedbeds",  icon: "🌱", label: "Semilleros"    },
            { href: "/coordinators",    icon: "👔", label: "Coordinadores" },
            { href: "/audits",          icon: "📋", label: "Auditoría"     },
        ],
        ADMINISTRATIVO: [
            { href: "/admin/seedbeds", icon: "🌱", label: "Semilleros"  },
            { href: "/projects",       icon: "📁", label: "Proyectos"   },
            { href: "/products",       icon: "🔬", label: "Productos"   },
            { href: "/results",        icon: "📊", label: "Resultados"  },
        ],
        LIDER_SEMILLERO: [
            { href: "/admin/seedbeds", icon: "🌱", label: "Mi Semillero" },
            { href: "/objectives",     icon: "🎯", label: "Objetivos"    },
            { href: "/results",        icon: "📊", label: "Resultados"   },
            { href: "/projects",       icon: "📁", label: "Proyectos"    },
        ],
        ESTUDIANTE: [
            { href: "/requests",  icon: "📨", label: "Solicitudes" },
            { href: "/proposals", icon: "💡", label: "Propuestas"  },
        ],
    };

    const quickLinks = (links[role] || links.ESTUDIANTE)
        .map(l => `<a href="${l.href}" data-link class="quick-link-card"><span class="ql-icon">${l.icon}</span>${l.label}</a>`)
        .join("");

    /* Charts solo para roles con acceso a datos globales */
    const showCharts = ["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(role);

    return `

    <!-- ── WELCOME ─────────────────────────────────── -->
    <div class="dashboard-welcome">
        <h1>${greeting}, ${user?.name?.split(" ")[0] || "usuario"} 👋</h1>
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
