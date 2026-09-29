/* archivo: forntend/modules/faculties/faculties.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

export const facultiesModule = createCrudModule({

entity:"faculties",

title:"Gestión de Facultades",

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
pageLength: 15

});