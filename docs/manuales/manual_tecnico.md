# Manual técnico

Para quien vaya a dar mantenimiento o seguir desarrollando Semilleros UT.

## 1. Arquitectura

- **Frontend:** SPA en JavaScript plano (sin framework), módulos ES (`import`/`export`), servida
  como archivos estáticos. Instalable como PWA (service worker + manifest).
- **Backend:** API REST en Laravel 12, autenticación con Sanctum (tokens Bearer, sin cookies de
  sesión ni CSRF — cada endpoint protegido exige `Authorization: Bearer <token>`).
- **Base de datos:** MySQL.
- **Producción:** Hostinger, hosting compartido (PHP-FPM, sin Docker, sin acceso root, sin proceso
  persistente propio — solo cron).

### Estructura de carpetas

```
/                       raíz del frontend (index.html, app.js, service-worker.js)
├── core/               router, motor CRUD genérico, utilidades (escape, password-policy…)
├── layout/             barra lateral y encabezado, comunes a todas las pantallas
├── modules/            un módulo por pantalla/entidad (auth, users, faculties, cats, pwa/…)
├── services/           llamadas HTTP (api.service.js) y almacenamiento local (storage.service.js)
├── css/                estilos (theme.css con tokens de diseño, pwa.css)
├── docs/                documentación (especificación, validación, manuales, SIA)
└── api/                backend Laravel
    ├── app/Http/Controllers/Api/   un controlador por entidad
    ├── app/Models/                 modelos Eloquent
    ├── app/Support/                utilidades compartidas (PasswordPolicy, MailBrand…)
    ├── database/migrations/        una migración por cambio de esquema
    ├── database/seeders/           datos de ejemplo para desarrollo
    ├── resources/views/emails/     plantillas de correo
    └── tests/Feature/              pruebas automatizadas (PHPUnit)
```

## 2. Patrón de un módulo CRUD nuevo (backend)

1. Migración: tabla o columnas nuevas, con `status` (`ACTIVO`/`INACTIVO`) si aplica — nunca se
   elimina físicamente (RN01).
2. Modelo: `$fillable` explícito. Si algún campo es sensible y no debe llegar por formulario
   (ej. `google_id`, `data_consent_at`), se deja fuera de `$fillable` y solo se escribe con
   `forceFill()` desde el controlador.
3. Controlador: `index/store/update/toggleStatus`, sin `destroy` (así una petición `DELETE` a esa
   ruta responde 405 automáticamente, porque la URL existe para otros métodos).
4. Registrar el modelo en `AppServiceProvider::registerAuditObservers()` para que quede en
   auditoría (RN07).
5. Rutas en `api/routes/api.php`, con el middleware `role:...` correspondiente.
6. Prueba en `api/tests/Feature/UseCases/RFxx....php`.

## 3. Patrón de un módulo CRUD nuevo (frontend)

`core/crud.engine.js` es genérico: un módulo nuevo solo declara sus `fields` (nombre, etiqueta,
tipo, si es obligatorio) y lo registra en `core/router.js`. Ver `modules/cats/cats.module.js` como
ejemplo corto.

## 4. Seguridad

- **Contraseñas (RN10):** política centralizada en `api/app/Support/PasswordPolicy.php` (servidor)
  y `core/password-policy.js` (cliente, mismo criterio para feedback inmediato). Cualquier
  formulario nuevo que pida una contraseña debe usar ambos.
- **Datos personales sensibles:** se cifran en reposo con `protected function casts(): array {
  return ['campo' => 'encrypted']; }` en el modelo (ejemplo: `Coordinator::$phone`). Dependen de
  `APP_KEY`: si se pierde, esos datos quedan ilegibles.
- **Límites de peticiones:** login (`RN14`, 5/min), recuperar contraseña (`CU04 E3`, 3/10min por
  correo), Google (10/min), SIA (15/min), y un límite general de la API (120/min por usuario,
  resuelto desde el token en `AppServiceProvider`).
- **Cabeceras:** `App\Http\Middleware\SecurityHeaders` (global) agrega `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y quita `X-Powered-By` de toda
  respuesta. El `.htaccess` de la raíz agrega las mismas para los archivos estáticos.
- **Auditoría (RN07):** vía `AuditObserver` en los modelos, más entradas manuales para eventos que
  no son cambios de modelo (`LOGIN`, `LOGOUT`, `PASSWORD_RESET`, `CONSENT`).

## 5. Colas (para CU04 E4 y otros correos)

Las notificaciones que dependen del servidor SMTP (`CustomResetPasswordNotification`) implementan
`ShouldQueue` con 3 reintentos. En producción esto requiere un cron que procese la cola:

```
* * * * * cd /home/USUARIO/domains/ut-edu.online/public_html/api && php artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
```

Sin este cron, los correos de recuperación de contraseña quedan encolados sin enviarse. En Docker
local, `QUEUE_CONNECTION=sync` hace que se procesen al instante, sin necesitar cron.

Si un job agota sus 3 intentos, cae a la tabla `failed_jobs` (no se pierde): se puede reintentar con
`php artisan queue:retry all`.

## 6. Pruebas

- **Backend:** `php artisan test` (PHPUnit). `phpunit.xml` fuerza SQLite en memoria, `bcrypt` a
  costo 4 (por velocidad) y `QUEUE_CONNECTION=sync`, así que las pruebas no dependen de MySQL, de
  un servidor de correo real ni de un worker de colas.
- **End-to-end:** scripts con Puppeteer (fuera del repositorio, en el flujo de trabajo de
  desarrollo) que abren el sitio en un navegador real y prueban un caso de uso completo, en
  escritorio y en móvil.

## 7. SIA (asistente con IA)

- `api/app/Services/Sia/`: `SiaAssistant` (orquesta), `SiaLocalResponder` (respuestas locales sin
  llamar a la API, por intención o por coincidencia con `sia_knowledge`), `GroqKeyPool` (rota entre
  varias cuentas de Groq).
- `api/resources/sia/base-conocimiento.md`: la memoria técnica que SIA usa como contexto.
  **Actualízala cuando cambie una función del sistema** — si no, SIA sigue respondiendo con
  información vieja.

## 8. Grafo de conocimiento (graphify)

El proyecto mantiene un mapa de todo el código y la documentación con `graphify`
(`graphify-out/graph.json`, `graphify-out/GRAPH_REPORT.md`). Después de un cambio grande, se
actualiza con el script incremental del proyecto (no con `graphify update .` a secas: eso descarta
los nodos de documentación).

## 9. Dónde está cada cosa (referencia rápida)

| Qué | Dónde |
|---|---|
| Especificación funcional (CU, RF, RN, RNF) | `docs/especificacion/` |
| Actas de validación por caso de uso | `docs/validacion/CUxx.md` |
| Documentación de SIA | `docs/sia/README.md` |
| Manuales (este documento y los demás) | `docs/manuales/` |
| Reglas y prohibiciones del proyecto | `CLAUDE.md` (raíz) |
