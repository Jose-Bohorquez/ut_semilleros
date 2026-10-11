# Editar campo en lote (varios registros a la vez) — 2026-10-11

| Campo | Valor |
|---|---|
| Fecha | 2026-10-11 |
| Ambiente | Producción (validado en vivo, cuenta admin real) |
| Estado | **Desplegado en producción** |

## Pedido de Jose

"Me falto el de seleccionar y ejemplo cambiar todo de una fila... en semilleros, a todos les
quiero poner CAT Kennedy, no puedo hacer eso en masivo, y no solo con esa columna, me gustaría
poder hacerlo con otras y no solo en semilleros, en todas de por sí." — la extensión del alcance
que se había dejado fuera a propósito en la ronda anterior (solo activar/inactivar en lote).

## Cambios (genérico en `crud.engine.js`, aplica a cualquier tabla con campos `select`/`relation`)

1. Nuevo botón **"Editar campo"** en la barra de selección en lote (aparece solo si la entidad
   tiene al menos un campo elegible).
2. **Campos elegibles**: cualquier campo `select` (ej. Nivel) o `relation` (ej. CAT, Coordinador,
   Programa, Grupo) que no sea de solo lectura. Se excluyen a propósito: `status` (ya tiene su
   propio botón Activar/Inactivar), relaciones múltiples (`relation-multi` — ambiguo sin preguntar
   si es reemplazar la lista completa o agregar), texto libre y contraseñas.
3. **Modal**: selector de campo → selector de valor (las opciones de una relación se cargan de
   `/{relation}`, solo activas, igual que el formulario normal de crear/editar).
4. **Al aplicar**: por cada fila seleccionada, arma el payload completo con los valores actuales
   del registro (`buildFullPayload`) y sobrescribe solo el campo elegido, antes de hacer
   `PUT /{entity}/{id}` — necesario porque los endpoints piden todos los campos obligatorios, no
   un parche parcial. Reporta cuántos se actualizaron y cuántos fallaron.
5. El modal reutiliza el `#crudModal` ya existente (mismo overlay, caja, tipografía y botones que
   el formulario de crear/editar) — sin CSS nuevo que mantener por separado.
6. Validado con el ejemplo real de Jose: Semilleros → CAT, con las 21 sedes activas cargadas
   correctamente (CAT - Kennedy incluido) para aplicar a los registros seleccionados.
7. Corregido de paso: dos avisos de accesibilidad (selects sin `name`, label sin `for`) en el
   modal nuevo.

## No resuelto en esta ronda

- Edición en lote de relaciones múltiples (Programas, Áreas) — reemplazar vs. agregar es una
  decisión de producto que no se asumió sin preguntar.
- No se probó aplicar el cambio real sobre datos de producción (se abrió el modal, se confirmó
  que carga las opciones correctas, y se canceló sin aplicar) — falta que Jose lo use una vez
  para el visto bueno final sobre datos reales.

## Pruebas

- **PHPUnit: 690 en verde** (sin cambios de backend — reutiliza el mismo `PUT /{entity}/{id}` que
  ya usa el formulario de edición individual, ya probado por cada módulo).
- **Producción:** diff 0 en el archivo subido. Validado en vivo con cuenta admin real en
  Semilleros: selección de 2 filas → "Editar campo" → selector con "Grupo de investigación" /
  "CAT" / "Coordinador" (los 3 campos relación de Semilleros) → al elegir CAT, las 21 opciones
  activas cargan correctamente, incluida "CAT - Kennedy". Sin errores en consola.
