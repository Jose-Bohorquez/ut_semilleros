// Rastreo de todas las pantallas por rol y viewport. Uso: VP=desktop node crawl.js
const { startRelays, connect, sleep } = require("./lib.js");
const H = require("./harness.js");

const ROUTES = {
  ADMIN_SISTEMA: ["/dashboard", "/admin/seedbeds", "/objectives", "/projects", "/products", "/results", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/admin/faculties", "/admin/programs", "/cats", "/areas", "/admin/users", "/audits", "/admin/sia", "/admin/rbac", "/notifications", "/profile"],
  ADMINISTRATIVO: ["/dashboard", "/admin/seedbeds", "/objectives", "/projects", "/products", "/results", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/notifications", "/profile"],
  LIDER_SEMILLERO: ["/dashboard", "/admin/seedbeds", "/objectives", "/results", "/projects", "/groups", "/coordinators", "/requests", "/proposals", "/reports", "/notifications", "/profile"],
  ESTUDIANTE: ["/dashboard", "/seedbeds", "/requests", "/proposals", "/notifications", "/profile"],
};

(async () => {
  startRelays();
  const browser = await connect();
  const vp = process.env.VP || "desktop";
  const roles = (process.env.ROLES || Object.keys(ROUTES).join(",")).split(",");
  for (const role of roles) {
    const { ctx, page } = await H.newSession(browser, vp, role);
    console.log(`\n### ${vp} / ${role}`);
    await H.login(page, role);
    await H.checkScreen(page, `${role}_login_landing`, `/t/out/shots/${vp}`);
    // dos pasadas: la segunda detecta fallos al volver a una pantalla ya visitada (p.ej. gráficas del Dashboard)
    for (const pass of [1, 2]) {
      for (const href of ROUTES[role]) {
        if (pass === 2 && !["/dashboard", "/reports", "/requests"].includes(href)) continue;
        await H.go(page, href);
        const r = await H.checkScreen(page, `${role}${href}${pass === 2 ? "_2" : ""}`, pass === 1 ? `/t/out/shots/${vp}` : null);
        const path = await page.evaluate(() => location.pathname);
        if (path !== href) H.report("NAV", `${href} terminó en ${path}`);
        if (pass === 1) console.log(`  ok ${href.padEnd(18)} h="${r.h}"`);
        await sleep(1500);
      }
    }
    await ctx.close();
  }
  H.finish({ vp });
  await browser.close();
  process.exit(0);
})().catch((e) => { console.error("FALLO", e.stack); process.exit(1); });
