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
    /* Nombre en singular y plural para los textos («Crear semillero», «No hay registros de proyectos»). */
    const SINGULAR = { seedbeds: "semillero", objectives: "objetivo", projects: "proyecto", products: "producto", results: "resultado",
        groups: "grupo", coordinators: "coordinador", faculties: "facultad", programs: "programa", areas: "área", cats: "CAT", users: "usuario" };
    const singular = config.singular || SINGULAR[entity] || title.split(" ").pop().toLowerCase();
    const plural   = title.replace(/^Gestión de /, "").toLowerCase();
    const fields  = config.fields;

    /* Los campos de contraseña son solo de formulario — nunca deben verse en una
       tabla de listado, ni siquiera vacíos (ver auditoría de diseño 2026-07-25).
       Los textarea (texto largo tipo descripción) tampoco caben en una columna. */
    const tableFields = fields.filter(f => f.type !== "password" && f.type !== "textarea");

    let currentEditId = null;
    let recordsCache  = [];
    let eventsBound   = false;
    let submitting    = false;
    /* Selección múltiple para activar/inactivar en lote (Jose, 2026-10-11:
       "si quiero hacer una selección múltiple o completa para modificar x
       campo en todas las filas, no es posible"). Se queda en IDs (string)
       para sobrevivir el re-render de DataTables entre páginas. */
    let selectedIds   = new Set();

    /* Auto-detect toggle-status support:
       entity supports it when it has a status field with ACTIVO *and* INACTIVO options. Proyectos tiene
       ACTIVO/FINALIZADO/SUSPENDIDO: no hay «inactivar» (la ruta toggle-status no existe y daba 404); su
       estado se cambia al editar. */
    const hasToggle = !config.readonly && fields.some(f =>
        f.name === "status" && f.options?.some(o => o.value === "ACTIVO") && f.options?.some(o => o.value === "INACTIVO")
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

        let data;
        try {
            data = await apiFetch(endpoint);
        } catch (err) {
            /* E4: fallo de conexión al cargar el listado inicial (transversal
               CU07-CU13) — antes quedaba una pantalla en blanco sin aviso. */
            renderLoadError(err);
            return;
        }
        recordsCache = data[entity] || [];
        renderTable(recordsCache);
        bindEvents();
    }

    function renderLoadError(err) {
        const isConnFailure = (err.status || 0) === 0;
        document.getElementById("app").innerHTML = LayoutView(`
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-plug"></i>
            </div>
            <h3>${isConnFailure ? "Sin conexión con el servidor" : "No se pudo cargar la información"}</h3>
            <p>${isConnFailure
                ? "No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo."
                : escapeHtml(err.message || "Ocurrió un error inesperado.")}</p>
            <button class="btn btn-primary" id="retryLoadBtn-${entity}">
                <i class="fas fa-redo"></i>
                Reintentar
            </button>
        </div>`);
        initLayoutController();
        document.getElementById(`retryLoadBtn-${entity}`)?.addEventListener("click", init);
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

    function filterOptions(f, records) {
        return typeof f.options === "function" ? (f.options(records) || []) : (f.options || []);
    }

    /* Predicados de filtro (`test`) registrados en DataTables para esta tabla;
       se retiran antes de cada render para no acumularlos. */
    let predicateSearches = [];

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

        /* Filtros (config.filters). Extensiones opcionales y retrocompatibles
           (CU16 paso 2/3, A1): `options` puede ser una función (registros) =>
           opciones (catálogo dinámico); `roles` limita el filtro a ciertos
           roles; `allLabel` cambia el texto de «Todos»; `test(registro, valor)`
           filtra por predicado en vez de por texto de columna; `defaultValue()`
           devuelve el valor preseleccionado. Sin estas claves todo funciona igual. */
        const activeFilters = (config.filters || []).filter(f => !f.roles || f.roles.includes(userRole));

        /* Empty state */
        if (records.length === 0) {
            const content = `
            <div class="table-toolbar">
                <h2>${title}</h2>
                ${!noCreate ? `
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="btn btn-primary" id="createBtn-${entity}">
                        <i class="fas fa-plus"></i>
                        Crear ${singular}
                    </button>
                    ${config.toolbarExtraHtml ? config.toolbarExtraHtml() : ""}
                </div>` : ""}
            </div>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>Sin registros</h3>
                <p>No hay registros de ${plural}. Crea el primero para comenzar.</p>
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

        /* Columna de selección — solo si el rol puede editar/togglear algo
           (seleccionar filas sin poder hacerles nada no tiene sentido). */
        const showSelectCol = hasToggle && !noEdit;
        selectedIds = new Set(); /* cada render (nuevo filtro, alta/edición) parte de cero */

        /* Rows */
        const rows = records.map(record => {

            const cols = tableFields.map(f => {

                if (f.type === "relation") {
                    const rel = record[f.relation.slice(0, -1)];
                    return `<td data-label="${f.label}">${escapeHtml(rel ? rel[f.display] : (record[f.name] ?? ""))}</td>`;
                }

                if (f.type === "relation-multi") {
                    /* Antes se concatenaban TODOS los nombres separados por coma en
                       una sola celda de texto plano — con semilleros de 12 programas
                       (carga IDEAD) la fila crecía a cientos de píxeles de alto y la
                       tabla quedaba inusable (hallazgo real, 2026-10-11). Se limita a
                       2 chips + "+N" en vez de volcar la lista completa. */
                    const items = record[f.name] || [];
                    const shown = items.slice(0, 2);
                    const rest = items.length - shown.length;
                    const chips = shown.map(item => `<span class="cell-chip">${escapeHtml(item[f.display])}</span>`).join("")
                        + (rest > 0 ? `<span class="cell-chip cell-chip-more">+${rest}</span>` : "");
                    return `<td data-label="${f.label}"><div class="cell-chips">${chips || "—"}</div></td>`;
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
                ${showSelectCol ? `<td class="select-col" data-label=""><input type="checkbox" id="rowSelect-${entity}-${escapeHtml(record.id)}" name="rowSelect-${entity}-${escapeHtml(record.id)}" class="row-select-${entity}" data-id="${escapeHtml(record.id)}" aria-label="Seleccionar fila"></td>` : ""}
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
                    Crear ${singular}
                </button>
                ${config.toolbarExtraHtml ? config.toolbarExtraHtml() : ""}
            </div>` : ""}
        </div>

        ${showSelectCol ? `
        <div class="bulk-bar" id="bulkBar-${entity}" style="display:none">
            <span><strong id="bulkCount-${entity}">0</strong> seleccionado(s)</span>
            <div class="bulk-bar-actions">
                <button type="button" class="btn btn-sm btn-success" id="bulkActivate-${entity}">
                    <i class="fas fa-toggle-off"></i> Activar
                </button>
                <button type="button" class="btn btn-sm btn-warning" id="bulkDeactivate-${entity}">
                    <i class="fas fa-toggle-on"></i> Inactivar
                </button>
                <button type="button" class="btn btn-sm btn-ghost" id="bulkClear-${entity}">
                    <i class="fas fa-xmark"></i> Cancelar selección
                </button>
            </div>
        </div>` : ""}

        ${activeFilters.length ? `
        <div class="crud-filters" role="group" aria-label="Filtrar registros">
            ${activeFilters.map(f => `
                <label class="crud-filter">
                    ${escapeHtml(f.label)}
                    <select data-crud-filter="${escapeHtml(f.field)}">
                        <option value="">${escapeHtml(f.allLabel || "Todos")}</option>
                        ${filterOptions(f, records).map(o => `<option value="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join("")}
                    </select>
                </label>`).join("")}
        </div>` : ""}

        <table id="datatable-${entity}" class="display mobile-card-table" style="width:100%">
            <thead>
                <tr>
                    ${showSelectCol ? `<th class="select-col"><input type="checkbox" id="selectAll-${entity}" aria-label="Seleccionar todo"></th>` : ""}
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
            predicateSearches.forEach(fn => {
                const i = $.fn.dataTable.ext.search.indexOf(fn);
                if (i !== -1) $.fn.dataTable.ext.search.splice(i, 1);
            });
            predicateSearches = [];
            /* El checkbox de selección (si existe) es siempre la columna 0 —
               los índices de las demás columnas (filtros, orden) se corren
               uno a la derecha. */
            const colOffset = showSelectCol ? 1 : 0;

            const table = $(tableId).DataTable({
                pageLength: config.pageLength ?? 10,
                dom: "Bfrtip",
                columnDefs: showSelectCol ? [{ orderable: false, searchable: false, targets: 0 }] : [],
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
                    zeroRecords: config.emptyFilterMessage || "No se encontraron resultados",
                    paginate: { next: "Siguiente", previous: "Anterior" }
                }
            });

            if (showSelectCol) bindBulkSelection(table);

            /* Filtros por columna (config.filters), ej. rol/estado en Usuarios
               (CU06 / RF01): coincidencia exacta sobre el texto de la celda,
               no una búsqueda parcial como el buscador general de DataTables. */
            activeFilters.forEach(f => {
                const sel = document.querySelector(`[data-crud-filter="${f.field}"]`);
                if (!sel) return;

                if (typeof f.test === "function") {
                    /* Filtro por predicado sobre el registro completo (la fila
                       i del DOM corresponde a records[i]). Se combina con el
                       buscador y con la exportación, que solo ven filas filtradas. */
                    const fn = (settings, _data, dataIndex) => {
                        if (settings.nTable.id !== `datatable-${entity}` || !sel.isConnected || !sel.value) return true;
                        const rec = records[dataIndex];
                        return rec ? !!f.test(rec, sel.value) : true;
                    };
                    $.fn.dataTable.ext.search.push(fn);
                    predicateSearches.push(fn);
                    sel.addEventListener("change", () => table.draw());
                } else {
                    const fieldIndex = tableFields.findIndex(tf => tf.name === f.field);
                    if (fieldIndex === -1) return;
                    const colIndex = fieldIndex + colOffset;
                    sel.addEventListener("change", e => {
                        const val = e.target.value;
                        table.column(colIndex).search(val ? `^${val}$` : "", true, false).draw();
                    });
                }

                const def = typeof f.defaultValue === "function" ? f.defaultValue() : null;
                if (def && Array.from(sel.options).some(o => o.value === String(def))) {
                    sel.value = String(def);
                    sel.dispatchEvent(new Event("change"));
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

        if (!value) {
            showFieldError(input, msgEl, "Este campo es obligatorio");
            return false;
        }
        if ((type === "email" || input.name === "email") && !isValidEmail(value)) {
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

            /* CU16: columna calculada de solo lectura (ej. facultad, líder,
               integrantes) — se muestra en la tabla pero no en el formulario. */
            if (f.readonly) continue;

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
                /* placeholder: al CREAR obliga a elegir (p. ej. el rol de un usuario no debe quedar en el primero de la lista). */
                const optionList = (f.placeholder && !record) ? [{ value: "", label: f.placeholder }, ...f.options] : f.options;
                const options = optionList.map(opt => {
                    const selected = record && record[f.name] === opt.value ? "selected" : "";
                    return `<option value="${escapeHtml(opt.value)}" ${selected}>${escapeHtml(opt.label)}</option>`;
                }).join("");

                inputs.push(`
                <div class="form-group">
                    <label for="field-${f.name}">${labelHtml}</label>
                    <select id="field-${f.name}" name="${f.name}" ${isRequired ? "required" : ""}>
                        ${options}
                    </select>
                    <span class="field-error-msg" id="err-${f.name}"></span>
                </div>`);

                if (isRequired) requiredFields.push(f.name);
                continue;
            }

            /* RELATION-MULTI (CU13 Ronda B: selección múltiple, ej. programas/áreas
               de un semillero) — mismo patrón que RELATION pero con <select multiple>
               y el valor actual como array de objetos relacionados en vez de un id. */
            if (f.type === "relation-multi") {
                try {
                    const relData = await apiFetch(`/${f.relation}`);
                    const items   = relData?.[f.relation] || [];
                    const currentIds = (record?.[f.name] || []).map(item => String(item.id));
                    const isOff = item => item.status === "INACTIVO";
                    const selectable = items.filter(item =>
                        !isOff(item) || currentIds.includes(String(item.id)));
                    const options = selectable.map(item => {
                        const selected = currentIds.includes(String(item.id)) ? "selected" : "";
                        const label = (item[f.display] ?? item.id) + (isOff(item) ? " (inactivo)" : "");
                        return `<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(label)}</option>`;
                    }).join("");

                    inputs.push(`
                    <div class="form-group">
                        <label for="field-${f.name}">${labelHtml}</label>
                        <select id="field-${f.name}" name="${f.name}" multiple required size="5">
                            ${options}
                        </select>
                        <span class="field-error-msg" id="err-${f.name}"></span>
                    </div>`);

                } catch (err) {
                    console.error(`[CRUD] Error al cargar /${f.relation}:`, err.message);
                    inputs.push(`
                    <div class="form-group">
                        <label for="field-${f.name}">${labelHtml}</label>
                        <select id="field-${f.name}" name="${f.name}" multiple disabled style="border-color:var(--color-error)">
                            <option>⚠ Error al cargar datos</option>
                        </select>
                        <span class="field-error-msg">No se pudo cargar la lista. Cierra el formulario e inténtalo de nuevo.</span>
                    </div>`);
                }
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
                        <select id="field-${f.name}" name="${f.name}" ${isRequired ? "required" : ""}>
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
                        ${record ? "Editar" : "Crear"} ${singular}
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
                        ${!record && config.draftOption ? `
                        <button type="submit" class="btn btn-ghost" id="saveDraftBtn">
                            <i class="fas fa-file"></i> Guardar borrador
                        </button>` : ""}
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

            /* Hook opcional: un módulo puede pedir datos extra (ej. motivo) o
               mostrar información adicional antes de confirmar el cambio de
               estado. Devuelve `false`/`null` para cancelar, o un objeto
               `{ body }` con el payload a enviar en el PUT. */
            if (config.customToggleConfirm) {
                const custom = await config.customToggleConfirm(record, status);
                if (!custom) return;

                try {
                    const response = await apiFetch(`/${entity}/${id}/toggle-status`, {
                        method: "PUT",
                        body: JSON.stringify(custom.body || {}),
                    });
                    await config.onToggled?.(response);
                    Swal.fire({
                        icon: "success", title: "Estado actualizado",
                        timer: 1500, showConfirmButton: false, toast: true, position: "top-end",
                    });
                    await init();
                } catch (err) {
                    Swal.fire({ icon: "error", title: "Error", text: err.message });
                }
                return;
            }

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
            const isDraft = e.submitter?.id === "saveDraftBtn";
            const saveBtn = document.getElementById(isDraft ? "saveDraftBtn" : "saveBtn");
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }

            const data = Object.fromEntries(new FormData(form).entries());

            /* CU13 A1: «Guardar borrador» registra el semillero inactivo
               (no visible en la PWA) sin importar lo elegido en Estado. */
            if (isDraft) data.status = "INACTIVO";

            /* Un campo opcional (required:false) vacío se omite en vez de
               enviarse como "" — evita que reglas tipo "nullable|min:6"
               rechacen una contraseña vacía (nullable solo perdona null,
               no un string vacío). */
            for (const f of fields) {
                if (f.required === false && data[f.name] === "") {
                    delete data[f.name];
                }
            }

            /* CU13 Ronda B: campos fuera de `fields` (ej. selección múltiple
               en extraFormHtml) que el módulo necesita inyectar en el payload
               antes de enviarlo. */
            config.beforeSave?.(data, form);

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
                    saveBtn.innerHTML = isDraft
                        ? '<i class="fas fa-file"></i> Guardar borrador'
                        : '<i class="fas fa-check"></i> Guardar';
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


    /* =====================================================
       SELECCIÓN MÚLTIPLE Y ACCIONES EN LOTE
       (Jose, 2026-10-11: "si quiero hacer una selección múltiple... no es
       posible, cosa que me dificulta la gestión completa")
    ===================================================== */

    function getFilteredIds(table) {
        /* Las filas se construyen como HTML (no data-driven), así que para
           saber qué IDs quedan tras un filtro/búsqueda se leen los checkbox
           de los <tr> que DataTables considera "aplicados" — funciona igual
           en cualquier página, no solo la visible. */
        return Array.from(table.rows({ search: "applied" }).nodes())
            .map(tr => tr.querySelector(`.row-select-${entity}`)?.dataset.id)
            .filter(Boolean);
    }

    function syncBulkUI(table) {
        document.querySelectorAll(`.row-select-${entity}`).forEach(cb => {
            cb.checked = selectedIds.has(cb.dataset.id);
        });

        const selectAllCb = document.getElementById(`selectAll-${entity}`);
        if (selectAllCb) {
            const filteredIds = getFilteredIds(table);
            const allSelected = filteredIds.length > 0 && filteredIds.every(id => selectedIds.has(id));
            const anySelected = filteredIds.some(id => selectedIds.has(id));
            selectAllCb.checked = allSelected;
            selectAllCb.indeterminate = anySelected && !allSelected;
        }

        const bar = document.getElementById(`bulkBar-${entity}`);
        const countEl = document.getElementById(`bulkCount-${entity}`);
        if (bar) bar.style.display = selectedIds.size > 0 ? "flex" : "none";
        if (countEl) countEl.textContent = selectedIds.size;
    }

    async function bulkToggle(ids, body) {
        let ok = 0, failed = 0;
        for (const id of ids) {
            try {
                await apiFetch(`/${entity}/${id}/toggle-status`, { method: "PUT", body: JSON.stringify(body) });
                ok++;
            } catch {
                failed++;
            }
        }
        selectedIds.clear();
        Swal.fire({
            icon:  failed ? "warning" : "success",
            title: failed ? `${ok} actualizado${ok === 1 ? "" : "s"}, ${failed} con error` : "Listo",
            timer: 2200, showConfirmButton: false, toast: true, position: "top-end",
        });
        await init();
    }

    function bindBulkSelection(table) {

        syncBulkUI(table);
        table.on("draw", () => syncBulkUI(table));

        document.getElementById(`selectAll-${entity}`)?.addEventListener("change", e => {
            const ids = getFilteredIds(table);
            ids.forEach(id => e.target.checked ? selectedIds.add(id) : selectedIds.delete(id));
            syncBulkUI(table);
        });

        document.getElementById(`datatable-${entity}`)?.addEventListener("change", e => {
            const cb = e.target.closest(`.row-select-${entity}`);
            if (!cb) return;
            cb.checked ? selectedIds.add(cb.dataset.id) : selectedIds.delete(cb.dataset.id);
            syncBulkUI(table);
        });

        document.getElementById(`bulkClear-${entity}`)?.addEventListener("click", () => {
            selectedIds.clear();
            syncBulkUI(table);
        });

        document.getElementById(`bulkActivate-${entity}`)?.addEventListener("click", async () => {
            const ids = [...selectedIds].filter(id => recordsCache.find(r => String(r.id) === id)?.status !== "ACTIVO");
            if (!ids.length) {
                Swal.fire({ icon: "info", title: "Ya están activos", timer: 1500, showConfirmButton: false, toast: true, position: "top-end" });
                return;
            }
            const result = await Swal.fire({
                title: `¿Activar ${ids.length} registro${ids.length === 1 ? "" : "s"}?`,
                icon: "warning", showCancelButton: true,
                confirmButtonText: "Sí, activar", confirmButtonColor: "#22c55e", reverseButtons: true,
            });
            if (result.isConfirmed) await bulkToggle(ids, {});
        });

        document.getElementById(`bulkDeactivate-${entity}`)?.addEventListener("click", async () => {
            const ids = [...selectedIds].filter(id => recordsCache.find(r => String(r.id) === id)?.status === "ACTIVO");
            if (!ids.length) {
                Swal.fire({ icon: "info", title: "Ya están inactivos", timer: 1500, showConfirmButton: false, toast: true, position: "top-end" });
                return;
            }
            /* Algunos módulos exigen motivo al inactivar (CU15/19/20/21: mín.
               5 caracteres) — se pide una sola vez para todo el lote; si el
               módulo no lo necesita, el backend simplemente lo ignora. */
            const result = await Swal.fire({
                title: `Inactivar ${ids.length} registro${ids.length === 1 ? "" : "s"}`,
                input: "text",
                inputLabel: "Motivo (si este módulo lo requiere)",
                inputPlaceholder: "Motivo de inactivación",
                showCancelButton: true,
                confirmButtonText: "Inactivar", confirmButtonColor: "#f59e0b", reverseButtons: true,
            });
            if (result.isConfirmed) await bulkToggle(ids, result.value ? { reason: result.value } : {});
        });
    }

    return { init };
}
