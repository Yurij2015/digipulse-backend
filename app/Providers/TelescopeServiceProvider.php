<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            if ($isLocal || config('telescope.record_all', false)) {
                return true;
            }

            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            if ($this->app->environment('local')) {
                return true;
            }

            // Check IP access
            $allowedIps = config('telescope.allowed_ips');
            if ($allowedIps) {
                $ips = array_map('trim', explode(',', $allowedIps));
                if (in_array(request()->ip(), $ips, true)) {
                    return true;
                }
            }

            // Check Email access (requires authentication)
            $allowedEmails = config('telescope.allowed_emails');
            if ($user && $allowedEmails) {
                $emails = array_map('trim', explode(',', $allowedEmails));
                if (in_array($user->email, $emails, true)) {
                    return true;
                }
            }

            return false;
        });
    }
}
