@extends('emails.layout')

@section('title', 'New Support Ticket Reply')

@section('content')
    <h1>New Reply Received</h1>
    <p>There is a new message in your support ticket <strong>"{{ $ticket->subject }}"</strong>.</p>
    
    <div style="background-color: #1e1e1e; border-radius: 12px; padding: 24px; margin: 24px 0; border-left: 4px solid #06b6d4; border: 1px solid rgba(255, 255, 255, 0.05);">
        <p style="margin-top: 0; margin-bottom: 12px; font-weight: bold; color: #ffffff;">From: {{ $author }}</p>
        <div style="color: #d4d4d4; line-height: 1.6; white-space: pre-wrap; font-size: 15px;">{{ $reply->message }}</div>
    </div>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $url }}" class="button">View Ticket History</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px;">If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
    <p style="font-size: 12px; word-break: break-all; color: #737373;">{{ $url }}</p>
    
    <p style="font-size: 13px; margin-top: 24px;">Thank you for using DigiPulse. We keep you informed about your website status.</p>
@endsection
