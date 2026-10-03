# Pruebas visuales y funcionales de la interfaz (2026-10-03)

Primera pasada **con navegador real** sobre todo el frontend (hasta hoy solo se había probado la API y el código). Chrome 154 controlado con Playwright,
contra el entorno local de Docker (nunca contra producción), con una cuenta QA por rol. Herramientas y forma de correrlas: `scripts/ui-tests/README.md`.

## Cobertura

| Prueba | Alcance | Resultado |
|---|---|---|
| Rastreo de pantallas | 4 roles × ~40 pantallas × escritorio (1440), tablet (820) y móvil (390) con dos pasadas; errores JS/consola, respuestas HTTP ≥ 400, desbordes horizontales, textos `undefined`/`NaN`, imágenes rotas | 1 hallazgo (deadlock, abajo); 0 en tablet y móvil |
| Todos los botones y modales | ~300 controles en escritorio y ~190 en móvil: abre cada modal, mide que quepa, envía vacío para validar, cierra | 3 hallazgos reales (abajo) |
| Flujos de extremo a extremo | Estudiante explora → solicita ingreso → líder rechaza sin motivo (valida) y aprueba → estudiante ve la respuesta; propuesta → administrativo archiva sin observación (valida) y con ella → estudiante la ve; notificaciones, perfil, auditoría con filtros y CSV, reportes con filtros, rango inválido y los 4 CSV, SIA, tema, cierre de sesión | Correctos en escritorio y móvil |
| CRUD de 12 catálogos | Crear, buscar, editar, inactivar y activar en facultades, programas, CAT, áreas, grupos, coordinadores, objetivos, resultados, proyectos, productos, semilleros y usuarios | Correcto salvo el botón de Proyectos (abajo) |
| PWA | Manifest, 4 iconos 192/512 any+maskable, apple-touch-icon, Service Worker, uso sin conexión y recuperación, navegación móvil | 1 hallazgo grave (abajo) |

## Hallazgos corregidos

| # | Gravedad | Hallazgo | Corrección |
|---|---|---|---|
| 1 | **Alta** | **PWA sin conexión: pantalla en blanco al reabrir.** El Service Worker buscaba `/app.js?v=5` en caché pero lo guardó como `/app.js`; además las librerías CDN solo se guardaban si se pedían con el SW ya activo (la primera visita no) | `service-worker.js`: fallback con `ignoreSearch`, precarga de las 15 librerías CDN al instalar y mensaje `CACHE_URLS` desde `app.js` para guardar lo ya cargado. `CACHE_NAME` → `semilleros-v27` |
| 2 | **Alta** | **500 aleatorio**: deadlock de MySQL (1213) en la tabla `cache` donde el limitador de peticiones guarda su contador (el Dashboard lanza ~12 peticiones en paralelo del mismo usuario). Aplica a producción (`CACHE_STORE=database`) | `App\Support\ResilientRateLimiter`: reintenta una vez y, si persiste, deja pasar la petición en vez de fallar; registrado con `extend()` (la `singleton` la pisaba el proveedor diferido del framework). 2 tests unitarios + 2 de cableado |
| 3 | **Alta (móvil)** | En el modal «Crear/Editar» la fila **Cancelar · Guardar borrador · Guardar** no cabía y **«Cancelar» quedaba cortado fuera de la pantalla** (no se podía tocar) | `.modal-actions` con `flex-wrap` y, en móvil, botones a todo el ancho |
| 4 | Media | **Proyectos** ofrecía «Inactivar» pero su estado es ACTIVO/FINALIZADO/SUSPENDIDO y no existe esa ruta: **404** | `crud.engine.js`: el botón solo aparece si hay opciones ACTIVO **e** INACTIVO |
| 5 | Media | Crear usuario **preseleccionaba el rol `ADMIN_SISTEMA`** (primero de la lista) y no validaba el formato del correo (campo de texto) | Selector con «Seleccione un rol» obligatorio (`placeholder` en el motor) y validación de correo por nombre de campo |
| 6 | Media | Con varios errores de validación el usuario veía **«(and 1 more error)»** en inglés | `api.service.js` lista todos los errores 422; el 429 («Too Many Attempts») se traduce |
| 7 | Media | Botones y filtros **sin estilo** en Solicitudes, Propuestas, Auditoría y Reportes (pantallas de CU24/27/28/30) | Clases `btn` y barra de filtros `.filter-bar` |
| 8 | Media | Los botones flotantes de SIA/WhatsApp **tapaban botones de acción** del borde derecho («Guardar límites», «Ver», «Exportar CSV») y, en móvil, los campos de las hojas inferiores (Nueva notificación) | Compactos (solo icono) en escritorio; el widget los oculta también con las hojas inferiores abiertas (`notifSheet`, `requestDetailSheet`) y observa cambios de `style` |
| 9 | Baja | Textos de catálogos: «Crear Semilleros», «Crear Investigación» (Productos), modal «Crear Gestión de Semilleros», «No hay gestión de proyectos» | Singular/plural por entidad en `crud.engine.js` («Crear semillero», «No hay registros de proyectos») |
| 10 | Baja | Búsqueda de semilleros sin resultados decía «Esta facultad no tiene semilleros activos» | «No encontramos semilleros que coincidan con tu búsqueda» |
| 11 | Baja | Gráficas del Dashboard sin protección ante doble dibujo («Canvas is already in use») | `freshCanvas()` destruye la gráfica previa |

## Falsos positivos descartados (no son defectos)
Paginadores deshabilitados con una sola página; validación nativa del navegador en formularios con `required` (aparece en inglés si el navegador está en inglés); contacto del estudiante sí visible al Administrativo; respuesta del líder visible en el **detalle** de la solicitud; «Editar/Inactivar» ausentes en Objetivos/Resultados al buscar por un texto que la tabla no muestra; estudiantes no se crean uno por uno (regla de negocio).

## No verificado
- **Safari/iOS y Firefox**: solo Chrome. La instalación como app («Añadir a pantalla de inicio») y las notificaciones push no se pueden probar en automático.
- **Flujo real con Google**, **respuestas del modelo de SIA** (el entorno local no tiene salida a internet) y envío real de correos.
- Descargas Excel/PDF de DataTables: se verifica que se genera la descarga, no el contenido del archivo.
- Escape no cierra las hojas inferiores de la PWA (solo su «×»): mejora de accesibilidad pendiente.
- Contraste de colores y lectores de pantalla (no se midió).
