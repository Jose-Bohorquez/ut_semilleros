/* =========================================================
   #archivo: /frontend/modules/requests/requests.module.js
   Gestionar solicitudes recibidas (RF10 / CU24)
   - Líder: ve y resuelve las solicitudes de SUS semilleros (RN06).
   - Admin de sistema: ve y resuelve todas.
   - Administrativo: solo consulta (A3), sin Aprobar/Rechazar.
   ========================================================= */

import { apiFetch }           from "../../services/api.service.js";
import { getUser }            from "../../services/storage.service.js";
import { LayoutView }         from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }         from "../../core/escape.js";

const STATUS_BADGE = {
    PENDIENTE: "badge-pwa-warning",
    APROBADA:  "badge-pwa-success",
    RECHAZADA: "badge-pwa-error",
};

const WRITE_ROLES = ["LIDER_SEMILLERO", "ADMIN_SISTEMA"];

/* Filtros vigentes: por defecto solo las pendientes (CU24 paso 2). */
let filters = { status: "PENDIENTE", seedbed_id: "", from: "", to: "" };

const canResolve = () => WRITE_ROLES.includes(getUser()?.role);

const fmtDate = (iso) => iso
    ? new Date(iso).toLocaleDateString("es-CO", { year: "numeric", month: "short", day: "2-digit" })
    : "—";

export const requestsModule = {

    async init(){

        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

        let data;
        try {
            data = await apiFetch(`/requests?${params.toString()}`);
        } catch (error) {
            renderShell(`<p class="empty-state">${escapeHtml(error.message || "No se pudieron cargar las solicitudes")}</p>`);
            return;
        }

        renderRequests(data.requests || [], data.seedbeds || []);
    }

};



function renderShell(content){
    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
}

function statusBadge(status){
    return `<span class="badge-pwa ${STATUS_BADGE[status] || "badge-pwa-neutral"}">${escapeHtml(status)}</span>`;
}

function renderRequests(requests, seedbeds){

    const rows = requests.map(r => `
        <tr>
            <td data-label="ID">${escapeHtml(r.id)}</td>
            <td data-label="Semillero">${escapeHtml(r.seedbed?.name || "—")}</td>
            <td data-label="Estudiante">${escapeHtml(r.user?.name || "—")}</td>
            <td data-label="Programa">${escapeHtml(r.program?.name || "—")}</td>
            <td data-label="Fecha" data-order="${escapeHtml(r.created_at || "")}">${escapeHtml(fmtDate(r.created_at))}</td>
            <td data-label="Estado">${statusBadge(r.status)}</td>
            <td data-label="Acciones">
                <button class="viewRequestBtn" data-request="${escapeHtml(r.id)}">Ver</button>
            </td>
        </tr>
    `).join("");

    const seedbedOptions = seedbeds.map(s => `
        <option value="${escapeHtml(s.id)}" ${String(s.id) === String(filters.seedbed_id) ? "selected" : ""}>
            ${escapeHtml(s.name)}
        </option>
    `).join("");

    const statusOptions = [
        ["", "Todos"], ["PENDIENTE", "Pendientes"], ["APROBADA", "Aprobadas"], ["RECHAZADA", "Rechazadas"],
    ].map(([v, l]) => `<option value="${v}" ${v === filters.status ? "selected" : ""}>${l}</option>`).join("");

    const readOnlyNote = canResolve() ? "" : `
        <p style="color:var(--color-text-muted);font-size:.9em">
            Vista de consulta: solo el líder del semillero puede aprobar o rechazar solicitudes.
        </p>`;

    const content = `

    <h2>Solicitudes recibidas</h2>

    ${readOnlyNote}

    <form id="requestFilters" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:1rem">

        <label>Semillero<br>
            <select name="seedbed_id">
                <option value="">Todos</option>
                ${seedbedOptions}
            </select>
        </label>

        <label>Estado<br>
            <select name="status">${statusOptions}</select>
        </label>

        <label>Desde<br>
            <input type="date" name="from" value="${escapeHtml(filters.from)}">
        </label>

        <label>Hasta<br>
            <input type="date" name="to" value="${escapeHtml(filters.to)}">
        </label>

        <button type="submit">Filtrar</button>
        <button type="button" id="clearRequestFilters">Limpiar</button>

    </form>

    <table id="requestsTable" class="display mobile-card-table" style="width:100%">

        <thead>
            <tr>
                <th>ID</th>
                <th>Semillero</th>
                <th>Estudiante</th>
                <th>Programa</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            ${rows}
        </tbody>

    </table>

    `;

    renderShell(content);

    document.getElementById("requestFilters").addEventListener("submit", (ev) => {
        ev.preventDefault();
        const f = Object.fromEntries(new FormData(ev.target).entries());
        filters = { status: f.status, seedbed_id: f.seedbed_id, from: f.from, to: f.to };
        requestsModule.init();
    });

    document.getElementById("clearRequestFilters").addEventListener("click", () => {
        filters = { status: "PENDIENTE", seedbed_id: "", from: "", to: "" };
        requestsModule.init();
    });

    document.querySelectorAll(".viewRequestBtn").forEach(btn => {
        btn.addEventListener("click", () => openRequest(btn.dataset.request));
    });

    /* DataTable: mismas opciones que el resto de tablas (CU21). */
    setTimeout(() => {

        const tableId = "#requestsTable";

        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }

        $(tableId).DataTable({

            pageLength: 10,

            order: [[0, "desc"]],

            dom: 'Bfrtip',

            buttons: [
                {extend:'copy',text:'Copiar'},
                {extend:'excel',text:'Excel'},
                {extend:'pdf',text:'PDF'},
                {extend:'print',text:'Imprimir'}
            ],

            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                emptyTable: "No hay solicitudes con estos filtros",
                paginate: { next: "Siguiente", previous: "Anterior" }
            }

        });

    }, 100);

}



