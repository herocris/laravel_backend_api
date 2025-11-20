<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class DrugDocs
{
    #[OA\Schema(
        schema: 'Drug',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 10),
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cocaína'),
            new OA\Property(property: 'logo', type: 'string', example: 'drug10.png', description: 'Nombre de archivo/logo')
        ]
    )]
    #[OA\Schema(
        schema: 'DrugCreateRequest',
        type: 'object',
        required: ['descripcion','logo'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cocaína'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', example: 'imagen.png', description: 'Archivo PNG')
        ]
    )]
    #[OA\Schema(
        schema: 'DrugUpdateRequest',
        type: 'object',
        required: ['descripcion'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cocaína refinada'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', nullable: true, example: 'imagen.png', description: 'Archivo PNG opcional')
        ]
    )]
    #[OA\Get(
        path: '/api/drug',
        tags: ['Drogas'],
        summary: 'Listar drogas',
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drug'))),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 405, description: 'Método no permitido', content: new OA\JsonContent(ref: '#/components/schemas/MethodNotAllowedResponse')),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes', content: new OA\JsonContent(ref: '#/components/schemas/TooManyRequestsResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ServerErrorResponse'))
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/drug/deleted',
        tags: ['Drogas'],
        summary: 'Listar drogas eliminadas (soft delete)',
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drug'))),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse'))
        ]
    )]
    public function indexDeleted() {}

    #[OA\Post(
        path: '/api/drug',
        tags: ['Drogas'],
        summary: 'Crear droga',
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugCreateRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Drug')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse'))
        ]
    )]
    public function store() {}

    #[OA\Post(
        path: '/api/drug/restore/{drug}',
        tags: ['Drogas'],
        summary: 'Restaurar droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drug', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/Drug')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse'))
        ]
    )]
    public function restore() {}

    #[OA\Get(
        path: '/api/drug/{drug}',
        tags: ['Drogas'],
        summary: 'Mostrar droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drug', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Drug')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse'))
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/drug/{drug}',
        tags: ['Drogas'],
        summary: 'Actualizar droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drug', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Drug')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ValidationResponse'))
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/drug/{drug}',
        tags: ['Drogas'],
        summary: 'Eliminar droga',
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: 'drug', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/Drug')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse'))
        ]
    )]
    public function destroy() {}
}
