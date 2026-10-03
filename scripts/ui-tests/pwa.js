// Pruebas de PWA: manifest, iconos, Service Worker, uso sin conexión y recuperación (teléfono).
const { startRelays, connect, sleep, BASE } = require("./lib.js");
const H = require("./harness.js");
const log = (...a) => console.log("   ", ...a);
const settle = async (page, ms = 1200) => { await page.waitForLoadState("networkidle").catch(() => {}); await sleep(ms); };
async function scenario(name, fn) { H.setLabel(`pwa/${name}`); console.log(`\n== ${name}`); try { await fn(); } catch (e) { H.report("FLOW", `${name}: ${String(e.message).split("\n")[0]}`); } }

(async () => {
  startRelays();
  const browser = await connect();
  const { ctx, page } = await H.newSession(browser, "mobile", "ESTUDIANTE", "/pwa");
  // el login lo hace el estudiante QA 2 (sin solicitudes) para no mezclar estados
  await H.login(page, "ESTUDIANTE", "qa_e2e_est2@example.invalid");

  await scenario("manifest-e-iconos", async () => {
    const href = await page.$eval('link[rel="manifest"]', (l) => l.getAttribute("href"));
    const res = await page.request.get(new URL(href, BASE).href);
    const m = await res.json();
    log("manifest:", res.status(), JSON.stringify({ name: m.name, short_name: m.short_name, start_url: m.start_url, display: m.display, theme: m.theme_color, bg: m.background_color, lang: m.lang }));
    for (const k of ["name", "short_name", "start_url", "display", "icons", "theme_color", "background_color"]) if (!m[k]) H.report("PWA", `manifest sin «${k}»`);
    if (!["standalone", "fullscreen", "minimal-ui"].includes(m.display)) H.report("PWA", `display=${m.display} no instalable como app`);
    let has192 = false, has512 = false, maskable = false;
    for (const ic of m.icons || []) {
      const r = await page.request.get(new URL(ic.src, BASE).href);
      const okType = /image\//.test(r.headers()["content-type"] || "");
      log("icono", ic.sizes, ic.purpose || "any", r.status(), okType ? "" : "(tipo raro)");
      if (r.status() !== 200 || !okType) H.report("PWA", `icono ${ic.src} -> ${r.status()}`);
      if (/192/.test(ic.sizes)) has192 = true; if (/512/.test(ic.sizes)) has512 = true; if (/maskable/.test(ic.purpose || "")) maskable = true;
    }
    if (!has192 || !has512) H.report("PWA", "faltan iconos 192 y/o 512");
    if (!maskable) H.report("PWA", "sin icono maskable");
    const apple = await page.$$eval('link[rel="apple-touch-icon"]', (l) => l.map((x) => x.getAttribute("href")));
    for (const a of apple) { const r = await page.request.get(new URL(a, BASE).href); if (r.status() !== 200) H.report("PWA", `apple-touch-icon ${a} -> ${r.status()}`); }
    log("apple-touch-icon:", apple.length, "| viewport meta:", await page.$eval('meta[name="viewport"]', (m) => m.content));
    log("theme-color meta:", await page.$eval('meta[name="theme-color"]', (m) => m.content).catch(() => "NO"));
  });

  await scenario("service-worker", async () => {
    const info = await page.evaluate(async () => {
      const reg = await navigator.serviceWorker.getRegistration();
      const keys = await caches.keys();
      const names = {};
      for (const k of keys) names[k] = (await (await caches.open(k)).keys()).length;
      return { registered: !!reg, active: !!(reg && reg.active), scope: reg && reg.scope, controller: !!navigator.serviceWorker.controller, caches: names };
    });
    log("SW:", JSON.stringify(info));
    if (!info.registered || !info.active) H.report("PWA", "Service Worker no está activo");
  });

  await scenario("sin-conexion", async () => {
    // visitar pantallas para que queden en caché/localStorage
    for (const r of ["/seedbeds", "/requests", "/proposals", "/dashboard"]) { await H.go(page, r); await settle(page, 600); }
    await ctx.setOffline(true);
    await sleep(800);
    await page.reload({ waitUntil: "domcontentloaded" }).catch((e) => H.report("PWA", `recargar sin conexión falló: ${String(e.message).split("\n")[0]}`));
    await sleep(2500);
    const txt = await page.evaluate(() => (document.body.innerText || "").replace(/\s+/g, " ").slice(0, 200));
    log("recargado offline en", new URL(page.url()).pathname, "->", txt);
    if (txt.trim().length < 20) H.report("PWA", "sin conexión la app queda en blanco al recargar");
    const banner = await page.$eval("body", (b) => /sin conexi[oó]n|offline/i.test(b.innerText)).catch(() => false);
    log("aviso de «sin conexión» visible:", banner);
    if (!banner) H.report("PWA", "no se muestra aviso de sin conexión");
    await page.screenshot({ path: "/t/out/shots/pwa_offline.png", fullPage: true }).catch(() => {});
    // navegar con la app ya cargada y sin red: lo cacheable debe verse; lo demás debe dar mensaje claro
    for (const r of ["/seedbeds", "/requests"]) {
      await H.go(page, r); await sleep(1500);
      const t = await page.evaluate(() => (document.querySelector("#content") || document.body).innerText.replace(/\s+/g, " ").slice(0, 140));
      log(`offline ${r}:`, t);
    }
    // enviar algo sin conexión: debe avisar sin romperse
    await H.go(page, "/proposals"); await sleep(1200);
    const nb = (await page.$("#newProposalBtn")) || (await page.$("#emptyNewProposalBtn"));
    if (nb) { await nb.click(); await sleep(600); log("formulario de propuesta abre sin conexión:", !!(await page.$("#proposalForm"))); }
    await ctx.setOffline(false);
    await sleep(2500);
    const back = await page.evaluate(() => !/sin conexi[oó]n/i.test((document.querySelector(".offline-banner, #offlineBanner") || {}).innerText || ""));
    log("aviso desaparece al volver la red:", back);
    await page.screenshot({ path: "/t/out/shots/pwa_online_again.png", fullPage: true }).catch(() => {});
  });

  await scenario("navegacion-movil", async () => {
    await H.go(page, "/dashboard"); await settle(page, 600);
    for (const href of ["/seedbeds", "/requests", "/proposals", "/profile"]) {
      const link = await page.$(`.pwa-bottom-nav a[href="${href}"]`);
      if (!link) { H.report("PWA", `la barra inferior no tiene ${href}`); continue; }
      await link.click(); await settle(page, 700);
      const p = new URL(page.url()).pathname;
      if (p !== href) H.report("NAV", `barra inferior ${href} terminó en ${p}`);
      const active = await page.$eval(`.pwa-bottom-nav a[href="${href}"]`, (a) => a.classList.contains("active") || a.getAttribute("aria-current") === "page" || /active/.test(a.className)).catch(() => false);
      log(href, "activo en la barra:", active);
    }
    // menú lateral en móvil
    await page.click("#hamburgerBtn"); await sleep(500);
    const open = await page.$eval("#sidebar", (s) => s.classList.contains("open"));
    log("menú hamburguesa abre:", open);
    if (!open) H.report("PWA", "el menú hamburguesa no abre el panel lateral");
    await page.click("#sidebarOverlay").catch(() => {}); await sleep(400);
    log("menú cierra con el fondo:", !(await page.$eval("#sidebar", (s) => s.classList.contains("open"))));
  });

  H.finish({ vp: "pwa" });
  await ctx.close(); await browser.close(); process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
