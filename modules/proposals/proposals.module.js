/* archivo:frontend/modules/proposals/proposals.module.js */


import { createCrudModule } from "../../core/crud.engine.js";

export const proposalsModule = createCrudModule({

entity:"proposals",

title:"Gestión de Propuestas",

fields:[

 {name:"id",label:"ID"},

 {
  name:"user_id",
  label:"Usuario",
  type:"relation",
  relation:"users",
  display:"name"
 },

 {
  name:"program_id",
  label:"Programa",
  type:"relation",
  relation:"programs",
  display:"name"
 },

 /* RF05 / CU25: toda propuesta tiene al menos un área, selección múltiple
    (antes area_id único). Mismo patrón que programas/áreas de semilleros
    (CU13 Ronda B). */
 {
  name:"areas",
  label:"Áreas",
  type:"relation-multi",
  relation:"areas",
  display:"name"
 },

 {name:"title",label:"Título",type:"text"},

 {name:"description",label:"Descripción",type:"textarea",hint:"Entre 20 y 2000 caracteres."},

 {name:"phone",label:"Teléfono",type:"text",required:false},

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"PENDIENTE",label:"PENDIENTE"},
   {value:"APROBADA",label:"APROBADA"},
   {value:"RECHAZADA",label:"RECHAZADA"}
  ]
 }

],

/* CU13 Ronda B / CU25: Object.fromEntries(FormData) solo conserva el último
   valor seleccionado en un <select multiple> — hay que leer todas las
   opciones marcadas antes de enviar. */
beforeSave(data, form) {
    data.areas = Array.from(form.querySelector('[name="areas"]')?.selectedOptions || []).map(o => o.value);
},

readonlyFor: ['ESTUDIANTE'],

noCreateFor: ['ADMIN_SISTEMA']

});
