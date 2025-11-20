<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class DrugPresentationDocs
{
    #[OA\Schema(
        schema: 'DrugPresentation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 2),
            new OA\Property(property: 'descripcion', type: 'string', example: 'Tabletas'),
            new OA\Property(property: 'logo', type: 'string', example: 'present2.png')
        ]
    )]
    #[OA\Schema(
        schema: 'DrugPresentationCreateRequest',
        type: 'object',
        required: ['descripcion','logo'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Tabletas'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', example: 'tabletas.png')
        ]
    )]
    #[OA\Schema(
        schema: 'DrugPresentationUpdateRequest',
        type: 'object',
        required: ['descripcion'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Tabletas recubiertas'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', nullable: true, example: 'tabletas.png')
        ]
    )]
    #[OA\Get(path: '/api/drugPresentation', tags: ['Presentaciones Drogas'], summary: 'Listar presentaciones de droga', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrugPresentation')))])]
    public function index() {}
    #[OA\Get(path: '/api/drugPresentation/deleted', tags: ['Presentaciones Drogas'], summary: 'Listar presentaciones eliminadas', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrugPresentation')))])]
    public function indexDeleted() {}
    #[OA\Post(path: '/api/drugPresentation', tags: ['Presentaciones Drogas'], summary: 'Crear presentación de droga', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentationCreateRequest')), responses: [new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentation'))])]
    public function store() {}
    #[OA\Post(path: '/api/drugPresentation/restore/{drugPresentation}', tags: ['Presentaciones Drogas'], summary: 'Restaurar presentación', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'drugPresentation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentation'))])]
    public function restore() {}
    #[OA\Get(path: '/api/drugPresentation/{drugPresentation}', tags: ['Presentaciones Drogas'], summary: 'Mostrar presentación', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'drugPresentation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentation'))])]
    public function show() {}
    #[OA\Put(path: '/api/drugPresentation/{drugPresentation}', tags: ['Presentaciones Drogas'], summary: 'Actualizar presentación', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'drugPresentation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentationUpdateRequest')), responses: [new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentation'))])]
    public function update() {}
    #[OA\Delete(path: '/api/drugPresentation/{drugPresentation}', tags: ['Presentaciones Drogas'], summary: 'Eliminar presentación', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'drugPresentation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/DrugPresentation'))])]
    public function destroy() {}
}
