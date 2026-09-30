# Onboarding — UT Semilleros (trabajo en paralelo, 2 cuentas Claude)

Este documento es para la **segunda cuenta de Claude** (VM Windows, conectada por SSH a este
mismo computador Linux) que se suma a trabajar en este proyecto junto con la cuenta principal.
Léelo completo antes de tocar nada.

## Qué es este proyecto

Sistema de Semilleros de Investigación de la Universidad del Tolima (IDEAD). Backend Laravel 12
(`api/`) + frontend SPA vanilla JS (raíz + `modules/`, `core/`, `layout/`, `services/`).
Producción: `https://ut-edu.online/`. Repo: `Jose-Bohorquez/ut_semilleros` en GitHub (público).

**Lee primero `CLAUDE.md` en la raíz de este repo** — es la fuente de verdad de: cómo está
desplegado de verdad, prohibiciones (cada una viene de un incidente real), convenciones de
pruebas, diseño, y el avance CU por CU. No dupliques esa información aquí; este archivo es solo
el punto de entrada.

## Dónde estás parado

Esta sesión corre **en el mismo computador Linux** que la cuenta principal, conectada por SSH
(clave ya autorizada en `~/.ssh/authorized_keys`). Es decir: **mismo filesystem, mismo repo
clonado, mismo `.env`, mismo acceso SSH al servidor de producción (alias `htg`)** — no hay nada
que clonar ni configurar de cero. Trabaja directamente en
`/home/jose/proyectos/propios/ut/_public_html`.

## Metodología de trabajo (síguela tal cual, no la reinventes)

El dueño del proyecto (Jose) valida el sistema **caso de uso por caso de uso**, en orden
(CU01...CU30), contra `docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md`.
Su patrón de instrucción es: "validemos que todo lo anterior esté documentado, probado y
desplegado a prod, y si es así, continuemos con el siguiente CU". Cada ronda:

1. Lee el CU exacto en la especificación (`grep -n "^### CUxx"` en el documento). **Ojo**: el
   número de CU casi nunca coincide con el número de RF/RNF — hay una tabla de equivalencias en
   la spec, revísala antes de asumir que CUxx = RFxx.
2. Audita el código real (controller, modelo, migraciones, rutas, frontend) contra lo que pide
   la spec: reglas de negocio (RN01-RN15), validaciones, permisos por rol.
3. Si hay una decisión de alcance ambigua (ej. "¿reconstruyo el modelo completo o solo parcho
   permisos?"), **pregúntale a Jose explícitamente antes de decidir** — no asumas.
4. Implementa, escribe/actualiza tests PHPUnit, corre la suite completa localmente.
5. Despliega a producción con el flujo de `CLAUDE.md` (backup si hay migración con datos reales,
   `--pretend` antes de `migrate --force`, `php -l` de lint, `route:clear`/`config:clear`, diff
   0 entre local y servidor, smoke test).
6. Valida en vivo en producción con cuentas de prueba reales (ver `credenciales_prueba.txt` en
   la raíz — léelo con un script que NUNCA imprima la contraseña en el output, el formato es una
   línea por campo: rol, luego email, luego password, cada 3 líneas un registro).
7. Escribe el acta en `docs/validacion/CUxx.md` (mismo formato que las ya existentes — ábrelas
   para ver el patrón exacto).
8. Commit con el trailer `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>` (o el que te
   corresponda a ti) y `git push`.

## Estado actual (revisa `docs/validacion/` para la lista completa y a la fecha real)

A la fecha de este onboarding: **CU01 a CU23 cerrados** (documentados, probados, desplegados,
validados en vivo). El siguiente en la cola es **CU24 — Gestionar solicitudes recibidas**. No
asumas que sigue siendo así cuando leas esto: corre `ls docs/validacion/` y mira el último
`CUxx.md` y el `git log` para confirmar dónde va realmente la validación antes de continuar.

## Coordinación entre las 2 cuentas (para no pisarse)

- **Antes de empezar cualquier ronda**: `git pull` — la otra cuenta puede haber avanzado.
- **No trabajen el mismo CU al mismo tiempo.** Si van a trabajar en paralelo, avísense
  explícitamente (a través de Jose) qué CU/archivo va a tocar cada quien.
- **No hagan deploy a producción los dos a la vez.** Es el mismo servidor (`htg`). Si ambos
  despliegan casi al mismo tiempo, un `scp` puede pisar el cambio del otro sin que ninguno lo
  note. Confirmen turno antes de subir.
- **Commits pequeños y frecuentes** (uno por CU, como ya se viene haciendo) para que el otro lado
  pueda hacer `git pull` sin conflictos grandes.
- Si encuentran un hallazgo real fuera del CU que están validando (bug, desviación de spec), no
  lo arreglen de paso sin decirlo — anótenlo y pregúntenle a Jose si lo priorizan ahora o después
  (así se ha venido haciendo toda la sesión).

## Prohibiciones y gotchas (de `CLAUDE.md`, repetidos aquí porque importan)

- Nunca `git add .` ni `git add -A` en esta carpeta (hay subcarpetas de otros proyectos y datos
  de BD local sin ignorar correctamente). Agrega archivos por ruta explícita.
- Nunca interpolar hashes/secretos directo en un `ssh "..."` — el shell remoto los corrompe.
  Escribe un archivo local y súbelo con `scp`.
- El CDN de Hostinger cachea assets estáticos hasta 5 minutos. Si cambias un archivo JS/CSS que
  se importa con `?v=N`, sube el número de versión en el importador (cascada: quien lo importa,
  y quien importa a ese, hasta `index.html`/`app.js`). Si cambias `service-worker.js`, sube
  `CACHE_NAME`.
- Nunca imprimas contraseñas de `credenciales_prueba.txt` en la salida de un comando.
- Todas las pruebas backend corren dentro de Docker: `docker exec -e DB_CONNECTION=sqlite -e
  DB_DATABASE=:memory: ut_semilleros_api php artisan test` (los `-e` no son opcionales, ver
  `CLAUDE.md`).
