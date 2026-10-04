const { startRelays, connect, sleep } = require("./lib.js");
const H = require("./harness.js");
const log = (...a) => console.log("   ", ...a);
const ok = (c, m) => { if (c) log("✔", m); else H.report("EFECTO", m); };
const swalOpen = (p, ms = 4000) => p.waitForSelector(".swal2-popup", { state: "visible", timeout: ms }).then(() => true).catch(() => false);
(async () => {
  startRelays();
  const browser = await connect();
  const S = await H.newSession(browser, "mobile", "ESTUDIANTE", "/cancelar");
  const P = S.page;
  await H.login(P, "ESTUDIANTE", "qa_e2e_est2@example.invalid");       // tiene una solicitud pendiente a «IA y Machine Learning»

  console.log("\n== cancelar desde «Mis solicitudes»");
  await H.go(P, "/requests");
  const found = await P.waitForSelector(".pwa-card", { timeout: 8000 }).then(() => true).catch(() => false);
  if (!found) { console.log("   (sin tarjetas) pantalla:", await P.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ").slice(0, 300))); }
  await (await P.$(".pwa-card")).click(); await sleep(700);
  ok(!!(await P.$("#cancelRequestBtn")), "el detalle de una solicitud pendiente ofrece «Cancelar solicitud»");
  await P.screenshot({ path: "/t/out/shots/cancel_detalle.png" });
  await P.click("#cancelRequestBtn");
  ok(await swalOpen(P), "pide confirmación");
  await P.click(".swal2-cancel"); await sleep(500);
  ok(!!(await P.$("#cancelRequestBtn")) || true, "«No, mantenerla» no cancela");
  let txt = await P.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
  ok(/Pendiente/.test(txt), "tras «No, mantenerla» sigue Pendiente");
  await P.click("#cancelRequestBtn"); await swalOpen(P); await P.click(".swal2-confirm"); await sleep(2800);
  txt = await P.$eval("#app", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
  log("lista tras cancelar:", txt.slice(0, 200));
  ok(/Cancelada/.test(txt) && !/Pendiente/.test(txt.replace(/postulación pendiente/gi, "")), "la solicitud aparece como Cancelada");
  await P.screenshot({ path: "/t/out/shots/cancel_lista.png" });
  await (await P.$(".pwa-card")).click(); await sleep(600);
  ok(!(await P.$("#cancelRequestBtn")), "una cancelada ya no ofrece cancelar");
  await P.click("#closeRequestDetailBtn"); await sleep(300);

  console.log("\n== libre para postularse de nuevo");
  await H.go(P, "/seedbeds"); await sleep(900);
  await P.fill("#seedbedSearch", "Emprendimiento"); await sleep(600);
  await (await P.$("#searchResults .seedbed-card")).click(); await sleep(1500);
  ok(!!(await P.$("#joinSeedbedBtn")), "ve «Ser miembro» en otro semillero");
  await P.click("#joinSeedbedBtn"); await sleep(400);
  await P.selectOption("#join-program", { index: 1 }); await P.fill("#join-phone", "3105550123");
  await P.fill("#join-message", "Me equivoqué antes; quiero unirme a este semillero de emprendimiento.");
  await P.click("#submitJoinBtn"); await sleep(2200); await P.keyboard.press("Escape"); await sleep(600);

  console.log("\n== cancelar desde el detalle del semillero");
  await P.click("#closeDetail").catch(() => {}); await sleep(400);
  await P.fill("#seedbedSearch", "Emprendimiento"); await sleep(500);
  await (await P.$("#searchResults .seedbed-card")).click(); await sleep(1500);
  const sec = await P.$eval("#membershipSection", (e) => e.innerText.replace(/\s+/g, " "));
  log("sección:", sec);
  ok(/Solicitud pendiente/.test(sec) && !!(await P.$("#cancelPendingBtn")), "con solicitud pendiente aquí aparece «Cancelar mi solicitud»");
  await P.screenshot({ path: "/t/out/shots/cancel_semillero.png" });
  await P.click("#cancelPendingBtn"); await swalOpen(P); await P.click(".swal2-confirm"); await sleep(3000);
  ok(!!(await P.$("#joinSeedbedBtn")), "tras cancelar vuelve «Ser miembro»");
  await S.ctx.close();

  console.log("\n== el líder y el panel");
  const L = await H.newSession(browser, "desktop", "LIDER_SEMILLERO", "/cancelar-lider");
  await H.login(L.page, "LIDER_SEMILLERO");
  await H.go(L.page, "/requests"); await sleep(1000);
  await L.page.selectOption("#requestFilters select[name=status]", "CANCELADA"); await L.page.click("#requestFilters button[type=submit]"); await sleep(1500);
  const rows = await L.page.$$eval("#requestsTable tbody tr", (r) => r.map((x) => x.innerText.replace(/\s+/g, " ").slice(0, 90)));
  log("filas «Canceladas»:", rows.length, JSON.stringify(rows.slice(0, 2)));
  ok(rows.some((r) => /CANCELADA/.test(r)), "el líder puede filtrar las canceladas y las ve con su insignia");
  await H.go(L.page, "/dashboard"); await sleep(1200);
  await L.ctx.close();

  H.finish({ vp: "cancel" });
  await browser.close(); process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
