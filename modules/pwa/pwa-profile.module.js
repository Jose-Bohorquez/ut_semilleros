/* #archivo: frontend/modules/pwa/pwa-profile.module.js
   Perfil del usuario — todos los roles
   Incluye: ver/editar datos + subir foto de perfil (cualquier formato)
   ─────────────────────────────────────────────────────────────────── */

import { apiFetch }           from "../../services/api.service.js";
import { getUser, setUser }   from "../../services/storage.service.js";
import { LayoutView }          from "../../layout/layout.view.js";
import { initLayoutController }from "../../layout/layout.controller.js";

const ROLE_LABELS = {
    ADMIN_SISTEMA:   { label: "Administrador",   color: "#b91c1c" },
    ADMINISTRATIVO:  { label: "Administrativo",  color: "#1d4ed8" },
    LIDER_SEMILLERO: { label: "Líder Semillero", color: "#15803d" },
    ESTUDIANTE:      { label: "Estudiante",       color: "#7c3aed" },
};

export const pwaProfileModule = {
    async init() {
        renderProfile(getUser());
        bindEvents();
    }
};

/* ── Render ──────────────────────────────────────────── */

function renderProfile(user) {
    const initials = (user?.name || "?")
        .split(" ").slice(0, 2).map(w => w[0]).join("").toUpperCase();
    const roleInfo = ROLE_LABELS[user?.role] || { label: user?.role, color: "#6b7280" };
    const photo    = user?.profile_photo || null;

    const avatarHtml = photo
        ? `<img id="avatarImg" src="${photo}"
               style="width:88px;height:88px;border-radius:50%;object-fit:cover;
                      border:3px solid rgba(255,255,255,0.5);display:block">`
        : `<div id="avatarInitials"
               style="width:88px;height:88px;border-radius:50%;
                      background:rgba(255,255,255,0.25);display:flex;
                      align-items:center;justify-content:center;
                      font-size:2rem;font-weight:700;color:#fff;
                      border:3px solid rgba(255,255,255,0.3)">
               ${initials}
           </div>`;

    const content = `
    <div style="padding:var(--space-4);padding-bottom:var(--space-8)">

        <!-- ── Cabecera con foto ──────────────────────── -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);
                    padding:var(--space-8) var(--space-4) var(--space-6);
                    margin-bottom:var(--space-5);
                    background:var(--color-gradient);border-radius:var(--radius-card);
                    color:#fff;text-align:center">

            <!-- Avatar táctil -->
            <div style="position:relative;cursor:pointer" id="avatarWrapper"
                 title="Cambiar foto de perfil">

                ${avatarHtml}

                <!-- Botón de cámara superpuesto -->
                <div style="position:absolute;bottom:2px;right:2px;
                             width:28px;height:28px;border-radius:50%;
                             background:#fff;display:flex;align-items:center;
                             justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.25);
                             border:2px solid var(--color-primary)">
                    <i class="fas fa-camera"
                       style="color:var(--color-primary);font-size:11px"></i>
                </div>

                <!-- Input oculto para elegir archivo -->
                <input type="file" id="photoInput"
                       accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/*"
                       style="position:absolute;inset:0;opacity:0;cursor:pointer;
                              border-radius:50%;width:100%;height:100%">
            </div>

            <!-- Nombre y rol -->
            <div>
                <h2 style="margin:0;font-size:var(--text-xl);font-weight:700;color:#fff">
                    ${user?.name || ""}
                </h2>
                <p style="margin:4px 0 0;font-size:var(--text-sm);color:rgba(255,255,255,0.85)">
                    ${user?.email || ""}
                </p>
            </div>

            <span style="padding:4px 14px;border-radius:var(--radius-full);
                          background:rgba(255,255,255,0.2);font-size:var(--text-xs);
                          font-weight:600;color:#fff;letter-spacing:0.05em">
                ${roleInfo.label}
            </span>

            <!-- Acciones de foto -->
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-2)">
                <button id="changePhotoBtn" class="btn btn-sm"
                        style="background:rgba(255,255,255,0.2);color:#fff;
                               border:1px solid rgba(255,255,255,0.3);min-height:32px">
                    <i class="fas fa-camera"></i>
                    Cambiar foto
                </button>
                ${photo ? `
                <button id="removePhotoBtn" class="btn btn-sm"
                        style="background:rgba(0,0,0,0.15);color:rgba(255,255,255,0.8);
                               border:1px solid rgba(255,255,255,0.2);min-height:32px">
                    <i class="fas fa-trash-alt"></i>
                    Quitar foto
                </button>` : ""}
            </div>

            <p style="font-size:var(--text-xs);color:rgba(255,255,255,0.65);margin:0">
                Toca la foto o usa el botón para cambiarla
            </p>
        </div>

        <!-- Banner de estado (foto / datos) -->
        <div id="profileBanner" style="display:none;margin-bottom:var(--space-4)"></div>

        <!-- ── Formulario de datos ─────────────────────── -->
        <div style="background:var(--color-surface);border:1px solid var(--color-border);
                    border-radius:var(--radius-card);padding:var(--space-5)">

            <h3 style="margin:0 0 var(--space-5);font-size:var(--text-lg);
                        font-weight:600;color:var(--color-text)">
                <i class="fas fa-user-edit"
                   style="color:var(--color-primary);margin-right:6px"></i>
                Editar información
            </h3>

            <form id="profileForm">

                <div class="pwa-form-group">
                    <label class="pwa-label" for="prof-name">
                        Nombre completo <span style="color:var(--color-error)">*</span>
                    </label>
                    <input class="pwa-input" id="prof-name" name="name"
                           type="text" value="${user?.name || ""}" required>
                    <span class="pwa-field-error" id="err-name"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label" for="prof-email">
                        Correo electrónico <span style="color:var(--color-error)">*</span>
                    </label>
                    <input class="pwa-input" id="prof-email" name="email"
                           type="email" value="${user?.email || ""}" required>
                    <span class="pwa-field-error" id="err-email"></span>
                </div>

                <div style="border-top:1px solid var(--color-border);
                             padding-top:var(--space-4);margin-top:var(--space-2)">
                    <p style="font-size:var(--text-xs);color:var(--color-text-muted);
                               margin:0 0 var(--space-4)">
                        <i class="fas fa-lock" style="margin-right:4px"></i>
                        Deja en blanco para conservar la contraseña actual
                    </p>

                    <div class="pwa-form-group">
                        <label class="pwa-label" for="prof-pass">
                            Nueva contraseña
                        </label>
                        <div style="position:relative">
                            <input class="pwa-input" id="prof-pass" name="password"
                                   type="password" autocomplete="new-password"
                                   placeholder="Mínimo 8 caracteres"
                                   style="padding-right:48px">
                            <button type="button" id="toggleProfPass"
                                    style="position:absolute;right:0;top:0;bottom:0;
                                           width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;
                                           background:transparent;border:none;cursor:pointer;
                                           color:var(--color-text-faint)">
                                <i class="fas fa-eye" id="profPassIcon"></i>
                            </button>
                        </div>
                        <span class="pwa-field-error" id="err-password"></span>
                    </div>

                    <div class="pwa-form-group">
                        <label class="pwa-label" for="prof-pass-confirm">
                            Confirmar nueva contraseña
                        </label>
                        <input class="pwa-input" id="prof-pass-confirm"
                               name="password_confirmation"
                               type="password" autocomplete="new-password"
                               placeholder="Repite la nueva contraseña">
                        <span class="pwa-field-error" id="err-pass-confirm"></span>
                    </div>
                </div>

                <button type="submit" class="pwa-btn-primary" id="saveProfileBtn"
                        style="margin-top:var(--space-4)">
                    <i class="fas fa-check"></i>
                    Guardar cambios
                </button>

            </form>
        </div>

    </div>`;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
}

