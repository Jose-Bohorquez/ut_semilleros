/* archivo: frontend/modules/objectives/objectives.module.js */

import { createCrudModule } from "../../core/crud.engine.js";

export const objectivesModule = createCrudModule({

entity: "objectives",

title: "Gestión de Objetivos",

fields: [

 {
  name: "id",
  label: "ID"
 },

 {
  name: "seedbed_id",
  label: "Semillero",
  type: "relation",

  /* endpoint que se consulta */

  relation: "seedbeds",

  /* campo que se muestra */

  display: "name"
 },

 {
  name: "content",
  label: "Objetivo",
  type: "textarea",
  hint: "Entre 10 y 2000 caracteres."
 },

 {
  name: "status",
  label: "Estado",
  type: "select",
  options: [
   {value: "ACTIVO",  label: "ACTIVO"},
   {value: "INACTIVO", label: "INACTIVO"}
  ]
 }

],

/* CU19: el actor secundario Administrativo es solo consulta (2026-09-30,
   mismo criterio ya aplicado a semilleros en CU16). */
noCreateFor: ['ESTUDIANTE', 'ADMINISTRATIVO'],

noEditFor: ['ESTUDIANTE', 'ADMINISTRATIVO']

});