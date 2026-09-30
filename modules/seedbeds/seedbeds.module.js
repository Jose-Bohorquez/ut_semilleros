/* #archivo: /frontend/modules/seedbeds/seedbeds.module.js */

import { createCrudModule } from "../../core/crud.engine.js";
import { seedbedMembersModule } from "../seedbed-members/seedbed-members.module.js";
import { apiFetch } from "../../services/api.service.js";
import { escapeHtml }      from "../../core/escape.js";

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
        <input type="text" class="objective-input" placeholder="Ej: Fomentar la investigación aplicada en..."
               value="${safe}" style="flex:1">
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

/* CU16 paso 2/3: filtro por estado. Facultad/CAT/Área requieren catálogo
   dinámico que el motor genérico de filtros (opciones estáticas) no
   soporta hoy — pendiente, documentado en CU16.md. */
filters: [
    { field: "status", label: "Estado", options: [
        { value: "ACTIVO", label: "ACTIVO" },
        { value: "INACTIVO", label: "INACTIVO" },
    ] },
],

pageLength: 15,

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
    return `
    ${concurrencyInput}
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
    for (const row of rows) {
        const content = row.querySelector(".objective-input")?.value.trim();
        if (!content) continue;

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
        }
        order++;
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
   Resultados aún no está vinculado a este módulo (pendiente, ver CU16.md).
   ========================================================= */

document.addEventListener("click", async function(e) {
    const btn = e.target.closest(".viewSeedbedBtn");
    if (!btn) return;

    const id = btn.dataset.id;
    try {
        const [seedbedData, objectivesData] = await Promise.all([
            apiFetch(`/seedbeds/${id}`),
            apiFetch("/objectives"),
        ]);
        const s = seedbedData.seedbed;
        const objectives = (objectivesData.objectives || []).filter(o => o.seedbed_id == id);
        const members = s.users || [];

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

                    <h4>Integrantes (${members.length})</h4>
                    ${members.length
                        ? `<ul>${members.map(m => `<li>${escapeHtml(m.name)} (${escapeHtml(m.pivot?.role || "")})</li>`).join("")}</ul>`
                        : "<p>Sin integrantes registrados.</p>"}
                </div>
            `,
        });
    } catch (err) {
        Swal.fire({ icon: "error", title: "No se pudo cargar el detalle", text: err.message });
    }
});
