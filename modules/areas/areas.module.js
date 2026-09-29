/* archivo: frontend/modules/areas/areas.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const areasModule = createCrudModule({

entity:"areas",

title:"Gestión de Áreas",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {name:"code",label:"Código",type:"text"},

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

/* CU10-A4: filtrar por estado (buscador de texto ya lo da DataTables) */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU10 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU10-A1: ver detalle (fechas + semilleros/propuestas asociados) */
actions: [
    { label: "Ver", class: "viewAreaBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewAreaBtn");
    if (!btn) return;
    const { area: a } = await apiFetch(`/areas/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(a.name),
        html: `
            <div style="text-align:left">
                <p><b>Código:</b> ${escapeHtml(a.code)}</p>
                <p><b>Estado:</b> ${escapeHtml(a.status)}</p>
                <p><b>Semilleros asociados:</b> ${escapeHtml(a.seedbeds_count)}</p>
                <p><b>Propuestas asociadas:</b> ${escapeHtml(a.proposals_count)}</p>
                <p><b>Creada:</b> ${fmtDate(a.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(a.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});