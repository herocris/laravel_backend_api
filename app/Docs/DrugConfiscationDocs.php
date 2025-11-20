<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

// Documentación de endpoints de sub-decomisos de Drogas
// Esta clase solo existe para que swagger-php escanee atributos.
class DrugConfiscationDocs
{
    #[OA\Schema(
        schema: 'DrugConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 10),
            new OA\Property(property: 'cantidad', type: 'integer', example: 5),
            new OA\Property(property: 'peso', type: 'number', format: 'float', example: 12.5),
            new OA\Property(property: 'foto', type: 'string', nullable: true, example: 'imagen.png'),
            new OA\Property(
                property: 'decomiso',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 3),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Operativo zona norte'),
                ]
            ),
            new OA\Property(
                property: 'droga',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 7),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Marihuana'),
                ]
            ),
            new OA\Property(
                property: 'presentacion',
                type: 'object',
                properties: [
                    new OA\Property(property: 'identificador', type: 'integer', example: 2),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Bolsa plástica'),
                ]
            ),
        ]
    )]
    #[OA\Schema(
        schema: 'DrugConfiscationCreateRequest',
        type: 'object',
        required: ['cantidad','peso','foto','decomiso','droga','presentacion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 5),
            new OA\Property(property: 'peso', type: 'number', format: 'float', example: 12.5),
            new OA\Property(property: 'foto', type: 'string', format: 'binary'),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3, description: 'ID del decomiso'),
            new OA\Property(property: 'droga', type: 'integer', example: 7, description: 'ID de la droga'),
            new OA\Property(property: 'presentacion', type: 'integer', example: 2, description: 'ID de la presentación de la droga'),
        ]
    )]
    #[OA\Schema(
        schema: 'DrugConfiscationUpdateRequest',
        type: 'object',
        required: ['cantidad','peso','decomiso','droga','presentacion'],
        properties: [
            new OA\Property(property: 'cantidad', type: 'integer', example: 6),
            new OA\Property(property: 'peso', type: 'number', format: 'float', example: 10.0),
            new OA\Property(property: 'foto', type: 'string', format: 'binary', nullable: true),
            new OA\Property(property: 'decomiso', type: 'integer', example: 3),
            new OA\Property(property: 'droga', type: 'integer', example: 7),
            new OA\Property(property: 'presentacion', type: 'integer', example: 2),
        ]
    )]
    #[OA\Get(
        path: '/api/drugConfiscation',
        tags: ['Decomisos Drogas'],
        summary: 'Listar decomisos de drogas',
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrugConfiscation'))),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/drugConfiscation/{idConfiscation}/confiscation',
        tags: ['Decomisos Drogas'],
        summary: 'Listar decomisos de drogas por decomiso',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'idConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrugConfiscation'))),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function indexByConfiscation() {}

    #[OA\Post(
        path: '/api/drugConfiscation',
        tags: ['Decomisos Drogas'],
        summary: 'Crear decomiso de droga',
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscationCreateRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscation')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: '/api/drugConfiscation/{drugConfiscation}',
        tags: ['Decomisos Drogas'],
        summary: 'Mostrar decomiso de droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drugConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscation')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/drugConfiscation/{drugConfiscation}',
        tags: ['Decomisos Drogas'],
        summary: 'Actualizar decomiso de droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drugConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscationUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscation')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/drugConfiscation/{drugConfiscation}',
        tags: ['Decomisos Drogas'],
        summary: 'Eliminar decomiso de droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drugConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscation')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function destroy() {}

    #[OA\Get(
        path: '/api/drugConfiscation/deleted',
        tags: ['Decomisos Drogas'],
        summary: 'Listar decomisos de drogas eliminados',
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrugConfiscation'))),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function indexDeleted() {}

    #[OA\Post(
        path: '/api/drugConfiscation/restore/{drugConfiscation}',
        tags: ['Decomisos Drogas'],
        summary: 'Restaurar decomiso de droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drugConfiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/DrugConfiscation')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function restore() {}

    #[OA\Get(
        path: '/api/drugGraphIndex',
        tags: ['Decomisos Drogas'],
        summary: 'Datos gráficos decomisos de drogas',
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: 'period', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'day')), 
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-01')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-31')),
            new OA\Parameter(name: 'drugs', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '[1,2]'), description: 'JSON array de IDs de drogas'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'lineBarData', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'pieData', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse')),
        ]
    )]
    public function graphIndex() {}
}
