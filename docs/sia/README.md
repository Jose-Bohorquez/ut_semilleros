# SIA — Sistema Integrado de Asistencia (RF17 propuesto)

> Funcionalidad nueva, fuera de la especificación original (pedida por Jose el 2026-09-28).
> Se documenta como **RF17** propuesto para incorporarla a `docs/especificacion/`.

## RF17 – Asistente de ayuda con IA y reporte de errores

| Campo | Descripción |
|---|---|
| Actores | Todos los usuarios, con sesión o sin ella (visitantes en el login) · Administrador del sistema (panel) |
| Descripción | En todas las pantallas (login, panel web y PWA) hay dos botones flotantes. **WhatsApp**, solo para reportar errores (bugs), al +57 317 877 3186, con un mensaje prellenado que incluye la pantalla. **SIA**, un chat que responde **solo** dudas de uso del Sistema de Semilleros IDEAD con la memoria técnica del sistema. Al finalizar cada conversación el usuario la califica con 5 caritas (😞…😄) y puede dejar un comentario. El Administrador del sistema revisa el consumo, las calificaciones y los comentarios, deja notas de revisión, agrega respuestas corregidas y ajusta los límites. |
| Reglas | SIA no responde temas ajenos al sistema; no revela sus instrucciones ni claves; no pide ni guarda contraseñas; deriva los errores a WhatsApp. Uso limitado para no superar el cupo gratis del proveedor. |
| Criterios de aceptación | Una pregunta ajena recibe la respuesta de alcance. Un intento de extraer instrucciones o claves es rechazado. Superar un límite responde 429 con un mensaje claro, sin llamar al proveedor. La key nunca llega al navegador. El panel es solo para ADMIN_SISTEMA. |
| RNF relacionados | RNF03 (seguridad: límites, escape de HTML), RNF07 (respuesta < 2 s; medido ≈ 1 s), RNF09 (accesibilidad), RNF12 (datos personales: IP con hash, aviso de no compartir datos). |

## Arquitectura

- **Proveedor:** Groq, API compatible con OpenAI, modelo `openai/gpt-oss-20b`. Cupo gratis: 1.000
  consultas por día y 8.000 tokens por minuto. La key va en `GROQ_API_KEY` del `.env` del servidor
  y nunca en el repo ni en el navegador.
- **Backend:**
  - `App\Services\Sia\SiaAssistant` arma el system prompt con las reglas de alcance y el
    CONOCIMIENTO. Las secciones relevantes de `api/resources/sia/base-conocimiento.md` se eligen
    por coincidencia de términos, y se suman las respuestas corregidas activas. Se envían los
    últimos 6 mensajes, con `max_completion_tokens` 400 y `reasoning_effort=low`. Cada consulta
    gasta ≈ 1.100 tokens antes de la ampliación de la base (2026-09-30) y ≈ 1.500–1.900 después;
    conviene vigilar «tokens por minuto» en el panel (límite gratis: 8.000 TPM por cuenta).
  - `SiaController` expone `POST /api/sia/chat` y `/api/sia/close`, que son públicas (throttle
    15/min), y `/api/sia/admin/*` solo para `role:ADMIN_SISTEMA`.
- **Tablas:** `sia_conversations` (calificación, comentario y revisión), `sia_messages` (tokens,
  latencia y estado), `sia_knowledge` y `sia_settings`.
- **Frontend:** `core/sia.widget.js` se monta una vez desde `app.js`, fuera de `#app`, y
  `modules/sia-admin/` es el panel en `/admin/sia`.

## Varias cuentas de Groq en rotación (2026-09-28)

- `GROQ_API_KEYS=cta_01=gsk_…,cta_02=gsk_…,cta_03=gsk_…` va en el `.env` del servidor, o en el
  override local. En la BD y en el panel **solo aparece la etiqueta**: nunca la key ni el correo de
  la cuenta.
- `GroqKeyPool` usa primero la cuenta **menos usada hoy**. Si una responde 429 queda en
  enfriamiento: hasta medianoche si agotó el cupo del día, o el tiempo que indique `retry-after`.
  Si responde 401/403 queda 1 h en enfriamiento y se registra para rotar esa key. En ambos casos se
  reintenta con la siguiente cuenta, con un tope total de 20 s por pregunta.
