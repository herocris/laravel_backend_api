<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class PermissionDocs
{
    #[OA\Schema(
        schema: 'Permission',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'nombre', type: 'string', example: 'confiscation.view'),
        ]
    )]
    #[OA\Schema(
        schema: 'PermissionCreateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'confiscation.view'),
        ]
    )]
    #[OA\Schema(
        schema: 'PermissionUpdateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'confiscation.view'),
        ]
    )]
    #[OA\Get(
        path: '/api/permission',
        operationId: 'Permission_index',
        tags: ['Permission'],
        summary: 'Listar registros',
        description: 'Requiere permission.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'nombre']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', maxItems: 0, uniqueItems: true, items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'nombre', type: 'string'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/permission/deleted',
        operationId: 'Permission_indexDeleted',
        tags: ['Permission'],
        summary: 'Listar eliminados',
        description: 'Requiere permission.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'nombre']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', maxItems: 0, uniqueItems: true, items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'nombre', type: 'string'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexDeleted(): void {}

    #[OA\Post(
        path: '/api/permission',
        operationId: 'Permission_store',
        tags: ['Permission'],
        summary: 'Crear registro',
        description: 'Requiere permission.create. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.create'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PermissionCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
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
        path: '/api/permission/{permission}',
        operationId: 'Permission_show',
        tags: ['Permission'],
        summary: 'Consultar registro',
        description: 'Requiere permission.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/permission/{permission}',
        operationId: 'Permission_update',
        tags: ['Permission'],
        summary: 'Actualizar registro (PUT)',
        description: 'Requiere permission.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PermissionUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
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
        path: '/api/permission/{permission}',
        operationId: 'Permission_patch',
        tags: ['Permission'],
        summary: 'Actualizar registro (PATCH)',
        description: 'Requiere permission.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PermissionUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
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
        path: '/api/permission/{permission}',
        operationId: 'Permission_destroy',
        tags: ['Permission'],
        summary: 'Eliminar lógicamente',
        description: 'Requiere permission.delete. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.delete'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
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
        path: '/api/permission/restore/{permission}',
        operationId: 'Permission_restore',
        tags: ['Permission'],
        summary: 'Restaurar registro',
        description: 'Requiere permission.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Las escrituras comprueban delegación: el actor solo puede asignar capacidades api que posee y gestionar objetivos cuyos privilegios cubre, incluidos los dormidos. Renombrar o restaurar permisos asignados no permite conceder capacidades no poseídas; se rechaza con 403. Se conservan IDs y contratos JSON. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'permission.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
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
