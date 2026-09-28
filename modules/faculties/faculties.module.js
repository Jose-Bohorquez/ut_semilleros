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

]

});