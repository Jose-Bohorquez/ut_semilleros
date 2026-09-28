# 🌱 UT Semilleros – Plataforma Web Académica

> **Proyecto base** desarrollado con Laravel 12, PHP 8.4 y MySQL 9 en entorno Dockerizado.  
> Forma parte del ecosistema de aplicaciones institucionales para la **Universidad del Tolima – Semilleros de Investigación.**

---

## 🎯 Enfoque del Proyecto

UT Semilleros es una iniciativa académica que busca **potenciar la gestión de los semilleros de investigación** mediante el desarrollo de una **aplicación web progresiva (PWA)** moderna, accesible y segura.  
Su diseño está orientado a la **colaboración**, la **automatización de procesos académicos** y la **trazabilidad de la información institucional**.

Este sistema tiene como meta conectar de manera eficiente a **docentes, estudiantes y coordinadores**, ofreciendo herramientas de seguimiento, comunicación y análisis de desempeño en los proyectos investigativos.

---

## 🎓 Objetivo General

Desarrollar una plataforma web institucional que permita la **gestión integral de los semilleros de investigación** de la Universidad del Tolima, garantizando la eficiencia, transparencia y disponibilidad de la información académica.

### 🎯 Objetivos Específicos

- Diseñar una arquitectura modular basada en **microservicios y APIs RESTful**.  
- Implementar un backend robusto con **Laravel 12 y PHP 8.4**.  
- Crear un entorno **Dockerizado** para el despliegue y la portabilidad del sistema.  
- Integrar un **sistema de autenticación seguro** con Laravel Sanctum o Passport.  
- Proporcionar interfaces accesibles mediante **PWA y consumo de APIs con JavaScript**.  
- Facilitar la administración de base de datos con **MySQL 9 y phpMyAdmin**.  
- Documentar el proyecto conforme a las buenas prácticas del desarrollo profesional.

---

## 🧱 Tecnologías Principales

| Componente | Versión | Descripción |
|-------------|----------|-------------|
| PHP | 8.2+ (producción 8.2, Docker 8.4) | Lenguaje backend principal |
| Laravel | 12.x | Framework MVC y REST API |
| MySQL | 9.0 | Motor de base de datos relacional |
| phpMyAdmin | 5.2.2 | Cliente web para administración de BD |
| Composer | 2.8 | Gestor de dependencias PHP |
| Docker & Docker Compose | Última | Contenedores y orquestación local |
| Apache | 2.4 | Servidor web embebido |

---

## ⚙️ Instalación y Ejecución Local (Entorno Docker)

Requisitos: Docker con Docker Compose v2 y Git. No hace falta PHP, Composer ni Node en el equipo.

### 1️⃣ Clonar el repositorio

```
git clone https://github.com/Jose-Bohorquez/ut_semilleros.git
cd ut_semilleros
```

### 2️⃣ Instalar y levantar (un solo comando)

```
./scripts/dev-setup.sh
```

El script crea `api/.env` a partir de `api/.env.example`, levanta los contenedores,
instala las dependencias PHP, genera la clave de la aplicación, da permisos de escritura
a `storage/` y migra y siembra la base de datos con datos de ejemplo. Se puede volver a
ejecutar sin riesgo: no vuelve a sembrar si ya hay usuarios.

Las variables de entorno de desarrollo (BD de Docker, correo a `log` y caché en base de datos)
están en `docker-compose.yml` y tienen prioridad sobre `api/.env`. Ningún correo real sale del
entorno local.

### 3️⃣ Acceso

| Servicio | URL |
|---|---|
| Aplicación (panel web y PWA) | http://localhost:8080 |
| API REST | http://localhost:8000/api |
| phpMyAdmin | http://localhost:8081 |

Los usuarios de ejemplo de cada rol están en `api/database/seeders/UserSeeder.php`. Son
solo para desarrollo y nunca se siembran en producción.

### 4️⃣ Pruebas

```
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: api php artisan test
```

Los `-e` son obligatorios: sin ellos, la suite usaría la BD MySQL de desarrollo, y
`RefreshDatabase` la borraría.

---

## 📂 Estructura del Proyecto

```
ut-semilleros/
├── api/                     # Código fuente Laravel 12
│   ├── app/                 # Controladores, modelos y lógica de negocio
│   ├── routes/              # Definición de rutas
│   ├── database/            # Migraciones y seeders
│   ├── public/              # Carpeta pública servida por Apache
│   └── ...
│
├── db/                      # Configuración de base de datos
│   ├── init.sql             # Script de inicialización
│   └── data/ (ignorada)     # Datos persistentes de MySQL
│
├── apache/                  # Configuración personalizada de Apache
│   └── laravel.conf
│
├── docker-compose.yml        # Orquestador de servicios
├── Dockerfile                # Imagen base PHP + Apache + Composer
└── .gitignore                # Reglas de exclusión Git
```

---

## 🧠 Convenciones de Commits

Este proyecto sigue el estándar **Conventional Commits**, adoptado para mantener un historial limpio y semántico.

### 🧩 Estructura del mensaje

```
<tipo>(<área opcional>): <resumen breve en presente>
```

### 📘 Tipos más comunes

| Tipo | Descripción | Ejemplo |
|------|--------------|---------|
| feat | Nueva funcionalidad | feat(users): add student registration module |
| fix | Corrección de errores | fix(api): correct null reference in controller |
| docs | Documentación o README | docs(readme): add installation guide |
| style | Cambios estéticos o formato | style(blade): adjust indentation |
| refactor | Reestructuración sin cambio funcional | refactor(routes): optimize middleware group |
| test | Nuevas pruebas o modificaciones | test(users): add unit tests for CRUD |
| chore | Tareas de mantenimiento | chore(git): update .gitignore rules |
| build | Cambios en dependencias, Docker o compilación | build(docker): add PHP 8.4 extensions |
| ci | Integración continua o despliegue | ci(github): add action for build testing |

