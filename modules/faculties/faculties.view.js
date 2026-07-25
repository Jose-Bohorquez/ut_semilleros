/* #archivo: /frontend/modules/faculties/faculties.view.js */

export function FacultiesView(faculties = []) {

    const rows = faculties.map(f => `
        <tr>
            <td>${f.id}</td>
            <td>${f.name}</td>
            <td>${f.status}</td>
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