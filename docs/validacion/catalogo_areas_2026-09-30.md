# Catálogo de áreas de conocimiento — carga inicial (2026-09-30)

## Contexto

El catálogo de "Áreas" solo tenía 1 registro ("Administrativa", código `1`) desde que se creó el
sistema — insuficiente para clasificar semilleros y propuestas de forma útil (los selects de
CU13/CU25 prácticamente no tenían opciones reales). Jose pidió alimentar el catálogo para mejorar
la usabilidad de esos módulos.

## Fuente

No existe un listado propio de "áreas de conocimiento" publicado por la Universidad del
Tolima/IDEAD (el sitio de semilleros los organiza por facultad, no por área). Se usó la
clasificación oficial que usa el sistema nacional de ciencia colombiano para este mismo
propósito: **Anexo 7 — Áreas OCDE**, Ministerio de Ciencia, Tecnología e Innovación (Minciencias).
<https://minciencias.gov.co/sites/default/files/upload/convocatoria/anexo_7._areas_ocde_0.pdf>

Se cargó el nivel 1 (las 6 "Gran Área"), no los subniveles (área/disciplina, que en el documento
llegan a cientos de entradas) — nivel de detalle razonable para un selector de formulario.

## Cargado (vía API, cuenta Admin, en producción)

| ID | Código | Nombre |
|---|---|---|
| 1 | `1` | Administrativa (ya existía) |
| 3 | `CN` | Ciencias Naturales |
| 4 | `IT` | Ingeniería y Tecnología |
| 5 | `MS` | Ciencias Médicas y de la Salud |
| 6 | `AGR` | Ciencias Agrícolas |
| 7 | `SOC` | Ciencias Sociales |
| 8 | `HUM` | Humanidades |

## Pendiente

- CU25 (Registrar propuesta) no coincide con la spec independientemente de este catálogo: falta
  campo Programa, Área debería ser selección múltiple (hoy es única), falta Teléfono, sobra
  "Título", y el límite de 5 propuestas/24h no está implementado. Documentado en la conversación,
  no se ha trabajado el código todavía — pendiente de que Jose confirme si se aborda como ronda
  CU25.
