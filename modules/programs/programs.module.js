/* #archivo: /frontend/modules/programs/programs.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

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
pageLength: 15

});