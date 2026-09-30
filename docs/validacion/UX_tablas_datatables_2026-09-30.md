# Tablas DataTables + responsivas (todas, web y PWA)

| Campo | Valor |
|---|---|
| Fecha | 2026-09-30 |
| Ambiente | Producción (validado en vivo, origin sirve código actualizado) |
| Estado | **Desplegado en producción** |

## Pedido de Jose

"Todas las tablas deben ser DataTables, y además ser responsivas, no quiero desbordamientos, ni
en web, ni en la PWA." Más un reporte aparte: en el módulo de semilleros, cuando el estudiante ve
un semillero, no le sale nada en misión, visión y objetivos.

## Aclaración: lo de misión/visión/objetivos no es una falla de código

Se verificó en vivo (cuenta ESTUDIANTE real) y por API directa: los 2 semilleros reales en
producción tienen `mision`, `vision` y `objetivo_general` en `NULL` en la base de datos — nunca
se llenaron. El frontend ya muestra el mensaje correcto ("Sin misión registrada.", "Este
semillero aún no tiene un objetivo general registrado.") en vez de dejarlo en blanco — confirmado
con captura de pantalla real. Es un hueco de datos, documentado desde CU13 Ronda A
("los 2 semilleros ya en producción no los tienen, se les exigirá al próximo editarlos"), no un
bug. **Acción pendiente de Jose:** editar esos 2 semilleros desde `/admin/seedbeds` y llenar esos
3 campos.

## Inventario de tablas (hecho antes de tocar código)

- **15 módulos ya usan DataTables** vía `core/crud.engine.js` (`createCrudModule`): areas, audits,
  cats, coordinators, faculties, groups, objectives, products, programs, projects, proposals,
  requests, results, seedbeds, users.
- **2 tablas NO estaban bien**, encontradas por fuera de `crud.engine.js`:
  - `modules/seedbed-members/seedbed-members.module.js` ("Ver integrantes" de un semillero): sí
    tenía DataTables, pero le faltaba la clase `mobile-card-table` y los `data-label` en cada
    `<td>` — en móvil quedaba una tabla ancha sin el mecanismo de "cards" que usa el resto del
    sistema.
  - `modules/project-members/project-members.module.js` ("Ver miembros" de un proyecto): **no
    tenía DataTables en absoluto** — tabla plana, sin buscador, paginación ni exportación, y
    tampoco tenía `mobile-card-table`. Bug real.
- **1 hueco de CSS real**: `.dataTables_wrapper { overflow-x: auto }` solo existía en
  `css/pwa.css`, dentro de `@media (max-width:768px)`. En la web de escritorio (fuera de la PWA),
  una tabla con muchas columnas (ej. auditoría) podía desbordar el layout en vez de generar su
  propio scroll horizontal, porque esa regla no existía fuera del breakpoint móvil.
- **1 tabla intencionalmente fuera de DataTables**: `modules/sia-admin/sia-admin.module.js`
  (listado de conversaciones de SIA) — tiene su propia paginación y filtros del lado del
  servidor (no es un CRUD de lista completa); ya usa `table-responsive` + `mobile-card-table`
  para no desbordar. Convertirla a DataTables duplicaría la paginación (una del servidor, otra
  del cliente) — se dejó como está a propósito.
- **1 código muerto encontrado, no tocado**: `modules/faculties/faculties.view.js` +
  `faculties.controller.js` — tabla HTML plana sin ningún mecanismo responsive, pero **no la
  importa ningún archivo del proyecto** (Facultades ya usa `faculties.module.js` con
  `crud.engine.js`). No se tocó por estar fuera del pedido explícito de esta ronda; queda anotado
  para una futura limpieza de código muerto si Jose la pide.

## Cambios

1. `seedbed-members.module.js`: clase `mobile-card-table` en la tabla + `data-label` en cada
   `<td>` (ID, Nombre, Email, Rol, Acciones).
2. `project-members.module.js`: mismo `data-label` + `mobile-card-table`, y se agregó la
   inicialización de DataTable completa (buscador, paginación en español, botones
   copiar/Excel/PDF/imprimir) — antes no existía.
3. `style.css`: `.dataTables_wrapper { overflow-x: auto }` sin restringir a mobile, para que
   ninguna tabla de la web de escritorio pueda desbordar el layout.

## No se tocó

Los 15 módulos que ya usaban `crud.engine.js` correctamente. La tabla de SIA (justificado arriba).
Ningún endpoint del backend.

## Pruebas

- **PHPUnit: 336 en verde** (sin cambios de backend).
- **Producción:** diff 0 en los 4 archivos subidos. Verificado por fetch directo (bypass de
  caché) que el origen sirve el código nuevo en los 2 módulos de integrantes.
- Pendiente: validación visual completa de "Ver integrantes"/"Ver miembros" en un viewport móvil
  real (se verificó el código y el patrón CSS, pero no se forzó un semillero/proyecto con
  integrantes reales para capturar la tabla ya convertida a tarjetas en pantalla angosta).
