/* archivo: frontend/modules/groups/groups.module.js */
import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const groupsModule = createCrudModule({

entity:"groups",

title:"Gestión de Grupos",

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

/* CU11-A5: actor principal es LIDER_SEMILLERO; ADMINISTRATIVO solo consulta
   (backend ya lo rechaza con 403 — esto evita el patrón "botón visible que
   da 403" prohibido en el proyecto). */
noCreateFor: ['ADMINISTRATIVO'],
noEditFor: ['ADMINISTRATIVO'],

/* CU11-A4: filtrar por estado (buscador de texto ya lo da DataTables) */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU11 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU11-A1: ver detalle */
actions: [
    { label: "Ver", class: "viewGroupBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewGroupBtn");
    if (!btn) return;
    const { group: g } = await apiFetch(`/groups/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(g.name),
        html: `
            <div style="text-align:left">
                <p><b>Código:</b> ${escapeHtml(g.code)}</p>
                <p><b>Estado:</b> ${escapeHtml(g.status)}</p>
                <p><b>Creado:</b> ${fmtDate(g.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(g.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});