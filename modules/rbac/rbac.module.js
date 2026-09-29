/* =========================================================
   #archivo: /frontend/modules/rbac/rbac.module.js
   Panel RBAC granular (solo ADMIN_SISTEMA), en 3 pestañas:
     Roles    → permisos por defecto de cada rol.
     Grupos   → personas de distintos roles agrupadas, con permisos que se
                otorgan al grupo completo (ej. "Comité editorial").
     Personas → excepción puntual (grant/revoke) para alguien específico,
                sin cambiar su rol.
   Ver api/config/rbac.php y App\Services\Rbac\PermissionResolver (orden de
   precedencia: ADMIN_SISTEMA > excepción por persona > rol o grupo).
   ========================================================= */

import { apiFetch }             from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }           from "../../core/escape.js";

const ROLE_LABELS = {
    ADMINISTRATIVO: "Administrativo",
    LIDER_SEMILLERO: "Líder de semillero",
    ESTUDIANTE: "Estudiante",
    ADMIN_SISTEMA: "Administrador del sistema",
};

let catalog = null;       // { modules: {mod: {label, actions:[{id,action,label}]}}, roles }
let activeTab = "roles";  // roles | groups | people

/* ───── Roles ───── */
let roleMatrix = null;    // { roles, matrix: { role: [permission_id,...] } }
let activeRole = "ADMINISTRATIVO";
let dirtyRolePerms = null;

/* ───── Grupos ───── */
let groups = [];
let activeGroupId = null;
let groupDetail = null;   // { group, members, member_ids, permission_ids }
let dirtyGroupPerms = null;
let dirtyGroupMembers = null;
let allUsers = null;      // cache de /rbac/users-lite

/* ───── Personas ───── */
let userSearch = "";
let userResult = null;    // { user, effective, overrides }

const toolbarTab = (key, label, icon) =>
    `<button type="button" class="btn btn-sm ${activeTab === key ? "btn-primary" : "btn-secondary"}" data-tab="${key}"><i class="fas ${icon}"></i> ${label}</button>`;

/** Acordeón reutilizable de módulos: cada módulo es un <details>, cerrado si no tiene nada marcado. */
function accordion(mode, getMarks) {
    return Object.entries(catalog.modules).map(([mod, def]) => {
        const marks = getMarks(mod, def);
        const anyMarked = mode === "checkbox" ? def.actions.some(a => marks.has(a.id)) : def.actions.some(a => marks[a.id]);
        const count = mode === "checkbox" ? def.actions.filter(a => marks.has(a.id)).length : def.actions.filter(a => marks[a.id]).length;

        const rows = def.actions.map(a => {
            if (mode === "checkbox") {
                return `<label class="rbac-perm">
                    <input type="checkbox" data-perm="${a.id}" ${marks.has(a.id) ? "checked" : ""}>
                    ${escapeHtml(a.label || a.action)}
                </label>`;
            }
            const eff = marks[a.id] || "";
            return `<div class="rbac-perm rbac-perm-override">
                <span>${escapeHtml(a.label || a.action)}</span>
                <select data-override-perm="${a.id}">
                    <option value="" ${eff === "" ? "selected" : ""}>Según su rol/grupo</option>
                    <option value="grant" ${eff === "grant" ? "selected" : ""}>Permitir (excepción)</option>
                    <option value="revoke" ${eff === "revoke" ? "selected" : ""}>Quitar (excepción)</option>
                </select>
            </div>`;
        }).join("");

        return `<details class="rbac-accordion-item" ${anyMarked ? "open" : ""}>
            <summary>${escapeHtml(def.label || mod)} ${count ? `<span class="rbac-count-badge">${count}</span>` : ""}</summary>
            <div class="rbac-perms">${rows}</div>
        </details>`;
    }).join("");
}

/* =========================================================
   Pestaña Roles
   ========================================================= */

function renderRolesTab() {
    const selected = dirtyRolePerms ?? new Set((roleMatrix.matrix[activeRole] || []));
    dirtyRolePerms = selected;

    return `
      <div class="rbac-subtabs" role="tablist">
        ${roleMatrix.roles.map(r => `<button type="button" class="btn btn-sm ${r === activeRole ? "btn-primary" : "btn-secondary"}" data-role-tab="${r}">${escapeHtml(ROLE_LABELS[r] || r)}</button>`).join("")}
      </div>
      <p class="rbac-hint">Permisos por defecto de <b>${escapeHtml(ROLE_LABELS[activeRole] || activeRole)}</b>. Se aplican a todas las personas con este rol, salvo que tengan una excepción propia o pertenezcan a un grupo con más permisos.</p>
      <div class="rbac-accordion">${accordion("checkbox", () => selected)}</div>
      <button type="button" id="rbac-save-role" class="btn btn-primary rbac-save-sticky">Guardar permisos de ${escapeHtml(ROLE_LABELS[activeRole] || activeRole)}</button>
    `;
}

