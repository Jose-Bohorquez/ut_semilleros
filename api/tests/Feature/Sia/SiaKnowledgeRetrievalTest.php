<?php

namespace Tests\Feature\Sia;

use Tests\TestCase;
use App\Services\Sia\SiaAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SIA: la memoria técnica (api/resources/sia/base-conocimiento.md) debe recuperar
 * la sección correcta para las preguntas reales de cada rol. No llama a Groq:
 * comprueba solo el contexto que se le enviaría (buildContext).
 */
class SiaKnowledgeRetrievalTest extends TestCase
{
    use RefreshDatabase;

    /** pregunta => fragmento del título de la sección que debe llegar al contexto */
    public static function questions(): array
    {
        return [
            // ── Estudiante
            'postularme'            => ['¿Cómo me postulo a un semillero?', 'Ser miembro de un semillero'],
            'unirme'                => ['quiero unirme a un semillero, qué hago', 'Ser miembro de un semillero'],
            'sin boton mas'         => ['no me sale el botón + para enviar solicitud', 'regla de una sola solicitud activa'],
            'estado solicitud'      => ['dónde veo el estado de mi solicitud', 'Ver mis solicitudes'],
            'significa pendiente'   => ['qué significa pendiente en mi solicitud', 'Qué significan Pendiente, Aprobada y Rechazada'],
            'me rechazaron'         => ['me rechazaron la solicitud, puedo volver a intentar', 'Qué significan Pendiente, Aprobada y Rechazada'],
            'solicitud pendiente'   => ['mi solicitud lleva días pendiente', 'sigue pendiente hace mucho'],
            'registrar propuesta'   => ['cómo registro una propuesta de investigación', 'Registrar una propuesta'],
            'editar propuesta'      => ['puedo editar mi propuesta', 'Ver, editar y evaluar mis propuestas'],
            'ver semilleros'        => ['cómo veo los semilleros por facultad', 'Explorar y buscar semilleros'],
            'detalle semillero'     => ['dónde veo la misión y visión de un semillero', 'Ver el detalle de un semillero'],
            'instalar'              => ['cómo instalo la app en mi iPhone', 'Instalar la aplicación en el celular'],
            'sin internet'          => ['puedo usar la app sin internet', 'Usar la app sin conexión'],
            'app vieja'             => ['la app se ve desactualizada, cómo limpio la caché', 'Actualizar la app o limpiar la caché'],
            'datos personales'      => ['por qué me pide autorizar datos personales', 'Autorización de datos personales'],
            'google'                => ['puedo entrar con mi cuenta de Google', 'Iniciar sesión con Google'],
            'notificaciones push'   => ['cómo activo las notificaciones en el celular', 'notificaciones push'],
            'olvide clave'          => ['olvidé mi contraseña', 'Olvidé mi contraseña'],
            'no puedo entrar'       => ['no puedo iniciar sesión, dice credenciales incorrectas', 'No puedo iniciar sesión'],
            'sesion expiro'         => ['me sale que mi sesión expiró', 'Su sesión expiró'],
            'que es cat'            => ['qué es un CAT', 'Glosario: facultad, programa'],
            // ── Líder
            'crear semillero'       => ['cómo creo un semillero nuevo', 'registro (creo) un semillero'],
            'campos semillero'      => ['qué campos son obligatorios para registrar un semillero', 'campos son obligatorios al registrar un semillero'],
            'editar semillero'      => ['cómo edito mi semillero', 'modifico (edito) un semillero'],
            'inactivar semillero'   => ['cómo desactivo un semillero', 'inactivo (desactivo o doy de baja) un semillero'],
            'no puedo editar'       => ['por qué no puedo editar este semillero', 'no puedo editar o inactivar este semillero'],
            'agregar integrante'    => ['cómo agrego un integrante al semillero', 'agrego un integrante a un semillero'],
            'objetivos'             => ['cómo agrego objetivos a mi semillero', 'objetivos de un semillero'],
            'resultados'            => ['cómo registro los resultados del semillero', 'resultados de un semillero'],
            'aprobar solicitud'     => ['cómo apruebo una solicitud de un estudiante', 'apruebo (acepto) una solicitud'],
            'rechazar solicitud'    => ['cómo rechazo una solicitud', 'rechazo (deniego) una solicitud'],
            'ver solicitudes'       => ['dónde veo quién quiere unirse a mi semillero', 'quién quiere unirse a mi semillero'],
            'ya resuelta'           => ['me sale Esta solicitud ya fue resuelta', 'Esta solicitud ya fue resuelta'],
            'aprobe integrante'     => ['aprobé una solicitud, el estudiante ya es integrante', 'ya es integrante del semillero'],
            'evaluar propuestas'    => ['cómo evalúo las propuestas de los estudiantes', 'evalúo las propuestas'],
            'proyectos'             => ['cómo creo un proyecto y agrego miembros', 'gestiono los proyectos'],
            // ── Administrativo / Administrador
            'administrativo aprueba'=> ['el administrativo puede aprobar solicitudes', 'Administrativo puede aprobar o rechazar solicitudes'],
            'crear usuario'         => ['cómo creo un usuario nuevo', 'Crear un usuario nuevo'],
            'importar'              => ['cómo hago la carga masiva de estudiantes', 'Importar usuarios'],
            'inactivar usuario'     => ['no me deja inactivar un usuario', 'inactivar un usuario'],
            'reenviar activacion'   => ['un usuario no recibió el correo de activación', 'correo de activación'],
            'facultad'              => ['cómo creo una facultad', 'Crear una facultad'],
            'programa'              => ['cómo agrego un programa académico', 'Crear un programa académico'],
            'cat'                   => ['cómo registro un CAT', 'Crear un CAT'],
            'area'                  => ['cómo creo un área de conocimiento', 'Crear un área de conocimiento'],
            'grupo'                 => ['cómo creo un grupo de investigación', 'Crear un grupo de investigación'],
            'coordinador'           => ['cómo registro un coordinador', 'coordinador o editarlo'],
            'auditoria'             => ['dónde consulto la auditoría', 'Consultar la Auditoría'],
            'filtrar auditoria'     => ['cómo filtro el registro de auditoría por usuario y fechas', 'Consultar la Auditoría'],
            'exportar auditoria'    => ['cómo exporto la auditoría a CSV', 'Consultar la Auditoría'],
            'rbac'                  => ['para qué sirven los permisos RBAC', 'Permisos por módulo'],
            'reportes'              => ['dónde están los reportes', 'Reportes y estadísticas'],
            'exportar'              => ['cómo exporto una tabla a Excel', 'Buscar, paginar y exportar'],
            'eliminar'              => ['puedo eliminar un registro', 'Puedo eliminar'],
            'menu'                  => ['por qué no veo el menú de usuarios', 'no veo el menú'],
            'dashboard'             => ['qué significan las tarjetas del dashboard', 'Panel principal'],
            'enviar notificacion'   => ['cómo envío una notificación a mi semillero', 'Enviar una notificación'],
            'foto perfil'           => ['cómo cambio mi foto de perfil', 'foto'],
            'sia admin'             => ['cómo reviso las calificaciones de SIA', 'Panel de SIA'],
            'bug'                   => ['encontré un error en el sistema', 'Reportar un error'],
            // ── Ronda de auditoría (2026-10-01)
            'asignar lider'         => ['cómo asigno el líder de un semillero', 'líder responsable de un semillero'],
            'cambiar correo'        => ['puedo cambiar mi correo en el perfil', 'Perfil: cambiar mis datos'],
            'limite propuestas'     => ['por qué no me deja registrar más propuestas', 'Registrar una propuesta'],
            'respuesta evaluador'   => ['dónde veo la respuesta del evaluador de mi propuesta', 'Ver, editar y evaluar mis propuestas'],
        ];
    }

