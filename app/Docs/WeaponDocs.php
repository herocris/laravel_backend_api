<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class WeaponDocs
{
    #[OA\Schema(
        schema: 'Weapon',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 5),
            new OA\Property(property: 'descripcion', type: 'string', example: 'Pistola 9mm'),
            new OA\Property(property: 'logo', type: 'string', example: 'weapon5.png')
        ]
    )]
    #[OA\Schema(
        schema: 'WeaponCreateRequest',
        type: 'object',
        required: ['descripcion','logo'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Pistola 9mm'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', example: 'weapon.png')
        ]
    )]
    #[OA\Schema(
        schema: 'WeaponUpdateRequest',
        type: 'object',
        required: ['descripcion'],
        properties: [
            new OA\Property(property: 'descripcion', type: 'string', example: 'Pistola 9mm Modificada'),
            new OA\Property(property: 'logo', type: 'string', format: 'binary', nullable: true, example: 'weapon.png')
        ]
    )]
    #[OA\Get(path: '/api/weapon', tags: ['Armas'], summary: 'Listar armas', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Weapon')))])]
    public function index() {}
    #[OA\Get(path: '/api/weapon/deleted', tags: ['Armas'], summary: 'Listar armas eliminadas', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Weapon')))])]
    public function indexDeleted() {}
    #[OA\Post(path: '/api/weapon', tags: ['Armas'], summary: 'Crear arma', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/WeaponCreateRequest')), responses: [new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Weapon'))])]
    public function store() {}
    #[OA\Post(path: '/api/weapon/restore/{weapon}', tags: ['Armas'], summary: 'Restaurar arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weapon', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/Weapon'))])]
    public function restore() {}
    #[OA\Get(path: '/api/weapon/{weapon}', tags: ['Armas'], summary: 'Mostrar arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weapon', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Weapon'))])]
    public function show() {}
    #[OA\Put(path: '/api/weapon/{weapon}', tags: ['Armas'], summary: 'Actualizar arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weapon', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/WeaponUpdateRequest')), responses: [new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Weapon'))])]
    public function update() {}
    #[OA\Delete(path: '/api/weapon/{weapon}', tags: ['Armas'], summary: 'Eliminar arma', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'weapon', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/Weapon'))])]
    public function destroy() {}
}