**Ejemplo de primer commit:**  
```
chore(init): initial project setup with Laravel 12, Docker, and MySQL
```

---

## 🌐 Despliegue en Hostinger

El proyecto está optimizado para correr en planes **Premium Web Hosting de Hostinger**, que soportan PHP, MySQL y Composer.  
Se debe establecer `/api/public` como raíz del dominio y configurar `.htaccess` para redirecciones.

Próximas mejoras previstas:

- Despliegue automatizado con **GitHub Actions**  
- Script de migraciones remotas  
- Integración CDN + caché estático  
- Monitoreo de logs y errores desde panel web

---

## 📚 Documentación

- [`docs/roles-usuarios.md`](docs/roles-usuarios.md) — matriz de roles y permisos, validada contra el backend real.
- [`docs/CHANGELOG.md`](docs/CHANGELOG.md) — bugs encontrados y corregidos en cada ronda de auditoría (última: 2026-08-31).

## 🔑 Credenciales y configuración sensible

Este repositorio es **público** — ninguna contraseña, token o secreto real se documenta
aquí ni en ningún otro `.md` del proyecto. Lo que sí queda documentado es **dónde vive**
cada credencial:

| Credencial | Dónde vive |
|---|---|
| `.env` de producción (BD, `APP_KEY`, SMTP, VAPID) | Solo en el servidor (`api/.env`, `chmod 600`), nunca en el repo (`.gitignore` ya excluye `.env`/`.env.*`). |
| Contraseña del buzón `activacion@ut-edu.online` (SMTP de correos de activación/recuperación) | hPanel de Hostinger → Correos. Usada en `MAIL_USERNAME`/`MAIL_PASSWORD` del `.env` de producción. |
| Contraseñas de usuarios reales del sistema | Nunca se generan ni se comparten en texto plano salvo una única vez al crear la primera cuenta admin; todo usuario nuevo se crea **sin contraseña** y activa la suya propia vía el correo de activación (`/reset-password?...&activation=1`). |
| Credenciales de BD de producción | hPanel de Hostinger → Bases de datos. |

## 🚀 Estado actual

El sistema ya está desplegado en producción (`https://ut-edu.online/`), con autenticación,
gestión de semilleros (con objetivos reordenables), proyectos, productos, resultados,
solicitudes de ingreso, propuestas de investigación, notificaciones push, dashboard por
rol, e **importación masiva de usuarios con activación de cuenta por correo real** (SMTP
configurado, ya no `MAIL_MAILER=log`). Ver `docs/CHANGELOG.md` para el detalle de qué se
validó y corrigió en cada ronda.

### Próximas mejoras identificadas (no bloqueantes)

- Confirmar recepción visible/audible de una notificación push en un dispositivo físico
  real (el envío del lado del servidor ya se verificó exitoso contra FCM).
- Versionamiento de propuestas (historial de cambios), mencionado como posible mejora
  futura por el equipo.
- Cargar los usuarios reales pendientes (CAT Kennedy) vía el importador, una vez se
  confirmen o corrijan los nombres sugeridos.

---

## 👨🏻‍💻 Autor

**Jose Julio Bohórquez Delgado**  
**Analista Full Stack en Desarrollo de Software y Ciberseguridad**  
📍 *Universidad del Tolima* | *SENA – Centro de Electricidad, Electrónica y Telecomunicaciones (CEET)*  

Formación y certificaciones destacadas:

- 🎓 **Estudiante de Ingeniería de Sistemas – Semestre 2**  
  *Universidad del Tolima*  
- 🎓 **Tecnólogo en Análisis y Desarrollo de Software (ADSO)**  
  *SENA – CEET (Centro de Electricidad, Electrónica y Telecomunicaciones)*  
- 🎓 **Técnico en Programación de Software**  
  *SENA – CEET (Centro de Electricidad, Electrónica y Telecomunicaciones)*  
- 🎓 **Técnico en Sistemas**  
  *SENA – CME (Centro de Diseño y Metrología)*  
- 💻 **Certificación Profesional en Ciberseguridad**  
  *Google / Coursera*  
- 🔐 **Certificado en Análisis de Datos y Seguridad de la Información**  
  *Colnodo / Fundación Telefónica Movistar*  
- ☁️ **Certificado en Genesys Cloud CX Foundations & Automation**  

Apasionado por el desarrollo web moderno, la seguridad informática, la automatización de procesos y la formación continua.  
Actualmente enfocado en construir soluciones digitales seguras, escalables y centradas en el usuario.  

📧 **Correo:** [josejbohorquezd@gmail.com](mailto:josejbohorquezd@gmail.com)  
🐙 **GitHub:** [@Jose-Bohorquez](https://github.com/Jose-Bohorquez)  
💼 **LinkedIn:** [linkedin.com/in/josebohorquez](https://www.linkedin.com/in/jose-bohorquez-full-stack-software-developer/)  
🌐 **Portafolio (en desarrollo):** [josebohorquez.dev](https://jose-bohorquez.github.io/)

---

## 📜 Licencia

Este proyecto se distribuye bajo la licencia **MIT**.  
Puedes usarlo, modificarlo y redistribuirlo libremente, siempre que mantengas los créditos originales.

---

> 💬 “El conocimiento compartido multiplica el aprendizaje.”  
> — Inspirado por la comunidad de semilleros UT 🌱