/* =========================================================
   Detalle + Aprobar / Rechazar (pasos 3-6, A1)
   ========================================================= */

async function openRequest(id){

    let req;
    try {
        req = (await apiFetch(`/requests/${id}`)).request;
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo abrir la solicitud", text: error.message || "" });
        return;
    }

    const pending  = req.status === "PENDIENTE";
    const resolver = pending && canResolve();

    const response = req.reason ? `
        <p><b>Respuesta:</b><br>${escapeHtml(req.reason)}</p>` : "";

    const result = await Swal.fire({
        title: `Solicitud #${escapeHtml(req.id)}`,
        html: `
            <div style="text-align:left;line-height:1.5">
                <p><b>Semillero:</b> ${escapeHtml(req.seedbed?.name || "—")}</p>
                <p><b>Estudiante:</b> ${escapeHtml(req.user?.name || "—")}<br>
                   <b>Correo:</b> ${escapeHtml(req.user?.email || "—")}<br>
                   <b>Teléfono:</b> ${escapeHtml(req.phone || "—")}<br>
                   <b>Programa:</b> ${escapeHtml(req.program?.name || "—")}</p>
                <p><b>Fecha:</b> ${escapeHtml(fmtDate(req.created_at))} · ${statusBadge(req.status)}</p>
                <p><b>Mensaje:</b><br>${escapeHtml(req.message || "—")}</p>
                ${response}
            </div>`,
        showConfirmButton: resolver,
        confirmButtonText: "Aprobar",
        confirmButtonColor: "#16a34a",
        showDenyButton: resolver,
        denyButtonText: "Rechazar",
        showCancelButton: true,
        cancelButtonText: "Cerrar",
    });

    if (result.isConfirmed) await approve(req);
    else if (result.isDenied) await reject(req);
}

async function resolve(id, body){
    return apiFetch(`/requests/${id}/update-status`, {
        method: "PUT",
        body: JSON.stringify(body),
    });
}

