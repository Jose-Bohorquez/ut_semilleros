/* =========================================================
   #archivo: /frontend/core/escape.js
   Escape de HTML compartido.

   Todo dato que venga del backend o del usuario y se inserte en un
   template que termine en innerHTML (o en el `html:` de Swal) debe pasar
   por escapeHtml(). Sirve tanto para contenido de texto como para valores
   de atributos entre comillas dobles o simples.

   Motivo (hallazgo C-03, 2026-09-27): el motor CRUD y las vistas PWA
   pintaban nombres/títulos crudos — un estudiante podía guardar
   `<img src=x onerror=...>` en una propuesta y robar el token de sesión
   (localStorage) del admin que abriera el listado.
   ========================================================= */

const ESCAPES = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };

export function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, c => ESCAPES[c]);
}

/* URL segura para href/src: solo http(s) y mailto (absolutas o relativas).
   Escapar no basta en un href — `javascript:alert(1)` no lleva ningún
   carácter especial y se ejecuta igual al hacer clic. Se usa el parser de URL
   del navegador (no una regex) porque normaliza igual que él: descarta
   tabs/saltos de línea, así "java\nscript:" se detecta como javascript:. */
export function safeUrl(value) {
    const raw = String(value ?? "").trim();
    if (!raw) return "";
    let url;
    try {
        url = new URL(raw, globalThis.location?.origin || "http://localhost");
    } catch {
        return "#";
    }
    return ["http:", "https:", "mailto:"].includes(url.protocol) ? escapeHtml(raw) : "#";
}

/* src de <img>: lo mismo que safeUrl, más las fotos de perfil, que el
   backend guarda como data URI base64 (AuthController::updatePhoto). */
export function safeImageSrc(value) {
    const url = String(value ?? "").trim();
    if (/^data:image\/(png|jpe?g|gif|webp|bmp|svg\+xml);base64,[a-z0-9+/=\s]+$/i.test(url)) return escapeHtml(url);
    return safeUrl(url);
}
