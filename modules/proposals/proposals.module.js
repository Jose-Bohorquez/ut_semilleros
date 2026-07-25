/* archivo:frontend/modules/proposals/proposals.module.js */


import { createCrudModule } from "../../core/crud.engine.js";

export const proposalsModule = createCrudModule({

entity:"proposals",

title:"Gestión de Propuestas",

fields:[

 {name:"id",label:"ID"},

 {
  name:"user_id",
  label:"Usuario",
  type:"relation",
  relation:"users",
  display:"name"
 },

 {name:"title",label:"Título",type:"text"},

 {name:"description",label:"Descripción",type:"text"},

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