<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class RoleDocs
{
    #[OA\Schema(
        schema: 'Role',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'nombre', type: 'string', example: 'admin'),
            new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer', example: 1), example: [1,2,3], description: 'IDs de permisos asignados', readOnly: true),
        ]
    )]
    #[OA\Schema(
        schema: 'RoleCreateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'editor'),
            new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), example: [1,2,3])
        ]
    )]
    #[OA\Schema(
        schema: 'RoleUpdateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'editor'),
            new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), example: [1,2,3])
        ]
    )]
    #[OA\Get(
        path: "/api/role",
        tags: ["Roles"],
        summary: "Mostrar roles",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Role"))),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/api/role",
        tags: ["Roles"],
        summary: "Crear rol",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/RoleCreateRequest")),
        responses: [
            new OA\Response(response: 201, description: "Creado", content: new OA\JsonContent(ref: "#/components/schemas/Role")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 422, description: "Validación fallida", content: new OA\JsonContent(ref: "#/components/schemas/ValidationResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: "/api/role/{role}",
        tags: ["Roles"],
        summary: "Mostrar rol",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "role", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Role")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: "/api/role/{role}",
        tags: ["Roles"],
        summary: "Actualizar rol",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "role", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/RoleUpdateRequest")),
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Role")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 422, description: "Validación fallida", content: new OA\JsonContent(ref: "#/components/schemas/ValidationResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: "/api/role/{role}",
        tags: ["Roles"],
        summary: "Eliminar rol",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "role", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Eliminado", content: new OA\JsonContent(ref: "#/components/schemas/Role")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function destroy() {}
}
