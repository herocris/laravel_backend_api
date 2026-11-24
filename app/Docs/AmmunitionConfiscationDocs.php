<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class AmmunitionConfiscationDocs
{
    #[OA\Schema(
        schema: 'AmmunitionConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 12),
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 100),
            new OA\Property(property: 'foto', type: 'string', nullable: true, example: 'municion.png'),
            new OA\Property(
                property: 'decomiso',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 3),
                    new OA\Property(property: 'observacion', type: 'string', example: 'Hallazgo en vehículo'),
                ]
            ),
            new OA\Property(
                property: 'municion',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 9),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Calibre 9mm'),
                ]
            ),
        ]
    )]
    #[OA\Schema(
        schema: 'AmmunitionConfiscationCreateRequest',
        type: 'object',
        required: ['cantidad', 'foto', 'decomiso', 'municion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 100),
            new OA\Property(property: 'foto', type: 'string', format: 'binary'),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'municion', type: 'integer', example: 9),
        ]
    )]
    #[OA\Schema(
        schema: 'AmmunitionConfiscationUpdateRequest',
        type: 'object',
        required: ['cantidad', 'decomiso', 'municion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 110),
            new OA\Property(property: 'foto', type: 'string', format: 'binary', nullable: true),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'municion', type: 'integer', example: 9),
        ]
    )]
    #[OA\Get(
        path: '/api/ammunitionConfiscation',
        operationId: 'AmmunitionConfiscation_index',
        tags: ['AmmunitionConfiscation'],
        summary: 'Listar registros',
        description: 'Requiere ammunitionConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'cantidad', 'foto']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', uniqueItems: true, items: new OA\Items(type: 'string', enum: ['decomiso', 'municion']))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'cantidad', type: 'integer'),
                new OA\Property(property: 'foto', type: 'string'),
                new OA\Property(property: 'decomiso', type: 'integer'),
                new OA\Property(property: 'decomiso_descripcion', type: 'string'),
                new OA\Property(property: 'municion', type: 'integer'),
                new OA\Property(property: 'municion_descripcion', type: 'string'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/ammunitionConfiscation/deleted',
        operationId: 'AmmunitionConfiscation_indexDeleted',
        tags: ['AmmunitionConfiscation'],
        summary: 'Listar eliminados',
        description: 'Requiere ammunitionConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'cantidad', 'foto']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', uniqueItems: true, items: new OA\Items(type: 'string', enum: ['decomiso', 'municion']))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'cantidad', type: 'integer'),
                new OA\Property(property: 'foto', type: 'string'),
                new OA\Property(property: 'decomiso', type: 'integer'),
                new OA\Property(property: 'decomiso_descripcion', type: 'string'),
                new OA\Property(property: 'municion', type: 'integer'),
                new OA\Property(property: 'municion_descripcion', type: 'string'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexDeleted(): void {}

    #[OA\Post(
        path: '/api/ammunitionConfiscation',
        operationId: 'AmmunitionConfiscation_store',
        tags: ['AmmunitionConfiscation'],
        summary: 'Crear registro',
        description: 'Requiere ammunitionConfiscation.create. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.create'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/AmmunitionConfiscationCreateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
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
        path: '/api/ammunitionConfiscation/{ammunitionConfiscation}',
        operationId: 'AmmunitionConfiscation_show',
        tags: ['AmmunitionConfiscation'],
        summary: 'Consultar registro',
        description: 'Requiere ammunitionConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/ammunitionConfiscation/{ammunitionConfiscation}',
        operationId: 'AmmunitionConfiscation_update',
        tags: ['AmmunitionConfiscation'],
        summary: 'Actualizar registro (PUT)',
        description: 'Requiere ammunitionConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/AmmunitionConfiscationUpdateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
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
        path: '/api/ammunitionConfiscation/{ammunitionConfiscation}',
        operationId: 'AmmunitionConfiscation_patch',
        tags: ['AmmunitionConfiscation'],
        summary: 'Actualizar registro (PATCH)',
        description: 'Requiere ammunitionConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/AmmunitionConfiscationUpdateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
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
        path: '/api/ammunitionConfiscation/{ammunitionConfiscation}',
        operationId: 'AmmunitionConfiscation_destroy',
        tags: ['AmmunitionConfiscation'],
        summary: 'Eliminar lógicamente',
        description: 'Requiere ammunitionConfiscation.delete. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.delete'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
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
        path: '/api/ammunitionConfiscation/restore/{ammunitionConfiscation}',
        operationId: 'AmmunitionConfiscation_restore',
        tags: ['AmmunitionConfiscation'],
        summary: 'Restaurar registro',
        description: 'Requiere ammunitionConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 419, description: 'CsrfResponse', content: new OA\JsonContent(ref: '#/components/schemas/CsrfResponse')),
        ]
    )]
    public function restore(): void {}

    #[OA\Get(
        path: '/api/ammunitionConfiscation/{idConfiscation}/confiscation',
        operationId: 'AmmunitionConfiscation_indexByConfiscation',
        tags: ['AmmunitionConfiscation'],
        summary: 'Listar detalles de un decomiso',
        description: 'Requiere ammunitionConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'idConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'cantidad', 'foto']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', uniqueItems: true, items: new OA\Items(type: 'string', enum: ['decomiso', 'municion']))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'cantidad', type: 'integer'),
                new OA\Property(property: 'foto', type: 'string'),
                new OA\Property(property: 'decomiso', type: 'integer'),
                new OA\Property(property: 'decomiso_descripcion', type: 'string'),
                new OA\Property(property: 'municion', type: 'integer'),
                new OA\Property(property: 'municion_descripcion', type: 'string'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexByConfiscation(): void {}

    #[OA\Get(
        path: '/api/ammunitionConfiscation/{ammunitionConfiscation}/photo',
        operationId: 'AmmunitionConfiscation_photo',
        tags: ['AmmunitionConfiscation'],
        summary: 'Descargar evidencia privada PNG',
        description: 'Requiere ammunitionConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'ammunitionConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\MediaType(mediaType: 'image/png', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function photo(): void {}

    #[OA\Get(
        path: '/api/ammunitionGraphIndex',
        operationId: 'AmmunitionConfiscation_graphIndex',
        tags: ['GraphResponse'],
        summary: 'Consultar estadísticas de calendario',
        description: 'Requiere graph.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'graph.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'period', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['month', 'quarter', 'semester', 'year'], description: 'Calendario: YYYY-MM, YYYY-Q1…Q4, YYYY-S1…S2, YYYY')),
            new OA\Parameter(name: 'typeGraph', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['bar', 'line', 'pie'])),
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', description: 'Inclusiva; mayor o igual a start_date')),
            new OA\Parameter(name: 'ammunitions', in: 'query', required: true, schema: new OA\Schema(type: 'string', description: 'Array JSON de IDs únicos positivos existentes; [] devuelve series vacías. También admite array nativo', example: '[1]')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/GraphResponse')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function graphIndex(): void {}
}
