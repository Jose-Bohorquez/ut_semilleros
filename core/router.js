/* #archivo: /frontend/core/router.js */

/*
🧠 Tip para evitar este error

Cuando trabajes con routers tipo objeto:


export const routes = {
   ruta1: () => {},
   ruta2: () => {},
   ruta3: () => {}
}
*/

/* =========================================================
   #archivo: /frontend/core/router.js
   ---------------------------------------------------------
   Router SPA del sistema de semilleros
   Maneja navegación entre vistas sin recargar la página
   ========================================================= */

import { requireAuth, requireRole } from "./guards.js";
import { LayoutView }              from "../layout/layout.view.js";
import { initLayoutController }    from "../layout/layout.controller.js";
import { getUser }                 from "../services/storage.service.js";


/* =========================================================
   CONTROL DE NAVEGACIÓN
   Evita múltiples navegaciones simultáneas
   ========================================================= */

let navigating = false;


/* =========================================================
   DEFINICIÓN DE RUTAS
   ========================================================= */

export const routes = {

    "/": async () => {

        const view = await import("../modules/auth/auth.view.js");
        const controller = await import("../modules/auth/auth.controller.js");

        document.getElementById("app").innerHTML =
            view.LoginView();

        controller.initLoginController();

    },



    "/dashboard": async () => {

        if (!requireAuth()) return;

        const view = await import("../modules/dashboard/dashboard.view.js");
        const controller = await import("../modules/dashboard/dashboard.controller.js");

        const content = view.DashboardView();

        document.getElementById("app").innerHTML =
            LayoutView(content);

        initLayoutController();
        controller.initDashboardController();

    },



    "/admin/users": async () => {

        if (!requireAuth()) return;

        if (!requireRole(["ADMIN_SISTEMA"])) return;

        const module = await import("../modules/users/users.module.js");

        await module.usersModule.init();

    },



    "/admin/faculties": async () => {

        if (!requireAuth()) return;

        if (!requireRole(["ADMIN_SISTEMA","ADMINISTRATIVO"])) return;

        const module = await import("../modules/faculties/faculties.module.js");

        await module.facultiesModule.init();

    },



    "/admin/programs": async () => {

        if (!requireAuth()) return;

        if (!requireRole(["ADMIN_SISTEMA","ADMINISTRATIVO"])) return;

        const module = await import("../modules/programs/programs.module.js");

        await module.programsModule.init();

    },



    "/admin/seedbeds": async () => {

        if (!requireAuth()) return;

        if (!requireRole(["ADMIN_SISTEMA","ADMINISTRATIVO"])) return;

        const module = await import("../modules/seedbeds/seedbeds.module.js");

        await module.seedbedsModule.init();

    },

    "/projects": async () => {

        if (!requireAuth()) return;

        if (!requireRole([
            "ADMIN_SISTEMA",
            "ADMINISTRATIVO",
            "LIDER_SEMILLERO"
        ])) return;

        const module = await import("../modules/projects/projects.module.js");

        await module.projectsModule.init();

    },

    "/products": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/products/products.module.js");

        await module.productsModule.init();

    },


    "/coordinators": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/coordinators/coordinators.module.js");

        await module.coordinatorsModule.init();

    },

    "/cats": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/cats/cats.module.js");

        await module.catsModule.init();

    },


    "/areas": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/areas/areas.module.js");

        await module.areasModule.init();

    },




    "/groups": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/groups/groups.module.js");

        await module.groupsModule.init();

    },

    "/objectives": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/objectives/objectives.module.js");

        await module.objectivesModule.init();

    },

    "/results": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/results/results.module.js");

        await module.resultsModule.init();

    },

    "/requests": async () => {

        if (!requireAuth()) return;

        /* Estudiante → vista PWA propia (solo sus solicitudes) */
        if (getUser()?.role === "ESTUDIANTE") {
            const m = await import("../modules/pwa/pwa-requests.module.js");
            await m.pwaRequestsModule.init();
            return;
        }

        const module = await import("../modules/requests/requests.module.js");
        await module.requestsModule.init();

    },


    "/proposals": async () => {

        if (!requireAuth()) return;

        /* Estudiante → vista PWA propia (solo sus propuestas) */
        if (getUser()?.role === "ESTUDIANTE") {
            const m = await import("../modules/pwa/pwa-proposals.module.js");
            await m.pwaProposalsModule.init();
            return;
        }

        const module = await import("../modules/proposals/proposals.module.js");
        await module.proposalsModule.init();

    },

    /* Centro de notificaciones — todos los roles */
    "/notifications": async () => {

        if (!requireAuth()) return;

        const m = await import("../modules/notifications/notifications.module.js");
        await m.notificationsModule.init();

    },

    /* Perfil — todos los roles */
    "/profile": async () => {

        if (!requireAuth()) return;

        const m = await import("../modules/pwa/pwa-profile.module.js");
        await m.pwaProfileModule.init();

    },

    /* Semilleros para estudiante — vista de consulta */
    "/seedbeds": async () => {

        if (!requireAuth()) return;

        if (getUser()?.role === "ESTUDIANTE") {
            const m = await import("../modules/pwa/pwa-seedbeds.module.js");
            await m.pwaSeedbedsModule.init();
            return;
        }

        /* Otros roles: redirigir al admin seedbeds */
        const module = await import("../modules/seedbeds/seedbeds.module.js");
        await module.seedbedsModule.init();

    },



    "/audits": async () => {

        if (!requireAuth()) return;

        const module = await import("../modules/audits/audits.module.js");

        await module.auditsModule.init();

    },





};



/* =========================================================
   NAVEGACIÓN SPA
   ========================================================= */

export async function navigateTo(path) {

    if (navigating) return;

    navigating = true;

    try {

        window.history.pushState({}, "", path);

        await renderRoute();

    } catch (error) {

        console.error("Error en navegación:", error);

    }

    navigating = false;

}



/* =========================================================
   RENDER DE RUTA
   ========================================================= */

export async function renderRoute() {

    const path = window.location.pathname;

    const route = routes[path] || routes["/"];

    await route();

}



/* =========================================================
   BOTÓN ATRÁS / ADELANTE DEL NAVEGADOR
   ========================================================= */

window.addEventListener("popstate", renderRoute);