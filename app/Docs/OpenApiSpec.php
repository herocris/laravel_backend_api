<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API de gestión de decomisos. Preparación de portafolio local; publicación pendiente. JWT guard api por cookie HttpOnly o Bearer; login/registro requieren sesión y CSRF. Inicializar GET /api/csrf-cookie y reenviar XSRF-TOKEN como X-XSRF-TOKEN con las cookies. El JWT se entrega solo en cookie, nunca en el JSON. auth + can aplican permisos Spatie explícitos. Consultar README para instalación, matriz y recorrido sintético.',
    title: 'GraphMapApp API',
)]
#[OA\Server(url: '/', description: 'Mismo origen que sirve la API; no presupone un servidor publicado')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT',
    description: 'JWT obtenido por un flujo autorizado. Login/registro entregan token en cookie HttpOnly; no hay access_token en JSON. Bearer evita CSRF salvo en login/registro.',
)]
#[OA\SecurityScheme(
    securityScheme: 'authCookie', type: 'apiKey', in: 'cookie', name: 'token',
    description: 'Cookie JWT HttpOnly; el navegador la envía con credentials. Escrituras requieren también sesión y CSRF. Swagger UI no puede establecer manualmente cookies HttpOnly.',
)]
#[OA\SecurityScheme(
    securityScheme: 'csrfHeader', type: 'apiKey', in: 'header', name: 'X-XSRF-TOKEN',
    description: 'Valor URL-decodificado de XSRF-TOKEN obtenido en GET /api/csrf-cookie. Reenviar cookies de sesión del mismo cliente. Necesario en login/registro y escrituras por cookie JWT.',
)]
class OpenApiSpec {}
