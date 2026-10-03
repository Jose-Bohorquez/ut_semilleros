// Prueba de TODOS los botones/controles de cada pantalla: abre cada modal, lo valida y lo cierra sin confirmar acciones.
// Uso: VP=desktop ROLES=ADMIN_SISTEMA node buttons.js
const { startRelays, connect, sleep, BASE } = require("./lib.js");
const H = require("./harness.js");

const ROUTES = {
  ADMIN_SISTEMA: ["/dashboard", "/admin/seedbeds", "/objectives", "/projects", "/products", "/results", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/admin/faculties", "/admin/programs", "/cats", "/areas", "/admin/users", "/audits", "/admin/sia", "/admin/rbac", "/notifications", "/profile"],
  ADMINISTRATIVO: ["/dashboard", "/admin/seedbeds", "/objectives", "/projects", "/products", "/results", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/notifications", "/profile"],
  LIDER_SEMILLERO: ["/dashboard", "/admin/seedbeds", "/objectives", "/results", "/projects", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/notifications", "/profile"],
  ESTUDIANTE: ["/dashboard", "/seedbeds", "/requests", "/proposals", "/notifications", "/profile"],
};

const SKIP = /cerrar sesi|logout|themeToggle|hamburger|sia-|Reportar bug/i;

async function listControls(page) {
  return page.evaluate(() => {
    const root = document.querySelector("#content") || document.body;
    const els = [...root.querySelectorAll("button, a.btn, [role=button], input[type=submit], a[data-link].btn, .dataTables_paginate a, .tab, [data-tab]")]
      .filter((e) => e.offsetParent !== null && !e.disabled);
    const seen = new Set(), out = [];
    els.forEach((e) => {
      const label = (e.textContent || e.title || e.getAttribute("aria-label") || e.value || "").trim().replace(/\s+/g, " ").slice(0, 40);
      const sig = e.tagName + "|" + label + "|" + (e.id || "") + "|" + String(e.className).split(" ").filter((c) => !/^(dt|paginate|current|odd|even)/.test(c)).join(".");
      if (seen.has(sig)) return;
      seen.add(sig);
      // índice dentro de su grupo de firma para volver a localizarlo
      out.push({ sig, label, id: e.id || "", idx: [...root.querySelectorAll(e.tagName.toLowerCase())].indexOf(e), tag: e.tagName.toLowerCase() });
    });
    return out.slice(0, 40);
  });
}

async function clickBySig(page, sig) {
  return page.evaluateHandle((s) => {
    const root = document.querySelector("#content") || document.body;
    const els = [...root.querySelectorAll("button, a.btn, [role=button], input[type=submit], a[data-link].btn, .dataTables_paginate a, .tab, [data-tab]")].filter((e) => e.offsetParent !== null && !e.disabled);
    return els.find((e) => {
      const label = (e.textContent || e.title || e.getAttribute("aria-label") || e.value || "").trim().replace(/\s+/g, " ").slice(0, 40);
      const sg = e.tagName + "|" + label + "|" + (e.id || "") + "|" + String(e.className).split(" ").filter((c) => !/^(dt|paginate|current|odd|even)/.test(c)).join(".");
      return sg === s;
    }) || null;
  }, sig);
}

async function swalInfo(page) {
  return page.evaluate(() => {
    const p = document.querySelector(".swal2-popup");
    if (!p || p.offsetParent === null && getComputedStyle(p).display === "none") return null;
    const b = p.getBoundingClientRect();
    return {
      title: (p.querySelector(".swal2-title") || {}).textContent || "",
      inputs: [...p.querySelectorAll("input:not([type=hidden]), select, textarea")].filter((i) => i.offsetParent !== null).map((i) => (i.id || i.name || i.type)).slice(0, 12),
      confirm: !!p.querySelector(".swal2-confirm") && p.querySelector(".swal2-confirm").offsetParent !== null,
      cancel: !!p.querySelector(".swal2-cancel") && p.querySelector(".swal2-cancel").offsetParent !== null,
      w: Math.round(b.width), h: Math.round(b.height), top: Math.round(b.top), left: Math.round(b.left), right: Math.round(b.right),
      vw: innerWidth, vh: innerHeight,
      scrollable: p.scrollHeight > p.clientHeight || !!p.closest(".swal2-container") && p.closest(".swal2-container").scrollHeight > p.closest(".swal2-container").clientHeight,
      text: (p.innerText || "").slice(0, 160).replace(/\s+/g, " "),
    };
  });
}

async function crudInfo(page) {
  return page.evaluate(() => {
    const m = document.getElementById("crudModal");
    if (!m) return null;
    const box = m.querySelector(".crudModalBox") || m;
    const b = box.getBoundingClientRect();
    return {
      title: ((m.querySelector("#crudModalTitle") || {}).textContent || "").trim().replace(/\s+/g, " ").slice(0, 40),
      fields: m.querySelectorAll("input:not([type=hidden]), select, textarea").length,
      w: Math.round(b.width), h: Math.round(b.height), left: Math.round(b.left), right: Math.round(b.right), vw: innerWidth, vh: innerHeight,
      scrollable: box.scrollHeight > box.clientHeight || m.scrollHeight > m.clientHeight || /(auto|scroll)/.test(getComputedStyle(box).overflowY) || /(auto|scroll)/.test(getComputedStyle(m).overflowY),
    };
  });
}

async function closeCrud(page) {
  for (let i = 0; i < 2 && (await page.$("#crudModal")); i++) {
    const b = await page.$("#closeModalBtn");
    if (b && (await b.isVisible())) await b.click().catch(() => {}); else await page.keyboard.press("Escape");
    await sleep(400);
  }
}

async function closeSwal(page) {
  for (let i = 0; i < 3; i++) {
    const open = await page.$(".swal2-popup");
    if (!open) return true;
    const cancel = await page.$(".swal2-cancel");
    if (cancel && (await cancel.isVisible())) await cancel.click().catch(() => {});
    else { const x = await page.$(".swal2-close"); if (x && (await x.isVisible())) await x.click().catch(() => {}); else await page.keyboard.press("Escape"); }
    await sleep(500);
  }
  return !(await page.$(".swal2-popup"));
}

(async () => {
  startRelays();
  const browser = await connect();
  const vp = process.env.VP || "desktop";
  const roles = (process.env.ROLES || Object.keys(ROUTES).join(",")).split(",");
  let controls = 0;
  for (const role of roles) {
    const { ctx, page } = await H.newSession(browser, vp, role, "/botones");
    console.log(`\n### botones ${vp} / ${role}`);
    await H.login(page, role);
    for (const href of ROUTES[role]) {
      if (await page.$("#crudModal")) await closeCrud(page);
      if (await page.$(".swal2-popup")) await closeSwal(page);
      try { await H.go(page, href); } catch (e) { H.report("NAV", `${href}: ${String(e.message).split("\n")[0]}`); continue; }
      const list = await listControls(page);
      for (const c of list) {
        if (SKIP.test(c.label) || SKIP.test(c.id)) continue;
        const exportBtn = /^(Copiar|Excel|PDF|Imprimir|Exportar CSV)$/.test(c.label);
        const handle = await clickBySig(page, c.sig);
        const el = handle.asElement();
        if (!el) continue;
        controls++;
        const urlBefore = page.url();
        let dl = null; const onDl = (d) => { dl = d; }; page.once("download", onDl);
        const popup = ctx.waitForEvent("page", { timeout: 1200 }).catch(() => null);
        try { await el.scrollIntoViewIfNeeded({ timeout: 2000 }); await el.click({ timeout: 4000 }); }
        catch (e) { H.report("CLICK", `${href} «${c.label}» no se pudo pulsar: ${String(e.message).split("\n")[0]}`); continue; }
        await sleep(exportBtn ? 1500 : 900);
        const np = await popup; if (np) { await np.close().catch(() => {}); }
        const sw = await swalInfo(page);
        const tag = `${href} «${c.label || c.id}»`;
        const crud = await crudInfo(page);
        if (crud) {
          console.log(`  modal-crud ${tag}: "${crud.title}" campos=${crud.fields} ${crud.w}x${crud.h}`);
          if (crud.left < -1 || crud.right > crud.vw + 1) H.report("MODAL-FUERA", `${tag} se sale horizontalmente (${crud.left}..${crud.right} de ${crud.vw})`);
          if (crud.h > crud.vh && !crud.scrollable) H.report("MODAL-ALTO", `${tag} más alto que la pantalla (${crud.h}>${crud.vh}) y sin scroll`);
          if (/^Crear/i.test(c.label) && (await page.$("#saveBtn"))) {
            page.__expect = (s) => s === 422;
            await page.click("#saveBtn").catch(() => {}); await sleep(900);
            const msg = await page.$eval("#formErrorText", (e) => e.textContent).catch(() => "");
            const inline = await page.$$eval("#crudModal [id^=err-]", (els) => els.map((e) => e.textContent.trim()).filter(Boolean).length).catch(() => 0);
            page.__expect = null;
            console.log(`     guardar vacío -> banner="${msg.slice(0, 70)}" errores_en_campos=${inline}`);
            if (!msg && !inline) H.report("VALID", `${tag}: guardar vacío no muestra ningún error`);
          }
          await closeCrud(page);
          if (await page.$("#crudModal")) H.report("MODAL-NO-CIERRA", `${tag} (modal CRUD) no se pudo cerrar`);
        } else if (sw) {
          console.log(`  modal ${tag}: "${sw.title.slice(0, 40)}" inputs=${sw.inputs.length} ${sw.w}x${sw.h}`);
          if (sw.left < -1 || sw.right > sw.vw + 1) H.report("MODAL-FUERA", `${tag} se sale horizontalmente (${sw.left}..${sw.right} de ${sw.vw})`);
          if (sw.h > sw.vh && !sw.scrollable) H.report("MODAL-ALTO", `${tag} más alto que la pantalla (${sw.h}>${sw.vh}) y sin scroll`);
          if (!sw.confirm && !sw.cancel) { const x = await page.$(".swal2-close"); if (!x) H.report("MODAL-SIN-SALIDA", `${tag} no tiene botón para cerrar`); }
          // «Crear/Nuevo…»: enviar vacío debe validar sin romper
          if (/^(Crear|Nueva?|Registrar|Agregar|Importar)/i.test(c.label) && sw.confirm && sw.inputs.length) {
            const ok = await page.$(".swal2-confirm");
            await ok.click().catch(() => {}); await sleep(900);
            const after = await swalInfo(page);
            const val = await page.$eval(".swal2-validation-message", (e) => e.textContent).catch(() => "");
            console.log(`     enviar vacío -> ${after ? "modal sigue abierto" : "modal cerrado"}${val ? ` (${val.slice(0, 60)})` : ""}`);
          }
          const closed = await closeSwal(page);
          if (!closed) H.report("MODAL-NO-CIERRA", `${tag} no se pudo cerrar`);
        } else if (dl) {
          console.log(`  descarga ${tag}: ${dl.suggestedFilename()}`);
        } else if (page.url() !== urlBefore) {
          console.log(`  navega ${tag} -> ${page.url().replace(BASE, "")}`);
          await H.go(page, href);
        } else if (exportBtn && /Copiar|Imprimir/.test(c.label)) {
          // copiar/imprimir no producen modal visible; solo verificamos que no rompan
        }
        // cualquier overlay colgado
        if (await page.$(".swal2-container.swal2-backdrop-show")) await closeSwal(page);
        if (await page.$("#crudModal")) await closeCrud(page);
      }
      await H.checkScreen(page, `${role}${href} (tras botones)`, null);
    }
    await ctx.close();
  }
  console.log(`\ncontroles pulsados: ${controls}`);
  H.finish({ vp, controls });
  await browser.close();
  process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
