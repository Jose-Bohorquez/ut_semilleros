/* #archivo: /frontend/modules/faculties/faculties.view.js */
import { escapeHtml }      from "../../core/escape.js";

export function FacultiesView(faculties = []) {

    const rows = faculties.map(f => `
        <tr>
            <td>${escapeHtml(f.id)}</td>
            <td>${escapeHtml(f.name)}</td>
            <td>${escapeHtml(f.status)}</td>
        </tr>
    `).join("");

    return `

        <h2>Gestión de Facultades</h2>

        <table border="1" cellpadding="8">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                ${rows}
            </tbody>

        </table>

    `;
}