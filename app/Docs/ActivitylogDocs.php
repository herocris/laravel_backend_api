<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class ActivitylogDocs
{
    #[OA\Schema(
        schema: 'Activitylog',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'tipo_de_evento', type: 'string', example: 'created'),
            new OA\Property(property: 'descripcion', type: 'string', example: 'user'),
            new OA\Property(property: 'id_usuario', type: 'integer', example: 5, nullable: true),
            new OA\Property(property: 'usuario', type: 'string', example: 'user'),
            new OA\Property(property: 'cambios', type: 'object', example: ['name' => 'Juan', 'email' => 'juan@example.com']),
            new OA\Property(property: 'fecha', type: 'string', format: 'date-time'),
        ]
    )]
    #[OA\Get(
        path: '/api/activity',
        operationId: 'Activitylog___invoke',
        tags: ['Activitylog'],
        summary: 'Consultar auditoría',
        description: 'Requiere activity.view. Cookie JWT: CSRF en escrituras; Bearer: sin CSRF. Ver README para los contratos y límites. Para actualizar archivos, enviar POST multipart con _method=PUT.',
        x: ['permission' => 'activity.view'],
        security: [['bearerAuth' => []], ['authCookie' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Búsqueda parcial literal, sin distinguir mayúsculas, en textos y relaciones permitidos; se combina con los demás filtros.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Campos escalares públicos. CSV o fields[]. Siempre incluye identificador. Omitir conserva todos los campos.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', minItems: 1, uniqueItems: true, items: new OA\Items(type: 'string', enum: ['identificador', 'tipo_de_evento', 'descripcion', 'id_usuario', 'usuario', 'fecha', 'cambios']))),
            new OA\Parameter(name: 'include', in: 'query', required: false, description: 'Relaciones públicas. CSV o include[]. Omitir conserva las relaciones anteriores; include= las omite. No se admiten rutas anidadas.', style: 'form', explode: false, schema: new OA\Schema(type: 'array', maxItems: 0, uniqueItems: true, items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filtros tipados filter[campo]. Números e IDs usan igualdad exacta; textos usan coincidencia parcial literal; fechas YYYY-MM-DD y rangos inclusivos. Los filtros escalares antiguos conservan su comportamiento.', style: 'deepObject', explode: true, schema: new OA\Schema(type: 'object', additionalProperties: false, properties: [
                new OA\Property(property: 'identificador', type: 'integer'),
                new OA\Property(property: 'tipo_de_evento', type: 'string'),
                new OA\Property(property: 'descripcion', type: 'string'),
                new OA\Property(property: 'id_usuario', type: 'integer'),
                new OA\Property(property: 'usuario', type: 'string'),
                new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_desde', type: 'string', format: 'date'),
                new OA\Property(property: 'fecha_hasta', type: 'string', format: 'date'),
            ])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 2, maximum: 100, default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Campo público escalar permitido; ver Support/Query/Definitions')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], description: 'desc por defecto al indicar sort_by; desempate por ID')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Pagination'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Activitylog'))])])),
            new OA\Response(response: 401, description: 'UnauthorizedResponse', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 403, description: 'ForbiddenResponse', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenResponse')),
            new OA\Response(response: 404, description: 'NotFoundResponse', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'ValidationResponse', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'TooManyRequestsResponse', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        ]
    )]
    public function __invoke(): void {}
}
