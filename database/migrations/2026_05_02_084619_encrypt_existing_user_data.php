<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $update = [];

            if ($user->email && ! $this->isEncrypted($user->email)) {
                $update['email'] = Crypt::encryptString($user->email);
                $update['email_bindex'] = $this->generateBlindIndex($user->email);
            }

            if ($user->name && ! $this->isEncrypted($user->name)) {
                $update['name'] = Crypt::encryptString($user->name);
            }

            if ($user->google_id && ! $this->isEncrypted($user->google_id)) {
                $update['google_id'] = Crypt::encryptString($user->google_id);
                $update['google_id_bindex'] = $this->generateBlindIndex($user->google_id);
            }

            if ($user->telegram_chat_id && ! $this->isEncrypted($user->telegram_chat_id)) {
                $update['telegram_chat_id'] = Crypt::encryptString($user->telegram_chat_id);
            }

            if ($user->first_name && ! $this->isEncrypted($user->first_name)) {
                $update['first_name'] = Crypt::encryptString($user->first_name);
            }
            if ($user->last_name && ! $this->isEncrypted($user->last_name)) {
                $update['last_name'] = Crypt::encryptString($user->last_name);
            }

            if (! empty($update)) {
                DB::table('users')->where('id', $user->id)->update($update);
            }
        }
    }

    private function isEncrypted(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return str_starts_with($value, 'eyJpdiI6');
    }

    private function generateBlindIndex(string $value): string
    {
        return hash_hmac('sha256', strtolower($value), config('app.key'));
    }
};
