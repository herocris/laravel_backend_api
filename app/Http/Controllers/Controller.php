<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[
    OA\Info(
        version: "1.0.0", 
        description: "Documentacion la API graphMapApp, accede al apartado de auntenticación y por medio del Login con el usuario que esta por defecto; obten el token de autorización y pegalo en boton Authorize para consumir los endpoints que requieran autenticación.", 
        title: "GraphMapApp API"
    ),
    OA\Server(url: 'http://127.0.0.1', description: "local server"),
    OA\Server(url: 'http://staging.example.com', description: "staging server"),
    OA\Server(url: 'http://example.com', description: "production server"),
    // Autenticación por header Authorization (opcional si también usas cookie)
    OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        name: "Authorization",
        in: "header",
        scheme: "bearer",
        bearerFormat: "JWT",
        description: "Enviar 'Authorization: Bearer <token>'"
    ),
    // Autenticación por cookie HttpOnly que contiene el JWT o token de sesión
    // OA\SecurityScheme(
    //     securityScheme: "authCookie",
    //     type: "apiKey",
    //     in: "cookie",
    //     name: "access_token",
    //     description: "Cookie HttpOnly 'access_token' con el JWT. El explorador ya la envía automáticamente en cada request."
    // ),
]
abstract class Controller
{
    //
}
