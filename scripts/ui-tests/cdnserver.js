// Sirve las librerías CDN descargadas, para que el navegador (sin internet) cargue la app completa.
const https = require("https"), fs = require("fs"), path = require("path");
const DIR = "/t/cdn";
const map = {};
for (const line of fs.readFileSync(DIR + "/map.txt", "utf8").split("\n").filter(Boolean)) {
  const [u, f] = line.split("\t"); const x = new URL(u); map[x.host + x.pathname + x.search] = f;
}
const types = { ".css": "text/css", ".js": "application/javascript", ".woff2": "font/woff2" };
exports.start = () => https.createServer({ key: fs.readFileSync("/t/key.pem"), cert: fs.readFileSync("/t/cert.pem") }, (req, res) => {
  const host = (req.headers.host || "").split(":")[0];
  let f = map[host + req.url];
  if (!f && /\/webfonts\//.test(req.url)) { const n = path.basename(req.url.split("?")[0]); if (fs.existsSync(`${DIR}/${n}`)) f = n; }
  if (host === "www.ut.edu.co") { res.writeHead(204); return res.end(); }
  if (host === "fonts.gstatic.com") { res.writeHead(200, { "access-control-allow-origin": "*", "content-type": "font/ttf" }); return res.end(); }
  if (!f) { res.writeHead(404); return res.end(); }
  const isCss = /css/.test(req.url) || f === "f7";
  res.writeHead(200, { "content-type": types[path.extname(f)] || (/\.css|css2|css\//.test(req.url) ? "text/css" : "application/javascript"), "access-control-allow-origin": "*", "cache-control": "no-cache" });
  fs.createReadStream(`${DIR}/${f}`).pipe(res);
}).listen(443, "127.0.0.1");
