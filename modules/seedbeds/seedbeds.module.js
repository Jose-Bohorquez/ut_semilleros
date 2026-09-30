/* #archivo: /frontend/modules/seedbeds/seedbeds.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { seedbedMembersModule } from "../seedbed-members/seedbed-members.module.js";
import { apiFetch } from "../../services/api.service.js";
import { escapeHtml }      from "../../core/escape.js";
import { getUser }         from "../../services/storage.service.js";
import { navigateTo }      from "../../core/router.js";

/* CU19: límites del contenido de un objetivo (mismos que el servidor). */
const OBJECTIVE_MIN = 10;
const OBJECTIVE_MAX = 2000;

const isAdmin = () => getUser()?.role === "ADMIN_SISTEMA";

/* CU16 A1: id del líder responsable (fila LIDER de seedbed_user), o null. */
function leaderIdOf(seedbed) {
    const leader = (seedbed?.users || []).find(u => u.pivot?.role === "LIDER");
    return leader ? leader.id : null;
}

/* CU16 paso 2: opciones únicas {value,label} (por id) de una relación de los
   semilleros, ordenadas por nombre. */
function distinctOptions(records, pick) {
    const map = new Map();
    records.forEach(r => pick(r).forEach(item => {
        if (item && item.id != null && !map.has(String(item.id))) map.set(String(item.id), item.name || String(item.id));
    }));
    return Array.from(map, ([value, label]) => ({ value, label }))
        .sort((a, b) => a.label.localeCompare(b.label, "es"));
}

/* =========================================================
   OBJETIVOS ANIDADOS EN EL MISMO FORMULARIO DE SEMILLERO
   (Jose, 2026-07-28: pidió que no fuera una vista aparte, y luego pidió
   poder reordenarlos/editarlos/quitarlos de verdad, no solo agregar)
   ========================================================= */

/* =========================================================
   PESTAÑAS DEL FORMULARIO (CU13 paso 2/4/6: «Datos generales» /
   «Misión y visión» / «Justificación»). Se arma reorganizando el DOM
   después de montado el modal, sin tocar el motor genérico crud.engine.js.
   ========================================================= */

function setupSeedbedTabs() {
    const form = document.getElementById("crudForm-seedbeds");
    if (!form) return;

    const misionGroup = document.getElementById("field-mision")?.closest(".form-group");
    const visionGroup = document.getElementById("field-vision")?.closest(".form-group");
    const justGroup   = document.getElementById("field-justificacion")?.closest(".form-group");
    if (!misionGroup && !visionGroup && !justGroup) return;   /* campos no encontrados: no arma pestañas */

    const generalPane = document.createElement("div");
    generalPane.dataset.tabPane = "general";
    /* Todo lo que ya es hijo directo del form (menos misión/visión/justificación)
       pasa a la pestaña "Datos generales", en su orden original. */
    Array.from(form.children).forEach(child => {
        if (child === misionGroup || child === visionGroup || child === justGroup) return;
        if (child.tagName === "H3" || child.id === "formErrorBanner" || child.classList.contains("form-legend")) return;
        if (child.classList.contains("modal-actions")) return;
        generalPane.appendChild(child);
    });

    const misionPane = document.createElement("div");
    misionPane.dataset.tabPane = "mision";
    misionPane.style.display = "none";
    if (misionGroup) misionPane.appendChild(misionGroup);
    if (visionGroup) misionPane.appendChild(visionGroup);

    const justPane = document.createElement("div");
    justPane.dataset.tabPane = "justificacion";
    justPane.style.display = "none";
    if (justGroup) justPane.appendChild(justGroup);

    const nav = document.createElement("div");
    nav.className = "form-tabs-nav";
    nav.style.cssText = "display:flex;gap:4px;margin-bottom:16px;border-bottom:1px solid var(--color-border);flex-wrap:wrap";
    nav.innerHTML = `
        <button type="button" class="btn btn-ghost btn-sm form-tab-btn active" data-tab="general" style="border-bottom:2px solid var(--color-primary)">Datos generales</button>
        <button type="button" class="btn btn-ghost btn-sm form-tab-btn" data-tab="mision">Misión y visión</button>
        <button type="button" class="btn btn-ghost btn-sm form-tab-btn" data-tab="justificacion">Justificación</button>
    `;

    const modalActions = form.querySelector(".modal-actions");
    form.insertBefore(nav, modalActions);
    form.insertBefore(generalPane, modalActions);
    form.insertBefore(misionPane, modalActions);
    form.insertBefore(justPane, modalActions);

    const panes = { general: generalPane, mision: misionPane, justificacion: justPane };
    nav.addEventListener("click", e => {
        const btn = e.target.closest(".form-tab-btn");
        if (!btn) return;
        nav.querySelectorAll(".form-tab-btn").forEach(b => {
            b.classList.remove("active");
            b.style.borderBottom = "";
        });
        btn.classList.add("active");
        btn.style.borderBottom = "2px solid var(--color-primary)";
        Object.entries(panes).forEach(([key, pane]) => {
            pane.style.display = key === btn.dataset.tab ? "" : "none";
        });
    });
}

