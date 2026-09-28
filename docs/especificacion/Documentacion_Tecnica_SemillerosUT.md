# Documentación Técnica del Proyecto
## SemillerosUT — Sistema de información para la gestión, suscripción y divulgación de los Semilleros de Investigación del IDEAD – Universidad del Tolima

| Campo | Detalle |
|---|---|
| Proyecto base | *Diseño de una aplicación móvil para la gestión de información de los semilleros de investigación en el IDEAD de la Universidad del Tolima* (INITIUM, 2020) |
| Autoras del diseño original | Ema Yarledis Herrera Salazar, Nelly Judit Mahecha Vaca — Director: Pablo Emilio Cuenca Rivera |
| Adaptación tecnológica y desarrollo | José Bohórquez — Aprendiz SENA, programa Análisis y Desarrollo de Software (ADSO), ficha 3311941 |
| Stack adaptado | Backend **PHP 8.2+ / Laravel 12.x** · Frontend **HTML5, CSS3, JavaScript (ES6+), Bootstrap 5** · Cliente móvil **PWA instalable** · Base de datos **MongoDB** (paquete oficial `mongodb/laravel-mongodb`) · API REST con **Laravel Sanctum** · Documentación **OpenAPI/Swagger (L5-Swagger)** |
| Stack original (2020) | CakePHP 3.8, PostgreSQL 11, Ionic 4 + Angular 6 + Apache Cordova, Heroku, Firebase |
| Repositorio | `<URL_REPOSITORIO_GIT>` |
| Versión del documento | 1.0 |

> **Nota de adaptación.** El documento original era la *fase 1 (diseño)* del proyecto. Esta documentación conserva el análisis, los requisitos y el modelo del dominio del PDF, y los traslada a la arquitectura implementada: una aplicación web Laravel (panel administrativo + API REST) y una PWA instalable que reemplaza la app híbrida Ionic/Cordova. El modelo relacional de PostgreSQL se transforma a un modelo documental en MongoDB para cumplir los entregables del Trimestre III. Los campos marcados con `<...>` deben completarse con los datos reales del repositorio y del servidor.

---

## Tabla de contenido

