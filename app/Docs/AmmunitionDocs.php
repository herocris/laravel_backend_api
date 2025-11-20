<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class AmmunitionDocs
{
    #[OA\Schema(
        schema: 'Ammunition',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 3),
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cartucho 5.56mm'),
            new OA\Property(property: 'logo', type: 'string', example: 'ammo3.png')
        ]
    )]
    #[OA\Schema(
        schema: 'AmmunitionCreateRequest',
        type: 'object',
        required: ['descripcion','logo'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cartucho 5.56mm'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', example: 'ammo.png')
        ]
    )]
    #[OA\Schema(
        schema: 'AmmunitionUpdateRequest',
        type: 'object',
        required: ['descripcion'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Cartucho 5.56mm verde'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', nullable: true, example: 'ammo.png')
        ]
    )]
    #[OA\Get(path: '/api/ammunition', tags: ['Municiones'], summary: 'Listar municiones', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Ammunition')))])]
    public function index() {}
    #[OA\Get(path: '/api/ammunition/deleted', tags: ['Municiones'], summary: 'Listar municiones eliminadas', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Ammunition')))])]
    public function indexDeleted() {}
    #[OA\Post(path: '/api/ammunition', tags: ['Municiones'], summary: 'Crear munición', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionCreateRequest')), responses: [new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Ammunition'))])]
    public function store() {}
    #[OA\Post(path: '/api/ammunition/restore/{ammunition}', tags: ['Municiones'], summary: 'Restaurar munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunition', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/Ammunition'))])]
    public function restore() {}
    #[OA\Get(path: '/api/ammunition/{ammunition}', tags: ['Municiones'], summary: 'Mostrar munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunition', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Ammunition'))])]
    public function show() {}
    #[OA\Put(path: '/api/ammunition/{ammunition}', tags: ['Municiones'], summary: 'Actualizar munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunition', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AmmunitionUpdateRequest')), responses: [new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Ammunition'))])]
    public function update() {}
    #[OA\Delete(path: '/api/ammunition/{ammunition}', tags: ['Municiones'], summary: 'Eliminar munición', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'ammunition', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/Ammunition'))])]
    public function destroy() {}
}
