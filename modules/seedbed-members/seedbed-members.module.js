/* =========================================================
   #archivo: /frontend/modules/seedbed-members/seedbed-members.module.js
   Gestión de integrantes de semilleros (CU05)
   ========================================================= */

import { apiFetch } from "../../services/api.service.js";
import { LayoutView } from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";

export const seedbedMembersModule = {

    async init(seedbedId){

        const data = await apiFetch(`/seedbeds/${seedbedId}/members`);

        renderMembers(seedbedId,data.members);

    }

};



async function renderMembers(seedbedId,members){

    const rows = members.map(member => {

        return `
        <tr>

            <td>${member.id}</td>
            <td>${member.name}</td>
            <td>${member.email}</td>
            <td>${member.pivot.role}</td>

            <td>

                <button 
                class="removeMemberBtn"
                data-seedbed="${seedbedId}"
                data-user="${member.id}">
                Eliminar
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

    <table id="membersTable" class="display" style="width:100%">

        <thead>

            <tr>

                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
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





document.addEventListener("click", async function(e){

    if(e.target.id === "addMemberBtn"){

        const seedbedId = e.target.dataset.seedbed;

        const users = await apiFetch("/users");

        const options = users.users.map(u => `
            <option value="${u.id}">
                ${u.name} (${u.email})
            </option>
        `).join("");

        const modal = `

        <div id="crudModal">

            <div class="crudModalBox">

                <form id="addMemberForm">

                    <h3>Agregar integrante</h3>

                    <label>Usuario</label>

                    <select name="user_id" required>
                        ${options}
                    </select>

                    <label>Rol</label>

                    <select name="role" required>
                        <option value="LIDER">LIDER</option>
                        <option value="INVESTIGADOR">INVESTIGADOR</option>
                        <option value="AUXILIAR">AUXILIAR</option>
                    </select>

                    <button type="submit">
                        Guardar
                    </button>

                    <button type="button" id="closeModalBtn">
                        Cancelar
                    </button>

                </form>

            </div>

        </div>

        `;

        document.body.insertAdjacentHTML("beforeend",modal);



        document.getElementById("addMemberForm")
        .addEventListener("submit",async function(ev){

            ev.preventDefault();

            const formData = new FormData(ev.target);

            const data = Object.fromEntries(formData.entries());

            await apiFetch(
                `/seedbeds/${seedbedId}/members`,
                {
                    method:"POST",
                    body:JSON.stringify(data)
                }
            );

            location.reload();

        });

    }

});