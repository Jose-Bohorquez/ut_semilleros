// Flujos funcionales de extremo a extremo (cada escenario es independiente y reporta sus fallos).
// Uso: VP=desktop node flows.js   (VP=mobile repite los flujos del estudiante en teléfono)
const { startRelays, connect, sleep, BASE } = require("./lib.js");
const H = require("./harness.js");

const VP = process.env.VP || "desktop";
const STAMP = Date.now().toString().slice(-6);
const log = (...a) => console.log("   ", ...a);

const swalOpen = (page, ms = 5000) => page.waitForSelector(".swal2-popup", { state: "visible", timeout: ms }).then(() => true).catch(() => false);
const swalText = (page) => page.$eval(".swal2-popup", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 220)).catch(() => "");
const swalClick = async (page, which) => { await page.click(`.swal2-${which}`, { timeout: 4000 }); await sleep(700); };
const swalClose = async (page) => { for (let i = 0; i < 3 && (await page.$(".swal2-popup")); i++) { const b = (await page.$(".swal2-confirm")) ; if (b && await b.isVisible()) await b.click().catch(() => {}); else await page.keyboard.press("Escape"); await sleep(500); } };
const settle = async (page, ms = 1200) => { await page.waitForLoadState("networkidle").catch(() => {}); await sleep(ms); };

async function scenario(name, fn) {
  H.setLabel(`${VP}/${name}`);
  console.log(`\n== ${name}`);
  try { await fn(); } catch (e) { H.report("FLOW", `${name}: ${String(e.message).split("\n")[0]}`); }
}

