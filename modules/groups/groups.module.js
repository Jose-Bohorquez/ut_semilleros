/* archivo: frontend/modules/groups/groups.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

export const groupsModule = createCrudModule({

entity:"groups",

title:"Gestión de Grupos",

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