/* archivo: forntend/modules/faculties/faculties.module.js */
import { createCrudModule } from "../../core/crud.engine.js";
import { apiFetch }         from "../../services/api.service.js";
import { escapeHtml }       from "../../core/escape.js";

const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—";

export const facultiesModule = createCrudModule({

entity:"faculties",

title:"Gestión de Facultades",

/* CU07-CU10 A5: Líder y Administrativo solo consultan (la API ya responde 403 a
   sus escrituras); sin botones de crear, editar ni inactivar. Se usan
   noCreateFor/noEditFor y no readonlyFor para conservar la acción «Ver». */
noCreateFor: ["ADMINISTRATIVO", "LIDER_SEMILLERO"],

noEditFor: ["ADMINISTRATIVO", "LIDER_SEMILLERO"],

fields:[

 {name:"id",label:"ID"},

 /* RF02 / RN08: código único (el servidor lo normaliza a mayúsculas) */
 {
  name:"code",
  label:"Código",
  type:"text",
  maxlength:20,
  uppercase:true,
  placeholder:"Ej: IDEAD",
  hint:"Único. Letras, números, guion o guion bajo."
 },

 {name:"name",label:"Nombre",type:"text"},

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

/* CU07-A4: filtrar por estado. Buscador de texto (código/nombre) ya lo
   da DataTables. */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

/* CU07 flujo básico paso 2: "listado paginado (15 por página)" */
pageLength: 15,

/* CU07-A1: ver detalle (fechas + programas asociados) */
actions: [
    { label: "Ver", class: "viewFacultyBtn" },
],

});

document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".viewFacultyBtn");
    if (!btn) return;
    const { faculty: f } = await apiFetch(`/faculties/${btn.dataset.id}`);
    Swal.fire({
        title: escapeHtml(f.name),
        html: `
            <div style="text-align:left">
                <p><b>Código:</b> ${escapeHtml(f.code)}</p>
                <p><b>Estado:</b> ${escapeHtml(f.status)}</p>
                <p><b>Programas asociados:</b> ${escapeHtml(f.programs_count)}</p>
                <p><b>Creada:</b> ${fmtDate(f.created_at)}</p>
                <p><b>Última modificación:</b> ${fmtDate(f.updated_at)}</p>
            </div>`,
        confirmButtonText: "Volver",
    });
});