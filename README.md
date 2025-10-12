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
| PHP | 8.4 | Lenguaje backend principal |
| Laravel | 12.x | Framework MVC y REST API |
| MySQL | 9.0 | Motor de base de datos relacional |
| phpMyAdmin | 5.2.2 | Cliente web para administración de BD |
| Composer | 2.8 | Gestor de dependencias PHP |
| Docker & Docker Compose | Última | Contenedores y orquestación local |
| Apache | 2.4 | Servidor web embebido |

---

## ⚙️ Instalación y Ejecución Local (Entorno Docker)

### 1️⃣ Clonar el repositorio

```
git clone https://github.com/Jose-Bohorquez/ut_semilleros.git
cd ut_semilleros
```

### 2️⃣ Construir y levantar contenedores

```
docker compose up -d --build
```

Servicios que se ejecutan:

- `ut_semilleros_api` → Backend Laravel + Apache  
- `ut_semilleros_db` → Base de datos MySQL  
- `ut_semilleros_phpmyadmin` → Interfaz de administración de BD

### 3️⃣ Acceso a la aplicación

- Aplicación web: http://localhost:8080  
- phpMyAdmin: http://localhost:8081  
  - Usuario: root  
  - Contraseña: root

### 4️⃣ Verificar contenedores activos

```
docker ps
```

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

## 🧩 Próximos Pasos

1. Crear el archivo `.env` con configuración Docker + Producción.  
2. Implementar autenticación con Laravel Sanctum.  
3. Desarrollar modelos y migraciones de semilleros, proyectos y usuarios.  
4. Construir endpoints RESTful iniciales.  
5. Desarrollar frontend PWA para consumo de API y notificaciones.  

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
🌐 **Portafolio (en desarrollo):** [josebohorquez](https://jose-bohorquez.github.io/)

---

## 📜 Licencia

Este proyecto se distribuye bajo la licencia **MIT**.  
Puedes usarlo, modificarlo y redistribuirlo libremente, siempre que mantengas los créditos originales.

---

> 💬 “El conocimiento compartido multiplica el aprendizaje.”  
> — Inspirado por la comunidad de semilleros UT 🌱
