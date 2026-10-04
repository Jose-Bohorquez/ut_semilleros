# Manual de usuario — Administrador del sistema

Como administrador, tienes control total: usuarios, facultades, programas, CAT, áreas, grupos,
coordinadores, semilleros, auditoría y el panel de SIA.

## 1. Iniciar sesión

Entra a `https://ut-edu.online` con tu correo y contraseña. Si olvidaste tu contraseña, usa
**«¿Olvidó su contraseña?»** en la pantalla de inicio: te llega un enlace válido por 60 minutos.

## 2. Cómo funcionan los listados (igual en todo el sistema)

Cada sección (Usuarios, Facultades, Programas, CAT, Áreas, Grupos, Coordinadores…) sigue el mismo
patrón:

- **Crear:** botón **«Crear …»** arriba de la tabla. Los campos obligatorios llevan un asterisco
  (`*`).
- **Editar:** ícono de lápiz en la fila.
- **Activar / Inactivar:** ningún registro se elimina; se activa o inactiva con el interruptor de
  estado. Un registro inactivo no aparece como opción en los formularios que dependen de él (por
  ejemplo, una facultad inactiva no aparece al crear un programa).
- **Auditoría:** toda creación, modificación y cambio de estado queda registrada automáticamente.

## 3. Usuarios

Ve a **«Usuarios»**.

- **Crear un usuario:** nombre, correo institucional y rol (Administrador del sistema,
  Administrativo, Líder de semillero, Estudiante). Si dejas la contraseña en blanco, el usuario
  recibe un correo **«Activa tu cuenta · Semilleros UT»** con un enlace (válido 7 días) para
  definirla él mismo; si escribes una contraseña, debe cumplir la política (mínimo 8 caracteres,
  mayúscula, minúscula, número y símbolo) y confirmarse en el segundo campo.
- **Carga masiva:** si tienes muchos usuarios que registrar (ej. un listado de estudiantes), usa la
  opción de carga masiva con nombre, correo y rol; cada uno recibe su correo de activación.
- **Inactivar:** cierra automáticamente las sesiones abiertas de ese usuario.

## 4. Facultades, Programas y CAT

- **Facultades:** código (único), nombre y estado.
- **Programas:** facultad, código (único), nombre y tipo (Pregrado o Posgrado).
- **CAT** (Centros de Atención Tutorial): código (único), nombre, dirección, ciudad, correo y hasta
  3 teléfonos — se exige al menos uno de los tres.

## 5. Áreas, Grupos y Coordinadores

Se gestionan igual que facultades: código, nombre, estado, sin eliminación.

## 6. Semilleros, Objetivos, Proyectos, Productos y Resultados

Como administrador puedes ver y modificar cualquier semillero (a diferencia del líder, que solo ve
los suyos). El formulario de semillero incluye sus objetivos en la misma pantalla (con flechas ↑↓
para reordenarlos).

## 7. Auditoría

Ve a **«Auditoría»** para consultar quién hizo qué y cuándo: creaciones, modificaciones, cambios de
estado, inicios y cierres de sesión, y restablecimientos de contraseña. Ningún registro de
auditoría se puede modificar ni eliminar.

## 8. SIA — Asistente

Ve a **«SIA · Asistente»** para:

- Consultar cuántas conversaciones hay y su calificación (caritas 😞–😄).
- Leer los comentarios que dejaron los usuarios.
- Corregir una respuesta: agrega una pregunta con su respuesta correcta a la base de conocimiento,
  y SIA la usará en la siguiente pregunta parecida, sin necesidad de desplegar nada.
- Ajustar límites de uso.

## 9. Tu perfil

En **«Perfil»** puedes cambiar tu foto, nombre, correo y contraseña.

## 10. Preguntas y errores

- **Dudas de uso:** botón rojo **SIA**, en todas las pantallas.
- **Errores del sistema:** botón verde de **WhatsApp**, junto a SIA.

## 11. Cerrar sesión

Escritorio: botón **«Cerrar sesión»** de la barra superior. Celular: toca tu **avatar** (arriba a la derecha) → **«Cerrar sesión»**. Cierra la sesión de este dispositivo; si el usuario tenía
sesiones abiertas en otros dispositivos, esas no se ven afectadas (solo un restablecimiento de
contraseña cierra todas a la vez).

## 12. Retención de la auditoría

La tabla de auditoría crece con cada acción del sistema y **no se borra sola**. El equipo técnico puede
eliminar los registros más antiguos con `php artisan audits:prune` (conserva 24 meses por defecto, nunca
menos de 6; `--dry-run` solo cuenta lo que borraría y `--archive=ruta.csv` guarda antes una copia). La
purga queda registrada en la propia auditoría como **«Purga de auditoría»**. No está programada.
