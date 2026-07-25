/* =========================================================
   #archivo: /frontend/core/crud.engine.js
   Motor CRUD reutilizable optimizado para SPA
   ========================================================= */

import { apiFetch }            from "../services/api.service.js";
import { LayoutView }           from "../layout/layout.view.js";
import { initLayoutController } from "../layout/layout.controller.js";
import { getUser }              from "../services/storage.service.js";

export function createCrudModule(config) {

    const entity  = config.entity;
    const title   = config.title;
    const fields  = config.fields;

    let currentEditId = null;
    let recordsCache  = [];
    let eventsBound   = false;
    let submitting    = false;

    /* Auto-detect toggle-status support:
       entity supports it when it has a status field with ACTIVO/INACTIVO options */
    const hasToggle = !config.readonly && fields.some(f =>
        f.name === "status" && f.options?.some(o => o.value === "ACTIVO")
    );


    /* =====================================================
       INIT
    ===================================================== */

    async function init() {
        /* ESTUDIANTE solo accede a sus propios registros en estos módulos */
        const userRole = getUser()?.role || "";
        let endpoint   = `/${entity}`;
        if (userRole === "ESTUDIANTE") {
            if (entity === "requests")  endpoint = "/requests/my";
            if (entity === "proposals") endpoint = "/proposals/my";
        }

        const data   = await apiFetch(endpoint);
        recordsCache = data[entity] || [];
        renderTable(recordsCache);
        bindEvents();
    }


    /* =====================================================
       STATUS BADGE HELPER
    ===================================================== */

    function statusBadge(value) {
        if (!value) return "";
        const map = {
            ACTIVO:    '<span class="badge badge-active">ACTIVO</span>',
            INACTIVO:  '<span class="badge badge-inactive">INACTIVO</span>',
            PENDIENTE: '<span class="badge badge-pending">PENDIENTE</span>',
            APROBADA:  '<span class="badge badge-approved">APROBADA</span>',
            RECHAZADA: '<span class="badge badge-rejected">RECHAZADA</span>',
        };
        return map[value] || `<span class="badge">${value}</span>`;
    }


    /* =====================================================
       RENDER TABLA
    ===================================================== */

    function renderTable(records) {

        /* ── Role-based access control ─────────────────────────────────── */
        const userRole = getUser()?.role || "";

        if (config.hiddenFor?.includes(userRole)) {
            document.getElementById("app").innerHTML = LayoutView(`
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Sin acceso</h3>
                <p>No tienes permisos para ver este módulo. Contacta al administrador.</p>
            </div>`);
            initLayoutController();
            return;
        }

        const isReadonly  = config.readonly || config.readonlyFor?.includes(userRole) || false;
        const noCreate    = isReadonly || config.noCreateFor?.includes(userRole) || false;
        const noEdit      = isReadonly || config.noEditFor?.includes(userRole)   || false;
        /* ────────────────────────────────────────────────────────────────── */

        const headers = fields.map(f => `<th>${f.label}</th>`).join("");

        /* Empty state */
        if (records.length === 0) {
            const content = `
            <div class="table-toolbar">
                <h2>${title}</h2>
                ${!noCreate ? `
                <button class="btn btn-primary" id="createBtn-${entity}">
                    <i class="fas fa-plus"></i>
                    Crear ${title.split(" ").pop()}
                </button>` : ""}
            </div>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>Sin registros</h3>
                <p>No hay ${title.toLowerCase()} registrados. Crea el primero para comenzar.</p>
                ${!noCreate ? `
                <button class="btn btn-primary" id="createBtn-${entity}-empty">
                    <i class="fas fa-plus"></i>
                    Crear primer registro
                </button>` : ""}
            </div>`;

            document.getElementById("app").innerHTML = LayoutView(content);
            initLayoutController();

            document.getElementById(`createBtn-${entity}-empty`)
                ?.addEventListener("click", () => renderForm());

            return;
        }

        /* Show actions column when at least one action type is available */
        const showActionsCol = !noEdit || (!isReadonly && config.actions?.length > 0);

        /* Rows */
        const rows = records.map(record => {

            const cols = fields.map(f => {

                if (f.type === "relation") {
                    const rel = record[f.relation.slice(0, -1)];
                    return `<td data-label="${f.label}">${rel ? rel[f.display] : (record[f.name] ?? "")}</td>`;
                }

                const val = record[f.name] ?? "";

                /* Render status as badge */
                if (f.name === "status") {
                    return `<td data-label="${f.label}">${statusBadge(val)}</td>`;
                }

                return `<td data-label="${f.label}">${val}</td>`;

            }).join("");

            /* Action buttons */
            let actions = "";

            if (!noEdit) {

                actions += `
                <button class="btn btn-sm btn-secondary editBtn-${entity}" data-id="${record.id}" title="Editar">
                    <i class="fas fa-edit"></i> Editar
                </button>`;

                if (hasToggle) {
                    const isActive = record.status === "ACTIVO";
                    actions += `
                    <button class="btn btn-sm ${isActive ? "btn-warning" : "btn-success"} toggleBtn-${entity}"
                        data-id="${record.id}" data-status="${record.status ?? ""}"
                        title="${isActive ? "Inactivar" : "Activar"}">
                        <i class="fas fa-${isActive ? "toggle-on" : "toggle-off"}"></i>
                        ${isActive ? "Inactivar" : "Activar"}
                    </button>`;
                }
            }

            if (!isReadonly && config.actions) {
                config.actions.forEach(action => {
                    actions += `
                    <button class="btn btn-sm btn-secondary ${action.class}" data-id="${record.id}">
                        <i class="fas fa-users"></i> ${action.label}
                    </button>`;
                });
            }

            return `
            <tr>
                ${cols}
                ${showActionsCol ? `<td class="actions-col" data-label="Acciones"><div class="actions-cell">${actions}</div></td>` : ""}
            </tr>`;

        }).join("");

        const content = `
        <div class="table-toolbar">
            <div>
                <h2>${title}</h2>
                <span class="record-count">
                    <i class="fas fa-list" style="margin-right:4px"></i>
                    ${records.length} registro${records.length !== 1 ? "s" : ""}
                </span>
            </div>
            ${!noCreate ? `
            <button class="btn btn-primary" id="createBtn-${entity}">
                <i class="fas fa-plus"></i>
                Crear ${title.split(" ").pop()}
            </button>` : ""}
        </div>

        <table id="datatable-${entity}" class="display mobile-card-table" style="width:100%">
            <thead>
                <tr>
                    ${headers}
                    ${showActionsCol ? `<th class="actions-col">Acciones</th>` : ""}
                </tr>
            </thead>
            <tbody>
                ${rows}
            </tbody>
        </table>`;

        document.getElementById("app").innerHTML = LayoutView(content);
        initLayoutController();

        setTimeout(() => {
            const tableId = `#datatable-${entity}`;
            if ($.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            $(tableId).DataTable({
                pageLength: 10,
                dom: "Bfrtip",
                buttons: [
                    { extend: "copy",    text: '<i class="fas fa-copy"></i> Copiar'   },
                    { extend: "excel",   text: '<i class="fas fa-file-excel"></i> Excel' },
                    { extend: "pdf",     text: '<i class="fas fa-file-pdf"></i> PDF'  },
                    { extend: "print",   text: '<i class="fas fa-print"></i> Imprimir' }
                ],
                language: {
                    search:      "Buscar:",
                    lengthMenu:  "Mostrar _MENU_ registros",
                    info:        "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty:   "Sin registros",
                    zeroRecords: "No se encontraron resultados",
                    paginate: { next: "Siguiente", previous: "Anterior" }
                }
            });
        }, 100);
    }


    /* =====================================================
       FORM VALIDATION HELPERS
    ===================================================== */

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showFieldError(input, msgEl, message) {
        if (!input || !msgEl) return;
        input.classList.add("field-invalid");
        input.classList.remove("field-valid");
        msgEl.textContent = message;
    }

    function clearFieldError(input, msgEl) {
        if (!input || !msgEl) return;
        input.classList.remove("field-invalid");
        input.classList.add("field-valid");
        msgEl.textContent = "";
    }

    function validateField(input, msgEl) {
        const value = input.value.trim();
        const type  = input.type;
        const name  = input.name;

        if (!value) {
            showFieldError(input, msgEl, "Este campo es obligatorio");
            return false;
        }
        if (type === "email" && !isValidEmail(value)) {
            showFieldError(input, msgEl, "Ingrese un correo electrónico válido");
            return false;
        }
        if (type === "password" && value.length < 8) {
            showFieldError(input, msgEl, "La contraseña debe tener al menos 8 caracteres");
            return false;
        }
        clearFieldError(input, msgEl);
        return true;
    }

    function validateForm(form) {
        const errors = [];
        form.querySelectorAll("input[required], select[required]").forEach(input => {
            const msgEl = form.querySelector(`#err-${input.name}`);
            if (!validateField(input, msgEl)) {
                errors.push(input);
            }
        });
        return errors;
    }


    /* =====================================================
       RENDER FORM (MODAL)
    ===================================================== */

    async function renderForm(record = null) {

        /* ── Role-based access control ─────────────────────────────────── */
        const userRole = getUser()?.role || "";
        const isReadonlyRole = config.readonlyFor?.includes(userRole) || false;
        const noEditRole     = config.noEditFor?.includes(userRole)   || false;
        if (config.readonly || isReadonlyRole || noEditRole) return;
        /* ────────────────────────────────────────────────────────────────── */

        currentEditId = record ? record.id : null;

        const existingModal = document.getElementById("crudModal");
        if (existingModal) existingModal.remove();

        const inputs = [];
        const requiredFields = [];

        for (const f of fields) {

            if (f.name === "id") continue;

            const isRequired = true; /* all non-id fields are required by default */
            const labelHtml  = `${f.label}<span class="required-star" aria-hidden="true">*</span>`;

            /* SELECT */
            if (f.type === "select") {
                const options = f.options.map(opt => {
                    const selected = record && record[f.name] === opt.value ? "selected" : "";
                    return `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                }).join("");

                inputs.push(`
                <div class="form-group">
                    <label for="field-${f.name}">${labelHtml}</label>
                    <select id="field-${f.name}" name="${f.name}" required>
                        ${options}
                    </select>
                    <span class="field-error-msg" id="err-${f.name}"></span>
                </div>`);

                requiredFields.push(f.name);
                continue;
            }

            /* RELATION */
            if (f.type === "relation") {
                try {
                    const relData = await apiFetch(`/${f.relation}`);
                    const items   = relData?.[f.relation] || [];
                    const options = items.map(item => {
                        const selected = record && record[f.name] == item.id ? "selected" : "";
                        return `<option value="${item.id}" ${selected}>${item[f.display] ?? item.id}</option>`;
                    }).join("");

                    inputs.push(`
                    <div class="form-group">
                        <label for="field-${f.name}">${labelHtml}</label>
                        <select id="field-${f.name}" name="${f.name}" required>
                            <option value="">Seleccione...</option>
                            ${options}
                        </select>
                        <span class="field-error-msg" id="err-${f.name}"></span>
                    </div>`);

                } catch (err) {

                    const httpStatus = err.status || 0;

                    /* ─── 403: sin permiso para listar el recurso ──────────
                       Si el recurso es "users", auto-rellenar con el usuario
                       actual — tiene sentido para propuestas y solicitudes
                       donde el autor siempre es el usuario en sesión.
                    ───────────────────────────────────────────────────── */
                    if (httpStatus === 403 && f.relation === "users") {

                        const me       = getUser();
                        const selfId   = record?.[f.name] ?? me?.id ?? "";
                        const selfName = me?.name ?? "Usuario actual";

                        inputs.push(`
                        <div class="form-group">
                            <label for="field-${f.name}">${labelHtml}</label>
                            <input type="hidden"
                                   id="field-${f.name}"
                                   name="${f.name}"
                                   value="${selfId}">
                            <div style="
                                padding: 10px 14px;
                                border: 1px solid var(--color-border);
                                border-radius: var(--radius-input);
                                background: var(--color-surface-2);
                                font-size: var(--text-sm);
                                color: var(--color-text);
                                display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-user-circle"
                                   style="color:var(--color-primary);font-size:1.1rem;flex-shrink:0"></i>
                                <span>${selfName}</span>
                                <span style="font-size:var(--text-xs);color:var(--color-text-faint);margin-left:auto">
                                    asignado automáticamente
                                </span>
                            </div>
                        </div>`);

                    /* ─── 403: otro recurso restringido ──────────────────── */
                    } else if (httpStatus === 403) {

                        console.warn(`[CRUD] 403 al cargar relación /${f.relation} — sin permisos`);
                        inputs.push(`
                        <div class="form-group">
                            <label for="field-${f.name}">${labelHtml}</label>
                            <select id="field-${f.name}" name="${f.name}" required
                                    style="border-color:var(--color-error)">
                                <option value="" disabled selected>
                                    ⚠ Sin permisos para cargar esta lista
                                </option>
                            </select>
                            <span class="field-error-msg" style="display:flex;align-items:center;gap:4px">
                                <i class="fas fa-lock"></i>
                                Tu rol no puede ver esta lista. Contacta al administrador.
                            </span>
                        </div>`);

                    /* ─── 401: sesión expirada ───────────────────────────── */
                    } else if (httpStatus === 401) {

                        inputs.push(`
                        <div class="form-group">
                            <label for="field-${f.name}">${labelHtml}</label>
                            <select id="field-${f.name}" name="${f.name}" required
                                    style="border-color:var(--color-warning)">
                                <option value="" disabled selected>
                                    ⚠ Sesión expirada — recarga la página
                                </option>
                            </select>
                            <span class="field-error-msg" style="display:flex;align-items:center;gap:4px;color:var(--color-warning-text)">
                                <i class="fas fa-clock"></i>
                                Tu sesión expiró. Cierra el formulario y vuelve a iniciar sesión.
                            </span>
                        </div>`);

                    /* ─── Otro error ──────────────────────────────────────── */
                    } else {

                        console.error(`[CRUD] Error ${httpStatus} al cargar /${f.relation}:`, err.message);
                        inputs.push(`
                        <div class="form-group">
                            <label for="field-${f.name}">${labelHtml}</label>
                            <select id="field-${f.name}" name="${f.name}" required
                                    style="border-color:var(--color-error)">
                                <option value="" disabled selected>
                                    ⚠ Error al cargar datos (${httpStatus || "red"})
                                </option>
                            </select>
                            <span class="field-error-msg" style="display:flex;align-items:center;gap:4px">
                                <i class="fas fa-exclamation-circle"></i>
                                No se pudo cargar la lista. Cierra el formulario e inténtalo de nuevo.
                            </span>
                        </div>`);

                    }
                }
                requiredFields.push(f.name);
                continue;
            }

            /* INPUT */
            const inputType  = f.type || "text";
            const inputValue = record ? (record[f.name] ?? "") : "";
            const extraAttrs = inputType === "password"
                ? 'autocomplete="new-password" placeholder="Mínimo 8 caracteres"'
                : `autocomplete="off"`;

            inputs.push(`
            <div class="form-group">
                <label for="field-${f.name}">${labelHtml}</label>
                <input
                    type="${inputType}"
                    id="field-${f.name}"
                    name="${f.name}"
                    value="${inputType !== "password" ? inputValue : ""}"
                    ${extraAttrs}
                    required>
                <span class="field-error-msg" id="err-${f.name}"></span>
            </div>`);

            requiredFields.push(f.name);
        }

        const modal = `
        <div id="crudModal" role="dialog" aria-modal="true" aria-labelledby="crudModalTitle">
            <div class="crudModalBox">
                <form id="crudForm-${entity}" novalidate>
                    <h3 id="crudModalTitle">
                        <i class="fas fa-${record ? "pencil-alt" : "plus-circle"}" style="color:var(--color-primary);margin-right:8px"></i>
                        ${record ? "Editar" : "Crear"} ${title}
                    </h3>

                    <p class="form-legend">
                        <span class="required-star">*</span> Campos obligatorios
                    </p>

                    <div id="formErrorBanner" class="form-error-banner" style="display:none" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="formErrorText"></span>
                    </div>

                    ${inputs.join("")}

                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" id="closeModalBtn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-check"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>`;

        document.body.insertAdjacentHTML("beforeend", modal);

        /* Blur validation after modal is in DOM */
        const form = document.getElementById(`crudForm-${entity}`);
        if (form) {
            form.querySelectorAll("input[required], select[required]").forEach(input => {
                input.addEventListener("blur", () => {
                    const msgEl = form.querySelector(`#err-${input.name}`);
                    validateField(input, msgEl);
                });
            });

            /* Auto-focus first field */
            form.querySelector("input, select")?.focus();
        }
    }


    /* =====================================================
       CREATE / UPDATE
    ===================================================== */

    async function create(data) {
        await apiFetch(`/${entity}`, { method: "POST", body: JSON.stringify(data) });
        await init();
    }

    async function update(id, data) {
        await apiFetch(`/${entity}/${id}`, { method: "PUT", body: JSON.stringify(data) });
        await init();
    }


    /* =====================================================
       EVENTOS
    ===================================================== */

    function bindEvents() {

        if (eventsBound) return;
        eventsBound = true;

        const app = document.getElementById("app");
        if (!app) return;

        /* CREATE */
        app.addEventListener("click", async e => {
            if (e.target.id === `createBtn-${entity}` ||
                e.target.closest(`#createBtn-${entity}`)) {
                await renderForm();
            }
        });

        /* EDIT */
        app.addEventListener("click", async e => {
            const btn = e.target.closest(`.editBtn-${entity}`);
            if (btn) {
                const id     = btn.dataset.id;
                const record = recordsCache.find(r => r.id == id);
                await renderForm(record);
            }
        });

        /* TOGGLE STATUS */
        app.addEventListener("click", async e => {
            const btn = e.target.closest(`.toggleBtn-${entity}`);
            if (!btn) return;

            const id        = btn.dataset.id;
            const status    = btn.dataset.status;
            const label     = status === "ACTIVO" ? "inactivar" : "activar";
            const record    = recordsCache.find(r => r.id == id);
            const name      = record?.name || record?.title || `#${id}`;

            const result = await Swal.fire({
                title:             `¿${label.charAt(0).toUpperCase() + label.slice(1)} registro?`,
                html:              `Se cambiará el estado de <strong>${name}</strong>.`,
                icon:              "warning",
                showCancelButton:  true,
                confirmButtonText: `Sí, ${label}`,
                cancelButtonText:  "Cancelar",
                confirmButtonColor: status === "ACTIVO" ? "#f59e0b" : "#22c55e",
                reverseButtons:    true,
            });

            if (!result.isConfirmed) return;

            try {
                await apiFetch(`/${entity}/${id}/toggle-status`, { method: "PUT" });
                Swal.fire({
                    icon:             "success",
                    title:            "Estado actualizado",
                    timer:            1500,
                    showConfirmButton: false,
                    toast:            true,
                    position:         "top-end",
                });
                await init();
            } catch (err) {
                Swal.fire({ icon: "error", title: "Error", text: err.message });
            }
        });

        /* CLOSE MODAL */
        document.body.addEventListener("click", e => {
            if (e.target.id === "closeModalBtn" || e.target.closest("#closeModalBtn")) {
                document.getElementById("crudModal")?.remove();
            }
            /* Click on backdrop */
            if (e.target.id === "crudModal") {
                e.target.remove();
            }
        });

        /* ESC closes modal */
        document.addEventListener("keydown", e => {
            if (e.key === "Escape") {
                document.getElementById("crudModal")?.remove();
            }
        });

        /* SUBMIT FORM */
        document.body.addEventListener("submit", async e => {

            if (e.target.id !== `crudForm-${entity}`) return;
            e.preventDefault();
            if (submitting) return;

            const form     = e.target;
            const errors   = validateForm(form);
            const banner   = document.getElementById("formErrorBanner");
            const bannerTx = document.getElementById("formErrorText");

            if (errors.length > 0) {
                if (banner && bannerTx) {
                    bannerTx.textContent =
                        `Corrige ${errors.length} error${errors.length > 1 ? "es" : ""} antes de continuar`;
                    banner.style.display = "flex";
                }
                errors[0].scrollIntoView({ behavior: "smooth", block: "center" });
                errors[0].focus();
                return;
            }

            if (banner) banner.style.display = "none";

            submitting = true;
            const saveBtn = document.getElementById("saveBtn");
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }

            const data = Object.fromEntries(new FormData(form).entries());

            try {
                if (currentEditId) {
                    await update(currentEditId, data);
                } else {
                    await create(data);
                }

                document.getElementById("crudModal")?.remove();

                Swal.fire({
                    icon:             "success",
                    title:            currentEditId ? "Actualizado" : "Creado",
                    text:             `El registro fue ${currentEditId ? "actualizado" : "creado"} correctamente.`,
                    timer:            2000,
                    showConfirmButton: false,
                    toast:            true,
                    position:         "top-end",
                });

            } catch (error) {
                const msg = error.message || "Error al guardar";
                Swal.fire({ icon: "error", title: "Error", text: msg });

                if (banner && bannerTx) {
                    bannerTx.textContent = msg;
                    banner.style.display = "flex";
                }
            } finally {
                submitting = false;
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-check"></i> Guardar';
                }
            }
        });

        /* Custom module actions */
        if (config.onAction) {
            app.addEventListener("click", e => {
                config.onAction(e, recordsCache);
            });
        }
    }

    return { init };
}
