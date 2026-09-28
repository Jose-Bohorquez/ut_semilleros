<?php

namespace App\Services\Sia;

use App\Models\SiaKnowledge;
use Illuminate\Support\Str;

/**
 * Respuestas de SIA que NO gastan cupo de Groq (Jose, 2026-09-28):
 *  1. Intenciones simples (saludo, quién eres, quién te creó, contacto, gracias,
 *     despedida) cuando el mensaje no trae otra pregunta.
 *  2. Coincidencia fuerte con una respuesta corregida por el administrador
 *     (sia_knowledge): a más feedback cargado, menos llamadas a la API.
 */
class SiaLocalResponder
{
    private const FILLER = ['hola','buenas','buenos','buen','dias','tardes','noches','sia','por','favor','gracias','muchas','oye','hey','saludos','que','tal','como','estas','esta','usted','tu','eres','quien','quienes','cual','es','el','la','los','las','un','una','de','del','y','o','a','me','te','se','lo','le','mi','su','para','con','en','al','puedes','puede','podrias','decir','dime','sabes','hiciste','hizo','chao','chau','adios','bye','hasta','luego','nos','vemos','agradezco','mil','holi','hey','pronto'];

    private const INTENTS = [
        'identity' => [
            'pattern' => '/\b(quien|que)\s+(eres|es\s+sia)\b|\bque\s+(haces|puedes\s+hacer|sabes\s+hacer)\b|\bpara\s+que\s+sirves\b|\bpresentate\b/u',
            'answer'  => "Soy **SIA**, el Sistema Integrado de Asistencia del Sistema de Semilleros de Investigación del IDEAD – Universidad del Tolima. Te ayudo con dudas de uso: iniciar sesión, recuperar tu contraseña, instalar la app, postularte a un semillero, registrar propuestas y qué puede hacer cada rol.\nPara reportar un error del sistema usa el botón verde de WhatsApp «Reportar bug».",
        ],
        /* Más información del desarrollador: solo si la piden (Jose, 2026-09-28:
           «no quiero ser egocéntrico»; la respuesta básica va resumida). */
        'creator_more' => [
            'pattern' => '/\b(mas|otra)\s+(informacion|info|datos)\b.*\b(jose|bohorquez|creador|desarrollador|autor)\b|\b(quien\s+es|sobre|contacto\s+de|contactar\s+a|perfil\s+de|conocer\s+a|redes\s+de)\s+(jose|bohorquez|creador|el\s+creador|el\s+desarrollador|tu\s+creador)\b|\b(linkedin|github|portafolio|portfolio)\b/u',
            'answer'  => "**José Bohórquez**\n\n- Desarrollador autodidacta\n- Especialista en Genesys Cloud\n- Técnico en Programación de Software (TPS)\n- Tecnólogo en Análisis y Desarrollo de Sistemas de Información (ADSO)\n- Estudiante de la Facultad de Ingeniería\n\n**Contacto**\n\n- Teléfono / WhatsApp: +57 317 877 3186\n- Correo institucional: jjbohorquezd@ut.edu.co\n- Correo personal: josejbohorquezd@gmail.com\n- LinkedIn: https://www.linkedin.com/in/jose-bohorquez-full-stack-software-developer/\n- GitHub: https://github.com/Jose-Bohorquez\n- Portafolio: https://jose-bohorquez.github.io/",
        ],
        'creator' => [
            'pattern' => '/\b(quien|quienes)\s+(te\s+)?(creo|crearon|hizo|hicieron|desarrollo|desarrollaron|programo|diseno)\b|\b(creador|creadores|desarrollador|desarrolladores|autor|autores)\b/u',
            'answer'  => "El Sistema de Semilleros IDEAD y SIA fueron desarrollados por **José Bohórquez**, desarrollador autodidacta y estudiante de la Facultad de Ingeniería, para el IDEAD de la Universidad del Tolima.\n\nParten del diseño original **INITIUM (2020)** de Ema Herrera y Nelly Mahecha, a quienes agradecemos.\n\nSi encontraste un error, repórtalo con el botón verde de WhatsApp «Reportar bug».",
        ],
        'contact' => [
            'pattern' => '/\b(hablar|hablo|comunicarme|comunico|contactar|contacto|contacto|escribir|escribo|escribirle|comunicarse)\b.*\b(creador|creadores|desarrollador|soporte|administrador|alguien|persona|humano)\b|\bsoporte\s+tecnico\b|\bnumero\s+de\s+contacto\b/u',
            'answer'  => "Según lo que necesites:\n1. **Un error del sistema** (algo no funciona): usa el botón verde de WhatsApp «Reportar bug», indicando la pantalla, qué hiciste y qué pasó.\n2. **Tu cuenta o permisos**: escribe al administrador del sistema de tu sede.\n3. **Temas académicos del semillero**: habla con el líder o la coordinación de tu semillero, o usa el enlace «Contacto» del pie de la pantalla de inicio.",
        ],
        'thanks' => [
            'pattern' => '/^(muchas\s+)?gracias\b|\bmil\s+gracias\b|\bte\s+agradezco\b/u',
            'answer'  => "¡Con gusto! Si tienes otra duda sobre el sistema, aquí estoy. Cuando termines, toca «Finalizar» y cuéntame con las caritas qué tal te fue.",
        ],
        'bye' => [
            'pattern' => '/^(adios|chao|chau|hasta\s+luego|nos\s+vemos|bye)\b/u',
            'answer'  => "¡Hasta pronto! Antes de irte, toca «Finalizar» y califica cómo te respondió SIA: nos ayuda a mejorar.",
        ],
        'greeting' => [
            'pattern' => '/^(hola|holi|buenas|buenos\s+dias|buenas\s+tardes|buenas\s+noches|hey|saludos|que\s+tal)\b/u',
            'answer'  => "¡Hola! Soy **SIA**. ¿En qué te ayudo con el Sistema de Semilleros? Por ejemplo: cómo postularte a un semillero, crear una propuesta o recuperar tu contraseña.",
        ],
    ];

