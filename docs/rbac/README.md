# RBAC granular (permisos por módulo + acción, por rol y por persona)

Fecha: 2026-09-29. Fuera del alcance de los 30 CU de la especificación oficial —
es una capacidad nueva que Jose pidió agregar directamente (no reemplaza RN06 ni
ningún requisito de la especificación; convive con ella).

**Estado: desplegado y validado en producción (2026-09-29).** Migración puramente
aditiva (3 tablas nuevas, sin tocar tablas existentes), diff 0 entre el commit y
el servidor, y probado en vivo el caso real que motivó esta ronda: dar a la
cuenta de prueba `LIDER_SEMILLERO` el permiso `sia.curate` sin tocar su rol
(confirmado en `effective` antes/después), auditado (`CREATE user_permissions`
en `/api/audits`), y limpiado al terminar. Se confirmó que ninguna ruta
existente (`/seedbeds`, `/users`, `/sia/admin/stats`) cambió de comportamiento.

## Qué resuelve

Antes de esto, el acceso era binario: 4 roles fijos (`ADMIN_SISTEMA`,
`ADMINISTRATIVO`, `LIDER_SEMILLERO`, `ESTUDIANTE`), hardcodeados en
`api/routes/api.php` (`role:A,B,C`) y en `core/crud.engine.js`
(`noCreateFor`/`noEditFor`). Cualquier ajuste de acceso requería tocar código y
desplegar.

Ahora se puede, sin tocar código:

- Ver y cambiar qué módulos/acciones tiene cada rol por defecto.
- Darle a **una persona concreta** un permiso que su rol no tiene (ej.: un
  estudiante al que se delega retroalimentar SIA), o quitarle uno que su rol
  sí tiene, sin cambiarle el rol.

`ADMIN_SISTEMA` siempre tiene acceso total — no se le puede quitar, ni desde
el panel ni por override (hardcodeado en `PermissionResolver::can()`).

## Arquitectura

- **Catálogo** (`api/config/rbac.php`): módulos + acciones, y los permisos por
  defecto de cada rol al momento de crear este sistema (espejo exacto de lo
  que ya permitían las 38 rutas `role:` — activar RBAC no cambió el acceso de
  nadie).
- **Tablas** (`2026_09_29_000004_create_rbac_tables.php`):
  - `permissions` (module, action) — el catálogo, en BD.
  - `role_permissions` (role, permission_id) — fila presente = el rol lo tiene.
  - `user_permissions` (user_id, permission_id, effect: grant|revoke) —
    excepción por persona; `grant` da un permiso que su rol no tiene, `revoke`
    quita uno que sí tiene. Sin fila, manda el rol.
- **Resolución** (`App\Services\Rbac\PermissionResolver::can($user, $modulo, $accion)`):
  1. `ADMIN_SISTEMA` → siempre `true`.
  2. Si hay override por persona → manda el override.
  3. Si no, manda lo que tenga el rol.
  Cachea el mapa de permisos efectivos por usuario 60s (`Cache::remember`),
  invalidado en cada escritura (`forgetCache`).
- **Middleware** (`->middleware('permission:modulo,accion')`, alias `permission`
  en `bootstrap/app.php`): nuevo, **en paralelo** al `role:` existente. Ninguna
  ruta lo usa todavía — se irá aplicando ruta por ruta en rondas futuras,
  reemplazando el `role:` hardcodeado, para no arriesgar producción de una vez.
- **Panel admin** (`App\Http\Controllers\Api\RbacController`, solo
  `role:ADMIN_SISTEMA`): catálogo, matriz rol×permiso editable, y
  búsqueda de persona + sus excepciones. Frontend en
  `modules/rbac/rbac.module.js`, ruta `/admin/rbac`.
- **Auditoría**: `RolePermission` y `UserPermission` están registrados en
  `AuditObserver` (`AppServiceProvider::registerAuditObservers()`) — todo
  cambio de permisos queda en la auditoría (CU29/CU30) igual que cualquier
  otro módulo administrativo.

## Cómo se siembra

```
php artisan migrate --force
php artisan db:seed --class=RbacSeeder --force
```

Idempotente: se puede correr varias veces sin duplicar filas ni tocar
`user_permissions` (los overrides por persona no los toca el seeder).

## Cómo migrar una ruta del `role:` viejo al `permission:` nuevo

1. Confirmar en `config/rbac.php` que el módulo/acción existe (si no, agregarlo
   ahí primero y correr `RbacSeeder` de nuevo).
2. Agregar `->middleware('permission:modulo,accion')` a la ruta, sin quitar
   el `role:` todavía.
3. Probar (CU por caso de uso relevante) que el comportamiento no cambió.
4. Solo entonces, quitar el `role:` de esa ruta específica.

No hacer esto en bloque para las 38 rutas de una sola vez — decisión de Jose,
2026-09-29 ("convive primero, luego reemplaza").

## v2 — Grupos de permisos (2026-09-29, misma ronda)

Personas de **distintos roles** agrupadas (ej. "Comité editorial", "Comunicados"),
a las que se les da un conjunto de permisos como grupo, sin tocar el rol de nadie.

- Tablas: `permission_groups`, `permission_group_user` (integrantes, cualquier
  rol), `permission_group_permissions` (lo que otorga el grupo).
- Un grupo **solo otorga** permisos, nunca los quita — para quitarle algo
  puntual a alguien se sigue usando la excepción por persona.
- Orden de precedencia final en `PermissionResolver::effective()`:
  1. `ADMIN_SISTEMA` → siempre total.
  2. Excepción por persona (`user_permissions`) → gana sobre todo lo demás,
     `grant` o `revoke`.
  3. Si no hay excepción: el permiso está activo si lo da el **rol o algún
     grupo** al que pertenezca (unión, no hace falta que lo den ambos).
- Endpoints nuevos bajo `/rbac`: `GET|POST /groups`, `GET|PUT|DELETE
  /groups/{id}`, `PUT /groups/{id}/members`, `PUT /groups/{id}/permissions`,
  y `GET /users-lite` (listado liviano para el buscador del panel).
- Limitación conocida: los cambios de integrantes/permisos de un grupo
  (`sync()` en una relación many-to-many) no generan fila de auditoría
  automática como sí lo hacen crear/renombrar/borrar el grupo (eventos de
  modelo Eloquent normales). Si se necesita trazar cada cambio de membresía,
  se debe loguear explícitamente en el controlador — pendiente si Jose lo pide.

## UX/UI del panel (2026-09-29)

Rediseño en la misma ronda, con foco en usabilidad (17 módulos era demasiado
para una sola pantalla plana):

- 3 pestañas: **Roles** (permisos por defecto), **Grupos** (esta sección) y
  **Personas** (excepción puntual) — antes todo estaba junto en una sola vista.
- Cada módulo es un `<details>` (acordeón nativo, accesible por teclado sin JS
  extra); se abre solo si ya tiene algo marcado, con una insignia mostrando
  cuántas acciones tiene activas.
- El botón de guardar queda flotante (`position: sticky`) al fondo del panel
  para no tener que hacer scroll de vuelta arriba tras marcar algo.

## Pendiente (fuera de esta ronda)

- Roles completamente dinámicos (crear roles nuevos desde el panel, no solo
  los 4 actuales) — decisión explícita de Jose: no entra en v1.
- Reemplazar el `role:` hardcodeado de las 38 rutas y los
  `noCreateFor`/`noEditFor` del frontend por el `permission:` nuevo, ruta por
  ruta, CU por CU.
