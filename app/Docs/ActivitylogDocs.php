<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class ActivitylogDocs
{
    #[OA\Schema(
        schema: 'Activitylog',
        type: 'object',
        properties: [
            new OA\Property(property: 'identificador', type: 'integer', example: 1),
            new OA\Property(property: 'tipo_de_evento', type: 'string', example: 'created'),
            new OA\Property(property: 'descripcion', type: 'string', example: 'user'),
            new OA\Property(property: 'id_usuario', type: 'integer', example: 5, nullable: true),
            new OA\Property(property: 'usuario', type: 'string', example: 'user'),
            new OA\Property(property: 'cambios', type: 'object', example: ['name' => 'Juan', 'email' => 'juan@example.com']),
            new OA\Property(property: 'fecha', type: 'string', format: 'date-time'),
        ]
    )]
    #[OA\Get(
        path: "/api/activity",
        tags: ["Logs de Actividad"],
        summary: "Mostrar logs de actividad",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Activitylog"))),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/UnauthorizedResponse")),
            new OA\Response(response: 404, description: "No encontrado", content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")),
            new OA\Response(response: 405, description: "Método no permitido", content: new OA\JsonContent(ref: "#/components/schemas/MethodNotAllowedResponse")),
            new OA\Response(response: 429, description: "Demasiadas solicitudes", content: new OA\JsonContent(ref: "#/components/schemas/TooManyRequestsResponse")),
            new OA\Response(response: 500, description: "Error interno", content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse"))
        ]
    )]
    public function __invoke() {}
}
