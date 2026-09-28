/* =========================================================
   #archivo: /frontend/modules/sia-admin/sia-admin.module.js
   Panel de SIA para ADMIN_SISTEMA (RF17): consumo vs cupo, calificaciones,
   conversaciones (filtros), revisión, respuestas corregidas y límites.
   ========================================================= */

import { apiFetch }             from "../../services/api.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }           from "../../core/escape.js";

const FACE = { 1: "😞", 2: "😕", 3: "😐", 4: "🙂", 5: "😄" };
const LIMIT_LABELS = {
    guest_per_hour: "Visitante: preguntas por hora", guest_per_day: "Visitante: preguntas por día",
    user_per_day: "Usuario con sesión: preguntas por día", max_messages: "Preguntas por conversación",
    max_question_chars: "Largo máximo de la pregunta (caracteres)", max_answer_tokens: "Largo máximo de la respuesta (tokens)",
    global_requests_per_day: "Tope global: consultas por día", global_tokens_per_day: "Tope global: tokens por día",
    per_key_requests_per_day: "Tope por cuenta de Groq: consultas por día",
};
let filter = "", page = 1;

const pct = (a, b) => (b ? Math.min(100, Math.round(a / b * 100)) : 0);
const fmtDate = d => d ? new Date(d).toLocaleString("es-CO", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" }) : "—";
const md = t => escapeHtml(t).replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>").replace(/\n/g, "<br>");

function kpi(title, value, sub, bar) {
    return `<div class="card sia-kpi"><p class="sia-kpi-t">${title}</p><p class="sia-kpi-v">${value}</p>${sub ? `<p class="sia-kpi-s">${sub}</p>` : ""}${bar !== undefined ? `<div class="sia-bar" role="progressbar" aria-valuenow="${bar}" aria-valuemin="0" aria-valuemax="100"><span style="width:${bar}%" class="${bar >= 80 ? "is-hot" : ""}"></span></div>` : ""}</div>`;
}

async function render() {
    const [st, cv, kn, cfg] = await Promise.all([
        apiFetch("/sia/admin/stats"),
        apiFetch(`/sia/admin/conversations?page=${page}${filter ? `&filter=${filter}` : ""}`),
        apiFetch("/sia/admin/knowledge"),
        apiFetch("/sia/admin/settings"),
    ]);
    const t = st.today, c = st.conversations, dist = c.distribution || {};
    const maxD = Math.max(1, ...Object.values(dist));
    const rows = cv.data.map(x => `
      <tr data-id="${escapeHtml(x.id)}" class="sia-row" tabindex="0">
        <td data-label="Fecha">${fmtDate(x.created_at)}</td>
        <td data-label="Usuario">${x.user ? escapeHtml(x.user.name) + `<br><small>${escapeHtml(x.user.role)}</small>` : "<em>Visitante</em>"}</td>
        <td data-label="Pantalla">${escapeHtml(x.page || "—")}</td>
        <td data-label="Mensajes">${escapeHtml(x.messages_count)}</td>
        <td data-label="Calificación">${x.rating ? `<span title="${x.rating}/5">${FACE[x.rating]} ${x.rating}</span>` : (x.rating_skipped ? "Omitida" : (x.status === "OPEN" ? "Abierta" : "—"))}</td>
        <td data-label="Comentario">${x.feedback ? escapeHtml(x.feedback.slice(0, 80)) + (x.feedback.length > 80 ? "…" : "") : "—"}</td>
        <td data-label="Revisión">${x.reviewed_at ? "✅" : "Pendiente"}</td>
      </tr>`).join("");

    const content = `
      <div class="table-toolbar"><h2><i class="fas fa-robot"></i> SIA · Asistente</h2>
        <span class="sia-status ${cfg.configured ? "ok" : "bad"}">${cfg.configured ? `Activo · ${escapeHtml(cfg.model)} · ${escapeHtml(cfg.accounts)} cuenta(s)` : "Sin API key configurada"}</span></div>
      <div class="sia-kpis">
        ${kpi("Consultas hoy", `${t.requests} / ${t.requests_cap}`, `${t.errors} errores · ${t.avg_ms} ms promedio`, pct(t.requests, t.requests_cap))}
        ${kpi("Tokens hoy", `${t.tokens.toLocaleString("es-CO")} / ${t.tokens_cap.toLocaleString("es-CO")}`, "Cupo gratis de Groq: 1.000 consultas/día", pct(t.tokens, t.tokens_cap))}
        ${kpi("Este mes", `${st.month.requests} consultas`, `${st.month.tokens.toLocaleString("es-CO")} tokens`)}
        ${kpi("Calificación promedio", c.avg_rating ? `${FACE[Math.round(c.avg_rating)] || ""} ${c.avg_rating} / 5` : "—", `${c.rated} calificadas · ${c.skipped} omitidas`)}
        ${kpi("Por revisar", c.unreviewed, "mal calificadas o con comentario")}
        ${kpi("Respondidas sin API hoy", t.local, "saludos, identidad y respuestas corregidas")}
      </div>
      <div class="card sia-dist"><h3>Cuentas de Groq (rotación)</h3>
        ${(st.keys || []).map(k => `<div class="sia-dist-row"><span class="sia-acct">${escapeHtml(k.label)}</span><div class="sia-bar"><span style="width:${pct(k.requests, k.cap)}%" class="${pct(k.requests, k.cap) >= 80 ? "is-hot" : ""}"></span></div><b>${escapeHtml(k.requests)}/${escapeHtml(k.cap)}</b>${k.cooling ? ' <span class="sia-cool" title="En enfriamiento por límite o error">⏸</span>' : ""}</div>`).join("") || "<p class='sia-empty'>Sin cuentas configuradas.</p>"}
      </div>
      <div class="card sia-dist"><h3>Distribución de calificaciones</h3>
        ${[5, 4, 3, 2, 1].map(r => `<div class="sia-dist-row"><span>${FACE[r]} ${r}</span><div class="sia-bar"><span style="width:${Math.round((dist[r] || 0) / maxD * 100)}%"></span></div><b>${dist[r] || 0}</b></div>`).join("")}
      </div>
      <div class="card"><div class="sia-filters" role="group" aria-label="Filtrar conversaciones">
          ${[["", "Todas"], ["low", "Mal calificadas (≤2)"], ["feedback", "Con comentario"], ["unreviewed", "Sin revisar"]].map(([v, l]) => `<button type="button" class="btn btn-sm ${filter === v ? "btn-primary" : "btn-secondary"}" data-filter="${v}" aria-pressed="${filter === v}">${l}</button>`).join("")}
        </div>
        ${cv.data.length ? `<div class="table-responsive"><table class="mobile-card-table sia-table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Pantalla</th><th>Mensajes</th><th>Calificación</th><th>Comentario</th><th>Revisión</th></tr></thead><tbody>${rows}</tbody></table></div>` : `<p class="sia-empty">No hay conversaciones con este filtro.</p>`}
        <div class="sia-pager">${cv.prev_page_url ? `<button type="button" class="btn btn-sm btn-secondary" data-page="${cv.current_page - 1}">← Anterior</button>` : ""}<span>Página ${cv.current_page} de ${cv.last_page}</span>${cv.next_page_url ? `<button type="button" class="btn btn-sm btn-secondary" data-page="${cv.current_page + 1}">Siguiente →</button>` : ""}</div>
      </div>
      <div class="card"><h3>Respuestas corregidas <small>(SIA las usa como conocimiento verificado)</small></h3>
        <form id="sia-kn-form" class="sia-kn-form">
          <label for="kn-q">Pregunta</label><input id="kn-q" maxlength="300" required placeholder="Ej: ¿Cuál es el horario del CAT Kennedy?">
          <label for="kn-a">Respuesta correcta</label><textarea id="kn-a" rows="3" maxlength="3000" required></textarea>
          <button class="btn btn-primary" type="submit">Agregar al conocimiento de SIA</button>
        </form>
        <ul class="sia-kn-list">${kn.knowledge.map(k => `<li class="${k.active ? "" : "is-off"}"><div><strong>${escapeHtml(k.question)}</strong><p>${md(k.answer)}</p></div><button type="button" class="btn btn-sm ${k.active ? "btn-warning" : "btn-success"}" data-kn="${escapeHtml(k.id)}" data-active="${k.active ? 1 : 0}">${k.active ? "Desactivar" : "Activar"}</button></li>`).join("") || "<li class='sia-empty'>Aún no hay respuestas corregidas.</li>"}</ul>
      </div>
      <div class="card"><h3>Límites anti-abuso</h3>
        <form id="sia-cfg-form" class="sia-cfg-form">
          ${Object.entries(LIMIT_LABELS).map(([k, l]) => `<label>${l}<input type="number" min="1" name="${k}" value="${escapeHtml(cfg.settings[k])}" required></label>`).join("")}
          <label class="sia-switch"><input type="checkbox" name="enabled" ${cfg.settings.enabled ? "checked" : ""}> SIA habilitado</label>
          <button class="btn btn-primary" type="submit">Guardar límites</button>
        </form>
      </div>`;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
    bind();
}

async function openConversation(id) {
    const { conversation: c } = await apiFetch(`/sia/admin/conversations/${encodeURIComponent(id)}`);
    const firstQ = (c.messages.find(m => m.role === "user") || {}).content || "";
    const msgs = c.messages.map(m => `<div class="sia-msg sia-msg-${m.role === "user" ? "u" : "a"}">${m.status === "ERROR" ? "<em>(error del modelo)</em>" : md(m.content)}${m.role === "assistant" && m.status === "OK" ? `<small class="sia-meta">${m.prompt_tokens}+${m.completion_tokens} tokens · ${m.latency_ms} ms</small>` : ""}</div>`).join("");
    const r = await Swal.fire({
        title: "Conversación con SIA", width: 720, showCancelButton: true, confirmButtonText: "Guardar revisión", cancelButtonText: "Cerrar",
        html: `<div class="sia-admin-detail">
            <p><b>${c.user ? escapeHtml(c.user.name) + " (" + escapeHtml(c.user.role) + ")" : "Visitante"}</b> · ${fmtDate(c.created_at)} · ${escapeHtml(c.page || "")}</p>
            <p>Calificación: ${c.rating ? FACE[c.rating] + " " + c.rating + "/5" : (c.rating_skipped ? "omitida" : "sin calificar")}</p>
            ${c.feedback ? `<blockquote>${md(c.feedback)}</blockquote>` : ""}
            <div class="sia-body sia-admin-log">${msgs}</div>
            <label for="sia-note">Nota de revisión</label>
            <textarea id="sia-note" rows="3" maxlength="2000">${escapeHtml(c.admin_note || "")}</textarea>
            <label class="sia-switch"><input type="checkbox" id="sia-to-kn"> Crear una respuesta corregida con esta pregunta</label>
          </div>`,
        preConfirm: () => ({ note: document.getElementById("sia-note").value, kn: document.getElementById("sia-to-kn").checked }),
    });
    if (!r.isConfirmed) return;
    await apiFetch(`/sia/admin/conversations/${encodeURIComponent(id)}/review`, { method: "PUT", body: JSON.stringify({ admin_note: r.value.note || null }) });
    if (r.value.kn) {
        document.getElementById("kn-q").value = firstQ.slice(0, 300);
        document.getElementById("kn-a").focus();
    }
    Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Revisión guardada", timer: 1800, showConfirmButton: false });
    if (!r.value.kn) render();
}

function bind() {
    const app = document.getElementById("app");
    app.querySelectorAll("[data-filter]").forEach(b => b.addEventListener("click", () => { filter = b.dataset.filter; page = 1; render(); }));
    app.querySelectorAll("[data-page]").forEach(b => b.addEventListener("click", () => { page = Number(b.dataset.page); render(); }));
    app.querySelectorAll(".sia-row").forEach(tr => {
        tr.addEventListener("click", () => openConversation(tr.dataset.id));
        tr.addEventListener("keydown", e => { if (e.key === "Enter") openConversation(tr.dataset.id); });
    });
    app.querySelectorAll("[data-kn]").forEach(b => b.addEventListener("click", async () => {
        await apiFetch(`/sia/admin/knowledge/${encodeURIComponent(b.dataset.kn)}`, { method: "PUT", body: JSON.stringify({ active: b.dataset.active !== "1" }) });
        render();
    }));
    document.getElementById("sia-kn-form").addEventListener("submit", async e => {
        e.preventDefault();
        await apiFetch("/sia/admin/knowledge", { method: "POST", body: JSON.stringify({ question: document.getElementById("kn-q").value.trim(), answer: document.getElementById("kn-a").value.trim() }) });
        Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Agregado al conocimiento de SIA", timer: 1800, showConfirmButton: false });
        render();
    });
    document.getElementById("sia-cfg-form").addEventListener("submit", async e => {
        e.preventDefault();
        const f = new FormData(e.target), body = {};
        Object.keys(LIMIT_LABELS).forEach(k => { body[k] = Number(f.get(k)); });
        body.enabled = f.get("enabled") === "on";
        try {
            await apiFetch("/sia/admin/settings", { method: "PUT", body: JSON.stringify(body) });
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Límites actualizados", timer: 1800, showConfirmButton: false });
        } catch (err) { Swal.fire({ icon: "error", title: "No se guardó", text: err.message }); }
    });
}

export const siaAdminModule = { init: render };
