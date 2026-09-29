/* archivo: frontend/modules/cats/cats.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

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
pageLength: 15

});