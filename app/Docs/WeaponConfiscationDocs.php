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
            new OA\Property(property: 'cantidad', type: 'integer', example: 4),
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
        required: ['cantidad','foto','decomiso','arma'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 4),
            new OA\Property(property: 'foto', type: 'string', format: 'binary'),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 8),
        ]
    )]
    #[OA\Schema(
        schema: 'WeaponConfiscationUpdateRequest',
        type: 'object',
        required: ['cantidad','decomiso','arma'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 5),
            new OA\Property(property: 'foto', type: 'string', format: 'binary', nullable: true),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 8),
        ]
    )]
    #[OA\Get(path: '/api/weaponConfiscation', tags: ['Decomisos Armas'], summary: 'Listar decomisos de armas', security: [["bearerAuth" => []]], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function index() {}

    #[OA\Get(path: '/api/weaponConfiscation/{idConfiscation}/confiscation', tags: ['Decomisos Armas'], summary: 'Listar decomisos de armas por decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'idConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function indexByConfiscation() {}

    #[OA\Post(path: '/api/weaponConfiscation', tags: ['Decomisos Armas'], summary: 'Crear decomiso de arma', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscationCreateRequest')), responses: [
        new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function store() {}

    #[OA\Get(path: '/api/weaponConfiscation/{weaponConfiscation}', tags: ['Decomisos Armas'], summary: 'Mostrar decomiso de arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function show() {}

    #[OA\Put(path: '/api/weaponConfiscation/{weaponConfiscation}', tags: ['Decomisos Armas'], summary: 'Actualizar decomiso de arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscationUpdateRequest')), responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function update() {}

    #[OA\Delete(path: '/api/weaponConfiscation/{weaponConfiscation}', tags: ['Decomisos Armas'], summary: 'Eliminar decomiso de arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function destroy() {}

    #[OA\Get(path: '/api/weaponConfiscation/deleted', tags: ['Decomisos Armas'], summary: 'Listar decomisos de armas eliminados', security: [["bearerAuth" => []]], responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/WeaponConfiscation'))),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function indexDeleted() {}

    #[OA\Post(path: '/api/weaponConfiscation/restore/{weaponConfiscation}', tags: ['Decomisos Armas'], summary: 'Restaurar decomiso de arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weaponConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/WeaponConfiscation')),
        new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
        new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
        new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
        new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
    ])]
    public function restore() {}

    #[OA\Get(path: '/api/weaponGraphIndex', tags: ['Decomisos Armas'], summary: 'Datos gráficos decomisos de armas', security: [["bearerAuth" => []]], parameters: [
        new OA\Parameter(name: 'period', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'day')),
        new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-01')),
        new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-02-01')),
        new OA\Parameter(name: 'weapons', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '[1,2]'), description: 'JSON array de IDs de armas'),
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