    /** @dataProvider questions */
    #[\PHPUnit\Framework\Attributes\DataProvider('questions')]
    public function test_context_contains_the_right_section(string $question, string $expectedTitlePart): void
    {
        $context = (new SiaAssistant())->buildContext($question);

        $titles = collect(preg_split('/\R/', $context))->filter(fn ($l) => str_starts_with($l, '## '))->implode(' | ');

        $this->assertStringContainsStringIgnoringCase(
            $expectedTitlePart,
            $titles,
            "Para «{$question}» llegaron: {$titles}"
        );
    }

    public function test_roles_section_and_identity_always_included(): void
    {
        $context = (new SiaAssistant())->buildContext('hola quiero saber algo');

        $this->assertStringContainsString('## Qué es el sistema', $context);
        $this->assertStringContainsString('## Roles y qué puede hacer cada uno', $context);
    }

    public function test_context_stays_small_enough_for_the_token_budget(): void
    {
        $worst = 0;
        foreach (array_column(self::questions(), 0) as $q) {
            $worst = max($worst, strlen((new SiaAssistant())->buildContext($q)));
        }

        // ≈ 4 caracteres por token: el contexto no debe superar ~1.700 tokens.
        $this->assertLessThan(7000, $worst, "Contexto más grande: {$worst} caracteres");
    }

    public function test_every_section_is_a_reasonable_size(): void
    {
        $raw = file_get_contents(resource_path('sia/base-conocimiento.md'));
        foreach (preg_split('/^(?=## )/m', $raw) as $sec) {
            if (trim($sec) === '') continue;
            $this->assertLessThan(1500, mb_strlen($sec), 'Sección demasiado larga: ' . strtok($sec, "\n"));
        }
    }
}
