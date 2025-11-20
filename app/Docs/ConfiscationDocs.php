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
            new OA\Property(property: 'longitud', type: 'number', example: -87.12345)
        ]
    )]
    #[OA\Schema(
        schema: 'ConfiscationCreateRequest',
        type: 'object',
        required: ['fecha','observacion','direccion','departamento','municipalidad','latitud','longitud'],
        properties: [
            new OA\Property(property: 'fecha', type: 'string', example: '2024-01-15'),
            new OA\Property(property: 'observacion', type: 'string', example: 'Operativo nocturno'),
            new OA\Property(property: 'direccion', type: 'string', example: 'Av. Central 123'),
            new OA\Property(property: 'departamento', type: 'string', example: 'Departamento X'),
            new OA\Property(property: 'municipalidad', type: 'string', example: 'Municipio Y'),
            new OA\Property(property: 'latitud', type: 'number', example: 14.12345),
            new OA\Property(property: 'longitud', type: 'number', example: -87.12345)
        ]
    )]
    #[OA\Schema(
        schema: 'ConfiscationUpdateRequest',
        type: 'object',
        required: ['fecha','observacion','direccion','departamento','municipalidad','latitud','longitud'],
        properties: [
            new OA\Property(property: 'fecha', type: 'string', example: '2024-02-01'),
            new OA\Property(property: 'observacion', type: 'string', example: 'Operativo diurno'),
            new OA\Property(property: 'direccion', type: 'string', example: 'Av. Central 456'),
            new OA\Property(property: 'departamento', type: 'string', example: 'Departamento X'),
            new OA\Property(property: 'municipalidad', type: 'string', example: 'Municipio Y'),
            new OA\Property(property: 'latitud', type: 'number', example: 14.54321),
            new OA\Property(property: 'longitud', type: 'number', example: -87.54321)
        ]
    )]
    #[OA\Schema(
        schema: 'MapDrugConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto1.png'),
            new OA\Property(property: 'cantidad', type: 'integer', example: 5),
            new OA\Property(property: 'peso', type: 'number', example: 2.5),
            new OA\Property(property: 'droga', type: 'integer', example: 10),
            new OA\Property(property: 'droga_nombre', type: 'string', example: 'Cocaína')
        ]
    )]
    #[OA\Schema(
        schema: 'MapWeaponConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 2),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto2.png'),
            new OA\Property(property: 'cantidad', type: 'integer', example: 3),
            new OA\Property(property: 'arma', type: 'integer', example: 5),
            new OA\Property(property: 'arma_nombre', type: 'string', example: 'Pistola 9mm')
        ]
    )]
    #[OA\Schema(
        schema: 'MapAmmunitionConfiscation',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 3),
            new OA\Property(property: 'decomiso', type: 'integer', example: 100),
            new OA\Property(property: 'foto', type: 'string', example: 'foto3.png'),
            new OA\Property(property: 'cantidad', type: 'integer', example: 100),
            new OA\Property(property: 'municion', type: 'integer', example: 3),
            new OA\Property(property: 'municion_nombre', type: 'string', example: 'Cartucho 5.56mm')
        ]
    )]
    #[OA\Schema(
        schema: 'MapConfiscationItem',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer'),
            new OA\Property(property: 'fecha', type: 'string'),
            new OA\Property(property: 'observacion', type: 'string'),
            new OA\Property(property: 'latitud', type: 'number'),
            new OA\Property(property: 'longitud', type: 'number'),
            new OA\Property(property: 'drugConfiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapDrugConfiscation')),
            new OA\Property(property: 'weaponConfiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapWeaponConfiscation')),
            new OA\Property(property: 'ammunitionConfiscations', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapAmmunitionConfiscation'))
        ]
    )]
    #[OA\Schema(
        schema: 'MapConfiscationResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'mapItems', type: 'array', items: new OA\Items(ref: '#/components/schemas/MapConfiscationItem'))
        ]
    )]
    #[OA\Get(path: '/api/confiscation', tags: ['Decomisos'], summary: 'Listar decomisos', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Confiscation')))])]
    public function index() {}
    #[OA\Get(path: '/api/confiscation/deleted', tags: ['Decomisos'], summary: 'Listar decomisos eliminados', security: [["bearerAuth" => []]], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Confiscation')))])]
    public function indexDeleted() {}
    #[OA\Get(path: '/api/confiscation/map', tags: ['Decomisos'], summary: 'Mapa de decomisos filtrado', security: [["bearerAuth" => []]], parameters: [
        new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'drugs', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'JSON array de IDs de drogas')),
        new OA\Parameter(name: 'weapons', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'JSON array de IDs de armas')),
        new OA\Parameter(name: 'ammunitions', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'JSON array de IDs de municiones'))
    ], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MapConfiscationResponse'))])]
    public function mapConfiscations() {}
    #[OA\Post(path: '/api/confiscation', tags: ['Decomisos'], summary: 'Crear decomiso', security: [["bearerAuth" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ConfiscationCreateRequest')), responses: [new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation'))])]
    public function store() {}
    #[OA\Post(path: '/api/confiscation/restore/{confiscation}', tags: ['Decomisos'], summary: 'Restaurar decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Restaurado', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation'))])]
    public function restore() {}
    #[OA\Get(path: '/api/confiscation/{confiscation}', tags: ['Decomisos'], summary: 'Mostrar decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation'))])]
    public function show() {}
    #[OA\Put(path: '/api/confiscation/{confiscation}', tags: ['Decomisos'], summary: 'Actualizar decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ConfiscationUpdateRequest')), responses: [new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation'))])]
    public function update() {}
    #[OA\Delete(path: '/api/confiscation/{confiscation}', tags: ['Decomisos'], summary: 'Eliminar decomiso', security: [["bearerAuth" => []]], parameters: [new OA\Parameter(name: 'confiscation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Eliminado', content: new OA\JsonContent(ref: '#/components/schemas/Confiscation'))])]
    public function destroy() {}
}
