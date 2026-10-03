// Utilidades de prueba: sesiones por rol/viewport, recolección de problemas, navegación SPA y chequeos visuales.
const fs = require("fs");
const { connect, BASE, PASS, USERS, sleep } = require("./lib.js");

const VPS = {
  desktop: { viewport: { width: 1440, height: 900 } },
  tablet:  { viewport: { width: 820, height: 1180 }, hasTouch: true },
  mobile:  { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2,
             userAgent: "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1" },
};
exports.VPS = VPS;

const IGNORE_URL = /datatables\.net\/.*\/images|favicon|fonts\.gstatic|ut\.edu\.co/;
exports.issues = [];
let ctxLabel = "";
exports.setLabel = (l) => { ctxLabel = l; };
exports.report = (kind, detail) => {
  const key = `${kind}|${ctxLabel}|${String(detail).slice(0, 200)}`;
  if (exports.issues.some((i) => i.key === key)) return;
  exports.issues.push({ key, where: ctxLabel, kind, detail: String(detail).slice(0, 500) });
  console.log(`  [${kind}] ${ctxLabel} :: ${String(detail).slice(0, 300)}`);
};

/** Adjunta recolectores de errores a una página. page.__expect(status,url) permite tolerar respuestas esperadas. */
exports.watch = (page, getRoute) => {
  page.on("pageerror", (e) => exports.report("JS", `${getRoute()} ${e.message}`));
  page.on("console", (m) => {
    if (m.type() !== "error") return;
    const t = m.text();
    if (/Failed to load resource/.test(t)) return; // se informa por 'response' con URL y estado
    exports.report("CONSOLE", `${getRoute()} ${t}`);
  });
  page.on("response", (r) => {
    const s = r.status(), u = r.url();
    if (s >= 400 && !IGNORE_URL.test(u) && !(page.__expect && page.__expect(s, u))) {
      exports.report("HTTP" + s, `${getRoute()} ${r.request().method()} ${u.replace(BASE, "").replace("http://localhost:8000", "")}`);
    }
  });
  page.on("requestfailed", (r) => {
    const u = r.url();
    if (IGNORE_URL.test(u) || /ERR_ABORTED/.test((r.failure() || {}).errorText || "")) return;
    exports.report("REQFAIL", `${getRoute()} ${u} ${(r.failure() || {}).errorText}`);
  });
};

exports.newSession = async (browser, vp, role, tag = "") => {
  const ctx = await browser.newContext({ ...VPS[vp], locale: "es-CO", timezoneId: "America/Bogota", acceptDownloads: true });
  const page = await ctx.newPage();
  page.setDefaultTimeout(10000);
  let route = "/";
  exports.watch(page, () => route);
  page.__setRoute = (r) => { route = r; };
  exports.setLabel(`${vp}/${role}${tag}`);
  return { ctx, page };
};

exports.login = async (page, role, email = USERS[role], pass = PASS) => {
  await page.goto(BASE + "/", { waitUntil: "networkidle" });
  await page.fill("#email", email);
  await page.fill("#password", pass);
  await page.click("button[type=submit]");
  await page.waitForURL((u) => !/^\/(login)?$/.test(u.pathname), { timeout: 15000 }).catch(() => {});
  await page.waitForLoadState("networkidle").catch(() => {});
  await sleep(800);
};

exports.openMenu = async (page) => {
  const hb = await page.$("#hamburgerBtn");
  if (hb && (await hb.isVisible())) {
    const open = await page.$eval("#sidebar", (s) => s.classList.contains("open")).catch(() => false);
    if (!open) { await hb.click(); await sleep(350); }
  }
};

/** Navega haciendo clic en el enlace real del menú lateral (en móvil abre el menú antes). */
exports.go = async (page, href) => {
  if (page.__setRoute) page.__setRoute(href);
  await exports.openMenu(page);
  const link = await page.$(`.sidebar a[data-link][href="${href}"]`);
  if (link && (await link.isVisible())) await link.click();
  else await page.evaluate((h) => { history.pushState({}, "", h); dispatchEvent(new PopStateEvent("popstate")); }, href);
  await page.waitForLoadState("networkidle").catch(() => {});
  await sleep(1500);
};

/** Chequeos visuales/estructurales de la pantalla actual. */
exports.checkScreen = async (page, name, shotDir) => {
  const r = await page.evaluate(() => {
    const vw = window.innerWidth;
    const out = { overflowX: 0, offenders: [], badText: [], brokenImgs: 0, h: "", contentLen: 0 };
    out.overflowX = document.documentElement.scrollWidth - vw;
    if (out.overflowX > 1) {
      for (const el of document.querySelectorAll("body *")) {
        const b = el.getBoundingClientRect();
        if (b.width > 0 && b.right > vw + 1 && getComputedStyle(el).position !== "fixed") {
          let p = el.parentElement, scrolls = false;
          while (p && p !== document.body) {
            if (/(auto|scroll|hidden)/.test(getComputedStyle(p).overflowX)) { scrolls = true; break; }
            p = p.parentElement;
          }
          if (!scrolls) {
            out.offenders.push((el.tagName + "." + String(el.className || "").split(" ")[0] + "#" + el.id).slice(0, 60) + " w=" + Math.round(b.width));
            if (out.offenders.length > 4) break;
          }
        }
      }
    }
    const txt = (document.querySelector("#content") || document.body).innerText || "";
    for (const re of [/\bundefined\b/, /\bNaN\b/, /\[object Object\]/, /Invalid Date/, /\{\{.*\}\}/, /\bTypeError\b/]) {
      const m = txt.match(re);
      if (m) out.badText.push(m[0]);
    }
    out.brokenImgs = [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.src && !/ut\.edu\.co/.test(i.src)).length;
    out.h = ((document.querySelector("#content h1, #content h2, .content h2") || {}).textContent || "").trim().replace(/\s+/g, " ").slice(0, 60);
    out.contentLen = txt.trim().length;
    return out;
  });
  if (r.overflowX > 1 && r.offenders.length) exports.report("OVERFLOW", `${name} +${r.overflowX}px: ${r.offenders.join("; ")}`);
  if (r.badText.length) exports.report("TEXT", `${name} muestra ${r.badText.join(",")}`);
  if (r.brokenImgs) exports.report("IMG", `${name} ${r.brokenImgs} imagen(es) rotas`);
  if (r.contentLen < 20) exports.report("EMPTY", `${name} contenido casi vacío`);
  if (shotDir) {
    fs.mkdirSync(shotDir, { recursive: true });
    await page.screenshot({ path: `${shotDir}/${name.replace(/[\/\s]+/g, "_")}.png`, fullPage: true }).catch(() => {});
  }
  return r;
};

exports.finish = (extra = {}) => {
  fs.mkdirSync("/t/out", { recursive: true });
  fs.writeFileSync(`/t/out/issues_${process.env.TAG || "run"}.json`, JSON.stringify({ issues: exports.issues, ...extra }, null, 1));
  console.log(`\nTOTAL hallazgos: ${exports.issues.length}`);
};
