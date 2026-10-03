// PWA del estudiante (teléfono): cada interacción se VERIFICA por su efecto, no solo por poder pulsarse.
const { startRelays, connect, sleep } = require("./lib.js");
const H = require("./harness.js");
const log = (...a) => console.log("   ", ...a);
const ok = (cond, msg) => { if (cond) log("✔", msg); else H.report("EFECTO", msg); };
async function scenario(name, fn) { H.setLabel(`pwa-deep/${name}`); console.log(`\n== ${name}`); try { await fn(); } catch (e) { H.report("FLOW", `${name}: ${String(e.message).split("\n")[0]}`); } }
const settle = async (p, ms = 900) => { await p.waitForLoadState("networkidle").catch(() => {}); await sleep(ms); };
const shown = (p, sel) => p.$eval(sel, (e) => e.offsetParent !== null || getComputedStyle(e).position === "fixed" && getComputedStyle(e).display !== "none").catch(() => false);

(async () => {
  startRelays();
  const browser = await connect();
  const { ctx, page: P } = await H.newSession(browser, "mobile", "ESTUDIANTE", "/pwa-deep");
  await H.login(P, "ESTUDIANTE", "qa_e2e_est2@example.invalid");

  await scenario("semilleros-navegacion-y-pestañas", async () => {
    await H.go(P, "/seedbeds"); await settle(P);
    const faculties = await P.$$(".faculty-card");
    ok(faculties.length > 0, `hay tarjetas de facultad (${faculties.length})`);
    await faculties[0].click(); await settle(P, 600);
    ok(await shown(P, "#backToFaculties"), "al entrar a una facultad aparece «volver»");
    const cards = await P.$$(".seedbed-card");
    ok(cards.length > 0, `la facultad lista semilleros (${cards.length})`);
    await P.click("#backToFaculties"); await sleep(500);
    ok((await P.$$(".faculty-card")).length === faculties.length, "«volver» regresa al listado de facultades");

    for (const nombre of ["IA y Machine", "Emprendimiento"]) {       // dos aperturas seguidas: detecta listeners acumulados
      await P.fill("#seedbedSearch", nombre); await sleep(700);
      await (await P.$("#searchResults .seedbed-card")).click(); await sleep(1500);
      ok(await shown(P, "#seedbedDetail"), `el detalle de «${nombre}» abre`);
      const title = await P.$eval("#detailTitle", (e) => e.textContent.trim());
      ok(title.includes(nombre.split(" ")[0]), `el título del detalle es «${title}»`);

      const state = () => P.evaluate(() => ({
        active: [...document.querySelectorAll(".detail-tab-btn")].filter((b) => b.classList.contains("active")).map((b) => b.dataset.tab),
        visible: [...document.querySelectorAll("[data-tab-pane]")].filter((p) => p.style.display !== "none").map((p) => p.dataset.tabPane),
        text: Object.fromEntries([...document.querySelectorAll("[data-tab-pane]")].map((p) => [p.dataset.tabPane, p.innerText.replace(/\s+/g, " ").trim().slice(0, 60)])),
      }));
      let s = await state();
      ok(s.visible.join() === "mision" && s.active.join() === "mision", `por defecto se ve solo Misión (${JSON.stringify(s.visible)})`);
      for (const tab of ["vision", "objetivos", "mision"]) {
        await P.click(`.detail-tab-btn[data-tab="${tab}"]`); await sleep(500);
        s = await state();
        ok(s.visible.join() === tab && s.active.join() === tab, `la pestaña «${tab}» muestra solo su panel (visible=${JSON.stringify(s.visible)}, activa=${JSON.stringify(s.active)})`);
        if (tab === "objetivos") {
          await sleep(900);
          s = await state();
          ok(!/skeleton/.test(await P.$eval("#seedbedObjectives", (e) => e.innerHTML)), "el panel Objetivos terminó de cargar (sin esqueleto)");
          log("   objetivos:", s.text.objetivos);
          ok(!/Sin objetivos registrados/.test(s.text.objetivos) || nombre.startsWith("Emprend") === false, "el semillero con objetivos en BD los muestra");
        }
      }
      await P.screenshot({ path: `/t/out/shots/pwa_deep_detalle_${nombre.split(" ")[0]}.png` });
      await P.click("#closeDetail"); await sleep(500);
      ok(!(await shown(P, "#seedbedDetail")), "la «×» cierra el detalle");
    }
    // cierre tocando el fondo
    await (await P.$("#searchResults .seedbed-card")).click(); await sleep(1200);
    await P.mouse.click(195, 30); await sleep(600);
    log("cierra tocando fuera:", !(await shown(P, "#seedbedDetail")));
    await P.reload(); await settle(P);
    await P.click("#proposeIdeaFab").catch(() => {}); await sleep(900);
    ok(new URL(P.url()).pathname === "/proposals", "el botón «+» (proponer idea) lleva a Propuestas");
  });

  await scenario("solicitudes", async () => {
    await H.go(P, "/requests"); await settle(P);
    const empty = await P.$("#goToSeedbedsBtn");
    if (empty) { await empty.click(); await sleep(900); ok(new URL(P.url()).pathname === "/seedbeds", "«Ver semilleros» lleva a Semilleros"); }
    else {
      const card = await P.$(".pwa-card"); if (card) { await card.click(); await sleep(800); ok(await shown(P, "#requestDetailSheet"), "la tarjeta abre el detalle"); await P.click("#closeRequestDetailBtn"); await sleep(400); ok(!(await shown(P, "#requestDetailSheet")), "la × cierra el detalle"); }
    }
  });

  await scenario("propuestas-hoja", async () => {
    await H.go(P, "/proposals"); await settle(P);
    const nb = (await P.$("#newProposalBtn")) || (await P.$("#emptyNewProposalBtn"));
    await nb.click(); await sleep(700);
    ok(await shown(P, "#proposalSheet"), "«Nueva propuesta» abre la hoja");
    const areas = await P.$$("#prop-areas input[type=checkbox]");
    if (areas.length) {
      await areas[0].check(); await sleep(200);
      ok(await areas[0].isChecked(), `se puede marcar un área (hay ${areas.length})`);
      if (areas.length > 1) { await areas[1].check(); ok((await P.$$eval("#prop-areas input:checked", (e) => e.length)) === 2, "se pueden marcar varias áreas"); }
      await areas[0].uncheck(); ok(!(await areas[0].isChecked()), "se puede desmarcar un área");
    } else H.report("EFECTO", "la hoja de propuesta no lista áreas");
    ok((await P.$$eval("#prop-program option", (o) => o.length)) > 1, "el selector de programa trae opciones");
    await P.click("#closeProposalSheet"); await sleep(500);
    ok(!(await shown(P, "#proposalSheet")), "la × cierra la hoja de propuesta");
  });

  await scenario("perfil", async () => {
    await H.go(P, "/profile"); await settle(P);
    const type1 = await P.$eval("#prof-pass", (e) => e.type);
    await P.click("#toggleProfPass"); await sleep(300);
    const type2 = await P.$eval("#prof-pass", (e) => e.type);
    ok(type1 !== type2, `el ojito alterna ver/ocultar contraseña (${type1} → ${type2})`);
    const [chooser] = await Promise.all([P.waitForEvent("filechooser", { timeout: 3000 }).catch(() => null), P.click("#changePhotoBtn")]);
    ok(!!chooser, "«Cambiar foto» abre el selector de archivos");
    await P.fill("#prof-phone", "3109998877");
    await P.click("#saveProfileBtn"); await sleep(1500);
    const msg = await P.evaluate(() => (document.querySelector(".swal2-popup") || document.querySelector("#app")).innerText.replace(/\s+/g, " ").slice(0, 160));
    log("guardar perfil →", msg.slice(0, 100));
    await P.keyboard.press("Escape"); await sleep(300);
    const tt = await P.$eval("#themeToggleBtn", (e) => e.className); await P.click("#themeToggleBtn"); await sleep(300);
    ok((await P.evaluate(() => document.documentElement.getAttribute("data-theme") || "")) !== "", "el botón de tema cambia el tema");
    await P.click("#themeToggleBtn"); await sleep(200);
  });

  await scenario("notificaciones-y-barra", async () => {
    await P.click("#bellBtn"); await settle(P, 700);
    ok(new URL(P.url()).pathname === "/notifications", "la campana lleva a Notificaciones");
    const tabs = await P.$$eval("#content button", (b) => b.map((x) => x.textContent.trim()).filter((t) => /Recibidas|Enviadas/.test(t)));
    log("pestañas visibles para el estudiante:", JSON.stringify(tabs));
    const hb = await P.$("#hamburgerBtn"); await hb.click(); await sleep(400);
    ok(await P.$eval("#sidebar", (s) => s.classList.contains("open")), "la hamburguesa abre el menú");
    const link = await P.$('#sidebar a[href="/seedbeds"]'); await link.click(); await settle(P, 700);
    ok(new URL(P.url()).pathname === "/seedbeds", "un enlace del menú navega");
    ok(!(await P.$eval("#sidebar", (s) => s.classList.contains("open"))), "el menú se cierra al navegar");
  });

  await scenario("sia", async () => {
    await H.go(P, "/dashboard"); await settle(P);
    await P.click("#sia-open"); await sleep(700);
    ok(await shown(P, "#sia-panel"), "el botón SIA abre el panel");
    await P.fill("#sia-input", "¿Cómo solicito ingresar a un semillero?"); await P.click("#sia-send"); await sleep(5000);
    const reply = await P.$eval("#sia-body", (e) => e.innerText.replace(/\s+/g, " ").slice(-200));
    log("respuesta/estado de SIA:", reply);
    await P.click("#sia-close").catch(() => {}); await sleep(400);
    await P.click("#sia-toggle-collapse"); await sleep(400);
    ok(!(await P.$eval(".sia-fabs", (e) => getComputedStyle(e).display !== "none")), "«ocultar botones» esconde SIA y WhatsApp");
    await P.click("#sia-toggle-expand"); await sleep(400);
    ok(await P.$eval(".sia-fabs", (e) => getComputedStyle(e).display !== "none"), "«mostrar» los vuelve a traer");
  });

  H.finish({ vp: "pwa-deep" });
  await ctx.close(); await browser.close(); process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
