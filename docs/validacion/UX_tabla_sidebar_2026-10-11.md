# Tabla de Semilleros (columnas relation-multi) + orden del sidebar (2026-10-11)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-11 |
| Ambiente | Producción (validado en vivo, cuenta admin real) |
| Estado | **Desplegado en producción** |

## Pedido 1: tabla de Semilleros "se ve fatal" en web

Hallazgo real: `crud.engine.js` (motor genérico de ~20 módulos admin) volcaba **todos** los nombres
de una relación `relation-multi` (Programas, Áreas) como texto plano separado por comas, sin
límite. Con un semillero de 12 programas (placeholder de la carga IDEAD) la celda crecía a
cientos de píxeles y la fila completa con ella — la tabla de Semilleros medía 10872px de alto para
~20 filas.

**Corregido** (genérico, aplica a toda tabla con columnas `relation-multi`, no solo Semilleros):
máximo 2 chips + badge "+N" por celda; columnas de texto libre (objetivo, descripción) con tope de
ancho (320px) y `overflow-wrap`. La misma tabla pasó de 10872px a 2470px de alto.

## Pedido 2: orden del sidebar según flujo de gestión real

Jose: "las categorías deben ir de acuerdo a cómo debería hacerse la gestión... para crear un
semillero debemos tener presente que ya previamente debimos gestionar programas, áreas, grupos,
CAT, coordinadores". El sidebar mostraba "Semilleros" primero y los catálogos de los que depende
(Facultades/Programas/Áreas/CAT) en una sección aparte, más abajo.

**Reordenado** (`layout/layout.view.js`), mismos enlaces y mismo control de acceso por rol — solo
cambia el orden/agrupación visual:

1. **Catálogos base** — Facultades, Programas, Áreas, CAT (solo ADMIN_SISTEMA, como ya era),
   Grupos, Coordinadores (ADMIN_SISTEMA + ADMINISTRATIVO, como ya era).
2. **Semilleros**.
3. **Gestión del semillero** — Objetivos, Resultados, Proyectos, Productos.
4. **Solicitudes y propuestas**.
5. **Reportes**.
6. **Sistema** (solo ADMIN_SISTEMA) — Usuarios, Auditoría, SIA, RBAC.

Para LIDER_SEMILLERO: sección **"Antes de crear un semillero"** (Grupos, Coordinadores) +
Semilleros + Gestión del semillero (sin Productos, que no le corresponde) + Solicitudes y
propuestas + Reportes. ESTUDIANTE no se tocó (solo 3 enlaces, sin dependencias que ordenar).

## No se tocó

Ningún endpoint del backend. Ningún permiso de rol — los mismos enlaces que ya veía cada rol
siguen visibles, solo cambia el orden/agrupación.

## Pruebas

- **PHPUnit: 690 en verde** (sin cambios de backend).
- **Producción:** diff 0 en los 4 archivos (`crud.engine.js`, `style.css`, `index.html`,
  `service-worker.js`, `layout/layout.view.js`). Validado en vivo con cuenta admin real: tabla de
  Semilleros con chips "+N" y filas de altura normal; sidebar con el nuevo orden y agrupación.
