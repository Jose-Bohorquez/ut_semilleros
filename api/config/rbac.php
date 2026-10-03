<?php

/*
|--------------------------------------------------------------------------
| Catálogo RBAC granular (permisos por módulo + acción)
|--------------------------------------------------------------------------
|
| Fuente de verdad de QUÉ se puede asignar. Cada módulo tiene una lista de
| acciones. RbacSeeder crea una fila en `permissions` por cada combinación
| módulo+acción de aquí, y le da a cada rol exactamente los permisos que ya
| tenía por el `role:` hardcodeado en api/routes/api.php (2026-09-29), para
| que activar el nuevo sistema no cambie el acceso de nadie.
|
| ADMIN_SISTEMA siempre tiene acceso total: no se le puede quitar un permiso
| (ver App\Services\Rbac\PermissionResolver::can()).
|
| Agregar un módulo o acción nuevo aquí NO lo aplica solo: hay que además
| poner `->middleware('permission:modulo,accion')` en la ruta. Mientras eso
| no se haga, el `role:` de la ruta sigue mandando (conviven a propósito).
|
*/

return [

    'modules' => [

        'users' => [
            'label' => 'Usuarios',
            'actions' => [
                'view'          => 'Ver listado',
                'view_detail'   => 'Ver detalle',
                'create'        => 'Crear / importar',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'faculties' => [
            'label' => 'Facultades',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'programs' => [
            'label' => 'Programas',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'cats' => [
            'label' => 'CAT',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'areas' => [
            'label' => 'Áreas de conocimiento',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'groups' => [
            'label' => 'Grupos',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'coordinators' => [
            'label' => 'Coordinadores',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'seedbeds' => [
            'label' => 'Semilleros',
            'actions' => [
                'view'           => 'Ver listado y detalle',
                'create'         => 'Crear',
                'update'         => 'Editar',
                'toggle_status'  => 'Activar / inactivar',
                'manage_members' => 'Gestionar integrantes',
            ],
        ],

        'objectives' => [
            'label' => 'Objetivos',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
                'delete'        => 'Eliminar',
            ],
        ],

        'results' => [
            'label' => 'Resultados',
            'actions' => [
                'view'          => 'Ver',
                'create'        => 'Crear',
                'update'        => 'Editar',
                'toggle_status' => 'Activar / inactivar',
            ],
        ],

        'requests' => [
            'label' => 'Solicitudes de vinculación',
            'actions' => [
                'view'     => 'Ver todas',
                'view_own' => 'Ver las propias',
                'create'   => 'Crear',
                'update'   => 'Editar',
                'review'   => 'Aprobar / rechazar',
            ],
        ],

        'proposals' => [
            'label' => 'Propuestas',
            'actions' => [
                'view'     => 'Ver todas',
                'view_own' => 'Ver las propias',
                'create'   => 'Crear',
                'update'   => 'Editar',
                'review'   => 'Aprobar / rechazar',
            ],
        ],

        'projects' => [
            'label' => 'Proyectos',
            'actions' => [
                'view'           => 'Ver',
                'create'         => 'Crear',
                'update'         => 'Editar',
                'manage_members' => 'Gestionar integrantes',
            ],
        ],

        'products' => [
            'label' => 'Productos',
            'actions' => [
                'view'   => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar',
            ],
        ],

        'audits' => [
            'label' => 'Auditoría',
            'actions' => [
                'view' => 'Consultar',
            ],
        ],

        'notifications' => [
            'label' => 'Notificaciones internas',
            'actions' => [
                'send' => 'Enviar',
            ],
        ],

        'sia' => [
            'label' => 'SIA (asistente)',
            'actions' => [
                'view_stats'      => 'Ver estadísticas de uso',
                'view_feedback'   => 'Ver conversaciones y calificaciones',
                'curate'          => 'Retroalimentar (corregir respuestas, revisar conversaciones)',
                'manage_settings' => 'Configurar límites y ajustes',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Permisos por defecto de cada rol (estado actual del sistema, 2026-09-29)
    |--------------------------------------------------------------------------
    |
    | "*" = todas las acciones del módulo. Espejo exacto de los `role:` en
    | api/routes/api.php al momento de crear este catálogo — ver ese archivo
    | si hay dudas de por qué un rol tiene o no un permiso.
    |
    */

    'role_defaults' => [

        'ADMIN_SISTEMA' => ['*'], // acceso total, siempre (hardcodeado en el resolver)

        'ADMINISTRATIVO' => [
            'users'         => ['view'],
            'faculties'     => ['view'],
            'programs'      => ['view'],
            'cats'          => ['view'],
            'areas'         => ['view'],
            'groups'        => ['view'],
            'coordinators'  => ['view'],
            'seedbeds'      => ['view', 'create', 'update', 'toggle_status'],
            'objectives'    => ['view', 'create', 'update', 'toggle_status', 'delete'],
            'results'       => ['view', 'create', 'update', 'toggle_status'],
            'requests'      => ['view', 'create', 'update', 'review'],
            'proposals'     => ['view', 'review'],   // CU27: solo el Administrativo evalúa
            'projects'      => ['view', 'create', 'update', 'manage_members'],
            'products'      => ['view', 'create', 'update'],
            'notifications' => ['send'],
        ],

        'LIDER_SEMILLERO' => [
            'users'         => ['view'],
            'faculties'     => ['view'],
            'programs'      => ['view'],
            'cats'          => ['view'],
            'areas'         => ['view'],
            'groups'        => ['view'],
            'coordinators'  => ['view', 'create', 'update'],
            'seedbeds'      => ['view', 'create', 'update', 'toggle_status', 'manage_members'],
            'objectives'    => ['view', 'create', 'update', 'toggle_status', 'delete'],
            'results'       => ['view', 'create', 'update', 'toggle_status'],
            'requests'      => ['view', 'create', 'update', 'review'],
            'proposals'     => ['view'],   // CU27-A1: el Líder solo consulta (las de las áreas de sus semilleros)
            'projects'      => ['view', 'create', 'update', 'manage_members'],
            'products'      => ['view', 'create', 'update'],
            'notifications' => ['send'],
        ],

        'ESTUDIANTE' => [
            'areas'      => ['view'],
            'seedbeds'   => ['view'],
            'objectives' => ['view'],
            'requests'   => ['view_own', 'create'],
            'proposals'  => ['view_own', 'create', 'update'],
        ],

    ],

];
