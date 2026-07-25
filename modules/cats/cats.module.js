/* archivo: frontend/modules/cats/cats.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

export const catsModule = createCrudModule({

entity:"cats",

title:"Gestión de CAT",

fields:[

 {name:"id",label:"ID"},

 {name:"name",label:"Nombre",type:"text"},

 {name:"code",label:"Código",type:"text"},

 {name:"address",label:"Dirección",type:"text"},

 {name:"city",label:"Ciudad",type:"text"},

 {name:"phone1",label:"Teléfono 1",type:"text"},

 {name:"phone2",label:"Teléfono 2",type:"text"},

 {name:"phone3",label:"Teléfono 3",type:"text"},

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"ACTIVO",label:"ACTIVO"},
   {value:"INACTIVO",label:"INACTIVO"}
  ]
 }

]

});