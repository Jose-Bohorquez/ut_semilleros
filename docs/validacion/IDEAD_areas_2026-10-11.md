# Facultades y áreas en la PWA — corrección de agrupación (2026-10-11)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-11 |
| Ambiente | Docker local + producción (validación en vivo con cuenta estudiante real) |
| Estado | **Desplegado en producción** |

## Reporte de Jose

"Los 19 semilleros pertenecen a diferentes facultades, no solo a las de IDEAD" — la pantalla de
Facultades de la PWA (CU17) agrupaba 16 semilleros bajo una sola tarjeta "Instituto de Educación
a Distancia (IDEAD)", lo cual no refleja cómo funciona esa unidad académica.

## Hallazgo real (no era bug de código, era dato/diseño incompleto)

1. **El IDEAD no se divide en facultades** — es una unidad académica paralela a las 10 facultades
   presenciales, que administra directamente sus 12 programas agrupados por **área de estudio**
   (Ciencias Empresariales y Económicas / Ingeniería y Tecnologías / Educación), según
   documentación oficial que compartió Jose. Agruparlo como una sola "facultad" en la PWA era
   conceptualmente incorrecto.
2. **Los 16 semilleros cargados por `seedbeds:import` tienen los 12 programas del IDEAD pegados
   a propósito** (`ImportSeedbeds.php`, decisión documentada desde la carga: "el listado no dice
   cuál programa aplica a cada uno... se pueden ajustar luego desde Editar"). Por eso cada uno
   "pertenecía" a las 3 áreas a la vez.
3. **Hallazgo real adicional, no reportado por Jose**: los 3 semilleros originales de prueba
   (`#1`, `#7`, `#14`) apuntaban a una facultad "Ingenieria" y un programa "Ingeniería de
   Sistemas" **inventados** (sin código SNIES, no están en la oferta oficial) — duplicado del
   programa real "Ingeniería de Sistemas" del IDEAD (código `0854`).

## Cambios

1. **Migración** `programs.area_tematica` (nullable) + backfill de los 12 programas del IDEAD con
   los 3 grupos que indicó Jose. **Sin clasificar por Jose**: "Tecnología en Protección y
   Recuperación de Ecosistemas Forestales" (0838) — se puso en "Ingeniería y Tecnologías" por
   afinidad temática (juicio propio, pendiente de confirmación).
2. **`SeedIdeadCatalog`** actualizado para asignar el área al crear programas nuevos (idempotente
   hacia adelante).
3. **Comando nuevo `catalog:fix-legacy-ingenieria`**: reasigna los 3 semilleros legacy al programa
   real (IDEAD, `0854`) y deja la facultad/programa inventados **INACTIVOS** (RN01, sin borrado
   físico). Ejecutado en producción.
4. **Frontend** (`pwa-seedbeds.module.js`): nueva función `sectionsOf()` — las facultades
   presenciales siguen agrupándose por facultad real; el IDEAD se agrupa por `area_tematica`; los
   semilleros con el placeholder de los 12 programas (más de un área distinta) caen en una
   sección aparte **"IDEAD · Por clasificar"** (borde punteado, nota explicativa) en vez de
   repetirse en las 3 áreas a la vez. `facultiesOf()` (nombres reales de facultad) se mantiene
   intacta para el detalle de cada semillero (CU18 paso 2).
5. Corrección de paso: "2 secciónes" → "2 secciones" (tilde de más en el plural).

## No resuelto en esta ronda

- Los 16 semilleros "Por clasificar" siguen sin su programa real individual — falta que Jose
  entregue el mapeo semillero → programa real (el listado de carga no lo traía).
- Clasificación del área de "Tecnología en Protección y Recuperación de Ecosistemas Forestales"
  sin confirmar por Jose.

## Pruebas

- **PHPUnit: 690 en verde** (sin tests nuevos — cambio de catálogo + frontend, sin lógica de
  backend nueva que validar más allá de lo que ya cubre `RF05AreaTest`/`ProposalCrudTest` sobre
  `Program`/`Faculty`).
- **Producción:** backup de `programs`/`faculties`/`seedbed_program` antes de migrar y antes de
  correr el comando de fix. Migración aditiva (`--pretend` revisado). `catalog:fix-legacy-ingenieria
  --dry-run` confirmó los 3 semilleros exactos antes de aplicar. Diff 0 en los 6 archivos subidos.
  Validado en vivo con cuenta estudiante real: pantalla ahora muestra "Ingeniería y Tecnologías"
  (3 semilleros, los legacy ya corregidos) y "IDEAD · Por clasificar" (16) — ya no hay una
  facultad "IDEAD" plana ni la facultad/programa "Ingenieria" inventados. Sin errores en consola.
