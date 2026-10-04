/* #archivo: /frontend/layout/layout.controller.js */

import { logout }          from "../modules/auth/auth.service.js";
import { navigateTo }      from "../core/router.js?v=2";
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
    "/reports":        "Reportes",
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
        _closeUserMenu();
        navigateTo(el.getAttribute("href").replace(window.location.origin, ""));
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") { _closeSidebar(); _closeUserMenu(); _closeBottomSheet(); }
    });

    /* Tocar fuera del menú de usuario lo cierra */
    document.addEventListener("click", e => {
        if (!e.target.closest("#userMenu, #userAvatarLink")) _closeUserMenu();
    });
}

/* Hojas inferiores de la PWA (detalle de semillero, nueva propuesta/notificación, detalle de solicitud):
   Escape cierra la que esté abierta pulsando su propia «×» (así cada módulo hace su limpieza habitual).
   Si hay un SweetAlert o el modal de crear abierto, esos ya gestionan su Escape y no se toca nada. */
const _SHEETS = [
    ["notifSheet", "closeNotifSheet"], ["proposalSheet", "closeProposalSheet"],
    ["requestDetailSheet", "closeRequestDetailBtn"], ["seedbedDetail", "closeDetail"],
];
function _closeBottomSheet() {
    if (document.querySelector(".swal2-container, #crudModal")) return;
    for (const [sheetId, closeId] of _SHEETS) {
        const sheet = document.getElementById(sheetId);
        if (sheet && getComputedStyle(sheet).display !== "none") {
            document.getElementById(closeId)?.click();
            return;
        }
    }
}

function _openUserMenu() {
    const menu = document.getElementById("userMenu");
    if (!menu) return;
    menu.hidden = false;
    document.getElementById("userAvatarLink")?.setAttribute("aria-expanded", "true");
}

function _closeUserMenu() {
    const menu = document.getElementById("userMenu");
    if (!menu || menu.hidden) return;
    menu.hidden = true;
    document.getElementById("userAvatarLink")?.setAttribute("aria-expanded", "false");
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

    /* Logout — el botón de la barra (escritorio) y el ítem del menú de usuario (móvil) */
    document.querySelectorAll("#logoutBtn, [data-action='logout']").forEach(el => el.addEventListener("click", async e => {
        const btn = e.currentTarget;
        if (btn.disabled) return;   /* doble clic */
        btn.disabled = true;
        btn.setAttribute("aria-busy", "true");
        await logout();
        navigateTo("/");   /* CU03 paso 4 */
    }));

    /* Theme toggle — idem */
    syncThemeIcon();
    document.querySelectorAll("#themeToggleBtn, [data-action='theme']").forEach(el => el.addEventListener("click", () => {
        const isDark = document.documentElement.getAttribute("data-theme") === "dark";
        applyTheme(isDark ? "light" : "dark");
        _closeUserMenu();
    }));

    /* Menú de usuario (móvil): el avatar lo abre; en escritorio sigue llevando al perfil */
    const avatar = document.getElementById("userAvatarLink");
    avatar?.addEventListener("click", e => {
        if (!window.matchMedia("(max-width: 768px)").matches) return;   // escritorio: navega al perfil
        e.preventDefault();
        e.stopPropagation();                                              // el listener global de [data-link] no debe navegar
        const menu = document.getElementById("userMenu");
        menu?.hidden ? _openUserMenu() : _closeUserMenu();
    }, true);

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
    if (icon) icon.className = isDark ? "fas fa-moon" : "fas fa-sun";
    document.querySelectorAll("[data-theme-label]").forEach(l => { l.textContent = isDark ? "Modo claro" : "Modo oscuro"; });
}
