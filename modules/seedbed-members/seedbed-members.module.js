/* =========================================================
   #archivo: /frontend/modules/seedbed-members/seedbed-members.module.js
   Gestión de integrantes de semilleros (RF12 / CU21)
   ========================================================= */

import { apiFetch } from "../../services/api.service.js";
import { LayoutView } from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }      from "../../core/escape.js";

const LEVEL_LABEL = { PR: "Pregrado", PG: "Posgrado" };

export const seedbedMembersModule = {

    async init(seedbedId){

        const [membersData, programsData] = await Promise.all([
            apiFetch(`/seedbeds/${seedbedId}/members`),
            apiFetch("/programs"),
        ]);

        renderMembers(seedbedId, membersData.members, (programsData.programs || []).filter(p => p.status === "ACTIVO"));

    }

};



async function renderMembers(seedbedId, members, programs){

    const rows = members.map(member => {

        return `
        <tr>

            <td data-label="ID">${escapeHtml(member.id)}</td>
            <td data-label="Nombre">${escapeHtml(member.name)}</td>
            <td data-label="Código">${escapeHtml(member.student_code)}</td>
            <td data-label="Programa">${escapeHtml(member.program?.name || "—")}</td>
            <td data-label="Nivel">${escapeHtml(LEVEL_LABEL[member.level] || member.level)}</td>
            <td data-label="Email">${escapeHtml(member.email)}</td>
            <td data-label="Estado">
                <span class="badge-pwa ${member.status === "ACTIVO" ? "badge-pwa-success" : ""}">${escapeHtml(member.status)}</span>
            </td>

            <td data-label="Acciones">

                <button
                class="editMemberBtn"
                data-member="${escapeHtml(member.id)}">
                Editar
                </button>

                <button
                class="toggleMemberBtn"
                data-seedbed="${seedbedId}"
                data-member="${escapeHtml(member.id)}"
                data-status="${escapeHtml(member.status)}">
                ${member.status === "ACTIVO" ? "Inactivar" : "Activar"}
                </button>

            </td>

        </tr>
        `;

    }).join("");



    const content = `

    <h2>Integrantes del Semillero</h2>

    <button id="addMemberBtn" data-seedbed="${seedbedId}">
    Agregar integrante
    </button>

    <table id="membersTable" class="display mobile-card-table" style="width:100%">

        <thead>

            <tr>

                <th>ID</th>
                <th>Nombre</th>
                <th>Código</th>
                <th>Programa</th>
                <th>Nivel</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Acciones</th>

            </tr>

        </thead>

        <tbody>

            ${rows}

        </tbody>

    </table>

    `;

    document.getElementById("app").innerHTML =
        LayoutView(content);

    initLayoutController();

    window.__seedbedMembersPrograms = programs;



    /* =====================================================
       ACTIVAR DATATABLE
    ===================================================== */

    setTimeout(()=>{

        const tableId = "#membersTable";

        if($.fn.DataTable.isDataTable(tableId)){
            $(tableId).DataTable().destroy();
        }

        $(tableId).DataTable({

            pageLength:10,

            dom:'Bfrtip',

            buttons:[
                {extend:'copy',text:'Copiar'},
                {extend:'excel',text:'Excel'},
                {extend:'pdf',text:'PDF'},
                {extend:'print',text:'Imprimir'}
            ],

            language:{
                search:"Buscar:",
                lengthMenu:"Mostrar _MENU_ registros",
                info:"Mostrando _START_ a _END_ de _TOTAL_ registros",
                paginate:{
                    next:"Siguiente",
                    previous:"Anterior"
                }
            }

        });

    },100);

}

function programOptions(selectedId){
    return (window.__seedbedMembersPrograms || []).map(p => `
        <option value="${escapeHtml(p.id)}" ${String(p.id) === String(selectedId) ? "selected" : ""}>
            ${escapeHtml(p.name)}
        </option>
    `).join("");
}

