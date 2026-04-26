<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        /* Email client CSS resets and base styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #0a0a0a;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 32px 16px;
        }
        .card {
            background-color: #171717;
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            color: #ffffff !important;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
            margin-top: 24px;
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #737373;
            font-size: 12px;
        }
        h1 {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin-top: 0;
            margin-bottom: 16px;
            color: #ffffff;
        }
        p {
            color: #a3a3a3;
            margin-bottom: 24px;
        }
        .divider {
            height: 1px;
            background-color: rgba(255, 255, 255, 0.08);
            margin: 32px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="pulse-icon-container" style="display: inline-block; width: 48px; height: 48px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);">
                <svg width="48" height="48" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="10" fill="url(#email-logo-grad)" />
                    <g transform="translate(4, 4)">
                        <rect x="2" y="18" width="3" height="10" rx="1" fill="white" />
                        <rect x="6" y="12" width="3" height="16" rx="1" fill="white" />
                        <rect x="10" y="6" width="3" height="22" rx="1" fill="white" />
                        <rect x="14" y="14" width="3" height="14" rx="1" fill="white" />
                        <rect x="18" y="20" width="3" height="8" rx="1" fill="white" />
                        <rect x="22" y="10" width="3" height="18" rx="1" fill="white" />
                        <rect x="26" y="4" width="3" height="24" rx="1" fill="white" />
                    </g>
                    <defs>
                        <linearGradient id="email-logo-grad" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#8b5cf6" />
                            <stop offset="1" stop-color="#06b6d4" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div style="font-size: 24px; font-weight: 900; margin-top: 12px; color: #ffffff !important; text-decoration: none; letter-spacing: -1px;">{{ config('app.name') }}</div>
        </div>
        
        <div class="card">
            @yield('content')
        </div>
        
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            Reliable Website Status Monitoring
        </div>
    </div>
</body>
</html>
