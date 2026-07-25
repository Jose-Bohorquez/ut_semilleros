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
          <a href="https://www.ut.edu.co" target="_blank" rel="noopener">
            <i class="fas fa-book" style="margin-right:4px"></i>Manuales
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

          <!-- Features (idéntico al grid de login.php) -->
          <div class="login-features">

            <div class="login-feature-item">
              <div class="login-feature-icon"
                   style="background:#DCFCE7;color:#16A34A">
                <i class="fas fa-shield-alt"></i>
              </div>
              <span style="color:var(--color-text-2);font-size:var(--text-sm)">Acceso Seguro</span>
            </div>

            <div class="login-feature-item">
              <div class="login-feature-icon"
                   style="background:#DBEAFE;color:#2563EB">
                <i class="fas fa-clock"></i>
              </div>
              <span style="color:var(--color-text-2);font-size:var(--text-sm)">Disponible 24/7</span>
            </div>

            <div class="login-feature-item">
              <div class="login-feature-icon"
                   style="background:#F3E8FF;color:#9333EA">
                <i class="fas fa-mobile-alt"></i>
              </div>
              <span style="color:var(--color-text-2);font-size:var(--text-sm)">Multiplataforma</span>
            </div>

            <div class="login-feature-item">
              <div class="login-feature-icon"
                   style="background:#FEF9C3;color:#CA8A04">
                <i class="fas fa-headset"></i>
              </div>
              <span style="color:var(--color-text-2);font-size:var(--text-sm)">Soporte Técnico</span>
            </div>

          </div>

          <!-- Social links -->
          <div class="login-social">
            <a href="https://www.facebook.com/comunicacionesuniversidaddeltolima"
               target="_blank" rel="noopener"
               style="background:#1D4ED8"
               title="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com/Uni_Tolima"
               target="_blank" rel="noopener"
               style="background:#0EA5E9"
               title="Twitter / X">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="https://www.instagram.com/uni_tolima"
               target="_blank" rel="noopener"
               style="background:#EC4899"
               title="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
          </div>

        </div>

        <!-- Lado derecho — formulario -->
        <div class="login-form-side">
          <div class="login-card">

            <!-- Header del card -->
            <div class="login-card-header">

              <div class="login-logo-wrapper">
                <img src="assets/images/login/logo.png"
                     alt="Universidad del Tolima"
                     style="height:96px;width:auto">
              </div>

              <h1>Universidad del Tolima</h1>
              <p>Sistema de Semilleros IDEAD</p>

              <!-- Barra azul-verde — idéntica a login.php -->
              <div class="login-divider"></div>

            </div>

            <!-- Formulario -->
            <form id="loginForm">

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
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
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

              <!-- Botón de envío — idéntico al de login.php -->
              <button type="submit" class="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar sesión
              </button>

            </form>

            <!-- Links -->
            <div class="login-links">
              <a href="#" class="login-link-primary">
                <i class="fas fa-key"></i>
                ¿Olvidaste tu contraseña?
              </a>
              <a href="https://www.ut.edu.co" target="_blank" rel="noopener"
                 class="login-link-secondary">
                <i class="fas fa-question-circle"></i>
                ¿Necesitas ayuda?
              </a>
            </div>

            <!-- Advertencia — idéntica al bloque amarillo de login.php -->
            <div class="login-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <p>
                <strong>Importante:</strong>
                Asegúrate de usar las credenciales institucionales
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
          <a href="#">Términos de Uso</a>
          <a href="#">Privacidad</a>
          <a href="#">Contacto</a>
        </div>
      </div>
    </footer>

  </div>
  `;
}
