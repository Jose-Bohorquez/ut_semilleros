# Selección múltiple + activar/inactivar en lote (2026-10-11)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-11 |
| Ambiente | Producción (validado en vivo, cuenta admin real) |
| Estado | **Desplegado en producción** |

## Pedido de Jose

"Si quiero hacer una selección múltiple o completa para modificar x campo en todas las filas, no
es posible, cosa que me dificulta la gestión completa... mejorar el UX y UI, poner animaciones,
mejorar la usabilidad/responsividad, y ajustar más esas tablas, todas."

Alcance confirmado explícitamente: activar/inactivar en lote (no edición arbitraria de cualquier
campo — cada entidad tiene reglas de validación distintas que un endpoint de bulk-edit genérico
tendría que respetar una por una; eso queda fuera de esta ronda).

## Cambios (genérico en `crud.engine.js`, aplica a las ~20 tablas admin con estado activar/inactivar)

1. **Columna de selección**: checkbox por fila + "seleccionar todo" en el encabezado. Solo aparece
   si la entidad tiene estado ACTIVO/INACTIVO y el rol puede editar (mismo criterio que ya decide
   si se muestra el botón individual de activar/inactivar).
2. **"Seleccionar todo" respeta filtros y búsqueda**: usa `table.rows({search:"applied"})` de
   DataTables, no solo la página visible — selecciona todas las filas que coinciden con el filtro
   actual, en cualquier página.
3. **Barra de acciones en lote**: aparece al seleccionar ≥1 fila, con contador, botón "Activar"
   (filas inactivas seleccionadas), botón "Inactivar" (filas activas seleccionadas, pide un motivo
   una sola vez para todo el lote — algunos módulos lo exigen, como CU15/19/20/21; si el módulo no
   lo necesita, el backend lo ignora), y "Cancelar selección".
4. **Reutiliza el endpoint `toggle-status` que ya existe en cada módulo** (uno por fila, en bucle)
   — sin endpoint nuevo de backend, sin tocar reglas de negocio por entidad.
5. **Selección persiste entre páginas de DataTables** dentro de la misma tabla (el Set de IDs vive
   en el módulo, se resincroniza con los checkboxes en cada `draw` de DataTables).
6. Corregido de paso: el filtro por columna (`config.filters`) calculaba el índice de columna sin
   contar la nueva columna de checkbox — se habría filtrado la columna equivocada en toda tabla
   con filtros activos (Semilleros, Usuarios, etc.) si no se corregía el offset.
7. **Animaciones** (`prefers-reduced-motion` respetado): filas de la tabla entran con un fundido
   escalonado; la barra de selección en lote aparece con una animación suave.

## No resuelto en esta ronda

- Edición de otros campos en lote (ej. reasignar facultad/programa a varias filas a la vez) —
  decisión explícita de Jose de dejarlo fuera por ahora.
- No se exploró la extensión DataTables Responsive — sigue descartada a propósito (ver CLAUDE.md,
  CU07: compite con el `mobile-card-table` propio del sistema).

## Pruebas

- **PHPUnit: 690 en verde** (sin cambios de backend — reutiliza endpoints ya probados).
- **Producción:** diff 0 en los 4 archivos subidos (`crud.engine.js`, `style.css`, `index.html`,
  `service-worker.js`). Validado en vivo con cuenta admin real en Semilleros: selección de 2 filas
  → barra con "2 seleccionado(s)" y botones Activar/Inactivar; "Cancelar selección" limpia sin
  tocar datos reales (no se ejecutó la acción destructiva a propósito, para no alterar los 23
  semilleros reales de producción). Responsive verificado en viewport móvil (390px): el checkbox
  se integra bien al modo tarjeta existente. Sin errores en consola (se corrigió además un aviso
  de accesibilidad menor: checkboxes de fila sin `id`/`name`).
