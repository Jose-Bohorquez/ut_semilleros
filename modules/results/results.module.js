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
  type:"textarea",
  hint:"Entre 10 y 2000 caracteres."
 },

 {
  name:"result_date",
  label:"Fecha del resultado",
  type:"date",
  required:false
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

/* CU20: el actor secundario Administrativo es solo consulta (2026-09-30,
   mismo criterio ya aplicado a semilleros en CU16 y objetivos en CU19).
   Bug real corregido de paso: aquí se bloqueaba a ADMIN_SISTEMA en vez de
   permitirlo — la ruta de escritura tampoco lo tenía en el backend. */
noCreateFor: ['ESTUDIANTE', 'ADMINISTRATIVO'],

noEditFor: ['ESTUDIANTE', 'ADMINISTRATIVO']

});