<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        description: 'API documentation for the DigiPulse monitoring system. **Authentication:** Use these endpoints to register, login, and manage user tokens.',
        title: 'DigiPulse API'
    ),
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: 'frontendKey',
                type: 'apiKey',
                description: 'Custom frontend key for API access',
                name: 'X-Frontend-Key',
                in: 'header'
            ),
            new OA\SecurityScheme(
                securityScheme: 'bearerAuth',
                type: 'http',
                description: 'BearerAuth access token',
                bearerFormat: 'JWT',
                scheme: 'bearer'
            ),
        ]
    )
)]
class ApiInfo {}
