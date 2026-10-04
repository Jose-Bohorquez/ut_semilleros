# Carga de datos del IDEAD (2026-10-04)

Jose entregó el listado de semilleros (`ListadoDeSemilleros.xlsx`) y la oferta de programas 2026 B (presencial y a distancia/IDEAD).

## Qué trae cada archivo
- **Semilleros (Excel):** 21 semilleros del IDEAD con título, objetivo, coordinador, correo y celular (la hoja 2 repite 15 coordinadores). 4 no traen objetivo, 6 coordinadores no traen celular. El semillero con código `220424` ya existe en producción.
- **Oferta a distancia (IDEAD):** 12 programas de pregrado (códigos 0803, 0838, 0845–0856) y 21 CAT/sedes.
- **Oferta presencial:** 27 programas con código SNIES y código interno; **no se cargaron** (no traen la facultad y no son de IDEAD): falta que Jose confirme los nombres de las facultades.

## Cómo se carga
1. `catalog:idead`: facultad IDEAD (reutiliza «Facultad de Educación a Distancia» si ya existe), 12 programas y 21 CAT. Idempotente.
2. `seedbeds:import`: coordinadores (nombre y correo; documento vacío, es opcional) y semilleros con código `IDEAD-001…` (o el código propio si lo trae). Todos los programas del IDEAD quedan asociados a cada semillero (el listado no dice cuáles aplican), las áreas se asignaron **por tema del título** (provisional) y la referencia de aprobación (RN03) es «Pendiente de acta (carga masiva IDEAD)». Los que no traen objetivo quedan **inactivos** (borrador) con un objetivo provisional.
3. Probado en el entorno local (29 semilleros, 29 coordinadores) y con 11 pruebas automáticas. **Producción: pendiente de la confirmación de Jose.**

## Decisiones pendientes antes de cargar en producción
- ¿Áreas por tema (propuestas) o por defecto? ¿Referencia de aprobación real o provisional?
- ¿Publicar (activos) los 17 completos o dejar todos como borrador hasta revisarlos?
- Los 4 sin objetivo: texto provisional (hoy) o esperar el dato.
- ¿Crear cuentas de Líder para los coordinadores (envía un correo de activación a cada persona)?
- Programas presenciales: nombres de facultad para cargarlos.
