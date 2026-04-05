<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: 'DigiPulse API',
        version: '1.0.0',
        description: 'API documentation for the DigiPulse monitoring system. **Authentication:** Use these endpoints to register, login, and manage user tokens.'
    ),
    servers: [
        new OA\Server(url: 'http://localhost', description: 'Local Development Server'),
    ],
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: 'frontendKey',
                type: 'apiKey',
                name: 'X-Frontend-Key',
                in: 'header',
                description: 'Custom frontend key for API access'
            ),
            new OA\SecurityScheme(
                securityScheme: 'bearerAuth',
                type: 'http',
                scheme: 'bearer',
                bearerFormat: 'JWT',
                description: 'BearerAuth access token'
            ),
        ]
    )
)]
class ApiInfo {}
