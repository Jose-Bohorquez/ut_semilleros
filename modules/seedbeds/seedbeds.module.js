/* #archivo: /frontend/modules/seedbeds/seedbeds.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { seedbedMembersModule } from "../seedbed-members/seedbed-members.module.js";


export const seedbedsModule = createCrudModule({

entity:"seedbeds",

title:"Gestión de Semilleros",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {
  name:"program_id",
  label:"Programa",
  type:"relation",
  relation:"programs",
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

],

actions:[
 {
  label:"INTEGRANTES",
  class:"membersBtn"
 }
],

noCreateFor: ['ADMIN_SISTEMA', 'ESTUDIANTE'],

noEditFor: ['ADMIN_SISTEMA', 'ESTUDIANTE']

});



/* =========================================================
   EVENTO: VER INTEGRANTES
   ========================================================= */

document.addEventListener("click", async function(e) {
    const btn = e.target.closest(".membersBtn");
    if (btn) {
        seedbedMembersModule.init(btn.dataset.id);
    }
});