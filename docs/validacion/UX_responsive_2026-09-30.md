# Ronda de UX/responsive — 2026-09-30

Transversal (no un CU específico). Jose reportó: móvil descuadrado por todo lado, botones
SIA/WhatsApp tapando contenido, contenido desbordado, pidió que los botones fueran opcionales
de ocultar. Se validó en viewport móvil real (375×667, Chrome DevTools) antes de tocar código.

## Bug real crítico encontrado y corregido

Los botones flotantes de SIA y WhatsApp (`z-index: 1100`) se renderizaban **por encima de
cualquier modal** (`#crudModal`, z-index 1000; SweetAlert2; bottom-sheets), porque el
`MutationObserver` que los reposiciona solo observaba `#app`, y los modales se insertan en
`document.body` (fuera de `#app`) — nunca los detectaba.

Confirmado visualmente: al abrir "Crear Semillero" en móvil, el robot de SIA tapaba la mitad del
campo "Nombre" y el botón de WhatsApp tapaba "Descripción". Esto afecta **todos los módulos**
que usan el modal genérico de `crud.engine.js` (~20 módulos) y cualquier `Swal.fire`.

**Corregido**: el observer ahora vigila `document.body` completo, y detecta la presencia de
`#crudModal`, `.swal2-container` o cualquier `[role="dialog"]` abierto para ocultar los FABs
mientras dura la interacción. Validado en vivo: al abrir el modal, los botones desaparecen por
completo; al cerrarlo, reaparecen.

## Nueva función: ocultar/mostrar SIA y WhatsApp (pedido explícito)

Botón adicional (chevron) en el mismo grupo de FABs para colapsarlos manualmente. Se recuerda
entre sesiones vía `localStorage` (`sia_collapsed`) — a diferencia del resto del estado de SIA
que usa `sessionStorage`, porque esto es una preferencia de UI, no datos de la conversación.
Validado en vivo: al tocar "Ocultar botones" desaparecen y aparece un botón pequeño "Mostrar
botones" en su lugar; funciona correctamente.

## Revisado y sin problemas encontrados

- Pantalla de facultades (PWA, CU17): sin desbordes.
- Formulario de crear/editar semillero completo, incluidas las pestañas y los selects múltiples
  de Programas/Áreas (`size="5"`): se ven correctamente, sin overflow horizontal ni recortes.
- Tabla `mobile-card-table` (patrón label/valor apilado) del panel admin: la estructura en sí no
  se desborda: el problema real era exclusivamente el z-index de los FABs por encima, ya
  corregido arriba.

## Pendiente (alcance — no se revisó todo el sistema en esta ronda)

Esta fue una auditoría dirigida a los síntomas más señalados por Jose (FABs tapando contenido),
no un rediseño completo de responsive. Falta revisar sistemáticamente:

- Pantallas de Dashboard, Perfil, Solicitudes, Propuestas, Notificaciones (no se abrieron esta
  ronda).
- Formularios largos de otros módulos (Usuarios, Grupos, Coordinadores, CAT) en móvil.
- Tema oscuro (`data-theme="dark"`) combinado con móvil — no se probó esta ronda.
- Tablas con muchas columnas en tablet (768px–1024px), rango intermedio entre el layout de
  tarjetas móvil y el de tabla de escritorio.
- Cualquier "contenido desbordado" adicional que Jose haya visto en pantallas puntuales no
  cubiertas aquí — se necesita que indique cuáles para revisarlas dirigidamente, o continuar la
  auditoría pantalla por pantalla en una próxima ronda.

## Archivos modificados

- `core/sia.widget.js`: observer ampliado a `document.body`, detección de modal abierto, botón
  de colapsar/expandir persistente.
- `style.css`: reglas `.sia--modal-open`, `.sia--collapsed`, estilos del botón de colapsar.

## Pruebas

- Sin cambios de backend — suite de 314 sigue en verde.
- Validado en vivo en producción con Chrome DevTools en viewport móvil (375×667): modal oculta
  los FABs correctamente; botón de colapsar/expandir funciona y persiste.
