/* archivo: forntend/modules/faculties/faculties.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

export const facultiesModule = createCrudModule({

entity:"faculties",

title:"Gestión de Facultades",

fields:[

 {name:"id",label:"ID"},

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