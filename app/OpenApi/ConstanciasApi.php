<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API de Solicitud de Constancias',
    description: 'API REST del parcial. El usuario solicita la constancia que necesita y queda pendiente. El administrador le da seguimiento y se la envía por correo. También administra usuarios e informes.'
)]
#[OA\Server(
    url: 'http://localhost:8081',
    description: 'Servidor Docker (Apache)'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token devuelto por POST /api/login. Ejemplo: Bearer 1|token'
)]
#[OA\Tag(name: 'Autenticación', description: 'Login del administrador y del registrador')]
#[OA\Tag(name: 'Constancias', description: 'Registro, listado paginado, detalle, actualización, eliminación, correo y QR')]
#[OA\Tag(name: 'Reportes', description: 'PDF, CSV, resumen por mes y gráfico de activas e inactivas')]
#[OA\Schema(
    schema: 'Usuario',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@constancias.com'),
        new OA\Property(property: 'rol', type: 'string', enum: ['administrador', 'usuario'], example: 'administrador'),
        new OA\Property(property: 'password_temporal', type: 'boolean', example: true),
        new OA\Property(property: 'totp_confirmado', type: 'boolean', example: false),
        new OA\Property(
            property: 'permisos',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['constancias.ver', 'constancias.crear', 'reportes.pdf', 'reportes.grafico']
        ),
    ]
)]
#[OA\Schema(
    schema: 'Constancia',
    properties: [
        new OA\Property(property: 'id_constancias', type: 'integer', example: 1),
        new OA\Property(property: 'nombre_empleado', type: 'string', example: 'María López'),
        new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'maria.lopez@correo.com'),
        new OA\Property(property: 'movil', type: 'string', example: '88881234'),
        new OA\Property(property: 'tipo_de_constancia', type: 'string', example: 'Laboral'),
        new OA\Property(property: 'descripcion', type: 'string', example: 'Constancia laboral para trámite bancario.'),
        new OA\Property(property: 'cantidad', type: 'integer', example: 1),
        new OA\Property(property: 'fecha_de_solicitud', type: 'string', format: 'date', example: '2026-10-23'),
        new OA\Property(property: 'hora_de_solicitud', type: 'string', example: '08:15:00'),
        new OA\Property(property: 'estado_solicitud', type: 'boolean', example: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['Activa', 'Inactiva'], example: 'Activa'),
    ]
)]
#[OA\Schema(
    schema: 'ConstanciaInput',
    required: ['nombre_empleado', 'correo', 'movil', 'tipo_de_constancia', 'descripcion', 'cantidad'],
    properties: [
        new OA\Property(property: 'nombre_empleado', type: 'string', minLength: 3, maxLength: 150, example: 'María López'),
        new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'maria.lopez@correo.com'),
        new OA\Property(property: 'movil', type: 'string', example: '88881234', description: 'Entre 8 y 20 dígitos. Puede incluir +, espacios y guiones.'),
        new OA\Property(property: 'tipo_de_constancia', type: 'string', minLength: 3, maxLength: 100, example: 'Laboral'),
        new OA\Property(property: 'descripcion', type: 'string', minLength: 10, maxLength: 500, example: 'Constancia laboral para trámite bancario.'),
        new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 99, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'ConstanciaUpdate',
    required: ['nombre_empleado', 'correo', 'movil', 'tipo_de_constancia', 'descripcion', 'cantidad', 'estado_solicitud'],
    allOf: [new OA\Schema(ref: '#/components/schemas/ConstanciaInput')],
    properties: [
        new OA\Property(property: 'estado_solicitud', type: 'boolean', example: false, description: 'true = activa, false = inactiva'),
    ]
)]
class ConstanciasApi
{
    #[OA\Post(
        path: '/api/login',
        summary: 'Iniciar sesión',
        description: 'Comprueba el correo y la contraseña. La respuesta trae un token limitado al paso codigo. Después hay que enviar el código de 6 dígitos.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@constancias.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión iniciada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'paso', type: 'string', example: 'codigo'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/Usuario'),
                        new OA\Property(property: 'enrolamiento', type: 'object', nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Credenciales incorrectas o datos inválidos'),
        ]
    )]
    public function login(): void
    {
    }

    #[OA\Post(
        path: '/api/verificar-codigo',
        summary: 'Verificar el código de la app',
        description: 'Usa el token del login. Si el código es válido y la contraseña es temporal, el siguiente paso es cambiar-password.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['codigo'],
                properties: [
                    new OA\Property(property: 'codigo', type: 'string', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Código aceptado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Código inválido'),
        ]
    )]
    public function verificarCodigo(): void
    {
    }

    #[OA\Get(
        path: '/api/enrolamiento',
        summary: 'Código QR para configurar la app',
        description: 'Disponible mientras el usuario no ha confirmado el código. Devuelve el secreto y el QR otpauth.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Datos para la app de autenticación'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function enrolamiento(): void
    {
    }

    #[OA\Post(
        path: '/api/cambiar-password',
        summary: 'Cambiar la contraseña temporal',
        description: 'Obligatorio antes de usar el sistema cuando password_temporal es verdadero.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password_actual', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'password_actual', type: 'string', format: 'password'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Contraseña actualizada y sesión completa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'La contraseña no cumple las reglas'),
        ]
    )]
    public function cambiarPassword(): void
    {
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Usuario autenticado',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del usuario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Usuario'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(): void
    {
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Cerrar sesión',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(): void
    {
    }

    #[OA\Get(
        path: '/api/constancias',
        summary: 'Listar constancias',
        description: 'Solo el administrador. El paginador devuelve 5 registros. Use estado=activa o estado=inactiva.',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'nombre', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtra por nombre del empleado'),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtra por tipo de constancia'),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['activa', 'inactiva'])),
            new OA\Parameter(name: 'mes', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Página de 5 constancias'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'El registrador no puede listar'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Post(
        path: '/api/constancias',
        summary: 'Registrar constancia',
        description: 'Solo el administrador. Elige el usuario dueño. La fecha y la hora se asignan solas. El estado puede quedar activa o pendiente.',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConstanciaInput')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Constancia registrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Post(
        path: '/api/mis-constancias',
        summary: 'Solicitar constancia',
        description: 'Solo el usuario. La solicitud queda pendiente, a su nombre y con su correo. El administrador la ve para darle seguimiento y enviarla.',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['movil', 'tipo_de_constancia', 'descripcion', 'cantidad'],
                properties: [
                    new OA\Property(property: 'movil', type: 'string', example: '55551212'),
                    new OA\Property(property: 'tipo_de_constancia', type: 'string', example: 'Trabajo'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'La necesito para un trámite bancario.'),
                    new OA\Property(property: 'cantidad', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Solicitud creada y pendiente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el usuario'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function solicitar(): void
    {
    }

    #[OA\Get(
        path: '/api/constancias/{id}',
        summary: 'Ver detalle',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la constancia'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 404, description: 'No se encontró el registro'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Put(
        path: '/api/constancias/{id}',
        summary: 'Actualizar constancia',
        description: 'Solo el administrador. La fecha y la hora de solicitud no cambian.',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConstanciaUpdate')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Constancia actualizada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 404, description: 'No se encontró el registro'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/constancias/{id}',
        summary: 'Eliminar constancia',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Constancia eliminada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 404, description: 'No se encontró el registro'),
        ]
    )]
    public function destroy(): void
    {
    }

    #[OA\Post(
        path: '/api/constancias/{id}/enviar-correo',
        summary: 'Enviar correo cuando está lista',
        description: 'Solo el administrador. Envía la constancia al correo de quien la pidió, adjunta el QR y la deja inactiva.',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Correo enviado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 404, description: 'No se encontró el registro'),
        ]
    )]
    public function enviarCorreo(): void
    {
    }

    #[OA\Get(
        path: '/api/constancias/{id}/qr',
        summary: 'Código QR de la constancia',
        tags: ['Constancias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Imagen PNG',
                content: new OA\MediaType(mediaType: 'image/png')
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 404, description: 'No se encontró el registro'),
        ]
    )]
    public function qr(): void
    {
    }

    #[OA\Get(
        path: '/api/reportes/pdf',
        summary: 'Informe PDF por mes',
        description: 'Solo el administrador.',
        tags: ['Reportes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'mes', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PDF en línea',
                content: new OA\MediaType(mediaType: 'application/pdf')
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Mes o año inválido'),
        ]
    )]
    public function pdf(): void
    {
    }

    #[OA\Get(
        path: '/api/reportes/csv',
        summary: 'Informe CSV por mes',
        description: 'Solo el administrador.',
        tags: ['Reportes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'mes', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo CSV',
                content: new OA\MediaType(mediaType: 'text/csv')
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Mes o año inválido'),
        ]
    )]
    public function csv(): void
    {
    }

    #[OA\Get(
        path: '/api/reportes/por-mes',
        summary: 'Cantidad de constancias por mes',
        tags: ['Reportes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resumen de los 12 meses'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function porMes(): void
    {
    }

    #[OA\Get(
        path: '/api/reportes/grafico',
        summary: 'Gráfico de activas e inactivas',
        description: 'Solo el administrador. Hay que seleccionar el mes.',
        tags: ['Reportes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mes', in: 'query', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 10)),
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos para el gráfico',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'anio', type: 'integer', example: 2026),
                        new OA\Property(property: 'mes', type: 'integer', example: 10),
                        new OA\Property(property: 'nombre_mes', type: 'string', example: 'Octubre'),
                        new OA\Property(property: 'activas', type: 'integer', example: 4),
                        new OA\Property(property: 'inactivas', type: 'integer', example: 2),
                        new OA\Property(property: 'total', type: 'integer', example: 6),
                        new OA\Property(property: 'labels', type: 'array', items: new OA\Items(type: 'string'), example: ['Activas', 'Inactivas']),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'integer'), example: [4, 2]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo el administrador'),
            new OA\Response(response: 422, description: 'Debe seleccionar el mes'),
        ]
    )]
    public function grafico(): void
    {
    }
}