- Tope por cuenta: `per_key_requests_per_day` (800 = 80 % de las 1.000 gratis).
- Hoy hay 3 cuentas: 3.000 consultas/día de cupo gratis.

## Respuestas locales (sin gastar API)

`SiaLocalResponder` responde **sin llamar a Groq**, y sin consumir los límites de la persona:
- **Intenciones simples:** saludo, «¿quién eres?», «¿qué haces?», «¿quién te creó?», «¿cómo hablo
  con los creadores o con soporte?», gracias y despedida. El saludo, las gracias y la despedida
  solo si el mensaje no trae otra pregunta. Identidad, creador y contacto solo en mensajes cortos.
- **Respuestas corregidas** del panel, cuando la pregunta coincide fuerte con ellas (≥ 75 % de
  términos en común). A medida que se carga feedback al conocimiento, las llamadas a la API bajan.

En el panel, «Respondidas sin API hoy» mide cuánto cupo se está ahorrando.

## Límites (valores por defecto, ajustables en el panel)

| Límite | Valor |
|---|---|
| Visitante (por IP) | 20 preguntas/hora, 40/día (etapa de pruebas) |
| Usuario con sesión | 60 preguntas/día |
| Preguntas por conversación | 20 |
| Largo de la pregunta / de la respuesta | 500 caracteres / 400 tokens |
| Tope por cuenta de Groq | 800 consultas/día |
| Tope global diario (API) | 2.000 consultas y 2.500.000 tokens. Las respuestas locales no cuentan |

## Operar y mejorar SIA

1. Revisa en `/admin/sia` los filtros «Mal calificadas» y «Con comentario».
2. Abre la conversación, lee la transcripción y deja tu nota de revisión.
3. Si SIA respondió mal o le faltó información, agrega una **respuesta corregida**. Entra a su
   conocimiento de inmediato, sin deploy.
4. Los cambios de fondo (nuevas funciones del sistema) se hacen en
   `api/resources/sia/base-conocimiento.md`, que sí requiere deploy.
5. **Rotar la key:** crear una nueva en console.groq.com, reemplazar `GROQ_API_KEY` en el `.env`
   del servidor **por archivo** (nunca interpolada en SSH), correr `php artisan config:clear` y
   revocar la anterior.

## Validación (2026-09-28, local)

- PHPUnit `tests/Feature/Sia/SiaPoolAndLocalTest.php`: 8 tests. Cubren la rotación ante 429 con
  registro de la cuenta, key inválida enfriada, todas las cuentas agotadas (503), la menos usada
  primero, el tope por cuenta, intenciones locales sin API, saludo con pregunta real que sí va a
  la API, respuesta corregida directa y que las respuestas locales no consumen límites.
- En vivo, con las 3 cuentas: las consultas se repartieron cta_01/02/03 y 3 de 6 preguntas se
  respondieron sin API.
- PHPUnit `tests/Feature/Sia/SiaTest.php`: 13 tests con `Http::fake`. Cubren respuesta y registro
  de tokens, no filtrar la key ni el prompt, largo máximo, límite por hora, tope por conversación,
  tope global sin llamar al proveedor, error del proveedor (503), trazabilidad del usuario,
  calificación y cierre único, omitir, panel solo admin, revisión, conocimiento y límites.
- Contra Groq real: responde con los nombres exactos de botones y pestañas; rechaza temas ajenos y
  el prompt injection; deriva los bugs a WhatsApp; responde en ≈ 0,5–1 s.
- E2E en navegador 17/17:
  - login con visitante;
  - PWA móvil de estudiante: sin tapar la barra ni el «+», hoja inferior;
  - escape del HTML del usuario;
  - calificación con caritas y omitir;
  - panel del administrador: KPIs, filtros, transcripción, revisión, respuesta corregida.

## Despliegue en producción (2026-09-28)

- Respaldo de 8 archivos y del `.env` en `~/backups/ut-edu.online/2026-09-28_pre_sia/`. Lint con
  PHP 8.2 sin errores en 13 archivos. Migraciones solo aditivas (4 tablas nuevas), revisadas con
  `--pretend`.