/* Paso 5: la respuesta al estudiante es opcional al aprobar. */
async function approve(req){

    const answer = await Swal.fire({
        title: "Aprobar solicitud",
        input: "textarea",
        inputLabel: "Respuesta para el estudiante (opcional)",
        inputAttributes: { maxlength: 1000 },
        showCancelButton: true,
        confirmButtonText: "Aprobar",
        cancelButtonText: "Cancelar",
        inputValidator: (v) => (v && v.trim().length > 0 && v.trim().length < 5)
            ? "Escribe al menos 5 caracteres o deja el campo vacío" : undefined,
    });
    if (!answer.isConfirmed) return;

    try {
        const body = { status: "APROBADA" };
        if (answer.value && answer.value.trim()) body.reason = answer.value.trim();
        await resolve(req.id, body);
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo aprobar", text: error.message || "" });
        await requestsModule.init();
        return;
    }

    await requestsModule.init();
    await offerMembership(req);
}

/* A1 / E1: el motivo es obligatorio para rechazar. */
async function reject(req){

    const answer = await Swal.fire({
        title: "Rechazar solicitud",
        input: "textarea",
        inputLabel: "Motivo del rechazo (obligatorio)",
        inputAttributes: { maxlength: 1000 },
        showCancelButton: true,
        confirmButtonText: "Rechazar",
        confirmButtonColor: "#dc2626",
        cancelButtonText: "Cancelar",
        inputValidator: (v) => (!v || v.trim().length < 5)
            ? "Escribe el motivo (mínimo 5 caracteres)" : undefined,
    });
    if (!answer.isConfirmed) return;

    try {
        await resolve(req.id, { status: "RECHAZADA", reason: answer.value.trim() });
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo rechazar", text: error.message || "" });
    }

    await requestsModule.init();
}



/* =========================================================
   Paso 7: ofrecer registrar al estudiante como integrante
   (punto de extensión «Aprobación» → CU21)
   ========================================================= */

async function offerMembership(req){

    const ask = await Swal.fire({
        icon: "success",
        title: "Solicitud aprobada",
        text: "¿Quieres registrar ahora al estudiante como integrante del semillero?",
        showCancelButton: true,
        confirmButtonText: "Registrar integrante",
        cancelButtonText: "Más tarde",
    });
    if (!ask.isConfirmed) return;

    await Swal.fire({
        title: "Registrar integrante",
        html: `
            <div style="text-align:left">
                <label>Nombre</label>
                <input id="mbName" class="swal2-input" value="${escapeHtml(req.user?.name || "")}">
                <label>Código estudiantil</label>
                <input id="mbCode" class="swal2-input" placeholder="Código del estudiante">
                <label>Programa</label>
                <select id="mbProgram" class="swal2-select" style="display:flex;width:100%">
                    <option value="${escapeHtml(req.program_id || "")}">${escapeHtml(req.program?.name || "—")}</option>
                </select>
                <label>Nivel</label>
                <select id="mbLevel" class="swal2-select" style="display:flex;width:100%">
                    <option value="PR">Pregrado</option>
                    <option value="PG">Posgrado</option>
                </select>
                <label>Correo</label>
                <input id="mbEmail" type="email" class="swal2-input" value="${escapeHtml(req.user?.email || "")}">
                <label>Teléfono</label>
                <input id="mbPhone" class="swal2-input" value="${escapeHtml(req.phone || "")}">
            </div>`,
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        preConfirm: async () => {
            const val = (id) => document.getElementById(id).value.trim();
            if (!val("mbCode")) {
                Swal.showValidationMessage("El código estudiantil es obligatorio");
                return false;
            }
            try {
                await apiFetch(`/seedbeds/${req.seedbed_id}/members`, {
                    method: "POST",
                    body: JSON.stringify({
                        user_id:      req.user_id,
                        name:         val("mbName"),
                        student_code: val("mbCode"),
                        program_id:   val("mbProgram"),
                        level:        val("mbLevel"),
                        email:        val("mbEmail"),
                        phone:        val("mbPhone") || null,
                    }),
                });
            } catch (error) {
                Swal.showValidationMessage(error.message || "No se pudo registrar al integrante");
                return false;
            }
            return true;
        },
    }).then(r => {
        if (r.isConfirmed) Swal.fire({ icon: "success", title: "Integrante registrado", timer: 1800, showConfirmButton: false });
    });
}
