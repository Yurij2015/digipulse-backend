<?php

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
    $this->user = User::factory()->create();
});

it('lists only the authenticated user\'s tickets', function () {
    SupportTicket::factory()->create(['user_id' => $this->user->id, 'subject' => 'My ticket']);
    SupportTicket::factory()->create(['user_id' => User::factory()->create()->id, 'subject' => 'Other ticket']);

    Sanctum::actingAs($this->user);

    $response = $this->getJson(route('v1.support.tickets.index'), ['X-Frontend-Key' => $this->frontendKey]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.subject'))->toBe('My ticket');
});

it('can view own ticket with messages', function () {
    $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);

    Sanctum::actingAs($this->user);

    $this->getJson(route('v1.support.tickets.show', $ticket), ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(200)
        ->assertJsonPath('data.id', $ticket->id);
});

it('cannot view another user\'s ticket', function () {
    $ticket = SupportTicket::factory()->create(['user_id' => User::factory()->create()->id]);

    Sanctum::actingAs($this->user);

    $this->getJson(route('v1.support.tickets.show', $ticket), ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(403);
});

it('can create a support ticket', function () {
    Notification::fake();
    Sanctum::actingAs($this->user);

    $this->postJson(route('v1.support.tickets.store'), [
        'subject' => 'Need help',
        'message' => 'Something is not working.',
        'priority' => 'high',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(201)
        ->assertJsonPath('ticket.subject', 'Need help');

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $this->user->id,
        'subject' => 'Need help',
        'status' => 'open',
    ]);
});

it('can reply to own ticket', function () {
    Notification::fake();

    $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id, 'status' => 'open']);

    Sanctum::actingAs($this->user);

    $this->postJson(route('v1.support.tickets.reply', $ticket), [
        'message' => 'Here is more detail.',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(201);

    $this->assertDatabaseHas('support_ticket_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $this->user->id,
        'message' => 'Here is more detail.',
        'is_admin_reply' => false,
    ]);
});

it('cannot reply to another user\'s ticket', function () {
    $ticket = SupportTicket::factory()->create(['user_id' => User::factory()->create()->id]);

    Sanctum::actingAs($this->user);

    $this->postJson(route('v1.support.tickets.reply', $ticket), [
        'message' => 'Sneaky reply.',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(403);
});

it('reopens a closed ticket when user replies', function () {
    Notification::fake();

    $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id, 'status' => 'closed']);

    Sanctum::actingAs($this->user);

    $this->postJson(route('v1.support.tickets.reply', $ticket), [
        'message' => 'Still having issues.',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(201);

    expect($ticket->fresh()->status)->toBe('open');
});