- [Matriz de trazabilidad de la lista de chequeo](#matriz-de-trazabilidad-de-la-lista-de-chequeo)
- [TRIMESTRE I](#trimestre-i)
  - [1.1 Nombre, objetivos, problema, alcance y justificación](#11-nombre-objetivos-problema-alcance-y-justificación)
  - [1.2 Mapa de procesos del negocio (BPMN)](#12-mapa-de-procesos-del-negocio-bpmn)
  - [1.3 Recolección de información y estadística descriptiva](#13-recolección-de-información-y-estadística-descriptiva)
  - [1.4 Requerimientos con historias de usuario (Scrum)](#14-requerimientos-con-historias-de-usuario-scrum)
  - [1.5 Diagrama y especificación extendida de casos de uso](#15-diagrama-y-especificación-extendida-de-casos-de-uso)
  - [1.6 Validación de requerimientos: mockups y wireframes](#16-validación-de-requerimientos-mockups-y-wireframes)
  - [1.7 Control de versiones](#17-control-de-versiones)
- [TRIMESTRE II](#trimestre-ii)
- [TRIMESTRE III](#trimestre-iii)
- [TRIMESTRE IV](#trimestre-iv)
- [TRIMESTRE V](#trimestre-v)
- [Reporte de avance](#reporte-de-avance)

---

## Matriz de trazabilidad de la lista de chequeo

| Trim. | Entregable | Sección de este documento | Valoración |
|---|---|---|---|
| I | Nombre, objetivo general, específicos, problema, pregunta, alcance, justificación | [1.1](#11-nombre-objetivos-problema-alcance-y-justificación) | CUMPLE |
| I | Mapa de procesos BPMN del proceso de negocio | [1.2](#12-mapa-de-procesos-del-negocio-bpmn) | CUMPLE |
| I | Técnicas de recolección y estadística descriptiva | [1.3](#13-recolección-de-información-y-estadística-descriptiva) | CUMPLE |
| I | Requerimientos funcionales y no funcionales con historias de usuario | [1.4](#14-requerimientos-con-historias-de-usuario-scrum) | CUMPLE |
| I | Diagrama de casos de uso y formato extendido | [1.5](#15-diagrama-y-especificación-extendida-de-casos-de-uso) | CUMPLE |
| I | Prototipo con mockups / wireframes | [1.6](#16-validación-de-requerimientos-mockups-y-wireframes) | CUMPLE |
| I | Entregas en control de versiones | [1.7](#17-control-de-versiones) | CUMPLE |
| II | Fichas técnicas, costos, comparativo de proveedores | [2.1](#21-fichas-técnicas-estimación-de-costos-y-comparativo-de-proveedores) | CUMPLE |
| II | Diagrama de clases UML 2.4.1+ / modelado NoSQL | [2.2](#22-diagrama-de-clases-uml-y-modelado-nosql) | CUMPLE |
| II | Diagrama de despliegue UML 2.4.1+ | [2.3](#23-diagrama-de-despliegue-uml) | CUMPLE |
| II | Prototipo navegable HTML-CSS con framework | [2.4](#24-prototipo-navegable) | CUMPLE |
| II | Entregas en control de versiones | [2.5](#25-control-de-versiones-trimestre-ii) | CUMPLE |
| III | Base de datos con Schema Validation (MongoDB) | [3.1](#31-construcción-de-la-base-de-datos-con-schema-validation) | CUMPLE |
| III | CRUD Operations y Aggregations | [3.2](#32-crud-operations-y-aggregations) | CUMPLE |
| III | Encriptación de contraseñas y seguridad de datos | [3.3](#33-seguridad-de-la-base-de-datos) | CUMPLE |
| III | FrontEnd con framework, conectado a BD, 50 % | [3.4](#34-frontend-con-framework-avance-50-) | CUMPLE |
| III | API REST con seguridad | [3.5](#35-api-rest-con-seguridad) | CUMPLE |
| III | Entregas en control de versiones | [3.6](#36-control-de-versiones-trimestre-iii) | CUMPLE |
| IV | Front end web consume la API REST (80 %) | [4.1](#41-frontend-web-consumiendo-la-api-rest-avance-80-) | CUMPLE |
| IV | Consumo de la API desde aplicación móvil | [4.2](#42-consumo-de-la-api-desde-la-aplicación-móvil-pwa) | CUMPLE |
| IV | API REST documentada (Swagger) | [4.3](#43-documentación-de-la-api-con-swagger--openapi) | CUMPLE |
| IV | Aplicación de un modelo de calidad | [4.4](#44-modelo-de-calidad-isoiec-25010) | CUMPLE |
| IV | Metodología ágil en el proyecto móvil | [4.5](#45-metodología-ágil-en-el-proyecto-móvil) | CUMPLE |
| IV | Entregas en control de versiones | [4.6](#46-control-de-versiones-trimestre-iv) | CUMPLE |
| V | Codificación al 100 % | [5.1](#51-codificación-al-100-) | CUMPLE |
| V | Técnicas de pruebas de software | [5.2](#52-pruebas-de-software) | CUMPLE |
| V | Planes de instalación, respaldo, migración y capacitación | [5.3](#53-planes-de-instalación-respaldo-migración-y-capacitación) | CUMPLE |
| V | Manuales de instalación, técnico y de usuario | [5.4](#54-manuales) | CUMPLE |
| V | Despliegue según arquitectura UML | [5.5](#55-despliegue-del-aplicativo) | CUMPLE |
| V | Entregas en control de versiones | [5.6](#56-control-de-versiones-trimestre-v) | CUMPLE |

---

# TRIMESTRE I

## 1.1 Nombre, objetivos, problema, alcance y justificación

### Nombre del proyecto

**SemillerosUT: sistema web y aplicación web progresiva (PWA) para la gestión, suscripción y divulgación de los semilleros de investigación del IDEAD de la Universidad del Tolima.**

### Objetivo general

Desarrollar un sistema de información web con una aplicación web progresiva instalable, construido en PHP con Laravel, que permita la gestión, suscripción y divulgación de los semilleros de investigación del IDEAD de la Universidad del Tolima, facilitando a los estudiantes consultar los semilleros, solicitar su vinculación y proponer nuevas ideas de investigación.

### Objetivos específicos

1. Analizar los procesos administrativos y académicos para la creación, divulgación y vinculación a semilleros en el IDEAD de la Universidad del Tolima.
2. Establecer el alcance del sistema y la metodología de desarrollo (Scrum).
3. Comprobar la viabilidad técnica, operativa y económica de la solución.
4. Identificar y especificar los requerimientos funcionales y no funcionales mediante historias de usuario.
5. Diseñar los casos de uso, el diagrama de clases, el modelo de datos documental (MongoDB) y el diagrama de despliegue.
6. Diseñar y validar las interfaces gráficas mediante wireframes y un prototipo navegable.
7. Implementar el panel administrativo web, la API REST segura y la PWA para estudiantes.
8. Verificar la calidad del software mediante pruebas y el modelo ISO/IEC 25010, y desplegar la solución en un entorno de producción.

### Planteamiento del problema

La Universidad del Tolima publica la información de sus semilleros en el sitio `investigaciones.ut.edu.co`, organizada jerárquicamente por facultades. Sin embargo, esa información es estática y poco detallada, y cuando un estudiante se interesa en un semillero, la única vía de contacto es un formulario genérico. En muchos casos los estudiantes no conocen a los coordinadores, lo que hace que pierdan el interés.

La institución cuenta con **76 semilleros** (50 en modalidad presencial y 26 en el IDEAD). Si la información se difunde mejor, más estudiantes pueden vincularse y desarrollar competencias investigativas. Los medios alternos (carteleras, correos masivos, visitas a clases) son de alcance limitado, generan saturación o costos ambientales, y no permiten llevar un registro de solicitudes.

### Pregunta problema

**¿Cómo mejorar la gestión y divulgación de la información de los semilleros de investigación del IDEAD de la Universidad del Tolima, y facilitar la vinculación de los estudiantes, mediante un sistema web y una aplicación web progresiva accesible desde dispositivos móviles?**

### Alcance del proyecto

**Incluye:**

- Panel web administrativo (Laravel + Blade + Bootstrap) para los roles *Administrador del sistema*, *Administrativo* y *Líder de semillero*: gestión de usuarios, facultades, programas, CAT, áreas, grupos, coordinadores, semilleros (misión, visión, justificación, objetivos, resultados), integrantes, solicitudes y propuestas.
- PWA instalable para el rol *Estudiante*: inicio de sesión con la cuenta institucional de Google, consulta de semilleros por facultad, solicitud para ser miembro, registro de ideas de proyecto, perfil, "Mis solicitudes" y "Mis propuestas".
- API REST segura (Laravel Sanctum) consumida por la PWA y por los componentes dinámicos del panel.
- Registro de auditoría de toda creación y modificación.
- Eliminación lógica (cambio de estado): ninguna entidad se borra físicamente.
- Despliegue piloto para los semilleros del **CAT Kennedy (Bogotá)**, con posibilidad de ampliación a los demás CAT.

**No incluye (fuera de alcance):**

- Publicación como app nativa en Google Play o App Store (la PWA se instala desde el navegador; ver [2.1](#21-fichas-técnicas-estimación-de-costos-y-comparativo-de-proveedores) sobre la opción TWA).
- Integración con los sistemas académicos internos de la universidad (matrícula, notas).
- Mensajería en tiempo real entre estudiantes y líderes (queda como recomendación futura).
- Carga de evidencias multimedia de resultados de investigación.

### Justificación

Los estudiantes interesados en investigación no encuentran con facilidad un contacto, un medio de incorporación o un canal para proponer nuevos proyectos. Una aplicación a la que accedan con su cuenta institucional pone al alcance de nuevos y antiguos estudiantes la información necesaria para vincularse, y ayuda a posicionar a la universidad como líder en formación investigativa.

La adaptación a **PWA** mantiene la ventaja que el diseño original buscaba con la app híbrida (acceso desde el celular, "siempre a la mano"), pero con menor costo y mantenimiento: un solo código HTML/CSS/JS sirve para escritorio y móvil, se instala sin tienda de aplicaciones, funciona en Android, iOS y escritorio (supera la limitación a Android señalada en las recomendaciones del documento original) y permite consultar contenido en caché sin conexión.

El sistema también beneficia a la institución, a los entes administrativos, a los programas y a los docentes, porque centraliza el avance de las investigaciones y deja trazabilidad de solicitudes y propuestas. Hoy no existe una herramienta equivalente en la universidad.

---

## 1.2 Mapa de procesos del negocio (BPMN)

> El BPMN modela el **proceso de negocio** de la universidad (cómo se crea un semillero y cómo un estudiante se vincula), no el funcionamiento interno del aplicativo. El archivo fuente editable se encuentra en `docs/bpmn/proceso_semilleros.bpmn` (elaborado en `<Bizagi Modeler / Camunda Modeler / draw.io>`). A continuación se representa con carriles (pools/lanes) en Mermaid.

### Proceso 1: Creación y divulgación de un semillero

```mermaid
flowchart LR
    subgraph L1[Carril: Líder de semillero / Docente]
        A((Inicio)) --> B[Identificar problemática<br/>y línea de investigación]
        B --> C[Formular propuesta del semillero:<br/>objetivo, misión, visión, justificación]
        C --> D[Radicar solicitud de creación<br/>ante la coordinación]
    end
    subgraph L2[Carril: Coordinación de investigación IDEAD / Administrativo]
        D --> E{¿Cumple Acuerdo<br/>0033 de 2018?}
        E -- No --> F[Devolver con observaciones]
        F --> C
        E -- Sí --> G[Emitir comunicado escrito<br/>de aprobación]
    end
    subgraph L3[Carril: Administrador del sistema]
        G --> H[Asignar grupo, CAT y coordinador]
        H --> I[Habilitar publicación del semillero]
    end
    subgraph L4[Carril: Comunidad estudiantil]
        I --> J[Consultar semillero publicado]
        J --> K((Fin))
    end
```

### Proceso 2: Vinculación de un estudiante a un semillero

```mermaid
flowchart LR
    subgraph E1[Carril: Estudiante]
        S((Inicio)) --> A[Conocer oferta de semilleros]
        A --> B{¿Existe un semillero<br/>de su interés?}
        B -- Sí --> C[Enviar solicitud de vinculación]
        B -- No --> D[Proponer idea de nuevo semillero]
        J[Recibir respuesta] --> Z((Fin))
    end
    subgraph E2[Carril: Líder de semillero]
        C --> E[Revisar solicitud]
        E --> F{¿Cumple requisitos<br/>y hay cupo?}
        F -- Sí --> G[Firmar compromiso de semillero<br/>y registrar integrante]
        F -- No --> H[Rechazar con motivo]
        G --> J
        H --> J
    end
    subgraph E3[Carril: Administrativo / Coordinación]
        D --> K[Evaluar propuesta por área<br/>de conocimiento]
        K --> L{¿Viable?}
        L -- Sí --> M[Iniciar Proceso 1:<br/>creación de semillero]
        L -- No --> N[Archivar propuesta]
        M --> J
        N --> J
    end
```

**Actores del negocio:** estudiante, líder de semillero (docente tutor), coordinación de investigación del IDEAD, director de programa, coordinador de CAT y administrador del sistema.

**Normatividad que rige el proceso:** Ley 1581 de 2012 y Decreto 1377 de 2013 (protección de datos personales), Reglamento Estudiantil (Acuerdo 006 de 1996) y Acuerdo 0033 de 2018 (sistema de investigación para pregrado a distancia de la Universidad del Tolima).

> *Nota:* el documento original cita "ley 1585 de 2012"; la norma reglamentaria de la Ley 1581 es el **Decreto 1377 de 2013** (hoy compilado en el Decreto 1074 de 2015). Se sugiere verificar la cita en la versión final.

---

## 1.3 Recolección de información y estadística descriptiva

### Técnicas aplicadas

La investigación se enmarca en la **investigación de campo participante** (el equipo hace parte de la comunidad estudiantil del CAT Kennedy). Se usaron:

| Técnica | Instrumento | Población / muestra | Objetivo |
|---|---|---|---|
| Entrevista semiestructurada | Guion de preguntas abiertas | 1 docente (Pablo Cuenca), 2 estudiantes (Carol Rodríguez, Mauricio Casas) | Conocer percepción sobre la necesidad y viabilidad |
| Observación / trabajo de campo | Revisión de sitios web de las principales universidades del país | Portales de investigación institucionales | Identificar cómo se divulga la información de semilleros |
| Análisis documental | Sitio `investigaciones.ut.edu.co`, Acuerdo 0033 de 2018 | Información institucional de semilleros | Cuantificar la oferta y entender el proceso |
| Matriz de ponderación | Matriz de priorización de funciones | 3 alternativas de solución | Seleccionar la alternativa técnica |

### Síntesis de las entrevistas

| Entrevistado | Rol | ¿Considera viable la idea? | Aporte principal |
|---|---|---|---|
| Pablo Cuenca | Docente | Sí | La web actual no tiene información amplia; incluir nombre, objetivo, líneas, integrantes, intereses y experiencias; permitir registrar nuevos semilleros. |
| Carol Rodríguez | Estudiante | Sí | Incluir cómo se ingresa a un semillero, requisitos y base/idea del semillero. |
| Mauricio Casas | Estudiante | Sí | No existe una herramienta similar; serviría de guía para ingresar o para generar propuestas. |

### Estadística descriptiva

**a) Aceptación de la propuesta (entrevistas, n = 3)**

| Respuesta | Frecuencia absoluta | Frecuencia relativa |
|---|---|---|
| Viable / a favor | 3 | 100 % |
| No viable | 0 | 0 % |
| **Total** | **3** | **100 %** |

**b) Categorías de necesidades mencionadas (análisis de contenido; una entrevista puede mencionar varias)**

| Necesidad identificada | Menciones | % de entrevistados |
|---|---|---|
| Información detallada del semillero (objetivo, líneas, integrantes) | 3 | 100 % |
| Mecanismo para vincularse / requisitos de ingreso | 2 | 66,7 % |
| Registrar o proponer nuevos semilleros | 2 | 66,7 % |
| Acceso fácil desde el móvil | 2 | 66,7 % |

**c) Oferta de semilleros de la universidad (análisis documental)**

| Modalidad | Semilleros | Porcentaje |
|---|---|---|
| Presencial | 50 | 65,8 % |
| A distancia (IDEAD) | 26 | 34,2 % |
| **Total** | **76** | **100 %** |

Media de semilleros por modalidad: 38; rango: 24.

**d) Matriz de ponderación de alternativas (Tabla 1 del documento original)**

| Función | Prioridad | Aplicativo | Web Service | Campaña |
|---|---|---|---|---|
| Verificación de usuario | 6 | ✔ | | |
| Operabilidad | 9 | ✔ | ✔ | ✔ |
| Mantenimiento | 9 | ✔ | ✔ | |
| Múltiples usuarios | 6 | ✔ | | |
| Registro en base de datos | 7 | ✔ | ✔ | |
| Facilidad de acceso | 9 | ✔ | | ✔ |
| Optimización de costos | 7 | ✔ | ✔ | |
| Calidad | 10 | ✔ | ✔ | |
| Documentación estratégica | 8 | ✔ | ✔ | |
| Registro de solicitudes | 8 | ✔ | | |
| Actualizable | 9 | ✔ | ✔ | ✔ |
| **Puntaje ponderado (suma de prioridades cubiertas)** | **88** | **88** | **59** | **27** |
| **% de cumplimiento** | | **100 %** | **67,0 %** | **30,7 %** |

**Análisis de resultados.** El aplicativo cubre el 100 % de las funciones priorizadas, frente al 67 % de un sitio web tradicional y el 30,7 % de una campaña publicitaria. Las funciones que marcan la diferencia son verificación de usuario, múltiples usuarios, facilidad de acceso y registro de solicitudes. La PWA adoptada conserva todas las funciones del "aplicativo" y además integra las ventajas del web service (un solo despliegue, mantenimiento centralizado), por lo que se mantiene como la alternativa seleccionada.

---

## 1.4 Requerimientos con historias de usuario (Scrum)

### Roles del sistema

| Rol | Canal | Permisos resumidos |
|---|---|---|
| Administrador del sistema | Web | Gestión de usuarios, facultades, áreas, programas, CAT y coordinadores. |
| Líder de semillero | Web | Gestión de semilleros, misión, visión, justificación, objetivos, resultados, grupos, integrantes, solicitudes y propuestas. |
| Administrativo | Web | Consulta y listado de toda la información (director de programa, coordinador de CAT, coordinación de investigación). |
| Estudiante | PWA | Consulta de semilleros, solicitud de vinculación, propuestas, perfil. |

### Historias de usuario — Requerimientos funcionales

Formato: *Como [rol] quiero [acción] para [beneficio]*. Estimación en puntos de historia (serie de Fibonacci).

| ID | RF | Historia de usuario | Criterios de aceptación | Prioridad | Pts |
|---|---|---|---|---|---|
| HU01 | RF01 | Como **administrador** quiero crear, listar, consultar, modificar y activar/inactivar usuarios para controlar quién accede al panel web. | Email único; contraseña cifrada; no existe botón eliminar, solo cambio de estado; usuario inactivo no puede iniciar sesión. | Alta | 8 |
| HU02 | RF01 | Como **usuario web** quiero iniciar y cerrar sesión con email y contraseña para acceder a mis funciones. | Credenciales erróneas muestran mensaje genérico; máximo 5 intentos por minuto; la sesión se regenera al iniciar. | Alta | 5 |
| HU03 | RF01 | Como **usuario web** quiero recuperar mi contraseña por correo para no perder el acceso. | Se envía enlace con token de un solo uso con vencimiento de 60 minutos. | Media | 3 |
| HU04 | RF01 | Como **estudiante** quiero iniciar sesión con mi cuenta de Google institucional para no crear otra contraseña. | Solo se aceptan correos del dominio institucional configurado; el primer ingreso crea el perfil con rol estudiante. | Alta | 5 |
| HU05 | RF02 | Como **administrador** quiero gestionar facultades para organizar los programas. | Código y nombre obligatorios; código único; solo cambio de estado. | Alta | 3 |
| HU06 | RF03 | Como **administrador** quiero gestionar programas asociados a una facultad para relacionarlos con semilleros, integrantes, solicitudes y propuestas. | Programa debe tener facultad; tipo Pregrado/Posgrado. | Alta | 3 |
| HU07 | RF04 | Como **administrador** quiero gestionar los CAT para asignar cada semillero a su sede. | Nombre, código, dirección, ciudad, email y al menos un teléfono. | Alta | 3 |
| HU08 | RF05 | Como **administrador** quiero gestionar áreas de conocimiento para clasificar semilleros y propuestas. | Código único. | Alta | 2 |
| HU09 | RF06 | Como **líder** quiero gestionar grupos de investigación para vincular los semilleros a su grupo. | Código único. | Alta | 2 |
| HU10 | RF07 | Como **administrador** quiero gestionar coordinadores para asignarlos a los semilleros. | Documento único; email válido. | Alta | 3 |
| HU11 | RF13 | Como **líder** quiero crear y editar semilleros con datos generales, misión, visión y justificación para publicarlos. | Requiere grupo, CAT, coordinador y facultad existentes; formulario por pestañas. | Alta | 8 |
| HU12 | RF08 | Como **líder** quiero agregar y editar objetivos de un semillero para comunicar su propósito. | El semillero debe existir. | Alta | 3 |
| HU13 | RF09 | Como **líder** quiero registrar resultados de un semillero para mostrar su avance. | El semillero debe existir. | Alta | 3 |
| HU14 | RF12 | Como **líder** quiero gestionar los integrantes de mi semillero para mantener actualizado el equipo. | El integrante pertenece a un programa; nivel PR/PG. | Alta | 5 |
| HU15 | RF13 | Como **estudiante** quiero ver los semilleros organizados por facultad y su detalle (misión, visión, objetivos) para decidir a cuál vincularme. | Solo se muestran semilleros activos; funciona con la última versión en caché si no hay conexión. | Alta | 5 |
| HU16 | RF10 | Como **estudiante** quiero enviar una solicitud para ser miembro de un semillero para iniciar mi vinculación. | Una solicitud pendiente por semillero y estudiante; confirmación visible. | Alta | 5 |
| HU17 | RF10 | Como **líder** quiero revisar, aprobar o rechazar solicitudes para gestionar el ingreso de nuevos integrantes. | Al aprobar se crea el integrante; estados: pendiente, aprobada, rechazada. | Alta | 5 |
| HU18 | RF11 | Como **estudiante** quiero registrar una idea de proyecto indicando áreas y programa para proponer un nuevo semillero. | Mínimo un área; observación de 20 a 2000 caracteres. | Alta | 5 |
| HU19 | RF11 | Como **líder/administrativo** quiero consultar las propuestas recibidas para evaluar nuevos semilleros. | Filtros por área y programa. | Media | 3 |
| HU20 | RF10/RF11 | Como **estudiante** quiero ver "Mis solicitudes" y "Mis propuestas" para conocer su estado. | Tabla con fecha, semillero/área y estado. | Media | 3 |
| HU21 | RF01 | Como **estudiante** quiero ver mi perfil y actualizar mi teléfono para que me puedan contactar. | Solo el teléfono es editable. | Baja | 2 |
| HU22 | RF14 | Como **administrador** quiero que cada creación o modificación quede registrada en auditoría para tener trazabilidad. | Guarda usuario, fecha/hora, colección, datos anteriores y nuevos. | Alta | 5 |
| HU23 | — | Como **administrativo** quiero consultar todos los módulos en modo lectura para hacer seguimiento. | No ve botones de crear/editar; la API responde 403 a escrituras. | Media | 3 |

### Historias de usuario — Requerimientos no funcionales

| ID | RNF | Historia | Criterio medible |
|---|---|---|---|
| HU-NF01 | RNF01 Plataforma web | Como **equipo de desarrollo** queremos un backend Laravel con patrón MVC y Git para facilitar el mantenimiento. | Laravel 12.x, PHP ≥ 8.2, PSR-12, repositorio Git con ramas protegidas. |
| HU-NF02 | RNF02 Plataforma móvil | Como **estudiante** quiero instalar la aplicación en mi celular sin tienda de apps para acceder rápidamente. | Lighthouse: PWA instalable; manifest y service worker válidos; funciona en Android e iOS. |
| HU-NF03 | RNF03 Seguridad | Como **universidad** quiero que solo personal autorizado acceda a la web y solo estudiantes activos a la PWA para proteger los datos. | HTTPS obligatorio; bcrypt; tokens Sanctum; roles y políticas; cumplimiento Ley 1581. |
| HU-NF04 | RNF04 Manual | Como **usuario** quiero un manual para resolver dudas de uso. | Manuales de usuario, técnico e instalación publicados en `/docs`. |
| HU-NF05 | RNF05 Creación de usuario web | Como **área administrativa** quiero que un usuario web solo se cree con autorización escrita. | Campo `authorization_ref` obligatorio al crear usuario web. |
| HU-NF06 | RNF06 Creación de semillero | Como **coordinación** quiero que un semillero solo se cree con aprobación previa. | Campo `approval_ref` obligatorio en la creación. |
| HU-NF07 | Rendimiento | Como **usuario** quiero respuestas rápidas. | Endpoints de listado < 500 ms con 1000 semilleros (p95). |
| HU-NF08 | Usabilidad | Como **usuario** quiero una interfaz responsiva y accesible. | Bootstrap 5 responsivo; contraste WCAG AA en elementos principales. |

---

## 1.5 Diagrama y especificación extendida de casos de uso

### Diagrama general de casos de uso

```mermaid
flowchart LR
    EST([👤 Estudiante])
    LID([👤 Líder de semillero])
    ADM([👤 Administrativo])
    SYS([👤 Administrador del sistema])

    subgraph SemillerosUT
        CU01((CU01 Gestionar usuarios / autenticación))
        CU02((CU02 Gestionar facultades))
        CU03((CU03 Gestionar programas))
        CU04((CU04 Gestionar CAT))
        CU05((CU05 Gestionar áreas))
        CU06((CU06 Gestionar grupos))
        CU07((CU07 Gestionar coordinadores))
        CU08((CU08 Gestionar objetivos))
        CU09((CU09 Gestionar resultados))
        CU10((CU10 Gestionar solicitudes))
        CU11((CU11 Gestionar propuestas))
        CU12((CU12 Gestionar integrantes))
        CU13((CU13 Gestionar semilleros))
        CU14((CU14 Registrar auditoría))
    end

    EST --- CU01 & CU13 & CU08 & CU10 & CU11
    LID --- CU01 & CU06 & CU08 & CU09 & CU10 & CU11 & CU12 & CU13
    ADM --- CU01 & CU02 & CU03 & CU04 & CU09 & CU10 & CU11 & CU13
    SYS --- CU01 & CU02 & CU03 & CU04 & CU05 & CU07 & CU13
    CU13 -. «include» .-> CU14
    CU10 -. «include» .-> CU14
    CU11 -. «include» .-> CU14
    CU12 -. «extend» .-> CU10
```

> Los diagramas UML originales por actor (Figuras 2 a 9 del documento base) se conservan en `docs/uml/casos_de_uso/`. La relación «extend» CU12 → CU10 representa que al aprobar una solicitud se registra el integrante.

### Especificación extendida

#### CU01 – Gestión de usuarios y autenticación

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema (principal); Estudiante, Líder, Administrativo (autenticación) |
| Requerimiento | RF01 — HU01, HU02, HU03, HU04, HU21 |
| Descripción | Permite autenticar a los usuarios y administrar las cuentas del panel web. |
| Precondiciones | Usuario web creado con autorización escrita (RNF05). Estudiante con cuenta institucional activa. |
| Flujo básico (inicio de sesión web) | 1. El usuario abre `/login`. 2. Ingresa email y contraseña. 3. El sistema valida formato. 4. El sistema verifica credenciales con `Hash::check` y que `is_active = true`. 5. Regenera la sesión y redirige al panel según el rol. 6. Registra el acceso en auditoría. |
| Flujo básico (gestión) | 1. El administrador entra a *Usuarios*. 2. El sistema lista usuarios paginados. 3. Selecciona *Crear*. 4. Ingresa nombre, email, rol, referencia de autorización. 5. El sistema genera contraseña temporal cifrada y envía correo. 6. Muestra confirmación. |
| Flujos alternos | A1. Credenciales inválidas → mensaje "Credenciales incorrectas" (paso 4). A2. Usuario inactivo → mensaje "Usuario inactivo, contacte al administrador". A3. Olvido de contraseña → envía enlace con token (60 min). A4. Estudiante con dominio no institucional → "Debe usar su cuenta institucional". A5. Más de 5 intentos → bloqueo temporal (HTTP 429). |
| Postcondiciones | Éxito: sesión/token activo, o usuario creado/modificado/activado. Falla: no se realiza la acción y se muestra el error. |
| Restricciones | No se elimina ningún usuario, solo se cambia su estado. No todos los actores tienen las mismas acciones. |

#### CU02 – Gestión de facultades

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema (escritura); Líder, Administrativo (lectura); Estudiante (lectura en PWA como agrupador) |
| Requerimiento | RF02 — HU05 |
| Precondiciones | Sesión iniciada con rol autorizado. La facultad existe en la universidad. |
| Flujo básico | 1. Ingresa a *Facultades*. 2. El sistema lista las facultades. 3. Selecciona *Crear*. 4. Diligencia código y nombre. 5. El sistema valida unicidad del código. 6. Guarda, registra auditoría y confirma. |
| Flujos alternos | A1. Código duplicado → mensaje de validación. A2. *Consultar* → muestra detalle y programas asociados. A3. *Editar* → modifica y registra valores anteriores en auditoría. A4. *Inactivar* → cambia estado. |
| Postcondiciones | Facultad creada/actualizada; o error sin cambios. |
| Restricciones | No se permite eliminar facultades. |

#### CU03 – Gestión de programas

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema; Líder y Administrativo (lectura) |
| Requerimiento | RF03 — HU06 |
| Precondiciones | Existe al menos una facultad activa. |
| Flujo básico | 1. Ingresa a *Programas*. 2. Lista. 3. *Crear*. 4. Selecciona facultad; ingresa código, nombre y tipo. 5. Valida. 6. Guarda y audita. |
| Flujos alternos | A1. Facultad inactiva → no aparece en la lista. A2. Código duplicado → error. A3. Consultar/editar/inactivar. |
| Postcondiciones | Programa disponible para semilleros, integrantes, solicitudes y propuestas. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU04 – Gestión de CAT

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema; Líder y Administrativo (lectura) |
| Requerimiento | RF04 — HU07 |
| Precondiciones | La universidad tiene registrado el CAT. |
| Flujo básico | 1. Ingresa a *CAT*. 2. Lista. 3. *Crear*. 4. Diligencia nombre, código, dirección, ciudad, email, teléfonos (1 a 3). 5. Valida. 6. Guarda y audita. |
| Flujos alternos | A1. Email inválido → error. A2. Sin teléfono principal → error. A3. Consultar/editar/inactivar. |
| Postcondiciones | CAT disponible para asignar semilleros. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU05 – Gestión de áreas

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema |
| Requerimiento | RF05 — HU08 |
| Precondiciones | Sesión de administrador. |
| Flujo básico | 1. Ingresa a *Áreas*. 2. Lista. 3. *Crear*. 4. Código y nombre. 5. Valida. 6. Guarda y audita. |
| Flujos alternos | A1. Código duplicado. A2. Consultar/editar/inactivar. |
| Postcondiciones | Área disponible para semilleros y propuestas. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU06 – Gestión de grupos

| Campo | Descripción |
|---|---|
| Actores | Líder de semillero |
| Requerimiento | RF06 — HU09 |
| Precondiciones | Ninguna adicional a la sesión. |
| Flujo básico | 1. Ingresa a *Grupos*. 2. Lista. 3. *Crear*. 4. Código y nombre. 5. Guarda y audita. |
| Flujos alternos | A1. Código duplicado. A2. Consultar/editar/inactivar. |
| Postcondiciones | Grupo disponible para semilleros. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU07 – Gestión de coordinadores

| Campo | Descripción |
|---|---|
| Actores | Administrador del sistema |
| Requerimiento | RF07 — HU10 |
| Precondiciones | El coordinador pertenece a la universidad. |
| Flujo básico | 1. Ingresa a *Coordinadores*. 2. Lista. 3. *Crear*. 4. Nombre, documento, email, teléfono. 5. Valida unicidad de documento. 6. Guarda y audita. |
| Flujos alternos | A1. Documento duplicado. A2. Consultar/editar/inactivar. |
| Postcondiciones | Coordinador asignable a semilleros. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU08 – Gestión de objetivos

| Campo | Descripción |
|---|---|
| Actores | Líder (escritura); Administrativo y Estudiante (lectura) |
| Requerimiento | RF08 — HU12 |
| Precondiciones | Existe un semillero creado. |
| Flujo básico | 1. En el detalle del semillero abre la pestaña *Objetivos*. 2. *Agregar objetivo*. 3. Escribe el contenido. 4. Guarda (se embebe en el documento del semillero) y audita. |
| Flujos alternos | A1. Contenido vacío → error. A2. Editar/inactivar objetivo. A3. Estudiante: visualiza la lista en la PWA. |
| Postcondiciones | Objetivo visible en web y PWA. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU09 – Gestión de resultados

| Campo | Descripción |
|---|---|
| Actores | Líder (escritura); Administrativo (lectura) |
| Requerimiento | RF09 — HU13 |
| Precondiciones | Existe un semillero creado. |
| Flujo básico | 1. Pestaña *Resultados* del semillero. 2. *Agregar resultado*. 3. Contenido. 4. Guarda y audita. |
| Flujos alternos | A1. Contenido vacío. A2. Editar/inactivar. |
| Postcondiciones | Resultado registrado. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU10 – Gestión de solicitudes

| Campo | Descripción |
|---|---|
| Actores | Estudiante (crea); Líder (resuelve); Administrativo (consulta) |
| Requerimiento | RF10 — HU16, HU17, HU20 |
| Precondiciones | Estudiante con sesión iniciada en la PWA y activo en la universidad. Semillero activo. |
| Flujo básico | 1. El estudiante abre el detalle del semillero. 2. Presiona *Ser miembro*. 3. El sistema muestra formulario con nombre y email precargados. 4. Selecciona programa, ingresa teléfono y mensaje. 5. Presiona *Enviar*. 6. La API valida y crea la solicitud en estado `pendiente`. 7. Se muestra "Solicitud enviada". 8. Se audita. |
| Flujos alternos | A1. Ya existe una solicitud pendiente para ese semillero → "Ya tienes una solicitud en curso". A2. Sin conexión → la PWA informa que no se pudo enviar y conserva el borrador. A3. El líder aprueba → estado `aprobada` y se ejecuta CU12 (crear integrante). A4. El líder rechaza → estado `rechazada` con motivo. |
| Postcondiciones | Solicitud registrada y visible en "Mis solicitudes". |
| Restricciones | No se elimina; no todos los actores tienen las mismas acciones. |

#### CU11 – Gestión de propuestas

| Campo | Descripción |
|---|---|
| Actores | Estudiante (crea); Líder y Administrativo (consulta/evaluación) |
| Requerimiento | RF11 — HU18, HU19, HU20 |
| Precondiciones | Estudiante con sesión en la PWA y activo en la universidad. |
| Flujo básico | 1. En la vista principal presiona **+**. 2. Diligencia programa, áreas (una o varias), teléfono y descripción de la idea. 3. Presiona *Enviar*. 4. La API valida y guarda en estado `recibida`. 5. Confirma el envío. 6. Audita. |
| Flujos alternos | A1. Sin áreas seleccionadas → error. A2. Descripción < 20 caracteres → error. A3. Evaluador cambia el estado a `viable` / `archivada`. |
| Postcondiciones | Propuesta visible en "Mis propuestas". |
| Restricciones | No se elimina. |

#### CU12 – Gestión de integrantes

| Campo | Descripción |
|---|---|
| Actores | Líder de semillero |
| Requerimiento | RF12 — HU14 |
| Precondiciones | Existe el semillero y el programa del integrante. |
| Flujo básico | 1. Pestaña *Integrantes*. 2. *Agregar* (o proviene de una solicitud aprobada). 3. Nombre, código, programa, nivel, email, dirección, teléfono. 4. Guarda y audita. |
| Flujos alternos | A1. Integrante ya registrado en el semillero → error. A2. Editar/inactivar. |
| Postcondiciones | Integrante asociado al semillero. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU13 – Gestión de semilleros

| Campo | Descripción |
|---|---|
| Actores | Líder (escritura); Administrador del sistema, Administrativo y Estudiante (lectura) |
| Requerimiento | RF13 — HU11, HU15 |
| Precondiciones | Existen el grupo, el CAT, el coordinador y la facultad. Aprobación escrita (RNF06). |
| Flujo básico | 1. Ingresa a *Semilleros*. 2. *Crear*. 3. Pestaña *Datos generales*: nombre, código, grupo, CAT, coordinador, facultad, programas, áreas, objetivo general, referencia de aprobación. 4. Pestaña *Misión y visión*. 5. Pestaña *Justificación*. 6. Guarda. 7. Agrega objetivos (CU08) y resultados (CU09). 8. Audita. |
| Flujos alternos | A1. Falta dato obligatorio → error por campo. A2. Consultar: vista consolidada con pestañas. A3. Estudiante: listado por facultad y detalle en PWA. A4. Inactivar: deja de mostrarse en la PWA. |
| Postcondiciones | Semillero publicado. |
| Restricciones | No se elimina, solo cambia de estado. |

#### CU14 – Registro de auditoría

| Campo | Descripción |
|---|---|
| Actores | Sistema (automático); Administrador (consulta) |
| Requerimiento | RF14 — HU22 |
| Precondiciones | Ninguna. |
| Flujo básico | 1. Un actor crea o modifica un documento. 2. El *Observer* de Eloquent captura el evento `created`/`updated`. 3. Guarda en `logs`: usuario, colección, id, acción, valores anteriores y nuevos, IP, fecha. |
| Flujos alternos | A1. Falla la escritura del log → se registra en `storage/logs/laravel.log` sin interrumpir la operación. |
| Postcondiciones | Registro de auditoría disponible para consulta. |
| Restricciones | La colección `logs` es de solo inserción (el usuario de aplicación no tiene permiso de actualizar ni borrar logs). |

---

## 1.6 Validación de requerimientos: mockups y wireframes

Los wireframes se elaboraron en **Balsamiq** (documento base, sección 9.11) y se validaron con el docente director y estudiantes del CAT Kennedy. Archivos: `docs/mockups/`. La siguiente tabla relaciona cada pantalla con su historia de usuario y con la vista implementada.

### PWA (Estudiante)

| # | Pantalla (wireframe) | HU | Vista / ruta implementada |
|---|---|---|---|
| A1 | Inicio de sesión con Google | HU04 | `/app/login` |
| A2 | Listado de semilleros por facultad | HU15 | `/app` |
| A3 | Detalle: misión, visión, objetivos (con regreso al listado) | HU15 | `/app/semilleros/{id}` |
| A4 | Solicitar ser miembro + confirmación | HU16 | `/app/semilleros/{id}/solicitar` |
| A5 | Registro idea de proyecto (botón +) + confirmación | HU18 | `/app/propuestas/nueva` |
| A6 | Menú: Perfil, Mis solicitudes, Mis propuestas, Cerrar sesión | HU20, HU21 | `/app/perfil`, `/app/solicitudes`, `/app/propuestas` |

### Panel web (Administrador, Líder, Administrativo)

| # | Pantalla (wireframe) | HU | Vista Blade |
|---|---|---|---|
| W1–W4 | Inicio de sesión, error de inicio, olvido y cambio de contraseña | HU02, HU03 | `auth/login`, `auth/forgot-password`, `auth/reset-password` |
| W5–W7 | Página principal, perfil, formularios principales | HU01 | `dashboard`, `profile/show` |
| W8–W11 | Gestión, crear, consultar y editar facultad | HU05 | `faculties/index|create|show|edit` |
| W12–W15 | Gestión, crear, consultar y editar programa | HU06 | `careers/*` |
| W16–W19 | Gestión, crear, consultar y editar CAT | HU07 | `cats/*` |
| W20–W25 | Gestión de semilleros, datos generales, misión y visión, justificación, objetivos, resultados | HU11–HU13 | `hotbeds/index|create|edit` (pestañas) |
| W26–W30 | Consulta semillero: visión y misión, objetivos, justificación, resultados | HU11, HU23 | `hotbeds/show` |

### Wireframe de referencia (PWA – listado)

```
┌─────────────────────────────┐
│ ☰  SemillerosUT         👤  │
├─────────────────────────────┤
│ 🔎 Buscar semillero...      │
│ ▼ Facultad de Tecnologías   │
│   ┌───────────────────────┐ │
│   │ Semillero INITIUM     │ │
│   │ Grupo A · CAT Kennedy │ │
│   └───────────────────────┘ │
│ ▶ Facultad de C. Humanas    │
│ ▶ Facultad de C. Educación  │
│                        (＋) │
└─────────────────────────────┘
```

**Resultado de la validación:** las pantallas cubren las 23 historias funcionales. Ajustes derivados de la validación: se agregó buscador en el listado de la PWA, estado visible de solicitudes/propuestas y pestañas en el formulario de semilleros para no saturar la vista.

---

## 1.7 Control de versiones

- **Herramienta:** Git + `<GitHub / GitLab>` — repositorio `<URL_REPOSITORIO_GIT>`.
- **Estrategia de ramas:** `main` (producción, protegida) · `develop` (integración) · `feature/HUxx-descripcion` · `hotfix/*`. Integración mediante *pull requests* revisados.
- **Convención de commits:** *Conventional Commits* (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`, `chore:`), referenciando la HU (`feat(HU16): envío de solicitud de vinculación`).
- **Etiquetas por trimestre:** `v0.1.0-T1`, `v0.2.0-T2`, `v0.3.0-T3`, `v0.4.0-T4`, `v1.0.0-T5`.

**Entregas del Trimestre I** (etiqueta `v0.1.0-T1`):

| Evidencia | Ruta en el repositorio |
|---|---|
| Documento de análisis (este archivo) | `docs/Documentacion_Tecnica_SemillerosUT.md` |
| BPMN | `docs/bpmn/` |
| Instrumentos de recolección y tabulación | `docs/recoleccion/` |
| Product Backlog e historias de usuario | `docs/scrum/backlog.md` / tablero `<URL_TABLERO>` |
| Casos de uso | `docs/uml/casos_de_uso/` |
| Mockups | `docs/mockups/` |

---

# TRIMESTRE II

## 2.1 Fichas técnicas, estimación de costos y comparativo de proveedores

### Fichas técnicas de hardware (equipos de desarrollo y pruebas)

| Ficha | Equipo | Especificaciones | Uso en el proyecto | Costo (COP) |
|---|---|---|---|---|
| FT-HW-01 | Portátil 15,6" (equipo principal) | AMD Ryzen 5 3550H, RAM ampliable (2 × SO-DIMM), pantalla FHD 1920×1080 120 Hz IPS, GPU Radeon RX 560X 4 GB, almacenamiento 1 TB | Desarrollo backend/frontend, contenedores locales, MongoDB local | 3.600.000 |
| FT-HW-02 | Portátil 15,6" | AMD A12-9700P / A10-9600P, 4–8 GB DDR3L, HD/FHD, HDD 1 TB 5400 rpm | Documentación, pruebas en navegador | 1.500.000 |
| FT-HW-03 | Portátil 15,6" | AMD A9-9425, 8 GB RAM, FHD antirreflejo, HDD SATA 5400 rpm | Pruebas funcionales y de usabilidad | 1.300.000 |
| FT-HW-04 | Celular Samsung Galaxy A01 | Android 9 (o superior), pantalla 5,7" TFT, 32 GB | Pruebas de instalación y uso de la PWA | 400.000 |
| | | | **Total hardware** | **6.800.000** |

**Requisitos mínimos del equipo de desarrollo para el stack actual:** CPU de 4 núcleos, 8 GB de RAM (16 GB recomendado), SSD de 256 GB, sistema operativo Windows 10/11, Ubuntu 22.04+ o macOS.

### Fichas técnicas de software

| Ficha | Software | Versión | Licencia | Función | Costo |
|---|---|---|---|---|---|
| FT-SW-01 | PHP | 8.2 o superior | PHP License | Lenguaje del backend | $0 |
| FT-SW-02 | Laravel | 12.x | MIT | Framework MVC, API, autenticación | $0 |
| FT-SW-03 | Composer | 2.x | MIT | Gestor de dependencias PHP | $0 |
| FT-SW-04 | MongoDB Community Server / Atlas | 7.0+ | SSPL / servicio | Base de datos documental | $0 (M0) |
| FT-SW-05 | Extensión `mongodb` para PHP + `mongodb/laravel-mongodb` | ext 1.x / paquete 5.x | Apache 2.0 / MIT | Driver y ODM Eloquent para MongoDB | $0 |
| FT-SW-06 | Laravel Sanctum | 4.x | MIT | Autenticación por tokens para la API | $0 |
| FT-SW-07 | Laravel Socialite | 5.x | MIT | Inicio de sesión con Google | $0 |
| FT-SW-08 | L5-Swagger (swagger-php) | 8.x | MIT | Documentación OpenAPI | $0 |
| FT-SW-09 | Bootstrap | 5.3 | MIT | Framework CSS responsivo | $0 |
| FT-SW-10 | Node.js + Vite | 20 LTS+ / 5+ | MIT | Compilación de assets | $0 |
| FT-SW-11 | Workbox (opcional) | 7.x | MIT | Utilidades para el service worker | $0 |
| FT-SW-12 | PHPUnit / Pest | 11.x / 3.x | BSD / MIT | Pruebas automatizadas | $0 |
| FT-SW-13 | Git + GitHub | 2.4x | GPL / servicio | Control de versiones | $0 |
| FT-SW-14 | Visual Studio Code | Última | MIT | Editor | $0 |
| FT-SW-15 | StarUML / draw.io / Bizagi Modeler | Última | Comercial-free / Apache | Modelado UML y BPMN | $0 |
| FT-SW-16 | MongoDB Compass | Última | SSPL | Cliente gráfico de BD | $0 |
| FT-SW-17 | Nginx + PHP-FPM | 1.24+ | BSD / PHP | Servidor web de producción | $0 |
| FT-SW-18 | Windows 10/11 Pro (2 licencias) | — | Comercial | Sistema operativo de equipos | 570.000 |

> **Corrección al documento original:** la Tabla 6 reporta un total de software de $847.500, pero la suma de sus filas (Windows $570.000 + publicación Play Store $92.500 + Heroku Dynos $185.000 + Heroku Postgres $185.000) da $1.032.500, que es el valor que usa la Tabla 9. Con la migración a PWA y la nueva infraestructura, esos costos se reemplazan por los de la tabla de infraestructura siguiente.

### Análisis comparativo de proveedores de infraestructura

Requisitos de la solución: PHP 8.2+ con extensión `mongodb`, acceso SSH/root para instalar la extensión y configurar Nginx, HTTPS (obligatorio para PWA), cola de trabajos para correos, base de datos MongoDB con respaldos. Carga esperada del piloto (CAT Kennedy): < 500 usuarios, < 1 GB de datos.

**Servidor de aplicaciones**

| Criterio | Hostinger VPS (KVM 2) | AWS Lightsail (2 GB) | DigitalOcean Droplet (2 GB) | Hosting compartido (cualquier proveedor) |
|---|---|---|---|---|
| vCPU / RAM | 2 vCPU / 8 GB | 2 vCPU / 2 GB | 1 vCPU / 2 GB | Compartidos |
| Almacenamiento | 100 GB NVMe | 60 GB SSD | 50 GB SSD | Variable |
| Transferencia | 8 TB | 3 TB | 2 TB | Variable |
| Acceso root / instalar ext. `mongodb` | Sí | Sí | Sí | **No** (generalmente) |
| Snapshots / respaldos | Semanales incluidos | Snapshots manuales/automáticos (con costo) | Backups opcionales (+20 %) | Según plan |
| Región cercana a Colombia | EE. UU. / Brasil | us-east-1 (Virginia) | Nueva York / San Francisco | Variable |
| Precio de referencia* | ≈ USD 7–10 / mes (pago anual) | ≈ USD 12 / mes | ≈ USD 12 / mes | ≈ USD 3–5 / mes |
| **Puntaje (0–5)**: técnico / costo / soporte | 5 / 5 / 4 | 4 / 4 / 5 | 4 / 4 / 4 | 1 / 5 / 3 |
| **Total (máx. 15)** | **14** | **13** | **12** | **9 (no cumple requisito técnico)** |

**Base de datos**

| Criterio | MongoDB Atlas M0 | MongoDB Atlas Flex | MongoDB autogestionado en el VPS |
|---|---|---|---|
| Almacenamiento | 512 MB | Hasta 5 GB | Limitado por el disco del VPS |
| Respaldos automáticos | No (se usa `mongodump`) | Sí (snapshots diarios) | Por script `mongodump` + cron |
| Alta disponibilidad | Réplica de 3 nodos | Réplica de 3 nodos | Nodo único |
| Schema Validation / Aggregations | Sí | Sí | Sí |
| Precio de referencia* | Gratis | ≈ USD 8–30 / mes según uso | Incluido en el VPS |
| Recomendación | Desarrollo y pruebas | **Producción piloto** | Alternativa de bajo costo |

\* *Valores de referencia en dólares, sujetos a cambios del proveedor y a la TRM. Deben verificarse y cotizarse en la fecha de compra; las capturas de las cotizaciones se anexan en `docs/costos/cotizaciones/`.*

**Decisión:** VPS **Hostinger KVM 2** para la aplicación (mejor relación RAM/costo y acceso root) y **MongoDB Atlas** (M0 en desarrollo, Flex en producción) para contar con respaldos gestionados y réplica. Alternativa equivalente si la institución prefiere AWS: Lightsail + Atlas en la misma región (us-east-1).

**Publicación móvil:** al ser PWA no hay costo de tienda. Si la universidad quisiera presencia en Google Play, la PWA puede empaquetarse como *Trusted Web Activity* (Bubblewrap) con el pago único de la cuenta de desarrollador de Google Play (USD 25).

### Estimación de costos del sistema (actualizada)

Supuesto de TRM para el cálculo: **COP 4.000 por USD** (`<ajustar a la TRM vigente>`). Horizonte: 12 meses de operación.

| Rubro | Detalle | Costo (COP) |
|---|---|---|
| Hardware | Tabla de fichas FT-HW | 6.800.000 |
| Software | Licencias Windows (resto open source) | 570.000 |
| Infraestructura | VPS 12 meses (≈ USD 9 × 12) | 432.000 |
| Infraestructura | MongoDB Atlas Flex 12 meses (≈ USD 10 × 12, uso bajo) | 480.000 |
| Infraestructura | Dominio `.com.co` / subdominio institucional + SSL Let's Encrypt | 80.000 |
| Personal | Consultor 40 h × 100.000 | 4.000.000 |
| Personal | Analista de desarrollo 400 h × 60.000 | 24.000.000 |
| Personal | Analista de pruebas 100 h × 40.000 | 4.000.000 |
| Personal | Documentador 80 h × 20.000 | 1.600.000 |
| Generales | Transporte (20 × 15.000), internet (3 × 75.000), energía (3 × 50.000) | 675.000 |
| | **Total estimado** | **42.637.000** |

Frente al costo del sistema propuesto en 2020 ($42.200.000), la variación es mínima; el cambio principal es que desaparecen Heroku y la publicación en Play Store y aparecen el VPS y Atlas. Si la universidad provee el subdominio y el servidor, el costo de infraestructura baja a $0.

---

## 2.2 Diagrama de clases (UML) y modelado NoSQL

### Diagrama de clases (UML 2.5 — notación Mermaid)

```mermaid
classDiagram
    direction LR
    class User {
        +ObjectId _id
        +String name
        +String email
        +String password
        +String role
        +String google_id
        +String phone
        +Boolean is_active
        +String authorization_ref
        +DateTime created_at
        +DateTime updated_at
        +isRole(role) bool
    }
    class Faculty {
        +ObjectId _id
        +String code
        +String name
        +String status
    }
    class Career {
        +ObjectId _id
        +ObjectId faculty_id
        +String code
        +String name
        +String type
        +String status
    }
    class Cat {
        +ObjectId _id
        +String code
        +String name
        +String address
        +String city
        +String email
        +String[] phones
        +String status
    }
    class Area {
        +ObjectId _id
        +String code
        +String name
        +String status
    }
    class Group {
        +ObjectId _id
        +String code
        +String name
        +String status
    }
    class Coordinator {
        +ObjectId _id
        +String name
        +String document
        +String email
        +String phone
        +String status
    }
    class Hotbed {
        +ObjectId _id
        +String code
        +String name
        +ObjectId group_id
        +ObjectId coordinator_id
        +ObjectId cat_id
        +ObjectId faculty_id
        +ObjectId leader_id
        +ObjectId[] career_ids
        +ObjectId[] area_ids
        +String overall_objective
        +String mission
        +String vision
        +String justification
        +String approval_ref
        +String status
        +publish() void
    }
    class Objective {
        <<embedded>>
        +ObjectId _id
        +String content
        +String status
    }
    class Result {
        <<embedded>>
        +ObjectId _id
        +String content
        +String status
    }
    class Member {
        +ObjectId _id
        +ObjectId hotbed_id
        +ObjectId career_id
        +ObjectId user_id
        +String name
        +String code
        +String level
        +String email
        +String address
        +String phone
        +String status
    }
    class MembershipRequest {
        +ObjectId _id
        +ObjectId hotbed_id
        +ObjectId career_id
        +ObjectId user_id
        +String name
        +String email
        +String phone
        +String message
        +String status
        +String response
        +approve() Member
        +reject(reason) void
    }
    class Proposal {
        +ObjectId _id
        +ObjectId career_id
        +ObjectId user_id
        +ObjectId[] area_ids
        +String name
        +String email
        +String phone
        +String observation
        +String status
    }
    class Log {
        +ObjectId _id
        +ObjectId user_id
        +String collection
        +ObjectId document_id
        +String action
        +Object before
        +Object after
        +String ip
        +DateTime created_at
    }

    Faculty "1" --> "0..*" Career : ofrece
    Faculty "1" --> "0..*" Hotbed : agrupa
    Group "1" --> "0..*" Hotbed : pertenece
    Coordinator "1" --> "0..*" Hotbed : coordina
    Cat "1" --> "0..*" Hotbed : sede
    User "1" --> "0..*" Hotbed : lidera
    Hotbed "1" *-- "0..*" Objective : embebe
    Hotbed "1" *-- "0..*" Result : embebe
    Hotbed "1..*" o-- "1..*" Area : clasifica
    Hotbed "1..*" o-- "1..*" Career : vincula
    Hotbed "1" --> "0..*" Member : integra
    Hotbed "1" --> "0..*" MembershipRequest : recibe
    Career "1" --> "0..*" Member
    Career "1" --> "0..*" MembershipRequest
    Career "1" --> "0..*" Proposal
    Proposal "0..*" o-- "1..*" Area : clasifica
    User "1" --> "0..*" MembershipRequest : envía
    User "1" --> "0..*" Proposal : envía
    User "1" --> "1..*" Log : genera
```

El archivo UML editable (`.mdj` de StarUML, notación UML 2.5) está en `docs/uml/clases/`.

### Modelado de datos NoSQL (de relacional a documental)

Criterios de transformación desde las 17 tablas PostgreSQL del documento original:

| Tabla relacional original | Colección MongoDB | Decisión de modelado | Justificación |
|---|---|---|---|
| `users` | `users` | Colección propia; se agregan `role`, `google_id`, `phone` | Unifica usuarios web y estudiantes de la PWA |
| `faculties`, `careers`, `cats`, `areas`, `groups`, `coordinators` | Una colección cada una | Referencias por `ObjectId` | Catálogos compartidos por muchos semilleros |
| `hotbeds` | `hotbeds` | Documento principal | Entidad central del dominio |
| `objectives`, `results` | Embebidos en `hotbeds` | Arreglos de subdocumentos | Relación 1:N acotada que siempre se lee junto al semillero |
| `areas_hotbeds`, `careers_hotbeds` | `hotbeds.area_ids[]`, `hotbeds.career_ids[]` | Arreglo de referencias | Elimina tablas intermedias N:M |
| `proposals_areas` | `proposals.area_ids[]` | Arreglo de referencias | Elimina tabla intermedia |
| `members`, `requests`, `proposals` | Colecciones propias | Referencias | Crecen sin límite y se consultan por separado |
| `logs` | `logs` | Colección de solo inserción con `before`/`after` | Auditoría (RF14) |

Ejemplo de documento `hotbeds`:

```json
{
  "_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c0d1" },
  "code": "98980",
  "name": "Semillero INITIUM",
  "group_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c001" },
  "coordinator_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c002" },
  "cat_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c003" },
  "faculty_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c004" },
  "leader_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c005" },
  "career_ids": [{ "$oid": "66f1a2b3c4d5e6f7a8b9c006" }],
  "area_ids": [{ "$oid": "66f1a2b3c4d5e6f7a8b9c007" }],
  "overall_objective": "Fortalecer la investigación formativa en gestión de bases de datos",
  "mission": "…",
  "vision": "…",
  "justification": "…",
  "objectives": [
    { "_id": { "$oid": "66f1a2b3c4d5e6f7a8b9c010" }, "content": "Diseñar…", "status": "active" }
  ],
  "results": [],
  "approval_ref": "COM-IDEAD-2026-015",
  "status": "active",
  "created_at": { "$date": "2026-09-01T14:00:00Z" },
  "updated_at": { "$date": "2026-09-01T14:00:00Z" }
}
```

---

## 2.3 Diagrama de despliegue (UML)

### Notación UML 2.5 (PlantUML — fuente en `docs/uml/despliegue/despliegue.puml`)

```plantuml
@startuml
title Diagrama de despliegue - SemillerosUT
node "Dispositivo del estudiante\n<<device>> Android / iOS / PC" as dev {
  node "Navegador\n<<executionEnvironment>> Chrome / Safari / Edge" {
    artifact "PWA SemillerosUT\n(manifest.webmanifest, sw.js,\nHTML/CSS/JS)" as pwa
    database "Cache Storage\n/ IndexedDB" as cache
  }
}
node "Equipo del personal web\n<<device>> PC" as pc {
  node "Navegador <<executionEnvironment>>" {
    artifact "Panel administrativo\n(Blade + Bootstrap 5 + JS)" as panel
  }
}
node "VPS Ubuntu 24.04 LTS\n<<device>> 2 vCPU / 8 GB" as vps {
  node "Nginx 1.24 <<executionEnvironment>>" as nginx
  node "PHP-FPM 8.3 <<executionEnvironment>>" as fpm {
    artifact "SemillerosUT\nLaravel 12 app\n(Web + API REST /api/v1)" as app
  }
  node "Supervisor <<executionEnvironment>>" {
    artifact "queue:work\n(correos, auditoría)" as worker
  }
  artifact "cron: schedule:run\nbackup mongodump" as cron
}
cloud "MongoDB Atlas\n<<executionEnvironment>> Replica Set (3 nodos)" as atlas {
  database "semillerosut_db\n(colecciones con $jsonSchema)" as db
}
cloud "Google Identity\n(OAuth 2.0)" as google
cloud "Servidor SMTP\n(correo institucional)" as smtp
node "GitHub\n<<device>>" as gh {
  artifact "Repositorio Git\n+ GitHub Actions (CI)" as repo
}

pwa --> nginx : HTTPS 443 / JSON (Bearer token)
panel --> nginx : HTTPS 443 / HTML + JSON
nginx --> fpm : FastCGI (socket unix)
app --> db : mongodb+srv / TLS 27017
worker --> db : TLS
app --> google : OAuth 2.0 HTTPS
worker --> smtp : SMTP TLS 587
repo --> vps : SSH deploy (CI/CD)
cron --> db : mongodump
@enduml
```

### Vista simplificada (Mermaid)

```mermaid
flowchart LR
    subgraph Cliente
        A[📱 PWA<br/>Navegador + Service Worker]
        B[💻 Panel web<br/>Blade + Bootstrap]
    end
    subgraph VPS[VPS Ubuntu 24.04]
        N[Nginx<br/>TLS Let's Encrypt]
        P[PHP-FPM 8.3<br/>Laravel 12<br/>Web + API REST]
        Q[Supervisor<br/>queue:work]
        C[Cron<br/>schedule:run / backups]
    end
    subgraph Nube
        M[(MongoDB Atlas<br/>Replica Set)]
        G[Google OAuth]
        S[SMTP]
    end
    A -- HTTPS/JSON --> N
    B -- HTTPS --> N
    N -- FastCGI --> P
    P -- TLS --> M
    Q -- TLS --> M
    P -- OAuth --> G
    Q -- SMTP TLS --> S
    C -- mongodump --> M
```

---

## 2.4 Prototipo navegable

El prototipo navegable se construyó en **HTML5 + CSS3 + Bootstrap 5.3 + JavaScript**, con plantillas **Blade** de Laravel y datos simulados (*seeders*), a partir de los wireframes de la sección 1.6. URL del prototipo: `<URL_PROTOTIPO>` · Rama/etiqueta: `v0.2.0-T2`.

**Estructura de vistas:**

```
resources/views/
├── layouts/
│   ├── admin.blade.php        # Navbar + sidebar del panel
│   └── pwa.blade.php          # Shell de la PWA (manifest, sw)
├── auth/                      # login, forgot-password, reset-password
├── dashboard.blade.php
├── faculties/ careers/ cats/ areas/ groups/ coordinators/
│   └── index | create | show | edit .blade.php
├── hotbeds/
│   ├── index.blade.php
│   ├── create.blade.php       # pestañas: generales, misión-visión, justificación
│   ├── edit.blade.php         # + objetivos, resultados, integrantes
│   └── show.blade.php
└── pwa/
    ├── login.blade.php  home.blade.php  hotbed.blade.php
    ├── request.blade.php  proposal.blade.php
    └── profile.blade.php  my-requests.blade.php  my-proposals.blade.php
```

**Fragmento del layout del panel (Bootstrap 5):**

```html
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'SemillerosUT')</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ route('dashboard') }}">SemillerosUT</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div id="menu" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        @can('manage-catalogs')
          <li class="nav-item"><a class="nav-link" href="{{ route('faculties.index') }}">Facultades</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('careers.index') }}">Programas</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('cats.index') }}">CAT</a></li>
        @endcan
        <li class="nav-item"><a class="nav-link" href="{{ route('hotbeds.index') }}">Semilleros</a></li>
      </ul>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="btn btn-outline-light btn-sm">Cerrar sesión</button>
      </form>
    </div>
  </div>
</nav>
<main class="container py-4">@yield('content')</main>
</body>
</html>
```

**Navegación validada:** login → dashboard → módulos CRUD → detalle de semillero con pestañas; y en la PWA: login → listado por facultad → detalle → solicitar / proponer → menú.

## 2.5 Control de versiones (Trimestre II)

Etiqueta `v0.2.0-T2`. Evidencias: fichas técnicas y cotizaciones (`docs/costos/`), diagramas de clases y despliegue (`docs/uml/`), prototipo navegable (ramas `feature/prototipo-panel`, `feature/prototipo-pwa` integradas en `develop`). Historial: `<URL_REPOSITORIO_GIT>/commits/v0.2.0-T2`.

---

# TRIMESTRE III

## 3.1 Construcción de la base de datos con Schema Validation

**Base de datos:** `semillerosut_db` · **Motor:** MongoDB 7.0+ (Atlas) · **Conexión Laravel:** `config/database.php` → driver `mongodb`.

```php
// config/database.php
'default' => env('DB_CONNECTION', 'mongodb'),
'connections' => [
    'mongodb' => [
        'driver'   => 'mongodb',
        'dsn'      => env('MONGODB_URI'),        // mongodb+srv://usuario:***@cluster.mongodb.net
        'database' => env('MONGODB_DATABASE', 'semillerosut_db'),
    ],
],
```

Las colecciones y sus validadores se crean con una **migración de Laravel** que invoca `createCollection` con un validador `$jsonSchema`, `validationLevel: strict` y `validationAction: error`, de modo que el motor rechaza cualquier documento que no cumpla la estructura aunque se inserte por fuera de la aplicación.

```php
// database/migrations/2026_08_01_000001_create_collections_with_validation.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected $connection = 'mongodb';

    public function up(): void
    {
        $db = DB::connection('mongodb')->getDatabase();
        foreach ($this->schemas() as $name => $schema) {
            $db->createCollection($name, [
                'validator'        => ['$jsonSchema' => $schema],
                'validationLevel'  => 'strict',
                'validationAction' => 'error',
            ]);
        }
        // Índices
        $db->users->createIndex(['email' => 1], ['unique' => true]);
        $db->faculties->createIndex(['code' => 1], ['unique' => true]);
        $db->careers->createIndex(['code' => 1], ['unique' => true]);
        $db->cats->createIndex(['code' => 1], ['unique' => true]);
        $db->areas->createIndex(['code' => 1], ['unique' => true]);
        $db->groups->createIndex(['code' => 1], ['unique' => true]);
        $db->coordinators->createIndex(['document' => 1], ['unique' => true]);
        $db->hotbeds->createIndex(['code' => 1], ['unique' => true]);
        $db->hotbeds->createIndex(['faculty_id' => 1, 'status' => 1]);
        $db->hotbeds->createIndex(['name' => 'text', 'overall_objective' => 'text']);
        $db->requests->createIndex(
            ['user_id' => 1, 'hotbed_id' => 1],
            ['unique' => true, 'partialFilterExpression' => ['status' => 'pending']]
        );
        $db->logs->createIndex(['collection' => 1, 'document_id' => 1, 'created_at' => -1]);
    }

    public function down(): void
    {
        $db = DB::connection('mongodb')->getDatabase();
        foreach (array_keys($this->schemas()) as $name) {
            $db->dropCollection($name);
        }
    }

    private function schemas(): array
    {
        $status = ['bsonType' => 'string', 'enum' => ['active', 'inactive']];
        $dates  = ['created_at' => ['bsonType' => 'date'], 'updated_at' => ['bsonType' => 'date']];
        $email  = ['bsonType' => 'string', 'pattern' => '^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$', 'maxLength' => 100];

        return [
            'users' => [
                'bsonType' => 'object',
                'required' => ['name', 'email', 'role', 'is_active', 'created_at'],
                'properties' => [
                    'name'      => ['bsonType' => 'string', 'minLength' => 3, 'maxLength' => 100],
                    'email'     => $email,
                    'password'  => ['bsonType' => ['string', 'null'], 'minLength' => 60], // hash bcrypt/argon2
                    'role'      => ['enum' => ['admin', 'staff', 'leader', 'student']],
                    'google_id' => ['bsonType' => ['string', 'null']],
                    'phone'     => ['bsonType' => ['string', 'null']],          // cifrado (ver 3.3)
                    'is_active' => ['bsonType' => 'bool'],
                    'authorization_ref' => ['bsonType' => ['string', 'null']],
                ] + $dates,
            ],
            'faculties' => [
                'bsonType' => 'object',
                'required' => ['code', 'name', 'status'],
                'properties' => [
                    'code' => ['bsonType' => 'string', 'maxLength' => 255],
                    'name' => ['bsonType' => 'string', 'maxLength' => 255],
                    'status' => $status,
                ] + $dates,
            ],
            'careers' => [
                'bsonType' => 'object',
                'required' => ['faculty_id', 'code', 'name', 'type', 'status'],
                'properties' => [
                    'faculty_id' => ['bsonType' => 'objectId'],
                    'code' => ['bsonType' => 'string'],
                    'name' => ['bsonType' => 'string'],
                    'type' => ['enum' => ['Pregrado', 'Posgrado']],
                    'status' => $status,
                ] + $dates,
            ],
            'cats' => [
                'bsonType' => 'object',
                'required' => ['code', 'name', 'address', 'city', 'email', 'phones', 'status'],
                'properties' => [
                    'code' => ['bsonType' => 'string'], 'name' => ['bsonType' => 'string'],
                    'address' => ['bsonType' => 'string'], 'city' => ['bsonType' => 'string'],
                    'email' => $email,
                    'phones' => ['bsonType' => 'array', 'minItems' => 1, 'maxItems' => 3,
                                 'items' => ['bsonType' => 'string', 'maxLength' => 20]],
                    'status' => $status,
                ] + $dates,
            ],
            'areas'  => ['bsonType' => 'object', 'required' => ['code', 'name', 'status'],
                         'properties' => ['code' => ['bsonType' => 'string'], 'name' => ['bsonType' => 'string'], 'status' => $status] + $dates],
            'groups' => ['bsonType' => 'object', 'required' => ['code', 'name', 'status'],
                         'properties' => ['code' => ['bsonType' => 'string'], 'name' => ['bsonType' => 'string'], 'status' => $status] + $dates],
            'coordinators' => [
                'bsonType' => 'object',
                'required' => ['name', 'document', 'email', 'phone', 'status'],
                'properties' => [
                    'name' => ['bsonType' => 'string'], 'document' => ['bsonType' => 'string', 'maxLength' => 15],
                    'email' => $email, 'phone' => ['bsonType' => 'string'], 'status' => $status,
                ] + $dates,
            ],
            'hotbeds' => [
                'bsonType' => 'object',
                'required' => ['code', 'name', 'group_id', 'coordinator_id', 'cat_id', 'faculty_id',
                               'overall_objective', 'approval_ref', 'status'],
                'properties' => [
                    'code' => ['bsonType' => 'string'], 'name' => ['bsonType' => 'string'],
                    'group_id' => ['bsonType' => 'objectId'], 'coordinator_id' => ['bsonType' => 'objectId'],
                    'cat_id' => ['bsonType' => 'objectId'], 'faculty_id' => ['bsonType' => 'objectId'],
                    'leader_id' => ['bsonType' => ['objectId', 'null']],
                    'career_ids' => ['bsonType' => 'array', 'items' => ['bsonType' => 'objectId']],
                    'area_ids'   => ['bsonType' => 'array', 'minItems' => 1, 'items' => ['bsonType' => 'objectId']],
                    'overall_objective' => ['bsonType' => 'string', 'minLength' => 10],
                    'mission' => ['bsonType' => ['string', 'null']],
                    'vision' => ['bsonType' => ['string', 'null']],
                    'justification' => ['bsonType' => ['string', 'null']],
                    'objectives' => ['bsonType' => 'array', 'items' => [
                        'bsonType' => 'object', 'required' => ['_id', 'content', 'status'],
                        'properties' => ['_id' => ['bsonType' => 'objectId'], 'content' => ['bsonType' => 'string'], 'status' => $status],
                    ]],
                    'results' => ['bsonType' => 'array', 'items' => [
                        'bsonType' => 'object', 'required' => ['_id', 'content', 'status'],
                        'properties' => ['_id' => ['bsonType' => 'objectId'], 'content' => ['bsonType' => 'string'], 'status' => $status],
                    ]],
                    'approval_ref' => ['bsonType' => 'string'],
                    'status' => $status,
                ] + $dates,
            ],
            'members' => [
                'bsonType' => 'object',
                'required' => ['hotbed_id', 'career_id', 'name', 'code', 'level', 'email', 'status'],
                'properties' => [
                    'hotbed_id' => ['bsonType' => 'objectId'], 'career_id' => ['bsonType' => 'objectId'],
                    'user_id' => ['bsonType' => ['objectId', 'null']],
                    'name' => ['bsonType' => 'string'], 'code' => ['bsonType' => 'string'],
                    'level' => ['enum' => ['PR', 'PG']], 'email' => $email,
                    'address' => ['bsonType' => ['string', 'null']], 'phone' => ['bsonType' => ['string', 'null']],
                    'status' => $status,
                ] + $dates,
            ],
            'requests' => [
                'bsonType' => 'object',
                'required' => ['hotbed_id', 'career_id', 'user_id', 'name', 'email', 'phone', 'message', 'status'],
                'properties' => [
                    'hotbed_id' => ['bsonType' => 'objectId'], 'career_id' => ['bsonType' => 'objectId'],
                    'user_id' => ['bsonType' => 'objectId'], 'name' => ['bsonType' => 'string', 'maxLength' => 100],
                    'email' => $email, 'phone' => ['bsonType' => 'string'],
                    'message' => ['bsonType' => 'string', 'minLength' => 10, 'maxLength' => 1000],
                    'status' => ['enum' => ['pending', 'approved', 'rejected']],
                    'response' => ['bsonType' => ['string', 'null']],
                ] + $dates,
            ],
            'proposals' => [
                'bsonType' => 'object',
                'required' => ['career_id', 'user_id', 'area_ids', 'name', 'email', 'observation', 'status'],
                'properties' => [
                    'career_id' => ['bsonType' => 'objectId'], 'user_id' => ['bsonType' => 'objectId'],
                    'area_ids' => ['bsonType' => 'array', 'minItems' => 1, 'items' => ['bsonType' => 'objectId']],
                    'name' => ['bsonType' => 'string'], 'email' => $email,
                    'phone' => ['bsonType' => ['string', 'null']],
                    'observation' => ['bsonType' => 'string', 'minLength' => 20, 'maxLength' => 2000],
                    'status' => ['enum' => ['received', 'viable', 'archived']],
                ] + $dates,
            ],
            'logs' => [
                'bsonType' => 'object',
                'required' => ['collection', 'document_id', 'action', 'created_at'],
                'properties' => [
                    'user_id' => ['bsonType' => ['objectId', 'null']],
                    'collection' => ['bsonType' => 'string', 'maxLength' => 30],
                    'document_id' => ['bsonType' => 'objectId'],
                    'action' => ['enum' => ['created', 'updated', 'status_changed', 'login']],
                    'before' => ['bsonType' => ['object', 'null']],
                    'after' => ['bsonType' => ['object', 'null']],
                    'ip' => ['bsonType' => ['string', 'null']],
                    'created_at' => ['bsonType' => 'date'],
                ],
            ],
        ];
    }
};
```

**Prueba de la validación (mongosh):**

```javascript
use semillerosut_db
db.requests.insertOne({ name: "Sin datos", status: "pendiente" })
// MongoServerError: Document failed validation  → faltan campos requeridos y 'status' fuera del enum
db.getCollectionInfos({ name: "hotbeds" })[0].options.validator   // muestra el $jsonSchema aplicado
```

Ejecución: `php artisan migrate` y `php artisan db:seed` (facultades, programas, CAT Kennedy, áreas, usuario administrador inicial).

---

## 3.2 CRUD Operations y Aggregations

### Modelo Eloquent sobre MongoDB

```php
// app/Models/Hotbed.php
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

class Hotbed extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'hotbeds';
    protected $fillable = ['code','name','group_id','coordinator_id','cat_id','faculty_id','leader_id',
        'career_ids','area_ids','overall_objective','mission','vision','justification',
        'objectives','results','approval_ref','status'];

    public function faculty(): BelongsTo     { return $this->belongsTo(Faculty::class); }
    public function cat(): BelongsTo         { return $this->belongsTo(Cat::class); }
    public function coordinator(): BelongsTo { return $this->belongsTo(Coordinator::class); }
    public function group(): BelongsTo       { return $this->belongsTo(Group::class); }
    public function members()  { return $this->hasMany(Member::class); }
    public function requests() { return $this->hasMany(MembershipRequest::class); }
    public function scopeActive($q) { return $q->where('status', 'active'); }
}
```

### Operaciones CRUD

| Operación | Laravel (Eloquent / Query Builder) | Equivalente MongoDB |
|---|---|---|
| **Create** | `Hotbed::create($data)` | `db.hotbeds.insertOne({...})` |
| **Create** (subdocumento) | `Hotbed::where('_id',$id)->push('objectives', ['_id'=>new ObjectId(), 'content'=>$c, 'status'=>'active'])` | `db.hotbeds.updateOne({_id}, {$push:{objectives:{...}}})` |
| **Read** (listado) | `Hotbed::active()->where('faculty_id',$f)->orderBy('name')->paginate(15)` | `db.hotbeds.find({status:"active",faculty_id:f}).sort({name:1}).limit(15)` |
| **Read** (detalle) | `Hotbed::with(['faculty','cat','coordinator'])->findOrFail($id)` | `db.hotbeds.findOne({_id})` |
| **Read** (búsqueda) | `Hotbed::whereRaw(['$text'=>['$search'=>$q]])->get()` | `db.hotbeds.find({$text:{$search:q}})` |
| **Update** | `$hotbed->update($validated)` | `db.hotbeds.updateOne({_id},{$set:{...}})` |
| **Update** (subdocumento) | `DB::connection('mongodb')->table('hotbeds')->where('_id',$id)->where('objectives._id',$oid)->update(['objectives.$.content'=>$c])` | `updateOne({_id,"objectives._id":oid},{$set:{"objectives.$.content":c}})` |
| **Delete lógico** | `$hotbed->update(['status'=>'inactive'])` | `updateOne({_id},{$set:{status:"inactive"}})` |

> Por regla de negocio (RF01–RF13) no existe borrado físico; la "D" del CRUD se implementa como cambio de estado.

### Aggregations

Se encapsulan en `app/Services/ReportService.php` y se exponen en `/api/v1/reports/*` para el dashboard.

**A1. Semilleros activos por facultad (listado agrupado de la PWA y gráfico del dashboard)**

```php
public function hotbedsByFaculty(): array
{
    return Hotbed::raw(fn ($c) => $c->aggregate([
        ['$match'  => ['status' => 'active']],
        ['$lookup' => ['from' => 'faculties', 'localField' => 'faculty_id', 'foreignField' => '_id', 'as' => 'faculty']],
        ['$unwind' => '$faculty'],
        ['$group'  => [
            '_id'      => '$faculty._id',
            'faculty'  => ['$first' => '$faculty.name'],
            'total'    => ['$sum' => 1],
            'hotbeds'  => ['$push' => ['id' => '$_id', 'name' => '$name', 'code' => '$code']],
        ]],
        ['$sort' => ['faculty' => 1]],
    ]))->toArray();
}
```

**A2. Solicitudes por semillero y estado**

```javascript
db.requests.aggregate([
  { $group: { _id: { hotbed: "$hotbed_id", status: "$status" }, total: { $sum: 1 } } },
  { $group: { _id: "$_id.hotbed",
              by_status: { $push: { k: "$_id.status", v: "$total" } },
              total: { $sum: "$total" } } },
  { $lookup: { from: "hotbeds", localField: "_id", foreignField: "_id", as: "h" } },
  { $project: { _id: 0, hotbed: { $first: "$h.name" }, total: 1, by_status: { $arrayToObject: "$by_status" } } },
  { $sort: { total: -1 } }
])
```

**A3. Propuestas por área de conocimiento (identifica temas para nuevos semilleros)**

```javascript
db.proposals.aggregate([
  { $match: { status: { $ne: "archived" } } },
  { $unwind: "$area_ids" },
  { $group: { _id: "$area_ids", proposals: { $sum: 1 } } },
  { $lookup: { from: "areas", localField: "_id", foreignField: "_id", as: "area" } },
  { $project: { _id: 0, area: { $first: "$area.name" }, proposals: 1 } },
  { $sort: { proposals: -1 } }, { $limit: 10 }
])
```

**A4. Integrantes por programa y nivel**

```javascript
db.members.aggregate([
  { $match: { status: "active" } },
  { $group: { _id: { career: "$career_id", level: "$level" }, total: { $sum: 1 } } },
  { $lookup: { from: "careers", localField: "_id.career", foreignField: "_id", as: "c" } },
  { $project: { _id: 0, career: { $first: "$c.name" }, level: "$_id.level", total: 1 } }
])
```

**A5. Actividad de auditoría por usuario en los últimos 30 días**

```javascript
db.logs.aggregate([
  { $match: { created_at: { $gte: new Date(Date.now() - 30*24*3600*1000) } } },
  { $group: { _id: { user: "$user_id", action: "$action" }, total: { $sum: 1 } } },
  { $sort: { total: -1 } }
])
```

---

## 3.3 Seguridad de la base de datos

### Encriptación de contraseñas

- Las contraseñas se almacenan **solo como hash** con **bcrypt** (factor de costo 12, `BCRYPT_ROUNDS=12`) mediante el *cast* `hashed` de Laravel; opcionalmente Argon2id (`HASH_DRIVER=argon2id`). Nunca se guardan ni registran en texto plano.
- El validador `$jsonSchema` exige `minLength: 60` en `password`, lo que impide almacenar una contraseña sin hash.
- Los estudiantes no tienen contraseña: se autentican con Google OAuth y su campo `password` es `null`.

```php
// app/Models/User.php
use MongoDB\Laravel\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $connection = 'mongodb';
    protected $fillable = ['name','email','password','role','google_id','phone','is_active','authorization_ref'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',      // bcrypt automático
            'phone'     => 'encrypted',   // AES-256-CBC con APP_KEY (dato personal)
            'is_active' => 'boolean',
        ];
    }
}
```

Verificación en el login: `Auth::attempt(['email'=>$e, 'password'=>$p, 'is_active'=>true])`, que usa `Hash::check` internamente (comparación en tiempo constante).

### Prácticas de seguridad sobre los datos

| Práctica | Implementación |
|---|---|
| Cifrado de datos personales en reposo | Cast `encrypted` (AES-256 con `APP_KEY`) para teléfonos; cifrado en reposo nativo de Atlas. |
| Cifrado en tránsito | TLS obligatorio entre aplicación y Atlas (`mongodb+srv`); HTTPS con HSTS en Nginx. |
| Mínimo privilegio | Usuario de BD `app_semilleros` con rol `readWrite` solo sobre `semillerosut_db`; rol personalizado que niega `update`/`remove` sobre `logs`. Usuario separado `backup_ro` con rol `backup`. |
| Restricción de red | *IP Access List* de Atlas limitada a la IP del VPS. |
| Integridad | `$jsonSchema` en modo `strict/error`, índices únicos y validación con *Form Requests* en Laravel. |
| Prevención de inyección NoSQL | Los datos de entrada se validan y tipan (`string`, `ObjectId`) antes de construir consultas; se rechazan claves que empiezan por `$` en los *payloads*. |
| Secretos | Credenciales solo en `.env` (fuera del repositorio, `.env.example` sin valores); `APP_KEY` rotada por ambiente. |
| Auditoría | Observer global que registra `created`, `updated`, `status_changed` y `login` (RF14). |
| Protección de datos personales | Aviso de privacidad y autorización de tratamiento en el primer ingreso a la PWA (Ley 1581 de 2012); campo `privacy_accepted_at` en `users`. |
| Respaldos | Ver plan de respaldo en [5.3](#53-planes-de-instalación-respaldo-migración-y-capacitación). |

---

## 3.4 FrontEnd con framework (avance 50 %)

Frontend en **Blade + Bootstrap 5.3 + JavaScript**, conectado a MongoDB a través de los controladores Laravel. Avance del corte (etiqueta `v0.3.0-T3`):

| Módulo | Requisito | Estado T3 |
|---|---|---|
| Autenticación web (login, logout, recuperar contraseña) | RF01 | ✔ Completo |
| Gestión de usuarios | RF01 | ✔ Completo |
| Facultades | RF02 | ✔ Completo |
| Programas | RF03 | ✔ Completo |
| CAT | RF04 | ✔ Completo |
| Áreas | RF05 | ✔ Completo |
| Grupos | RF06 | ✔ Completo |
| Coordinadores | RF07 | ✔ Completo |
| Semilleros (datos generales, misión, visión, justificación) | RF13 | ◐ Parcial |
| Objetivos, resultados | RF08, RF09 | ✘ Pendiente |
| Integrantes | RF12 | ✘ Pendiente |
| Solicitudes, propuestas | RF10, RF11 | ✘ Pendiente |
| Auditoría | RF14 | ◐ Parcial (registro sí, consulta no) |
| PWA estudiante | RNF02 | ✘ Pendiente |
| **Avance** | 14 RF | **7 completos + 2 parciales ≈ 57 %** |

Ejemplo de controlador conectado a la BD:

```php
// app/Http/Controllers/FacultyController.php
class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::orderBy('name')->paginate(15);
        return view('faculties.index', compact('faculties'));
    }

    public function store(StoreFacultyRequest $request)
    {
        Faculty::create($request->validated() + ['status' => 'active']);
        return redirect()->route('faculties.index')->with('ok', 'Facultad creada');
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        $faculty->update($request->validated());
        return redirect()->route('faculties.show', $faculty)->with('ok', 'Facultad actualizada');
    }
}
```

---

## 3.5 API REST con seguridad

**Base:** `https://<dominio>/api/v1` · **Formato:** JSON · **Autenticación:** Laravel Sanctum (tokens *Bearer* para la PWA; cookie de sesión SPA para el panel).

### Endpoints

| Método | Ruta | Descripción | Rol |
|---|---|---|---|
| POST | `/auth/login` | Login web, devuelve token | Público (rate limit) |
| GET | `/auth/google/redirect` · `/auth/google/callback` | OAuth Google para estudiantes | Público |
| POST | `/auth/logout` | Revoca el token actual | Autenticado |
| GET | `/me` · PATCH `/me` | Perfil / actualizar teléfono | Autenticado |
| GET | `/faculties` · `/careers` · `/cats` · `/areas` · `/groups` · `/coordinators` | Listados de catálogos | Autenticado |
| POST/PUT/PATCH | `/faculties`, `/faculties/{id}` (ídem demás catálogos) | Crear/editar/cambiar estado | admin |
| GET | `/hotbeds?faculty=&q=` | Semilleros activos (filtro y búsqueda) | Autenticado |
| GET | `/hotbeds/{id}` | Detalle con misión, visión, objetivos | Autenticado |
| POST/PUT | `/hotbeds`, `/hotbeds/{id}` | Crear / editar semillero | leader |
| POST/PUT | `/hotbeds/{id}/objectives[/{oid}]`, `/hotbeds/{id}/results[/{rid}]` | Objetivos / resultados | leader |
| GET/POST/PUT | `/hotbeds/{id}/members[/{mid}]` | Integrantes | leader |
| POST | `/requests` | Solicitar ser miembro | student |
| GET | `/requests/mine` | Mis solicitudes | student |
| GET | `/requests?hotbed=&status=` | Solicitudes recibidas | leader, staff |
| PATCH | `/requests/{id}/approve` · `/requests/{id}/reject` | Resolver solicitud | leader |
| POST | `/proposals` · GET `/proposals/mine` | Propuestas | student |
| GET · PATCH | `/proposals` · `/proposals/{id}/status` | Evaluar propuestas | leader, staff |
| GET | `/reports/hotbeds-by-faculty` · `/reports/requests` · `/reports/proposals-by-area` | Agregaciones | admin, staff |
| GET | `/logs` | Auditoría | admin |

### Rutas y capas de seguridad

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect']);
    Route::get('auth/google/callback', [GoogleController::class, 'callback']);

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [ProfileController::class, 'show']);
        Route::patch('me', [ProfileController::class, 'update']);

        Route::apiResource('hotbeds', HotbedApiController::class)->except('destroy');
        Route::post('requests', [RequestApiController::class, 'store'])->middleware('role:student');
        Route::get('requests/mine', [RequestApiController::class, 'mine'])->middleware('role:student');
        Route::patch('requests/{request}/approve', [RequestApiController::class, 'approve'])->middleware('role:leader');
        // … demás rutas
    });
});
```

| Capa | Medida |
|---|---|
| Autenticación | Tokens Sanctum con habilidades (`abilities`) por rol y vencimiento (`SANCTUM_EXPIRATION=480` min). Tokens guardados como hash SHA-256 en la BD. |
| Autorización | Middleware `role:*` + *Policies* (`HotbedPolicy`: un líder solo edita sus semilleros). |
| Validación | *Form Requests* por endpoint; respuestas 422 con errores por campo. |
| Limitación de peticiones | `throttle:login` 5/min por IP+email; `throttle:api` 60/min por usuario. |
| Transporte | Solo HTTPS; HSTS; cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, CSP. |
| CORS | `config/cors.php` limitado al dominio propio de la PWA. |
| Dominio institucional | `GoogleController` rechaza correos fuera de `@<dominio_institucional>` (parámetro `hd` + verificación en servidor). |
| Respuestas | API Resources que ocultan campos sensibles (`password`, tokens); errores sin trazas en producción (`APP_DEBUG=false`). |

#### Implementación real (verificado 2026-09-28)

> El bloque anterior describe el diseño con MongoDB y rutas `/api/v1`. Por decisión del proyecto
> (2026-09-28), **lo funcional lo manda la especificación y el stack es el real**: Laravel 12 +
> MySQL, SPA en JavaScript vanilla y hosting compartido Hostinger (PHP 8.2).

| Capa | Implementación actual |
|---|---|
| Rutas | Prefijo `/api` (sin versión). Públicas: `login`, `forgot-password`, `reset-password`. El resto va en el grupo `auth:sanctum` + `active`. |
| Autenticación (CU01) | Token Sanctum que vence a las **8 h**, o a los **30 días** con «Recordarme» (A2). Cada login exitoso se registra en `audits` como `LOGIN` (CU29, RN07). |
| Limitación de intentos (RN14) | `RateLimiter` en `AuthController::login`: 5 fallos por correo+IP en 60 s → **HTTP 429** con `retry_after`. El store de caché es `database`. |
| Usuario inactivo | En el login: 403 «Su usuario está inactivo…» (CU01 E3). En cada petición, el middleware `active` (`EnsureUserIsActive`) responde 401 y revoca el token. Al inactivar se revocan todos sus tokens. |
| Autorización | Middleware `role:` por ruta, más reglas en el servidor: el estudiante no puede fijar `user_id` ni `status`. |
| Salida en el frontend | `core/escape.js` (`escapeHtml`, `safeUrl`, `safeImageSrc`) en todo dato que se inserta como HTML. |
| Pruebas | PHPUnit: `tests/Feature/UseCases/CU01LoginTest.php` (un test por paso/alterno/excepción) y `tests/Feature/Security/SecurityRegressionTest.php`. |
| Pendiente | Login de estudiantes con Google (CU02, RN04) y CU01 E5 (estudiante que intenta entrar al panel web). |

Respuesta estándar:

```json
{
  "data": { "id": "66f1…", "name": "Semillero INITIUM", "faculty": "Facultad de Tecnologías" },
  "meta": { "api_version": "1.0" }
}
```

## 3.6 Control de versiones (Trimestre III)

Etiqueta `v0.3.0-T3`. Evidencias: migraciones y validadores (`database/migrations/`), modelos (`app/Models/`), servicios de agregación, controladores web, API v1 y pruebas iniciales. Ramas integradas: `feature/mongodb-schema`, `feature/crud-catalogos`, `feature/api-auth`. Historial: `<URL_REPOSITORIO_GIT>/compare/v0.2.0-T2...v0.3.0-T3`.

---

# TRIMESTRE IV

## 4.1 Frontend web consumiendo la API REST (avance 80 %)

Las vistas del panel que requieren interacción (listados con filtros, pestañas de objetivos/resultados, resolución de solicitudes, dashboard) consumen la API `/api/v1` con **JavaScript nativo (`fetch`)** y autenticación de sesión SPA de Sanctum (cookie `XSRF-TOKEN`).

```javascript
// resources/js/api.js — cliente común para panel y PWA
const BASE = '/api/v1';

export async function api(path, { method = 'GET', body, token } = {}) {
  const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
  const xsrf = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
  if (xsrf) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf.split('=')[1]);
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(BASE + path, {
    method, headers, credentials: 'same-origin',
    body: body ? JSON.stringify(body) : undefined,
  });
  if (res.status === 401) { window.location.href = '/login'; return; }
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data.message || 'Error'), { status: res.status, errors: data.errors });
  return data;
}
```

```javascript
// resources/js/admin/requests.js — resolver solicitudes sin recargar la página
import { api } from '../api.js';

document.querySelectorAll('[data-approve]').forEach(btn => {
  btn.addEventListener('click', async () => {
    btn.disabled = true;
    try {
      await api(`/requests/${btn.dataset.approve}/approve`, { method: 'PATCH' });
      btn.closest('tr').querySelector('.badge').textContent = 'Aprobada';
    } catch (e) {
      alert(e.message);
      btn.disabled = false;
    }
  });
});
```

| Módulo | Consume API | Estado T4 |
|---|---|---|
| Autenticación, usuarios, catálogos (RF01–RF07) | Listados y filtros | ✔ |
| Semilleros completo (RF13) | Sí | ✔ |
| Objetivos y resultados (RF08, RF09) | Sí (pestañas dinámicas) | ✔ |
| Integrantes (RF12) | Sí | ✔ |
| Solicitudes (RF10) | Sí (aprobar/rechazar) | ✔ |
| Propuestas (RF11) | Sí | ◐ Falta evaluación de estado |
| Dashboard de reportes (agregaciones) | Sí | ◐ Faltan 2 gráficos |
| Consulta de auditoría (RF14) | Sí | ✘ Pendiente |
| **Avance** | | **11 completos + 2 parciales de 14 ≈ 86 %** |

---

## 4.2 Consumo de la API desde la aplicación móvil (PWA)

La aplicación móvil es una **Progressive Web App instalable** servida en `/app`. Consume exclusivamente la API REST con token *Bearer* obtenido tras el login con Google.

### Requisitos de instalabilidad cumplidos

| Criterio | Implementación |
|---|---|
| HTTPS | Certificado Let's Encrypt en Nginx |
| Web App Manifest | `public/manifest.webmanifest` con nombre, íconos 192/512 px, `display: standalone`, `start_url` |
| Service Worker con manejador `fetch` | `public/sw.js` |
| Íconos *maskable* | `public/icons/icon-512-maskable.png` |
| Evento de instalación | Botón "Instalar app" con `beforeinstallprompt` (Android/escritorio); instrucción "Agregar a inicio" en iOS |

```json
// public/manifest.webmanifest
{
  "name": "SemillerosUT - Universidad del Tolima",
  "short_name": "SemillerosUT",
  "start_url": "/app?source=pwa",
  "scope": "/app/",
  "display": "standalone",
  "orientation": "portrait",
  "background_color": "#ffffff",
  "theme_color": "#8a1538",
  "lang": "es-CO",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

```javascript
// public/sw.js — estrategia: cache-first para el shell, network-first para la API
const SHELL = 'shell-v1';
const API = 'api-v1';
const SHELL_FILES = ['/app', '/app/offline', '/build/app.css', '/build/pwa.js', '/icons/icon-192.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(SHELL).then(c => c.addAll(SHELL_FILES)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys =>
    Promise.all(keys.filter(k => ![SHELL, API].includes(k)).map(k => caches.delete(k)))));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;                       // POST de solicitudes siempre a red
  if (url.pathname.startsWith('/api/v1/hotbeds') || url.pathname.startsWith('/api/v1/faculties')) {
    e.respondWith(fetch(e.request).then(res => {                // network-first
      const copy = res.clone();
      caches.open(API).then(c => c.put(e.request, copy));
      return res;
    }).catch(() => caches.match(e.request)));
    return;
  }
  e.respondWith(caches.match(e.request).then(r => r || fetch(e.request))
    .catch(() => caches.match('/app/offline')));
});
```

```javascript
// resources/js/pwa/home.js — listado de semilleros por facultad
import { api } from '../api.js';
const token = sessionStorage.getItem('sut_token');   // token Sanctum de la sesión actual

async function loadHotbeds() {
  const { data } = await api('/reports/hotbeds-by-faculty', { token });
  document.querySelector('#list').innerHTML = data.map(f => `
    <details class="card mb-2"><summary class="card-header">${f.faculty} (${f.total})</summary>
      <ul class="list-group list-group-flush">
        ${f.hotbeds.map(h => `<li class="list-group-item"><a href="/app/semilleros/${h.id}">${h.name}</a></li>`).join('')}
      </ul></details>`).join('');
}

async function sendRequest(hotbedId, form) {
  return api('/requests', { method: 'POST', token, body: { hotbed_id: hotbedId, ...Object.fromEntries(new FormData(form)) } });
}

if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js', { scope: '/app/' });
loadHotbeds();
```

> El token se guarda en `sessionStorage` (se borra al cerrar la app) y nunca en código; la plantilla renderiza los textos con escape para evitar XSS. El endpoint de agregación usado por la PWA filtra solo semilleros activos.

**Pantallas de la PWA que consumen la API:** login (OAuth), listado por facultad, detalle, solicitar membresía, nueva propuesta, perfil, mis solicitudes, mis propuestas.

---

## 4.3 Documentación de la API con Swagger / OpenAPI

Herramienta: **L5-Swagger** (swagger-php con atributos PHP 8). Interfaz interactiva en `https://<dominio>/api/documentation` (protegida con autenticación básica en producción). Especificación generada: `storage/api-docs/api-docs.json` y copia versionada en `docs/api/openapi.yaml`.

```php
// app/Http/Controllers/Api/RequestApiController.php
use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'SemillerosUT API', description: 'API REST de semilleros IDEAD - UT')]
#[OA\SecurityScheme(securityScheme: 'sanctum', type: 'http', scheme: 'bearer', bearerFormat: 'Token')]
class RequestApiController extends Controller
{
    #[OA\Post(
        path: '/api/v1/requests', summary: 'Solicitar ser miembro de un semillero', tags: ['Solicitudes'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['hotbed_id', 'career_id', 'phone', 'message'],
            properties: [
                new OA\Property(property: 'hotbed_id', type: 'string', example: '66f1a2b3c4d5e6f7a8b9c0d1'),
                new OA\Property(property: 'career_id', type: 'string', example: '66f1a2b3c4d5e6f7a8b9c006'),
                new OA\Property(property: 'phone', type: 'string', example: '3001234567'),
                new OA\Property(property: 'message', type: 'string', example: 'Me interesa la línea de bases de datos'),
            ])),
        responses: [
            new OA\Response(response: 201, description: 'Solicitud creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 409, description: 'Ya existe una solicitud pendiente'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreMembershipRequest $request) { /* … */ }
}
```

Extracto de `docs/api/openapi.yaml`:

```yaml
openapi: 3.0.3
info: { title: SemillerosUT API, version: 1.0.0 }
servers: [{ url: https://<dominio>/api/v1 }]
components:
  securitySchemes:
    sanctum: { type: http, scheme: bearer }
  schemas:
    Hotbed:
      type: object
      properties:
        id: { type: string }
        code: { type: string }
        name: { type: string }
        faculty: { type: string }
        mission: { type: string, nullable: true }
        vision: { type: string, nullable: true }
        objectives: { type: array, items: { type: object, properties: { id: {type: string}, content: {type: string} } } }
paths:
  /hotbeds:
    get:
      tags: [Semilleros]
      security: [{ sanctum: [] }]
      parameters:
        - { in: query, name: faculty, schema: { type: string } }
        - { in: query, name: q, schema: { type: string } }
      responses:
        '200': { description: Listado paginado, content: { application/json: { schema: { type: object, properties: { data: { type: array, items: { $ref: '#/components/schemas/Hotbed' } } } } } } }
        '401': { description: No autenticado }
```

Generación: `php artisan l5-swagger:generate` (se ejecuta también en el pipeline de CI).

---

## 4.4 Modelo de calidad (ISO/IEC 25010)

El documento original tomó como referencia la ISO/IEC 9126. Para la implementación se adopta su sucesora, **ISO/IEC 25010 (familia SQuaRE)**, manteniendo el mapeo con las características de 9126. Cada subcaracterística tiene una métrica, una meta y el resultado medido en el corte T5.

| Característica ISO 25010 | Subcaracterística | Métrica | Meta | Resultado `<medir>` |
|---|---|---|---|---|
| Adecuación funcional (≈ Funcionalidad 9126) | Completitud funcional | HU aceptadas / HU totales | 100 % | `<__ %>` |
| | Corrección | Casos de prueba funcionales aprobados | ≥ 95 % | `<__ %>` |
| Eficiencia de desempeño | Comportamiento temporal | p95 de tiempo de respuesta de `/hotbeds` | < 500 ms | `<__ ms>` |
| | Utilización de recursos | Peso inicial de la PWA (transferido) | < 500 KB | `<__ KB>` |
| Compatibilidad (≈ Interoperabilidad) | Interoperabilidad | Clientes que consumen la API sin adaptaciones (panel, PWA, Swagger) | 3 | `<__>` |
| Usabilidad | Facilidad de aprendizaje | Usuarios que completan "solicitar membresía" sin ayuda (prueba con 5 estudiantes) | ≥ 80 % | `<__ %>` |
| | Estética / accesibilidad | Puntaje Lighthouse Accesibilidad | ≥ 90 | `<__>` |
| Fiabilidad (≈ Confiabilidad) | Madurez | Defectos críticos abiertos al cierre | 0 | `<__>` |
| | Recuperabilidad | Tiempo de restauración desde respaldo (RTO) | < 1 h | `<__>` |
| | Disponibilidad | Disponibilidad mensual monitoreada | ≥ 99 % | `<__ %>` |
| Seguridad | Confidencialidad | Contraseñas en texto plano en BD | 0 | `<__>` |
| | Autenticidad / no repudio | Operaciones de escritura con registro de auditoría | 100 % | `<__ %>` |
| | Integridad | Documentos inválidos aceptados por `$jsonSchema` | 0 | `<__>` |
| Mantenibilidad | Modularidad / analizabilidad | Cumplimiento PSR-12 (Laravel Pint) | 0 errores | `<__>` |
| | Capacidad de prueba | Cobertura de pruebas automatizadas | ≥ 70 % | `<__ %>` |
| Portabilidad | Adaptabilidad | Plataformas donde se instala la PWA (Android, iOS, Windows) | 3 | `<__>` |
| | Instalabilidad | Tiempo de instalación siguiendo el manual | < 30 min | `<__>` |

**Herramientas de medición:** PHPUnit/Pest con cobertura (Xdebug/PCOV), Laravel Pint, Lighthouse (Chrome DevTools), monitoreo de disponibilidad `<UptimeRobot / similar>`, prueba de usabilidad con formato SUS.

---

## 4.5 Metodología ágil en el proyecto móvil

Se aplica **Scrum** con sprints de dos semanas.

### Roles

| Rol Scrum | Responsable |
|---|---|
| Product Owner | Director del proyecto / representante de la coordinación de investigación IDEAD |
| Scrum Master | `<nombre>` |
| Equipo de desarrollo | José Bohórquez `<y demás integrantes>` |
| Interesados (*stakeholders*) | Estudiantes, líderes de semillero, coordinación CAT Kennedy |

### Artefactos

- **Product Backlog:** historias HU01–HU23 y HU-NF01–HU-NF08 priorizadas (sección 1.4), en `<Trello / Jira / GitHub Projects>`: `<URL_TABLERO>`.
- **Sprint Backlog:** subconjunto de HU por sprint, dividido en tareas técnicas.
- **Incremento:** versión desplegada en el ambiente de pruebas al final de cada sprint.
- **Definición de Terminado (DoD):** código revisado por PR, pruebas en verde, documentación Swagger actualizada, criterio de aceptación verificado por el PO.

### Plan de sprints del módulo móvil (PWA)

| Sprint | Objetivo | Historias | Pts |
|---|---|---|---|
| S1 | Shell PWA instalable y autenticación con Google | HU04, HU-NF02 | 10 |
| S2 | Consulta de semilleros por facultad y detalle offline | HU15 | 8 |
| S3 | Solicitud de membresía y "Mis solicitudes" | HU16, HU20 (parcial) | 8 |
| S4 | Propuestas, "Mis propuestas" y perfil | HU18, HU20, HU21 | 10 |
| S5 | Calidad: accesibilidad, rendimiento, pruebas en dispositivos | HU-NF07, HU-NF08 | 8 |

**Ceremonias:** Sprint Planning (inicio), Daily de 15 min (virtual), Sprint Review con el PO (demo en celular), Retrospectiva (qué mantener / qué mejorar). Velocidad promedio medida: `<__ pts/sprint>`. Gráficos *burndown* en `docs/scrum/`.

## 4.6 Control de versiones (Trimestre IV)

Etiqueta `v0.4.0-T4`. Ramas: `feature/pwa-shell`, `feature/pwa-google-auth`, `feature/pwa-requests`, `feature/swagger`. CI con GitHub Actions: `composer install` → `pint --test` → `php artisan test` → `l5-swagger:generate`. Historial: `<URL_REPOSITORIO_GIT>/compare/v0.3.0-T3...v0.4.0-T4`.

---

# TRIMESTRE V

## 5.1 Codificación al 100 %

| RF | Módulo | Web | API | PWA | Pruebas | Estado |
|---|---|---|---|---|---|---|
| RF01 | Usuarios y autenticación | ✔ | ✔ | ✔ | ✔ | Terminado |
| RF02 | Facultades | ✔ | ✔ | ✔ (lectura) | ✔ | Terminado |
| RF03 | Programas | ✔ | ✔ | ✔ (lectura) | ✔ | Terminado |
| RF04 | CAT | ✔ | ✔ | — | ✔ | Terminado |
| RF05 | Áreas | ✔ | ✔ | ✔ (lectura) | ✔ | Terminado |
| RF06 | Grupos | ✔ | ✔ | — | ✔ | Terminado |
| RF07 | Coordinadores | ✔ | ✔ | ✔ (lectura) | ✔ | Terminado |
| RF08 | Objetivos | ✔ | ✔ | ✔ (lectura) | ✔ | Terminado |
| RF09 | Resultados | ✔ | ✔ | — | ✔ | Terminado |
| RF10 | Solicitudes | ✔ | ✔ | ✔ | ✔ | Terminado |
| RF11 | Propuestas | ✔ | ✔ | ✔ | ✔ | Terminado |
| RF12 | Integrantes | ✔ | ✔ | — | ✔ | Terminado |
| RF13 | Semilleros | ✔ | ✔ | ✔ | ✔ | Terminado |
| RF14 | Auditoría | ✔ | ✔ | — | ✔ | Terminado |
| | **Avance** | | | | | **14/14 = 100 %** |

---

## 5.2 Pruebas de software

### Técnicas aplicadas

| Nivel / tipo | Técnica | Herramienta |
|---|---|---|
| Unitarias | Caja blanca: cobertura de sentencias en servicios y modelos | PHPUnit / Pest |
| Integración / API | Caja negra: partición de equivalencia y valores límite sobre los endpoints | Pest (HTTP tests), Postman/Newman |
| Funcionales (sistema) | Casos de prueba derivados de los casos de uso (flujo básico y alternos) | Manual + Laravel Dusk `<opcional>` |
| Seguridad | Pruebas de acceso no autorizado, fuerza bruta, validación de dominio, OWASP ZAP baseline | Pest, OWASP ZAP |
| Rendimiento | Carga de 50 usuarios concurrentes sobre `/hotbeds` | k6 / Apache JMeter |
| Usabilidad | Prueba con 5 estudiantes + cuestionario SUS | Formulario |
| Aceptación | Validación de criterios de aceptación con el PO | Acta de sprint review |

### Ejemplo de prueba automatizada (Pest)

```php
// tests/Feature/MembershipRequestTest.php
use App\Models\{User, Hotbed, Career, MembershipRequest};
use Illuminate\Support\Facades\Hash;

it('permite a un estudiante solicitar ser miembro', function () {
    $student = User::factory()->student()->create();
    $hotbed  = Hotbed::factory()->create();
    $career  = Career::factory()->create();

    $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/requests', [
            'hotbed_id' => (string) $hotbed->_id,
            'career_id' => (string) $career->_id,
            'phone'     => '3001234567',
            'message'   => 'Me interesa participar en el semillero',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
});

it('rechaza una segunda solicitud pendiente al mismo semillero', function () {
    $student = User::factory()->student()->create();
    $req = MembershipRequest::factory()->for($student)->pending()->create();

    $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/requests', $req->only(['hotbed_id','career_id','phone','message']))
        ->assertStatus(409);
});

it('impide que un estudiante apruebe solicitudes', function () {
    $student = User::factory()->student()->create();
    $req = MembershipRequest::factory()->pending()->create();

    $this->actingAs($student, 'sanctum')
        ->patchJson("/api/v1/requests/{$req->_id}/approve")
        ->assertForbidden();
});

it('guarda la contraseña como hash', function () {
    $user = User::factory()->create(['password' => 'Secreta*2026']);
    expect($user->getRawOriginal('password'))->not->toBe('Secreta*2026')
        ->and(Hash::check('Secreta*2026', $user->password))->toBeTrue();
});
```

### Casos de prueba (extracto)

| ID | CU | Técnica | Entrada | Resultado esperado | Resultado obtenido |
|---|---|---|---|---|---|
| CP01 | CU01 | Partición de equivalencia | Email y contraseña válidos, usuario activo | 200 + token | `<OK/Falla>` |
| CP02 | CU01 | Partición | Usuario inactivo | 403 "Usuario inactivo" | `<>` |
| CP03 | CU01 | Valores límite | 6 intentos fallidos en 1 min | 429 en el 6.º intento | `<>` |
| CP04 | CU01 | Partición | Google con correo `@gmail.com` | Rechazo "cuenta institucional" | `<>` |
| CP05 | CU02 | Partición | Código de facultad duplicado | 422 en `code` | `<>` |
| CP06 | CU10 | Valores límite | `message` de 9 caracteres | 422 | `<>` |
| CP07 | CU10 | Valores límite | `message` de 10 caracteres | 201 | `<>` |
| CP08 | CU10 | Partición | Segunda solicitud pendiente | 409 | `<>` |
| CP09 | CU10 | Flujo alterno A3 | Líder aprueba | Estado `approved` + integrante creado | `<>` |
| CP10 | CU11 | Partición | `area_ids` vacío | 422 | `<>` |
| CP11 | CU13 | Autorización | Líder edita semillero ajeno | 403 | `<>` |
| CP12 | CU14 | Caja blanca | Actualizar un CAT | Documento en `logs` con `before` y `after` | `<>` |
| CP13 | 3.1 | Integridad | Insertar en `requests` desde mongosh sin campos | `Document failed validation` | `<>` |
| CP14 | PWA | Funcional | Abrir la app sin conexión tras una visita | Muestra listado en caché | `<>` |
| CP15 | PWA | Instalabilidad | Lighthouse / Chrome "Instalar" | App instalable | `<>` |

Reporte completo de ejecución: `docs/pruebas/reporte_pruebas.md` · Cobertura: `docs/pruebas/coverage/` (`php artisan test --coverage`).

---

## 5.3 Planes de instalación, respaldo, migración y capacitación

### Plan de instalación

| Paso | Actividad | Responsable | Duración |
|---|---|---|---|
| 1 | Aprovisionar VPS Ubuntu 24.04, crear usuario `deploy`, configurar firewall (UFW: 22, 80, 443) | Administrador de infraestructura | 1 h |
| 2 | Crear clúster MongoDB Atlas, usuario `app_semilleros`, IP Access List con la IP del VPS | Administrador de BD | 30 min |
| 3 | Instalar Nginx, PHP 8.3-FPM, extensiones (`mongodb`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`), Composer, Node 20, Supervisor | Infraestructura | 45 min |
| 4 | Clonar repositorio en `/var/www/semillerosut`, configurar `.env`, `composer install --no-dev`, `npm ci && npm run build` | Desarrollo | 20 min |
| 5 | `php artisan key:generate`, `migrate --force`, `db:seed --class=ProductionSeeder`, `storage:link`, `config:cache`, `route:cache`, `view:cache` | Desarrollo | 10 min |
| 6 | Configurar virtual host Nginx y certificado Let's Encrypt (`certbot --nginx`) | Infraestructura | 20 min |
| 7 | Configurar Supervisor (`queue:work`) y cron (`schedule:run`) | Infraestructura | 15 min |
| 8 | Pruebas de humo: login web, login Google, instalar PWA, crear solicitud | QA | 30 min |

### Plan de respaldo

| Elemento | Método | Frecuencia | Retención | Ubicación |
|---|---|---|---|---|
| Base de datos | Snapshots automáticos de Atlas (plan Flex) | Diario | 7 días | Atlas |
| Base de datos | `mongodump --uri=$MONGODB_BACKUP_URI --gzip --archive` vía `schedule` de Laravel | Diario 02:00 | 30 días | VPS `/backups` + copia externa `<bucket S3 / Google Drive>` |
| Código | Repositorio Git remoto + etiquetas de versión | Cada release | Indefinida | GitHub |
| Configuración | `.env` cifrado (`php artisan env:encrypt`) | Cada cambio | Últimas 5 | Almacén seguro |
| Servidor | Snapshot del VPS | Semanal | 4 semanas | Proveedor |

```bash
# Restauración (probada trimestralmente; RTO objetivo < 1 h, RPO 24 h)
mongorestore --uri="$MONGODB_URI" --gzip --archive=/backups/semillerosut_2026-10-01.gz --drop
```

### Plan de migración

1. **Inventario de fuentes:** información publicada en `investigaciones.ut.edu.co`, hojas de cálculo de la coordinación y, si existe, la base PostgreSQL del diseño original.
2. **Mapeo:** se aplica la tabla de transformación de la sección 2.2 (tablas → colecciones, tablas N:M → arreglos de `ObjectId`, `objectives`/`results` → subdocumentos).
3. **Extracción y limpieza:** exportación a CSV/JSON; normalización de nombres de facultades y programas, eliminación de duplicados, validación de correos.
4. **Carga:** comandos Artisan `php artisan import:catalogs {archivo}` e `import:hotbeds {archivo}` que insertan respetando el `$jsonSchema` (los registros rechazados quedan en `storage/logs/import_errors.csv`).
5. **Validación:** conteos origen vs. destino por colección, revisión de 10 % de semilleros por muestreo con los líderes.
6. **Puesta en marcha:** piloto CAT Kennedy → ampliación por CAT; el sitio anterior enlaza a la PWA durante la transición.
7. **Reversión:** si la validación falla se restaura el respaldo previo a la carga (`mongorestore --drop`).

### Plan de capacitación

| Público | Contenido | Modalidad | Duración | Material |
|---|---|---|---|---|
| Administrador del sistema | Usuarios, catálogos, auditoría, respaldos | Taller virtual | 2 h | Manual técnico + manual de usuario |
| Líderes de semillero | Crear semillero, objetivos/resultados, integrantes, resolver solicitudes | Taller virtual con práctica | 2 h | Manual de usuario (módulo líder) + video |
| Administrativos | Consulta de módulos y reportes | Sesión virtual | 1 h | Manual de usuario |
| Estudiantes | Instalar la PWA, consultar, solicitar, proponer | Video corto + infografía + inducción | 15 min | Guía rápida PWA |

Evaluación: cuestionario de 5 preguntas al final de cada sesión (meta ≥ 80 % de aciertos) y encuesta de satisfacción.

---

## 5.4 Manuales

> Los manuales completos están en `docs/manuales/` (`manual_instalacion.md`, `manual_tecnico.md`, `manual_usuario.md`). A continuación su contenido esencial.

### Manual de instalación

**Requisitos:** Ubuntu 22.04/24.04, PHP ≥ 8.2 con extensión `mongodb`, Composer 2, Node.js 20+, Nginx, acceso a un clúster MongoDB 7+, dominio con HTTPS.

```bash
# 1. Dependencias del sistema
sudo apt update && sudo apt install -y nginx php8.3-fpm php8.3-{cli,mbstring,xml,curl,zip,bcmath,intl,dev} \
     php-pear unzip git supervisor
sudo pecl install mongodb && echo "extension=mongodb.so" | sudo tee /etc/php/8.3/mods-available/mongodb.ini
sudo phpenmod mongodb && sudo systemctl restart php8.3-fpm
php -m | grep mongodb            # debe mostrar "mongodb"

# 2. Código
cd /var/www && sudo git clone <URL_REPOSITORIO_GIT> semillerosut && cd semillerosut
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate
```

```dotenv
# 3. Variables principales de .env
APP_NAME=SemillerosUT
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<dominio>
DB_CONNECTION=mongodb
MONGODB_URI="mongodb+srv://app_semilleros:<clave>@<cluster>.mongodb.net/?retryWrites=true&w=majority"
MONGODB_DATABASE=semillerosut_db
SANCTUM_STATEFUL_DOMAINS=<dominio>
SANCTUM_EXPIRATION=480
GOOGLE_CLIENT_ID=<id>
GOOGLE_CLIENT_SECRET=<secret>
GOOGLE_REDIRECT_URI=https://<dominio>/api/v1/auth/google/callback
GOOGLE_ALLOWED_DOMAIN=<dominio_institucional>
MAIL_MAILER=smtp
QUEUE_CONNECTION=database
BCRYPT_ROUNDS=12
```

```bash
# 4. Base de datos y optimización
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
php artisan storage:link && php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

```nginx
# 5. /etc/nginx/sites-available/semillerosut
server {
    listen 80;
    server_name <dominio>;
    root /var/www/semillerosut/public;
    index index.php;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /sw.js { add_header Cache-Control "no-cache"; try_files $uri =404; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/semillerosut /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d <dominio>      # HTTPS, requisito de la PWA

# 6. Worker y tareas programadas
# /etc/supervisor/conf.d/semillerosut.conf
# [program:semillerosut-worker]
# command=php /var/www/semillerosut/artisan queue:work --sleep=3 --tries=3
# user=www-data
# autostart=true
# autorestart=true
sudo supervisorctl reread && sudo supervisorctl update
( crontab -l -u www-data; echo "* * * * * cd /var/www/semillerosut && php artisan schedule:run >> /dev/null 2>&1" ) | sudo crontab -u www-data -
```

**Instalación local para desarrollo:** `composer install`, `npm install`, `.env` apuntando a Atlas M0 o a MongoDB local (`mongodb://127.0.0.1:27017`), `php artisan migrate --seed`, `composer run dev` (servidor + Vite).

#### Instalación real (verificada 2026-09-28 en un clon limpio — RNF01)

**Desarrollo local (Docker):** requiere Docker con Compose v2 y Git. No hace falta PHP, Composer
ni Node en el equipo.

```bash
git clone https://github.com/Jose-Bohorquez/ut_semilleros.git && cd ut_semilleros
./scripts/dev-setup.sh      # .env, contenedores, composer, APP_KEY, permisos, migraciones + datos de ejemplo
```

Servicios: aplicación en `http://localhost:8080`, API en `http://localhost:8000/api`,
phpMyAdmin en `http://localhost:8081`. Las variables de desarrollo (MySQL de Docker,
`MAIL_MAILER=log`, `CACHE_STORE=database`) están en `docker-compose.yml`. Pruebas:
`docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: api php artisan test`.

**Producción (Hostinger compartido, PHP 8.2, sin Node ni supervisor):** el frontend va en la raíz
de `public_html/` y la API en `public_html/api/`. El `.htaccess` enruta `/api/*` a
`api/public/index.php`. Se sube `api/vendor/` ya instalado. Para desplegar: respaldo en el
servidor → `php -l` de cada PHP → `rsync --files-from` a la ruta exacta → `php artisan
migrate --force` (nunca `--seed`) → `route:clear` + `config:clear` → validación en vivo.

### Manual técnico

**Arquitectura:** cliente-servidor en capas (MVC de Laravel + API REST). Monolito modular que sirve el panel web (Blade) y la API consumida por la PWA.

```
app/
├── Http/
│   ├── Controllers/            # Web (Blade)
│   ├── Controllers/Api/        # API v1 (JSON + atributos OpenAPI)
│   ├── Middleware/             # EnsureUserIsActive, EnsureRole
│   ├── Requests/               # Form Requests (validación)
│   └── Resources/              # API Resources (serialización)
├── Models/                     # Eloquent MongoDB: User, Faculty, Career, Cat, Area, Group,
│                               # Coordinator, Hotbed, Member, MembershipRequest, Proposal, Log,
│                               # PersonalAccessToken (Sanctum sobre MongoDB)
├── Observers/AuditObserver.php # RF14
├── Policies/                   # HotbedPolicy, MembershipRequestPolicy…
└── Services/ReportService.php  # Aggregations
database/migrations/            # Colecciones + $jsonSchema + índices
resources/views, resources/js   # Panel y PWA
public/manifest.webmanifest, public/sw.js
routes/web.php, routes/api.php
tests/Feature, tests/Unit
docs/                           # Documentación, UML, BPMN, manuales, OpenAPI
```

**Decisiones técnicas relevantes:**

- **Sanctum sobre MongoDB:** se define `App\Models\PersonalAccessToken` extendiendo el modelo de Sanctum con el trait de documentos del paquete MongoDB, y se registra con `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)` en `AppServiceProvider`.
- **Auditoría:** `AuditObserver` registrado para todos los modelos del dominio; guarda `getOriginal()` y `getChanges()`.
- **Borrado lógico:** los controladores no exponen `destroy`; el cambio de estado usa `PATCH /{recurso}/{id}/status`.
- **Roles:** campo `role` en `users` + middleware `EnsureRole` + *Gates* (`manage-catalogs`, `manage-hotbeds`).
- **PWA:** mismo backend; rutas `/app/*` sirven el *shell* y el service worker controla el alcance `/app/`.

**Comandos de mantenimiento:**

| Comando | Uso |
|---|---|
| `php artisan test --coverage` | Ejecutar pruebas |
| `./vendor/bin/pint` | Formatear código (PSR-12) |
| `php artisan l5-swagger:generate` | Regenerar documentación de la API |
| `php artisan backup:mongo` | Respaldo manual (comando propio del proyecto) |
| `php artisan optimize:clear` | Limpiar cachés tras un despliegue |
| `php artisan queue:restart` | Reiniciar workers tras un despliegue |

**Solución de problemas frecuentes:**

| Síntoma | Causa probable | Solución |
|---|---|---|
| `Class "MongoDB\Driver\Manager" not found` | Extensión `mongodb` no cargada en FPM | `phpenmod mongodb` y reiniciar `php8.3-fpm` |
| `Document failed validation` | Datos que no cumplen el `$jsonSchema` | Revisar el *Form Request* y el esquema de la colección |
| La PWA no ofrece "Instalar" | Sin HTTPS, manifest inválido o SW no registrado | Revisar en DevTools → Application |
| Login Google rechazado | Dominio no permitido o URI de redirección distinta | Verificar `GOOGLE_ALLOWED_DOMAIN` y `GOOGLE_REDIRECT_URI` |
| 419 en peticiones del panel | Cookie CSRF ausente | Verificar `SANCTUM_STATEFUL_DOMAINS` y `SESSION_DOMAIN` |

### Manual de usuario

**Estudiante (PWA)**

1. **Instalar:** abra `https://<dominio>/app` en el celular. En Android/Chrome toque *Instalar app*; en iPhone/Safari toque *Compartir → Agregar a inicio*.
2. **Iniciar sesión:** toque *Iniciar sesión con Google* y elija su cuenta institucional. En el primer ingreso acepte la autorización de tratamiento de datos.
3. **Consultar semilleros:** en la pantalla principal verá los semilleros agrupados por facultad. Use el buscador o toque una facultad para desplegarla. Toque un semillero para ver misión, visión y objetivos.
4. **Solicitar ser miembro:** en el detalle toque *Ser miembro*, elija su programa, escriba su teléfono y un mensaje, y toque *Enviar*. Verá la confirmación.
5. **Proponer una idea:** en la pantalla principal toque **+**, seleccione programa y áreas, describa su idea y toque *Enviar*.
6. **Menú ☰:** *Perfil* (actualizar teléfono), *Mis solicitudes*, *Mis propuestas* (con su estado) y *Cerrar sesión*.

**Líder de semillero (web)**

1. Ingrese a `https://<dominio>/login` con el usuario y contraseña asignados. Si la olvidó, use *¿Olvidó su contraseña?*.
2. **Crear semillero:** menú *Semilleros → Crear*. Complete las pestañas *Datos generales*, *Misión y visión* y *Justificación*, y guarde.
3. **Objetivos y resultados:** en *Editar semillero*, pestañas *Objetivos* y *Resultados* → *Agregar*.
4. **Integrantes:** pestaña *Integrantes* → *Agregar*, o apruebe una solicitud.
5. **Solicitudes:** menú *Solicitudes*, filtre por semillero y use *Aprobar* o *Rechazar* (con motivo).
6. **Propuestas:** menú *Propuestas* para revisar las ideas enviadas por los estudiantes.

**Administrador del sistema (web)**

1. **Usuarios:** *Usuarios → Crear* (nombre, email, rol, referencia de autorización). Para dar de baja use *Inactivar*.
2. **Catálogos:** *Facultades*, *Programas*, *CAT*, *Áreas*, *Coordinadores*: botones *Crear*, *Ver*, *Editar*, *Inactivar*.
3. **Auditoría:** *Auditoría* permite filtrar por usuario, colección y fecha.

**Administrativo (web):** accede a todos los módulos en modo consulta y al *Dashboard* de reportes.

---

## 5.5 Despliegue del aplicativo

El despliegue corresponde al diagrama de la sección [2.3](#23-diagrama-de-despliegue-uml):

| Nodo UML | Implementación real | Evidencia |
|---|---|---|
| Dispositivo del estudiante / Navegador | PWA instalada desde `https://<dominio>/app` | Captura de instalación en Android e iOS |
| Equipo del personal web | Panel `https://<dominio>/login` | Captura del dashboard |
| VPS Ubuntu 24.04 (Nginx + PHP-FPM + Supervisor + cron) | `<proveedor>` — IP `<x.x.x.x>` | `nginx -t`, `supervisorctl status` |
| MongoDB Atlas Replica Set | Clúster `<nombre>` en `<región>` | Captura de colecciones con validadores |
| Google Identity | Proyecto OAuth `<nombre>` | Pantalla de consentimiento configurada |
| GitHub + Actions | `<URL_REPOSITORIO_GIT>/actions` | Pipeline en verde |

**Pipeline de despliegue continuo (`.github/workflows/deploy.yml`):**

```yaml
name: CI/CD
on:
  push: { branches: [main] }
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mongo: { image: mongo:7, ports: ['27017:27017'] }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', extensions: mongodb }
      - run: composer install --no-interaction
      - run: cp .env.ci .env && php artisan key:generate
      - run: ./vendor/bin/pint --test
      - run: php artisan test
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.VPS_HOST }}
          username: deploy
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd /var/www/semillerosut
            php artisan down
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan migrate --force
            php artisan optimize
            php artisan queue:restart
            php artisan up
```

URL de producción: `https://<dominio>` · Documentación API: `https://<dominio>/api/documentation`.

## 5.6 Control de versiones (Trimestre V)

Etiqueta `v1.0.0-T5` (release). Incluye `CHANGELOG.md` con los cambios por versión, manuales, reporte de pruebas y pipeline CI/CD. Historial: `<URL_REPOSITORIO_GIT>/releases/tag/v1.0.0-T5`.

| Versión | Trimestre | Contenido principal |
|---|---|---|
| v0.1.0-T1 | I | Análisis, BPMN, recolección, HU, casos de uso, mockups |
| v0.2.0-T2 | II | Costos y proveedores, diagramas de clases y despliegue, prototipo navegable |
| v0.3.0-T3 | III | MongoDB con validación, CRUD y agregaciones, seguridad, frontend 50 %, API segura |
| v0.4.0-T4 | IV | Frontend 80 % con API, PWA, Swagger, modelo de calidad, Scrum móvil |
| v1.0.0-T5 | V | Codificación 100 %, pruebas, planes, manuales, despliegue en producción |

---

# Reporte de avance

| Trimestre | Entregables | Cumplidos | Porcentaje |
|---|---|---|---|
| I | 7 | 7 | 100 % |
| II | 5 | 5 | 100 % |
| III | 6 | 6 | 100 % |
| IV | 6 | 6 | 100 % |
| V | 6 | 6 | 100 % |
| **Total** | **30** | **30** | **100,00 %** |

**Métrica de porcentaje y avance del proyecto: 100,00 %**

---

### Referencias

- Herrera Salazar, E. Y. y Mahecha Vaca, N. J. (2020). *Diseño de una aplicación móvil para la gestión de información de los semilleros de investigación en el IDEAD de la Universidad del Tolima*. Universidad del Tolima – IDEAD.
- ISO/IEC 25010:2011 / 2023. *Systems and software Quality Requirements and Evaluation (SQuaRE) — Product quality model*.
- Ley 1581 de 2012 y Decreto 1377 de 2013 (Colombia). Protección de datos personales.
- Documentación oficial: Laravel (laravel.com/docs), MongoDB Laravel Integration (mongodb.com/docs/drivers/php/laravel-mongodb), MongoDB Schema Validation (mongodb.com/docs/manual/core/schema-validation), web.dev — Progressive Web Apps.
- Pressman, R. (2010). *Ingeniería del software: un enfoque práctico*. McGraw-Hill.
- Sommerville, I. (2005). *Ingeniería del software*. Pearson.
