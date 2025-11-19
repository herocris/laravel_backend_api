<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

// Documentación de endpoints de Usuario aislada del controlador real.
// Esta clase NO se usa en la lógica; solo existe para que swagger-php escanee atributos.
class UserDocs
{
    // Schemas trasladados desde el modelo y CommonSchemas
    #[OA\Schema(
        schema: "User",
        type: "object",
        properties: [
            new OA\Property(property: "identificador", type: "integer", example: 1),
            new OA\Property(property: "nombre", type: "string", example: "Juan Pérez"),
            new OA\Property(property: "correo", type: "string", format: "email", example: "juan@example.com"),
            new OA\Property(
                property: "roles",
                type: "array",
                items: new OA\Items(type: "integer", example: 1),
                example: [1,2],
                description: "IDs de roles asignados",
                readOnly: true
            ),
            new OA\Property(
                property: "permisos",
                type: "array",
                items: new OA\Items(type: "integer", example: 1),
                example: [1,2,3],
                description: "IDs de permisos asignados",
                readOnly: true
            ),
        ]
    )]
    #[OA\Schema(
        schema: 'UserCreateRequest',
        type: 'object',
        required: ['nombre','correo','password'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
            new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'juan@example.com'),
            new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'Secret123')
        ]
    )]
    #[OA\Schema(
        schema: 'UserUpdateRequest',
        type: 'object',
        required: ['nombre','correo','password'],
        properties: [
            new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
            new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'juan@example.com'),
            new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'Secret123')
        ]
    )]
    #[OA\Get(
        path: "/api/user",
        tags: ["Usuarios"],
        summary: "Mostrar usuarios",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/User")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/api/user",
        tags: ["Usuarios"],
        summary: "Crear usuario",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UserCreateRequest")
        ),
        responses: [
            new OA\Response(response: 201, description: "Creado", content: new OA\JsonContent(ref: "#/components/schemas/User")),
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
        path: "/api/user/{user}",
        tags: ["Usuarios"],
        summary: "Mostrar usuario",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/User")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: "/api/user/{user}",
        tags: ["Usuarios"],
        summary: "Actualizar usuario",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/UserUpdateRequest")),
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/User")),
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
        path: "/api/user/{user}",
        tags: ["Usuarios"],
        summary: "Eliminar usuario",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Eliminado", content: new OA\JsonContent(ref: "#/components/schemas/User")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function destroy() {}
}
