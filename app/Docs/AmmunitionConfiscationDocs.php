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
            new OA\Property(property: 'cantidad', type: 'integer', example: 100),
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
        required: ['cantidad','foto','decomiso','municion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', format: 'binary'),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'municion', type: 'integer', example: 9),
        ]
    )]
    #[OA\Schema(
        schema: 'AmmunitionConfiscationUpdateRequest',
        type: 'object',
        required: ['cantidad','decomiso','municion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 110),
            new OA\Property(property: 'foto', type: 'string', format: 'binary', nullable: true),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'municion', type: 'integer', example: 9),
        ]
    )]
    #[OA\Get(path: '/api/ammunitionConfiscation', tags: ['Decomisos Municiones'], summary: 'Listar decomisos de municiones', security: [["bearerAuth" => []]], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function index() {}

    #[OA\Get(path: '/api/ammunitionConfiscation/{idConfiscation}/confiscation', tags: ['Decomisos Municiones'], summary: 'Listar decomisos de municiones por decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'idConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function indexByConfiscation() {}

    #[OA\Post(path: '/api/ammunitionConfiscation', tags: ['Decomisos Municiones'], summary: 'Crear decomiso de munición', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscationCreateRequest')), responses: [
        new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function store() {}

    #[OA\Get(path: '/api/ammunitionConfiscation/{ammunitionConfiscation}', tags: ['Decomisos Municiones'], summary: 'Mostrar decomiso de munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function show() {}

    #[OA\Put(path: '/api/ammunitionConfiscation/{ammunitionConfiscation}', tags: ['Decomisos Municiones'], summary: 'Actualizar decomiso de munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscationUpdateRequest')), responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function update() {}

    #[OA\Delete(path: '/api/ammunitionConfiscation/{ammunitionConfiscation}', tags: ['Decomisos Municiones'], summary: 'Eliminar decomiso de munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function destroy() {}

    #[OA\Get(path: '/api/ammunitionConfiscation/deleted', tags: ['Decomisos Municiones'], summary: 'Listar decomisos de municiones eliminados', security: [["bearerAuth" => []]], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AmmunitionConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function indexDeleted() {}

    #[OA\Post(path: '/api/ammunitionConfiscation/restore/{ammunitionConfiscation}', tags: ['Decomisos Municiones'], summary: 'Restaurar decomiso de munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunitionConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function restore() {}

    #[OA\Get(path: '/api/ammunitionGraphIndex', tags: ['Decomisos Municiones'], summary: 'Datos gráficos decomisos de municiones', security: [["bearerAuth" => []]], parameters: [
        new OA\Parameter(name: 'period', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'day')),
        new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-01')),
        new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-31')),
        new OA\Parameter(name: 'ammunitions', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '[1,2]'), description: 'JSON array de IDs de municiones'),
    ], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object', properties: [
            new OA\Property(property: 'lineBarData', type: 'array', items: new OA\Items(type: 'object')),
            new OA\Property(property: 'pieData', type: 'array', items: new OA\Items(type: 'object')),
        ])),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function graphIndex() {}
}
