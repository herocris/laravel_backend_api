<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class AuthDocs
{
    #[OA\Schema(
        schema: 'LoginRequest',
        type: 'object',
        required: ['email','password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'demoSwagger@test.com'),
            new OA\Property(property: 'password', type: 'string', example: 'password')
        ]
    )]
    #[OA\Schema(
        schema: 'AuthResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'access_token', type: 'string', example: 'token_jwt_ejemplo_1234567890'),
            new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
            new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
            new OA\Property(property: 'user', type: 'object', properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                new OA\Property(property: 'email', type: 'string', example: 'juan@example.com')
            ]),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['admin', 'editor']),
            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['create-users', 'edit-users'])
        ]
    )]
    #[OA\Post(
        path: "/api/register",
        tags: ["Autenticación"],
        summary: "Registrar nuevo usuario",
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/UserCreateRequest")),
        responses: [
            new OA\Response(response: 201, description: "Usuario registrado", content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")),
            new OA\Response(response: 422, description: "Validación fallida", content: new OA\JsonContent(ref: "#/components/schemas/ValidationResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function register() {}

    #[OA\Post(
        path: "/api/login",
        tags: ["Autenticación"],
        summary: "Iniciar sesión",
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")),
        responses: [
            new OA\Response(response: 200, description: "Login exitoso", content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")),
            new OA\Response(response: 401, description: "Credenciales incorrectas", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function login() {}

    #[OA\Get(
        path: "/api/me",
        tags: ["Autenticación"],
        summary: "Obtener usuario autenticado",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/User")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function me() {}

    #[OA\Post(
        path: "/api/logout",
        tags: ["Autenticación"],
        summary: "Cerrar sesión",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logout exitoso", content: new OA\JsonContent(properties: [
                new OA\Property(property: "message", type: "string", example: "Logout successful")
            ])),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function logout() {}

    #[OA\Post(
        path: "/api/refresh",
        tags: ["Autenticación"],
        summary: "Refrescar token",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Token refrescado", content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function refresh() {}
}
