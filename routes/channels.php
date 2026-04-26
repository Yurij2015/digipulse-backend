<?php

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum', 'frontend.key']]);

Broadcast::channel('App.Models.User.{id}', static function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tickets.{ticketId}', static function ($user, $ticketId) {
    if ($user->email === config('app.admin_email') || $user->hasRole('admin')) {
        return true;
    }

    // User can only access their own tickets
    $ticket = SupportTicket::find($ticketId);

    return $ticket && (int) $user->id === (int) $ticket->user_id;
});
