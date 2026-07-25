/*archivo: frontend/modules/coordinators/coordinators.module.js*/



import { createCrudModule } from "../../core/crud.engine.js";

export const coordinatorsModule = createCrudModule({

entity:"coordinators",

title:"Gestión de Coordinadores",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {name:"email",label:"Email",type:"email"},

 {name:"phone",label:"Teléfono",type:"text"},

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