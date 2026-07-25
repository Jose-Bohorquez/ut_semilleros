/* #archivo: /frontend/layout/layout.controller.js */

import { logout }          from "../modules/auth/auth.service.js";
import { navigateTo }      from "../core/router.js";
import { startBadgePolling } from "../modules/notifications/notifications.badge.js";

/* Mapa ruta → título para el top bar en mobile */
const PAGE_TITLES = {
    "/dashboard":      "Inicio",
    "/admin/users":    "Usuarios",
    "/admin/faculties":"Facultades",
    "/admin/programs": "Programas",
    "/admin/seedbeds": "Semilleros",
    "/seedbeds":       "Semilleros",
    "/cats":           "CAT",
    "/areas":          "Áreas",
    "/groups":         "Grupos",
    "/coordinators":   "Coordinadores",
    "/audits":         "Auditoría",
    "/projects":       "Proyectos",
    "/products":       "Productos",
    "/results":        "Resultados",
    "/objectives":     "Objetivos",
    "/requests":       "Solicitudes",
    "/proposals":      "Propuestas",
    "/profile":        "Mi Perfil",
    "/notifications":  "Notificaciones",
};

/* ──────────────────────────────────────────────────────────
   IMPORTANTE: el listener de navegación SPA se registra UNA
   SOLA VEZ a nivel de módulo.  initLayoutController() se
   llama en cada renderizado; si registrara el listener dentro
   se acumularían N listeners → N navegaciones simultáneas.
   ────────────────────────────────────────────────────────── */
let _navListenerRegistered  = false;
let _pushSubscribeCalled    = false;

function _registerNavListener() {
    if (_navListenerRegistered) return;
    _navListenerRegistered = true;

    document.addEventListener("click", function (e) {
        const el = e.target.matches("[data-link]")
            ? e.target
            : e.target.closest("[data-link]");
        if (!el) return;
        e.preventDefault();
        _closeSidebar();
        navigateTo(el.getAttribute("href").replace(window.location.origin, ""));
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") _closeSidebar();
    });
}

/* Sidebar helpers accesibles dentro y fuera del módulo */
function _openSidebar() {
    const hamburger = document.getElementById("hamburgerBtn");
    const sidebar   = document.getElementById("sidebar");
    const overlay   = document.getElementById("sidebarOverlay");
    sidebar?.classList.add("open");
    overlay?.classList.add("active");
    hamburger?.setAttribute("aria-expanded", "true");
    hamburger?.querySelector("i")?.classList.replace("fa-bars", "fa-times");
}

function _closeSidebar() {
    const hamburger = document.getElementById("hamburgerBtn");
    const sidebar   = document.getElementById("sidebar");
    const overlay   = document.getElementById("sidebarOverlay");
    sidebar?.classList.remove("open");
    overlay?.classList.remove("active");
    hamburger?.setAttribute("aria-expanded", "false");
    hamburger?.querySelector("i")?.classList.replace("fa-times", "fa-bars");
}


/* ── INIT — llamado en cada renderizado de ruta ─────── */

export function initLayoutController() {

    const currentPath = window.location.pathname;

    /* Registra el listener de navegación solo la primera vez */
    _registerNavListener();

    /* Inicia polling del badge de notificaciones (solo una vez) */
    startBadgePolling();

    /* Suscribir al push una sola vez por sesión, tras validar que hay sesión activa */
    if (!_pushSubscribeCalled) {
        _pushSubscribeCalled = true;
        window.__subscribePush?.();
    }

    /* Logout — se añade al botón que acaba de renderizar */
    document.getElementById("logoutBtn")?.addEventListener("click", async () => {
        await logout();
        navigateTo("/");
    });

    /* Theme toggle — se añade al botón que acaba de renderizar */
    syncThemeIcon();
    document.getElementById("themeToggleBtn")?.addEventListener("click", () => {
        const isDark = document.documentElement.getAttribute("data-theme") === "dark";
        applyTheme(isDark ? "light" : "dark");
    });

    /* Hamburger / sidebar mobile */
    const hamburger = document.getElementById("hamburgerBtn");
    const overlay   = document.getElementById("sidebarOverlay");

    hamburger?.addEventListener("click", () =>
        document.getElementById("sidebar")?.classList.contains("open")
            ? _closeSidebar()
            : _openSidebar()
    );
    overlay?.addEventListener("click", _closeSidebar);

    /* Marcar enlace activo en sidebar */
    document.querySelectorAll(".sidebar a[data-link]").forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === currentPath);
    });

    /* Marcar ítem activo en bottom nav */
    document.querySelectorAll(".pwa-bottom-nav .nav-item").forEach(item => {
        const href = item.getAttribute("data-path") || item.getAttribute("href");
        item.classList.toggle("active", href === currentPath);
    });

    /* Actualizar título en mobile top bar */
    const logoEl   = document.querySelector(".navbar .logo strong");
    const isMobile = window.innerWidth <= 768;
    if (logoEl && isMobile) {
        const title = PAGE_TITLES[currentPath];
        if (title) logoEl.textContent = title;
    }
}


/* ── HELPERS EXPORTADOS ─────────────────────────────── */

export function applyTheme(theme) {
    if (theme === "dark") {
        document.documentElement.setAttribute("data-theme", "dark");
    } else {
        document.documentElement.removeAttribute("data-theme");
    }
    localStorage.setItem("theme", theme);
    syncThemeIcon();
}

export function syncThemeIcon() {
    const isDark = document.documentElement.getAttribute("data-theme") === "dark";
    const icon   = document.getElementById("themeIcon");
    if (!icon) return;
    icon.className = isDark ? "fas fa-moon" : "fas fa-sun";
}
