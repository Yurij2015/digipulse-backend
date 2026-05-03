<?php

namespace App\Domain\Monitoring\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SiteLimitExceededException extends HttpException
{
    public function __construct(string $message = 'You have reached the limit of 3 sites. Please contact support or upgrade to add more.')
    {
        parent::__construct(403, $message);
    }
}
