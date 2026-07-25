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
  type: "text"
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

noCreateFor: ['ADMIN_SISTEMA'],

noEditFor: ['ADMIN_SISTEMA']

});