/* ── Eventos ─────────────────────────────────────────── */

function bindEvents() {

    /* Botón "Cambiar foto" y clic en el avatar → abren el input */
    document.getElementById("changePhotoBtn")
        ?.addEventListener("click", () => triggerFilePicker());
    document.getElementById("avatarWrapper")
        ?.addEventListener("click", e => {
            /* Solo si no es el input mismo */
            if (e.target.id !== "photoInput") triggerFilePicker();
        });

    function triggerFilePicker() {
        document.getElementById("photoInput")?.click();
    }

    /* Selección de archivo → comprimir y subir */
    document.getElementById("photoInput")?.addEventListener("change", async e => {
        const file = e.target.files?.[0];
        if (!file) return;

        /* Validar que sea imagen */
        if (!file.type.startsWith("image/")) {
            showBanner("error",
                '<i class="fas fa-exclamation-circle"></i> El archivo seleccionado no es una imagen.');
            return;
        }

        /* Comprimir con Canvas */
        const btn = document.getElementById("changePhotoBtn");
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        }

        try {
            const base64 = await compressImage(file, 400, 0.82);

            /* Preview inmediato */
            updateAvatarPreview(base64);

            /* Subir al backend */
            const res = await apiFetch("/profile/photo", {
                method: "POST",
                body:   JSON.stringify({ photo: base64 }),
            });

            setUser(res.user);
            showBanner("success",
                '<i class="fas fa-check-circle"></i> Foto actualizada correctamente');

        } catch (err) {
            showBanner("error",
                `<i class="fas fa-exclamation-circle"></i> ${err.message || "Error al subir la foto"}`);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-camera"></i> Cambiar foto';
            }
            /* Limpiar el input para que pueda seleccionarse el mismo archivo de nuevo */
            e.target.value = "";
        }
    });

    /* Quitar foto */
    document.getElementById("removePhotoBtn")?.addEventListener("click", async () => {
        const confirmed = await Swal.fire({
            title:             "¿Quitar foto de perfil?",
            text:              "Se volverá a mostrar tu avatar con iniciales.",
            icon:              "question",
            showCancelButton:  true,
            confirmButtonText: "Sí, quitar",
            cancelButtonText:  "Cancelar",
            confirmButtonColor:"#ef4444",
            reverseButtons:    true,
        });
        if (!confirmed.isConfirmed) return;

        try {
            await apiFetch("/profile/photo", { method: "DELETE" });
            const user = getUser();
            user.profile_photo = null;
            setUser(user);
            renderProfile(user);
            bindEvents();
            showBanner("success",
                '<i class="fas fa-check-circle"></i> Foto de perfil eliminada');
        } catch (err) {
            showBanner("error",
                `<i class="fas fa-exclamation-circle"></i> ${err.message}`);
        }
    });

    /* Toggle contraseña */
    document.getElementById("toggleProfPass")?.addEventListener("click", () => {
        const input = document.getElementById("prof-pass");
        const icon  = document.getElementById("profPassIcon");
        const show  = input.type === "password";
        input.type  = show ? "text" : "password";
        icon.className = show ? "fas fa-eye-slash" : "fas fa-eye";
    });

    /* Submit datos del perfil */
    document.getElementById("profileForm")?.addEventListener("submit", async e => {
        e.preventDefault();

        const btn  = document.getElementById("saveProfileBtn");
        const name = document.getElementById("prof-name").value.trim();
        const email= document.getElementById("prof-email").value.trim();
        const pass = document.getElementById("prof-pass").value;
        const conf = document.getElementById("prof-pass-confirm").value;

        clearErrors();
        let hasError = false;

        if (!name) { showError("name", "El nombre es obligatorio"); hasError = true; }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError("email", "Ingresa un correo válido"); hasError = true;
        }
        if (pass && pass.length < 8) {
            showError("password", "Mínimo 8 caracteres"); hasError = true;
        }
        if (pass && pass !== conf) {
            showError("pass-confirm", "Las contraseñas no coinciden"); hasError = true;
        }
        if (hasError) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        const payload = { name, email };
        if (pass) { payload.password = pass; payload.password_confirmation = conf; }

        try {
            const res = await apiFetch("/profile", {
                method: "PUT",
                body:   JSON.stringify(payload),
            });
            setUser(res.user);
            showBanner("success",
                '<i class="fas fa-check-circle"></i> Información actualizada correctamente');
            setTimeout(() => { renderProfile(res.user); bindEvents(); }, 1200);
        } catch (err) {
            showBanner("error",
                `<i class="fas fa-exclamation-circle"></i> ${err.message}`);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Guardar cambios';
        }
    });
}

