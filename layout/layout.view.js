/* #archivo: /frontend/layout/layout.view.js */

import { getUser } from "../services/storage.service.js";

/* =========================================================
   Bottom nav items por rol
   Máximo 5 ítems — los más usados de cada rol
   ========================================================= */

const BOTTOM_NAV = {

    ADMIN_SISTEMA: [
        { href: "/dashboard",      icon: "fa-home",           label: "Inicio"      },
        { href: "/admin/seedbeds", icon: "fa-seedling",       label: "Semilleros"  },
        { href: "/admin/users",    icon: "fa-users",          label: "Usuarios"    },
        { href: "/audits",         icon: "fa-clipboard-list", label: "Auditoría"   },
        { href: "/profile",        icon: "fa-user-circle",    label: "Perfil"      },
    ],

    ADMINISTRATIVO: [
        { href: "/dashboard",      icon: "fa-home",           label: "Inicio"      },
        { href: "/admin/seedbeds", icon: "fa-seedling",       label: "Semilleros"  },
        { href: "/requests",       icon: "fa-paper-plane",    label: "Solicitudes" },
        { href: "/proposals",      icon: "fa-lightbulb",      label: "Propuestas"  },
        { href: "/profile",        icon: "fa-user-circle",    label: "Perfil"      },
    ],

    LIDER_SEMILLERO: [
        { href: "/dashboard",      icon: "fa-home",           label: "Inicio"      },
        { href: "/admin/seedbeds", icon: "fa-seedling",       label: "Semilleros"  },
        { href: "/requests",       icon: "fa-paper-plane",    label: "Solicitudes" },
        { href: "/proposals",      icon: "fa-lightbulb",      label: "Propuestas"  },
        { href: "/profile",        icon: "fa-user-circle",    label: "Perfil"      },
    ],

    /* ESTUDIANTE — 5 ítems PWA enfocados en su flujo */
    ESTUDIANTE: [
        { href: "/dashboard",  icon: "fa-home",        label: "Inicio"      },
        { href: "/seedbeds",   icon: "fa-seedling",    label: "Semilleros"  },
        { href: "/requests",   icon: "fa-paper-plane", label: "Solicitudes" },
        { href: "/proposals",  icon: "fa-lightbulb",   label: "Propuestas"  },
        { href: "/profile",    icon: "fa-user-circle", label: "Perfil"      },
    ],

};

