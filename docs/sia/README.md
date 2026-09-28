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
    gasta ≈ 1.100 tokens.
  - `SiaController` expone `POST /api/sia/chat` y `/api/sia/close`, que son públicas (throttle
    15/min), y `/api/sia/admin/*` solo para `role:ADMIN_SISTEMA`.
- **Tablas:** `sia_conversations` (calificación, comentario y revisión), `sia_messages` (tokens,
  latencia y estado), `sia_knowledge` y `sia_settings`.
- **Frontend:** `core/sia.widget.js` se monta una vez desde `app.js`, fuera de `#app`, y
  `modules/sia-admin/` es el panel en `/admin/sia`.

## Límites (valores por defecto, ajustables en el panel)

| Límite | Valor |
|---|---|
| Visitante (por IP) | 10 preguntas/hora, 20/día |
| Usuario con sesión | 30 preguntas/día |
| Preguntas por conversación | 12 |
| Largo de la pregunta / de la respuesta | 500 caracteres / 400 tokens |
| Tope global diario | 600 consultas y 400.000 tokens (el que llegue primero; con ≈ 1.100 tokens por consulta, ≈ 360 consultas) |

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