    /** @return array{answer:string, source:string, intent:?string}|null */
    public function match(string $question): ?array
    {
        $norm = $this->normalize($question);

        /* Saludo / gracias / despedida solo si no viene otra pregunta en el mismo mensaje */
        $rest = $this->meaningful($norm);
        foreach (['identity', 'creator_more', 'creator', 'contact'] as $intent) {
            /* «más información sobre José Bohórquez» tiene más palabras útiles que un «¿quién eres?» */
            [$maxWords, $maxSpaces] = $intent === 'creator_more' ? [8, 14] : [4, 8];
            if (preg_match(self::INTENTS[$intent]['pattern'], $norm) && count($rest) <= $maxWords && substr_count($norm, ' ') < $maxSpaces) {
                return ['answer' => self::INTENTS[$intent]['answer'], 'source' => 'LOCAL', 'intent' => $intent];
            }
        }
        foreach (['thanks', 'bye', 'greeting'] as $intent) {
            if (preg_match(self::INTENTS[$intent]['pattern'], $norm) && count($rest) === 0) {
                return ['answer' => self::INTENTS[$intent]['answer'], 'source' => 'LOCAL', 'intent' => $intent];
            }
        }

        /* Respuesta corregida por el admin con coincidencia fuerte */
        $qTerms = $this->meaningful($norm);
        if (count($qTerms) >= 2) {
            $best = null; $bestScore = 0.0;
            foreach (SiaKnowledge::where('active', true)->get(['question', 'answer']) as $k) {
                $kTerms = $this->meaningful($this->normalize($k->question));
                if (!$kTerms) continue;
                $inter = count(array_intersect($qTerms, $kTerms));
                $score = $inter / max(count($qTerms), count($kTerms));   // simétrico: evita falsos positivos
                if ($score > $bestScore) { $bestScore = $score; $best = $k; }
            }
            if ($best && $bestScore >= 0.75) {
                return ['answer' => $best->answer, 'source' => 'FAQ', 'intent' => null];
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::of($text)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]/', ' ')->toString()));
    }

    /** términos con contenido (sin relleno), recortados a 5 letras */
    private function meaningful(string $norm): array
    {
        $w = array_filter(explode(' ', $norm), fn ($t) => strlen($t) >= 3 && !in_array($t, self::FILLER, true));
        return array_values(array_unique(array_map(fn ($t) => substr($t, 0, 5), $w)));
    }
}
