<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const FRONTEND_KEY = 'test-frontend-key';

describe('Frontend Key Authorization', function () {
    it('fails if X-Frontend-Key is missing', function () {
        $this->postJson('/api/login', [])
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    });

    it('fails if X-Frontend-Key is invalid', function () {
        $this->postJson('/api/login', [], ['X-Frontend-Key' => 'invalid-key'])
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    });
});

describe('User Registration', function () {
    it('can register a new user', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'johndoe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.pro',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], ['X-Frontend-Key' => FRONTEND_KEY]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'first_name', 'last_name', 'email_verified_at', 'created_at', 'updated_at'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.pro',
            'name' => 'johndoe',
        ]);
    });

    it('fails registration with validation errors', function () {
        $this->postJson('/api/register', [], ['X-Frontend-Key' => FRONTEND_KEY])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'first_name', 'last_name']);
    });
});

describe('User Authentication', function () {
    it('can login with correct credentials', function () {
        $user = User::factory()->create([
            'password' => bcrypt($password = 'StrongPass123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ], ['X-Frontend-Key' => FRONTEND_KEY]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    });

    it('cannot login with wrong password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('StrongPass123!'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ], ['X-Frontend-Key' => FRONTEND_KEY])
            ->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    });

    it('can logout and revoke token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->postJson('/api/logout', [], [
            'X-Frontend-Key' => FRONTEND_KEY,
            'Authorization' => "Bearer $token",
        ])->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $user->fresh()->tokens);
    });
});