function bindRolesTab() {
    const panel = document.getElementById("rbac-panel");
    panel.querySelectorAll("[data-role-tab]").forEach(btn => btn.addEventListener("click", () => {
        activeRole = btn.dataset.roleTab;
        dirtyRolePerms = null;
        panel.innerHTML = renderRolesTab();
        bindRolesTab();
    }));
    panel.querySelectorAll("[data-perm]").forEach(chk => chk.addEventListener("change", () => {
        const id = Number(chk.dataset.perm);
        if (chk.checked) dirtyRolePerms.add(id); else dirtyRolePerms.delete(id);
    }));
    const saveBtn = document.getElementById("rbac-save-role");
    if (saveBtn) saveBtn.addEventListener("click", async () => {
        try {
            await apiFetch(`/rbac/roles/${encodeURIComponent(activeRole)}`, {
                method: "PUT",
                body: JSON.stringify({ permission_ids: Array.from(dirtyRolePerms) }),
            });
            roleMatrix.matrix[activeRole] = Array.from(dirtyRolePerms);
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Permisos del rol guardados", timer: 1800, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: "error", title: "No se guardó", text: err.message });
        }
    });
}

/* =========================================================
   Pestaña Grupos
   ========================================================= */

function renderGroupsTab() {
    const list = groups.map(g => `
        <button type="button" class="rbac-group-card ${g.id === activeGroupId ? "is-active" : ""}" data-group="${g.id}">
            <b>${escapeHtml(g.name)}</b>
            <span>${g.users_count} persona(s) · ${g.permissions_count} permiso(s)</span>
        </button>`).join("") || "<p class='rbac-empty'>Aún no hay grupos.</p>";

    return `
      <div class="rbac-groups-layout">
        <div class="card rbac-groups-list">
          <div class="table-toolbar"><h3>Grupos</h3><button type="button" id="rbac-new-group" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nuevo</button></div>
          <div class="rbac-group-cards">${list}</div>
        </div>
        <div class="card rbac-group-detail" id="rbac-group-detail">${renderGroupDetail()}</div>
      </div>
    `;
}

function renderGroupDetail() {
    if (!activeGroupId || !groupDetail) {
        return `<p class="rbac-empty">Elige un grupo de la izquierda, o crea uno nuevo.</p>`;
    }
    const membersSelected = dirtyGroupMembers ?? new Set(groupDetail.member_ids);
    dirtyGroupMembers = membersSelected;
    const permsSelected = dirtyGroupPerms ?? new Set(groupDetail.permission_ids);
    dirtyGroupPerms = permsSelected;

    const userRows = (allUsers || []).map(u => `
        <label class="rbac-perm">
            <input type="checkbox" data-member="${u.id}" ${membersSelected.has(u.id) ? "checked" : ""}>
            ${escapeHtml(u.name)} <small>(${escapeHtml(ROLE_LABELS[u.role] || u.role)})</small>
        </label>`).join("");

    return `
      <div class="table-toolbar">
        <h3>${escapeHtml(groupDetail.group.name)}</h3>
        <button type="button" id="rbac-delete-group" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Eliminar grupo</button>
      </div>
      ${groupDetail.group.description ? `<p class="rbac-hint">${escapeHtml(groupDetail.group.description)}</p>` : ""}
      <h4>Integrantes (cualquier rol)</h4>
      <div class="rbac-perms rbac-member-list">${userRows}</div>
      <button type="button" id="rbac-save-group-members" class="btn btn-secondary">Guardar integrantes</button>

      <h4 class="rbac-section-gap">Permisos que otorga este grupo</h4>
      <p class="rbac-hint">Se suman a los del rol de cada integrante (nunca quitan nada).</p>
      <div class="rbac-accordion">${accordion("checkbox", () => permsSelected)}</div>
      <button type="button" id="rbac-save-group-perms" class="btn btn-primary rbac-save-sticky">Guardar permisos del grupo</button>
    `;
}

async function loadGroups() {
    const r = await apiFetch("/rbac/groups");
    groups = r.groups;
}

async function openGroup(id) {
    activeGroupId = id;
    dirtyGroupMembers = null;
    dirtyGroupPerms = null;
    if (!allUsers) {
        const r = await apiFetch("/rbac/users-lite");
        allUsers = r.users;
    }
    groupDetail = await apiFetch(`/rbac/groups/${id}`);
}