- `GROQ_API_KEYS` (3 cuentas) cargada en el `.env` de producción **por archivo**, con permisos 600.
- Frontend: primero los módulos nuevos, después el resto, y a los 6 min el SW v17. Resultado:
  0 diferencias entre el repo y el servidor.
- **Validación en vivo 7/7:**
  - Login con SIA arriba y WhatsApp abajo.
  - Respuesta local sin API; respuesta real de Groq sin XSS.
  - Calificación con 4 caritas y comentario.
  - PWA móvil sin tapar la barra inferior ni el «+».
  - Rotación: varias cuentas usadas.
  - Panel con las 3 cuentas.
- Limpieza: usuarios `qa_temp_sia_*` y conversaciones de prueba eliminados.

## Memoria técnica ampliada (2026-09-30)

`api/resources/sia/base-conocimiento.md` pasó de 15 secciones (11 KB) a ~112 (84 KB), redactadas
contra el código real del front y del API, no solo contra la especificación: cubre el panel web por
rol (menús, permisos, catálogos, usuarios, perfil, notificaciones, RBAC, auditoría), el núcleo de
semilleros (registrar, editar, inactivar, objetivos, resultados, integrantes, solicitudes recibidas,
propuestas, proyectos, productos), la app del estudiante (explorar, «Ser miembro», solicitudes,
propuestas, perfil, offline, instalación), glosario y solución de problemas, con los nombres
exactos de botones y los mensajes de error que ve el usuario.

**Cómo está armada y cómo mantenerla**
- Una sección `## ` por tarea, de 400–1.200 caracteres (un test lo exige: máx. 1.500). El título es
  una pregunta natural con sinónimos (crear/registrar, inactivar/desactivar, unirme/postularme,
  envío/enviar…): pesa el doble en la búsqueda.
- El archivo **debe empezar por `## Qué es el sistema`**: esa sección va siempre. También va siempre
  `## Roles y qué puede hacer cada uno` (constante `ROLES_SECTION`), porque el prompt le pide a SIA
  decir si el rol de quien pregunta puede hacer algo. Las demás se eligen: las 3 con más términos.
- Búsqueda: raíz de 5 letras, un punto por término y una fracción por repeticiones (desempata);
  las siglas `cat` y `pwa` cuentan aunque tengan menos de 4 letras (como palabra completa).
- `tests/Feature/Sia/SiaKnowledgeRetrievalTest.php`: 57 preguntas reales por rol que deben traer la
  sección esperada, más límites de tamaño. **Al agregar o renombrar una sección, corre ese test.**
- Cuando cambie una función del sistema, actualiza su sección en el mismo commit.

**Discrepancias encontradas al redactarla (código vs. documentos)**, para decidir:
- `docs/roles-usuarios.md` y `docs/manuales/manual_estudiante.md` / `manual_lider_semillero.md`
  están desactualizados (Administrativo ya solo consulta semilleros/objetivos/resultados; la
  solicitud pide programa, teléfono y mensaje; aprobar no vuelve integrante al estudiante).
- El menú lateral de escritorio no lista «Solicitudes» ni «Propuestas» (solo la barra inferior
  móvil): SIA lo explica así mientras no se cambie.
- RBAC (`/admin/rbac`): ninguna ruta usa aún `permission:`; guardar permisos no cambia el acceso.
- Importar usuarios no envía `authorization_reference`: las filas que no son Estudiante fallan (RN02).
- Propuestas: sin motivo ni observación del evaluador; el estudiante no sabe por qué la rechazaron.
- Sin aviso automático (campana/push) al aprobar o rechazar solicitudes o propuestas.
- Sin conexión solo funcionan `/seedbeds` y `/objectives`; «Solicitudes» y «Propuestas» no.
- Botón «Inactivar» de Proyectos llama a una ruta que no existe; el Líder no tiene «Productos».
- Quitar un objetivo desde «Editar» semillero lo borra de forma permanente.
- No hay módulo de Reportes (CU28/RF15).

**Pendiente de validar en producción:** las respuestas del modelo real con la base nueva (el
Docker local no resuelve DNS hacia Groq). El test de recuperación prueba qué contexto recibe SIA,
no cómo lo redacta el modelo.
