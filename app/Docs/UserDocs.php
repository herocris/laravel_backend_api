<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

// Documentación de endpoints de Usuario aislada del controlador real.
// Esta clase NO se usa en la lógica; solo existe para que swagger-php escanee atributos.
class UserDocs
{
    // Schemas trasladados desde el modelo y CommonSchemas
    #[OA\Schema(
        schema: 'User',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
            new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'juan@example.com'),
            new OA\Property(
                property: 'roles',
                type: 'array',
                items: new OA\Items(type: 'integer', example: 1),
                example: [1, 2],
                description: 'IDs de roles asignados',
                readOnly: true
            ),
            new OA\Property(
                property: 'permisos',
                type: 'array',
                items: new OA\Items(type: 'integer', example: 1),
                example: [1, 2, 3],
                description: 'IDs de permisos asignados',
                readOnly: true
            ),
        ]
    )]
    #[OA\Schema(
        schema: 'UserCreateRequest',
        type: 'object',
        required: ['nombre', 'correo', 'password'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
            new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'juan@example.com'),
            new OA\Property(property: 'password', type: 'string', minLength: 6, writeOnly: true),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs activos api; omitir conserva, [] revoca en actualización'),
            new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs activos api; omitir conserva, [] revoca en actualización'),
        ]
    )]
    #[OA\Schema(
        schema: 'UserUpdateRequest',
        type: 'object',
        required: ['nombre', 'correo', 'password'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
            new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'juan@example.com'),
            new OA\Property(property: 'password', type: 'string', minLength: 6, writeOnly: true),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs activos api; omitir conserva, [] revoca en actualización'),
            new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs activos api; omitir conserva, [] revoca en actualización'),
        ]
    )]
    #[OA\Get(
        path: '/api/user',
        operationId: 'User_index',
        tags: ['User'],
        summary: 'Listar registros',
        description: 'Requiere user.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'nombre', 'correo']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', uniqueItems: true, items: new OA\Items(type: 'string', enum: ['permisos', 'roles']))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'nombre', type: 'string'),
                new OA\Property(property: 'correo', type: 'string'),
                new OA\Property(property: 'permiso', type: 'integer'),
                new OA\Property(property: 'rol', type: 'integer'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/user/deleted',
        operationId: 'User_indexDeleted',
        tags: ['User'],
        summary: 'Listar eliminados',
        description: 'Requiere user.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'nombre', 'correo']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', uniqueItems: true, items: new OA\Items(type: 'string', enum: ['permisos', 'roles']))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'nombre', type: 'string'),
                new OA\Property(property: 'correo', type: 'string'),
                new OA\Property(property: 'permiso', type: 'integer'),
                new OA\Property(property: 'rol', type: 'integer'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexDeleted(): void {}

    #[OA\Post(
        path: '/api/user',
        operationId: 'User_store',
        tags: ['User'],
        summary: 'Crear registro',
        description: 'Requiere user.create. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.create'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/user/{user}',
        operationId: 'User_show',
        tags: ['User'],
        summary: 'Consultar registro',
        description: 'Requiere user.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/user/{user}',
        operationId: 'User_update',
        tags: ['User'],
        summary: 'Actualizar registro (PUT)',
        description: 'Requiere user.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function update(): void {}

    #[OA\Patch(
        path: '/api/user/{user}',
        operationId: 'User_patch',
        tags: ['User'],
        summary: 'Actualizar registro (PATCH)',
        description: 'Requiere user.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function patch(): void {}

    #[OA\Delete(
        path: '/api/user/{user}',
        operationId: 'User_destroy',
        tags: ['User'],
        summary: 'Eliminar lógicamente',
        description: 'Requiere user.delete. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.delete'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function destroy(): void {}

    #[OA\Post(
        path: '/api/user/restore/{user}',
        operationId: 'User_restore',
        tags: ['User'],
        summary: 'Restaurar registro',
        description: 'Requiere user.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'user.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function restore(): void {}
}
