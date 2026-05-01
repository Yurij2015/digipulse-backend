<?php

namespace App\Domain\Monitoring\Contracts;

interface AlertServiceInterface
{
    /**
     * Send an alert if the site is down.
     */
    public function sendSiteDownAlert(int $configurationId): void;
}
