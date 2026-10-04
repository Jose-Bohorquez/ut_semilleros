// Botón de instalar tras instalar, actualización automática y animaciones.
const { startRelays, connect, sleep, BASE } = require("./lib.js");
const fs = require("fs");
const H = require("./harness.js");
const log = (...a) => console.log("   ", ...a);
const ok = (c, m) => { if (c) log("✔", m); else H.report("EFECTO", m); };
const visible = (p, sel) => p.$eval(sel, (e) => !e.hidden && getComputedStyle(e).display !== "none").catch(() => false);
const ANDROID = "Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36";
const VERSION_FILE = "/host/home/jose/proyectos/propios/ut/_public_html/version.json";   // lo sirve el contenedor del frontend (bind mount)
const setVersion = (v) => fs.writeFileSync(VERSION_FILE, JSON.stringify({ version: v }) + "\n");

(async () => {
  startRelays();
  const browser = await connect();
  const mkAndroid = async (init) => {
    const c = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, userAgent: ANDROID, locale: "es-CO" });
    if (init) await c.addInitScript(init);
    const p = await c.newPage(); p.setDefaultTimeout(10000);
    return { c, p };
  };

  console.log("\n== el botón de instalar desaparece cuando la app ya está instalada");
  let s = await mkAndroid();
  await s.p.goto(BASE + "/", { waitUntil: "networkidle" }); await sleep(700);
  ok(await visible(s.p, "#pwa-install"), "en el navegador (sin instalar) Android ofrece instalar");
  await s.p.evaluate(() => window.dispatchEvent(new Event("appinstalled"))); await sleep(400);
  ok(!(await visible(s.p, "#pwa-install")), "al instalarse (`appinstalled`) el botón se oculta");
  await s.p.reload({ waitUntil: "networkidle" }); await sleep(700);
  ok(!(await visible(s.p, "#pwa-install")), "y sigue oculto en la pestaña del navegador después (se recuerda en el dispositivo)");
  await s.c.close();

  // La app instalada con display_override «fullscreen» (el caso real): antes solo se miraba «standalone»
  for (const mode of ["fullscreen", "standalone", "minimal-ui"]) {
    s = await mkAndroid(`(() => { const o = window.matchMedia.bind(window); window.matchMedia = (q) => (q === "(display-mode: ${mode})" ? { matches: true, media: q, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {} } : o(q)); })();`);
    await s.p.goto(BASE + "/", { waitUntil: "networkidle" }); await sleep(700);
    ok(!(await visible(s.p, "#pwa-install")), `abierta como app instalada (display-mode: ${mode}) no se ofrece instalar`);
    await s.c.close();
  }

  console.log("\n== si la desinstalan, vuelve a ofrecerse");
  s = await mkAndroid();
  await s.p.goto(BASE + "/", { waitUntil: "networkidle" });
  await s.p.evaluate(() => { localStorage.setItem("pwa_installed", "1"); }); await s.p.reload({ waitUntil: "networkidle" }); await sleep(600);
  ok(!(await visible(s.p, "#pwa-install")), "con la marca de «instalada» está oculto");
  await s.p.evaluate(() => { const e = new Event("beforeinstallprompt", { cancelable: true }); e.prompt = async () => {}; e.userChoice = Promise.resolve({ outcome: "dismissed" }); window.dispatchEvent(e); }); await sleep(400);
  ok(await visible(s.p, "#pwa-install"), "si el navegador vuelve a ofrecer instalar (la desinstalaron) el botón reaparece");
  await s.c.close();

  console.log("\n== actualización automática");
  setVersion("v-prueba-1");
  const E = await H.newSession(browser, "mobile", "ESTUDIANTE", "/update");
  const P = E.page;
  await H.login(P, "ESTUDIANTE", "qa_e2e_est2@example.invalid"); await sleep(1500);
  await P.evaluate(() => { window.__marcaPestana = "no-recargada"; });
  ok(!(await P.$("#updateBanner")), "sin cambios de versión no hay aviso");
  setVersion("v-prueba-2");                                         // «se despliega» una versión nueva
  await P.evaluate(() => window.dispatchEvent(new Event("online"))); await sleep(1500);   // equivale a recuperar la conexión
  ok(!!(await P.$("#updateBanner")), "al detectar una versión nueva aparece «Hay una versión nueva»");
  ok(await P.evaluate(() => window.__updateReady === true), "queda marcada como lista para aplicar");
  await P.screenshot({ path: "/t/out/shots/update_banner.png" });
  // en la siguiente navegación se recarga sola (momento seguro)
  await P.click('.pwa-bottom-nav a[href="/seedbeds"]'); await sleep(2500);
  ok((await P.evaluate(() => window.__marcaPestana)) === undefined, "al cambiar de pantalla la app se recargó con la versión nueva");
  ok(new URL(P.url()).pathname === "/seedbeds", "y llegó a la pantalla que se había pedido");
  // botón «Actualizar»
  setVersion("v-prueba-3");
  await P.evaluate(() => { window.__marcaPestana = "otra"; window.dispatchEvent(new Event("online")); }); await sleep(1500);
  if (await P.$("#updateNowBtn")) { await P.click("#updateNowBtn"); await sleep(2500); }
  ok((await P.evaluate(() => window.__marcaPestana)) === undefined, "el botón «Actualizar» recarga la app");
  await E.ctx.close();
  fs.unlinkSync(VERSION_FILE);

  console.log("\n== animaciones");
  const A = await H.newSession(browser, "mobile", "ESTUDIANTE", "/anim");
  await H.login(A.page, "ESTUDIANTE", "qa_e2e_est2@example.invalid");
  await H.go(A.page, "/seedbeds"); await sleep(1200);
  const anim = await A.page.$eval(".faculty-card", (e) => getComputedStyle(e).animationName).catch(() => "?");
  ok(anim !== "none" && anim !== "?", `las tarjetas entran con animación (${anim})`);
  const navAnim = await A.page.$eval(".pwa-bottom-nav .nav-item.active .nav-icon", (e) => getComputedStyle(e).animationName).catch(() => "?");
  ok(navAnim !== "none" && navAnim !== "?", `el icono activo de la barra inferior «salta» (${navAnim})`);
  await H.go(A.page, "/dashboard"); await sleep(1500);
  const cols = await A.page.$eval(".kpi-grid", (e) => getComputedStyle(e).gridTemplateColumns.split(" ").length).catch(() => 0);
  ok(cols === 2, `las tarjetas de resumen son compactas en móvil (${cols} columnas)`);
  await A.page.screenshot({ path: "/t/out/shots/anim_dashboard.png", fullPage: false });
  await A.ctx.close();

  const R = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: "reduce", locale: "es-CO" });
  const rp = await R.newPage(); rp.setDefaultTimeout(10000);
  await rp.goto(BASE + "/", { waitUntil: "networkidle" });
  await rp.fill("#email", "qa_e2e_est2@example.invalid"); await rp.fill("#password", "QaE2E!2026x"); await rp.click("button[type=submit]"); await sleep(3000);
  await rp.goto(BASE + "/seedbeds", { waitUntil: "networkidle" }); await sleep(1200);
  const animR = await rp.$eval(".faculty-card", (e) => getComputedStyle(e).animationName).catch(() => "?");
  ok(animR === "none", `con «reducir movimiento» no hay animaciones (${animR})`);
  await R.close();

  H.finish({ vp: "update" });
  await browser.close(); process.exit(0);
})().catch((e) => { try { fs.unlinkSync(VERSION_FILE); } catch {} console.error("FALLO", e.stack); process.exit(1); });
