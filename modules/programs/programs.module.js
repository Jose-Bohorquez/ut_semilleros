/* #archivo: /frontend/modules/programs/programs.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const programsModule = createCrudModule({

entity:"programs",

title:"Gestión de Programas",

fields:[

 {name:"id",label:"ID"},

 /* RF03 / RN08: código único (el servidor lo normaliza a mayúsculas) */
 {
  name:"code",
  label:"Código",
  type:"text",
  maxlength:20,
  uppercase:true,
  placeholder:"Ej: ISIS",
  hint:"Único. Letras, números, guion o guion bajo."
 },

 {name:"name",label:"Nombre",type:"text"},

 /* RF03: el tipo solo admite Pregrado o Posgrado */
 {
  name:"type",
  label:"Tipo",
  type:"select",
  options:[
   {value:"PREGRADO",label:"Pregrado"},
   {value:"POSGRADO",label:"Posgrado"}
  ]
 },

 {
  name:"faculty_id",
  label:"Facultad",
  type:"relation",
  relation:"faculties",
  display:"name"
 },

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

/* CU08-A4: filtrar por estado (buscador de texto ya lo da DataTables) */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU08 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU08-A1: ver detalle (fechas + facultad + semilleros asociados) */
actions: [
    { label: "Ver", class: "viewProgramBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewProgramBtn");
    if (!btn) return;
    const { program: p } = await apiFetch(`/programs/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(p.name),
        html: `
            <div style="text-align:left">
                <p><b>Código:</b> ${escapeHtml(p.code)}</p>
                <p><b>Tipo:</b> ${escapeHtml(p.type)}</p>
                <p><b>Facultad:</b> ${escapeHtml(p.faculty?.name || "—")}</p>
                <p><b>Estado:</b> ${escapeHtml(p.status)}</p>
                <p><b>Semilleros asociados:</b> ${escapeHtml(p.seedbeds_count)}</p>
                <p><b>Creado:</b> ${fmtDate(p.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(p.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});