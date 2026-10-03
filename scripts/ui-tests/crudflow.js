// CRUD de extremo a extremo por catálogo (Administrador): crear, buscar, editar, inactivar y activar. Uso: VP=desktop node crudflow.js
const { startRelays, connect, sleep } = require("./lib.js");
const H = require("./harness.js");

const VP = process.env.VP || "desktop";
const STAMP = Date.now().toString().slice(-6);
const log = (...a) => console.log("   ", ...a);
const CATALOGS = [
  ["/admin/faculties", "faculties"], ["/admin/programs", "programs"], ["/cats", "cats"], ["/areas", "areas"],
  ["/groups", "groups"], ["/coordinators", "coordinators"], ["/objectives", "objectives"], ["/results", "results"],
  ["/projects", "projects"], ["/products", "products"], ["/admin/seedbeds", "seedbeds"], ["/admin/users", "users"],
];

async function fillModal(page, token) {
  const fields = await page.$$eval("#crudModal input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]), #crudModal select, #crudModal textarea",
    (els) => els.filter((e) => e.offsetParent !== null).map((e) => ({ name: e.name || e.id, tag: e.tagName.toLowerCase(), type: e.type, multiple: e.multiple, required: e.required, value: e.value })));
  const filled = [];
  for (const f of fields) {
    const sel = `#crudModal [name="${f.name}"]`;
    try {
      if (f.tag === "select") {
        const opts = await page.$$eval(`${sel} option`, (o) => o.map((x) => x.value).filter((v) => v !== ""));
        if (f.name === "role" && opts.includes("ESTUDIANTE")) { await page.selectOption(sel, "ESTUDIANTE"); filled.push("role=ESTUDIANTE"); } else if (opts.length && (f.required || !f.value)) { if (f.multiple) await page.selectOption(sel, [opts[0]]); else if (!f.value || f.name !== "status") await page.selectOption(sel, opts[0]); filled.push(`${f.name}=${opts[0]}`); }
      } else if (/password/.test(f.type)) { /* opcional: se deja vacía */ }
      else if (f.type === "email" || /email|correo/i.test(f.name)) { await page.fill(sel, `qa_crud_${token}@example.invalid`); filled.push(f.name); }
      else if (f.type === "date") { await page.fill(sel, "2026-01-15"); filled.push(f.name); }
      else if (f.type === "number") { await page.fill(sel, "5"); filled.push(f.name); }
      else if (/phone|tel|celular/i.test(f.name) || f.type === "tel") { await page.fill(sel, "3001112233"); filled.push(f.name); }
      else if (/code|codigo|código/i.test(f.name)) { await page.fill(sel, `QA${token}`.slice(0, 12)); filled.push(f.name); }
      else if (/url|link|enlace|sitio/i.test(f.name)) { await page.fill(sel, "https://example.com/qa"); filled.push(f.name); }
      else if (f.type === "text" || f.tag === "textarea") {
        if (f.name === "authorization_reference") { await page.fill(sel, "Acta QA 001 de 2026"); }
        else await page.fill(sel, f.tag === "textarea" ? `QA registro de prueba automatizada ${token}. Texto suficientemente largo para validaciones.` : `QA ${f.name} ${token}`);
        filled.push(f.name);
      }
    } catch (e) { log(`  (campo ${f.name}: ${String(e.message).split("\n")[0]})`); }
  }
  return { fields: fields.length, filled };
}

async function swalText(page, ms = 4000) {
  const ok = await page.waitForSelector(".swal2-popup", { state: "visible", timeout: ms }).then(() => true).catch(() => false);
  return ok ? page.$eval(".swal2-popup", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 200)) : "";
}
const closeSwal = async (page) => { for (let i = 0; i < 3 && (await page.$(".swal2-popup")); i++) { const c = await page.$(".swal2-confirm"); if (c && (await c.isVisible())) await c.click().catch(() => {}); else await page.keyboard.press("Escape"); await sleep(600); } };

