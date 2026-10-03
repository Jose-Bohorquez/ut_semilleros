# Envíos asíncronos: avisos push y correos por la cola (2026-10-03)

| Campo | Valor |
|---|---|
| Pedido | Jose: «todo debería ser asíncrono», opción B (cola real con reintentos) |
| Relación con la spec | CU04 E4 (reintentos del correo), CU06 (activación), CU24/CU27 (avisos al estudiante), anuncios |
| Commits | `5398811` audits:prune · `e9df7d8` avisos push · `b37bf6c` cola · `9f3f7a4` caché v28 |
| Ambiente | **Producción desde el 2026-10-03** (diff 0, 19 archivos) con `QUEUE_CONNECTION=sync` |
| Pendiente | **Jose agrega el cron en hPanel → se cambia a `QUEUE_CONNECTION=database`** |

## Qué cambió

- **Avisos push:** `PushSender::queue()` encola un `App\Jobs\DeliverPushNotification` por dispositivo. Si un
  dispositivo falla, solo ese se reintenta y nadie recibe el aviso dos veces. 3 intentos (30 s, 2 min), `afterCommit`.
  Suscripción caducada (404/410): se borra. Sin claves VAPID: no reintenta. Agotados los intentos: `failed_jobs`.
- **Correo de activación** (`AccountActivationNotification`): `ShouldQueue` con 3 intentos, como ya era el de CU04.
- La notificación de la campana se guarda en el acto; solo el push va a la cola.
- La **notificación de prueba** del perfil (`POST /push-subscriptions/test`) se entrega en el acto a propósito: su
  función es diagnosticar.
- Con `sync` (producción hoy) todo se entrega en el acto, sin reintentos, y un fallo de push no rompe la operación.

## Pruebas

641 tests en verde (11 nuevos en `AsyncDeliveryTest`): la petición no entrega (solo encola, uno por dispositivo y
solo a la audiencia del anuncio), la prueba de diagnóstico sigue siendo inmediata, el job lanza para reintentar,
borra la suscripción caducada, no reintenta sin VAPID, ignora suscripciones dadas de baja y, con `sync`, un fallo de
push no rompe la aprobación de una solicitud. El correo de activación se encola con 3 intentos y `afterCommit`.

## Despliegue

Paquete incremental de 19 archivos sobre `813fe80` (sin migraciones; las tablas `jobs` y `failed_jobs` ya existían).
Respaldo `~/backups/ut-edu.online/2026-10-03_pre_async/files.tgz`. Lint con PHP 8.2 del servidor sin errores. Cachés de
config y rutas limpiadas. **Diff 0** (19/19 iguales). `semilleros-v28` y `app.js?v=6` publicados.

Comprobado en vivo:

- Las clases nuevas cargan; el job tiene 3 intentos, espera 30 s/2 min y `afterCommit`; un job sobre una suscripción
  inexistente termina sin error; el correo de activación es `ShouldQueue`.
- **El comando exacto del cron** (`/usr/bin/php …/api/artisan queue:work database --stop-when-empty --tries=3
  --max-time=50`) corre y termina con la cola vacía (exit 0). `jobs=0`, `failed_jobs=0`.
- Cuenta de prueba ESTUDIANTE: `POST /push-subscriptions/test` → 200, entregado a 1 de 9 suscripciones registradas
  (las demás no respondieron como entregadas: probablemente navegadores de pruebas automáticas ya cerrados). Sesión
  cerrada al terminar.

No verificado: reintentos reales en producción (requieren el cron) y la llegada del aviso al teléfono de un
estudiante al aprobar su solicitud.

## Para activar la cola (Jose)

1. hPanel → Avanzado → Tareas Cron → «Personalizado», cada minuto (`* * * * *`), comando:
   `/usr/bin/php /home/u682531786/domains/ut-edu.online/public_html/api/artisan queue:work --stop-when-empty --tries=3 --max-time=50`
2. Avisar para cambiar `QUEUE_CONNECTION=database` en `api/.env` de producción (+ `config:clear`) y validar que la
   tabla `jobs` vuelve a 0 en menos de un minuto. Detalle en `docs/manuales/manual_tecnico.md` §5.
