/*archivo: frontend/modules/coordinators/coordinators.module.js*/

import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const coordinatorsModule = createCrudModule({

entity:"coordinators",

title:"Gestión de Coordinadores",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {name:"document",label:"Documento",type:"text",required:false,hint:"Único."},

 {name:"email",label:"Email",type:"email"},

 {name:"phone",label:"Teléfono",type:"text"},

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"ACTIVO",label:"ACTIVO"},
   {value:"INACTIVO",label:"INACTIVO"}
  ]
 }

],

/* CU12-A5: actor principal es ADMIN_SISTEMA; Líder y Administrativo solo
   consultan (antes el Líder sí podía escribir — desalineado con la spec,
   corregido 2026-09-29). */
noCreateFor: ['LIDER_SEMILLERO', 'ADMINISTRATIVO'],
noEditFor: ['LIDER_SEMILLERO', 'ADMINISTRATIVO'],

/* CU12-A4: filtrar por estado (buscador de texto ya lo da DataTables) */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU12 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU12-A1: ver detalle */
actions: [
    { label: "Ver", class: "viewCoordinatorBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewCoordinatorBtn");
    if (!btn) return;
    const { coordinator: c } = await apiFetch(`/coordinators/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(c.name),
        html: `
            <div style="text-align:left">
                <p><b>Documento:</b> ${escapeHtml(c.document || "—")}</p>
                <p><b>Correo:</b> ${escapeHtml(c.email)}</p>
                <p><b>Teléfono:</b> ${escapeHtml(c.phone || "—")}</p>
                <p><b>Estado:</b> ${escapeHtml(c.status)}</p>
                <p><b>Creado:</b> ${fmtDate(c.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(c.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});
