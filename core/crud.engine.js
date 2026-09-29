/* =========================================================
   #archivo: /frontend/core/crud.engine.js
   Motor CRUD reutilizable optimizado para SPA
   ========================================================= */

import { apiFetch }            from "../services/api.service.js";
import { LayoutView }           from "../layout/layout.view.js";
import { initLayoutController } from "../layout/layout.controller.js";
import { getUser }              from "../services/storage.service.js";
import { escapeHtml }           from "./escape.js";
import { passwordPolicyError }  from "./password-policy.js";

export function createCrudModule(config) {

    const entity  = config.entity;
    const title   = config.title;
    const fields  = config.fields;

    /* Los campos de contraseña son solo de formulario — nunca deben verse en una
       tabla de listado, ni siquiera vacíos (ver auditoría de diseño 2026-07-25).
       Los textarea (texto largo tipo descripción) tampoco caben en una columna. */
    const tableFields = fields.filter(f => f.type !== "password" && f.type !== "textarea");

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
        return map[value] || `<span class="badge">${escapeHtml(value)}</span>`;
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

        const headers = tableFields.map(f => `<th>${f.label}</th>`).join("");

        /* Empty state */
        if (records.length === 0) {
            const content = `
            <div class="table-toolbar">
                <h2>${title}</h2>
                ${!noCreate ? `
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="btn btn-primary" id="createBtn-${entity}">
                        <i class="fas fa-plus"></i>
                        Crear ${title.split(" ").pop()}
                    </button>
                    ${config.toolbarExtraHtml ? config.toolbarExtraHtml() : ""}
                </div>` : ""}
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

            config.afterTableMount?.();

            return;
        }

        /* Show actions column when at least one action type is available */
        const showActionsCol = !noEdit || (!isReadonly && config.actions?.length > 0);

        /* Rows */
        const rows = records.map(record => {

            const cols = tableFields.map(f => {

                if (f.type === "relation") {
                    const rel = record[f.relation.slice(0, -1)];
                    return `<td data-label="${f.label}">${escapeHtml(rel ? rel[f.display] : (record[f.name] ?? ""))}</td>`;
                }

                const val = record[f.name] ?? "";

                /* Render status as badge */
                if (f.name === "status") {
                    return `<td data-label="${f.label}">${statusBadge(val)}</td>`;
                }

                return `<td data-label="${f.label}">${escapeHtml(val)}</td>`;

            }).join("");

            /* Action buttons */
            let actions = "";

            if (!noEdit) {

                actions += `
                <button class="btn btn-sm btn-secondary editBtn-${entity}" data-id="${escapeHtml(record.id)}" title="Editar">
                    <i class="fas fa-edit"></i> Editar
                </button>`;

                if (hasToggle) {
                    const isActive = record.status === "ACTIVO";
                    actions += `
                    <button class="btn btn-sm ${isActive ? "btn-warning" : "btn-success"} toggleBtn-${entity}"
                        data-id="${escapeHtml(record.id)}" data-status="${escapeHtml(record.status)}"
                        title="${isActive ? "Inactivar" : "Activar"}">
                        <i class="fas fa-${isActive ? "toggle-on" : "toggle-off"}"></i>
                        ${isActive ? "Inactivar" : "Activar"}
                    </button>`;
                }
            }

            if (!isReadonly && config.actions) {
                config.actions.forEach(action => {
                    actions += `
                    <button class="btn btn-sm btn-secondary ${action.class}" data-id="${escapeHtml(record.id)}">
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
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn-primary" id="createBtn-${entity}">
                    <i class="fas fa-plus"></i>
                    Crear ${title.split(" ").pop()}
                </button>
                ${config.toolbarExtraHtml ? config.toolbarExtraHtml() : ""}
            </div>` : ""}
        </div>

        ${config.filters?.length ? `
        <div class="crud-filters" role="group" aria-label="Filtrar registros">
            ${config.filters.map(f => `
                <label class="crud-filter">
                    ${escapeHtml(f.label)}
                    <select data-crud-filter="${escapeHtml(f.field)}">
                        <option value="">Todos</option>
                        ${f.options.map(o => `<option value="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join("")}
                    </select>
                </label>`).join("")}
        </div>` : ""}

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

        /* Hook para que un módulo active su propio JS del toolbar extra
           (ej. botón "Importar") una vez la tabla ya está en el DOM. */
        config.afterTableMount?.();

        setTimeout(() => {
            const tableId = `#datatable-${entity}`;
            if ($.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            const table = $(tableId).DataTable({
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

            /* Filtros por columna (config.filters), ej. rol/estado en Usuarios
               (CU06 / RF01): coincidencia exacta sobre el texto de la celda,
               no una búsqueda parcial como el buscador general de DataTables. */
            config.filters?.forEach(f => {
                const colIndex = tableFields.findIndex(tf => tf.name === f.field);
                if (colIndex === -1) return;
                document.querySelector(`[data-crud-filter="${f.field}"]`)?.addEventListener("change", e => {
                    const val = e.target.value;
                    table.column(colIndex).search(val ? `^${val}$` : "", true, false).draw();
                });
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

        if (!value) {
            showFieldError(input, msgEl, "Este campo es obligatorio");
            return false;
        }
        if (type === "email" && !isValidEmail(value)) {
            showFieldError(input, msgEl, "Ingrese un correo electrónico válido");
            return false;
        }
        /* RN10: no solo longitud — mayúscula, minúscula, número y símbolo. */
        if (type === "password" && input.name !== "password_confirmation") {
            const err = passwordPolicyError(value);
            if (err) { showFieldError(input, msgEl, err); return false; }
        }
        clearFieldError(input, msgEl);
        return true;
    }

    function validateForm(form) {
        const errors = [];
        form.querySelectorAll("input[required], select[required], textarea[required]").forEach(input => {
            const msgEl = form.querySelector(`#err-${input.name}`);
            if (!validateField(input, msgEl)) {
                errors.push(input);
            }
        });

        /* Un campo password opcional (ej: "cambiar contraseña" al editar un
           usuario) no lleva [required], pero si el admin escribió algo debe
           cumplir RN10 igual — el bucle de arriba lo salta por completo. */
        form.querySelectorAll('input[type="password"]:not([required])').forEach(input => {
            if (!input.value) return;
            const msgEl = form.querySelector(`#err-${input.name}`);
            if (input.name === "password_confirmation") return;   /* se valida abajo */
            if (!validateField(input, msgEl)) errors.push(input);
        });

        /* Contraseña y confirmación deben coincidir cuando ambas tienen valor
           (server-side también lo exige con "confirmed", esto es solo para
           no esperar el viaje de ida y vuelta al servidor). */
        const pass = form.querySelector('input[name="password"]');
        const conf = form.querySelector('input[name="password_confirmation"]');
        if (pass?.value && conf && pass.value !== conf.value) {
            const msgEl = form.querySelector("#err-password_confirmation");
            showFieldError(conf, msgEl, "Las contraseñas no coinciden");
            errors.push(conf);
        }

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

            /* Todos los campos son obligatorios por defecto — un módulo puede
               marcar required:false explícitamente (ej. una descripción). */
            const isRequired = f.required !== false;
            const labelHtml  = isRequired
                ? `${f.label}<span class="required-star" aria-hidden="true">*</span>`
                : `${f.label} <span class="optional-hint">(opcional)</span>`;

            /* TEXTAREA */
            if (f.type === "textarea") {
                const inputValue = record ? (record[f.name] ?? "") : "";
                /* Escapar: el contenido va dentro de <textarea>...</textarea> —
                   sin esto, una descripción con "</textarea>" rompería el markup. */
                const safeValue = String(inputValue)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");
                inputs.push(`
                <div class="form-group">
                    <label for="field-${f.name}">${labelHtml}</label>
                    <textarea
                        id="field-${f.name}"
                        name="${f.name}"
                        rows="4"
                        ${isRequired ? "required" : ""}
                    >${safeValue}</textarea>
                    <span class="field-error-msg" id="err-${f.name}"></span>
                </div>`);
                if (isRequired) requiredFields.push(f.name);
                continue;
            }

            /* SELECT */
            if (f.type === "select") {
                const options = f.options.map(opt => {
                    const selected = record && record[f.name] === opt.value ? "selected" : "";
                    return `<option value="${escapeHtml(opt.value)}" ${selected}>${escapeHtml(opt.label)}</option>`;
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
                    /* RF02 / RF03: un registro inactivo (o cuya facultad / programa
                       padre esté inactivo) no se ofrece en los formularios. Se
                       conserva solo si es el valor actual del registro que se edita,
                       para no cambiarlo sin que el usuario lo note. */
                    const isOff = item => item.status === "INACTIVO"
                        || item.faculty?.status === "INACTIVO"
                        || item.program?.status === "INACTIVO";
                    const selectable = items.filter(item =>
                        !isOff(item) || (record && record[f.name] == item.id));
                    const options = selectable.map(item => {
                        const selected = record && record[f.name] == item.id ? "selected" : "";
                        const label = (item[f.display] ?? item.id) + (isOff(item) ? " (inactivo)" : "");
                        return `<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(label)}</option>`;
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
                                   value="${escapeHtml(selfId)}">
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
                                <span>${escapeHtml(selfName)}</span>
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
            const extraAttrs = (inputType === "password"
                ? 'autocomplete="new-password" placeholder="Mínimo 8 caracteres"'
                : `autocomplete="off"`)
                + (f.maxlength   ? ` maxlength="${Number(f.maxlength)}"` : "")
                + (f.placeholder ? ` placeholder="${escapeHtml(f.placeholder)}"` : "")
                + (f.uppercase   ? ` style="text-transform:uppercase" autocapitalize="characters"` : "");

            inputs.push(`
            <div class="form-group">
                <label for="field-${f.name}">${labelHtml}</label>
                <input
                    type="${inputType}"
                    id="field-${f.name}"
                    name="${f.name}"
                    value="${inputType !== "password" ? escapeHtml(inputValue) : ""}"
                    ${extraAttrs}
                    ${isRequired ? "required" : ""}>
                ${f.hint ? `<span class="optional-hint" style="display:block;margin-top:4px">${f.hint}</span>` : ""}
                <span class="field-error-msg" id="err-${f.name}"></span>
            </div>`);

            if (isRequired) requiredFields.push(f.name);
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

                    ${config.extraFormHtml ? config.extraFormHtml(record) : ""}

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
            form.querySelectorAll("input[required], select[required], textarea[required]").forEach(input => {
                input.addEventListener("blur", () => {
                    const msgEl = form.querySelector(`#err-${input.name}`);
                    validateField(input, msgEl);
                });
            });

            /* Auto-focus first field */
            form.querySelector("input, select")?.focus();
        }

        /* Hook para que un módulo agregue su propia lógica (ej. lista de
           objetivos anidada) una vez el formulario ya está en el DOM. */
        await config.afterFormMount?.(record);
    }


    /* =====================================================
       CREATE / UPDATE
    ===================================================== */

    async function create(data) {
        const response = await apiFetch(`/${entity}`, { method: "POST", body: JSON.stringify(data) });
        await init();
        return response;
    }

    async function update(id, data) {
        const response = await apiFetch(`/${entity}/${id}`, { method: "PUT", body: JSON.stringify(data) });
        await init();
        return response;
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
                html:              `Se cambiará el estado de <strong>${escapeHtml(name)}</strong>.`,
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

            /* Un campo opcional (required:false) vacío se omite en vez de
               enviarse como "" — evita que reglas tipo "nullable|min:6"
               rechacen una contraseña vacía (nullable solo perdona null,
               no un string vacío). */
            for (const f of fields) {
                if (f.required === false && data[f.name] === "") {
                    delete data[f.name];
                }
            }

            try {
                let response;
                if (currentEditId) {
                    response = await update(currentEditId, data);
                } else {
                    response = await create(data);
                }

                /* Hook para que un módulo haga algo con el registro recién
                   guardado (ej. crear los objetivos anidados de un semillero
                   nuevo, usando el id que acaba de asignar el backend). */
                await config.onSaved?.(response, !!currentEditId, currentEditId);

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
