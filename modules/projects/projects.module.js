/* archivo: frontend/modules/projects/projects.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { projectMembersModule } from "../project-members/project-members.module.js";

export const projectsModule = createCrudModule({

entity:"projects",

title:"Gestión de Proyectos",

fields:[

 {name:"id",label:"ID"},

 {
  name:"seedbed_id",
  label:"Semillero",
  type:"relation",
  relation:"seedbeds",
  display:"name"
 },

 {name:"title",label:"Título",type:"text"},

 {name:"description",label:"Descripción",type:"text"},

 {name:"users_count",label:"Miembros"},

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"ACTIVO",label:"ACTIVO"},
   {value:"FINALIZADO",label:"FINALIZADO"},
   {value:"SUSPENDIDO",label:"SUSPENDIDO"}
  ]
 }

],

actions:[
 {
  label:"MIEMBROS",
  class:"projectMembersBtn"
 }
]

});



/* =========================================================
   EVENTO: VER MIEMBROS DEL PROYECTO
   ========================================================= */

document.addEventListener("click", async function(e) {
    const btn = e.target.closest(".projectMembersBtn");
    if (btn) {
        projectMembersModule.init(btn.dataset.id);
    }
});