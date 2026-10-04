const { startRelays, connect, sleep } = require("./lib.js");
const H = require("./harness.js");
const log = (...a) => console.log("   ", ...a);
const ok = (c, m) => { if (c) log("✔", m); else H.report("EFECTO", m); };
const visible = (p, sel) => p.$eval(sel, (e) => !e.hidden && getComputedStyle(e).display !== "none").catch(() => false);
(async () => {
  startRelays();
  const browser = await connect();

  // ── botón «Instalar app» ──
  for (const [vp, label, ua] of [["desktop", "escritorio (Chrome)", null], ["mobile", "iPhone (Safari)", null], ["mobile", "Android (Chrome)", "Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36"]]) {
    console.log(`\n== instalar · ${label}`);
    const { ctx, page } = await H.newSession(browser, vp, "ESTUDIANTE", "/instalar");
    if (ua) { await ctx.close(); const c2 = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, userAgent: ua, locale: "es-CO" }); const p2 = await c2.newPage(); p2.setDefaultTimeout(10000); await p2.goto("http://localhost:8080/", { waitUntil: "networkidle" }); await sleep(800); await installChecks(p2, "android"); await c2.close(); continue; }
    await page.goto("http://localhost:8080/", { waitUntil: "networkidle" }); await sleep(800);
    await installChecks(page, vp === "desktop" ? "desktop" : "ios");
    await ctx.close();
  }

  async function installChecks(page, kind) {
    if (kind === "desktop") {
      ok(!(await visible(page, "#pwa-install")), "sin aviso del navegador el botón NO aparece en escritorio");
      await page.evaluate(() => {
        window.__prompted = 0;
        const ev = new Event("beforeinstallprompt", { cancelable: true });
        ev.prompt = async () => { window.__prompted++; };
        ev.userChoice = Promise.resolve({ outcome: "accepted" });
        window.dispatchEvent(ev);
      });
      await sleep(300);
      ok(await visible(page, "#pwa-install"), "con `beforeinstallprompt` el botón aparece");
      await page.click("#pwa-install"); await sleep(500);
      ok((await page.evaluate(() => window.__prompted)) === 1, "al tocarlo se muestra el diálogo nativo de instalación");
      ok(!(await visible(page, "#pwa-install")), "tras usar el aviso el botón se oculta (el evento solo sirve una vez)");
    } else {
      ok(await visible(page, "#pwa-install"), `el botón aparece en ${kind === "ios" ? "iPhone" : "Android"} sin necesitar el aviso del navegador`);
      await page.click("#pwa-install"); await sleep(700);
      const txt = await page.$eval(".swal2-popup", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
      log("instrucciones:", txt.slice(0, 160));
      ok(kind === "ios" ? /Compartir/.test(txt) && /pantalla de inicio/i.test(txt) : /Instalar aplicación/.test(txt), "muestra los pasos correctos para el dispositivo");
      await page.keyboard.press("Escape"); await sleep(300);
    }
    // el botón no debe tapar al resto ni salirse de la pantalla
    const box = await page.$eval(".sia-fabs", (e) => { const r = e.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, left: r.left, right: r.right, vh: innerHeight, vw: innerWidth }; });
    ok(box.top >= 0 && box.bottom <= box.vh && box.right <= box.vw, `la pila de botones cabe en pantalla (${JSON.stringify(box)})`);
    await page.screenshot({ path: `/t/out/shots/instalar_${kind}.png` });
  }

  // ── elegibilidad en el detalle del semillero ──
  console.log("\n== postulación: regla «sin ningún semillero asociado»");
  const busy = await H.newSession(browser, "mobile", "ESTUDIANTE", "/elig");
  await H.login(busy.page, "ESTUDIANTE", "qa_e2e_est2@example.invalid");   // tiene una solicitud pendiente a «IA y Machine Learning»
  const P = busy.page;
  await H.go(P, "/seedbeds"); await sleep(900);
  for (const [name, expect] of [["IA y Machine", /Solicitud pendiente/], ["Emprendimiento", /No disponible por ahora/]]) {
    await P.fill("#seedbedSearch", name); await sleep(600);
    await (await P.$("#searchResults .seedbed-card")).click(); await sleep(1500);
    const txt = await P.$eval("#membershipSection", (e) => e.innerText.replace(/\s+/g, " ")).catch(() => "");
    log(`${name}:`, txt.slice(0, 200));
    ok(expect.test(txt), `«${name}» → ${expect}`);
    if (name.startsWith("Emprend")) ok(/IA y Machine/.test(txt) && /pendiente/i.test(txt), "el mensaje nombra el semillero donde ya tiene la solicitud pendiente");
    ok(!(await P.$("#joinSeedbedBtn")), `«${name}»: no se ofrece el formulario de postulación`);
    await P.screenshot({ path: `/t/out/shots/elig_${name.split(" ")[0]}.png` });
    await P.click("#closeDetail"); await sleep(400);
  }
  await busy.ctx.close();

  const free = await H.newSession(browser, "mobile", "ESTUDIANTE", "/elig2");
  await H.login(free.page, "ESTUDIANTE", "qa_e2e_est@example.invalid");     // sin nada (datos reiniciados)
  await H.go(free.page, "/seedbeds"); await sleep(900);
  await free.page.fill("#seedbedSearch", "Emprendimiento"); await sleep(600);
  await (await free.page.$("#searchResults .seedbed-card")).click(); await sleep(1500);
  ok(!!(await free.page.$("#joinSeedbedBtn")), "un estudiante sin semillero SÍ ve «Ser miembro»");
  await free.ctx.close();

  H.finish({ vp: "install-elig" });
  await browser.close(); process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
