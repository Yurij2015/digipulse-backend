<?php

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', static function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tickets.{ticketId}', static function ($user, $ticketId) {
    $adminEmail = config('app.admin_email');
    $adminEmail = is_string($adminEmail) ? $adminEmail : null;
    $isAdminByEmail = ! empty($adminEmail)
        && ! empty($user->email_bindex)
        && $user->email_bindex === User::generateBlindIndex($adminEmail);

    if ($isAdminByEmail || $user->hasRole('admin')) {
        return true;
    }

    // User can only access their own tickets
    $ticket = SupportTicket::find($ticketId);

    return $ticket && (int) $user->id === (int) $ticket->user_id;
});
