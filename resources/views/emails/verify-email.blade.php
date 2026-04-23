@extends('emails.layout')

@section('title', 'Verify Your Email Address')

@section('content')
    <h1>Verify Your Account</h1>
    <p>Welcome to DigiPulse! Please confirm your email address to unlock all features and start monitoring your digital infrastructure.</p>
    
    <div style="text-align: center;">
        <a href="{{ $url }}" class="button">Verify Email Address</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px;">If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
    <p style="font-size: 12px; word-break: break-all; color: #737373;">{{ $url }}</p>
    
    <p style="font-size: 13px; margin-top: 24px;">If you did not create an account, no further action is required.</p>
@endsection
