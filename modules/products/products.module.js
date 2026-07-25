/** archivo:frontend/modules/products/products.module.js */
import { createCrudModule } from "../../core/crud.engine.js";

export const productsModule = createCrudModule({

entity:"products",

title:"Productos de Investigación",

fields:[

 {name:"id",label:"ID"},

 {
  name:"project_id",
  label:"Proyecto",
  type:"relation",
  relation:"projects",
  display:"title"
 },

 {
  name:"type",
  label:"Tipo",
  type:"select",
  options:[
   {value:"ARTICULO",label:"ARTÍCULO"},
   {value:"PONENCIA",label:"PONENCIA"},
   {value:"POSTER",label:"POSTER"},
   {value:"LIBRO",label:"LIBRO"},
   {value:"SOFTWARE",label:"SOFTWARE"},
   {value:"PROTOTIPO",label:"PROTOTIPO"}
  ]
 },

 {name:"title",label:"Título",type:"text"},

 {name:"year",label:"Año",type:"number"},

 {name:"url",label:"URL",type:"text"}

]

});