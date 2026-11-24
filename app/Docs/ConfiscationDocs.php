<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class ConfiscationDocs
{
    #[OA\Schema(
        schema: 'Confiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 100),
            new OA\Property(property: 'fecha', type: 'string', example: '2024-01-15'),
            new OA\Property(property: 'observacion', type: 'string', example: 'Operativo nocturno'),
            new OA\Property(property: 'direccion', type: 'string', example: 'Av. Central 123'),
            new OA\Property(property: 'departamento', type: 'string', example: 'Departamento X'),
            new OA\Property(property: 'municipalidad', type: 'string', example: 'Municipio Y'),
            new OA\Property(property: 'latitud', type: 'number', example: 14.12345),
            new OA\Property(property: 'longitud', type: 'number', example: -87.12345),
        ]
    )]
    #[OA\Schema(
        schema: 'ConfiscationCreateRequest',
        type: 'object',
        required: ['fecha', 'observacion', 'direccion', 'departamento', 'municipalidad', 'latitud', 'longitud'],
        properties: [
            new OA\Property(property: 'fecha', type: 'string', example: '2024-01-15'),
            new OA\Property(property: 'observacion', type: 'string', example: 'Operativo nocturno'),
            new OA\Property(property: 'direccion', type: 'string', example: 'Av. Central 123'),
            new OA\Property(property: 'departamento', type: 'string', example: 'Departamento X'),
            new OA\Property(property: 'municipalidad', type: 'string', example: 'Municipio Y'),
            new OA\Property(property: 'latitud', type: 'number', example: 14.12345),
            new OA\Property(property: 'longitud', type: 'number', example: -87.12345),
        ]
    )]
    #[OA\Schema(
        schema: 'ConfiscationUpdateRequest',
        type: 'object',
        required: ['fecha', 'observacion', 'direccion', 'departamento', 'municipalidad', 'latitud', 'longitud'],
        properties: [
            new OA\Property(property: 'fecha', type: 'string', example: '2024-02-01'),
            new OA\Property(property: 'observacion', type: 'string', example: 'Operativo diurno'),
            new OA\Property(property: 'direccion', type: 'string', example: 'Av. Central 456'),
            new OA\Property(property: 'departamento', type: 'string', example: 'Departamento X'),
            new OA\Property(property: 'municipalidad', type: 'string', example: 'Municipio Y'),
            new OA\Property(property: 'latitud', type: 'number', example: 14.54321),
            new OA\Property(property: 'longitud', type: 'number', example: -87.54321),
        ]
    )]
    #[OA\Schema(
        schema: 'MapDrugConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto1.png'),
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 5),
            new OA\Property(property: 'peso', type: 'number', minimum: 0, exclusiveMinimum: true, description: 'Gramos; positivo, hasta dos decimales; almacenamiento DOUBLE sin conversión histórica', example: 2.5),
            new OA\Property(property: 'droga', type: 'integer', example: 10),
            new OA\Property(property: 'droga_nombre', type: 'string', example: 'Cocaína'),
        ]
    )]
    #[OA\Schema(
        schema: 'MapWeaponConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 2),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto2.png'),
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 5),
            new OA\Property(property: 'arma_nombre', type: 'string', example: 'Pistola 9mm'),
        ]
    )]
    #[OA\Schema(
        schema: 'MapAmmunitionConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 3),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto3.png'),
            new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, maximum: 2147483647, example: 100),
            new OA\Property(property: 'municion', type: 'integer', example: 3),
            new OA\Property(property: 'municion_nombre', type: 'string', example: 'Cartucho 5.56mm'),
        ]
    )]
    #[OA\Schema(
        schema: 'MapConfiscationItem',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'fecha', type: 'string'),
            new OA\Property(property: 'observacion', type: 'string'),
            new OA\Property(property: 'latitud', type: 'number'),
            new OA\Property(property: 'longitud', type: 'number'),
            new OA\Property(property: 'drug_confiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapDrugConfiscation')),
            new OA\Property(property: 'weapon_confiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapWeaponConfiscation')),
            new OA\Property(property: 'ammunition_confiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapAmmunitionConfiscation')),
        ]
    )]
    #[OA\Schema(
        schema: 'MapConfiscationResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'mapItems', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapConfiscationItem')),
        ]
    )]
    #[OA\Get(
        path: '/api/confiscation',
        operationId: 'Confiscation_index',
        tags: ['Confiscation'],
        summary: 'Listar registros',
        description: 'Requiere confiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'fecha', 'observacion', 'direccion', 'departamento', 'municipalidad', 'latitud', 'longitud']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', maxItems: 0, uniqueItems: true, items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                new OA\Property(property: 'observacion', type: 'string'),
                new OA\Property(property: 'direccion', type: 'string'),
                new OA\Property(property: 'departamento', type: 'string'),
                new OA\Property(property: 'municipalidad', type: 'string'),
                new OA\Property(property: 'latitud', type: 'number'),
                new OA\Property(property: 'longitud', type: 'number'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Confiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/confiscation/deleted',
        operationId: 'Confiscation_indexDeleted',
        tags: ['Confiscation'],
        summary: 'Listar eliminados',
        description: 'Requiere confiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'fecha', 'observacion', 'direccion', 'departamento', 'municipalidad', 'latitud', 'longitud']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', maxItems: 0, uniqueItems: true, items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                new OA\Property(property: 'observacion', type: 'string'),
                new OA\Property(property: 'direccion', type: 'string'),
                new OA\Property(property: 'departamento', type: 'string'),
                new OA\Property(property: 'municipalidad', type: 'string'),
                new OA\Property(property: 'latitud', type: 'number'),
                new OA\Property(property: 'longitud', type: 'number'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Confiscation'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function indexDeleted(): void {}

    #[OA\Post(
        path: '/api/confiscation',
        operationId: 'Confiscation_store',
        tags: ['Confiscation'],
        summary: 'Crear registro',
        description: 'Requiere confiscation.create. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.create'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ConfiscationCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
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
        path: '/api/confiscation/{confiscation}',
        operationId: 'Confiscation_show',
        tags: ['Confiscation'],
        summary: 'Consultar registro',
        description: 'Requiere confiscation.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/confiscation/{confiscation}',
        operationId: 'Confiscation_update',
        tags: ['Confiscation'],
        summary: 'Actualizar registro (PUT)',
        description: 'Requiere confiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ConfiscationUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
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
        path: '/api/confiscation/{confiscation}',
        operationId: 'Confiscation_patch',
        tags: ['Confiscation'],
        summary: 'Actualizar registro (PATCH)',
        description: 'Requiere confiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ConfiscationUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
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
        path: '/api/confiscation/{confiscation}',
        operationId: 'Confiscation_destroy',
        tags: ['Confiscation'],
        summary: 'Eliminar lógicamente',
        description: 'Requiere confiscation.delete. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.delete'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
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
        path: '/api/confiscation/restore/{confiscation}',
        operationId: 'Confiscation_restore',
        tags: ['Confiscation'],
        summary: 'Restaurar registro',
        description: 'Requiere confiscation.update. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'confiscation.update'],
        security: [['bearerAuth' => []], ['authCookie' => [], 'csrfHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation')),
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
        path: '/api/confiscation/map',
        operationId: 'MapConfiscationResponse_mapConfiscations',
        tags: ['MapConfiscationResponse'],
        summary: 'Consultar mapa filtrado',
        description: 'Requiere map.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'map.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'drugs', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Array JSON o nativo de IDs; ausente/[] no selecciona ese tipo. Tipos combinados con OR', example: '[1]')),
            new OA\Parameter(name: 'weapons', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Array JSON o nativo de IDs; ausente/[] no selecciona ese tipo. Tipos combinados con OR', example: '[1]')),
            new OA\Parameter(name: 'ammunitions', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Array JSON o nativo de IDs; ausente/[] no selecciona ese tipo. Tipos combinados con OR', example: '[1]')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MapConfiscationResponse')),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function mapConfiscations(): void {}
}
