/* archivo: frontend/modules/requests/requests.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const requestsModule = createCrudModule({

entity:"requests",

title:"Gestión de Solicitudes",

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
  name:"seedbed_id",
  label:"Semillero",
  type:"relation",
  relation:"seedbeds",
  display:"name"
 },

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

readonlyFor: ['ESTUDIANTE'],

noCreateFor: ['ADMIN_SISTEMA']

});