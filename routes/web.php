<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', static function () {
    return view('welcome');
});

Route::get('/auth/redirect', static function () {
    return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
});

Route::get('/auth/callback', static function () {
    $googleUser = Socialite::driver('google')->user();

    $user = User::where('google_id', $googleUser->getId())
        ->orWhere('email', $googleUser->getEmail())
        ->first();

    if ($user) {
        $user->update([
            'google_id' => $googleUser->getId(),
            'google_nickname' => $googleUser->getNickname(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
    } else {
        $user = User::create([
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_nickname' => $googleUser->getNickname(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);
    }

    $token = $user->createToken('google-sso')->plainTextToken;

    $frontendUrl = rtrim(config('app.frontend_url'), '/');

    return redirect()->away("{$frontendUrl}/auth/callback#token={$token}");
});
