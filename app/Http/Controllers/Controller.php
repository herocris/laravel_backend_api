<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[
    OA\Info(
        version: "1.0.0", 
        description: "Esta API esta hecha en PHP Laravel; accede al apartado de Auntenticación y realiza el Login con el usuario que esta por defecto para consumir los endpoints que requieran autenticación. Más info en [https://github.com/herocris/laravel_backend_api](https://github.com/herocris/laravel_backend_api).", 
        title: "GraphMapApp API"
    ),
    OA\Server(url: 'https://crisdemo.xyz/', description: "production server"),
    OA\Server(url: 'http://localhost', description: "local server"),
    OA\Server(url: 'http://staging.example.com', description: "staging server"),
    // Autenticación por header Authorization (opcional si también usas cookie)
    // OA\SecurityScheme(
    //     securityScheme: "bearerAuth",
    //     type: "http",
    //     name: "Authorization",
    //     in: "header",
    //     scheme: "bearer",
    //     bearerFormat: "JWT",
    //     description: "Enviar 'Authorization: Bearer <token>'"
    // ),
    // Autenticación por cookie HttpOnly que contiene el JWT o token de sesión
    OA\SecurityScheme(
        securityScheme: "authCookie",
        type: "apiKey",
        in: "cookie",
        name: "token",
        description: "Cookie HttpOnly 'token' con el JWT. El explorador ya la envía automáticamente en cada request."
    ),
]
abstract class Controller
{
    //
}
