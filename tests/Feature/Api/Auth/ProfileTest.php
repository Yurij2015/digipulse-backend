<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

describe('Profile Update', function () {
    it('can update profile fields', function () {
        $user = User::factory()->create(['first_name' => 'Old']);
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
        ], ['X-Frontend-Key' => $this->frontendKey])
            ->assertStatus(200)
            ->assertJsonPath('user.first_name', 'New')
            ->assertJsonPath('user.last_name', 'Name');

        expect($user->fresh()->first_name)->toBe('New');
    });

    it('requires authentication', function () {
        $this->putJson(route('profile.update'), [], ['X-Frontend-Key' => $this->frontendKey])
            ->assertStatus(401);
    });
});

describe('Change Password', function () {
    it('can change password and revokes all tokens', function () {
        $user = User::factory()->create(['password' => bcrypt('OldPass123!')]);
        Sanctum::actingAs($user);
        $user->createToken('other-session'); // second token

        $this->putJson(route('profile.password'), [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ], ['X-Frontend-Key' => $this->frontendKey])
            ->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully']);

        expect($user->fresh()->tokens)->toHaveCount(0);
    });

    it('fails with wrong current password', function () {
        $user = User::factory()->create(['password' => bcrypt('RealPass123!')]);
        Sanctum::actingAs($user);

        $this->putJson(route('profile.password'), [
            'current_password' => 'WrongPass!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ], ['X-Frontend-Key' => $this->frontendKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    });
});

describe('Delete Account', function () {
    it('can delete own account and revokes tokens', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->deleteJson(route('profile.destroy'), [], [
            'X-Frontend-Key' => $this->frontendKey,
            'Authorization' => "Bearer $token",
        ])->assertStatus(200)
            ->assertJson(['message' => 'Account deleted successfully']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('also deletes associated sites on account deletion', function () {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test')->plainTextToken;

        $this->deleteJson(route('profile.destroy'), [], [
            'X-Frontend-Key' => $this->frontendKey,
            'Authorization' => "Bearer $token",
        ])->assertStatus(200);

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    });
});
