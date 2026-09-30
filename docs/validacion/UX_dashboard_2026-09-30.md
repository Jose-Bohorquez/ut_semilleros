# Mejora de UI/UX del Dashboard (todos los roles)

| Campo | Valor |
|---|---|
| Fecha | 2026-09-30 |
| Alcance | `modules/dashboard/dashboard.view.js`, `dashboard.controller.js`, `style.css`, versionado (`app.js`, `core/router.js`, `index.html`, `service-worker.js`) |
| Ambiente | Producción (validado en vivo con cuentas reales admin y líder) |
| Estado | **Desplegado en producción** |

## Motivo

Jose reportó que el dashboard web "está al 10%" y pidió mejorarlo mucho para todos los roles,
sin romper lo que ya funciona. Se revisó en vivo (Chrome DevTools, cuentas reales por rol) antes
de tocar código.

## Hallazgo real corregido: gráfico "Semilleros por facultad" siempre vacío

`buildSeedbedsByFaculty()` en `dashboard.controller.js` leía `seedbed.program_id` — columna
eliminada desde CU13 Ronda B (2026-09-29), cuando programa/área pasaron a relación múltiple
(`seedbed.programs[]`). El gráfico llevaba mostrando "Aún no hay semilleros asociados a
facultades" desde entonces para **todos** los administradores, con datos reales existentes.
Corregido para recorrer `seedbed.programs[]` (un semillero cuenta una vez por cada facultad
distinta a la que pertenece, vía sus programas).

## Hallazgo de diseño: dashboard de Líder/Estudiante muy vacío

Confirmado visualmente: Líder de semillero solo tenía 3 tarjetas KPI + "Acceso rápido" — el resto
de la pantalla (más de la mitad, en 1440px) quedaba en blanco. No hay gráficas para estos roles
(por diseño, no ven datos globales), así que el vacío era real, no percibido.

**Corregido con una sección nueva "Actividad reciente"**, reutilizando los datos que
`loadKPIs()` ya trae (propuestas y solicitudes) — **sin llamadas nuevas a la API**. Combina
ambas listas en una sola línea de tiempo, ordenada por fecha, con ícono, título, badge de estado
y tiempo relativo ("hace 54 min", "28 de jul"). Aplica a todos los roles: Líder/Administrativo ven
la actividad del sistema, Estudiante ve la suya (los endpoints `/my` ya estaban filtrados).

## Polish visual (sin romper nada existente)

- Textura decorativa sutil (`aria-hidden`) detrás del saludo de bienvenida, con el gradiente de
  marca ya existente (`--gradient-accent`), opacidad 8%.
- Entrada escalonada de las tarjetas al cargar (`kpi-card`, `sys-card`, `chart-card`,
  `quick-link-card`, `activity-row`): 350ms, respeta `prefers-reduced-motion` (se desactiva por
  completo si el usuario lo pide, como exige la checklist de accesibilidad).
- Badge de estado "Rechazada" (`sys-card-badge.is-err`) — no existía la variante roja, solo
  ok/warn.

## No se tocó

Colores de marca, tipografía, sidebar, estructura de KPIs/gráficas existente, ningún endpoint del
backend. Cero cambios en `api/`.

## Pruebas

- **PHPUnit: 336 en verde** (sin cambios de backend, solo confirmación de que nada se rompió).
- **Producción:** diff 0 en los 7 archivos subidos (`dashboard.view.js`, `dashboard.controller.js`,
  `style.css`, `app.js`, `core/router.js`, `index.html`, `service-worker.js`). Cache-busting
  actualizado en cascada (`app.js` v3→v4, `router.js` v2→v3, `dashboard.*.js` v2→v3, `style.css`
  v16→v17, `CACHE_NAME` v18→v19) para que el CDN de Hostinger (TTL 300s) no sirviera versiones
  viejas.
- Validación en vivo con cuenta ADMIN_SISTEMA real: gráfico de facultades ahora muestra datos
  reales (antes vacío); actividad reciente poblada con eventos reales del sistema.
- Validación en vivo con cuenta LIDER_SEMILLERO real: ya no hay una zona en blanco grande debajo
  de "Acceso rápido"; actividad reciente poblada. Sin errores en consola del navegador.

## Ronda 2 (mismo día, a petición de Jose de profundizar más)

Jose pidió seguir mejorando la UX del dashboard, "se le falta mucho". Se agregó:

- **"Mi semillero" (solo Líder):** tarjeta con nombre, estado, facultad(es) y número de
  integrantes del semillero que lidera — **sin llamada nueva a la API**, filtra client-side sobre
  los datos que `loadKPIs()` ya trae, usando `members_count`/`faculty_names` (campos que el
  backend ya calculaba desde CU16). Si el líder no lidera ningún semillero todavía, muestra un
  estado vacío honesto en vez de nada — validado en vivo con la cuenta de líder de prueba: **hoy
  ningún semillero real en producción tiene un líder asignado por pivot** (CU21, integrantes,
  aún no está implementado), así que el estado vacío es correcto, no un bug.
- CSS nuevo (`.my-seedbed-grid`, `.my-seedbed-card`) con la misma animación de entrada y el mismo
  lenguaje visual que el resto del dashboard.

## Pendiente

- No se rediseñó la vista de Estudiante en detalle (solo se benefició de "Actividad reciente"
  genérica) — pendiente de una ronda específica si Jose quiere algo más allá de esto.
- La tarjeta "Mi semillero" no se pudo validar visualmente con datos reales (llena) porque ningún
  semillero en producción tiene líder asignado aún — se validará en cuanto CU21 (integrantes) se
  implemente y haya al menos un líder real asignado.