(async () => {
  startRelays();
  const browser = await connect();
  const est = await H.newSession(browser, VP, "ESTUDIANTE", "/flujo");
  const lid = await H.newSession(browser, "desktop", "LIDER_SEMILLERO", "/flujo");
  const adm = await H.newSession(browser, "desktop", "ADMINISTRATIVO", "/flujo");
  const sis = await H.newSession(browser, "desktop", "ADMIN_SISTEMA", "/flujo");
  for (const [s, r] of [[est, "ESTUDIANTE"], [lid, "LIDER_SEMILLERO"], [adm, "ADMINISTRATIVO"], [sis, "ADMIN_SISTEMA"]]) await H.login(s.page, r);
  const E = est.page, L = lid.page, A = adm.page, S = sis.page;
  const shot = (p, n) => p.screenshot({ path: `/t/out/shots/flow_${VP}_${n}.png`, fullPage: true }).catch(() => {});

  /* ───────── ESTUDIANTE: explorar semilleros y solicitar ingreso (CU16-CU22) ───────── */
  await scenario("est-explorar-semilleros", async () => {
    await H.go(E, "/seedbeds");
    const cards = await E.$$(".faculty-card");
    log("facultades:", cards.length);
    if (!cards.length) throw new Error("no hay tarjetas de facultad");
    await cards[0].click(); await settle(E, 800);
    const seeds = await E.$$(".seedbed-card");
    log("semilleros en la primera facultad:", seeds.length);
    await shot(E, "est_facultad");
    const back = await E.$("#backToFaculties"); if (back) { await back.click(); await sleep(500); }
    // buscador
    await E.fill("#seedbedSearch", "IA y Machine"); await sleep(800);
    const found = await E.$$("#searchResults .seedbed-card");
    log("búsqueda 'IA y Machine':", found.length);
    if (!found.length) throw new Error("la búsqueda no encontró el semillero");
    await E.fill("#seedbedSearch", "zzzxxyy"); await sleep(700);
    log("búsqueda sin resultados:", (await E.$eval("#searchResults", (e) => e.innerText.trim().slice(0, 60)).catch(() => "?")));
    await E.fill("#seedbedSearch", "IA y Machine"); await sleep(700);
    await (await E.$("#searchResults .seedbed-card")).click(); await sleep(1500);
    const detail = await E.$eval("#seedbedDetail", (e) => getComputedStyle(e).display).catch(() => "none");
    if (detail === "none") throw new Error("el detalle no se abre");
    for (const tab of await E.$$("#detailTabPanes [data-tab], #seedbedDetail [data-tab]")) { await tab.click().catch(() => {}); await sleep(400); }
    await shot(E, "est_detalle");
  });

  await scenario("est-solicitud-validaciones-y-envio", async () => {
    // el detalle sigue abierto del escenario anterior
    const joinBtn = await E.$("#joinSeedbedBtn");
    if (!joinBtn) { log("sin botón de unirse (ya hay solicitud o no disponible)"); return; }
    await joinBtn.click(); await sleep(500);
    E.__expect = (s) => s === 422;
    await E.click("#submitJoinBtn"); await sleep(900);
    const errs = await E.$$eval("#joinSeedbedForm .pwa-field-error", (els) => els.map((e) => e.textContent.trim()).filter(Boolean));
    log("errores al enviar vacío:", JSON.stringify(errs));
    if (errs.length < 2) H.report("VALID", "enviar solicitud vacía no muestra errores por campo");
    // teléfono inválido
    await E.fill("#join-phone", "abc"); await E.fill("#join-message", "corto"); await E.click("#submitJoinBtn"); await sleep(900);
    log("tras datos inválidos:", JSON.stringify(await E.$$eval("#joinSeedbedForm .pwa-field-error, #joinFormError", (els) => els.map((e) => e.textContent.trim()).filter(Boolean))));
    E.__expect = null;
    const opt = await E.$$eval("#join-program option", (o) => o.filter((x) => x.value).map((x) => x.value));
    await E.selectOption("#join-program", opt[0]);
    await E.fill("#join-phone", "3105550123");
    await E.fill("#join-message", "Quiero unirme porque me interesa la investigación aplicada y puedo dedicar tiempo semanal.");
    await shot(E, "est_form_solicitud");
    await E.click("#submitJoinBtn"); await sleep(2000);
    const t = (await swalOpen(E, 2500)) ? await swalText(E) : await E.$eval("#membershipSection", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 160)).catch(() => "");
    log("resultado del envío:", t);
    await swalClose(E);
    await shot(E, "est_tras_solicitud");
  });

  await scenario("est-mis-solicitudes", async () => {
    const close = await E.$("#closeDetail"); if (close) await close.click().catch(() => {});
    await H.go(E, "/requests"); await settle(E);
    const txt = await E.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
    log("Mis solicitudes:", txt.slice(0, 260));
    if (!/Pendiente/i.test(txt)) H.report("FLOW", "la solicitud enviada no aparece como Pendiente en «Mis solicitudes»");
    await shot(E, "est_mis_solicitudes");
  });

  /* ───────── ESTUDIANTE: registrar propuesta (CU25) ───────── */
  await scenario("est-propuesta", async () => {
    await H.go(E, "/proposals"); await settle(E);
    const nb = (await E.$("#newProposalBtn")) || (await E.$("#emptyNewProposalBtn"));
    if (!nb) throw new Error("no hay botón para registrar propuesta");
    await nb.click(); await sleep(700);
    E.__expect = (s) => s === 422;
    await E.click("#saveProposalBtn"); await sleep(900);
    const errs = await E.$$eval("#proposalForm [id^=err-prop]", (els) => els.map((e) => e.textContent.trim()).filter(Boolean));
    log("errores al enviar vacío:", JSON.stringify(errs));
    if (errs.length < 3) H.report("VALID", "propuesta vacía no valida todos los campos");
    E.__expect = null;
    const prog = await E.$$eval("#prop-program option", (o) => o.filter((x) => x.value).map((x) => x.value));
    await E.selectOption("#prop-program", prog[0]);
    const area = await E.$("#prop-areas input[type=checkbox]"); if (area) await area.check();
    await E.fill("#prop-title", `Idea QA ${STAMP}: monitoreo de calidad del agua con sensores`);
    await E.fill("#prop-desc", "Se propone un sistema de sensores de bajo costo para medir calidad del agua en veredas del Tolima, con tablero público.");
    await E.fill("#prop-phone", "3105550199");
    await shot(E, "est_form_propuesta");
    await E.click("#saveProposalBtn"); await sleep(2200);
    log("tras guardar:", (await swalOpen(E, 1500)) ? await swalText(E) : "(sin modal)");
    await swalClose(E); await settle(E);
    const list = await E.$eval("#proposalsList", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
    log("lista:", list.slice(0, 200));
    if (!list.includes(`Idea QA ${STAMP}`)) H.report("FLOW", "la propuesta creada no aparece en «Mis propuestas»");
    await shot(E, "est_mis_propuestas");
  });

  /* ───────── LÍDER: responder la solicitud (CU24) ───────── */
  await scenario("lider-aprobar-solicitud", async () => {
    await H.go(L, "/requests"); await settle(L);
    const rows = await L.$$(".viewRequestBtn");
    log("solicitudes pendientes visibles al líder:", rows.length);
    // el filtro por defecto es PENDIENTE; buscar la del estudiante QA
    const target = await L.$("tr:has-text('qa_e2e_est') .viewRequestBtn");
    if (!target) throw new Error("el líder no ve la solicitud del estudiante QA");
    await target.click();
    if (!(await swalOpen(L))) throw new Error("no abre el detalle");
    log("detalle:", (await swalText(L)).slice(0, 180));
    await shot(L, "lider_detalle_solicitud");
    await swalClick(L, "deny");                                 // Rechazar sin motivo → debe exigirlo (E1)
    if (await swalOpen(L)) {
      const ok = await L.$(".swal2-confirm"); if (ok) { await ok.click(); await sleep(700); }
      log("rechazar sin motivo:", (await L.$eval(".swal2-validation-message", (e) => e.textContent).catch(() => "(sin mensaje)")));
      await swalClick(L, "cancel");
    }
    await swalClose(L); await sleep(500);
    await (await L.$("tr:has-text('qa_e2e_est') .viewRequestBtn")).click(); await swalOpen(L);
    await swalClick(L, "confirm");                              // Aprobar
    await swalOpen(L); await L.fill(".swal2-textarea", "Bienvenido al semillero, nos reunimos los jueves.");
    await swalClick(L, "confirm"); await sleep(1500);
    log("tras aprobar:", (await swalOpen(L, 2000)) ? await swalText(L) : "(sin modal)");
    await swalClose(L); await sleep(600); await swalClose(L);
    await shot(L, "lider_tras_aprobar");
  });

  await scenario("est-ve-respuesta", async () => {
    await H.go(E, "/requests"); await settle(E);
    let txt = await E.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
    log("Mis solicitudes:", txt.slice(0, 260));
    if (!/Aprobad/i.test(txt)) H.report("FLOW", "el estudiante no ve la solicitud como Aprobada");
    // la respuesta del líder está en el detalle de la solicitud (tarjeta)
    const card = await E.$("#content .pwa-card, #app .pwa-card");
    if (card) { await card.click(); await sleep(900); txt = await E.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => ""); await shot(E, "est_solicitud_detalle"); }
    const closeBtn = await E.$("#closeRequestDetailBtn");
    if (closeBtn) { await closeBtn.click(); await sleep(500); log("detalle cerrado:", !(await E.$eval("#requestDetailSheet", (e) => getComputedStyle(e).display !== "none").catch(() => false))); }
    if (!/jueves/i.test(txt)) H.report("FLOW", "el estudiante no ve la respuesta del líder en el detalle");
    await shot(E, "est_solicitud_aprobada");
  });

  /* ───────── ADMINISTRATIVO: evaluar propuestas (CU27) ───────── */
  await scenario("administrativo-evaluar-propuesta", async () => {
    await H.go(A, "/proposals"); await settle(A);
    const btn = await A.$("tr:has-text('Idea QA') .proposalViewBtn");
    if (!btn) throw new Error("el administrativo no ve la propuesta QA");
    await btn.click(); await swalOpen(A);
    const d = await A.$eval(".swal2-popup", (e) => e.innerText.replace(/\s+/g, " ")); log("detalle:", d.slice(0, 200));
    if (!/Contacto del estudiante/.test(d)) H.report("FLOW", "el Administrativo no ve el contacto del estudiante");
    await shot(A, "adm_detalle_propuesta");
    await swalClick(A, "deny");                                  // Archivar sin observación
    await swalOpen(A); await swalClick(A, "confirm");
    const v = await A.$eval(".swal2-validation-message", (e) => e.textContent).catch(() => "");
    log("archivar sin observación:", v || "(sin mensaje)");
    if (!v) H.report("VALID", "archivar sin observación no muestra validación");
    await A.fill(".swal2-textarea", "No encaja con las líneas actuales de los semilleros.");
    await swalClick(A, "confirm"); await sleep(2000);
    log("tras archivar:", (await swalOpen(A, 1500)) ? await swalText(A) : "(sin modal)");
    await swalClose(A); await settle(A);
  });

  await scenario("est-ve-propuesta-evaluada", async () => {
    await H.go(E, "/proposals"); await settle(E);
    const list = await E.$eval("#proposalsList", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
    log("lista:", list.slice(0, 260));
    if (!/Archivad/i.test(list)) H.report("FLOW", "el estudiante no ve la propuesta como Archivada");
    if (!/líneas actuales/i.test(list)) H.report("FLOW", "el estudiante no ve la observación");
    await shot(E, "est_propuesta_archivada");
  });

  /* ───────── NOTIFICACIONES ───────── */
  await scenario("notificaciones", async () => {
    await H.go(S, "/notifications"); await settle(S);
    const nuevo = await S.$("#newNotifBtn");
    if (!nuevo) throw new Error("sin botón Nueva");
    await nuevo.click(); await sleep(800);
    log("formulario nueva notificación:", (await S.$$eval("#content input:visible, #content select:visible, #content textarea:visible, .swal2-popup input, .swal2-popup select, .swal2-popup textarea", (els) => els.map((e) => e.id || e.name || e.type))).join(","));
    await shot(S, "sis_nueva_notif");
    await S.click("#closeNotifSheet").catch(() => {}); await sleep(500);
  });

  /* ───────── PERFIL ───────── */
  await scenario("perfil-estudiante", async () => {
    await H.go(E, "/profile"); await settle(E);
    const ids = await E.$$eval("#app input, #app select, #app button", (els) => els.filter((e) => e.offsetParent).map((e) => e.id || e.name || e.type));
    log("controles:", ids.join(","));
    await shot(E, "est_perfil");
  });

  /* ───────── AUDITORÍA y REPORTES con filtros (CU28/CU30) ───────── */
  await scenario("auditoria-filtros", async () => {
    await H.go(S, "/audits"); await settle(S);
    await S.selectOption("#auditFilters select[name=action]", { index: 1 }); await S.click("#auditFilters button[type=submit]"); await settle(S, 800);
    const rows = await S.$$eval("#auditResults tbody tr", (r) => r.length);
    log("filas con filtro de acción:", rows);
    await (await S.$(".auditViewBtn"))?.click(); log("detalle:", (await swalOpen(S)) ? (await swalText(S)).slice(0, 160) : "(sin modal)"); await swalClose(S);
    const [dl] = await Promise.all([S.waitForEvent("download", { timeout: 8000 }).catch(() => null), S.click("#auditExport")]);
    log("exportar CSV:", dl ? dl.suggestedFilename() : "(sin descarga)");
    if (!dl) H.report("FLOW", "Exportar CSV de auditoría no descargó nada");
    await S.fill("#auditFilters input[name=from]", "2030-01-01"); await S.fill("#auditFilters input[name=to]", "2030-01-31");
    await S.click("#auditFilters button[type=submit]"); await settle(S, 800);
    log("sin resultados:", (await S.$eval("#auditResults", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 100))));
    await shot(S, "sis_auditoria_vacia");
  });

  await scenario("reportes-filtros-y-csv", async () => {
    await H.go(S, "/reports"); await settle(S);
    for (const b of await S.$$("[data-export]")) {
      const key = await b.getAttribute("data-export");
      const [dl] = await Promise.all([S.waitForEvent("download", { timeout: 8000 }).catch(() => null), b.click()]);
      log("CSV", key, "->", dl ? dl.suggestedFilename() : "SIN DESCARGA");
      if (!dl) H.report("FLOW", `Exportar CSV del reporte ${key} no descargó`);
    }
    await S.fill("#reportFilters input[name=from]", "2030-01-01"); await S.fill("#reportFilters input[name=to]", "2030-12-31");
    await S.click("#reportFilters button[type=submit]"); await settle(S, 800);
    log("periodo sin datos:", (await S.$eval("#reportResults", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 120))));
    S.__expect = (s) => s === 422;
    await S.fill("#reportFilters input[name=from]", "2026-03-10"); await S.fill("#reportFilters input[name=to]", "2026-03-01");
    await S.click("#reportFilters button[type=submit]"); await settle(S, 800);
    log("rango inválido:", await S.$eval("#reportError", (e) => e.textContent));
    S.__expect = null;
    await shot(S, "sis_reportes_error");
  });

  /* ───────── SIA (asistente) y tema ───────── */
  await scenario("sia-widget", async () => {
    await H.go(E, "/dashboard"); await settle(E);
    await E.click("#sia-open"); await sleep(800);
    await E.fill("#sia-input, #sia-form textarea, #sia-form input[type=text]", "¿Cómo envío una solicitud a un semillero?").catch(() => {});
    await E.click("#sia-send").catch(() => {}); await sleep(6000);
    const msgs = await E.$$eval("#sia-panel, #sia-messages, [id^=sia] ", (els) => els.map((e) => e.id + ":" + (e.innerText || "").replace(/\s+/g, " ").slice(0, 120))).catch(() => []);
    log("SIA:", JSON.stringify(msgs.slice(0, 4)));
    await shot(E, "est_sia");
  });

  await scenario("tema-y-logout", async () => {
    await E.click("#themeToggleBtn"); await sleep(400);
    const dark = await E.evaluate(() => document.documentElement.getAttribute("data-theme") || document.body.className);
    log("tema tras alternar:", dark);
    await shot(E, "est_tema_alterno");
    await E.click("#themeToggleBtn"); await sleep(300);
    await E.click("#logoutBtn"); await sleep(1500);
    if (await swalOpen(E, 1500)) { await swalClick(E, "confirm"); await sleep(1500); }
    log("tras cerrar sesión:", E.url());
    if (!/\/($|login)/.test(new URL(E.url()).pathname)) H.report("FLOW", "cerrar sesión no vuelve a la pantalla de ingreso");
    await E.goto(BASE + "/dashboard"); await settle(E);
    log("acceso a /dashboard sin sesión ->", new URL(E.url()).pathname);
  });

  for (const s of [est, lid, adm, sis]) await s.ctx.close();
  H.finish({ vp: VP });
  await browser.close();
  process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
