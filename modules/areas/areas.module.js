/* archivo: frontend/modules/areas/areas.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const areasModule = createCrudModule({

entity:"areas",

title:"Gestión de Áreas",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {name:"code",label:"Código",type:"text"},

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