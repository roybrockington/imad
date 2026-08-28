<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Reset Your Password</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #ffffff;
            color: #0a6cb5;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 4px solid #0a6cb5;
        }
        .header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }
        .content {
            padding: 30px 20px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box h2 {
            margin-top: 0;
            font-size: 18px;
            color: #1f2937;
        }
        .info-box p {
            margin: 10px 0;
            color: #4b5563;
        }
        .button {
            display: inline-block;
            background-color: #0a6cb5;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }

        /* Mobile Responsive Styles */
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 20px 15px;
            }
            .info-box, .warning-box {
                padding: 15px;
            }
            .button {
                display: block;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
        }

        /* Extra small mobile devices */
        @media only screen and (max-width: 400px) {
            .header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }}" onerror="this.style.display='none'">
            <h1>Reset Your Password</h1>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>You are receiving this email because we received a password reset request for your account.</p>

            <!-- Reset Info Box -->
            <div class="info-box">
                <h2>Password Reset Request</h2>
                <p>Click the button below to reset your password. This link will expire in 60 minutes.</p>
            </div>

            <center>
                <a href="{{ $resetUrl }}" class="button">
                    Reset Password
                </a>
            </center>

            <div class="warning-box">
                <strong>Security Notice:</strong>
                <p style="margin: 5px 0 0 0;">If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>

            <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
            <p style="word-break: break-all; color: #0a6cb5; font-size: 14px;">{{ $resetUrl }}</p>

            <p>Thank you for using Sound Service!</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p style="margin: 5px 0;">This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
