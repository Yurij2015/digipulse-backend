@extends('emails.layout')

@section('title', 'Support Ticket Closed')

@section('content')
    <h1>Ticket Closed</h1>
    <p>Your support ticket <strong>"{{ $ticket->subject }}"</strong> has been marked as closed by an administrator.</p>
    
    <div style="background-color: #1e1e1e; border-radius: 12px; padding: 24px; margin: 24px 0; border-left: 4px solid #10b981; border: 1px solid rgba(255, 255, 255, 0.05);">
        <p style="margin-top: 0; color: #ffffff; font-weight: bold;">Status: Closed</p>
        <p style="color: #d4d4d4; line-height: 1.6; margin-bottom: 0;">We hope your issue was resolved to your satisfaction. Our team is always striving to provide the best possible support.</p>
    </div>
    
    <p style="color: #a3a3a3; font-size: 14px;">If you have further questions regarding this issue, or if the problem persists, please <strong>create a new support ticket</strong> through your dashboard.</p>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $url }}" class="button">View Ticket History</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px;">Thank you for using DigiPulse. We keep you informed about your website status.</p>
@endsection