/* ── Helpers de UI ───────────────────────────────────── */

function updateAvatarPreview(base64) {
    const wrapper = document.getElementById("avatarWrapper");
    if (!wrapper) return;

    /* Reemplazar el avatar con la nueva imagen */
    const existing = wrapper.querySelector("img, #avatarInitials");
    if (existing) {
        existing.replaceWith(Object.assign(document.createElement("img"), {
            id:  "avatarImg",
            src: base64,
            style: "width:88px;height:88px;border-radius:50%;object-fit:cover;" +
                   "border:3px solid rgba(255,255,255,0.5);display:block",
        }));
    }
}

function showError(field, msg) {
    const el = document.getElementById(`err-${field}`);
    if (el) el.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
}

function clearErrors() {
    document.querySelectorAll(".pwa-field-error").forEach(el => el.textContent = "");
}

function showBanner(type, html) {
    const el = document.getElementById("profileBanner");
    if (!el) return;
    const c = type === "success"
        ? { bg: "var(--color-success-light)", border: "var(--color-success-border)", color: "var(--color-success-text)" }
        : { bg: "var(--color-error-light)",   border: "var(--color-error-border)",   color: "var(--color-error-text)"   };
    el.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
        border-radius:var(--radius-btn);font-size:var(--text-sm);
        background:${c.bg};border:1px solid ${c.border};color:${c.color}`;
    el.innerHTML = html;
    el.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

/* ── Compresión de imagen con Canvas API ─────────────── */

function compressImage(file, maxPx = 400, quality = 0.82) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onerror = () => reject(new Error("No se pudo leer la imagen"));

        reader.onload = e => {
            const img = new Image();

            img.onerror = () => reject(new Error("No se pudo procesar la imagen"));

            img.onload = () => {
                let { width, height } = img;

                /* Mantener proporción, reducir al máximo maxPx × maxPx */
                if (width > height) {
                    if (width > maxPx) { height = Math.round(height * maxPx / width); width = maxPx; }
                } else {
                    if (height > maxPx) { width = Math.round(width * maxPx / height); height = maxPx; }
                }

                const canvas  = document.createElement("canvas");
                canvas.width  = width;
                canvas.height = height;

                const ctx = canvas.getContext("2d");
                /* Fondo blanco para PNGs transparentes que se convierten a JPEG */
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                /* GIFs y PNGs con transparencia se guardan como PNG, el resto como JPEG */
                const outputType = (file.type === "image/png" || file.type === "image/gif")
                    ? "image/png"
                    : "image/jpeg";

                resolve(canvas.toDataURL(outputType, quality));
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
}
