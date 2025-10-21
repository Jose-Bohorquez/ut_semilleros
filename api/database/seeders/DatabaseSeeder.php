<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{
    Role,
    User,
    Departamento,
    Ciudad,
    Facultad,
    Programa,
    CentroTutorial,
    CatPrograma,
    Semillero,
    Proyecto
};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🌱 Roles base
        $roles = [
            ['nombre' => 'admin', 'descripcion' => 'Administrador del sistema'],
            ['nombre' => 'docente', 'descripcion' => 'Coordinador o líder de semillero'],
            ['nombre' => 'estudiante', 'descripcion' => 'Miembro participante de semillero'],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['nombre' => $r['nombre']], $r);
        }

        // 🌎 Departamentos y Ciudades
        $dep = Departamento::create(['nombre' => 'Cundinamarca']);
        Ciudad::insert([
            ['nombre' => 'Bogotá', 'departamento_id' => $dep->id],
            ['nombre' => 'Soacha', 'departamento_id' => $dep->id],
            ['nombre' => 'Zipaquirá', 'departamento_id' => $dep->id],
        ]);

        // 🏛️ Facultades
        $fac1 = Facultad::create(['nombre' => 'Ingeniería', 'descripcion' => 'Facultad de Ingeniería']);
        $fac2 = Facultad::create(['nombre' => 'Educación', 'descripcion' => 'Facultad de Educación']);
        $fac3 = Facultad::create(['nombre' => 'Ciencias', 'descripcion' => 'Facultad de Ciencias Naturales']);

        // 🎓 Programas
        $prog1 = Programa::create(['nombre' => 'Ingeniería de Sistemas', 'facultad_id' => $fac1->id, 'codigo' => 'INGSIS01', 'descripcion' => 'Programa de ingeniería de sistemas']);
        $prog2 = Programa::create(['nombre' => 'Licenciatura en Matemáticas', 'facultad_id' => $fac2->id, 'codigo' => 'LICMAT01', 'descripcion' => 'Programa de educación matemática']);
        $prog3 = Programa::create(['nombre' => 'Biología', 'facultad_id' => $fac3->id, 'codigo' => 'BIO01', 'descripcion' => 'Programa de ciencias biológicas']);

        // 🏫 Centros Tutoriales
        $cat1 = CentroTutorial::create(['nombre' => 'CAT Kennedy', 'direccion' => 'Cra 80 #45-20', 'telefono' => '3201234567', 'email' => 'kennedy@ut.edu.co', 'ciudad_id' => 1]);
        $cat2 = CentroTutorial::create(['nombre' => 'CAT Suba', 'direccion' => 'Calle 145 #90-12', 'telefono' => '3105556677', 'email' => 'suba@ut.edu.co', 'ciudad_id' => 1]);
        $cat3 = CentroTutorial::create(['nombre' => 'CAT Tunal', 'direccion' => 'Av Boyacá #40-30', 'telefono' => '3114448899', 'email' => 'tunal@ut.edu.co', 'ciudad_id' => 1]);

        // 🔗 Relación CAT ↔ Programa
        CatPrograma::create(['centro_tutorial_id' => $cat1->id, 'programa_id' => $prog1->id, 'jornada' => 'Nocturna', 'modalidad' => 'Distancia']);
        CatPrograma::create(['centro_tutorial_id' => $cat2->id, 'programa_id' => $prog2->id, 'jornada' => 'Fines de Semana', 'modalidad' => 'Virtual']);
        CatPrograma::create(['centro_tutorial_id' => $cat3->id, 'programa_id' => $prog3->id, 'jornada' => 'Diurna', 'modalidad' => 'Presencial']);

        // 🧾 Tipos de documento
        \DB::table('tipos_documento')->insert([
            ['nombre' => 'Cédula de Ciudadanía', 'abreviatura' => 'CC'],
            ['nombre' => 'Tarjeta de Identidad', 'abreviatura' => 'TI'],
            ['nombre' => 'Cédula de Extranjería', 'abreviatura' => 'CE'],
        ]);

        // 👥 Usuarios base (usando estructura real de tu tabla)
        $usuarios = [
            [
                'tipo_documento_id' => 1,
                'numero_documento' => '100000001',
                'primer_nombre' => 'Admin',
                'segundo_nombre' => 'Principal',
                'primer_apellido' => 'Sistema',
                'segundo_apellido' => 'UT',
                'correo_personal' => 'admin@ut.edu.co',
                'correo_institucional' => 'admin@ut.edu.co',
                'username' => 'admin',
                'password' => Hash::make('12345678'),
                'programa_id' => $prog1->id,
                'semestre' => 0,
                'ciudad_id' => 1,
                'estado' => 'Activo',
                'role_id' => 1
            ],
            [
                'tipo_documento_id' => 1,
                'numero_documento' => '100000002',
                'primer_nombre' => 'Luis',
                'segundo_nombre' => 'Docente',
                'primer_apellido' => 'Bohórquez',
                'segundo_apellido' => 'Delgado',
                'correo_personal' => 'docente@ut.edu.co',
                'correo_institucional' => 'docente@ut.edu.co',
                'username' => 'docente',
                'password' => Hash::make('12345678'),
                'programa_id' => $prog2->id,
                'semestre' => 0,
                'ciudad_id' => 1,
                'estado' => 'Activo',
                'role_id' => 2
            ],
            [
                'tipo_documento_id' => 1,
                'numero_documento' => '100000003',
                'primer_nombre' => 'Estudiante',
                'segundo_nombre' => 'Activo',
                'primer_apellido' => 'UT',
                'segundo_apellido' => 'Prueba',
                'correo_personal' => 'estudiante@ut.edu.co',
                'correo_institucional' => 'estudiante@ut.edu.co',
                'username' => 'estudiante',
                'password' => Hash::make('12345678'),
                'programa_id' => $prog3->id,
                'semestre' => 1,
                'ciudad_id' => 1,
                'estado' => 'Activo',
                'role_id' => 3
            ]
        ];
        foreach ($usuarios as $u) {
            User::create($u);
        }

        // 🔬 Semilleros
        $semi1 = Semillero::create(['nombre' => 'Semillero Alpha', 'descripcion' => 'Investigación en IA', 'programa_id' => $prog1->id, 'docente_id' => 2, 'centro_tutorial_id' => $cat1->id]);
        $semi2 = Semillero::create(['nombre' => 'Semillero Beta', 'descripcion' => 'Innovación Educativa', 'programa_id' => $prog2->id, 'docente_id' => 2, 'centro_tutorial_id' => $cat2->id]);
        $semi3 = Semillero::create(['nombre' => 'Semillero Gamma', 'descripcion' => 'Ecología Tropical', 'programa_id' => $prog3->id, 'docente_id' => 2, 'centro_tutorial_id' => $cat3->id]);

        // 📚 Proyectos
        Proyecto::create(['titulo' => 'Sistema de gestión de semilleros', 'descripcion' => 'Desarrollo con Laravel', 'semillero_id' => $semi1->id]);
        Proyecto::create(['titulo' => 'Plataforma educativa UT Virtual', 'descripcion' => 'Investigación en aprendizaje digital', 'semillero_id' => $semi2->id]);
        Proyecto::create(['titulo' => 'Catálogo de flora regional', 'descripcion' => 'Proyecto de biodiversidad', 'semillero_id' => $semi3->id]);
    }
}