function bindGroupsTab() {
    const panel = document.getElementById("rbac-panel");

    panel.querySelectorAll("[data-group]").forEach(btn => btn.addEventListener("click", async () => {
        await openGroup(Number(btn.dataset.group));
        panel.innerHTML = renderGroupsTab();
        bindGroupsTab();
    }));

    document.getElementById("rbac-new-group").addEventListener("click", async () => {
        const r = await Swal.fire({
            title: "Nuevo grupo", showCancelButton: true, confirmButtonText: "Crear",
            html: `<input id="rbac-g-name" class="swal2-input" placeholder="Nombre (ej: Comité editorial)" maxlength="100">
                   <input id="rbac-g-desc" class="swal2-input" placeholder="Descripción (opcional)" maxlength="255">`,
            preConfirm: () => {
                const name = document.getElementById("rbac-g-name").value.trim();
                if (!name) { Swal.showValidationMessage("El nombre es obligatorio"); return false; }
                return { name, description: document.getElementById("rbac-g-desc").value.trim() || null };
            },
        });
        if (!r.isConfirmed) return;
        try {
            const created = await apiFetch("/rbac/groups", { method: "POST", body: JSON.stringify(r.value) });
            await loadGroups();
            await openGroup(created.group.id);
            panel.innerHTML = renderGroupsTab();
            bindGroupsTab();
        } catch (err) {
            Swal.fire({ icon: "error", title: "No se creó el grupo", text: err.message });
        }
    });

    const detail = document.getElementById("rbac-group-detail");
    if (!activeGroupId || !groupDetail) return;

    detail.querySelectorAll("[data-member]").forEach(chk => chk.addEventListener("change", () => {
        const id = Number(chk.dataset.member);
        if (chk.checked) dirtyGroupMembers.add(id); else dirtyGroupMembers.delete(id);
    }));
    detail.querySelectorAll("[data-perm]").forEach(chk => chk.addEventListener("change", () => {
        const id = Number(chk.dataset.perm);
        if (chk.checked) dirtyGroupPerms.add(id); else dirtyGroupPerms.delete(id);
    }));

    document.getElementById("rbac-save-group-members").addEventListener("click", async () => {
        try {
            await apiFetch(`/rbac/groups/${activeGroupId}/members`, { method: "PUT", body: JSON.stringify({ user_ids: Array.from(dirtyGroupMembers) }) });
            await loadGroups();
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Integrantes guardados", timer: 1800, showConfirmButton: false });
        } catch (err) { Swal.fire({ icon: "error", title: "No se guardó", text: err.message }); }
    });

    document.getElementById("rbac-save-group-perms").addEventListener("click", async () => {
        try {
            await apiFetch(`/rbac/groups/${activeGroupId}/permissions`, { method: "PUT", body: JSON.stringify({ permission_ids: Array.from(dirtyGroupPerms) }) });
            await loadGroups();
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Permisos del grupo guardados", timer: 1800, showConfirmButton: false });
        } catch (err) { Swal.fire({ icon: "error", title: "No se guardó", text: err.message }); }
    });

    document.getElementById("rbac-delete-group").addEventListener("click", async () => {
        const r = await Swal.fire({ icon: "warning", title: `¿Eliminar «${groupDetail.group.name}»?`, text: "Sus integrantes perderán los permisos que solo tenían por este grupo.", showCancelButton: true, confirmButtonText: "Eliminar", confirmButtonColor: "#dc2626" });
        if (!r.isConfirmed) return;
        await apiFetch(`/rbac/groups/${activeGroupId}`, { method: "DELETE" });
        activeGroupId = null; groupDetail = null;
        await loadGroups();
        panel.innerHTML = renderGroupsTab();
        bindGroupsTab();
    });
}

/* =========================================================
   Pestaña Personas
   ========================================================= */

function renderPeopleTab() {
    const overridesByPerm = {};
    if (userResult) {
        userResult.overrides.forEach(o => { overridesByPerm[o.permission_id] = o.effect; });
    }

    return `
      <div class="card">
        <h3>Excepción para una persona específica</h3>
        <p class="rbac-hint">Busca a alguien para darle o quitarle un permiso puntual, sin cambiar su rol ni sus grupos. Ej: un estudiante al que se le delega retroalimentar SIA.</p>
        <form id="rbac-user-search" class="rbac-search">
          <input type="email" id="rbac-user-email" placeholder="correo institucional" value="${escapeHtml(userSearch)}" required>
          <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
      </div>
      ${userResult ? `
        <div class="card">
          <h4>${escapeHtml(userResult.user.name)} <small>(${escapeHtml(userResult.user.email)} · ${escapeHtml(ROLE_LABELS[userResult.user.role] || userResult.user.role)})</small></h4>
          <p class="rbac-hint">${userResult.effective.length} permiso(s) activo(s) en total (rol + grupos + excepciones).</p>
          <div class="rbac-accordion">${accordion("select", () => overridesByPerm)}</div>
          <button type="button" id="rbac-save-user" class="btn btn-primary rbac-save-sticky">Guardar excepciones</button>
        </div>` : ""}
    `;
}

