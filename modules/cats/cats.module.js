/* archivo: frontend/modules/cats/cats.module.js */
import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const catsModule = createCrudModule({

entity:"cats",

title:"Gestión de CAT",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 /* RF04 / RN08: código único (el servidor lo normaliza a mayúsculas) */
 {name:"code",label:"Código",type:"text",hint:"Único. Ej: CAT-BGA"},

 {name:"address",label:"Dirección",type:"text",required:false},

 {name:"city",label:"Ciudad",type:"text",required:false},

 {name:"email",label:"Correo",type:"email",required:false},

 /* Ninguno de los 3 es obligatorio por sí solo, pero se exige al menos uno
    (el servidor lo valida; aquí solo se explica en el formulario). */
 {name:"phone1",label:"Teléfono principal",type:"text",required:false,
   hint:"Se exige al menos un teléfono entre los 3 campos."},

 {name:"phone2",label:"Teléfono 2",type:"text",required:false},

 {name:"phone3",label:"Teléfono 3",type:"text",required:false},

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

/* CU09-A4: filtrar por estado (buscador de texto ya lo da DataTables) */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU09 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU09-A1: ver detalle (fechas) */
actions: [
    { label: "Ver", class: "viewCatBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewCatBtn");
    if (!btn) return;
    const { cat: c } = await apiFetch(`/cats/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(c.name),
        html: `
            <div style="text-align:left">
                <p><b>Código:</b> ${escapeHtml(c.code)}</p>
                <p><b>Dirección:</b> ${escapeHtml(c.address || "—")}</p>
                <p><b>Ciudad:</b> ${escapeHtml(c.city || "—")}</p>
                <p><b>Correo:</b> ${escapeHtml(c.email || "—")}</p>
                <p><b>Teléfonos:</b> ${[c.phone1, c.phone2, c.phone3].filter(Boolean).map(escapeHtml).join(" · ") || "—"}</p>
                <p><b>Estado:</b> ${escapeHtml(c.status)}</p>
                <p><b>Creado:</b> ${fmtDate(c.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(c.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});