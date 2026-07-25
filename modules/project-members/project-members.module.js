/** archivo: frontend/modules/project-members/project-members.module.js */

import { apiFetch } from "../../services/api.service.js";
import { LayoutView } from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";


export const projectMembersModule = {

    async init(projectId){

        const data = await apiFetch(`/projects/${projectId}/members`);

        renderMembers(projectId,data.members);

    }

};



/* =========================================================
   RENDER TABLA
   ========================================================= */

async function renderMembers(projectId,members){

    const rows = members.map(member => {

        return `
        <tr>

            <td>${member.id}</td>
            <td>${member.name}</td>
            <td>${member.email}</td>
            <td>${member.pivot.role}</td>

            <td>

                <button 
                class="removeProjectMemberBtn"
                data-project="${projectId}"
                data-user="${member.id}">
                Eliminar
                </button>

            </td>

        </tr>
        `;

    }).join("");



    const content = `

    <h2>Miembros del Proyecto</h2>

    <button id="addProjectMemberBtn" data-project="${projectId}">
    Agregar miembro
    </button>

    <table id="projectMembersTable" class="display" style="width:100%">

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

}



/* =========================================================
   ELIMINAR MIEMBRO DEL PROYECTO
   ========================================================= */

document.addEventListener("click",async function(e){

 if(e.target.classList.contains("removeProjectMemberBtn")){

  const projectId = e.target.dataset.project;
  const userId = e.target.dataset.user;

  if(!confirm("¿Eliminar miembro del proyecto?")) return;

  try{

      await apiFetch(
        `/projects/${projectId}/members/${userId}`,
        { method:"DELETE" }
      );

      projectMembersModule.init(projectId);

  }catch(error){

      console.error(error);
      alert("Error eliminando miembro");

  }

 }

});



/* =========================================================
   AGREGAR MIEMBRO AL PROYECTO
   ========================================================= */

document.addEventListener("click", async function(e){

    if(e.target.id === "addProjectMemberBtn"){

        const projectId = e.target.dataset.project;

        const users = await apiFetch("/users");

        const options = users.users.map(u => `
            <option value="${u.id}">
                ${u.name} (${u.email})
            </option>
        `).join("");



        const modal = `

        <div id="crudModal">

            <div class="crudModalBox">

                <form id="addProjectMemberForm">

                    <h3>Agregar miembro al proyecto</h3>

                    <label>Usuario</label>

                    <select name="user_id" required>
                        ${options}
                    </select>


                    <label>Rol</label>

                    <select name="role" required>

                        <option value="INVESTIGADOR">INVESTIGADOR</option>
                        <option value="COINVESTIGADOR">COINVESTIGADOR</option>
                        <option value="ESTUDIANTE">ESTUDIANTE</option>

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



        document
        .getElementById("addProjectMemberForm")
        .addEventListener("submit",async function(ev){

            ev.preventDefault();

            const formData = new FormData(ev.target);
            const data = Object.fromEntries(formData.entries());

            try{

                await apiFetch(
                    `/projects/${projectId}/members`,
                    {
                        method:"POST",
                        body:JSON.stringify(data)
                    }
                );

                const modal = document.getElementById("crudModal");
                if(modal) modal.remove();

                projectMembersModule.init(projectId);

            }catch(error){

                console.error(error);

                alert(error.message || "No se pudo agregar el miembro");

            }

        });

    }

});



/* =========================================================
   CERRAR MODAL
   ========================================================= */

document.addEventListener("click",function(e){

    if(e.target.id === "closeModalBtn"){

        const modal = document.getElementById("crudModal");

        if(modal) modal.remove();

    }

});