/* #archivo: /frontend/modules/faculties/faculties.service.js */

import { apiFetch } from "../../services/api.service.js";

export async function getFaculties() {

    try {

        const data = await apiFetch("/faculties");

        return data.faculties;

    } catch (error) {

        console.error("Error cargando facultades:", error);

        return [];
    }

}