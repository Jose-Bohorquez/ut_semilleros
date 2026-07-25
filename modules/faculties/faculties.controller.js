/* #archivo: /frontend/modules/faculties/faculties.controller.js */

import { getFaculties } from "./faculties.service.js";
import { FacultiesView } from "./faculties.view.js";
import { LayoutView } from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";

export async function initFacultiesController() {

    const faculties = await getFaculties();

    const content = FacultiesView(faculties);

    document.getElementById("app").innerHTML = LayoutView(content);

    initLayoutController();

}