function objectiveRowHtml(id = "", content = "") {
    const safe = String(content).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    return `
    <div class="objective-row" data-objective-id="${escapeHtml(id)}" style="display:flex;gap:6px;margin-bottom:8px;align-items:flex-start">
        <div style="display:flex;flex-direction:column;gap:2px;flex-shrink:0">
            <button type="button" class="btn btn-ghost btn-sm moveObjectiveUpBtn" title="Subir">
                <i class="fas fa-chevron-up"></i>
            </button>
            <button type="button" class="btn btn-ghost btn-sm moveObjectiveDownBtn" title="Bajar">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div style="flex:1">
            <input type="text" class="objective-input" placeholder="Ej: Fomentar la investigación aplicada en..."
                   value="${safe}" maxlength="${OBJECTIVE_MAX}" style="width:100%">
            <span class="field-error-msg objective-error-msg" style="display:none;color:var(--color-error)"></span>
        </div>
        <button type="button" class="btn btn-ghost btn-sm removeObjectiveBtn" title="Quitar" style="flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>
    </div>`;
}

export const seedbedsModule = createCrudModule({

entity:"seedbeds",

title:"Gestión de Semilleros",

fields:[

 {name:"id",label:"ID"},

 /* CU13 / RN08: código único */
 {name:"code",label:"Código",type:"text",hint:"Único."},

 {name:"name",label:"Nombre",type:"text"},

 /* CU16 paso 2: facultad, líder e integrantes en el listado (calculados en
    el backend, no se editan aquí). */
 {name:"faculty_names",label:"Facultad",type:"text",readonly:true},

 {
  name:"description",
  label:"Descripción",
  type:"textarea",
  required:false
 },

 /* CU13 Ronda B: selección múltiple (antes un solo programa) */
 {
  name:"programs",
  label:"Programas",
  type:"relation-multi",
  relation:"programs",
  display:"name"
 },

 /* RF05: todo semillero tiene al menos un área. CU13 Ronda B: selección múltiple. */
 {
  name:"areas",
  label:"Áreas",
  type:"relation-multi",
  relation:"areas",
  display:"name"
 },

 {name:"leader_name",label:"Líder",type:"text",readonly:true},

 {name:"members_count",label:"Integrantes",type:"text",readonly:true},

 /* CU13: grupo, CAT y coordinador (opcionales) */
 {
  name:"group_id",
  label:"Grupo de investigación",
  type:"relation",
  relation:"groups",
  display:"name",
  required:false
 },

 {
  name:"cat_id",
  label:"CAT",
  type:"relation",
  relation:"cats",
  display:"name",
  required:false
 },

 {
  name:"coordinator_id",
  label:"Coordinador",
  type:"relation",
  relation:"coordinators",
  display:"name",
  required:false
 },

 /* CU13 paso 8: mínimo 10 caracteres (el servidor lo exige) */
 {name:"objetivo_general",label:"Objetivo general",type:"textarea",hint:"Mínimo 10 caracteres."},

 /* RN03 / RNF06: aprobación escrita del área administrativa */
 {name:"authorization_reference",label:"Referencia de aprobación",type:"text",hint:"Ej: Acta 045 de 2026 (RN03)."},

 {name:"mision",label:"Misión",type:"textarea",required:false},

 {name:"vision",label:"Visión",type:"textarea",required:false},

 {name:"justificacion",label:"Justificación",type:"textarea",required:false},

 {
  name:"status",
  label:"Estado",
  type:"select",
  options:[
   {value:"ACTIVO",label:"ACTIVO"},
   {value:"INACTIVO",label:"INACTIVO"}
  ]
 }

],

actions:[
 {
  label:"Ver",
  class:"viewSeedbedBtn"
 },
 {
  label:"INTEGRANTES",
  class:"membersBtn"
 }
],

/* CU16 paso 2/3 y A1: filtros combinables por facultad, CAT, área y estado
   (más el buscador de DataTables) y «Mis semilleros» para el Líder.
   Las opciones se arman con los semilleros cargados (solo valores que existen
   en el listado, sin pedir catálogos aparte que un rol podría no poder leer). */
filters: [
    /* A1: el Líder ve primero sus semilleros; puede volver a «Todos». El
       responsable se identifica por id (users[].pivot.role = LIDER), no por
       nombre, porque dos personas pueden llamarse igual. */
    { field: "mine", label: "Ver", roles: ["LIDER_SEMILLERO"], allLabel: "Todos los semilleros",
      options: [{ value: "mine", label: "Mis semilleros" }],
      test: (s) => leaderIdOf(s) !== null && String(leaderIdOf(s)) === String(getUser()?.id),
      defaultValue: () => "mine" },
    { field: "faculty", label: "Facultad",
      options: (recs) => distinctOptions(recs, s => (s.programs || []).map(p => p.faculty)),
      test: (s, v) => (s.programs || []).some(p => String(p.faculty?.id) === v) },
    { field: "cat", label: "CAT",
      options: (recs) => distinctOptions(recs, s => [s.cat]),
      test: (s, v) => String(s.cat?.id ?? s.cat_id ?? "") === v },
    { field: "area", label: "Área",
      options: (recs) => distinctOptions(recs, s => s.areas || []),
      test: (s, v) => (s.areas || []).some(a => String(a.id) === v) },
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

pageLength: 15,

/* CU16 E1: texto exacto cuando los filtros/búsqueda no devuelven nada. */
emptyFilterMessage: "No se encontraron semilleros con los filtros seleccionados",

/* CU16 A2: Administrativo consulta, no edita (alineado a la spec,
   2026-09-30 — antes tenía escritura por decisión previa del proyecto). */
noCreateFor: ['ESTUDIANTE', 'ADMINISTRATIVO'],

noEditFor: ['ESTUDIANTE', 'ADMINISTRATIVO'],

/* CU13-A1: "Guardar borrador" registra el semillero inactivo. Solo aplica
   al crear (un semillero ya existente se inactiva con el toggle de estado). */
draftOption: true,

/* CU13 Ronda B: Object.fromEntries(FormData) solo conserva el último
   valor seleccionado en un <select multiple> — hay que leer todas las
   opciones marcadas antes de enviar. */
beforeSave(data, form) {
    data.programs = Array.from(form.querySelector('[name="programs"]')?.selectedOptions || []).map(o => o.value);
    data.areas = Array.from(form.querySelector('[name="areas"]')?.selectedOptions || []).map(o => o.value);

    /* CU15-H1: al EDITAR el estado no se envía (el servidor lo rechaza); solo
       cambia con Activar/Inactivar. `expected_updated_at` solo existe al editar. */
    if (form.querySelector('[name="expected_updated_at"]')) delete data.status;

    /* CU14-A2: líder responsable, solo lo envía el Admin y solo si eligió uno. */
    if (!data.leader_id) delete data.leader_id;
},

/* CU15: al inactivar, informa cuántas solicitudes pendientes tiene el
   semillero (paso 2) y exige un motivo antes de confirmar (paso 3 / E2).
   Al activar no se pide nada extra (A1). */
async customToggleConfirm(record, status) {
    const isInactivating = status === "ACTIVO";
    const name = record?.name || "este semillero";

    if (!isInactivating) {
        const result = await Swal.fire({
            title: "¿Activar semillero?",
            html: `<strong>${escapeHtml(name)}</strong> volverá a ser visible en la PWA.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, activar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#22c55e",
            reverseButtons: true,
        });
        return result.isConfirmed ? { body: {} } : null;
    }

    const pending = record?.pending_requests_count ?? 0;

    const result = await Swal.fire({
        title: "¿Inactivar semillero?",
        html: `
            <p><strong>${escapeHtml(name)}</strong> dejará de ser visible en la PWA.</p>
            <p>${pending > 0
                ? `Tiene <strong>${pending}</strong> solicitud${pending === 1 ? "" : "es"} pendiente${pending === 1 ? "" : "s"}: se rechazará${pending === 1 ? "" : "n"} automáticamente con el motivo «Semillero inactivo».`
                : "No tiene solicitudes pendientes."}</p>
        `,
        icon: "warning",
        input: "textarea",
        inputLabel: "Motivo de la inactivación",
        inputPlaceholder: "Ej: Cierre temporal por vacaciones del semestre",
        inputValidator: value => !value || value.trim().length < 5
            ? "El motivo debe tener al menos 5 caracteres."
            : undefined,
        showCancelButton: true,
        confirmButtonText: "Sí, inactivar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#f59e0b",
        reverseButtons: true,
    });

    if (!result.isConfirmed) return null;
    return { body: { reason: result.value.trim() } };
},

/* CU15 paso 5: muestra cuántas solicitudes quedaron rechazadas. */
async onToggled(response) {
    const count = response?.rejected_requests_count ?? 0;
    if (count > 0) {
        await Swal.fire({
            icon: "info",
            title: "Solicitudes rechazadas",
            text: `Se rechazaron automáticamente ${count} solicitud${count === 1 ? "" : "es"} pendiente${count === 1 ? "" : "s"}.`,
        });
    }
},

/* ── Sección extra dentro del mismo modal: Objetivos ──────────────────── */
extraFormHtml(record) {
    /* CU14 E3: se envía junto al formulario la marca de tiempo que el
       actor tenía cargada, para detectar si otro usuario ya modificó el
       semillero mientras tanto. Solo aplica al editar. */
    const concurrencyInput = record
        ? `<input type="hidden" name="expected_updated_at" value="${escapeHtml(record.updated_at)}">`
        : "";
    /* CU14-A2: solo el Administrador asigna/reasigna al líder responsable.
       Las opciones se cargan en afterFormMount (GET /users). */
    const leaderSelect = isAdmin() ? `
    <div class="form-group">
        <label for="field-leader_id">Líder responsable <span class="optional-hint">(opcional)</span></label>
        <select id="field-leader_id" name="leader_id"><option value="">Cargando...</option></select>
        <span class="field-error-msg" id="err-leader_id"></span>
    </div>` : "";
    return `
    ${concurrencyInput}
    ${leaderSelect}
    <div class="form-group">
        <label>Objetivos <span class="optional-hint">(opcional, puedes agregar, reordenar y quitar varios)</span></label>
        <div id="objectivesRepeater"></div>
        <button type="button" id="addObjectiveBtn" class="btn btn-ghost btn-sm">
            <i class="fas fa-plus"></i> Agregar objetivo
        </button>
    </div>`;
},

/* ── Una vez el modal está en el DOM: precargar objetivos existentes
   (si se está editando) y activar los botones de la lista. ── */
async afterFormMount(record) {
    const repeater = document.getElementById("objectivesRepeater");
    if (!repeater) return;

    /* CU15-H1: al editar, el selector «Estado» no aplica (se cambia con
       Activar/Inactivar). Se oculta y se deshabilita para que no viaje. */
    if (record) {
        const statusSelect = document.getElementById("field-status");
        if (statusSelect) {
            statusSelect.disabled = true;
            statusSelect.closest(".form-group")?.setAttribute("style", "display:none");
        }
    }

    /* CU14-A2: opciones del líder responsable (solo Admin). */
    const leaderSelect = document.getElementById("field-leader_id");
    if (leaderSelect) {
        try {
            const data = await apiFetch("/users");
            const leaders = (data.users || []).filter(u => u.role === "LIDER_SEMILLERO" && u.status === "ACTIVO");
            const currentId = (record?.users || []).find(u => u.pivot?.role === "LIDER")?.id;
            leaderSelect.innerHTML = `<option value="">${currentId ? "Mantener el líder actual" : "Sin asignar"}</option>` +
                leaders.map(u => `<option value="${escapeHtml(u.id)}" ${u.id === currentId ? "selected" : ""}>${escapeHtml(u.name)}</option>`).join("");
        } catch {
            leaderSelect.innerHTML = `<option value="">No se pudo cargar la lista de líderes</option>`;
            leaderSelect.disabled = true;
        }
    }

    /* CU19-H1 / E1: valida los objetivos ANTES de que el formulario se envíe
       (y el modal se cierre). Este listener corre antes que el del motor
       (que está en <body>) y corta la propagación si algo no cumple. */
    document.getElementById("crudForm-seedbeds")?.addEventListener("submit", e => {
        let firstBad = null;
        repeater.querySelectorAll(".objective-row").forEach((row, i) => {
            const input = row.querySelector(".objective-input");
            const msg = row.querySelector(".objective-error-msg");
            const len = input.value.trim().length;
            let error = "";
            if (len > 0 && len < OBJECTIVE_MIN) error = `El objetivo debe tener al menos ${OBJECTIVE_MIN} caracteres.`;
            else if (len > OBJECTIVE_MAX) error = `El objetivo no puede superar los ${OBJECTIVE_MAX} caracteres.`;
            if (msg) { msg.textContent = error; msg.style.display = error ? "block" : "none"; }
            if (error && !firstBad) firstBad = input;
        });
        if (firstBad) {
            e.preventDefault();
            e.stopPropagation();
            firstBad.scrollIntoView({ behavior: "smooth", block: "center" });
            firstBad.focus();
        }
    });

    if (record) {
        try {
            const data = await apiFetch("/objectives");
            /* El backend ya devuelve ordenado por seedbed_id + order */
            const existing = (data.objectives || []).filter(o => o.seedbed_id === record.id);
            repeater.innerHTML = existing.map(o => objectiveRowHtml(o.id, o.content)).join("");
        } catch {
            /* si falla la carga, simplemente se empieza con la lista vacía */
        }
    }

    document.getElementById("addObjectiveBtn")?.addEventListener("click", () => {
        repeater.insertAdjacentHTML("beforeend", objectiveRowHtml());
    });

    repeater.addEventListener("click", async e => {

        const upBtn = e.target.closest(".moveObjectiveUpBtn");
        if (upBtn) {
            const row = upBtn.closest(".objective-row");
            const prev = row?.previousElementSibling;
            if (prev) row.parentNode.insertBefore(row, prev);
            return;
        }

        const downBtn = e.target.closest(".moveObjectiveDownBtn");
        if (downBtn) {
            const row = downBtn.closest(".objective-row");
            const next = row?.nextElementSibling;
            if (next) row.parentNode.insertBefore(next, row);
            return;
        }

        const removeBtn = e.target.closest(".removeObjectiveBtn");
        if (removeBtn) {
            const row = removeBtn.closest(".objective-row");
            const objectiveId = row?.dataset.objectiveId;

            /* Fila nueva sin guardar aún — se quita del DOM sin más. */
            if (!objectiveId) {
                row.remove();
                return;
            }

            /* Objetivo ya existente — confirmar antes de borrar de verdad. */
            const result = await Swal.fire({
                title: "¿Quitar este objetivo?",
                text: "Se eliminará permanentemente del semillero.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, quitar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#dc2626",
            });
            if (!result.isConfirmed) return;

            try {
                await apiFetch(`/objectives/${objectiveId}`, { method: "DELETE" });
                row.remove();
            } catch (err) {
                Swal.fire({ icon: "error", title: "No se pudo quitar", text: err.message });
            }
        }
    });

    setupSeedbedTabs();
},

/* ── Tras guardar el semillero: crear/actualizar sus objetivos, en el
   orden final en que hayan quedado las filas dentro del formulario. ── */
async onSaved(response, isEdit, editId) {
    const seedbedId = isEdit ? editId : response?.seedbed?.id;
    if (!seedbedId) return;

    const rows = document.querySelectorAll("#objectivesRepeater .objective-row");
    let order = 0;
    let position = 0;
    /* CU19-H1: los objetivos que el servidor rechace (403/422/red) se
       acumulan para avisar al usuario con su texto, en vez de descartarlos
       en silencio. */
    const failed = [];
    for (const row of rows) {
        const content = row.querySelector(".objective-input")?.value.trim();
        if (!content) continue;
        position++;

        const objectiveId = row.dataset.objectiveId;
        const payload = { seedbed_id: seedbedId, content, order };
        try {
            if (objectiveId) {
                await apiFetch(`/objectives/${objectiveId}`, {
                    method: "PUT",
                    body: JSON.stringify(payload),
                });
            } else {
                await apiFetch("/objectives", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
            }
        } catch (err) {
            console.warn("[Semilleros] No se pudo guardar un objetivo:", err.message);
            failed.push({ position, content, message: err.message || "Error desconocido" });
        }
        order++;
    }

    if (failed.length) {
        await Swal.fire({
            icon: "warning",
            title: "El semillero se guardó, pero no todos los objetivos",
            width: 650,
            html: `<div style="text-align:left;font-size:0.9rem">
                <p>Estos objetivos no se guardaron. Copia el texto y agrégalo de nuevo editando el semillero:</p>
                <ul>${failed.map(f => `<li><strong>Objetivo ${f.position}:</strong> ${escapeHtml(f.message)}<br>
                    <em>${escapeHtml(f.content)}</em></li>`).join("")}</ul>
            </div>`,
        });
    }
}

});



/* =========================================================
   EVENTO: VER INTEGRANTES
   ========================================================= */

document.addEventListener("click", async function(e) {
    const btn = e.target.closest(".membersBtn");
    if (btn) {
        seedbedMembersModule.init(btn.dataset.id);
    }
});

/* =========================================================
   CU16 paso 5/6: VER — vista consolidada de solo lectura
   (datos generales, misión/visión, justificación, objetivos, integrantes).
   Incluye la sección de solo lectura «Resultados» (CU16 paso 6 / CU20).
   ========================================================= */

document.addEventListener("click", async function(e) {
    const btn = e.target.closest(".viewSeedbedBtn");
    if (!btn) return;

    const id = btn.dataset.id;
    try {
        const [seedbedData, objectivesData, resultsData] = await Promise.all([
            apiFetch(`/seedbeds/${id}`),
            apiFetch("/objectives"),
            /* CU16 paso 6 / CU20: resultados del semillero. GET /results no
               admite filtro por semillero, se filtra aquí. Si el rol no puede
               listarlos (403) o falla, la sección queda vacía sin romper el Ver. */
            apiFetch("/results").catch(() => null),
        ]);
        const s = seedbedData.seedbed;
        const objectives = (objectivesData.objectives || []).filter(o => o.seedbed_id == id);
        const results = (resultsData?.results || [])
            .filter(r => r.seedbed_id == id)
            .sort((a, b) => String(b.result_date || "").localeCompare(String(a.result_date || "")));
        /* CU20 disparador: la gestión (agregar/editar/inactivar) vive en /results;
           el atajo es solo para quien puede escribir (Líder/Admin; RN06 lo valida el servidor). */
        const canManageResults = ["LIDER_SEMILLERO", "ADMIN_SISTEMA"].includes(getUser()?.role);
        /* CU16-H2: integrantes reales (seedbed_members activos) — nombre,
           programa y nivel, sin correo ni teléfono; el líder va aparte. */
        const members = s.active_members || [];
        const LEVELS = { PR: "Pregrado", PG: "Posgrado" };

        Swal.fire({
            title: escapeHtml(s.name),
            width: 700,
            html: `
                <div style="text-align:left;font-size:0.9rem">
                    <h4>Datos generales</h4>
                    <p><strong>Código:</strong> ${escapeHtml(s.code || "—")}</p>
                    <p><strong>Facultad:</strong> ${escapeHtml(s.faculty_names || "—")}</p>
                    <p><strong>Programas:</strong> ${escapeHtml((s.programs || []).map(p => p.name).join(", ") || "—")}</p>
                    <p><strong>Áreas:</strong> ${escapeHtml((s.areas || []).map(a => a.name).join(", ") || "—")}</p>
                    <p><strong>Líder responsable:</strong> ${escapeHtml(s.leader_name || "—")}</p>
                    <p><strong>Estado:</strong> ${escapeHtml(s.status)}</p>
                    <p><strong>Objetivo general:</strong> ${escapeHtml(s.objetivo_general || "—")}</p>

                    <h4>Misión y visión</h4>
                    <p><strong>Misión:</strong> ${escapeHtml(s.mision || "—")}</p>
                    <p><strong>Visión:</strong> ${escapeHtml(s.vision || "—")}</p>

                    <h4>Justificación</h4>
                    <p>${escapeHtml(s.justificacion || "—")}</p>

                    <h4>Objetivos</h4>
                    ${objectives.length
                        ? `<ul>${objectives.map(o => `<li>${escapeHtml(o.content)}</li>`).join("")}</ul>`
                        : "<p>Sin objetivos registrados.</p>"}

                    <h4>Resultados</h4>
                    ${results.length
                        ? `<ul>${results.map(r => `<li>${escapeHtml(r.content)}
                            <small style="color:var(--color-text-2)">(${escapeHtml(r.result_date || "sin fecha")}${r.status === "INACTIVO" ? " · inactivo" : ""})</small></li>`).join("")}</ul>`
                        : "<p>Sin resultados registrados.</p>"}

                    <h4>Integrantes (${members.length})</h4>
                    ${members.length
                        ? `<ul>${members.map(m => `<li>${escapeHtml(m.name)} — ${escapeHtml(m.program_name || "—")} · ${escapeHtml(LEVELS[m.level] || m.level || "—")}</li>`).join("")}</ul>`
                        : "<p>Sin integrantes registrados.</p>"}
                </div>
            `,
            /* Siempre hay un botón para cerrar: quien puede gestionar resultados ve
               «Gestionar resultados» + «Cerrar»; el resto (Administrativo) solo «Cerrar». */
            showConfirmButton: true,
            confirmButtonText: canManageResults ? "Gestionar resultados" : "Cerrar",
            showCancelButton: canManageResults,
            cancelButtonText: "Cerrar",
        }).then(r => { if (r.isConfirmed && canManageResults) navigateTo("/results"); });
    } catch (err) {
        Swal.fire({ icon: "error", title: "No se pudo cargar el detalle", text: err.message });
    }
});
