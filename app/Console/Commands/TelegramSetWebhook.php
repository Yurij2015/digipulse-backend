<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the Telegram bot webhook URL';

    /**
     * Execute the console command.
     *
     * @throws \JsonException
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $token = config('services.telegram.bot_token');
        $url = $this->argument('url');

        if (! $url) {
            $this->info('Current webhook status:');
            $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $this->line(json_encode($response->json(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return;
        }

        // Ensure URL ends with the webhook endpoint
        if (! str_ends_with($url, '/api/webhooks/telegram')) {
            $url = rtrim($url, '/').'/api/webhooks/telegram';
        }

        $this->info("Setting webhook to: {$url}");

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
        ]);

        if ($response->successful()) {
            $this->info('Webhook set successfully!');
            $this->line(json_encode($response->json(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } else {
            $this->error('Failed to set webhook!');
            $this->line($response->body());
        }
    }
}