function memberFormFields(member = {}){
    return `
        <label>Nombre</label>
        <input type="text" name="name" required value="${escapeHtml(member.name || "")}">

        <label>Código estudiantil</label>
        <input type="text" name="student_code" required value="${escapeHtml(member.student_code || "")}">

        <label>Programa</label>
        <select name="program_id" required>
            ${programOptions(member.program_id)}
        </select>

        <label>Nivel</label>
        <select name="level" required>
            <option value="PR" ${member.level === "PR" ? "selected" : ""}>Pregrado</option>
            <option value="PG" ${member.level === "PG" ? "selected" : ""}>Posgrado</option>
        </select>

        <label>Correo</label>
        <input type="email" name="email" required value="${escapeHtml(member.email || "")}">

        <label>Dirección</label>
        <input type="text" name="address" value="${escapeHtml(member.address || "")}">

        <label>Teléfono</label>
        <input type="text" name="phone" value="${escapeHtml(member.phone || "")}">
    `;
}

function openMemberModal({ title, fields, onSubmit }){

    const modal = `
    <div id="crudModal">
        <div class="crudModalBox">
            <form id="memberForm">
                <h3>${escapeHtml(title)}</h3>
                ${fields}
                <div id="memberFormError" style="color:#c0392b;font-size:.85em"></div>
                <button type="submit">Guardar</button>
                <button type="button" id="closeModalBtn">Cancelar</button>
            </form>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modal);

    document.getElementById("memberForm").addEventListener("submit", async function(ev){
        ev.preventDefault();
        const formData = new FormData(ev.target);
        const data = Object.fromEntries(formData.entries());
        try {
            await onSubmit(data);
            document.getElementById("crudModal")?.remove();
        } catch (error) {
            const box = document.getElementById("memberFormError");
            if (box) box.textContent = error.message || "No se pudo guardar el integrante";
        }
    });
}



document.addEventListener("click", async function(e){

    if(e.target.id === "addMemberBtn"){

        const seedbedId = e.target.dataset.seedbed;

        openMemberModal({
            title: "Agregar integrante",
            fields: memberFormFields(),
            onSubmit: async (data) => {
                await apiFetch(`/seedbeds/${seedbedId}/members`, {
                    method: "POST",
                    body: JSON.stringify(data),
                });
                seedbedMembersModule.init(seedbedId);
            },
        });

    }

    if(e.target.classList.contains("editMemberBtn")){

        const seedbedId = document.getElementById("addMemberBtn")?.dataset.seedbed;
        const memberId = e.target.dataset.member;
        const data = await apiFetch(`/seedbeds/${seedbedId}/members`);
        const member = (data.members || []).find(m => String(m.id) === String(memberId));
        if (!member) return;

        openMemberModal({
            title: "Editar integrante",
            fields: memberFormFields(member),
            onSubmit: async (formData) => {
                await apiFetch(`/seedbeds/${seedbedId}/members/${memberId}`, {
                    method: "PUT",
                    body: JSON.stringify(formData),
                });
                seedbedMembersModule.init(seedbedId);
            },
        });

    }

    if(e.target.classList.contains("toggleMemberBtn")){

        const seedbedId = e.target.dataset.seedbed;
        const memberId  = e.target.dataset.member;
        const willInactivate = e.target.dataset.status === "ACTIVO";

        let reason = null;
        if (willInactivate) {
            const result = await Swal.fire({
                title: "Motivo de inactivación",
                input: "text",
                inputPlaceholder: "Retiro, grado, etc.",
                showCancelButton: true,
                confirmButtonText: "Inactivar",
                cancelButtonText: "Cancelar",
                inputValidator: (value) => (!value || value.trim().length < 5)
                    ? "Escribe al menos 5 caracteres" : undefined,
            });
            if (!result.isConfirmed) return;
            reason = result.value;
        } else if (!confirm("¿Activar de nuevo a este integrante?")) {
            return;
        }

        try {
            await apiFetch(`/seedbeds/${seedbedId}/members/${memberId}/toggle-status`, {
                method: "PUT",
                body: JSON.stringify(reason ? { reason } : {}),
            });
            seedbedMembersModule.init(seedbedId);
        } catch (error) {
            alert(error.message || "No se pudo actualizar el estado");
        }

    }

});



document.addEventListener("click",function(e){

    if(e.target.id === "closeModalBtn"){

        const modal = document.getElementById("crudModal");

        if(modal) modal.remove();

    }

});
