/* #archivo: /frontend/modules/programs/programs.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const programsModule = createCrudModule({

entity:"programs",

title:"Gestión de Programas",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

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

]

});