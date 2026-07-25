/* archiv: frontend/modules/results/results.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const resultsModule = createCrudModule({

entity:"results",

title:"Gestión de Resultados",

fields:[

 {name:"id",label:"ID"},

 {
  name:"seedbed_id",
  label:"Semillero",
  type:"relation",
  relation:"seedbeds",
  display:"name"
 },

 {
  name:"content",
  label:"Resultado",
  type:"text"
 },

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"ACTIVO",  label:"ACTIVO"},
   {value:"INACTIVO", label:"INACTIVO"}
  ]
 }

],

noCreateFor: ['ADMIN_SISTEMA'],

noEditFor: ['ADMIN_SISTEMA']

});