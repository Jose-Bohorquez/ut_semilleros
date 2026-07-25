/* archiv: frontend/modules/audits/audits.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const auditsModule = createCrudModule({

entity:"audits",

title:"Registro de Auditoría",

/* IMPORTANTE */
readonly:true,

fields:[

 {name:"id",label:"ID"},

 {
  name:"user_id",
  label:"Usuario",
  type:"relation",
  relation:"users",
  display:"name"
 },

 {name:"action",label:"Acción"},

 {name:"table_name",label:"Tabla"},

 {name:"record_id",label:"Registro"},

 {name:"created_at",label:"Fecha"}

]

});