async function findUserByEmail(email) {
    if (!allUsers) {
        const r = await apiFetch("/rbac/users-lite");
        allUsers = r.users;
    }
    return allUsers.find(u => u.email.toLowerCase() === email.toLowerCase());
}

function bindPeopleTab() {
    const panel = document.getElementById("rbac-panel");
    const form = document.getElementById("rbac-user-search");
    form.addEventListener("submit", async e => {
        e.preventDefault();
        userSearch = document.getElementById("rbac-user-email").value.trim();
        const found = await findUserByEmail(userSearch);
        if (!found) {
            userResult = null;
            Swal.fire({ icon: "warning", title: "No se encontró esa persona" });
        } else {
            userResult = await apiFetch(`/rbac/users/${found.id}`);
        }
        panel.innerHTML = renderPeopleTab();
        bindPeopleTab();
    });

    const saveBtn = document.getElementById("rbac-save-user");
    if (saveBtn) saveBtn.addEventListener("click", async () => {
        const overrides = [];
        panel.querySelectorAll("[data-override-perm]").forEach(sel => {
            if (sel.value) overrides.push({ permission_id: Number(sel.dataset.overridePerm), effect: sel.value });
        });
        try {
            await apiFetch(`/rbac/users/${userResult.user.id}`, { method: "PUT", body: JSON.stringify({ overrides }) });
            userResult = await apiFetch(`/rbac/users/${userResult.user.id}`);
            panel.innerHTML = renderPeopleTab();
            bindPeopleTab();
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Excepciones guardadas", timer: 1800, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: "error", title: "No se guardó", text: err.message });
        }
    });
}

/* =========================================================
   Orquestación
   ========================================================= */

function renderActiveTab() {
    if (activeTab === "roles") return renderRolesTab();
    if (activeTab === "groups") return renderGroupsTab();
    return renderPeopleTab();
}

function bindActiveTab() {
    if (activeTab === "roles") return bindRolesTab();
    if (activeTab === "groups") return bindGroupsTab();
    return bindPeopleTab();
}

async function render() {
    const [c, rp] = await Promise.all([
        apiFetch("/rbac/catalog"),
        apiFetch("/rbac/roles"),
    ]);
    catalog = c;
    roleMatrix = rp;
    dirtyRolePerms = null;
    await loadGroups();

    const content = `
      <div class="table-toolbar"><h2><i class="fas fa-user-shield"></i> Permisos (RBAC)</h2></div>
      <div class="card"><p class="rbac-hint">Como <b>Administrador del sistema</b> siempre tienes acceso total; esta pantalla no te afecta a ti. <b>Roles</b> define lo que puede hacer cada rol por defecto, <b>Grupos</b> junta personas de distintos roles para darles permisos en conjunto, y <b>Personas</b> ajusta una excepción puntual a alguien específico.</p></div>
      <div class="rbac-main-tabs" role="tablist">
        ${toolbarTab("roles", "Roles", "fa-user-tag")}
        ${toolbarTab("groups", "Grupos", "fa-people-group")}
        ${toolbarTab("people", "Personas", "fa-user")}
      </div>
      <div id="rbac-panel">${renderActiveTab()}</div>
    `;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
    bindTabs();
    bindActiveTab();
}

function bindTabs() {
    document.querySelectorAll("[data-tab]").forEach(btn => btn.addEventListener("click", async () => {
        activeTab = btn.dataset.tab;
        if (activeTab === "roles") dirtyRolePerms = null;
        if (activeTab === "people") { userResult = null; userSearch = ""; }
        document.querySelectorAll("[data-tab]").forEach(b => b.classList.toggle("btn-primary", b.dataset.tab === activeTab));
        document.querySelectorAll("[data-tab]").forEach(b => b.classList.toggle("btn-secondary", b.dataset.tab !== activeTab));
        document.getElementById("rbac-panel").innerHTML = renderActiveTab();
        bindActiveTab();
    }));
}

export const rbacModule = { init: render };
