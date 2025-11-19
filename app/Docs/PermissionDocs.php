<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class PermissionDocs
{
    #[OA\Schema(
        schema: 'Permission',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'nombre', type: 'string', example: 'edit-users'),
        ]
    )]
    #[OA\Schema(
        schema: 'PermissionCreateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'edit-users')
        ]
    )]
    #[OA\Schema(
        schema: 'PermissionUpdateRequest',
        type: 'object',
        required: ['nombre'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'edit-users')
        ]
    )]
    #[OA\Get(
        path: "/api/permission",
        tags: ["Permisos"],
        summary: "Mostrar permisos",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Permission"))),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/api/permission",
        tags: ["Permisos"],
        summary: "Crear permiso",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/PermissionCreateRequest")),
        responses: [
            new OA\Response(response: 201, description: "Creado", content: new OA\JsonContent(ref: "#/components/schemas/Permission")),
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
        path: "/api/permission/{permission}",
        tags: ["Permisos"],
        summary: "Mostrar permiso",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "permission", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Permission")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: "/api/permission/{permission}",
        tags: ["Permisos"],
        summary: "Actualizar permiso",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "permission", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/PermissionUpdateRequest")),
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Permission")),
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
        path: "/api/permission/{permission}",
        tags: ["Permisos"],
        summary: "Eliminar permiso",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "permission", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Eliminado", content: new OA\JsonContent(ref: "#/components/schemas/Permission")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function destroy() {}
}
