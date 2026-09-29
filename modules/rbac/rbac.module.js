/* =========================================================
   #archivo: /frontend/modules/rbac/rbac.module.js
   Panel RBAC granular (solo ADMIN_SISTEMA): matriz rol × permiso por
   módulo/acción, y excepciones por persona (grant/revoke) que ganan sobre
   el rol. Ver api/config/rbac.php y App\Services\Rbac\PermissionResolver.
   ========================================================= */

import { apiFetch }             from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }           from "../../core/escape.js";

const ROLE_LABELS = {
    ADMINISTRATIVO: "Administrativo",
    LIDER_SEMILLERO: "Líder de semillero",
    ESTUDIANTE: "Estudiante",
};

let catalog = null;      // { modules: {mod: {label, actions:[{id,action,label}]}}, roles }
let roleMatrix = null;   // { roles, matrix: { role: [permission_id,...] } }
let activeRole = "ADMINISTRATIVO";
let userSearch = "";
let userResult = null;   // { user, effective, overrides }
let dirtyRolePerms = null; // Set<number> mientras se edita, antes de guardar

function moduleBlock(moduleKey, def, selected) {
    const rows = def.actions.map(a => `
        <label class="rbac-perm">
            <input type="checkbox" data-perm="${a.id}" ${selected.has(a.id) ? "checked" : ""}>
            ${escapeHtml(a.label || a.action)}
        </label>`).join("");
    return `<div class="card rbac-module"><h4>${escapeHtml(def.label || moduleKey)}</h4><div class="rbac-perms">${rows}</div></div>`;
}

function renderRoleTab() {
    const selected = dirtyRolePerms ?? new Set((roleMatrix.matrix[activeRole] || []));
    dirtyRolePerms = selected;

    const blocks = Object.entries(catalog.modules)
        .map(([mod, def]) => moduleBlock(mod, def, selected))
        .join("");

    return `
      <div class="rbac-tabs" role="tablist">
        ${roleMatrix.roles.map(r => `<button type="button" class="btn btn-sm ${r === activeRole ? "btn-primary" : "btn-secondary"}" data-role-tab="${r}">${escapeHtml(ROLE_LABELS[r] || r)}</button>`).join("")}
      </div>
      <p class="rbac-hint">Estos son los permisos por defecto de <b>${escapeHtml(ROLE_LABELS[activeRole] || activeRole)}</b>. ADMIN_SISTEMA no aparece aquí: siempre tiene acceso total.</p>
      <div class="rbac-grid">${blocks}</div>
      <button type="button" id="rbac-save-role" class="btn btn-primary">Guardar permisos de ${escapeHtml(ROLE_LABELS[activeRole] || activeRole)}</button>
    `;
}

function renderUserPanel() {
    const overridesByPerm = {};
    if (userResult) {
        userResult.overrides.forEach(o => { overridesByPerm[o.permission_id] = o.effect; });
    }

    const blocks = userResult ? Object.entries(catalog.modules).map(([mod, def]) => {
        const rows = def.actions.map(a => {
            const eff = overridesByPerm[a.id] || "";
            return `<div class="rbac-perm rbac-perm-override">
                <span>${escapeHtml(a.label || a.action)}</span>
                <select data-override-perm="${a.id}">
                    <option value="" ${eff === "" ? "selected" : ""}>Según su rol</option>
                    <option value="grant" ${eff === "grant" ? "selected" : ""}>Permitir (excepción)</option>
                    <option value="revoke" ${eff === "revoke" ? "selected" : ""}>Quitar (excepción)</option>
                </select>
            </div>`;
        }).join("");
        return `<div class="card rbac-module"><h4>${escapeHtml(def.label || mod)}</h4><div class="rbac-perms">${rows}</div></div>`;
    }).join("") : "";

    return `
      <div class="card">
        <h3>Excepciones por persona</h3>
        <p class="rbac-hint">Busca a alguien para darle o quitarle un permiso puntual, sin cambiar su rol. Ej: un estudiante al que se le delega retroalimentar SIA.</p>
        <form id="rbac-user-search" class="rbac-search">
          <input type="email" id="rbac-user-email" placeholder="correo institucional" value="${escapeHtml(userSearch)}" required>
          <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
      </div>
      ${userResult ? `
        <div class="card">
          <h4>${escapeHtml(userResult.user.name)} <small>(${escapeHtml(userResult.user.email)} · ${escapeHtml(ROLE_LABELS[userResult.user.role] || userResult.user.role)})</small></h4>
          <div class="rbac-grid">${blocks}</div>
          <button type="button" id="rbac-save-user" class="btn btn-primary">Guardar excepciones</button>
        </div>` : ""}
    `;
}

async function findUserByEmail(email) {
    /* No hay endpoint de búsqueda por email; se reusa /users (paginado) y se
       filtra en cliente — el listado de usuarios de este sistema es chico. */
    const { users } = await apiFetch("/users");
    return (users || []).find(u => u.email.toLowerCase() === email.toLowerCase());
}

async function render() {
    const [c, rp] = await Promise.all([
        apiFetch("/rbac/catalog"),
        apiFetch("/rbac/roles"),
    ]);
    catalog = c;
    roleMatrix = rp;
    dirtyRolePerms = null;

    const content = `
      <div class="table-toolbar"><h2><i class="fas fa-user-shield"></i> Permisos (RBAC)</h2></div>
      <div class="card"><p class="rbac-hint">Como <b>Administrador del sistema</b> siempre tienes acceso total; esta pantalla no te afecta a ti. Aquí controlas qué puede ver y hacer cada rol, y puedes darle o quitarle un permiso puntual a una persona específica.</p></div>
      <div id="rbac-role-panel">${renderRoleTab()}</div>
      <div id="rbac-user-panel">${renderUserPanel()}</div>
    `;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
    bindRolePanel();
    bindUserPanel();
}

function bindRolePanel() {
    const panel = document.getElementById("rbac-role-panel");
    panel.querySelectorAll("[data-role-tab]").forEach(btn => btn.addEventListener("click", () => {
        activeRole = btn.dataset.roleTab;
        dirtyRolePerms = null;
        panel.innerHTML = renderRoleTab();
        bindRolePanel();
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

function bindUserPanel() {
    const form = document.getElementById("rbac-user-search");
    form.addEventListener("submit", async e => {
        e.preventDefault();
        userSearch = document.getElementById("rbac-user-email").value.trim();
        const found = await findUserByEmail(userSearch);
        if (!found) {
            userResult = null;
            Swal.fire({ icon: "warning", title: "No se encontró esa persona" });
            document.getElementById("rbac-user-panel").innerHTML = renderUserPanel();
            bindUserPanel();
            return;
        }
        userResult = await apiFetch(`/rbac/users/${found.id}`);
        document.getElementById("rbac-user-panel").innerHTML = renderUserPanel();
        bindUserPanel();
    });

    const saveBtn = document.getElementById("rbac-save-user");
    if (saveBtn) saveBtn.addEventListener("click", async () => {
        const overrides = [];
        document.querySelectorAll("[data-override-perm]").forEach(sel => {
            if (sel.value) overrides.push({ permission_id: Number(sel.dataset.overridePerm), effect: sel.value });
        });
        try {
            await apiFetch(`/rbac/users/${userResult.user.id}`, {
                method: "PUT",
                body: JSON.stringify({ overrides }),
            });
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Excepciones guardadas", timer: 1800, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: "error", title: "No se guardó", text: err.message });
        }
    });
}

export const rbacModule = { init: render };
