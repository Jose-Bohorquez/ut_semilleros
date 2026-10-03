// Utilidades compartidas: relés localhost:8080/8000 -> contenedores, conexión a Chrome y registro de hallazgos.
const net = require("net");
const { chromium } = require("playwright-core");

function relay(port, host, hport) {
  net.createServer((c) => {
    const s = net.connect(hport, host);
    c.pipe(s); s.pipe(c);
    c.on("error", () => s.destroy()); s.on("error", () => c.destroy());
  }).listen(port, "127.0.0.1");
}
const cdn = require("./cdnserver.js");
exports.startRelays = () => { cdn.start(); relay(8080, "frontend", 80); relay(8000, "api", 80); };

exports.connect = async () => chromium.connectOverCDP("http://127.0.0.1:9222");

exports.BASE = "http://localhost:8080";
exports.PASS = "QaE2E!2026x";
exports.USERS = {
  ADMIN_SISTEMA: "qa_e2e_sis@example.invalid", ADMINISTRATIVO: "qa_e2e_adm@example.invalid",
  LIDER_SEMILLERO: "qa_e2e_lid@example.invalid", ESTUDIANTE: "qa_e2e_est@example.invalid",
};
exports.sleep = (ms) => new Promise((r) => setTimeout(r, ms));
