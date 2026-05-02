<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthService
{
    /**
     * Register a new user.
     *
     * @throws Throwable
     */
    public function register(array $data): array
    {
        return DB::transaction(static function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'password' => Hash::make($data['password']),
            ])->refresh();

            try {
                event(new Registered($user));
            } catch (Throwable $e) {
                Log::error('Registration email failed to send', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => new UserResource($user),
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate a user.
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email_bindex', User::generateBlindIndex($email))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
        ];
    }
}