(async () => {
  startRelays();
  const browser = await connect();
  const { ctx, page } = await H.newSession(browser, VP, "ADMIN_SISTEMA", "/crud");
  await H.login(page, "ADMIN_SISTEMA");

  for (const [href, entity] of CATALOGS) {
    H.setLabel(`${VP}/crud/${entity}`);
    console.log(`\n== ${entity}`);
    const token = `${STAMP}${entity.slice(0, 2)}`;
    try {
      await page.goto("http://localhost:8080" + href, { waitUntil: "networkidle" }); await sleep(1200);
      const create = (await page.$(`#createBtn-${entity}`)) || (await page.$(`#createBtn-${entity}-empty`));
      if (!create) { H.report("CRUD", `${entity}: no hay botón de crear`); continue; }
      await create.click(); await sleep(900);
      page.__expect = (s) => s === 422;
      const { fields, filled } = await fillModal(page, token);
      log(`campos=${fields} rellenados=${filled.length}`);
      await page.click("#saveBtn"); await sleep(1800);
      page.__expect = null;
      const stillOpen = !!(await page.$("#crudModal"));
      let msg = await swalText(page, 1500);
      const banner = stillOpen ? await page.$eval("#formErrorText", (e) => e.textContent).catch(() => "") : "";
      const fieldErrs = stillOpen ? await page.$$eval("#crudModal .field-error-msg", (els) => els.map((e) => e.textContent.trim()).filter(Boolean)).catch(() => []) : [];
      log("crear ->", stillOpen ? `MODAL ABIERTO banner="${banner}" errores=${JSON.stringify(fieldErrs)}` : "modal cerrado", "| aviso:", msg || "(ninguno)");
      if (stillOpen) { H.report("CRUD", `${entity}: crear con datos válidos no guardó (${banner} ${fieldErrs.join("; ")})`); await page.keyboard.press("Escape"); await sleep(400); await closeSwal(page); continue; }
      if (/error|no se pudo/i.test(msg)) { H.report("CRUD", `${entity}: crear devolvió error: ${msg}`); await closeSwal(page); continue; }
      await closeSwal(page); await sleep(800);

      // buscar la fila creada
      await page.fill(`input[type=search]`, token).catch(() => {}); await sleep(700);
      const rows = await page.$$eval("table tbody tr", (r) => r.filter((x) => x.offsetParent !== null && x.innerText.trim() && !/No hay|Sin resultados|ningún/i.test(x.innerText)).length);
      log("filas que contienen el token tras crear:", rows);
      if (!rows) { H.report("CRUD", `${entity}: el registro creado no aparece en la tabla`); continue; }

      // editar
      const edit = await page.$(`table tbody tr .editBtn-${entity}`);
      if (!edit) { log("sin botón Editar (rol/estado)"); }
      else {
        await edit.click(); await sleep(900);
        const first = await page.$("#crudModal [name=name], #crudModal [name=title]");
        if (first) await first.fill(`${await first.inputValue()} ED`);
        page.__expect = (s) => s === 422;
        await page.click("#saveBtn"); await sleep(1500);
        page.__expect = null;
        const open2 = !!(await page.$("#crudModal"));
        msg = await swalText(page, 1500);
        log("editar ->", open2 ? "MODAL ABIERTO" : "ok", "|", msg || "(sin aviso)");
        if (open2) { H.report("CRUD", `${entity}: editar no guardó`); await page.keyboard.press("Escape"); await sleep(300); }
        await closeSwal(page); await sleep(600);
      }

      // inactivar y activar
      for (const [label, cls] of [["Inactivar", "toggleBtn"], ["Activar", "toggleBtn"]]) {
        const btn = await page.$(`table tbody tr button[title="${label}"]`);
        if (!btn) { log(`sin botón ${label}`); continue; }
        await btn.click(); await sleep(600);
        const ask = await swalText(page, 2500);
        const ok = await page.$(".swal2-confirm");
        // el modal de confirmación puede pedir un motivo (p. ej. semilleros)
        const reason = await page.$(".swal2-textarea:visible, .swal2-input:visible");
        if (reason) await reason.fill("Prueba automatizada de inactivación");
        if (ok) { await ok.click(); await sleep(1500); }
        const after = await swalText(page, 1500);
        log(`${label} ->`, ask.slice(0, 60), "=>", after.slice(0, 80) || "(sin aviso)");
        if (/error|no se pudo/i.test(after)) H.report("CRUD", `${entity}: ${label} devolvió «${after}»`);
        await closeSwal(page); await sleep(600);
      }
    } catch (e) {
      H.report("CRUD", `${entity}: ${String(e.message).split("\n")[0]}`);
      if (await page.$("#crudModal")) await page.keyboard.press("Escape");
      await closeSwal(page);
    }
  }
  H.finish({ vp: VP, stamp: STAMP });
  await ctx.close(); await browser.close(); process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
