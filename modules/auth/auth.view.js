/* #archivo: frontend/modules/auth/auth.view.js
   Diseño sincronizado con login.php (fuente de verdad)
   ─────────────────────────────────────────────────────
   Paleta:  rojo/naranja (#EF4444 / #F97316)
   Fondo:   #f8fafc con patrón radial
   Card:    blanco, card-shadow, rounded-2xl
   Inputs:  border-gray-300, rounded-lg, focus:ring-red-500
   Botón:   gradiente rojo → naranja, btn-hover
   ─────────────────────────────────────────────────────── */

export function LoginView() {

  return `
  <div class="login-bg">

    <!-- ── HEADER ──────────────────────────────────── -->
    <header class="login-header">
      <div class="login-header-inner">

        <div class="login-header-brand">
          <img src="assets/images/login/logo.png"
               alt="Universidad del Tolima"
               style="height:40px;width:auto">
          <div>
            <h1>Universidad del Tolima</h1>
            <p>Sistema de Semilleros IDEAD</p>
          </div>
        </div>

        <nav class="login-header-links">
          <a href="https://www.ut.edu.co" target="_blank" rel="noopener">
            <i class="fas fa-globe" style="margin-right:4px"></i>Sitio Web
          </a>
        </nav>

      </div>
    </header>

    <!-- ── MAIN ────────────────────────────────────── -->
    <main class="login-main">
      <div class="login-grid">

        <!-- Lado izquierdo — bienvenida -->
        <div class="login-welcome">

          <div class="login-welcome-icon">
            <i class="fas fa-seedling"></i>
          </div>

          <h2>
            ¡Gestiona tus
            <span class="text-gradient">semilleros</span>
            de investigación!
          </h2>

          <p>
            Accede a la plataforma institucional y administra
            semilleros, proyectos y propuestas de investigación
            de forma fácil y segura.
          </p>

        </div>

        <!-- Lado derecho — formulario -->
        <div class="login-form-side">
          <div class="login-card">

            <!-- Header del card -->
            <div class="login-card-header">

              <div class="login-logo-wrapper">
                <img src="assets/images/login/logo.png"
                     alt="Universidad del Tolima" class="login-logo">
              </div>

              <h1>Universidad del Tolima</h1>
              <p>Sistema de Semilleros IDEAD</p>

              <!-- Barra azul-verde — idéntica a login.php -->
              <div class="login-divider"></div>

            </div>

            <!-- CU02: ingreso con Google institucional. Oculto hasta confirmar que
                 el servidor tiene Client ID (GET /api/auth/config). -->
            <section id="google-login" class="login-google" aria-labelledby="google-login-title" hidden>
              <p id="google-login-title" class="login-google-title">
                Estudiantes: ingrese con su cuenta <strong>@ut.edu.co</strong>
              </p>
              <div id="google-btn" class="login-google-btn"></div>
              <div id="google-alert" class="login-alert" role="alert" hidden>
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span id="google-alert-text"></span>
              </div>
              <div class="login-or" aria-hidden="true"><span>o con correo y contraseña</span></div>
            </section>

            <!-- Formulario -->
            <form id="loginForm" novalidate>

              <!-- Email -->
              <div class="login-input-group">
                <label for="email">
                  <i class="fas fa-user"
                     style="margin-right:6px;color:var(--color-text-4)"></i>
                  Correo electrónico
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  class="login-input"
                  placeholder="correo@ut.edu.co"
                  autocomplete="username"
                  aria-describedby="err-email"
                  required>
                <span class="login-field-error" id="err-email"></span>
              </div>

              <!-- Contraseña -->
              <div class="login-input-group">
                <label for="password">
                  <i class="fas fa-lock"
                     style="margin-right:6px;color:var(--color-text-4)"></i>
                  Contraseña
                </label>
                <div style="position:relative">
                  <input
                    type="password"
                    id="password"
                    name="password"
                    class="login-input"
                    placeholder="Ingrese su contraseña"
                    autocomplete="current-password"
                    aria-describedby="err-password"
                    style="padding-right:48px"
                    required>
                  <button
                    type="button"
                    id="togglePassword"
                    style="
                      position:absolute;right:0;top:0;bottom:0;
                      display:flex;align-items:center;
                      padding:0 var(--space-4);
                      background:transparent;color:var(--color-text-4);
                      border:none;cursor:pointer;min-height:unset;
                      transition:color var(--transition-fast)
                    "
                    aria-label="Mostrar/ocultar contraseña">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                  </button>
                </div>
                <span class="login-field-error" id="err-password"></span>
              </div>

              <!-- CU01 A2: Recordarme (sesión de 30 días en este navegador) -->
              <label class="login-remember" for="remember">
                <input type="checkbox" id="remember" name="remember">
                <span>Recordarme en este equipo</span>
              </label>

              <!-- CU01 E2/E3/E4: errores del servidor, anunciados (role=alert) -->
              <div id="login-alert" class="login-alert" role="alert" hidden>
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span id="login-alert-text"></span>
              </div>

              <!-- Botón de envío — idéntico al de login.php -->
              <button type="submit" class="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                Ingresar
              </button>

            </form>

            <!-- Links -->
            <div class="login-links">
              <a href="/forgot-password" class="login-link-primary">
                <i class="fas fa-key"></i>
                ¿Olvidó su contraseña?
              </a>
              <a href="https://investigaciones.ut.edu.co/semilleros/idead.html" target="_blank" rel="noopener"
                 class="login-link-secondary">
                <i class="fas fa-question-circle"></i>
                ¿Necesita ayuda?
              </a>
            </div>

            <!-- Advertencia — idéntica al bloque amarillo de login.php -->
            <div class="login-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <p>
                <strong>Importante:</strong>
                Asegúrese de usar las credenciales institucionales
                de la Universidad del Tolima.
              </p>
            </div>

          </div>
        </div>

      </div>
    </main>

    <!-- ── FOOTER ───────────────────────────────────── -->
    <footer class="login-footer">
      <div class="login-footer-inner">
        <span>© 2026 Universidad del Tolima — Sistema de Semilleros IDEAD</span>
        <div class="login-footer-links">
          <a href="https://administrativos.ut.edu.co/images/RES._0676_DEL_27-05-19_ADOPTA_MANUAL_DE_POLITICAS.pdf"
             target="_blank" rel="noopener">Tratamiento de datos personales</a>
          <a href="http://administrativos.ut.edu.co/atencion-al-ciudadano/directorio.html?cck=contactos&amp;du_id_oficina=871&amp;boxchecked=0&amp;search=contactos&amp;task=search"
             target="_blank" rel="noopener">Contacto</a>
        </div>
      </div>
    </footer>

  </div>
  `;
}
