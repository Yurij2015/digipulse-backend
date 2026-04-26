@extends('emails.layout')

@section('title', 'New Support Ticket')

@section('content')
    <h1>New Support Ticket Submitted</h1>
    <p>A new support ticket has been received from <strong>{{ $userStr }}</strong>.</p>
    
    <div style="background-color: #1e1e1e; border-radius: 12px; padding: 24px; margin: 24px 0; border-left: 4px solid #3b82f6; border: 1px solid rgba(255, 255, 255, 0.05);">
        <p style="margin-top: 0; margin-bottom: 8px; color: #ffffff;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
        <p style="margin-bottom: 12px;"><strong>Priority:</strong> 
            <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; 
                @if($ticket->priority === 'high') background-color: #fee2e2; color: #991b1b; 
                @elseif($ticket->priority === 'medium') background-color: #fef3c7; color: #92400e;
                @else background-color: #dcfce7; color: #166534; @endif">
                {{ strtoupper($ticket->priority) }}
            </span>
        </p>
        <div style="color: #d4d4d4; line-height: 1.6; white-space: pre-wrap; border-top: 1px solid rgba(255,255,255,0.05); pt: 16px; margin-top: 16px; font-size: 15px;">{{ $ticket->message }}</div>
    </div>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $url }}" class="button">View & Process Ticket</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px;">Admin notification. Please ensure timely response to maintain high service standards.</p>
@endsection
