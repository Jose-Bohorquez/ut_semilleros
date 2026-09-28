# Manual de instalación

Cubre dos escenarios: instalar el proyecto en un equipo local para desarrollo (Docker), y desplegar
una actualización en el servidor de producción (Hostinger, hosting compartido).

Criterio de aceptación (RNF04): un técnico instala el sistema en menos de 30 minutos siguiendo este
manual.

## 1. Instalación local (Docker) — 5 a 10 minutos

### Requisitos

- Docker y Docker Compose.
- Git.
- Puertos libres: 8080 (frontend), 8000 (API), 3306 (MySQL), 8081 (phpMyAdmin).

### Pasos

```bash
git clone git@github.com:Jose-Bohorquez/ut_semilleros.git
cd ut_semilleros
./scripts/dev-setup.sh
```

El script es **idempotente** (se puede correr varias veces sin duplicar datos) y hace, en orden:

1. Copia `api/.env.example` a `api/.env` si no existe.
2. Levanta los contenedores (`docker compose up -d --build`).
3. Instala las dependencias de PHP (`composer install`).
4. Genera la clave de la aplicación (`APP_KEY`) y da permisos de escritura a `storage/` y
   `bootstrap/cache/`.
5. Espera a que la base de datos esté lista, corre las migraciones y siembra datos de ejemplo (solo
   si la base está vacía).

### Verificar que quedó bien

- Frontend: `http://localhost:8080` — debe mostrar la pantalla de inicio de sesión.
- API: `http://localhost:8000/api/auth/config` — debe responder JSON.
- Pruebas automatizadas: `docker compose exec api php artisan test` — deben pasar todas.
- Usuarios de ejemplo: ver `api/database/seeders/UserSeeder.php` (todos con la misma contraseña de
  ejemplo, definida ahí).

### Problema conocido: los correos no aparecen en el log

El driver de correo local (`MAIL_MAILER=log`) escribe en `api/storage/logs/laravel.log`. Si ese
archivo se borra y se vuelve a crear por fuera del flujo normal (por ejemplo, con un comando manual
dentro del contenedor corriendo como root), puede quedar con permisos que el proceso de Apache
(`www-data`) no puede escribir — la petición HTTP responde 500 con «Permission denied». El paso 4
del script ya deja `storage/` con permisos abiertos; si el problema vuelve a aparecer, corre:

```bash
docker compose exec api chmod -R a+rwX storage bootstrap/cache
```

## 2. Despliegue de una actualización en producción

Producción corre en Hostinger, hosting compartido (PHP-FPM, sin Docker, sin acceso root). El patrón
de despliegue es:

1. **Respaldo** antes de cualquier cambio: archivos que se van a reemplazar y las tablas de base de
   datos que la migración va a tocar.
2. **Lint** de los archivos PHP en una carpeta de prueba en el servidor (`php -l`), antes de
   instalarlos.
3. **Migraciones**, revisadas primero con `php artisan migrate --pretend --force` (muestra el SQL
   sin ejecutarlo) y luego con `php artisan migrate --force`.
4. **Archivos del frontend**, subidos en 2 o 3 tandas separadas por unos minutos, para darle tiempo
   al CDN de Hostinger de dejar de servir la versión anterior en caché.
5. **`php artisan route:clear` y `config:clear`** después de tocar el backend.
6. **Validación en vivo** con usuarios de prueba desechables (prefijo `qa_temp_`), que se borran al
   terminar.

Las credenciales de producción (`api/.env` del servidor, llaves SSH) nunca se documentan en el
repositorio, porque es público en GitHub. Quien vaya a desplegar necesita que alguien con acceso al
servidor le comparta esos datos por un canal seguro, fuera del repositorio.

## 3. Variables de entorno relevantes (`api/.env`)

| Variable | Para qué |
|---|---|
| `APP_KEY` | Cifra datos sensibles (ej. teléfono de coordinadores) y firma las sesiones. Sin respaldo, un `APP_KEY` perdido vuelve ilegibles los datos cifrados. |
| `DB_*` | Conexión a la base de datos. |
| `MAIL_*` | Envío de correos (activación de cuenta, recuperación de contraseña). |
| `QUEUE_CONNECTION` | En producción, `database`, con un cron ejecutando `queue:work` (ver manual técnico, sección «Colas»). En Docker local, `sync`. |
| `GOOGLE_CLIENT_ID` / `GOOGLE_ALLOWED_DOMAINS` | Login con Google institucional (CU02). Sin `GOOGLE_CLIENT_ID`, el botón de Google no aparece y el sistema sigue funcionando solo con correo y contraseña. |
| `GROQ_API_KEY` / `GROQ_API_KEYS` | El asistente SIA. Sin ellas, SIA responde solo con la base de conocimiento local. |
