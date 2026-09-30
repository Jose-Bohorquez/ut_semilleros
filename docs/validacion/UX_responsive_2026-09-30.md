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

## Ronda 2 (mismo día): ESTUDIANTE en web no podía gestionar nada

Jose reportó: en la vista **web** (no PWA/móvil), el rol ESTUDIANTE ve Solicitudes y Propuestas
en el menú pero "no me deja ver los semilleros, no me permite hacer propuestas, no me deja ver
mis solicitudes". Se investigó en vivo con Chrome DevTools en viewport de escritorio (no fue
"así es el caso de uso" ni RBAC del backend — 2 bugs reales de frontend):

1. **Sidebar de escritorio sin enlace «Semilleros»** — `layout/layout.view.js` tenía el enlace
   en el `BOTTOM_NAV` (usado solo en móvil) pero **no** en el menú lateral de escritorio para
   `ESTUDIANTE`. El estudiante literalmente no tenía cómo llegar a `/seedbeds` desde el panel
   web. Agregado el enlace faltante.
2. **`.pwa-fab` (botón «+» para crear) oculto en escritorio por diseño** — `css/pwa.css` tenía
   `@media (min-width: 769px) { .pwa-fab { display: none; } }`, pensado para un FAB tipo app
   móvil. Pero el router **no distingue ancho de pantalla para ESTUDIANTE**: siempre usa los
   módulos PWA (`pwa-proposals`, `pwa-requests`), incluso en escritorio — es el diseño de
   CU17/CU18/CU25 (el actor Estudiante siempre usa "la PWA", según la spec, sin importar el
   dispositivo). Ocultar el único botón de creación en escritorio dejaba al estudiante sin
   forma de crear una propuesta o una solicitud ahí. Corregido: el FAB se mantiene visible
   siempre; en pantallas ≥1024px se sube por encima del grupo SIA/WhatsApp (que en ese ancho
   también pasa al lado derecho) para no superponerse.
3. **Bottom-sheets de creación (`#proposalSheet`, `#newRequestModal`) tampoco ocultaban
   SIA/WhatsApp** — el fix de la Ronda 1 solo cubría `#crudModal`, `.swal2-container` y
   `#seedbedDetail`. Al abrir "Nueva Propuesta" los botones de SIA/WhatsApp seguían flotando
   encima. Agregados ambos selectores a la misma lista de `sia.widget.js`.

**Nota de manejo de credenciales**: durante el diagnóstico, un comando mal escrito imprimió en
la salida la contraseña de la cuenta de prueba ESTUDIANTE (el archivo
`credenciales_prueba.txt` cambió de formato — antes 3 columnas por línea, ahora una línea por
campo — y el script asumía el formato viejo). Se corrigió el parser para no volver a exponerla;
se recomienda rotar esa contraseña de prueba.

Validado en vivo con cuenta estudiante real en viewport de escritorio: enlace «Semilleros»
visible y funcional; botón «+» visible en `/proposals`; al abrir «Nueva Propuesta» el formulario
completo queda visible sin que SIA/WhatsApp lo tapen.

## Ronda 3 (mismo día): 2 bugs introducidos por los fixes de la Ronda 1/2

Jose detectó: al ocultar SIA/WhatsApp, el botón "Mostrar botones" aparecía del lado contrario
(izquierda) en vez de quedarse a la derecha en escritorio ancho. Al investigar se encontraron
2 bugs reales, ambos regresiones de mis propios cambios anteriores:

1. **`.sia-fab-expand` sin override de escritorio**: el `@media (min-width: 1024px)` que mueve
   `.sia-fabs` al lado derecho (para no chocar con el menú lateral) nunca incluyó al botón
   `.sia-fab-expand` (el que aparece solo cuando está colapsado, es un elemento aparte fuera de
   `.sia-fabs`). Corregido agregándolo al mismo bloque de medios.
2. **Detección de modal abierto rota por un espacio en el texto**: el selector CSS
   `[style*='display: none']` (con espacio) usado para saber si `#proposalSheet`/
   `#newRequestModal`/`#seedbedDetail` estaban visibles NUNCA coincidía con el HTML fuente real,
   que los escribe como `style="display:none;..."` (sin espacio). Resultado: en cualquier página
   con uno de esos sheets en el DOM (aunque nunca se hubiera abierto), `#sia-root` quedaba con
   la clase `sia--modal-open` pegada para siempre — SIA y WhatsApp desaparecían permanentemente
   en `/proposals` y `/requests`, sin relación con si había o no un modal realmente abierto.
   Corregido reemplazando el matching de texto por `getComputedStyle(el).display !== "none"`,
   que no depende de cómo esté escrito el atributo `style` en el HTML.

Validado en vivo: `#sia-root.className` vacío (no atascado) en `/proposals` con estudiante real;
al colapsar en `/dashboard`, el botón "Mostrar botones" aparece correctamente a la derecha.

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
