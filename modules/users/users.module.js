/* # archivo: /frontend/modules/users/users.module.js */
/* =========================================================
   #archivo: /frontend/modules/users/users.module.js
   ---------------------------------------------------------
   Módulo CRUD de gestión de usuarios usando CRUD Engine
   ========================================================= */

import { createCrudModule } from "../../core/crud.engine.js";

export const usersModule = createCrudModule({

entity: "users",
title: "Gestión de Usuarios",

fields: [

 { name:"id", label:"ID" },

 { name:"name", label:"Nombre", type:"text" },

 { name:"email", label:"Email", type:"text" },

 { name:"password", label:"Contraseña", type:"password" },

 {
  name:"role",
  label:"Rol",
  type:"select",
  options:[
   { value:"ADMIN_SISTEMA", label:"ADMIN_SISTEMA"},
   { value:"ADMINISTRATIVO", label:"ADMINISTRATIVO"},
   { value:"LIDER_SEMILLERO", label:"LIDER_SEMILLERO"},
   { value:"ESTUDIANTE", label:"ESTUDIANTE"}
  ]
 },

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   { value:"ACTIVO", label:"ACTIVO"},
   { value:"INACTIVO", label:"INACTIVO"}
  ]
 }

],

readonlyFor: ['ADMINISTRATIVO', 'LIDER_SEMILLERO']

});