<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

// Schemas de respuestas de error comunes centralizados.
#[OA\Schema(
    schema: 'NotFoundResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Url no encontrada'),
        new OA\Property(property: 'code', type: 'integer', example: 404),
    ]
)]
#[OA\Schema(
    schema: 'UnauthorizedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'No autenticado'),
        new OA\Property(property: 'code', type: 'integer', example: 401),
    ]
)]
#[OA\Schema(
    schema: 'MethodNotAllowedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Metodo no permitido'),
        new OA\Property(property: 'code', type: 'integer', example: 405),
    ]
)]
#[OA\Schema(
    schema: 'TooManyRequestsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Se ha revasado el numero de solicitudes'),
        new OA\Property(property: 'code', type: 'integer', example: 429),
    ]
)]
#[OA\Schema(
    schema: 'ValidationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'object', example: ['email' => ['The email has already been taken.']]),
        new OA\Property(property: 'code', type: 'integer', example: 422),
    ]
)]
#[OA\Schema(
    schema: 'ServerErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Error interno del servidor'),
        new OA\Property(property: 'code', type: 'integer', example: 500),
    ]
)]
class CommonSchemas {}