export function LayoutView(content = "") {

    const user = getUser();
    const role = user?.role || "";

    /* ─── SIDEBAR MENU ──────────────────────────────── */

    let menu = `
        <a href="/dashboard" data-link>
            <i class="fas fa-home"></i>
            Dashboard
        </a>
    `;

    if (role === "ADMIN_SISTEMA") {
        menu += `
        <a href="/admin/users"      data-link><i class="fas fa-users"></i>          Usuarios</a>
        <a href="/admin/faculties"  data-link><i class="fas fa-university"></i>      Facultades</a>
        <a href="/admin/programs"   data-link><i class="fas fa-graduation-cap"></i>  Programas</a>
        <a href="/cats"             data-link><i class="fas fa-map-marker-alt"></i>  CAT</a>
        <a href="/areas"            data-link><i class="fas fa-layer-group"></i>     Áreas</a>
        <a href="/groups"           data-link><i class="fas fa-object-group"></i>    Grupos</a>
        <a href="/coordinators"     data-link><i class="fas fa-user-tie"></i>        Coordinadores</a>
        <a href="/audits"           data-link><i class="fas fa-clipboard-list"></i>  Auditoría</a>
        `;
    }

    if (role === "ADMINISTRATIVO" || role === "ADMIN_SISTEMA") {
        menu += `
        <a href="/admin/seedbeds"   data-link><i class="fas fa-seedling"></i>        Semilleros</a>
        <a href="/projects"         data-link><i class="fas fa-project-diagram"></i> Proyectos</a>
        <a href="/products"         data-link><i class="fas fa-flask"></i>           Productos</a>
        <a href="/results"          data-link><i class="fas fa-chart-bar"></i>       Resultados</a>
        `;
    }

    if (role === "LIDER_SEMILLERO") {
        menu += `
        <a href="/admin/seedbeds"   data-link><i class="fas fa-seedling"></i>        Semilleros</a>
        <a href="/objectives"       data-link><i class="fas fa-bullseye"></i>        Objetivos</a>
        <a href="/results"          data-link><i class="fas fa-chart-bar"></i>       Resultados</a>
        <a href="/projects"         data-link><i class="fas fa-project-diagram"></i> Proyectos</a>
        `;
    }

    if (role === "ESTUDIANTE") {
        menu += `
        <a href="/requests"         data-link><i class="fas fa-paper-plane"></i>     Solicitudes</a>
        <a href="/proposals"        data-link><i class="fas fa-lightbulb"></i>       Propuestas</a>
        `;
    }


    /* ─── BOTTOM NAV ────────────────────────────────── */

    const navItems  = BOTTOM_NAV[role] || BOTTOM_NAV.ESTUDIANTE;
    const bottomNav = navItems.map(item => `
        <a href="${item.href}" data-link
           class="nav-item"
           data-path="${item.href}"
           aria-label="${item.label}">
            <i class="fas ${item.icon}"></i>
            <span>${item.label}</span>
        </a>
    `).join("");


    /* ─── HTML ──────────────────────────────────────── */

    return `
    <div class="layout">

        <!-- ── TOP APP BAR ──────────────────────────── -->
        <header class="navbar">

            <div style="display:flex;align-items:center;gap:12px">
                <button class="hamburger-btn" id="hamburgerBtn"
                        aria-label="Abrir menú" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <strong>&#127807; Semilleros IDEAD</strong>
                </div>
            </div>

            <div class="user-info">
                <!-- Avatar del navbar: foto si existe, iniciales si no -->
                <a href="/profile" data-link style="text-decoration:none;display:flex;align-items:center;gap:6px"
                   title="Ver perfil">
                    ${user?.profile_photo
                        ? `<img src="${user.profile_photo}"
                               style="width:28px;height:28px;border-radius:50%;
                                      object-fit:cover;border:2px solid rgba(255,255,255,0.4);
                                      flex-shrink:0"
                               alt="Foto de perfil">`
                        : `<div style="width:28px;height:28px;border-radius:50%;
                                        background:rgba(255,255,255,0.2);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:11px;font-weight:700;color:#fff;flex-shrink:0;
                                        border:2px solid rgba(255,255,255,0.3)">
                               ${(user?.name || "?").split(" ").slice(0,2).map(w=>w[0]).join("").toUpperCase()}
                           </div>`
                    }
                    <span class="username">${user?.name?.split(" ")[0] || ""}</span>
                </a>
                <!-- Campana de notificaciones -->
                <a href="/notifications" data-link id="bellBtn"
                   class="bell-btn" aria-label="Notificaciones" title="Notificaciones">
                    <i class="fas fa-bell"></i>
                    <span id="notifBadge" class="notif-badge" style="display:none">0</span>
                </a>

                <button id="themeToggleBtn" class="btn-theme-toggle"
                        aria-label="Cambiar tema" title="Cambiar modo claro/oscuro">
                    <i id="themeIcon" class="fas fa-sun"></i>
                </button>
                <button id="logoutBtn" class="btn btn-sm"
                        style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2)">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="pwa-hide-label">Salir</span>
                </button>
            </div>

        </header>

        <!-- Overlay mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="main">

            <!-- SIDEBAR (desktop / menú completo en mobile) -->
            <aside class="sidebar" id="sidebar">
                <nav class="menu">
                    ${menu}
                </nav>
            </aside>

            <!-- CONTENIDO -->
            <section class="content" id="content">
                ${content}
            </section>

        </div>

        <!-- ── BOTTOM NAV (solo mobile) ─────────────── -->
        <nav class="pwa-bottom-nav" id="pwaBottomNav" aria-label="Navegación principal">
            ${bottomNav}
        </nav>

    </div>
    `;
}
