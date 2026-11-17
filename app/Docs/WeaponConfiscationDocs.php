<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class WeaponConfiscationDocs
{
    #[OA\Schema(
        schema: 'WeaponConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 11),
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 4),
            new OA\Property(property: 'foto', type: 'string', nullable: true, example: 'arma.png'),
            new OA\Property(
                property: 'decomiso',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 3),
                    new OA\Property(property: 'observacion', type: 'string', example: 'Incautación nocturna'),
                ]
            ),
            new OA\Property(
                property: 'arma',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 8),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Pistola 9mm'),
                ]
            ),
        ]
    )]
    #[OA\Schema(
        schema: 'WeaponConfiscationCreateRequest',
        type: 'object',
        required: ['cantidad', 'foto', 'decomiso', 'arma'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 4),
            new OA\Property(property: 'foto', type: 'string', format: 'binary'),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 8),
        ]
    )]
    #[OA\Schema(
        schema: 'WeaponConfiscationUpdateRequest',
        type: 'object',
        required: ['cantidad', 'decomiso', 'arma'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 5),
            new OA\Property(property: 'foto', type: 'string', format: 'binary', nullable: true),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 8),
        ]
    )]
    #[OA\Get(
        path: '/api/weaponConfiscation',
        operationId: 'WeaponConfiscation_index',
        tags: ['WeaponConfiscation'],
        summary: 'Listar registros',
        description: 'Requiere weaponConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver LIST_FIELDS del controlador')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/weaponConfiscation/deleted',
        operationId: 'WeaponConfiscation_indexDeleted',
        tags: ['WeaponConfiscation'],
        summary: 'Listar eliminados',
        description: 'Requiere weaponConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver LIST_FIELDS del controlador')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexDeleted(): void {}

    #[OA\Post(
        path: '/api/weaponConfiscation',
        operationId: 'WeaponConfiscation_store',
        tags: ['WeaponConfiscation'],
        summary: 'Crear registro',
        description: 'Requiere weaponConfiscation.create. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.create'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/WeaponConfiscationCreateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
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
        path: '/api/weaponConfiscation/{weaponConfiscation}',
        operationId: 'WeaponConfiscation_show',
        tags: ['WeaponConfiscation'],
        summary: 'Consultar registro',
        description: 'Requiere weaponConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/weaponConfiscation/{weaponConfiscation}',
        operationId: 'WeaponConfiscation_update',
        tags: ['WeaponConfiscation'],
        summary: 'Actualizar registro (PUT)',
        description: 'Requiere weaponConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/WeaponConfiscationUpdateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
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
        path: '/api/weaponConfiscation/{weaponConfiscation}',
        operationId: 'WeaponConfiscation_patch',
        tags: ['WeaponConfiscation'],
        summary: 'Actualizar registro (PATCH)',
        description: 'Requiere weaponConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/WeaponConfiscationUpdateRequest'))),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
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
        path: '/api/weaponConfiscation/{weaponConfiscation}',
        operationId: 'WeaponConfiscation_destroy',
        tags: ['WeaponConfiscation'],
        summary: 'Eliminar lógicamente',
        description: 'Requiere weaponConfiscation.delete. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.delete'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
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
        path: '/api/weaponConfiscation/restore/{weaponConfiscation}',
        operationId: 'WeaponConfiscation_restore',
        tags: ['WeaponConfiscation'],
        summary: 'Restaurar registro',
        description: 'Requiere weaponConfiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
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
        path: '/api/weaponConfiscation/{idConfiscation}/confiscation',
        operationId: 'WeaponConfiscation_indexByConfiscation',
        tags: ['WeaponConfiscation'],
        summary: 'Listar detalles de un decomiso',
        description: 'Requiere weaponConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'idConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver LIST_FIELDS del controlador')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexByConfiscation(): void {}

    #[OA\Get(
        path: '/api/weaponConfiscation/{weaponConfiscation}/photo',
        operationId: 'WeaponConfiscation_photo',
        tags: ['WeaponConfiscation'],
        summary: 'Descargar evidencia privada PNG',
        description: 'Requiere weaponConfiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'weaponConfiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
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
        path: '/api/weaponGraphIndex',
        operationId: 'WeaponConfiscation_graphIndex',
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
            new OA\Parameter(name: 'weapons', in: 'query', required: true, schema: new OA\Schema(type: 'string', description: 'Array JSON de IDs únicos positivos existentes; [] devuelve series vacías. También admite array nativo', example: '[1]')),
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
