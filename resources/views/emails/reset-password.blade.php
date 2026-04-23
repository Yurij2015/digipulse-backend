@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('content')
    <h1>Reset Password</h1>
    <p>We received a request to reset your password for your DigiPulse account. This link will expire in {{ $count }} minutes.</p>
    
    <div style="text-align: center;">
        <a href="{{ $url }}" class="button">Reset Password</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px;">If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
    <p style="font-size: 12px; word-break: break-all; color: #737373;">{{ $url }}</p>
    
    <p style="font-size: 13px; margin-top: 24px;">If you did not request a password reset, no further action is required. Your account security is our priority.</p>
@endsection
